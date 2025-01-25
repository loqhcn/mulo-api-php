<?php

namespace mulo\api\logic\data_model;

use mulo\api\library\ModelDb;

/**
 * 数据注入处理
 * 
 * @example tree形数据,查询row时, 向上查询注入列表
 * 
 */
class InjectsHandle
{

    public $modelData = null;

    /** @var array api要返回的数据 */
    public $data = null;


    /** @var array 需要注入的 */
    public $injects = null;

    function __construct() {}

    /**
     * 构建链式方法
     * 
     * @param string $modelName 对应模型名称
     * 
     */
    static function src($data = null)
    {
        $obj = new self();

        $data && $obj->setData($data);

        return $obj;
    }

    /**
     * 设置模型数据
     * 
     * @param array $modelData 模型数据
     * 
     */
    function model($modelData)
    {
        $this->modelData = $modelData;
        return $this;
    }

    function setData($data)
    {
        $this->data = $data;
        return $this;
    }


    function getData()
    {
        return $this->data;
    }

    function setInjects(array $injects)
    {
        $this->injects = $injects;
        return $this;
    }


    function eachParent($currentRow, $list) {}

    /**
     * 导出新的数据
     */
    function dest()
    {
        // $this->data['plist'] = 
        foreach ($this->injects as $key => $inject) {
            // 树形状查询
            if ($inject['logic'] == 'tree') {
                $this->data['tree'] = [
                    'inject' => $inject,
                    'list' => TreeDataModelTool::src($this->modelData)->getParentTree($this->data['row']),
                ];
            }
        }
        return $this->data;
    }
}
