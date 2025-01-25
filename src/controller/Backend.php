<?php

declare(strict_types=1);

namespace mulo\controller;

use mulo\exception\MuloException;
use mulo\facade\Auth;

class Backend extends Common
{
    /**
     * 当前管理员 auth 实例
     *
     * @return Auth
     */
    public function auth()
    {
        return Auth::guard('admin');
    }

    

    /**
     * 批量操作
     *
     * @param collection|array $roles
     * @param \Closure $callback
     * @return void
     */
    public function batchOper($items, \Closure $callback = null)
    {
        $count = \think\facade\Db::transaction(function () use ($items, $callback) {
            $count = 0;

            foreach ($items as $item) {
                if ($callback) {
                    $count += $callback($item);
                } else {
                    $count += $item->delete();
                }
            }

            return $count;
        });

        if ($count) {
            return true;
        } else {
            throw new MuloException('未操作任何行');
        }
    }
}
