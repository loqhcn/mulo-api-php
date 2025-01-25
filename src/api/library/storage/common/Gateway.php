<?php

namespace mulo\api\library\storage\common;

use CURLFile;
use mulo\exception\MuloException;

/**
 * 
 * 
 * 
 */
class Gateway
{
    /**
     * @var string 文件名保存规则
     * @todo 定义文件名保存的默认模板
     * @todo 使用七牛云的规则, 其它平台根据这个规则适配
     * 
     * 
     */
    public $saveKey = "upload/$(dirs)/$(etag)$(ext)";

    /**
     * @var string 上传时文件在哪个字段
     * 
     */
    public $name = 'file';

    /**
     * @var Config
     */
    public $config = null;


    /**
     * Gateway constructor.
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }


    /**
     * 通过http请求方式上传
     * @return array
     */
    function runHttpUpload(File $file)
    {
        $flexExt =  $file->getFileExt();

        # 加载上传信息
        $res = $this->getUploadOption($flexExt);
        if ($res['code'] != 200) {
            throw new MuloException('获取上传信息失败', 0, [
                'res' => $res,
            ]);
        }

        // 七牛云上传已通过getUploadOption内的policy设置了上传规则,saveKey无需处理
        $saveKey = $option['saveKey'] ?? '';

        $option = $res['data']['option'];

        $http = \mulo\library\Http::src()
            ->setBeforeResponse(function ($res) {
                return $res;
            });

        $filePath = $file->getPath();

        # 添加文件
        $data = $option['data'] ?: [];
        $cfile = new CURLFile($filePath, mime_content_type($filePath), $file->getName());
        $data[$option['name']] = $cfile;

        $data = $this->parseUploadData($data, $file);

        // throw new MuloException('dev', 0, [
        //     'url'=>$option['url'],
        //     'data'=>$data,
        // ]);

        # 触发请求
        $ret = $http->post($option['url'], $data, [
            'verify' => false, //不校验ssl证书
            'headers' => [
                'Content-Type' => 'multipart/form-data'
            ]
        ]);

        return [
            'ret' => $ret,
            'url' => $option['url'],
            'data' => $data,
            'option' => $option,
        ];
    }



    /**
     * 替换字符串中的占位符 "$(key)"，根据传入的数组进行替换
     *
     * @param string $template 含有占位符的字符串
     * @param array $replacements 替换的键值对数组
     * @return string 替换后的字符串
     */
    function replacePlaceholders($template, $replacements)
    {
        // 使用正则匹配占位符 $(key)
        return preg_replace_callback('/\$\((\w+)\)/', function ($matches) use ($replacements) {
            $key = $matches[1]; // 获取占位符中的 key
            return isset($replacements[$key]) ? $replacements[$key] : $matches[0]; // 替换为数组中的值
        }, $template);
    }

    /**
     * 生成一个文件名
     * 
     */
    function roundFileName()
    {
        return dechex(time()) . bin2hex(random_bytes(8));
    }

    /**
     * 处理上传时的表单数据
     * 
     */
    function parseUploadData($data, File $file)
    {

        return $data;
    }
}
