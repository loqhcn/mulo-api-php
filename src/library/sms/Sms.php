<?php

declare(strict_types=1);

namespace mulo\library\sms;

use mulo\exception\MuloException;
use mulo\library\sms\message\CodeMessage;
use mulo\library\sms\message\NoticeMessage;
use think\facade\Log;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\EasySms;

class Sms
{

    /**
     * easysms 短信服务
     */
    protected $easysms = null;

    public function __construct()
    {
        $this->easysms = new EasySms(config('easysms'));     // easysms 服务
    }


    /**
     * 发送短信验证码
     *
     * @param string $mobile
     * @param string $event
     * @return array
     */
    public function send($mobile, $event)
    {
        try {
            // 发送短信验证码
            $result = $this->easysms->send($mobile, (new CodeMessage)->init($mobile, $event));
        } catch (NoGatewayAvailableException $e) {
            // 记录发送结果日志
            Log::error('smsEasysmsError:' . json_encode($e->getLastException()->getMessage(), JSON_UNESCAPED_UNICODE));
            // 抛出异常
            throw new MuloException($e->getLastException()->getMessage());
        } catch (\Exception $e) {
            // 记录错误结果日志
            Log::error('smsExceptionError:' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE));
            // 抛出异常
            throw new MuloException('短信发送失败');
        }

        return $result;
    }


    /**
     * 发送通知
     *
     * @param string $mobile
     * @param string $event
     * @return void
     */
    public function sendNotice($mobile, $message)
    {
        try {
            // 发送短信验证码
            $result = $this->easysms->send($mobile, $message);
        } catch (NoGatewayAvailableException $e) {
            // 记录发送结果日志
            Log::error('smsNoticeEasysmsError:' . json_encode($e->getLastException()->getMessage(), JSON_UNESCAPED_UNICODE));
            // 抛出异常
            throw new MuloException($e->getLastException()->getMessage());
        } catch (\Exception $e) {
            // 记录错误结果日志
            Log::error('smsNoticeExceptionError:' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE));
            // 抛出异常
            throw new MuloException('短信通知发送失败');
        }

        return $result;
    }


    /**
     * 检测验证码是否正确
     *
     * @param string $mobile
     * @param string $event
     * @param string $code
     * @return boolean
     */
    public function check($mobile, $event, $code, $exception = true)
    {
        return (new CodeMessage)->init($mobile, $event)->check($code, $exception);
    }
}
