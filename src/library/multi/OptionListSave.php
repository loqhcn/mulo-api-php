<?php

declare(strict_types=1);

namespace mulo\library\multi;

use mulo\exception\MuloException;


/**
 * 选项列表保存操作
 * @todo 选项存储在表中,保存时进行 增加|删除|更新|恢复 的操作
 * @todo 主要用于列表编辑形操作
 * @todo 也可以普通表数据批量保存(只处理增加和更新, 删除手动处理)
 * 
 */
class OptionListSave
{


    /**
     * 读取已存在的列表
     * 
     * @param \think\Model $modelClass  tp模型子类
     * @param array|callable $where     查询条件
     * @param array $options            配置查询项
     * @return array -list_has:已存在列表 -list_softDeleted软删除列表
     */
    function getMultiListHas($modelClass, $where = [], $options = ['checkHas' => true, 'checkSoftDelete' => false])
    {

        $list_has = [];
        $list_softDeleted = [];

        $needList_has  = $options['checkHas'] ?? false;
        $needList_softDeleted  = $options['checkSoftDelete'] ?? true;

        // 已存在的列表
        if ($needList_has) {
            $list = $modelClass::where($where)->select();
            $list_has = $list ? $list->toArray() : [];
        }

        // 软删除的列表
        if ($needList_softDeleted) {
            try {
                $list = $modelClass::onlyTrashed()->where($where)->select();
                $list_has = $list ? $list->toArray() : [];
            } catch (\Throwable $th) {
                //不执行错误
            }
        }

        return [
            'list_has' => $list_has,
            'list_softDeleted' => $list_softDeleted,
        ];
    }


    /**
     * 批量更新列表解析(添加的,删除的,更新的,恢复的)
     * @todo 不存在的自动创建(软删除的进行恢复,不传入$softDeleteList则进行创建)
     * @todo 存在的更新(被软删除的先恢复再更新)
     * @todo 不在列表中的删除
     * 
     * @param array $list               提交的列表
     * @param array $list_has           已存在的列表
     * @param array $list_softDeleted   已经软删除的列表
     * 
     * @return array
     */
    function parseMultiListSaves($list, $list_has = [], $list_softDeleted = [])
    {

        $idField = 'id';
        $list_add = [];         //添加的
        $list_delete = [];      //删除的
        $list_update = [];      //删除的
        $list_restore = [];     //恢复的(软删除时)  

        $list_has_idKey = $this->getListIdKey($list_has, $idField);
        $list_has_ids = array_column($list_has, $idField);
        $list_softDeleted_ids = array_column($list_has, $idField);

        $list_ids = [];
        foreach ($list as $key => $li) {
            if (isset($li[$idField]) && $li[$idField]) {
                $list_ids[] = $li[$idField];
            }

            if (!isset($li[$idField])) {
                $list_add[] = $li;
                continue;
            }

            // 是否存在
            $isExit = in_array($li[$idField], $list_has_ids);

            if ($isExit) {
                $list_update[] = $li;
            } else {
                $isSoftDelExit = in_array($li[$idField], $list_softDeleted_ids);
                if ($isSoftDelExit) {
                    $list_restore[] = $li;
                    $list_update[] = $li;
                } else {
                    unset($li[$idField]);
                    $list_add[] = $li;
                }
            }
        }

        // 删除的(在已存在列表,并且不在数据列表)
        foreach ($list_has as $key => $li) {
            if (!in_array($li[$idField], $list_ids)) {
                $list_delete[] = $li;
            }
        }

        return [
            'add' => $list_add,
            'update' => $list_update,
            'restore' => $list_restore,
            'delete' => $list_delete,
            'list_has' => $list_has,
            'list_softDeleted' => $list_softDeleted
        ];
    }


    /**
     * 列表更新索引
     * 
     */
    function getListIdKey($list, $idField = 'id')
    {
        $list_idKey = [];
        foreach ($list as $key => $li) {
            $list_idKey[$idField] = $li;
        }
        return $list_idKey;
    }


    /**
     * 执行批量保存
     * 
     * @todo 保存通过thinkphp模型
     * 
     */
    function saveMuitiList($modelClass, $parsedResult, $options = ['softDelete' => true])
    {
        $idField = 'id';
        $isSoftDelete = $options['softDelete'] ?? true;

        // 新增
        if (!empty($parsedResult['add'])) {
            $obj = new $modelClass();
            $obj->saveAll($parsedResult['add']);
        }

        // 恢复
        if (!empty($parsedResult['restore'])) {
            $list_softDeleted_idKey = $this->getListIdKey($parsedResult['list_softDeleted'], $idField);
            foreach ($parsedResult['restore'] as $key => $li) {
                $list_softDeleted_idKey[$li[$idField]]->restore();
            }
        }

        // 批量更新(自动判断主键)
        $obj = new $modelClass();
        $obj->saveAll($parsedResult['update']);
        // foreach ($parsedResult['update'] as $key => $li) {
        //     $modelClass::where($idField, $li[$idField])->update($li);
        // }
       
        // 删除
        $deleteIds = array_column($parsedResult['delete'], $idField);


        // var_dump($deleteIds);exit;
        if (!empty($deleteIds)) {
            if ($isSoftDelete) {
                $modelClass::where($idField, 'in', $deleteIds)->update([
                    'deletetime' => time()
                ]);
            } else {
                $modelClass::where($idField, 'in', $deleteIds)->delete();
            }
        }
    }

    /**
     * 执行批量保存
     * @param \think\Model $modelClass tp模型子类
     * 
     */
    function save($modelClass) {

    }

}
