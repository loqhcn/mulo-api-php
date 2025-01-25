<?php

declare(strict_types=1);

namespace mulo\middleware;

use mulo\facade\Auth;

class CheckLogin
{
    public $sceneOptions = [
        'user' => [
            // 服务提供者
            'provider' => \mulo\library\auth\provider\UserAuthProvider::class,
            // 用户模型
            'model' => \app\mm\model\MuloUser::class,
        ],
        'admin' => [
            // 服务提供者
            'provider' => \mulo\library\auth\provider\AdminAuthProvider::class,
            // 管理员模型
            'model' => \app\mm\model\MuloUser::class,
        ],
    ];

    public function handle($request, \Closure $next, $scene = null)
    {

        // TODO 解析令牌
        $token = $request->header('token');
        $auth = $this->auth($scene)->setUseException(true);
        $ret = $auth->checkLogin($token);
        $data = $ret['data'];
        // var_dump($ret);exit;
        
        $request->userId = $data['uid'];
        $request->authData = $data['data'];
        $request->scene = $scene;

        return $next($request);
    }


    /**
     * 当前用户 auth 实例
     *
     * @return \mulo\library\auth\AuthProvider
     */
    public function auth($scene)
    {
        return Auth::provider($scene, $this->sceneOptions[$scene]);
    }
}
