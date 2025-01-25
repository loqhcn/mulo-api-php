<?php

namespace mulo\api\list;

use app\mm\model\MuloModel;
use app\mm\model\MuloModelItem;
use mulo\exception\MuloException;
use mulo\facade\Db as MuloFacadeDb;

/**
 * [链式]模型规则操作
 * 
 * @todo 用于管理模型的渲染规则(列表和表单)
 * 
 */
class ListOperateTool
{

    public $rules = [];

    function __construct() {}

    static function src(array $rules = null)
    {
        $obj = new self();

        if ($rules && !empty($rules)) {
            $obj->setFields($rules);
        }

        return $obj;
    }


    function setFields(array $rules)
    {
        $this->rules = $rules;
        return $this;
    }


    /**
     * 载入默认操作
     * 
     */
    function useDefault()
    {
        $this->rules[] = [
            'title' => '添加',
            'action' => 'add',
            'weight' => 99,
            'case' => []
        ];

        $this->rules[] = [
            'title' => '编辑',
            'action' => 'edit',
            'weight' => 99,
            'case' => []
        ];

        $this->rules[] = [
            'title' => '删除',
            'action' => 'delete',
            'weight' => 0,
            'case' => []
        ];
        return $this;
    }

    /**
     * 移除操作事件
     * @param array $actions 操作事件
     * @example removeActions(['add','delete'])
     */
    function removeActions(array $actions)
    {
        $_rules = [];
        foreach ($this->rules as $key => $li) {
            if (in_array($li['action'], $actions)) {
                continue;
            }
            $_rules[] = $li;
        }
        $this->rules = $_rules;
        return $this;
    }

    function removeFields(array $removes)
    {

        return $this->rules;
    }



    /**
     * 获取修改后的规则
     * 
     */
    function dest()
    {

        return $this->rules;
    }
}
