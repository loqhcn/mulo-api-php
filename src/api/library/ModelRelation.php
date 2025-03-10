<?php

namespace mulo\api\library;

use mulo\api\form\ModelForm;
use mulo\api\library\BaseFilter;
use mulo\exception\MuloException;
use think\facade\Db;
use mulo\api\traits\DefineHook;
use mulo\library\data_type\PhpDataType;
use think\console\output\descriptor\Console;

// use mulo\facade\Db as MuloFacadeDb;

/**
 * 
 * 处理关联
 * 
 * 
 */
class ModelRelation
{

    /**
     * @var string 模型名称
     */
    public $modelName = '';
    public $modelLoadType = '';
    public $modelLoadTypeOption = '';

    public $options = [];

    /**
     * @var array 载入的关联配置
     * 
     */
    public $relations = [];


    /**
     * @var array 流水线处理数据
     * @todo 下方的一些函数只记录如何处理, 不实际处理
     * @todo 在dest时再进行实际处理
     * 
     */
    public $pipelineData = [];


    /**
     * 
     * 构建链式操作
     * @param string 当前模型名称
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
     * 添加关联
     * @param array $relationRow 关联配置
     * 
     */
    function relation($relationRow = [])
    {
        $this->relations[] = $relationRow;
        return $this;
    }

    function getRelations(array $list, array $relations) {}

    /**
     * 列表载入关联数据
     * 
     * 
     */
    function listLoadRelation(array $list, array $relations)
    {

        $list_idKey = PhpDataType::arrayIndex($list, 'id');
        foreach ($relations as $key => $relation) {

            $relationName = ''; //关联名称
            $fields = '';
            $fieldAlias = [];
            if (is_array($relation)) {
                $relationName = $key;
                $fields = $relation['field'] ?? '';
                $fieldAlias = $relation['fieldAlias'] ?? '';
            } else {
                $relationName = $relation;
            }

            $relationRow = null;
            $relationModel = '';


            // 加载关联信息
            $relationRow = $this->getRelation($relationName);
            if (!$relationRow) {
                // throw new MuloException('dev', 0, [
                //     '$relationRow' => $relationRow,
                // ]);
                continue;
            }
            $relationModel = $relationRow['model'];
            // 查询关联数据
            $ids = array_column($list, $relationRow['local_field']);
            $relationDatas = ModelDb::model($relationRow['model'])
                ->field($fields)
                ->where($relationRow['relation_field'], 'in', $ids)
                ->select();

            // throw new MuloException('dev', 0, [
            //     '$ids' => $ids,
            // ]);

            $relationDatas = $relationDatas ?: [];
            // 装载关联数据
            foreach ($list as $key => &$item) {
                $relationData = PhpDataType::arrayFind($relationDatas, function ($li) use ($item, $relationRow) {
                    return $li[$relationRow['relation_field']] == ($item[$relationRow['local_field']] ?? '');
                });

                if ($relationRow['import_mode'] == 'row') {
                    $item[$relationRow['name']] = $relationData;
                }

                if ($relationData && $relationRow['import_mode'] == 'field') {
                    foreach ($relationData as $key => $li) {
                        $field = $relationRow['name'] . '_' . $key;
                        if (isset($fieldAlias[$key])) {
                            $field = $fieldAlias[$key];
                        }
                        $item[$field] = $li;
                    }
                    continue;
                }
            }
            unset($item);
        }

        return $list;
    }

    function getRelation($name)
    {
        $relationRow = PhpDataType::arrayFind($this->relations, function ($li) use ($name) {
            if ($li['name'] == $name) {
                return true;
            }
            return false;
        });
        return $relationRow;
    }

    /**
     * 列表载入关联数据
     * 
     * 
     */
    function rowLoadRelation($list, array $relations)
    {
        foreach ($relations as $key => $value) {
        }
    }

    /**
     * 导出
     * 
     */
    function dest()
    {
        // 
    }
}
