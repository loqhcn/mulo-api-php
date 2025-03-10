<?php

namespace mulo\library\data_type;

class PhpDataType
{

    /**
     * @var string 数字类型
     * 
     */
    const number = 'number';

    /**
     * @var string 数组类型
     * @todo 区分php内的数组和对象
     */
    const array = 'array';

    /**
     * @var string 对象类型
     * @todo 区分php内的数组和对象
     * 
     */
    const object = 'object';


    /**
     * @var string 对象类型
     * @todo 区分php内的数组和对象
     * 
     */
    const string = 'string';


    /**
     * 查找
     * @todo 功能类似JS的 `array.findIndex()`
     * 
     * @return string 数据类型
     */
    static function getType($data)
    {
        if (is_array($data)) {
            return self::array;
        }

        if (is_string($data)) {
            return self::string;
        }

        if (is_object($data)) {
            return self::object;
        }
    }


    /**
     * 查找
     * @todo 功能类似JS的 `array.findIndex()`
     * 
     * @return any 下面的项目
     */
    static function isNumber($data)
    {
        //
    }

    /**
     * 查找
     * @todo 功能类似JS的 `array.findIndex()`
     * 
     * @param array $array 数组
     * @param callable $callback 条件处理函数
     * 
     * @return any 下面的项目
     */
    static function arrayFind(array $array, callable $callback)
    {
        foreach ($array as $key => $item) {
            if ($callback($item, $key, $array)) {
                return $item;
            }
        }
        return null;  // 如果没有找到符合条件的项，返回null
    }

    /**
     * 查找-index
     * @todo 功能类似JS的 `array.findIndex()`
     * 
     * @return int 索引
     */
    static function arrayFindIndex(array $array, callable $callback)
    {
        foreach ($array as $index => $item) {
            if ($callback($item, $index, $array)) {
                return $index;
            }
        }
        return -1;  // 如果没有找到符合条件的项，返回-1
    }

    /**
     * 为数组创建带索引的数组
     * 
     * @param   array   $array 数组
     * @param   string  $field 字段
     * 
     * @return  array   新的索引数组
     */
    static function arrayIndex(array $array, $field = 'id')
    {
        $_array = [];
        foreach ($array as $index => $item) {
            if (!is_array($item)) {
                $_array[$index] = $item;
                continue;
            }
            $key = $item[$field] ?? null;
            if ($key !== null) {
                $_array[$key] = $item;
                continue;
            }
        }
        return $_array;
    }


    /**
     * 数组是不是和js的对象一样
     * @return boolean
     */
    static function arrayAsJsObject(array $array)
    {
        // 检查是否为数组
        if (!is_array($arr)) {
            return false;
        }
        // 检查数组是否为空
        if (empty($arr)) {
            return false;
        }


        // 遍历数组，检查键是否为字符串
        $isObject = false;
        foreach ($arr as $key => $value) {
            if (is_string($key)) {
                $isObject = true;
                break;
            }
        }

        return $isObject;
    }


    static function isNumberIndexArray(array $arr)
    {
        foreach (array_keys($arr) as $key) {
            if (!is_int($key)) {
                return false; // 存在字符串键，说明是关联数组
            }
        }
        return true; // 所有键都是整数，说明是索引数组
    }
}
