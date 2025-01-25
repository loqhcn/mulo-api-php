<?php

namespace mulo\library\captcha\provider;

use think\Validate;
use mulo\exception\MuloException;
use mulo\facade\Client;

/**
 * 极验验证码
 */
class Geetest
{
    /**
     * 极验版本
     */
    const GT_SDK_VERSION = 'php_3.0.0';

    /**
     * 请求客户端
     */
    private $base_uri = 'http://api.geetest.com';

    /**
     * 缓存 key
     */
    protected $cacheKey = null;

    /**
     * 极验配置 captcha_id
     */
    protected $captcha_id;

    /**
     * 极验配置 private_key
     */
    protected $private_key;

    /**
     * 缓存实例
     */
    protected $cache = null;

    public function __construct($captcha, $config = [])
    {
        $this->captcha_id = $config['captcha_id'] ?? '';
        $this->private_key = $config['private_key'] ?? '';

        if (!$this->captcha_id || !$this->private_key) {
            throw new MuloException('缺少极验验证码配置');
        }

        $this->cacheKey = md5('geetest:' . request()->ip());

        $this->cache = cache()->store('persistent');
    }



    /**
     * 获取极验验证码参数
     *
     * @return think\response
     */
    public function captcha()
    {
        $data = [
            'client_type' => 'web',
            'ip_address' => request()->ip()
        ];
        $result = $this->preProcess($data);

        $this->cache->set($this->cacheKey, [
            'gtserver' => $result['success'],
        ], 1800);

        return $result;
    }


    /**
     * 检测验证码是否正确
     *
     * @param array $params
     * @return boolean
     */
    public function check($params = [])
    {
        $geetest_challenge = $params['geetest_challenge'] ?? request()->param('geetest_challenge');
        $geetest_validate = $params['geetest_validate'] ?? request()->param('geetest_validate');
        $geetest_seccode = $params['geetest_seccode'] ?? request()->param('geetest_seccode');

        $data = [
            'client_type' => 'web',
            'ip_address' => request()->ip()
        ];

        $cache = $this->cache->get($this->cacheKey);
        $gtserver = $cache['gtserver'] ?? 0;

        if ($gtserver == 1) {
            if ($this->successValidate($geetest_challenge, $geetest_validate, $geetest_seccode, $data)) {
                return true;
            }
        } else {
            if ($this->failValidate($geetest_challenge, $geetest_validate, $geetest_seccode)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Check Geetest server is running or not.
     *
     * @param null $user_id
     * @return int
     */
    public function preProcess($param, $new_captcha = 1)
    {
        $data = [
            'gt' => $this->captcha_id,
            'new_captcha' => $new_captcha
        ];
        $data = array_merge($data, $param);
        $query = http_build_query($data);
        $url = "/register.php?" . $query;
        $challenge = $this->sendRequest($url);

        if (strlen($challenge) != 32) {
            return $this->failbackProcess();
        }
        return $this->successProcess($challenge);
    }

    /**
     * @param $challenge
     */
    private function successProcess($challenge)
    {
        $challenge = md5($challenge . $this->private_key);
        $result = [
            'success' => 1,
            'gt' => $this->captcha_id,
            'challenge' => $challenge,
            'new_captcha' => 1
        ];
        return $result;
    }

    /**
     *
     */
    private function failbackProcess()
    {
        $rnd1 = md5(rand(0, 100));
        $rnd2 = md5(rand(0, 100));
        $challenge = $rnd1 . substr($rnd2, 0, 2);
        $result = [
            'success' => 0,
            'gt' => $this->captcha_id,
            'challenge' => $challenge,
            'new_captcha' => 1
        ];

        return $result;
    }


    /**
     * Get success validate result.
     *
     * @param      $challenge
     * @param      $validate
     * @param      $seccode
     * @param null $user_id
     * @return boolean
     */
    public function successValidate($challenge, $validate, $seccode, $param, $json_format = 1)
    {
        if (!$this->checkValidate($challenge, $validate)) {
            return 0;
        }
        $query = [
            "seccode" => $seccode,
            "timestamp" => time(),
            "challenge" => $challenge,
            "captchaid" => $this->captcha_id,
            "json_format" => $json_format,
            "sdk" => self::GT_SDK_VERSION,
        ];
        $query = array_merge($query, $param);
        $url = "/validate.php";
        $result = $this->postRequest($url, $query);
        if ($result && $result['seccode'] == md5($seccode)) {
            return true;
        }
        return false;
    }

    /**
     * Get fail result.
     *
     * @param $challenge
     * @param $validate
     * @param $seccode
     * @return int
     */
    public function failValidate($challenge, $validate, $seccode)
    {
        if (md5($challenge) == $validate) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * @param $challenge
     * @param $validate
     * @return bool
     */
    private function checkValidate($challenge, $validate)
    {
        if (strlen($validate) != 32) {
            return false;
        }
        if (md5($this->private_key . 'geetest' . $challenge) != $validate) {
            return false;
        }

        return true;
    }

    /**
     * GET
     *
     * @param $url
     * @return mixed|string
     */
    private function sendRequest($url)
    {
        $response = Client::httpGet($this->base_uri . $url);

        if ($response->getStatusCode() == 200) {
            $body = $response->getBody()->getContents();
            return $body;
        }

        return false;
    }



    /**
     * @param       $url
     * @param array $postdata
     * @return mixed|string
     */
    private function postRequest($url, $postdata = '')
    {
        $response = Client::httpPost($this->base_uri . $url, $postdata);

        if ($response->getStatusCode() == 200) {
            $body = $response->getBody()->getContents();

            // 处理 body 格式
            $body = is_string($body) ? json_decode($body, true) : $body;
            $body = $body ? : $body;
            return $body;
        }

        return false;
    }
}
