<?php

namespace mulo\api\logic;

use Exception;
use mulo\api\library\ModelDb;
use mulo\exception\MuloException;
use think\facade\Db;
use mulo\api\logic\auth\Auth;
use mulo\api\logic\auth\UserAuthProvider;
use mulo\api\library\ModelTool;

use mulo\api\traits\DefineHook;



/**
 * 用户模型接口
 * 
 */
class UserModelApi
{

    use DefineHook;


    public $modelData = null;
    public $where = null;

    /** @var string 认证场景 */
    public $authScene = 'ysx_user';

    /** @var ModelTool */
    public $tool = null;

    public $request = null;

    /**
     * 定义业务模型
     * 
     */
    public $models = [
        'user' => 'ysx_user',
        'user_wallet_log' => 'ysx_user_wallet_log',
    ];

    public $logics = [
        'mobile' => true,
    ];

    /** 缓存已加载的模型 */
    public $modelDatas = [];


    function __construct()
    {
        $this->request = request();
        $this->tool = modelTool();
    }

    function setLogic($field, $value)
    {
        $this->logics[$field] = $value;
        return $this;
    }

    function getLogic($field)
    {
        return  $this->logics[$field] ?? null;
    }

    /**
     * 构建链式方法
     * @param array $model 使用的模型(不强制使用) 
     * @example src( ['user' => 'ip_admin'] )
     * 
     * @return self
     */
    static function src(array $models = [])
    {
        $obj = new static();
        $obj->models = $models;
        return $obj;
    }

    /**
     * 设置认证场景
     * @param string $scene 场景(会根据场景验证jwt token)
     * 
     * @return static
     */
    function setScene($scene)
    {
        $this->authScene = $scene;
        return $this;
    }


    /**
     * 创建类
     * @param array $model 使用的模型(不强制使用) 
     * 
     * @return self
     */
    static function model($modelTool)
    {

        return new static($modelTool);
    }


    /**
     * 
     * @api 登录账号
     * 
     * @todo 通过账号密码登录账号
     */
    function login($username, $password)
    {
        // TODO 判断存在
        $modelData = $this->tool->getModel($this->models['user']);
        $user = ModelDb::model($modelData)->where([
            'username' => $username
        ])->find();
        // 手机号登录
        $useMobile = $this->getLogic('mobile');
        if (!$user && $useMobile) {
            $user =  ModelDb::model($modelData)->where([
                'mobile' => $username
            ])->find();
        }

        if (!$user) {
            return  handleResult(0, "账户不存在", [
                'username' => $username
            ]);
        }

        // TODO 验证密码
        if (!$this->auth()->verifyPassword($user, $password)) {
            return  handleResult(0, "密码错误", [
                'username' => $username
            ]);
        }

        // 生成令牌
        $token = $this->auth()->createToken($user);

        unset($user['password']);

        return handleResult([
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * 
     * @api 注册账号
     * 
     * @todo 通过账号密码注册
     */
    function register($username, $password)
    {
        // TODO 判断存在
        $modelData = $this->tool->getModel($this->models['user']);

        // 注册: 判断存在,创建账户
        $user = $this->auth()->register($username, $password);
        // 生成令牌
        $token = $this->auth()->createToken($user);



        return handleResult([
            'token' => $token,
            'user' => $user
        ]);
    }


    /**
     * 验证登录
     * 
     * @todo 未登录抛出异常
     * 
     * @return array @AuthInfo:[userId,authData,scene]
     */
    function checkLogin()
    {
        $token = $this->request->header('token');
        $ret = $this->auth()->setUseException(true)->checkLogin($token);

        $data = $ret['data'];

        $this->request->userId = $data['uid'];
        $this->request->authData = $data['data'];
        $this->request->scene = $this->authScene;

        return [
            'userId' => $data['uid'],
            'authData' => $data['data'],
            'scene' => $this->authScene,
        ];
    }


    /**
     * 当前用户 auth 实例
     *
     * @return \mulo\api\logic\auth\UserAuthProvider
     */
    public function auth()
    {

        $userModelData = $this->tool->getModel($this->models['user']);

        return \mulo\api\facade\Auth::provider($this->authScene, [
            // 服务提供者
            'provider' => \mulo\api\logic\auth\UserAuthProvider::class,
            // 用户模型
            'model' => $userModelData,
        ]);
    }

    /**
     * 验证密码
     * 
     */
    function verifyPassword()
    {
        $data = input('post.', []);

        list(
            $username,
            $password
        ) = [
            $data['username'],
            $data['password']
        ];

        // TODO 判断存在
        $user = MuloUser::where('username', $username)->find();
        if (!$user) {
            return  result(0, "账户不存在", [
                'username' => $username
            ]);
        }

        // TODO 验证密码
        if (!$this->auth()->verifyPassword($user, $password)) {
            return  result(0, "密码错误", [
                'username' => $username
            ]);
        }
        
        // 生成令牌
        $token = $this->auth()->createToken($user);
    }

    /**
     * 读取密码
     * 
     */
    function find() {}

    function handle($api = 'list')
    {
        $data = [
            'api' => $api,
        ];

        # 登录
        if ($api == 'login') {
            $params = input('post.', []);
            $data['params'] = $params;

            list(
                $username,
                $password
            ) = [
                $params['username'],
                $params['password']
            ];

            $ret = $this->login($username, $password);

            if ($ret['code'] != 200) {
                throw new MuloException($ret['msg'], $ret['code'], $data);
            }

            $data['token'] = $ret['data']['token'];
            $data['user'] = $ret['data']['user'];
        }

        # 注册
        if ($api == 'register') {
            $this->handleHook('user.handle.register.begin', null, null);
            $params = input('post.', []);
            $data['params'] = $params;
            list(
                $username,
                $password
            ) = [
                $params['username'],
                $params['password']
            ];

            $ret = $this->register($username, $password);

            if ($ret['code'] != 200) {
                throw new MuloException($ret['msg'], $ret['code'], $data);
            }

            $data['token'] = $ret['data']['token'];
            $data['user'] = $ret['data']['user'];
            $this->handleHook('user.handle.register.end', $data);
        }

        $data = $this->handleHook('user.handle.end', $data);

        return $data;
    }
}
