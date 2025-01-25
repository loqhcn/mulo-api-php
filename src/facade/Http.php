<?php

declare(strict_types=1);

namespace mulo\facade;

use think\Facade;

/**
 * @see \mulo\library\Http
 * 
 * @method array post() 发起post请求
 * 
 */
class Http extends Facade
{

    /**
     * @see \mulo\library\Db
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        $_SERVER['MULO_MODEL_FLAG'] = 1;
        return 'mulo\library\Http';
    }
}
