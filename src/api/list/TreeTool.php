<?php

namespace mulo\api\list;

use app\mm\model\MuloModel;
use app\mm\model\MuloModelItem;
use mulo\exception\MuloException;
use mulo\facade\Db as MuloFacadeDb;
use mulo\api\library\ModelDb;

/**
 * [链式]tree数据加载工具
 * 
 * @todo 定义树形数据  pid:父数据ID, deep:深度
 * 
 */
class TreeTool
{
    public $modelData = null;
    public $type = 'list';
    public $childrenField = 'children';

    /** @var string 数据主键 */
    public $idField = 'id';

    /** @var string 关系父级,默认pid */
    public $pidField = 'pid';

    /** @var string 深度定义字段(可选) 用于优化,减少查询次数 */
    public $deepField = ''; //深度字段 从0开始

    public $query = null;

    /** @var array 列表  */
    public $list = [];

    /** @var array 深度数据(根据deepField优化)  */
    public $deepDatas = [];

    /** @var array 把pid相同的放在一起加速加载  */
    public $childrenList_pidKey = [];


    function __construct() {}


    /**
     * 执行器
     * @param ModelDb $query 查询器即
     * 
     */
    static function src($query)
    {
        $obj = new self();
        $obj->setQuery($query);
        return $obj;
    }

    /**
     * 设置查询类
     * @param ModelDb $query 查询器即
     * 
     */
    function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }


    /**
     * 设置字段
     * 
     * @example setFields([ 'children' => 'items' ]) 设置存放子数据字段
     * @example setFields([ 'pid' => 'parent_id' ]) 上级关联字段
     * @example setFields([ 'deep' => 'deep' ])     深度字段
     * 
     */
    function setFields(array $fields)
    {
        if (isset($fields['children'])) {
            $this->childrenField = $fields['children'];
        }
        if (isset($fields['pid'])) {
            $this->pidField = $fields['pid'];
        }
        if (isset($fields['deep'])) {
            $this->deepField = $fields['deep'];
        }
        return $this;
    }

    function setList($list)
    {
        $this->list = $list;
        $this->type = 'list';
        return $this;
    }

    function setRow($row)
    {
        $this->list = [$row];
        $this->type = 'row';
        return $this;
    }

    /**
     * 渲染子合计
     * @param array $list 渲染的列表
     * @param int $deep 加载的深度
     * @param int $eachDeep 递归的深度,从1开始
     */
    function renderChildren($list, $deep = 1, $eachDeep = 1)
    {
        foreach ($list as $key => &$li) {
            if ($eachDeep > $deep) {
                $li[$this->childrenField] = [];
                continue;
            }
            $children = $this->getChildList($li);


            // throw new MuloException("getChildList", 0, [
            //     'li' => $li,
            //     '$children' => $children,
            //     'childrenList_pidKey' => $this->childrenList_pidKey,
            // ]);

            if (!empty($children)) {
                $nextEachDeep = $eachDeep + 1; //下一级深度
                $children = $this->renderChildren($children, $deep, $nextEachDeep);
            }
            $li[$this->childrenField] = $children;
        }
        unset($li);

        return $list;
    }

    function getChildList($row)
    {

        $pid = $row[$this->idField];

        if ($this->deepField) {
            return isset($this->childrenList_pidKey[$pid]) ? $this->childrenList_pidKey[$pid] : [];
        }

        // 通过查询加载
        $query = (clone $this->query);
        $list = $query->where($this->pidField, $pid)->select();
        return $list;
    }

    /**
     * @param int $deep 加载的深度
     * @param int $currentDeep 当前的深度
     */
    function dest($deep = 1, $currentDeep = 0)
    {
        //加载深度数据
        if ($this->deepField) {
            for ($i = 1; $i <= $deep; $i++) {
                // 查询的深度
                $deepFilter = $currentDeep + $i;



                $query = (clone $this->query);
                $deepData = $query->where($this->deepField, $deepFilter)->select();

                // 生成加载索引,降低循环次数
                foreach ($deepData as $key => $li) {
                    $pid = $li[$this->pidField];
                    if (!isset($this->childrenList_pidKey[$pid])) {
                        $this->childrenList_pidKey[$pid] = [];
                    }

                    $this->childrenList_pidKey[$pid][] = $li;
                }
                $this->deepDatas[$deepFilter] = $deepData;
            }

            // throw new MuloException("测试1", 0, [
            //     'childrenList_pidKey' => $this->childrenList_pidKey,
            //     'deep' => $deep,
            //     'currentDeep' => $currentDeep,
            // ]);
        }

        $list = $this->renderChildren($this->list, $deep);
        return $list;
    }
}
