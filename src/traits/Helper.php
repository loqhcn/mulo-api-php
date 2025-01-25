<?php

declare(strict_types=1);

namespace mulo\traits;

use mulo\exception\MuloException;
use think\facade\Db;
use think\facade\Config;

trait Helper
{

    /**
     * 更新配置值，Config::set 不好用
     */
    public function setConfig($key, $value) {
        $keyArr = explode('.', $key);
        $value = is_integer($value) ? $value : strip_tags($value);
        $parent_code = $keyArr[0] ?? '';

        if ($parent_code) {
            $config = config($parent_code);

            // 删除配置分组
            unset($keyArr[0]);
            $keyArr = array_values($keyArr);

            // 组合配置 key
            $configKey = '$config';
            foreach ($keyArr as $ks) {
                $configKey .= "['" . $ks . "']";
            }

            // 给配置赋值
            eval("isset($configKey) ? $configKey" . "=" . "'$value' : '';");

            Config::set($config, $parent_code);
        }
    }
}
