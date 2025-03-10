<?php

namespace mulo\api\logic\app;

use Exception;
use mulo\api\library\ModelDb;
use mulo\exception\MuloException;
use think\facade\Db;
use mulo\api\logic\auth\Auth;
use mulo\api\logic\auth\UserAuthProvider;
use mulo\api\library\ModelTool;

use mulo\api\traits\DefineHook;
use mulo\library\data_type\PhpDataType;

/**
 * 后台用户模型接口
 * 
 */
class AppModelApi
{

    public $options = [
        'app_id' => 0,
        'app_name' => '',
        'api_name' => '',
    ];
    /**
     * @var array 流水线处理数据
     * @todo 下方的一些函数只记录如何处理, 不实际处理
     * @todo 在dest时再进行实际处理
     * 
     */
    public $pipelineData = [];


    function __construct()
    {
        // 
    }

    /**
     * 构建显示方法
     * 
     * @param array $options 选项
     * 
     * @return self
     */
    static function src($options=[])
    {
        $obj = new self();

        if(isset($options['app_id'])) {
            $obj->appId($options['app_id']);
        }

        if(isset($options['app_name'])) {
            $obj->appName($options['app_name']);
        }

        return $obj;
    }

    function appId($appId)
    {
        $this->options['app_id'] = $appId;
        return $this;
    }

    function appName($appName)
    {
        $this->options['app_name'] = $appName;
        return $this;
    }




    function api($apiName = 'info')
    {
        $this->options['api_name'] = $apiName;
        return $this;
    }

    function dest() {
        $apiName = $this->options['api_name'];
        $appId = $this->options['app_id'];
        $appName = $this->options['app_name'];

        // 应用数据
        

        $data = [
            'api'=>$apiName,
            'appId'=>$appId,
        ];

        /**
         * 加载菜单
         */
        if($apiName == 'menus') {
            $name = input('useName',[]);
            $appData = modelTool()->getAppInfo($appId);

            $menuOptions = PhpDataType::arrayFind($appData['menus'],function($li) use($name) {
                if($li['name'] == $name) {
                    return true;
                }
                return false;
            });

            $data['menus'] = $menuOptions['items'];
        }

        return $data;
    }
}
