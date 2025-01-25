<?php

namespace mulo\api\logic;

use mulo\api\form\ModelForm;
use mulo\api\library\ModelDb;
use mulo\api\logic\config\ConfigModelTool;
use mulo\api\logic\trade\TradePayOrder;
use mulo\api\logic\user\UserWallet;
use mulo\exception\MuloException;

use mulo\api\traits\DefineHook;
use PDO;

/**
 * 配置模型
 * 
 * 
 * @todo [待解决] 使用不同的模型格式(json存储或者`标准配置表`存储)
 * @todo [待解决] 使用文件进行配置存储
 * 
 */
class ConfigModelApi
{

    use DefineHook;



    public $authInfo = [];
    public $modelData = [];
    /** @var string 存储配置的模型 */
    public $modelName = 'config'; //存储配置的模型名称
    /** @var string 使用了哪些规则 */
    public $ruleModels = [];

    public $models = [];


    function __construct() {}

    static function src($modelName = null)
    {
        $obj = new self();
        $modelName && $obj->model($modelName);
        return $obj;
    }

    function model($modelName)
    {
        $this->modelName = $modelName;
        return $this;
    }


    /**
     * 设置配置规则模型
     * 
     */
    function setRuleModels(array $models)
    {
        $this->ruleModels = $models;
        return $this;
    }

    function setAuthInfo($authInfo)
    {
        $this->authInfo = $authInfo;
        return $this;
    }


    /**
     * 处理接口
     * 
     * @api detail 付款订单详情(也可以使用DataModelApi的row)
     * @api pay    支付
     * @api create 创建
     * 
     */
    function handle($api = 'detail')
    {
        $data = [
            'api' => $api,
        ];

        # 读取规则
        if ($api == 'rules') {
            $configRules = [];
            foreach ($this->ruleModels as $key => $modelName) {
                $modelData = modelTool()->getModel($modelName);

                $modelForm = new ModelForm($modelData);
                $formRule = $modelForm->setHooks($this->hooks)->dest();
                $configRules[] = $formRule;
            }

            $data['config_rules'] = $configRules;
        }

        # 读取配置
        if ($api == 'get') {
            $name = input('name', '');
            if (!$name) {
                throw new MuloException("api 请输入 name");
            }

            $config = ConfigModelTool::src($this->modelName)->getConfig($name);

            $data['config'] = $config;
        }

        # 更新配置
        if ($api == 'set') {
            $name = input('name', '');
            if (!$name) {
                throw new MuloException("api 请输入 name");
            }
            $data = input('post.data/a', []);
            if (empty($data)) {
                throw new MuloException("配置数据为空");
            }
            
            $status = ConfigModelTool::src($this->modelName)->setConfig($name, $data);
            $data['status'] = $status;
        }


        $data = $this->handleHook('config.handle.end', $data);
        return $data;
    }
}
