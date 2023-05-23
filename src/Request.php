<?php

namespace Shisa\SimpleWeChatClient;

class Request
{
    private $method;
    private $url;
    private $params;
    private $data;
    private $payload;
    private $headers;

    public function __construct(
        string $method,
        string $url,
        array $params = [],
        $data = null,
        array $headers = []
    ) {
        $this->method = strtoupper($method);
        $this->url = $url;
        $this->params = $params;
        $this->data = $data;
        $this->headers = $headers;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getUrl()
    {
        $url = $this->url;
        if ($params = $this->getParams()) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

    public function getHeaders()
    {
        $this->getPayload();
        return $this->headers;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function withParams(array $params)
    {
        $cloned = clone $this;
        $cloned->params = $params;
        return $cloned;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getPayload()
    {
        if (!isset($this->payload)) {
            $this->payload = false;
            $data = $this->getData();
            if ($data) {
                if (is_array($data)) {
                    $this->payload = json_encode($data);
                    $this->headers[] = new Header('Content-Type: application/json');
                } else {
                    $this->payload = $data;
                }
            }
        }
        return $this->payload;
    }
}
