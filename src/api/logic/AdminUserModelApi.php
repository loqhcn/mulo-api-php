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
 * 后台用户模型接口
 * 
 */
class AdminUserModelApi extends UserModelApi
{
    /** @var string 认证场景 */
    public $authScene = 'ysx_admin';

    /** @var array 设置使用的模型 */
    public $models = [
        'user' => 'ysx_admin',
    ];

    // 需要复写这个方法, 否则会new self为父类
    static function model($modelTool)
    {   
        return new self($modelTool);
    }
}
