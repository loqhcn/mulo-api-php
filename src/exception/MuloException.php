<?php

namespace mulo\exception;

use Exception;

/**
 * 抛出业务错误 不记录日志，只开发环境显示错误信息， 一般用于参数合法性校验
 */
class MuloException extends Exception
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
