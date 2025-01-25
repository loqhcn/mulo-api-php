<?php

namespace mulo\library;

use think\DbManager;

/**
 * 复写thinkphp方法, 实现切换数据库的功能
 * 
 */
class Db extends DbManager
{
    public $title = 'MuloLibraryDb';

    /**
     * @param Event  $event
     * @param Config $config
     * @param Log    $log
     * @param Cache  $cache
     * @return Db
     * @codeCoverageIgnore
     */
    public static function __make()
    {
        $db = new static();

        // $db->setConfig($config);
        // $db->setEvent($event);
        // $db->setLog($log);

        // $store = $db->getConfig('cache_store');
        // $db->setCache($cache->store($store));
        // $db->triggerSql();

        return $db;
    }

    /**
     * 不注入模型对象
     * @access public
     * @return void
     */
    protected function modelMaker(): void
    {
        
    }

    /**
     * 设置连接信息
     * 
     * 
     */
    function setConnectConfig($config)
    {
        $this->setConfig($config);
        return $this;
    }

    /**
     * 设置配置对象
     * @access public
     * @param Config $config 配置对象
     * @return void
     */
    public function setConfig($config): void
    {
        $this->config = $config;
    }
}
