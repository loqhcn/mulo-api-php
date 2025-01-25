<?php

declare(strict_types=1);

namespace mulo\library\notification\traits;

use think\Model;
use mulo\tpmodel\notification\Notification;

/**
 * notification 通知方法
 */
trait HasDatabaseNotification
{
    /**
     * 所有通知
     */
    public function notifications()
    {
        // notifiable_type
        $notifiable_type = $this->getNotifiableType();
        return $this->morphMany(Notification::class, 'notifiable', $notifiable_type)
            ->order('create_time', 'desc');
    }

    /**
     * 未读通知
     */
    public function unreadNotifications()
    {
        // notifiable_type
        $notifiable_type = $this->getNotifiableType();
        return $this->morphMany(Notification::class, 'notifiable', $notifiable_type)
            ->where('read_time', null)
            ->order('create_time', 'desc');
    }

    public function prepareDatabase()
    {
        return $this->notifications();
    }


    /**
     * 获取 notifiable 身份类型 admin， user
     *
     * @return void
     */
    public function getNotifiableType()
    {   
        $notifiable_type = str_replace('\\', '', strtolower(strrchr(static::class, '\\')));
        return $notifiable_type;
    }
}