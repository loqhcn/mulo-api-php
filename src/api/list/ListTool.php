<?php

namespace mulo\api\list;

use app\mm\model\MuloModel;
use app\mm\model\MuloModelItem;
use mulo\exception\MuloException;
use mulo\facade\Db as MuloFacadeDb;

/**
 * [链式]模型规则操作
 * 
 * @todo 用于管理模型的渲染规则(列表和表单)
 * 
 */
class ListTool
{
    /** 处理的列表 */
    public $list = [];
    /** 列表的类型 */
    public $type = 'list';


    function __construct() {}

    /**
     * 
     */
    static function src(array $list = null, $type = 'list')
    {
        $obj = new self();

        if ($list && !empty($list)) {
            $obj->setList($list, $type);
        }

        return $obj;
    }


    function setList(array $list, $type)
    {
        $this->list = $list;
        $this->type = $type;

        return $this;
    }

    /**
     * 处理Item
     * 
     */
    function handleItem($handle,array $caseArr=[]){
        
    }



    /**
     * 获取修改后的规则
     * 
     */
    function dest()
    {

        return $this->list;
    }
}
