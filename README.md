# SimpleWeChatClient

    use Shisa\SimpleWeChatClient\DummyCache;
    use Shisa\SimpleWeChatClient\SimpleWeChatClient;
    use Shisa\SimpleWeChatClient\SimpleWeChatClientError;

    require_once './vendor/autoload.php';

    $client = new SimpleWeChatClient($appId, $appSecret, $options);
    $client->setCache(new DummyCache());

    $response = $client->requestWithAccessToken('POST', '/cgi-bin/message/subscribe/send', [], [
        'touser'      => $openid,
        'template_id' => $templateId,
        'data'        => $data,
        'page'        => $url
    ]);
    $data = $response->getJson();

    $response = $client->requestWithAccessToken('POST', '/wxa/getwxacodeunlimit', [], [
        'scene' => $scene,
        'page' => 'pages/quiz/quiz',
        'width' => 430,
        'auto_color' => false,
        'line_color' => ['r' => '0', 'g' => '0', 'b' => '0'],
        'is_hyaline' => false
    ]);