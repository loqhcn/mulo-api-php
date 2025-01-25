<?php

declare(strict_types=1);

namespace mulo\library\curd;

use mulo\exception\MuloException;


/**
 * 增删改查助手函数
 * 
 */
class Curd
{
    public $useException = true;
    public $modelClass = null;
    public $data = null;
    public $accessList = [];


    function model($modelClass)
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    /**
     * 是否开启异常
     * 
     */
    function setUseException(bool $useException)
    {
        $this->useException = $useException;
        return $this;
    }

    /**
     * edit 检查权限(配合find使用)
     * @todo 判断不符合权限设定是抛出异常
     * @example `checkAccess(['user_id'=>1])->find($id)` 判断所属用户
     */
    function checkAccess(array $access)
    {
        $this->accessList = $access;
        return $this;
    }

    function find($id, $idKey = 'id')
    {
        $row = $this->modelClass::where($idKey, $id)->find();
        if (!$row) {
            if ($this->useException) {
                throw new MuloException("未找到", 0, [
                    'id' => $id
                ]);
            }
        }
        return $row;
    }


    /**
     * edit 设置数据
     * @todo 设置用于验证和保存的数据
     */
    function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }


    /**
     * edit 设置数据
     * @todo 设置用于验证和保存的数据
     * 
     * @return 
     */
    function validate()
    {

        return $this;
    }

    /**
     * edit | add 保存数据
     * @param \think\Model $row 数据行
     * @todo row为空时 代表添加数据
     * 
     * @return $row
     */
    function save($row = null)
    {
        if (!$row) {
            $row = new $this->modelClass();
        }
        $row->save($this->data);

        return $row;
    }


    /**
     * edit | add 更新要保存的数据
     * @param callable $call 处理方法
     * @todo 保存前修改数据
     * 
     * @return 
     */
    function updateData(callable $call)
    {
        $this->data = $call($this->data);
        return $this;
    }


    /**
     * delete 删除数据
     * 
     */
    function delete($row)
    {
        $row->delete();
    }
}
