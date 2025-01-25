<?php

namespace mulo\api\traits;

use mulo\exception\MuloException;

trait DefineHook
{

    public $hooks = [];

    /**
     * 定义钩子
     * @todo 
     * @param array $hooks 钩子处理列表
     * @example setHooks(['hanle.form_rule.desc'=>function($params){  return $params; }])
     * 
     * 
     */
    function setHooks(array $hooks)
    {
        $this->hooks = $hooks;
        return $this;
    }

    /**
     * 定义单个钩子
     * 
     * 
     */
    function setHook($name, $callback)
    {
        // $this->hooks = $hooks;
        return $this;
    }

    /**
     * 判断钩子是否定义
     * @return bool;
     */
    function hasHook($name)
    {
        return isset($this->hooks[$name]) && $this->hooks[$name];
    }

    /**
     * 处理钩子
     * @param string $name hook的名称,规则为'xxx.xx.xx'
     * @param any $params 参数
     * @param string|null $returnType 规定返回数据类型,param类型为根据传入参数判断
     * 
     * 
     */
    function handleHook($name, $params = [], $returnType = 'param')
    {
        if ($this->hasHook($name)) {
            $ret = call_user_func($this->hooks[$name], $params, $returnType);
            if ($returnType && $returnType != 'none') {

                if ($returnType == 'bool') {
                    if (!is_bool($params)) {
                        throw new MuloException("定义hook `{$name}` 必须返回类型为 `{$returnType}` 的数据", 0, [
                            'demo' => "['{$name}'=>function(){  return true; }]"
                        ]);
                    }
                } else if ($returnType == 'param') {
                    if (gettype($params) != gettype($ret)) {
                        throw new MuloException("定义hook `{$name}` 必须返回类型为 `{$returnType}` 的数据", 0, [
                            'demo' => "['{$name}'=>function(\$param,\$type){  return \$param; }]",
                            'paramType'=>gettype($params),
                            'returnType'=>gettype($ret),
                        ]);
                    }
                } else if (!$ret && $returnType != 'none') {
                    throw new MuloException("定义hook `{$name}` 必须返回类型为 `{$returnType}` 的数据", 0, [
                        'demo' => "['{$name}'=>function(\$param,\$type){  return \$param; }]"
                    ]);
                }
            }
            return $ret;
        }

        return $params;
    }
}
