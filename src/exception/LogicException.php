<?php

namespace mulo\exception;

use Exception;

/**
 * 抛出业务错误,用于记录日志,通知
 * 
 * 
 * @todo 支付逻辑无法处理的情况
 * 
 */
class LogicException extends Exception
{
    /**
     * 参数错误
     * 
     */
    const PARAM_ERROR = 0;

    // 自定义属性
    private $data;

    // 构造函数
    public function __construct($message, $code = 0, $data = [])
    {
        // 调用父类的构造函数
        parent::__construct($message, $code);
        $this->data = $data;

        
    }

    public function getResponse()
    {
        return result($this->getCode(), $this->getMessage(), $this->data);
    }
}
