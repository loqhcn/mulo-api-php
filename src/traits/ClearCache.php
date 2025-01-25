<?php

declare(strict_types=1);

namespace mulo\traits;

use mulo\exception\MuloException;
use mulo\tpmodel\Config as ConfigModel;
use think\facade\Config;
use think\facade\Cache;

trait ClearCache
{

    /**
     * 清除全部缓存
     *
     * @return void
     */
    public function clearAll()
    {
        $this->clearContent();
        $this->clearTemplate();
    }


    /**
     * 清除内容缓存
     *
     * @return void
     */
    public function clearContent()
    {
        // 清空缓存，下面功能会受到影响
        // *、已生成，在有效期内等待验证的图片验证码将失效
        // *、已生成，在有效期内等待验证邮箱验证码将失效
        // *、已经因登录失败次数过多导致被锁定的账号，将被解锁

        $default = config('cache.default');
        $stores = config('cache.stores');

        // 只清空默认驱动的缓存
        Cache::store($default)->clear();

        // $stores = config('cache.stores');
        // $storeKeys = array_keys($stores);
        // foreach ($storeKeys as $key) {
        //     if (!in_array($key, ['session', 'persistent'])) {        // 跳过 session
        //         Cache::store($key)->clear();
        //     }
        // }
    }


    /**
     * 清除模板缓存
     *
     * @return void
     */
    public function clearTemplate() 
    {
        $apps = get_apps();
        // 循环每一个应用的 temp 目录，进行删除
        foreach($apps as $app) {
            $template_path = runtime_path($app . DIRECTORY_SEPARATOR . 'temp');
            rmdirs($template_path);
        }
        // 删除总的 temp(正常多应用模式这个目录是不会存在的)
        rmdirs(runtime_path('temp'));
    }
}
