<?php

declare(strict_types=1);

namespace mulo\facade;

use think\Facade;
use mulo\library\uploader\Uploader as UploaderManager;

/**
 * @see UploaderManager
 * @method static UploaderManager driver(string $storage) 设置驱动
 * @method static UploaderManager getDriver(string $storage) 获取驱动
 * @method static UploaderManager group(string $storage) 设置分组
 * @method static UploaderManager name(\Closure $callback) 自定义文件名
 * @method static UploaderManager file(\think\File $file) 设置上传文件实例
 * @method static UploaderManager upload(think\File $file, string $group, string $storage) 上传图片
 * @method static UploaderManager uploadSim(think\File $file, string $group, string $storage) 上传图片,不存数据库
 * @method static UploaderManager redeposit(string $path, string $save_path, string $storage) 路径转存图片,不存数据库
 * @method static boolean has(string $path) 检测文件是否存在,$path 不含域名
 * @method static boolean delete(string $path) 删除图片 $path 不含域名
 */
class Uploader extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'uploader';
    }
}
