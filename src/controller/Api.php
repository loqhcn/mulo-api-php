<?php

declare(strict_types=1);

namespace mulo\controller;


use mulo\exception\MuloException;
use mulo\facade\Auth;

class Api extends Common
{

    /**
     * 当前用户 auth 实例
     *
     * @return \mulo\library\auth\provider\UserAuthProvider
     */
    public function auth()
    {
        return Auth::provider('user', [
            // 服务提供者
            'provider' => \mulo\library\auth\provider\UserAuthProvider::class,
            // 用户模型
            'model' => \app\mm\model\MuloUser::class,
        ]);
    }


    // 初始化
    protected function initialize()
    {


        parent::initialize();
    }
}
