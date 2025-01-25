<?php

namespace mulo\api\logic\data_model;

use mulo\api\library\ModelDb;
use mulo\exception\MuloException;

class TreeDataModelTool
{

    public $modelData = null;

    /** @var string id字段 */
    public $idField = 'id';

    /** @var string pid字段 */
    public $pidField = 'pid';




    function __construct() {}

    /**
     * 构建链式方法
     * 
     * @param string $modelName 对应模型名称
     * 
     */
    static function src($modelData = null)
    {
        $obj = new self();
        $modelData && $obj->model($modelData);
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


    /**
     * 读取上级数据树
     * 
     * @param array $row 
     * 
     * @return array 上级递归,从近到远
     */
    function getParentTree($row)
    {
        $this->pidField = 'pid';
        $plist = [$row];
        $plist = $this->eachParent($row, $plist);

        $plist = array_reverse($plist);
        return $plist;
    }

    /**
     * 
     * @param array $currentRow 当前行数据
     * @param array $list 当前列表
     */
    function eachParent($currentRow, $list)
    {
        $pid = $currentRow[$this->pidField];
        if ($pid) {
            $prow = ModelDb::model($this->modelData)->where([
                $this->idField => $pid
            ])->find();

            if ($prow) {
                $list[] = $prow;

                if ($prow[$this->pidField]) {

                    // throw new MuloException("100", 0, [
                    //     'prow' => $prow,
                    //     'list' => $list,
                    // ]);

                    return $this->eachParent($prow, $list);
                }
            }
        }

        return $list;
    }

    /**
     * 
     */
    function dest() {}
}
