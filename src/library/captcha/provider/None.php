<?php

namespace mulo\library\captcha\provider;

use think\Validate;

/**
 * 没有验证码
 */
class None
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
        $this->cacheKey = md5('none:' . request()->ip());

        $this->cache = cache()->store('persistent');
    }


    /**
     * 生成图片验证码
     *
     * @return think\Response
     */
    public function captcha()
    {
        return json();
    }


    /**
     * 添加验证码验证规则
     *
     * @return void
     */
    public function validateExtend()
    {
        Validate::maker(function ($validate) {
            $validate->extend('sa_captcha', function ($code) {
                return $this->check();
            }, '');
        });
    }


    /**
     * 检测验证码是否正确
     *
     * @param array $params
     * @return boolean
     */
    public function check($params = [])
    {
        return true;
    }
}
