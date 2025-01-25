<?php

namespace mulo\api\library\storage\common;



/**
 * 
 * 
 * 
 */
class Config
{
    /**
     * @var Config
     */
    public $config;


    /**
     * Gateway constructor.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 读取配置
     * 
     * @return array 配置
     */
    function getConfig()
    {
        return $this->config;
    }
}
