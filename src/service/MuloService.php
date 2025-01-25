<?php

declare(strict_types=1);

namespace mulo\service;

use mulo\library\auth\Auth;
use mulo\library\uploader\Uploader;
use mulo\library\captcha\Captcha;
use mulo\library\sms\Sms;
use mulo\library\mail\Mail;
use think\Model;
use mulo\traits\ConfigSet;
use mulo\traits\ValidateExtend;
use mulo\traits\DatabaseOper;
use mulo\tpmodel\Config;
use mulo\library\Redis;
use mulo\library\Client;


class MuloService extends \think\Service
{
    use ConfigSet, ValidateExtend, DatabaseOper;

    public $bind = [
        'auth' => Auth::class,          // 用户认证服务
        'redis' => Redis::class,        // 注册 redis 服务
        'uploader' => Uploader::class,  // 图片上传服务
        'mail' => Mail::class,          // 邮件服务
        'sms' => Sms::class,            // 短信服务
        'captcha' => Captcha::class,    // 验证码服务
        'client' => Client::class       // Http请求服务
    ];
    
    /**
     * 注册自定义服务
     *
     * @return mixed
     */
    public function register()
    {
        // 全局加载其它应用服务
        $apps = glob(app()->getAppPath() . '*', GLOB_ONLYDIR);
        foreach ($apps as $app) {
            $appName = str_replace(app()->getAppPath(), "", $app);
            $appFile = $app . DIRECTORY_SEPARATOR . ucfirst($appName) . '.php';
            if (file_exists($appFile)) {
                $className = '\app\\' . $appName . '\\' . ucfirst($appName);
                $class = new $className;
                $class->boot();
            }
        }
    }

    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        // 覆盖 redis，要放到第一位，下面已经会用到 cache 了
        $this->coverRedisConfig();

        if (!is_installing() && !in_array(current_command('only_name'), ['migrate:run', 'service:discover', 'vendor:publish', 'seed:run'])) {      // 第一次 migrate:run 时一个表也没有，会报错

            // 这时候 model 的 db 还未初始化,手动初始化
            Model::setDb(app('db'));

            $this->coverFilesystemConfig();
            $this->coverCaptchaConfig();
            $this->coverChatConfig();
            $this->coverEasysmsConfig();

            // 扩展表单验证
            $this->validateExtend();

            // 开启 mysql 严格模式
            $this->strictMode();
        }
    }
}
