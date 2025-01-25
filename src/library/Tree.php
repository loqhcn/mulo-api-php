<?php

declare(strict_types=1);

namespace mulo\library;

use mulo\exception\MuloException;
use think\model\Collection;

class Tree
{
    protected $model = null;

    public function __construct($model)
    {
        $this->model = $model;
    }



    /**
     * 获取递归树
     *
     * @param integer|Collection $items     可以传入某个id 查询这个id的下级树，也可以传一个查询列表结果，将获取这个列表的所有的下级 树
     * @param \Closure $resultCb            用来处理每一次查询的结果 比如要取出树中的所有 name
     * @return Collection
     */
    public function getTree($items = 0, \Closure $resultCb = null)
    {
        if (!$items instanceof Collection) {
            $items = $this->getQuery()->where('parent_id', $items)->select();
            $resultCb && $items = $resultCb($items);
        }

        foreach ($items as $key => &$item) {
            $child = $this->getTree($item->id, $resultCb);
            if (!$child->isEmpty()) {
                $item->children = $child;
            }
        }

        return $items;
    }

    /**
     * 获取递归树(包含自身)
     *
     * @param integer  $id    
     * @param \Closure $resultCb            用来处理每一次查询的结果 比如要取出树中的所有 name
     * @return Collection
     */
    public function getChildren($id, \Closure $resultCb = null)
    {
        $self = $this->getQuery()->where('id', $id)->select();
        if($self->isEmpty()) {
            throw new MuloException('未找到数据');
        }

        $items = $this->getQuery()->where('parent_id', $id)->select();
        $resultCb && $items = $resultCb($items);

        foreach ($items as $key => &$item) {
            $child = $this->getTree($item->id, $resultCb);
            if (!$child->isEmpty()) {
                $item->children = $child;
            }
        }
        $self[0]->children = $items;

        return $self;
    }


    /**
     * 检测id 是不是自己的下级
     *
     * @param [type] $parent_id
     * @param [type] $id
     * @return void
     */
    public function checkParent($parent_id, $id)
    {
        if ($parent_id == $id) {
            throw new MuloException('当前上级不能是自己');
        }
        $childIds = $this->getChildIds($id);
        if (in_array($parent_id, $childIds)) {
            throw new MuloException('当前上级不能是自己的下级');
        }

        return true;
    }


    /**
     * 获取当前对象所属级别
     *
     * @param [type] $object
     * @return void
     */
    public function getLevel($object)
    {
        $parentIds = $this->getParentFields($object, 'id');
        return count($parentIds);
    }


    /**
     * 缓存递归获取当前对象的上级 指定字段
     *
     * @param \think\Model|int $id
     * @param boolean $self 是否包含自己
     * @return array
     */
    public function getParentFields($item, $field = 'id', $self = true)
    {
        if (!$item instanceof \think\Model) {
            $item = $this->getQuery()->find($item);
            if (!$item) {
                return [];
            }
        }
        // 判断缓存
        $cacheKey = 'object-' . $this->getTable() . '-' . $item->id . '-' . $field . '-parent-ids';
        $objectIds = cache($cacheKey);

        if (!$objectIds) {
            $objectIds = array_reverse($this->recursionGetParentFields($item, $field));
            if ($self) {
                $objectIds[] = $item[$field];     // 加上自己
            }
            // 缓存暂时注释，如果需要，可以打开，请注意后台更新角色记得清除缓存
            // cache($cacheKey, $objectIds, (600 + mt_rand(0, 300)));     // 加入随机秒数，防止一起全部过期
        }

        return $objectIds;
    }


    /**
     * 递归获取所有上级 id
     */
    private function recursionGetParentFields($item, $field = 'id', $ids = [])
    {
        if ($item->parent_id) {
            $parent = $this->getQuery()->find($item->parent_id);
            if ($parent) {
                $ids[] = $parent[$field];
                return $this->recursionGetParentFields($parent, $field, $ids);
            }
        }

        return $ids;
    }


    /**
     * 缓存递归获取子对象 id
     *
     * @param int $id       要查询的 id
     * @param boolean $self 是否包含自己
     * @return array
     */
    public function getChildIds($id, $self = true)
    {
        // 判断缓存
        $cacheKey = 'object-' . $this->getTable() . '-' . $id . '-child-ids';
        $objectIds = cache($cacheKey);

        if (!$objectIds) {
            $objectIds = $this->recursionGetChildIds($id, $self);

            // 缓存暂时注释，如果需要，可以打开，请注意后台更新角色记得清除缓存
            // cache($cacheKey, $objectIds, (600 + mt_rand(0, 300)));     // 加入随机秒数，防止一起全部过期
        }

        return $objectIds;
    }


    /**
     * 递归获取子分类 id
     * 
     */
    private function recursionGetChildIds($id, $self)
    {
        $ids = $self ? [$id] : [];
        $childrenIds = $this->getQuery()->where(['parent_id' => $id])->column('id');

        if ($childrenIds) {
            foreach ($childrenIds as $v) {
                $grandsonIds = $this->recursionGetChildIds($v, true);
                $ids = array_merge($ids, $grandsonIds);
            }
        }

        return $ids;
    }



    /**
     * 获取当前 查询
     *
     * @return think\model|think\db\Query
     */
    private function getQuery()
    {
        if ($this->model instanceof \Closure) {
            return ($this->model)();
        }

        return $this->model;
    }


    /**
     * 获取表
     */
    private function getTable()
    {
        $query = $this->getQuery();
        if ($query instanceof \think\Model) {
            $table_name = $query->db()->getTable();
        } else {
            $table_name = $query->getTable();
        }

        return $table_name;
    }
}
