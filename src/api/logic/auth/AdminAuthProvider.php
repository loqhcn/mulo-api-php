<?php

declare(strict_types=1);

namespace mulo\api\logic\auth;

use app\mm\model\MuloUser;
use mulo\exception\MuloException;
use mulo\api\logic\auth\AuthProvider;


class AdminAuthProvider extends AuthProvider
{

    public $authOptions = [];

    /**
     * 注册账户
     * @param string $username 用户名
     * @param string $password 密码
     * 
     * @return \app\mm\model\MuloUser 用户
     */
    public function register($username, $password)
    {
        # TODO 判断存在
        $isExit = $this->modelClass::where('username', $username)->find();
        if ($isExit) {
            throw new MuloException("账户已存在", 0, [
                'username' => $username
            ]);
        }
        # TODO 创建
        $user = new MuloUser();
        $user->allowField([
            'username', 'password'
        ])->save([
            'username' => $username,
            'password' => $password,
        ]);

        return $user;
    }

    /**
     * 验证密码
     * 
     * 
     * @return bool 是否验证成功
     */
    public function verifyPassword($user, $password)
    {
        $_password = $this->encryptPassword($user['password'], $user['salt'] ?? '');
        return $_password == $password;
    }


    function getUserData($user)
    {
        return [
            'nickname' => $user['nickname'],
        ];
    }
}
