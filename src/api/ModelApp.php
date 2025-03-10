<?php

namespace mulo\api;

use mulo\api\facade\DataType;
use mulo\api\form\ModelForm;
use mulo\api\list\ModelList;
use mulo\api\library\BaseFilter;
use mulo\api\library\ModelDb;
use mulo\api\logic\data_model\InjectsHandle;
use mulo\exception\MuloException;
use mulo\facade\Db;
use mulo\api\traits\DefineHook;
// use mulo\facade\Db as MuloFacadeDb;

/**
 * 应用
 * 
 * 
 * 
 */
class ModelApp
{

    
    function __construct() {
        
    }

    /**
     * 构建链式方法
     * @param array $config 应用信息
     * 
     */
    static function src($config = null)
    {
        $obj = new self();
        return $obj;
    }




    /**
     * 输出支付参数
     * 
     */
    function dest()
    {

    }

}