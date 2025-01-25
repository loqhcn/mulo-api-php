<?php

declare(strict_types=1);

namespace mulo\job;

use think\queue\Job;

class Test extends BaseJob
{
    /**
     * 普通优先级队列测试
     */
    public function mulo(Job $job, $data)
    {
        // 创建目录
        $this->mkdir();
        
        // 写入日志文件
        $filename = root_path('runtime/storage/queue') . 'mulo.log';
        file_put_contents($filename, date('Y-m-d H:i:s'));

        $job->delete();
    }


    /**
     * 高优先级队列测试
     */
    public function muloHigh(Job $job, $data)
    {
        // 创建目录
        $this->mkdir();

        // 写入日志文件
        $filename = root_path('runtime/storage/queue') . 'mulo-high.log';
        file_put_contents($filename, date('Y-m-d H:i:s'));

        $job->delete();
    }


    /**
     * 创建目录
     */
    private function mkdir()
    {
        $dir = root_path('runtime/storage/queue');
        @mkdir($dir, 0755, true);
    }
}
