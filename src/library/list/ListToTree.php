<?php

namespace mulo\library\list;


class ListToTree
{

    public $list = [];
    public $list_pidIndex = [];
    // `防止死循环` 存储加载过的索引, 
    public $eachIndexCache = [];

    public $options = [
        'idField' => 'id',
        'pidField' => 'pid',
        'deepField' => '',
        'childrenField' => 'children',
    ];

    function __construct() {}

    static function src($list = [])
    {
        $obj = new self();
        if ($list && !empty($list)) {
            $obj->setList($list);
        }

        return $obj;
    }

    function setList($list)
    {
        $this->list = $list;
    }

    function renderChildren($list = null, $deep = true, $eachDeep = 1)
    {
        if ($list === null) {
            $list = $this->getChildList(0);
        }

        foreach ($list as $key => &$li) {
            if ($deep !== true && $eachDeep > $deep) {
                $li[$this->options['childrenField']] = [];
                continue;
            }

            $itemId = $li[$this->options['idField']];

            if (in_array($itemId, $this->eachIndexCache)) {
                continue;
            }
            $this->eachIndexCache[] = $itemId;

            $children = $this->getChildList($itemId);

            if (!empty($children)) {
                $nextEachDeep = $eachDeep + 1; //下一级深度
                $children = $this->renderChildren($children, $deep, $nextEachDeep);
            }
            
            $li[$this->options['childrenField']] = $children;
        }
        unset($li);

        return $list;
    }



    protected function getChildList($pid)
    {
        // return 
        if (isset($this->list_pidIndex[$pid])) {
            return $this->list_pidIndex[$pid];
        }

        return [];
    }

    function dest()
    {
        $pidField = $this->options['pidField'];

        // 创建索引
        foreach ($this->list as $key => $li) {
            $pid = $li[$pidField];
            if (!isset($pid)) {
                $this->list_pidIndex[$pid] = [];
            }
            $this->list_pidIndex[$pid][] = $li;
        }

        return $this->renderChildren(null, true);
    }
}
