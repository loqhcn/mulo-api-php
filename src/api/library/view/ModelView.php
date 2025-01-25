<?php

namespace mulo\library\view;

use mulo\api\library\storage\common\File;
use mulo\exception\MuloException;
use Overtrue\EasySms\Contracts\GatewayInterface;

/**
 * 页面设计
 * 
 * 
 */
class ModelView
{
    

    public $config = null;

    /**
     * 构建链式方法
     */
    static function src()
    {
        $obj = new static();

        return $obj;
    }

    
    /**
     * Make gateway instance.
     *
     * @param string $gateway
     * @param array  $config
     *
     * @return GatewayInterface
     *
     * @throws \Exception
     */
    protected function makeTpl($gateway, $config)
    {
        if (!\class_exists($gateway)) {
            throw new \Exception(\sprintf('Class "%s" 是一个无效的 ModelView tpl.', $gateway));
        }

        return new $gateway($config);
    }

    /**
     * 输出
     * 
     */
    function dest(){
        
    }
}
