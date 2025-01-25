<?php

namespace mulo\api\form;

use mulo\api\traits\DefineHook;
use mulo\api\library\ModelRuleTool;


/**
 * 模型表单功能
 * 
 */
class ModelForm extends ModelRuleTool
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
     * 移除ID关联
     * 
     */
    function removeRelationFields()
    {
        $names = [];
        foreach ($this->items as $key => $item) {
            if (in_array($item['type'], ['relation_id'])) {
                $names[] = $item['name'];
            }
        }
        $this->removeFields($names);
        return $this;
    }

    /**
     * 移除基础字段
     * @todo 移除关联字段
     * 
     */
    function removeBaseFields()
    {
        $this->removeFields(['deletetime', 'createtime', 'updatetime', 'id']);

        $removeRalation = $this->handleHook('api.handle.form_rule.options.remove_relation', true, 'bool');
        if ($removeRalation) {
            $this->removeRelationFields();
        }

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
     * 
     */
    function dest()
    {

        $this->removeBaseFields();

        $_items = [];

        
        foreach ($this->items as $key => $item) {
            $_item = [
                'id' => $item['id'] ?? 0,
                'name' => $item['name'],
                'title' => $item['title'],
                'type' => $item['type'],
                'component' => $this->getModelTypeComponent($item['type']),
                'notnull' => $item['notnull'] ?? false,
                'describe' => $item['describe'] ?? '',
                'default' => $item['default'] ?? '',
                'weight' => $item['weight'] ?? 0,
            ];
            
            $_item = $this->handleHook('api.handle.form_rule.dest.item', $_item);
            $_items[] = $_item;
        }
        $this->items = $_items;

        $this->handleHook('api.handle.form_rule.dest.list', $this, 'none');

        // TODO 默认值
        $defaultRow = [];
        foreach ($this->items as $key => $item) {
            $defaultRow[$item['name']] = $item['default'] ?? '';
        }
        
        return [
            'title' => $this->modelData['row']['title'],
            'name' => $this->modelData['row']['name'],
            'items' => $this->items,
            'defaultRow' => $defaultRow,
        ];
    }
}
