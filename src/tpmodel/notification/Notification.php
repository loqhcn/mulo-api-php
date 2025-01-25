<?php

declare(strict_types=1);

namespace mulo\tpmodel\notification;

use mulo\tpmodel\Common;
use think\Collection;
use mulo\library\notification\NotificationCollection;

class Notification extends Common
{
    /**
     * 主键
     */
    protected $pk = 'id';

    protected $name = 'notification';

    protected $type = [
        'read_time' => 'timestamp',
        'data'      => 'array',
    ];

    public static $notificationType = [
        'system' => '系统消息',
        'shop' => '商城消息',
        // 'site' => '网站消息'
    ];


    public function scopeNotificationType($query, $type)
    {
        if ($type) {
            $query = $query->where('notification_type', $type);
        }

        return $query;
    }


    public function notifiable()
    {
        return $this->morphTo('notifiable', [
            'user' => \mulo\tpmodel\user\User::class,
            'admin' => \mulo\tpmodel\auth\Admin::class,
        ]);
    }


    public function markAsRead()
    {
        if (is_null($this->getData('read_time'))) {
            $this->save(['read_time' => time()]);
        }

        return $this;
    }


    /**
     * 将数据转换成可以显示成键值对的格式
     *
     * @param string $value
     * @param array $data
     * @return array
     */
    public function getDataAttr($value, $data)
    {
        $data = json_decode($data['data'], true);
        if (isset($data['message_type']) && $data['message_type'] == 'notification') {
            $messageData = $data['data'];
            $class = new $data['class_name']();
            $fields = $class->returnField['fields'] ?? [];
            $fields = array_column($fields, null, 'field');

            $newData = [];
            foreach ($messageData as $k => $d) {
                $da = $fields[$k] ?? [];
                if ($da) {
                    $da['value'] = $d;
                    $newData[] = $da;
                }
            }

            $data['data'] = $newData;
        }

        return $data;
    }


    /**
     * 转换数据集为数据集对象
     * @access public
     * @param  array|Collection $collection 数据集
     * @param  string           $resultSetType 数据集类
     * @return Collection
     */
    public function toCollection(iterable $collection = [], string $resultSetType = null): Collection
    {
        return new NotificationCollection($collection);
    }
}
