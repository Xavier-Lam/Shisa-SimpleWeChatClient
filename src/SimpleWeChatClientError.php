<?php

namespace Shisa\SimpleWeChatClient;

use Exception;

class SimpleWeChatClientError extends Exception
{
    private $context = [];
    private $request = null;
    private $response = null;

    const CACHE_NOT_SET = 1;
    const CURL_ERROR = 2;
    const UNEXPECTED_HTTP_STATUS = 3;
    const MALFORMED_JSON = 4;
    const RESPONSE_CODE_ERROR = 5;
    const INVALID_RESPONSE = 6;

    public static function create(
        int $code,
        string $message,
        array $context = []
    ) {
        $exc = new static($message, $code);
        $exc->context = $context;
        return $exc;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function withRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function withResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }
}
