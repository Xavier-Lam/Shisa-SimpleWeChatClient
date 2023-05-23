<?php

namespace Shisa\SimpleWeChatClient;

class Response
{
    private $headers;
    private $body;
    private $json;

    public function __construct(array $headers, string $body)
    {
        $this->headers = array_map(function ($header) {
            return new Header($header);
        }, $headers);
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return Header[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    public function getJson()
    {
        if (!isset($this->json)) {
            $data = json_decode($this->getBody(), true);
            if ($errno = json_last_error()) {
                throw SimpleWeChatClientError::create(
                    SimpleWeChatClientError::MALFORMED_JSON,
                    sprintf(_("Malformed json (%d): %s"), $errno, json_last_error_msg()),
                    [
                        'errno' => $errno,
                        'error' => json_last_error_msg()
                    ]
                );
            }
            if (!is_array($data)) {
                throw SimpleWeChatClientError::create(
                    SimpleWeChatClientError::MALFORMED_JSON,
                    _("Malformed json.")
                );
            }
            $this->json = $data;
        }
        return $this->json;
    }
}
