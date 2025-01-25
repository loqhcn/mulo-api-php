<?php

declare(strict_types=1);

namespace mulo\library\auth;

use mulo\exception\MuloException;
use mulo\tpmodel\user\User;
use mulo\tpmodel\auth\Admin;

class Auth
{
    protected $provider = [];
    protected $auth = null;
    protected $driver = null;
    protected $model = null;


    public function __construct()
    {
    }


    /**
     * 认证服务对象
     * 
     * @return AuthProvider 认证服务
     */
    function provider($name, $options)
    {
        $this->model = $options['model'];

        // facade设计模式
        if (!isset($this->provider[$name]) || is_null($this->provider[$name])) {
            $this->provider[$name] = new $options['provider']( $options['model'] );
        }

        return $this->provider[$name];
    }
}
