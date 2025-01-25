<?php

declare(strict_types=1);

namespace mulo\api\facade;

use think\Facade;

/**
 * @see \mulo\api\library\DataType
 * 
 * @method static bool isArrayOfJson($arr) 数组在json内是否为一个array
 * @method static bool isObjectOfJson($arr) 数组在json内是否为一个object
 * 
 */
class DataType extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * 
     * @return \mulo\api\library\DataType
     */
    protected static function getFacadeClass()
    {
        return 'mulo\api\library\DataType';
    }
}
