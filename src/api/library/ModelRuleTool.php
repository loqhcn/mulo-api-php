<?php

namespace mulo\api\library;

use app\mm\model\MuloModel;
use app\mm\model\MuloModelItem;
use mulo\exception\MuloException;
use mulo\facade\Db as MuloFacadeDb;

/**
 * 模型规则操作
 * @todo 用于管理模型的渲染规则(列表,表单,表格)
 * 
 */
class ModelRuleTool
{

    public $items = [];

    function __construct() {}

    static function src($items = null)
    {
        $obj = new self();

        if ($items && !empty($items)) {
            $obj->setFields($items);
        }

        return $obj;
    }


    function setFields(array $items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * 批量设置规则下的一个参数
     * @param array $names 修改的规则的名称
     * @param array $attrs 设置属性
     * 
     */
    function setFieldAttrs(array $names, $attrs = [])
    {
        foreach ($this->items as $key => &$rule) {
            //为选中的名称更新属性
            if (in_array($rule['name'], $names)) {
                foreach ($attrs as $field => $value) {
                    $rule[$field] = $value;
                }
            }
        }
        unset($rule);

        return $this;
    }

    /**
     * 添加一个规则
     * @param array $item 添加的规则(列表,表单,表格)
     * 
     */
    function addField($item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * 添加多个个规则
     * @param array $item 添加的规则(列表,表单,表格)
     * 
     */
    function addFields(array $items)
    {
        foreach ($items as $key => $item) {
            $this->items[] = $item;
        }
        return $this;
    }

    /**
     * 移除字段
     * @param array $removeNames 移除的字段的名称
     * 
     * @example removeFields(['user_id','relation_id'])
     * 
     */
    function removeFields(array $removeNames)
    {
        $_rules = [];
        foreach ($this->items as $key => &$rule) {
            //为选中的名称更新属性
            if (in_array($rule['name'], $removeNames)) {
                continue;
            }
            $_rules[] = $rule;
        }
        $this->items = $_rules;
        return $this;
    }

    /**
     * 获取修改后的规则
     * 
     */
    function dest()
    {
        return $this->items;
    }

    function destDefaultRow()
    {
        // TODO 默认值
        $defaultRow = [];
        foreach ($this->items as $key => $item) {
            $defaultRow[$item['name']] = $item['default'] ?? '';
        }
        
        return $defaultRow;
    }
}
