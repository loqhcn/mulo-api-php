<?php

declare(strict_types=1);

namespace mulo\library\notification;

use think\model\Collection;

class NotificationCollection extends Collection
{
    /**
     * 标记已读
     */
    public function markAsRead()
    {
        $this->each(function ($notification) {
            $notification->markAsRead();
        });
    }
}