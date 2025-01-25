<?php

declare(strict_types=1);

namespace mulo\library;

use mulo\exception\MuloException;
use think\model\Collection;
use mulo\facade\Client;

class Websocket
{

    protected $config = null;

    protected $base_uri = null;

    public function __construct()
    {
        $this->config = config('chat.system');

        $inside_host = $this->config['inside_host'] ?? '127.0.0.1';
        $inside_port = $this->config['inside_port'] ?? '9191';

        $this->base_uri = 'http://' . $inside_host . ':' . $inside_port;
    }



    public function notification($data) 
    {
        $response = Client::httpPost($this->base_uri . '/notification', $data);

        // 获取结果
        $result = $response->getBody()->getContents();

        return $result == 'ok' ? true : $result;
    }
}
