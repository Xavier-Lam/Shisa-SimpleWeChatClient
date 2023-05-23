<?php

namespace Shisa\SimpleWeChatClient;

class Header
{
    private $header;

    public function __construct(string $header)
    {
        $this->header = trim($header);
    }

    public function getName()
    {
        return trim(explode(':', $this->header, 2)[0]);
    }

    public function getValue()
    {
        return trim(explode(':', $this->header, 2)[1]);
    }

    public function __toString()
    {
        return $this->header;
    }
}
