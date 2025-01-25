<?php

declare(strict_types=1);

namespace mulo\tpmodel;

use think\Model;
use mulo\facade\Auth;
use mulo\filter\BaseFilter;
use think\db\Query;
use mulo\tpmodel\traits\ModelAttr;
use mulo\traits\DatabaseOper;

class Common extends Model
{

    use ModelAttr, DatabaseOper;

    /**
     * 当前 model 对应的 filter 实例
     *
     * @return BaseFilter
     */
    public function filterInstance()
    {
        $app_name = app('http')->getName();     // 当前模块名
        $filter_class = static::class;

        $class = str_replace('model', 'filter',  $filter_class) . 'Filter';

        if (!class_exists($class)) {
            return new BaseFilter();
        }
        return new $class();
    }


    /**
     * 查询范围 filter 搜索入口
     *
     * @param Query $query
     * @return void
     */
    public function scopeMuloFilter($query, $sort = true, $filters = null)
    {
        $instance = $this->filterInstance();
        $query = $instance->apply($query, $filters);
        if ($sort) {
            $query = $instance->filterOrder($query);
        }

        return $query;
    }


    /**
     * 获取模型中文名
     *
     * @return string|null
     */
    public function getModelName()
    {
        if (isset($this->modelName)) {
            $model_name = $this->modelName;
        } else {
            $tableComment = $this->tableComment();
            $table_name = $this->db()->getTable();
            $model_name = $tableComment[$table_name] ?? null;
        }

        return $model_name;
    }
}
