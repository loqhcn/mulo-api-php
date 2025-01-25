<?php

declare(strict_types=1);

namespace mulo\traits;

use think\facade\Config;

trait ConfigSet
{

    public function coverChatConfig()
    {
        $chat = config('chat');
        $config = mulo_config('chat');

        $replace = [
            'basic' => [
                'allocate' => $config['basic']['allocate'] ?? 'busy',
                'auto_customer_service' => $config['basic']['auto_customer_service'] ?? 1,
                'last_customer_service' => $config['basic']['last_customer_service'] ?? 1,
            ],
            'system'   =>  [
                'inside_host' => $config['system']['inside_host'] ?? '127.0.0.1',
                'inside_port' => $config['system']['inside_port'] ?? 9191,
                'port' => $config['system']['port'] ?? 2121,
                'ssl' => $config['system']['ssl'] ?? 'none',
                'ssl_cert' => $config['system']['ssl_cert'] ?? '',
                'ssl_key' => $config['system']['ssl_key'] ?? '',
            ],
            'application' => [
                'shop' => [
                    'room_id' => $config['application']['shop']['room_id'] ?? 'shop',
                ]
            ]
        ];
        $config = array_replace_recursive($chat, $replace);
        config($config, 'chat');
    }

    public function coverEasysmsConfig()
    {
        $easysms = config('easysms');
        $config = mulo_config('easysms');
        $replace = [
            'default' => [
                'gateways' => [$config['gateways']],
            ],
            'gateways' => [
                'aliyun' => [
                    'access_key_id' => $config['gateways_aliyun']['access_key_id'],
                    'access_key_secret' => $config['gateways_aliyun']['access_key_secret'],
                    'sign_name' => $config['gateways_aliyun']['sign_name'],
                    'template' => $config['gateways_aliyun']['template']
                ],
                'qcloud' => [
                    'sdk_app_id' => $config['gateways_qcloud']['sdk_app_id'],
                    'app_key' => $config['gateways_qcloud']['app_key'],
                    'sign_name' => $config['gateways_qcloud']['sign_name'],
                    'template' => $config['gateways_qcloud']['template'],
                ],
                'huawei' => [
                    'app_key' => $config['gateways_huawei']['app_key'],
                    'app_secret' => $config['gateways_huawei']['app_secret'],
                    'sign_name' => $config['gateways_huawei']['sign_name'],
                    'endpoint' => $config['gateways_huawei']['endpoint'],
                    'template' => $config['gateways_huawei']['template'],
                ],
                'smsbao' => [
                    'user'  => $config['gateways_smsbao']['user'],
                    'password'   => $config['gateways_smsbao']['password'],
                    'sign_name' => $config['gateways_smsbao']['sign_name'],
                ]
            ]
        ];
        $config = array_replace_recursive($easysms, $replace);
        config($config, 'easysms');
    }
    public function coverCaptchaConfig()
    {
        $captcha = config('captcha');
        $config = mulo_config('basic.login');
        $replace = [
            'captcha' => $config['captcha'],
            'code' => [
                'math'     => $config['captcha_code']['math'],
            ],
            'geetest' => [
                'captcha_id' => $config['captcha_geetest']['captcha_id'],
                'private_key' => $config['captcha_geetest']['private_key']
            ]
        ];
        $config = array_replace_recursive($captcha, $replace);
        config($config, 'captcha');
    }

    public function coverFilesystemConfig()
    {
        $filesystem = config('filesystem');
        $config = mulo_config('filesystem');
        $config = $config?:[];
        
        $replace = [
            'default' => $config['driver'],
            'filesize' => $config['filesize'],
            'imagesize' => $config['imagesize'],
            'extensions' => $config['extensions'],
            'disks'   => [
                'public' => [
                    'url'        => $config['disks']['public']['url'],
                ],
                'aliyun' => [
                    'accessId'     => $config['disks']['aliyun']['access_id'],
                    'accessSecret' => $config['disks']['aliyun']['access_secret'],
                    'bucket'       => $config['disks']['aliyun']['bucket'],
                    'endpoint'     => $config['disks']['aliyun']['endpoint'],
                    'url'          => $config['disks']['aliyun']['url'],
                ],
                'qiniu'  => [
                    'accessKey' => $config['disks']['qiniu']['access_key'],
                    'secretKey' => $config['disks']['qiniu']['secret_key'],
                    'bucket'    => $config['disks']['qiniu']['bucket'],
                    'url'       => $config['disks']['qiniu']['url'],
                ],
                'qcloud' => [
                    'region'      => $config['disks']['qcloud']['region'],
                    'appId'      => $config['disks']['qcloud']['app_id'],
                    'secretId'   => $config['disks']['qcloud']['secret_id'],
                    'secretKey'  => $config['disks']['qcloud']['secret_key'],
                    'bucket'          => $config['disks']['qcloud']['bucket'],
                    'url'          => $config['disks']['qcloud']['url'],
                ]
            ],
        ];
        $config = array_replace_recursive($filesystem, $replace);
        config($config, 'filesystem');
    }

    /**
     * 将 config/redis.php 中的配置覆盖到对应的 cache 和 queue 中
     *
     * @return void
     */
    public function coverRedisConfig()
    {
        $cacheConfig = Config::get('cache');
        $queueConfig = Config::get('queue');

        // 覆盖缓存的 redis 配置
        $cacheRedisConfig = $cacheConfig['stores']['redis'];
        $redisCacheConfig = get_redis_connection($cacheRedisConfig['connection']);
        // unset($cacheRedisConfig['connection']);          // 不能 unset，安装时可能会循环调用 coverRedisConfig
        $cacheConfig['stores']['redis'] = array_merge($cacheRedisConfig, $redisCacheConfig);
        Config::set($cacheConfig, 'cache');

        // 覆盖缓存的 session redis 配置(如果 session 设置为 cache， 并且 store 是 session，可为 session 设置专门的存储库)
        $sessionRedisConfig = $cacheConfig['stores']['session'];
        $redisSessionConfig = get_redis_connection($sessionRedisConfig['connection']);
        // unset($sessionRedisConfig['connection']);        // 不能 unset，安装时可能会循环调用 coverRedisConfig
        $cacheConfig['stores']['session'] = array_merge($sessionRedisConfig, $redisSessionConfig);
        Config::set($cacheConfig, 'cache');

        // 覆盖缓存的 persistent redis 配置(如果 persistent  type 为 redis 才覆盖
        $persistentRedisConfig = $cacheConfig['stores']['persistent'];
        if (strtolower($persistentRedisConfig['type']) == 'redis') {
            $redisPersistentConfig = get_redis_connection($persistentRedisConfig['connection']);
            // unset($persistentRedisConfig['connection']); // 不能 unset，安装时可能会循环调用 coverRedisConfig
            $cacheConfig['stores']['persistent'] = array_merge($persistentRedisConfig, $redisPersistentConfig);
            Config::set($cacheConfig, 'cache');
        }

        // var_dump(json_encode($queueConfig));exit;
        // 覆盖队列中的 redis 配置
        $queueRedisConfig = $queueConfig['connections']['redis'];
        $redisQueueConfig = get_redis_connection($queueRedisConfig['connection']??'redis');

        // var_dump(json_encode($redisQueueConfig));exit;

        // unset($queueRedisConfig['connection']);          // 不能 unset，安装时可能会循环调用 coverRedisConfig
        $queueConfig['connections']['redis'] = array_merge($queueRedisConfig, $redisQueueConfig);

        Config::set($queueConfig, 'queue');
    }
}
