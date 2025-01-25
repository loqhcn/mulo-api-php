<?php

namespace mulo\api\library;

use mulo\exception\MuloException;

/**
 * 筛选处理
 * 
 */
class BaseFilter
{

    public $filters;
    public $query = null;
    public $wheres = [];

    function __construct($filters)
    {
        $this->filters = $filters;
    }

    static function src($filters)
    {
        return new self($filters);
    }

    /**
     * 纯数字索引数组
     */
    function isNumberIndexArray(array $arr)
    {
        foreach (array_keys($arr) as $key) {
            if (!is_int($key)) {
                return false; // 存在字符串键，说明是关联数组
            }
        }
        return true; // 所有键都是整数，说明是索引数组
    }

    /**
     * 导出筛选处理
     * 
     * @todo 使用thinkphp动态参数调用方式
     * 
     * @return callable 可以通过tp的where使用的方法
     */
    function dest()
    {
        $wheres = [];
        foreach ($this->filters as $key => $filters) {
            $wheres[] = $filters;
        }

        // throw new MuloException('', 0, [
        //     'wheres' => $wheres
        // ]);

        return function ($query) use ($wheres) {

            foreach ($wheres as $key => $li) {
                // 传输传入方式 ["title","=","3"]
                if ($this->isNumberIndexArray($li)) {
                    $query->where(...$li);
                }
                // 参数直接筛选方式 {"title":3}
                else {
                    $query->where($li);
                }
            }
        };
    }
}
