<?php

namespace mulo\api\hook;



class HookTool
{
    // 验证登录

    public $modelNames = [];

    /**
     * 
     */
    public function __construct(public array $hooks)
    {
       
    }

    static function instance($hooks)
    {

        return new self($hooks);
    }

    function setModelNames(array $names)
    {
        foreach ($names as $key => $name) {
            $this->setModelName($name);
        }
        return $this;
    }

    function unsetModelNames(array $names)
    {

        return $this;
    }

    function setModelName($name)
    {
        if (!in_array($name, $this->modelNames)) {
        }
        return $this;
    }

    /**
     * 
     */
    function unsetModelName()
    {
    }

    /**
     * 注入登录验证
     * 
     */
    function injectLogin($hookName, callable $callback)
    {
        $this->hooks[$hookName] = function ($request, $type) use ($callback) {
            call_user_func($callback);
            return $request;
        };
        return $this;
    }


    /**
     * 导出钩子设置
     * 
     * @return array 
     */
    function dest()
    {
        
        return $this->hooks;
    }
}
