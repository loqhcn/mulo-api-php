<?php

declare(strict_types=1);

namespace mulo\traits;

use mulo\exception\MuloException;
use think\Validate;
use think\facade\Db;
use mulo\facade\Auth;
use mulo\facade\Captcha;

trait ValidateExtend
{

    /**
     * 扩展表单验证
     *
     * @return void
     */
    public function validateExtend()
    {
        // 添加自定义验证规则
        Validate::maker(function ($validate) {
            $prefix = Db::getConfig('connections.mysql.prefix');        // 数据表前缀

            // 数据表字段唯一验证，当前用户信息
            $validate->extend('sa_me_unique', function ($value, $params) use ($prefix) {
                $params = explode(',', $params);

                // params[0] user(表名) 或者 user-web(表名-guard)
                $tables = explode('-', $params[0]);
                $table = $tables[0] ?? '';
                $guard = $tables[1] ?? $table;

                $field = $params[3] ?? $params[2];
                $current = Auth::guard($guard)->user();
                $except_id = $current[$field] ?? 0;

                $result = Db::table($prefix . $table)
                    ->whereNotNull($params[1])
                    ->where($params[1], '<>', '')
                    ->where($params[1], $value)
                    ->where($field, '<>', $except_id)
                    ->count();

                return $result ? false : true;
            });

            $validate->extend('sa_captcha', function ($code = '') {
                $params = $code ? ['code' => $code] : [];
                return Captcha::check($params);
            });
        });
    }
}
