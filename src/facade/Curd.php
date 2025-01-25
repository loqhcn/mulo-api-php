<?php

declare(strict_types=1);

namespace mulo\facade;

use think\Facade;
use mulo\library\curd\Curd as CurdLibrary;

/**
 * 增删改查操作
 * @see CurdLibrary
 * 
 * @todo 使用链式方法操作数据, 简化代码
 * @todo 在出现(参数验证失败,数据查询失败)等情况自动抛出异常
 * @todo 异常通过handleException捕获后返回为apiJson
 * 
 * 
 * @method static CurdLibrary model(class $modelClass) 模型
 * 
 * 
 */
class Curd extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'mulo\library\curd\Curd';
    }
}
