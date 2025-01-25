<?php

declare(strict_types=1);

namespace mulo\library\uploader;

use think\facade\Filesystem;
use mulo\exception\MuloException;
use mulo\facade\Auth;
use mulo\facade\Client;
use mulo\tpmodel\Config;
use app\file\model\File as FileModel;
use app\file\model\FileGroup as FileGroupModel;

class Uploader
{
    /**
     * 上传配置
     */
    protected $config = null;
    /**
     * 上传驱动
     */
    protected $driver = null;
    /**
     * 上传文件 \think\File
     */
    protected $file = null;
    /**
     * 上传分组
     */
    protected $group = 'default';
    /**
     * 上传文件 md5
     */
    protected $fileMd5 = null;

    /**
     * 自定义文件名
     * @var Closure
     */
    protected $customeFileName = null;
    /**
     * 上传文件 fileModel 实例
     */
    protected $fileModel = null;
    /**
     * 图片宽度（仅图片存在）
     */
    protected $imageWidth = 0;
    /**
     * 图片高度（仅图片存在）
     */
    protected $imageHeight = 0;

    /**
     * 允许的后缀
     *
     * @var string|array
     */
    protected $allowExtensions = null;

    public function __construct()
    {
        $this->config = config('filesystem');
        $this->driver = $this->getDefaultDriver();
    }


    /**
     * 上传文件
     *
     * @param \think\File $file
     * @param string $group
     * @param string $driver
     * @return array
     */
    public function upload($file, $group = 'default', $driver = null)
    {
        // 设置 file
        $this->file($file);

        // 验证文件
        $this->validate();

        // 设置文件分组
        $this->group($group);

        // 更新并设置当前驱动
        if ($driver) {
            $this->driver($driver);
        }

        // 文件 md5 值
        $this->fileMd5 = $file->hash('md5');
        if ($this->checkFileExists()) {
            $result = $this->getResultPathUrl($this->fileModel);
        } else {
            // 上传文件
            $path = $this->getUploader()->putFile($this->pathPrefix(), $file, function ($file) {
                return $this->getFileName();
            });

            // 获取上传后路径和 url
            $result = $this->getResultPathUrl($path);        // path, fullurl
        }

        // 存入 file 表
        $this->saveFileModel([
            'url' => $result['path'],
        ]);

        return $result;
    }



    /**
     * 简单上传文件，不存文件管理
     *
     * @param \think\File $file
     * @param string $group
     * @param string $driver
     * @return array
     */
    public function uploadSim($file, $group = 'default', $driver = null)
    {
        // 设置 file
        $this->file($file);

        // 验证文件
        $this->validate();

        // 设置文件分组
        $this->group($group);

        // 更新并设置当前驱动
        if ($driver) {
            $this->driver($driver);
        }

        // 上传文件
        $path = $this->getUploader()->putFile($this->pathPrefix(), $file, function ($file) {
            return $this->getFileName();
        });

        // 获取上传后路径和 url
        $result = $this->getResultPathUrl($path);        // path, fullurl

        return $result;
    }



    /**
     * 简单转存文件，不存文件管理
     *
     * @param string $path  本地绝对路径， 或者网络路径
     * @param string $save_path 固定的存储路径
     * @param string $driver
     * @return array
     */
    public function redeposit($path, $save_path, $driver = null)
    {
        if (is_url($path)) {        // 本地或者远程路径
            $response = Client::httpGet($path);
            $body = $response->getBody()->getContents();    // 图片数据流
            @mkdir(root_path('runtime/storage/temp'), 0755, true);
            $temp_path = root_path('runtime/storage/temp') . basename($save_path);
            file_put_contents($temp_path, $body);       // 转存本地
            $is_del_temp = true;
        } else if (is_string($path) && @is_file($path)) {
            $temp_path = $path;
            $is_del_temp = false;
        }

        if (!isset($temp_path) || empty($temp_path) || empty($save_path)) {
            throw new MuloException("上传失败，不是一个有效的文件: " . $path);
        }

        $file = new \think\File($temp_path);
        if ($driver) {
            $this->driver($driver);
        }

        // 本地不能带 storage,对象存储必须带 storage/
        $save_path = in_array($this->driver, ['aliyun', 'qiniu', 'qcloud']) ? ltrim($save_path, '/') : str_replace('storage/', '', ltrim($save_path, '/'));
        // 上传文件
        $path = $this->getUploader()->putFileAs('', $file, $save_path);
        if ($is_del_temp) {
            @unlink($temp_path);        // 删除临时文件
        }

        // 获取上传后路径和 url
        $result = $this->getResultPathUrl($path);        // path, fullurl

        return $result;
    }



    /**
     * 查询要上传的图片是否已经在对应驱动传过了
     *
     * @return \think\Model
     */
    protected function checkFileExists()
    {
        $this->fileModel = FileModel::where('file_md5', $this->fileMd5)->where('storage', $this->driver)->where('group', $this->group)->find();

        if ($this->fileModel && $this->has($this->fileModel->url)) {
            // 有记录，并且文件也存在
            return true;
        } else if ($this->fileModel) {
            $this->fileModel->delete(); // 删除记录
        }

        return false;
    }


    /**
     * 文件是否存在
     *
     * @param string $path
     * @return boolean
     */
    public function has($path)
    {
        $path = $this->getStoragePath($path);
        return $this->getUploader()->has($path);
    }


    /**
     * 删除文件
     *
     * @param string $path
     * @return boolean
     */
    public function delete($path)
    {
        $path = $this->getStoragePath($path);
        return $this->getUploader()->delete($path);
    }


    /**
     * 存入 file 数据库表
     *
     * @param array $fileData
     * @return FileModel
     */
    protected function saveFileModel($fileData)
    {
        $fileModel = $this->fileModel ?: new FileModel();

        $data = array_merge([
            'admin_id' => Auth::guard('admin')->id() ?: 0,
            'image_width' => $this->imageWidth,
            'image_height' => $this->imageHeight,
            'group' => $this->group,
            'extension' => ($this->getPrefixExtension() ? $this->getPrefixExtension() . '.' : '') . strtolower($this->file->extension()),
            'filename' => $this->file->getOriginalName(),
            'filesize' => $this->file->getSize(),
            'mimetype' => $this->file->getMime(),
            'file_md5' => $this->file->hash('md5'),
            'storage' => $this->driver
        ], $fileData);

        $fileModel->force()->save($data);       // 如果没有变化， 连updatetime 也不会变，所以加上 force

        // 检查并且创建文件分组
        $groups = FileGroupModel::cache('db_file_group_groups', 3600)->column('group');
        if (!in_array($this->group, $groups)) {
            $groupData = [
                'name' => $this->group,
                'group' => $this->group
            ];
            try {
                (new FileGroupModel)->cache('db_file_group_groups')->save($groupData);
            } catch (\Exception $e) {
                // 捕获异常，并丢弃，group 是唯一字段
            }
        }

        return $fileModel;
    }



    /**
     * 拼接获取上传的基础文件夹前缀
     *
     * @param string $group
     * @return void
     */
    protected function pathPrefix()
    {
        $path = $this->group;
        if (in_array($this->driver, ['aliyun', 'qiniu', 'qcloud'])) {
            // 阿里云，七牛云，腾讯云，不会自己拼接 config 中的 root，导致上传的地址不带 storage 目录
            $config = $this->getDiskConfig();
            $path = $config['root'] . '/' . $path;
        }

        return ltrim($path);
    }


    /**
     * 获取上传文件名
     *
     * @return string
     */
    protected function getFileName()
    {
        if ($this->customeFileName) {
            // 自定义文件名
            return ($this->customeFileName)($this->file);
        } else {
            $prefix_ext = $this->getPrefixExtension();
            // 自定义文件路径和文件名，使用文件 md5, 同一个文件，会覆盖
            return date('Ymd') . '/' . $this->file->hash('md5') . ($prefix_ext ? '.' . $prefix_ext : '');
        }
    }


    /**
     * 获取文件后缀的前缀，比如 tar.gz 的 tar
     *
     * @return string
     */
    protected function getPrefixExtension()
    {
        // 处理多重后缀的文件，比如 .tar.gz
        $ext = $this->file->extension();
        $prefix_ext = '';
        if (in_array($ext, ['gz', 'xz'])) {
            $prefix_ext = 'tar';
        }

        return $prefix_ext;
    }


    /**
     * 获取最终返回的图片path 和 url
     *
     * @param string|\think\model $info
     * @return void
     */
    protected function getResultPathUrl($info)
    {
        if ($info instanceof \think\model) {
            $result = $this->getPathUrlByModel($info);
        } else {
            $result = $this->getPathUrlByUpload($info);
        }

        return $result;
    }


    /**
     * 根据上传结果获取完整地址
     *
     * @param string $path
     * @return array
     */
    protected function getPathUrlByUpload($path)
    {
        $config = $this->getDiskConfig();

        switch ($this->driver) {
            case 'local':
                // 不可访问，所以这个不拼接任何路径
                $path = '/' . $path;
                $fullurl = $path;       // 这地方不暴漏项目绝对地址
                break;
            case 'public':
                $prefixPath = explode('public', $config['root']);
                $path = ($prefixPath[1] ?? '')  . '/' . $path;      // 拼接上 /storeage
                $fullurl = domainurl($path, true);                        // 检测如果 url 未设置，拼接上当前访问域名
                break;
            default:
                $path = '/' . $path;
                $fullurl = $config['url'] . $path;                  // 拼接上对应对象存储 url 的域名
                break;
        }

        return compact("path", "fullurl");
    }


    /**
     * 根据 Model 记录获取完整地址
     *
     * @param string $path
     * @return array
     */
    protected function getPathUrlByModel($fileModel)
    {
        $config = $this->getDiskConfig();
        $path = $fileModel->url;

        switch ($this->driver) {
            case 'local':
                // 不可访问，所以这个不拼接任何路径
                $fullurl = $path;
                break;
            case 'public':
                $fullurl = domainurl($path, true);                        // 检测如果 url 未设置，拼接上当前访问域名
                break;
            default:
                $fullurl = $config['url'] . $path;                  // 拼接上对应对象存储 url 的域名
                break;
        }

        return compact("path", "fullurl");
    }



    /**
     * 获取 驱动中文件的路径，主要针对 public 驱动
     *
     * @param string $path  不含域名
     * @return string
     */
    protected function getStoragePath($path)
    {
        $config = $this->getDiskConfig();
        switch ($this->driver) {
            case 'public':
                $prefixPath = explode('public', $config['root']);
                $prefixPath = $prefixPath[1] ?? '';
                $path = str_replace($prefixPath, '', $path);
                break;
            default:
                $path = $path;                  // 拼接上对应对象存储 url 的域名
                break;
        }

        return $path;
    }


    /**
     * 获取上传实例
     *
     * @return \think\Filesystem
     */
    public function getUploader()
    {
        return Filesystem::disk($this->driver);
    }


    /**
     * 验证文件格式，大小
     *
     * @param think\File $file
     * @return void
     */
    public function validate()
    {
        $type = $this->isImage() ? 'image' : 'file';

        $rules = [
            'file' => 'require|' . $type . '|fileExt:' . join(',', $this->allowExtensions()) . '|fileSize:' . $this->allowFilesize()
        ];

        $message = [
            'file.require' => '请选择要上传文件',
            'file.image' => '请上传正确的图片',
            'file.file' => '请上传正确的文件',
            'file.fileExt' => '文件格式不支持，请上传' . join(',', $this->allowExtensions()) . '格式的文件',
            'file.fileSize' => '文件大小超出限制，最大' . $this->allowFilesize(false) . 'M',
        ];

        // 验证文件格式
        validate($rules, $message)->check(['file' => $this->file]);
    }



    /**
     * 是否是图片
     *
     * @return boolean
     */
    public function isImage()
    {
        $extension = $this->file ? strtolower($this->file->extension()) : null;
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];

        if (in_array($extension, $imageExtensions)) {
            $imgInfo = getimagesize($this->file->getRealPath());
            if ($imgInfo) {
                $this->imageWidth = $imgInfo[0] ?? 0;
                $this->imageHeight = $imgInfo[1] ?? 0;
            }

            return false;
        }

        return false;
    }



    /**
     * 获取允许的上传名
     *
     * @return array
     */
    public function allowExtensions()
    {
        $extensions = $this->allowExtensions ?: $this->config['extensions'];
        $extensions = is_string($extensions) ? explode(',', $extensions) : $extensions;
        return $extensions;
    }


    /**
     * 设置允许上传的后缀
     *
     * @param string|array $extensions
     * @return Uploader
     */
    public function extension($extensions)
    {
        $this->allowExtensions = $extensions;

        return $this;
    }


    /**
     * 获取允许的上传大小
     *
     * @param boolean $is_byte  是否是字节
     * @return int
     */
    public function allowFilesize($is_byte = true)
    {
        $type = $this->isImage() ? 'image' : 'file';
        $filesize = $this->config[$type . 'size'];      // 单位 M
        if ($is_byte) {
            $filesize = $filesize * 1024 * 1024;        // 单位 B
        }
        return $filesize;
    }


    /**
     * 获取当前 disk 配置
     *
     * @return array
     */
    public function getDiskConfig()
    {
        return $this->config['disks'][$this->driver];
    }



    /**
     * 设置当前图片的 uploa
     *
     * @param think\File $file
     * @return Uploader
     */
    public function file($file)
    {
        $this->file = $file;

        return $this;
    }


    /**
     * 获取当前驱动
     *
     * @return void
     */
    public function getDriver()
    {
        return $this->driver;
    }


    /**
     * 设置驱动
     *
     * @param string $driver
     * @return Uploader
     */
    public function driver($driver)
    {
        if ($driver) {
            $this->driver = $driver;
        }

        return $this;
    }


    /**
     * 设置分组
     *
     * @param string $driver
     * @return Uploader
     */
    public function group($group)
    {
        if ($group) {
            $this->group = $group;
        }

        return $this;
    }

    /**
     * 设置保存文件名
     *
     * @param \Closure
     * @return Uploader
     */
    public function name(\Closure $customeFileName)
    {
        $this->customeFileName = $customeFileName;

        return $this;
    }


    /**
     * 获取当前默认驱动
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config['default'] ?? 'public';
    }
}
