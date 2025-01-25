<?php

declare(strict_types=1);

namespace mulo\job;

use think\queue\Job;
use think\facade\Log;

class BaseJob
{
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }


    /**
     * 失败事件
     */
    public function failed($data)
    {
        // 失败队列，这里不用添加了， 后续程序会自动添加，这里可以发送邮件或者通知
    }

    /**
     * 记录详细错误日志
     * @return void
     */
    public function logError($error, $name = 'QUEUE'): void
    {
        $logInfo = [
            "========== $name LOG INFO BEGIN ==========",
            '[ Message ] ' . var_export('[' . $error->getCode() . ']' . $error->getMessage(), true),
            '[ File ] ' . var_export($error->getFile() . ':' . $error->getLine(), true),
            '[ Trace ] ' . var_export($error->getTrace(), true),
            "============================================= $name LOG INFO ENDED ==========",
        ];

        $logInfo = implode(PHP_EOL, $logInfo) . PHP_EOL;
        Log::error($logInfo);
    }
}
