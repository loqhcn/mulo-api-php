<?php

declare(strict_types=1);

namespace mulo\tpmodel\auth;

use mulo\tpmodel\Common;
use mulo\traits\UnifiedToken;
use mulo\traits\AdminAccess;
use mulo\library\auth\traits\AuthMethod;
use mulo\library\notification\traits\HasDatabaseNotification;
use yunwuxin\notification\Notifiable;

class Admin extends Common
{
    use HasDatabaseNotification, Notifiable, AuthMethod, UnifiedToken, AdminAccess;

    protected $name = 'admin';

    // 自动数据类型转换
    protected $type = [];

    protected $hidden = ['password', 'salt'];

    // 自动 json 转换
    protected $json = [];

    protected $append = [
        'status_text',
        'is_super'
    ];


    public function scopeRoomId($query, $room_id)
    {
        return $query->where('room_id', $room_id);
    }

    /**
     * 获取管理员是否是超级管理员
     *
     * @param void $value
     * @param array $data
     * @return array
     */
    public function getIsSuperAttr($value, $data)
    {
        if(isset($data['role_id']) && $data['role_id'] === 1) {
            return true;
        }
        return false;
    }


    /**
     * 判断管理员是否由特定权限
     *
     * @param \think\Model $admin
     * @param array $rules
     * @return boolean
     */
    public function hasAccess(\think\Model $admin, Array $rules = [])
    {
        if ($admin->is_super) {
            return true;
        }

        $accesses = $this->getAdminAccess($admin);

        if (array_intersect($rules, $accesses['permission'])) {
            return true;
        }

        return false;
    }
}
