<?php

namespace mulo\library\captcha\provider;

use think\captcha\facade\Captcha;
use think\Validate;

/**
 * 图形验证码
 */
class Code
{

    /**
     * 当前驱动配置
     */
    protected $config = null;

    /**
     * 缓存 key
     */
    protected $cacheKey = null;
    /**
     * 缓存实例
     */
    protected $cache = null;

    public function __construct($captcha, $config = [])
    {
        $this->cacheKey = md5('code:' . request()->ip());

        $this->cache = cache()->store('persistent');

        $this->config = $config;
    }


    /**
     * 生成图片验证码
     *
     * @return think\Response
     */
    public function captcha()
    {
        $response = Captcha::create('code');

        if (session('?captcha')) {
            // 将 验证码存到缓存
            $this->cache->set($this->cacheKey, session('captcha'), 1800);
            // 清除 session
            session('captcha', null);
        }

        return $response;
    }



    /**
     * 检测验证码是否正确
     *
     * @param array $params
     * @return boolean
     */
    public function check($params = [])
    {
        $code = $params['code'] ?? '';

        if (!$this->cache->has($this->cacheKey)) {
            return false;
        }

        $cache = $this->cache->get($this->cacheKey);
        $key = $cache['key'] ?? '';

        $code = mb_strtolower($code, 'UTF-8');

        $res = password_verify($code, $key);

        if ($res) {
            $this->cache->delete($this->cacheKey);
        }

        return $res;
    }
}
