<?php

namespace mulo\api\library\storage\gateways;

use mulo\api\library\storage\common\File;
use mulo\api\library\storage\common\Gateway;
use mulo\exception\MuloException;

use OSS\OssClient;
use OSS\Core\OssException;

/**
 * 阿里云oss上传
 * 
 */
class AliossGateway extends Gateway
{
    public $url = "";

    public $name = 'file';

    /**
     * @var int 生成上传 token，有效期为 2 小时（7200 秒）
     */
    public $expires = 7200;


    /**
     * 上传文件
     * @todo 使用前端上传的方式推送
     * 
     */
    function upload(File $file)
    {
        # 执行上传
        $upRet = $this->runHttpUpload($file);
        $ret = $upRet['ret'];
        $url = $upRet['url'];
        $data = $upRet['data'];
        $option = $upRet['option'];

        if ($ret['code'] != 200) {
            return handleResult(200, "上传失败", [
                'ret' => $ret
            ]);
        }

        # 处理放回结果
        $url = '/' . $data['key'];
        $fullUrl = $option['domain'] . $url;

        return handleResult(200, "上传", [
            'url' => $url,
            'fullUrl' => $fullUrl,
            'ret' => $ret,
        ]);
    }


    /**
     * 
     * @param string $fileExt 文件扩展名(后端上传时设置)
     * 
     */
    function getUploadOption($fileExt = '')
    {
        $config = $this->config->getConfig();

        # 设置上传文件的过期时间，单位为秒
        $expire = $this->expires;
        $now = time();
        $end = $now + $expire;
        $expiration = gmdate('Y-m-d\TH:i:s\Z', $end);

        # 上传信息
        $maxSize = 1048576000; // 文件多大1GB
        $replace = [
            'dirs' => date('Ymd'),
            // 'ext' => "." . $file->getFileExt(),
        ];
        if ($fileExt) {
            $replace['ext'] = "." . $fileExt;
        }
        $saveKey  = $this->replacePlaceholders($this->saveKey, $replace);
        $saveDir =  pathinfo($saveKey, PATHINFO_DIRNAME);
        // 设置上传策略
        $conditions = [
            ['content-length-range', 0, $maxSize], // 限制文件大小
            ['starts-with', '$key', $saveDir] // 限制上传文件的路径必须以 $dir 开头
        ];
        $policy = json_encode([
            'expiration' => $expiration, // 过期时间
            'conditions' => $conditions
        ]);

        # 签名
        $base64Policy = base64_encode($policy);
        $signature = base64_encode(hash_hmac('sha1', $base64Policy, $config['secretKey'], true));

        $url = "https://{$config['bucket']}.{$config['region']}.aliyuncs.com";
        # 选项数据
        $data = [
            'signature' => $signature,
            'success_action_status' => 200,
            'OSSAccessKeyId' => $config['accessKey'],
            'policy' => $base64Policy,
            'key' => $saveKey,
        ];
        return handleResult(200, "获取成功", [
            'optionType' => 'option',
            'expires' => $end,
            'option' => [
                'url' => $url,        //链接
                'name' => $this->name,      //文件在哪个字段
                'saveKey' => $saveKey,      //保存文件的路径规则
                'data' => $data,            //post请求数据
                'domain' => $config['domain'],
            ]
        ]);
    }

    /**
     * 处理上传时的表单数据
     * @todo 生成文件名
     * 
     */
    function parseUploadData($data, File $file)
    {
        if (isset($data['key'])) {
            $data['key'] = $this->replacePlaceholders($data['key'], [
                'etag' => $file->getFileSha1()
            ]);
        }
        return $data;
    }



    // /**
    //  * 上传文件
    //  * 
    //  */
    // function upload(File $file)
    // {

    //     # 加载配置与设置保存信息
    //     $config = $this->config->getConfig();

    //     $saveKey  = $file->replacePlaceholders($file->saveKey, [
    //         'dirs' => date('Ymd'),
    //         'etag' => $file->roundFileName(),
    //         'ext' => "." . $file->getFileExt(),
    //     ]);
    //     $filePath = $file->getPath();


    //     $ret = null;
    //     try {
    //         // 实例化 OSSClient 对象
    //         $ossClient = new OssClient($config['accessKey'], $config['secretKey'], $config['endpoint']);

    //         // 上传本地文件到 OSS
    //         $ret = $ossClient->uploadFile($config['bucket'], $saveKey, $filePath);

    //         // 拼接文件的访问 URL

    //     } catch (OssException $e) {
    //         // 上传失败，返回错误信息
    //         return handleResult(0, '上传失败: ' . $e->getMessage());
    //     }

    //     $url = '/' . $saveKey;
    //     $fullUrl = $config['domain'] . $url;
    //     // throw new MuloException('dev', 0, [
    //     //     'url' => $url,
    //     //     'full_url' => $fullUrl,
    //     //     'ret' => $ret,
    //     // ]);

    //     return handleResult(200, "上传成功", [
    //         'url'=>$url,
    //         'fullUrl'=>$fullUrl,
    //         'ret' => $ret,
    //     ]);
    // }


}
