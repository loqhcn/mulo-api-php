<?php

namespace mulo\api\logic\config;

use mulo\api\library\ModelDb;

class ConfigModelTool
{

    public $modelName = '';

    function __construct() {}

    /**
     * 构建链式方法
     * @param string $modelName 存储配置的模型名称
     * 
     */
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
     * 读取配置
     */
    function getConfig($ruleModelname)
    {
        $row = ModelDb::model($this->modelName)->where([
            'name' => $ruleModelname
        ])->find();
        $config = $row ? json_decode($row['data'], true) : null;
        return $config;
    }

   

    /**
     * 读取配置
     * 
     * @return int
     */
    function setConfig($ruleModelname, array $data)
    {
        $ruleModelData = modelTool()->getModel($ruleModelname);
        // var_dump($ruleModelData);exit;

        $row = ModelDb::model($this->modelName)->where([
            'name' => $ruleModelname
        ])->find();

        // 记录是否更新成功
        $updateNum = 0;
        // 添加
        if (!$row) {
            $rowId = ModelDb::model($this->modelName)->add([
                'name' => $ruleModelname,
                'title' => $ruleModelData['row']['title'],
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            $updateNum = 1;
        } else {
            $updateNum = ModelDb::model($this->modelName)->where(['id' => $row['id']])
                ->update([
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE)
                ]);
        }

        return $updateNum;
    }
}
