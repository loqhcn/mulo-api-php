<?php

declare(strict_types=1);

namespace mulo\library\sms\message;

use Overtrue\EasySms\Contracts\GatewayInterface;
use mulo\exception\MuloException;
use app\admin\model\Sms;

/**
 * 短信验证码消息类
 */
class CodeMessage extends Message
{
    protected $code;

    protected $expire = 300;

    protected $maxTimes = 3;

    protected $defaultContent = '您的短信验证码为：{code}, 五分钟有效，请勿告诉他人';


    /**
     * 获取数据，包含验证码
     *
     * @param GatewayInterface $gateway
     * @return void
     */
    public function getData(GatewayInterface $gateway = null)
    {
        $data = is_callable($this->data) ? call_user_func($this->data, $gateway) : $this->data;
        if (!$data) {
            $gatewayConfig = $this->getGatewayConfig($gateway->getName());
            $data = [
                'sign_name' => $gatewayConfig['sign_name'] ?? '',
                'code' => $this->code ?: $this->makeCode()
            ];
        }

        return $data;
    }


    /**
     * 生成随机验证码
     *
     * @return string
     */
    public function makeCode()
    {
        $this->code = mt_rand(1000, 9999);

        $smsData = [
            'event' => $this->event,
            'mobile' => $this->mobile
        ];
        $sms = \app\admin\model\Sms::where($smsData)->find();
        $sms = $sms ?: new Sms;

        $smsData['code'] = $this->code;
        $smsData['times'] = 0;
        $smsData['ip'] = request()->ip();
        $smsData['create_time'] = time();
        $result = $sms->save($smsData);

        return $this->code;
    }


    /**
     * 验证短信验证码
     *
     * @param string $code
     * @param string $exception
     * @return boolean
     */
    public function check($code, $exception = true): bool
    {
        $time = date('Y-m-d H:i:s', time() - $this->expire);
        $smsData = [
            'event' => $this->event,
            'mobile' => $this->mobile
        ];
        $sms = \app\admin\model\Sms::where($smsData)->find();
        if (!$sms) {
            if ($exception) throw new MuloException('验证码不正确');
            return false;
        }

        if ($sms['create_time'] < $time || $sms['times'] >= $this->maxTimes) {
            // 过期则清空该手机验证码
            \app\admin\model\Sms::where($smsData)->delete();
            if ($exception) throw new MuloException('验证码不正确');
            return false;
        }

        if ($code != $sms['code']) {
            $sms->times = $sms->times + 1;
            $sms->save();
            if ($exception) throw new MuloException('验证码不正确');
            return false;
        }

        // 验证成功，删除验证码
        \app\admin\model\Sms::where($smsData)->delete();
        return true;
    }
}
