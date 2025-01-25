<?php

declare(strict_types=1);

namespace mulo\controller;

use app\BaseController;
use mulo\exception\MuloException;

class Common extends BaseController
{

    /**
     * 参数验证 By Mulo-admin
     *
     * @param array $params
     * @param string $validator
     * @return void
     */
    protected function svalidate(array $params, string $validator = "")
    {
        if (false !== strpos($validator, '.')) {
            // 是否支持场景验证
            [$validator, $scene] = explode('.', $validator);
        }
        // 获取validate实例
        $class = false !== strpos($validator, '\\') ? $validator : str_replace("controller", "validate", get_class($this));
        if (!class_exists($class)) {
            return;
        }

        $validate     = new $class();
        // 添加场景验证
        if (!empty($scene)) {
            $validate->scene($scene);
        } else {
            // halt(22);
            // 只验证传入参数
            $validate->only(array_keys($params));
        }
        // 失败自动抛出异常信息
        return $validate->failException(true)->check($params);
    }



    /**
     * 过滤前端发来的短时间内的重复的请求
     *
     * @return void
     */
    public function repeatFilter($key = null, $expire = 5)
    {
        if (!$key) {
            $httpName = app('http')->getName();
            $url = request()->baseUrl();
            $ip = request()->ip();

            $key = $httpName . ':' . $url . ':' . $ip;
        }

        if (cache()->store('persistent')->has($key)) {
            throw new \Exception('请稍后再试');
        }

        // 缓存 5 秒
        cache()->store('persistent')->tag('repeat_filter')->set($key, time(), $expire);
    }




    /**
     * 监听数据库 sql
     *
     * @return void
     */
    public function dbListen()
    {
        \think\facade\Db::listen(function ($sql, $time) {
            echo $sql . '<br/>' . $time;
        });
    }



    /**
     * 获取请求的 access
     *
     * @return string
     */
    public function accessName()
    {
        $root = substr(request()->root(), 1);
        $controller = request()->controller();
        $action = request()->action();
        $access = strtolower("{$root}.{$controller}.{$action}");
        return $access;
    }
}
