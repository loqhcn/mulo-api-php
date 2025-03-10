<?php

namespace mulo\api\library;

use mulo\exception\MuloException;
use mulo\library\data_type\PhpDataType;
use stdClass;

/**
 * 模型处理工具
 * 
 */
class ModelHandle
{

    /**
     * @var string 模型名称
     */
    public $modelName = '';
    public $modelLoadType = '';
    public $modelLoadTypeOption = '';

    public $options = [];

    /**
     * @var array 流水线处理数据
     * @todo 下方的一些函数只记录如何处理, 不实际处理
     * @todo 在dest时再进行实际处理
     * 
     */
    public $pipelineData = [];

    function __construct() {}

    /**
     * 
     * @return self
     */
    static function src(string $modelName)
    {
        $obj = new self();
        if ($modelName) {
            $obj->setModel($modelName);
        }
        return $obj;
    }

    function setModel($modelName)
    {
        $this->modelName = $modelName;
        return $this;
    }

    /**
     * 渲染为[列表],[表格]的规则
     * @todo 记录类型
     */
    function asList($optionName = '')
    {
        $this->modelLoadType = 'list';
        $this->modelLoadTypeOption = $optionName;

        return $this;
    }

    /**
     * 渲染为[列表],[表格]的规则
     * @todo 记录类型
     */
    function asTable($optionName = '')
    {
        $this->modelLoadType = 'table';
        $this->modelLoadTypeOption = $optionName;

        return $this;
    }


    /**
     * 渲染为[列表],[表格]的规则
     * @todo 记录类型
     */
    function asDetail($optionName = '')
    {
        $this->modelLoadType = 'detail';
        $this->modelLoadTypeOption = $optionName;

        return $this;
    }

    /**
     * 渲染为[表单]的规则
     * @todo 记录类型
     */
    function asForm($optionName = '')
    {
        $this->modelLoadType = 'form';
        $this->modelLoadTypeOption = $optionName;

        return $this;
    }

    /**
     * 渲染为[表单]的规则
     * @todo 记录类型
     */
    function asFilter($optionName = '')
    {
        $this->modelLoadType = 'filter';
        $this->modelLoadTypeOption = $optionName;

        return $this;
    }

    /**
     * 渲染为[表单]的规则
     * @todo 记录类型
     */
    function asView($optionName = '')
    {
        $this->modelLoadType = 'view';
        $this->modelLoadTypeOption = $optionName;

        return $this;
    }


    /**
     * 使用字段配置
     * 
     */
    function useOpton($optionName)
    {
        $this->modelLoadTypeOption = $optionName;
    }

    /**
     * 开启默认值解析
     * 
     */
    function useDefaultRow() {}

    /**
     * 修改items
     * 
     * @todo 记录工序
     * @todo 将会覆盖所有items
     */
    function setItems(array $items)
    {
        $this->pipelineData[] = [
            'type' => 'setItems',
            'title' => '复写规则:覆盖整个items数组',
            'data' => [
                'items' => $items,
            ],
        ];

        return $this;
    }

    /**
     * 添加字段
     * @todo 记录工序
     */
    function addItem($itemRule)
    {
        $this->pipelineData[] = [
            'type' => 'addItem',
            'title' => '添加一项规则',
            'data' => [
                'row' => $itemRule,
            ],
        ];

        return $this;
    }

    /**
     * 移除字段
     * @todo 记录工序
     */
    function removeItem($name)
    {
        $this->pipelineData[] = [
            'type' => 'removeItem',
            'title' => '移除一项规则',
            'data' => [
                'name' => $name
            ],
        ];
        return $this;
    }

    /**
     * 移除多个字段
     * @todo 记录工序
     */
    function removeFields(array $fields)
    {
        $this->pipelineData[] = [
            'type' => 'removeFields',
            'title' => '移除多个字段',
            'data' => [
                'fields' => $fields
            ],
        ];
        return $this;
    }

    /**
     * 设置字段参数
     * @todo 记录工序
     */
    function setFieldAttrs(array $fields, array $attrs)
    {
        $this->pipelineData[] = [
            'type' => 'setFieldAttrs',
            'title' => '设置字段参数',
            'data' => [
                'fields' => $fields,
                'attrs' => $attrs,
            ],
        ];

        return $this;
    }

    function useOption($name = 'default') {}

    /**
     * TODO 导出模型规则数据
     * 
     */
    function dest()
    {
        $logs = [];
        $modelData = modelTool()->getModel($this->modelName);

        $items = $modelData['items'];
        $options = $modelData['options'] ?? [];
        $config = null;


        // # TODO -- 读取option规则
        if ($this->modelLoadType) {

            // TODO -- -- 加载ID(不包括筛选)
            if (!in_array($this->modelLoadType, ['filter'])) {
                $hasIdField = PhpDataType::arrayFind($items, function ($item) {
                    return $item['name'] == 'id';
                });
                if (!$hasIdField) {
                    $items[] = [
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
                }
            }

            // TODO -- -- 加载配置
            $optionRow = PhpDataType::arrayFind($options, function ($li) {
                if ($li['type'] == $this->modelLoadType) {
                    if (!$this->modelLoadTypeOption) {
                        return true;
                    }

                    if ($this->modelLoadTypeOption == $li['name']) {
                        return true;
                    }
                }
                return false;
            });
            # TODO -- -- 加载配置
            $logs[] = "# 加载配置";
            $logs[] = $optionRow;
            if ($optionRow) {

                /** @var array 字段配置*/
                $itemConfigs = $optionRow['item_config'] ?: [];
                $config = $optionRow['type_config']?? null;

                foreach ($items as $key => $item) {
                    $itemConfig = PhpDataType::arrayFind($itemConfigs, function ($li) use ($item) {
                        if ($li['name'] == $item['name']) {
                            return true;
                        }
                        return false;
                    });

                    if ($itemConfig) {
                        // $logs[] = "写入itemConfig";
                        // $logs[] = $itemConfig;

                        $items[$key] = array_merge($item, $itemConfig);
                    }
                }
            }

            // throw new MuloException('dev',0,[
            //     '$items'=>$items,
            // ]);

            // if($this->modelLoadTypeOption);   
        }

        # TODO -- 流水线处理

        $logs[] = "# 流水线工序";
        foreach ($this->pipelineData as $key => $step) {
            $logs[] = "工序:{$step['type']}";
            $logs[] = "{$step['title']}";
            $logs[] = $step;

            // 添加字段
            if ($step['type'] == 'addItem') {
                $items[] = $step['data']['row'];
            }
            // 移除字段
            else if ($step['type'] == 'removeItem') {

                $indexOfItems = PhpDataType::arrayFindIndex($items, function ($li) use ($step) {
                    if ($li['name'] == $step['data']['name']) {
                        return true;
                    }
                    return false;
                });
                if ($indexOfItems >= 0) {
                    array_splice($array, $indexOfItems, 1);
                }
            }
            // 覆盖字段
            else if ($step['type'] == 'setItems') {
                $items = $step['data']['items'];
            }
            // 移除多个字段
            else if ($step['type'] == 'removeFields') {
                $removeNames = $step['data']['fields'];
                $_items = [];
                foreach ($this->items as $key => &$rule) {
                    //为选中的名称更新属性
                    if (in_array($rule['name'], $removeNames)) {
                        continue;
                    }
                    $_rules[] = $rule;
                }
                $this->items = $_rules;

                $items = $_items;
            }
            // 设置字段参数
            else if ($step['type'] == 'setFieldAttrs') {
                $fields = $step['data']['fields'];
                $attrs = $step['data']['attrs'];

                foreach ($items as $key => &$rule) {
                    //为选中的名称更新属性
                    if (in_array($rule['name'], $fields)) {
                        foreach ($attrs as $field => $value) {
                            $rule[$field] = $value;
                        }
                    }
                }
                unset($rule);
            }
        }

        foreach ($items as $key => &$item) {
            if (!isset($item['component'])) {
                $item['component'] = $item['type'];
            }
        }
        unset($item);

        // 排序: `权重DESC`
        usort($items, function ($a, $b) {
            return ($b['weight'] ?? 0) <=> ($a['weight'] ?? 0);
        });

        $modelData['items'] = $items;

        $data = [
            'logs' => $logs,
            'model' => $this->modelName,
            'modelData' => $modelData,
            'items' => $items,
            'config' => $config
        ];

        return $data;
    }
}
