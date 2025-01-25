<?php

declare(strict_types=1);

namespace mulo\api\logic\auth;

use mulo\exception\MuloException;
use mulo\auth\lib\Jwt;


/**
 * 认证的基础功能
 * 
 */
class AuthProvider
{
    /**
     * 当前认证标识
     * @todo 用于区分 用户登录|管理员登录 等
     */
    public $scene = 'user';

    public $authOptions = [];

    public $authOptionsInit = [];


    /**
     * 登录最大尝试次数
     */
    protected $loginMaxAttempts = 5;

    /**
     * 锁定时间
     */
    protected $loginDecaySeconds = 3600;      // 锁定时间

    /**
     * 是否抛出异常
     */
    protected $useException = false;

    /**
     * 当前用户的所有角色
     */
    protected $roles = [];

    /**
     * 当前用户的所有权限
     */
    protected $rules = [];

    public $modelData = null;

    /**
     * @param array $modelData MuloModel的用户模型数组
     */
    public function __construct($modelData, $sceneName)
    {
        $this->scene = $sceneName;
        $this->modelData = $modelData;
    }

    /**
     * 获取用户信息，自动从 token 中解析用户信息
     *
     * @return object
     */
    public function user(bool $failException = false) {}


    /**
     * 创建用户令牌
     * 
     * @param \think\Model $user 用户
     * @return string JWT令牌
     */
    public function createToken($user)
    {
        $data = $this->getUserData($user);
        $ret = Jwt::getToken($user['id'], $data, $this->scene);

        return $ret;
    }

    /**
     * 验证登录
     * 
     */
    public function checkLogin($token)
    {

        // 是否已验证

        if (!$token) {
            return $this->handleResult(0, '请输入令牌');
        }

        $ret = Jwt::verifyToken($token, $this->scene);

        if ($ret['status'] != 'success') {
            return $this->handleResult(777, $ret['msg'], $ret['data'] ?? []);
        }

        return $this->handleResult(200, 'success', $ret['data']);
    }

    public function handleResult($code, $msg = "", $data = [])
    {
        if ($code != 200 && $this->useException) {
            throw new MuloException($msg, $code, $data);
        }

        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
    }

    /**
     * 设置是否抛出异常
     * @param bool $flag 是否抛出
     */
    public function setUseException($flag)
    {
        $this->useException = $flag;
        return $this;
    }


    /**
     * 获取jwt保存的data信息
     * @param all $user 用户信息
     * @return array
     */
    function getUserData($user)
    {
        return [];
    }


    /**
     * 密码加密
     * @param string $password 密码
     * @param string $password 密码盐
     */
    public function encryptPassword($password, $salt = '')
    {
        return $password;
        // return md5(md5($password) . $salt);
    }

    /**
     * 生成密码盐
     * 
     */
    public function createSalt()
    {
        $salt = mt_rand(1000, 9999);
        return $salt;
    }


    function test()
    {
        $logs = [];

        $logs[] = "123";

        return $logs;
    }
}
