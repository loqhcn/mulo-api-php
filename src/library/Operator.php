<?php

declare(strict_types=1);

namespace mulo\library;

use mulo\facade\Auth;
use mulo\tpmodel\auth\Admin;
use mulo\tpmodel\user\User;

class Operator
{
    const OPER_TYPE = [
        'admin' => '管理员',
        'user' => '用户',
        'system' => '系统'
    ];
    /**
     * 获取操作人
     */
    public static function get($user = NULL)
    {
        if ($user === NULL) {
            // 自动获取操作人
            $user = self::getDefaultOper();
        }

        if ($user instanceof Admin) {
            $oper = [
                'id' => $user->id,
                'name' => $user->nickname,
                'avatar' => $user->avatar,
                'type' => 'admin',
                'type_text' => (self::OPER_TYPE)['admin']
            ];
        } elseif ($user instanceof User) {
            $oper = [
                'id' => $user->id,
                'name' => $user->nickname,
                'avatar' => $user->avatar,
                'type' => 'user',
                'type_text' => (self::OPER_TYPE)['user']
            ];
        } else {
            $oper = [
                'id' => 0,
                'name' => '',
                'avatar' => '',
                'type' => 'system',
                'type_text' =>  (self::OPER_TYPE)['system']
            ];
        }
        return $oper;
    }

    /**
     * 解析操作人信息
     */
    public static function info($type, $user = NULL)
    {
        return [
            'id' => $user['id'] ?? 0,
            'name' => $user['nickname'] ?? '',
            'avatar' => $user['avatar'] ?? '',
            'type' => $type,
            'type_text' =>  (self::OPER_TYPE)[$type]
        ];
    }

    /**
     * 获取默认操作人
     */
    private static function getDefaultOper()
    {
        $user = NULL;

        if (!request()->isCli()) {
            // 检测管理员登陆
            $user = Auth::guard('admin')->user();
            if (!$user) {
                // 检测用户登陆
                $user = Auth::guard('user')->user();
            }
        }
        return $user;
    }
}
