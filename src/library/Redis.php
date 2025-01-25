<?php

declare(strict_types=1);

namespace mulo\library;

use mulo\exception\MuloException;
use Redis as SystemRedis;

class Redis
{
    protected $handler = null;

    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
    ];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        $config = get_redis_connection('default');
        $this->options = array_merge($this->options, $config);
        
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $this->handler = new SystemRedis();
        if ($this->options['persistent']) {
            $this->handler->pconnect($this->options['host'], intval($this->options['port']), $this->options['timeout'], 'persistent_id_' . $this->options['select']);
        } else {
            $this->handler->connect($this->options['host'], intval($this->options['port']), $this->options['timeout']);
        }

        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            $this->handler->select(intval($this->options['select']));
        }
    }

    public function getRedis()
    {
        return $this->handler;
    }

    
    /**
     * 方法转发到redis
     *
     * @param string $funcname
     * @param array $arguments
     * @return void
     */
    public function __call($funcname, $arguments)
    {
        return $this->getRedis()->{$funcname}(...$arguments);
    }
}
