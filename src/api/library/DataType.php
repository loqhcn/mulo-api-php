<?php

namespace mulo\api\library;

use mulo\exception\MuloException;

/**
 * 数据配型辅助函数
 * 
 */
class DataType
{


    /**
     * 数组在json内是否为一个array
     * 
     */
    function isArrayOfJson($arr)
    {
        if (!is_array($arr)) {
            return false;
        }

        foreach (array_keys($arr) as $key) {
            if (!is_int($key)) {
                return false; // 存在字符串键，说明是关联数组
            }
        }
        return true; // 所有键都是整数，说明是索引数组
    }

    /**
     * 数组在json内是否为一个对象
     * 
     */
    function isObjectOfJson($arr)
    {
        if (!is_array($arr)) {
            return false;
        }

        if ($this->isArrayOfJson($arr)) {
            return false;
        }
        return true;
    }
}
