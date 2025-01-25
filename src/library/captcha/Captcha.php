<?php

declare(strict_types=1);

namespace mulo\library\captcha;

use mulo\exception\MuloException;

class Captcha
{
    /**
     * 验证码配置
     */
    protected $config = null;

    /**
     * 当前验证驱动
     */
    protected $driver = null;

    /**
     * 当前驱动提供者
     */
    protected $provider = null;

    public function __construct($config = null)
    {
        // 设置验证码配置
        if (is_null($config)) {
            $config = config('captcha');
        }
        $this->config = config('captcha');
        // 设置默认驱动
        $this->driver($this->getDefaultDriver());
    }


    /**
     * 生成验证码
     *
     * @param string $driver 要使用的驱动
     * @return void
     */
    public function captcha($driver = null)
    {
        $this->driver($driver);

        return $this->getProvider()->captcha();
    }


    /**
     * 获取当前驱动提供者
     *
     * @return object
     */
    public function getProvider()
    {
        if (is_null($this->provider)) {
            $this->provider = $this->provider();
        }

        return $this->provider;
    }



    /**
     * 设置驱动
     *
     * @param string $driver
     * @return self
     */
    public function driver($driver)
    {
        if ($driver) {
            $this->driver = $driver;
        }
        return $this;
    }



    /**
     * 生成当前驱动提供者
     *
     * @return void
     */
    private function provider()
    {
        $class = "\\mulo\\library\\captcha\\provider\\" . ucfirst($this->driver);
        if (class_exists($class)) {
            return new $class($this, $this->getDriverConfig());
        }

        throw new MuloException('验证码驱动不支持');
    }


    /**
     * 获取默认驱动
     *
     * @return void
     */
    public function getDefaultDriver()
    {
        return $this->config['captcha'] ?? 'none';
    }



    /**
     * 获取当前驱动配置
     *
     * @return array
     */
    private function getDriverConfig()
    {   
        // var_dump($this->driver);exit;
        $driverConfig = $this->config[$this->driver];

        return $driverConfig;
    }


    /**
     * 方法转发到驱动提供者
     *
     * @param string $funcname
     * @param array $arguments
     * @return void
     */
    public function __call($funcname, $arguments)
    {
        return $this->getProvider()->{$funcname}(...$arguments);
    }
}
