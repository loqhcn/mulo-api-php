<?php

declare(strict_types=1);

namespace mulo\validate;

use think\Validate;

class Sms extends Validate
{
    protected $regex = [
        'password' => '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]+\S{5,12}$/'
    ];

    protected $rule = [
        'mobile' => 'require|mobile',
        'event' => 'require',
    ];

    protected $message  =   [
        'mobile.require'     => '手机号必须填写',
        'mobile.mobile'     => '手机号格式不正确',
        'mobile.unique'     => '手机号已被占用',
        'mobile.exists'     => '该手机号未注册',

        'event.require' => '缺少event参数',


    ];


    protected $scene = [
    ];

   /**
     * 验证是否存在
     * @access public
     * @param mixed  $value 字段值
     * @param mixed  $rule  验证规则 格式：数据表,字段名,排除ID,主键名
     * @param array  $data  数据
     * @param string $field 验证字段名
     * @return bool
     */
    public function exists($value, $rule, array $data = [], string $field = ''): bool
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }

        if (false !== strpos($rule[0], '\\')) {
            // 指定模型类
            $db = new $rule[0];
        } else {
            $db = $this->db->name($rule[0]);
        }

        $key = $rule[1] ?? $field;
        $map = [];

        if (strpos($key, '^')) {
            // 支持多个字段验证
            $fields = explode('^', $key);
            foreach ($fields as $key) {
                if (isset($data[$key])) {
                    $map[] = [$key, '=', $data[$key]];
                }
            }
        } elseif (isset($data[$field])) {
            $map[] = [$key, '=', $data[$field]];
        } else {
            $map = [];
        }

        $pk = !empty($rule[3]) ? $rule[3] : $db->getPk();

        if (is_string($pk)) {
            if (isset($rule[2])) {
                $map[] = [$pk, '<>', $rule[2]];
            } elseif (isset($data[$pk])) {
                $map[] = [$pk, '<>', $data[$pk]];
            }
        }

        if ($db->where($map)->field($pk)->find()) {
            return true;
        }

        return false;
    }

    public function sceneSmsLogin()
    {
        // 手机号必须存在
        return $this->only(['mobile', 'event'])
        ->append('mobile', 'exists:user');
    }

    public function sceneResetPassword()
    {
        return $this->only(['mobile', 'event'])
        ->append('mobile', 'exists:user');
    }

    public function sceneSmsRegister()
    {

        // 手机号必须唯一
        return $this->only(['mobile', 'code', 'password'])
            ->append('mobile', 'unique:user');
    }

    public function sceneChangeMobile()
    {
        // 手机号必须唯一
        return $this->only(['mobile', 'code'])
            ->append('mobile', 'unique:user');
    }
}
