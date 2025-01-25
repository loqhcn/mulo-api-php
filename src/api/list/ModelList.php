<?php

namespace mulo\api\list;

use mulo\api\library\OperateTool;
use mulo\api\traits\DefineHook;

/**
 * 模型列表功能
 * 
 */
class ModelList
{
    use DefineHook;
    public $modelData = null;
    public $items = [];
    public $title = "";
    public $client = 'uniapp';

    function __construct($modelData)
    {
        $this->modelData = $modelData;

        $this->title = $this->modelData['row']['title'];
        $this->items = $this->modelData['items'];
    }

    /**
     * 移除字段
     * 
     */
    function removeFields(array $fields)
    {
        $_items = [];
        foreach ($this->items as $key => $item) {
            if (in_array($item['name'], $fields)) {
                continue;
            }
            $_items[] = $item;
        }
        $this->items = $_items;
        return $this;
    }

    function removeRelationFields()
    {
        $_items = [];
        foreach ($this->items as $key => $item) {
            if (in_array($item['type'], ['relation_id'])) {
                continue;
            }
            $_items[] = $item;
        }
        $this->items = $_items;
        return $this;
    }

    /**
     * 移除基础字段
     * @todo 移除关联字段
     * 
     */
    function removeBaseFields()
    {
        $this->removeFields(['deletetime']);

        // $this->removeRelationFields();

        return $this;
    }

    /**
     * 获取模型使用的组件
     * 
     */
    function getModelTypeComponent($type)
    {

        return $type;

        // 默认直接输入
        return 'input';
    }



    /**
     * 输出处理结果
     * 
     */
    function dest()
    {
        $this->removeBaseFields();

        $this->handleHook('api.handle.list_rule.dest.list', $this, 'none');

        $_items = [];
        // 载入基础字段
        $_items[] = [
            'id' => 0,
            'name' => 'id',
            'title' => 'ID',
            'type' => 'number',
            'component' => 'text',
            'describe' => 'id',
            'weight' => 99,
            'fixed' => 'left',
            'width' => 80,
        ];

        // 载入字段
        foreach ($this->items as $key => $item) {
            $_item = [
                'id' => $item['id'],
                'name' => $item['name'],
                'title' => $item['title'],
                'type' => $item['type'],
                'component' => $this->getModelTypeComponent($item['type']),
                'describe' => $item['notnull'],
                'weight' => $item['weight'],
                'fixed' => false,
            ];

            $_item = $this->handleHook('model_list.dest', $_item);
            $_items[] = $_item;
        }

        $operates = ListOperateTool::src()
            ->useDefault()
            ->dest();

        return [
            'title' => $this->modelData['row']['title'],
            'items' => $_items,
            'operates' => $operates,
        ];
    }
}
