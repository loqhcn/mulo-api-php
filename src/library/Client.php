<?php

declare(strict_types=1);

namespace mulo\library;

use mulo\exception\MuloException;
use GuzzleHttp\Client as HttpClient;

class Client 
{
    protected $client = null;
    /**
     * HTTP请求
     */
    public function __construct($config = null)
    {
        if (!$config) {
            $config = config('wechat.defaults.http');
        }
        $this->client = new HttpClient($config);
    }


    /**
     * GET request.
     *
     * @param string $url
     * @param array  $query
     */
    public function httpGet(string $url, array $query = [])
    {
        return $this->client->request('GET', $url, ['query' => $query]);
    }

    /**
     * POST request.
     *
     * @param string $url
     * @param array  $data
     */
    public function httpPost(string $url, array $data = [])
    {
        return $this->client->request('POST', $url, ['form_params' => $data]);
    }

    /**
     * JSON request.
     *
     * @param string $url
     * @param array  $data
     * @param array  $query
     */
    public function httpPostJson(string $url, array $data = [], array $query = [])
    {
        return $this->client->request('POST', $url, ['query' => $query, 'json' => $data]);
    }


    /**
     * 方法转发到\GuzzleHttp\Client
     *
     * @param string $funcname
     * @param array $arguments
     * @return void
     */
    public function __call($funcname, $arguments)
    {
        return $this->client->{$funcname}(...$arguments);
    }
}
