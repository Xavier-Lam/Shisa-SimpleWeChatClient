<?php

namespace Shisa\SimpleWeChatClient;

use Psr\SimpleCache\CacheInterface;
use RuntimeException;

class SimpleWeChatClient
{
    private $options = [
        'cache_prefix' => 'shisa:wx',

        'access_token_url' => 'https://api.weixin.qq.com/cgi-bin/token',
        'access_token_expires_in' => 7200,
        'base_url' => 'https://api.weixin.qq.com',

        'timeout' => 5
    ];

    private $appid;
    private $secret;
    private $accessToken;
    private $cache;

    public function __construct(string $appid, string $secret, array $options = [])
    {
        $this->appid = $appid;
        $this->secret = $secret;
        $this->options = $options + $this->options;
    }

    public function request(
        string $method,
        string $url,
        $params = [],
        $data = null,
        array $options = []
    ) {
        $options += $this->options;

        $request = new Request(
            $method,
            static::getFullUrl($url, $options),
            $params,
            $data
        );

        return $this->send($request);
    }

    public function requestWithAccessToken(
        string $method,
        string $url,
        $params = [],
        $data = null,
        array $options = []
    ) {
        $options += $this->options;
        $params['access_token'] = $this->getAccessToken();

        $request = new Request(
            $method,
            static::getFullUrl($url, $options),
            $params,
            $data
        );

        try {
            return $this->send($request);
        } catch (SimpleWeChatClientError $e) {
            if (
                $e->getCode() === SimpleWeChatClientError::RESPONSE_CODE_ERROR
                && in_array($e->getContext()['errcode'], [
                    ErrorCode::ACCESS_TOKEN_EXPIRED,
                    ErrorCode::INVALID_ACCESS_TOKEN,
                    ErrorCode::INVALID_APP_SECRET
                ])
            ) {
                $accessToken = $this->fetchAccessToken()
                    ->getJson()['access_token'];
                $params = $request->getParams();
                $params['access_token'] = $accessToken;
                $request = $request->withParams($params);
                return $this->send($request, $options);
            }
            throw $e;
        }
    }

    public function getAccessToken()
    {
        if (isset($this->accessToken)) {
            return $this->accessToken;
        }
        $key = $this->getCacheKey('token');
        $accessToken = $this->getCache()->get($key);
        if (empty($accessToken)) {
            $response = $this->fetchAccessToken();
            $accessToken = $response->getJson()['access_token'];
        }
        $this->accessToken = $accessToken;

        return $accessToken;
    }

    public function fetchAccessToken($options = [])
    {
        $options += $this->options;
        $params = [
            'grant_type' => 'client_credential',
            'appid' => $this->appid,
            'secret' => $this->secret
        ];
        $request = new Request(
            'GET',
            $options['access_token_url'],
            $params
        );
        $response = $this->send($request, $options);
        $data = $response->getJson();
        $accessToken = $data['access_token'];
        if (!isset($data['access_token']) || !$data['access_token']) {
            throw SimpleWeChatClientError::create(
                SimpleWeChatClientError::RESPONSE_CODE_ERROR,
                _("AccessToken not found in response."),
                ['appid' => $this->appid]
            )->withRequest($request)
                ->withResponse($response);
        }

        $cacheKey = $this->getCacheKey('token');
        $expiresIn = $data['expires_in'] ?: $options['access_token_expires_in'];
        $this->getCache()->set($cacheKey, $accessToken, $expiresIn);

        return $response;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * @param mixed $data
     * @param array $options
     * @throws SimpleWeChatClientError
     * @return Response
     */
    public function send(
        Request $request,
        array $options = []
    ) {
        $options += $this->options;

        $url = $request->getUrl();

        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getMethod());

        // Add payload
        $payload = $request->getPayload();
        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        // Add headers
        $headers = $request->getHeaders();
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // options
        if (isset($options['proxy'])) {
            curl_setopt($ch, CURLOPT_PROXY, $options['proxy']);
        }
        if (isset($options['verify']) && !$options['verify']) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        if (isset($options['timeout'])) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
        }

        // Execute cURL request and handle errors
        $ret = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!$errno) {
            $log['response'] = $ret;
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $isJson = strpos($contentType, 'application/json') !== false;

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headersStr = substr($ret, 0, $headerSize);
            $headers = array_values(
                array_filter(
                    array_map(function ($o) {
                        return trim($o);
                    }, explode("\n", $headersStr))
                )
            );
            $body = substr($ret, $headerSize);
        }
        curl_close($ch);

        // Check for errors and throw exception if necessary
        if ($errno) {
            throw SimpleWeChatClientError::create(
                SimpleWeChatClientError::CURL_ERROR,
                sprintf(_("cURL error (%d): %s"), $errno, $error),
                [
                    'errno' => $errno,
                    'error' => $error
                ]
            )->withRequest($request);
        }

        $response = new Response($headers, $body);
        // Throw exception if HTTP status code is not successful
        if ($statusCode < 200 || $statusCode >= 300) {
            throw SimpleWeChatClientError::create(
                SimpleWeChatClientError::UNEXPECTED_HTTP_STATUS,
                sprintf(_("HTTP status code %d"), $statusCode),
                [
                    'status_code' => $statusCode
                ]
            )->withRequest($request)
                ->withResponse($response);
        }

        if ($isJson) {
            try {
                $json = $response->getJson();
            } catch (SimpleWeChatClientError $e) {
                throw $e->withRequest($request)
                    ->withResponse($response);
            }
            if (
                isset($json['errcode']) &&
                !($json['errcode'] === 0 || $json['errcode'] === "0")
            ) {
                $message = $json['errmsg'] ?: sprintf(_("Unexpected response code: %s"), $json['errcode']);
                throw SimpleWeChatClientError::create(
                    SimpleWeChatClientError::RESPONSE_CODE_ERROR,
                    $message,
                    [
                        'errcode' => $json['errcode']
                    ]
                )->withRequest($request)
                    ->withResponse($response);
            }
        }
        return $response;
    }

    /**
     * @return CacheInterface
     */
    public function getCache()
    {
        if (!isset($this->cache)) {
            throw new RuntimeException(
                _('You have not set up a cache implement. Call `setCache` method first.')
            );
        }
        return isset($this->cache) ? $this->cache : null;
    }

    /**
     * Set up a cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    protected function getCacheKey($name)
    {
        return implode(":", [
            $this->options['cache_prefix'],
            $this->appid,
            $name
        ]);
    }

    private static function getFullUrl($url, $options)
    {
        // Prepend base url
        if (!preg_match('/^(http|https):/', $url)) {
            $url = rtrim($options['base_url'], '/') . '/' . ltrim($url, '/');
        }
        return $url;
    }
}
