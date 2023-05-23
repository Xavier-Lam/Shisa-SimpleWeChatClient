<?php

namespace Shisa\SimpleWeChatClient;

/**
 * https://developers.weixin.qq.com/doc/offiaccount/en/Getting_Started/Global_Return_Code.html
 */
class ErrorCode
{
    /**
     * Incorrect AppSecret or invalid access_token. Check the accuracy of AppSecret or
     * check whether the API is called for a proper Official Account.
     */
    const INVALID_APP_SECRET = 40001;

    /**
     * Invalid access_token. Check the validity of access_token (whether it is expired)
     * or check whether the API is called for a proper Official Account.
     */
    const INVALID_ACCESS_TOKEN = 40014;

    /**
     * access_token expired. Check the validity period of access_token. See Get
     * access_token API in Basic Support for details.
     */
    const ACCESS_TOKEN_EXPIRED = 42001;
}
