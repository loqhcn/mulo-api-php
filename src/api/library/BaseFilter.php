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
                if (is_array($li)) {
                    if (empty($li)) {
                        continue;
                    }

                    // 数组索引筛选
                    if ($this->isNumberIndexArray($li)) {
                        // 传输传入方式 ["title","=","3"]
                        $query->where(...$li);
                        continue;
                    }
                }

                // throw new MuloException('dev',0,[
                //     'wheres'=>$li,
                // ]);

                // 参数直接筛选方式 {"title":3}
                $useParamsCondition = [];
                $useArrayCondition = [];
                foreach ($li as $key => $value) {
                    // 依然采用参数传递的 { "income_id": [ "in", [12] ] }
                    if(is_array($value) && count($value)==2 && is_string($value[0]??false)){
                        $useParamsCondition[] = [$key,$value[0],$value[1]];
                        continue;
                    }
                    // 普通方式传递的
                    $useArrayCondition[$key] = $value;
                }

                if (!empty($useParamsCondition)) {
                    $query->where($useParamsCondition);
                }

                if (!empty($useArrayCondition)) {
                    $query->where($useArrayCondition);
                }
                
            }
        };
    }
}
