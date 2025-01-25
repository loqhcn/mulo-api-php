<?php

declare(strict_types=1);

namespace mulo\tpmodel\user;

use mulo\tpmodel\Common;
use mulo\traits\UnifiedToken;
use mulo\library\auth\traits\AuthMethod;
use mulo\library\notification\traits\HasDatabaseNotification;
use yunwuxin\notification\Notifiable;
use think\model\concern\SoftDelete;
use mulo\tpmodel\ThirdOauth;

class User extends Common
{
    use HasDatabaseNotification, Notifiable, AuthMethod, UnifiedToken, SoftDelete;

    protected $name = 'user';

    // 自动数据类型转换
    protected $type = [
        'login_time' => 'timestamp',
    ];

    protected $hidden = ['password', 'salt'];

    // 自动 json 转换
    protected $json = [];

    protected $append = [
        'status_text',
        'gender_text'
    ];

    /**
     * 获取性别文字
     * @param   string $value
     * @param   array  $data
     * @return  object
     */
    public function getGenderTextAttr($value, $data)
    {
        $value = $value ?: ($data['gender'] ?? 0);

        $list = [0 => '未知', 1 => '男', 2 => '女'];
        return isset($list[$value]) ? $list[$value] : '';
    }



    /**
     * 登录账号需要查询的字段
     *
     * @return array
     */
    public function accountname()
    {
        return ['username', 'mobile', 'email'];
    }


    /**
     * 登录账号需要查询的字段
     *
     * @return array
     */
    public function getAvatarAttr($value, $data)
    {
        if (empty($value)) {
            $config = \mulo\tpmodel\Config::getConfigs('basic.user');
            return $config['avatar'];
        }
        return $value;
    }

    public function getVerificationAttr($value, $data)
    {
        $verification['username'] = !empty($data['username']);
        $verification['mobile'] = !empty($data['mobile']);
        $verification['password'] = !empty($data['password']);
        $verification['email'] = !empty($data['email']);

        return $verification;
    }

    public function thirdOauth()
    {
        return $this->hasMany(ThirdOauth::class, 'user_id', 'id');
    }
}
