<?php

namespace mulo\library;

use app\mm\model\MuloModel;
use app\mm\model\MuloModelItem;
use Exception;
use mulo\exception\MuloException;
use mulo\facade\Db as MuloFacadeDb;

/**
 * 网络请求
 * @todo 参考axios的使用方式开发
 * @todo [待处理]$beforeRequest请求前置未使用
 * 
 * 
 */
class Http
{


    public $baseUrl = "";
    public $headers = [];
    public $beforeRequest = null;
    public $beforeResponse = null;

    public function __construct() {}

    /**
     * 设置请求
     * @param array $config 设置(baseUrl,headers)
     * 
     * @example src(['baseUrl'=>'','headers=>[]])
     */
    static function src($config = null)
    {
        $obj = new self();
        if ($config) {
            $obj->setConfig($config);
        }
        return $obj;
    }

    /**
     * 设置请求
     * @param array $config 设置(baseUrl,headers)
     * 
     * @example setConfig(['baseUrl'=>'','headers=>[]])
     */
    public function setConfig(array $config)
    {
        $this->baseUrl = rtrim($config['baseUrl'] ?? '', '/');
        $this->headers = $config['headers'] ?? [];
        return $this;
    }

    /**
     * 设置请求拦截器
     */
    public function setBeforeRequest(callable $call)
    {
        $this->beforeRequest = $call;
        return $this;
    }

    /**
     * 设置响应拦截器
     * 
     */
    public function setBeforeResponse(callable $call)
    {
        $this->beforeResponse = $call;
        return $this;
    }

    function formatHeaders($headers)
    {
        $formattedHeaders = [];
        foreach ($headers as $key => $value) {
            
            $formattedHeaders[] = "$key: $value";
        }
        return $formattedHeaders;
    }

    public function request($method, $url, $data = [], $options = [])
    {
        
        # url
        $fullUrl = $this->baseUrl . $url;

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);


        # option
        if (isset($options['headers'])) {
            foreach ($options['headers'] as $key => $value) {
                $this->headers[$key] = $value;
            }
        }
        // 可设置不校验ssl证书
        $isVerify = $options['verify'] ?? true;
        if (!$isVerify) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        # method & data
        if ($method === 'POST' || $method === 'PUT') {

            if (!isset($this->headers['Content-Type']) || !$this->headers['Content-Type']) {
                $this->headers['Content-Type'] = 'application/json';
            }

            if ($this->headers['Content-Type'] == 'application/json') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        # header
        if (!empty($this->headers)) {
            $headers = $this->formatHeaders($this->headers);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }


        # request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        # result
        $dataType = 'json';
        $data = $response ? json_decode($response, true) : null;
        // 解析失败
        if (!$data) {
            $dataType = 'text';
            $data = $response;
        }

        $res =  [
            'code' => $httpCode,
            'dataType' => $dataType,
            'data' => $data,
        ];
        if ($this->beforeResponse) {
            $res = call_user_func($this->beforeResponse, $res);
        }
        return $res;
    }

    public function get($url, $params = [], $options = [])
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->request('GET', $url, [], $options);
    }

    /**
     * post请求
     * @todo Content-Type : application/json
     * 
     */
    public function post($url, $data = [], $options = [])
    {
        // throw new MuloException('dev-post', 0, [
        //     'data'=>$data,
        // ]);
        return $this->request('POST', $url, $data, $options);
    }
}
