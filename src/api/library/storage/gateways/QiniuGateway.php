<?php

namespace mulo\api\library\storage\gateways;

use CURLFile;
use mulo\api\library\storage\common\File;
use mulo\api\library\storage\common\Gateway;
use mulo\exception\MuloException;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

/**
 * 七牛云上传
 * 
 */
class QiniuGateway extends Gateway
{
    // 上传地址
    public $url = "https://upload.qiniup.com";


    // 生成上传 token，有效期为 2 小时（7200 秒）
    public $expires = 7200;


    /**
     * 上传文件
     * @todo 使用前端上传的方式推送
     * 
     */
    function upload(File $file)
    {
        $flexExt =  $file->getFileExt();

        # 加载上传信息
        $res = $this->getUploadOption($flexExt);
        if ($res['code'] != 200) {
            return $res;
        }

        // 七牛云上传已通过getUploadOption内的policy设置了上传规则,saveKey无需处理
        $saveKey = $option['saveKey'] ?? '';

        $option = $res['data']['option'];

        $http = \mulo\library\Http::src()
            ->setBeforeResponse(function ($res) {
                return $res['data'];
            });

        $filePath = $file->getPath();

        # 添加文件
        $data = $option['data'] ?: [];
        $cfile = new CURLFile($filePath, mime_content_type($filePath), $file->getName());
        $data[$option['name']] = $cfile;

        $data = $this->parseUploadData($data, $file);


        // throw new MuloException('dev', 0, [
        //     'url' => $option['url'],
        //     'data'=>$data,
        // ]);


        # 触发请求
        $ret = $http->post($option['url'], $data, [
            'verify' => false, //不校验ssl证书
            'headers' => [
                'Content-Type' => 'multipart/form-data'
            ]
        ]);

        throw new MuloException('dev', 0, [
            '$ret' => $ret,
        ]);

        $url = '/' . $ret['key'];
        $fullUrl = $option['domain'] . $url;

        return handleResult(200, "上传", [
            'url' => $url,
            'fullUrl' => $fullUrl,
            'ret' => $ret,
        ]);
    }

    // /**
    //  * 上传文件
    //  * 
    //  */
    // function upload(File $file)
    // {

    //     # 加载配置与设置保存信息

    //     $config = $this->config->config;
    //     $auth = new Auth($config['accessKey'], $config['secretKey']);

    //     $saveKey  = $file->replacePlaceholders($file->saveKey, [
    //         'dirs' => date('Ymd'),
    //         'ext' => "." . $file->getFileExt(),
    //     ]);
    //     // 设置上传策略, 通过saveKey生成文件路径
    //     $policy = [
    //         'saveKey' => $saveKey,
    //     ];
    //     $token = $auth->uploadToken($config['bucket'], null, $this->expires, $policy);

    //     # 上传到七牛

    //     $uploadMgr = new UploadManager();
    //     $filePath = $file->getPath();
    //     // throw new MuloException('dev', 0, [
    //     //     '$saveKey' => $saveKey
    //     // ]);
    //     list($ret, $err) = $uploadMgr->putFile($token, null, $filePath);

    //     // throw new MuloException('dev', 0, [
    //     //     '$token' => $token,
    //     //     '$ret' => $ret
    //     // ]);

    //     $url = '/' . $ret['key'];
    //     $fullUrl = $config['domain'] . $url;

    //     return handleResult(200, "上传", [
    //         'url' => $url,
    //         'fullUrl' => $fullUrl,
    //         'ret' => $ret,
    //     ]);
    // }


    /**
     * 
     * @param string $fileExt 文件扩展名(后端上传时设置)
     * 
     */
    function getUploadOption($fileExt = '')
    {
        $config = $this->config->getConfig();
        $auth = new Auth($config['accessKey'], $config['secretKey']);


        $replace = [
            'dirs' => date('Ymd'),
            // 'ext' => "." . $file->getFileExt(),
        ];
        if ($fileExt) {
            $replace['ext'] = "." . $fileExt;
        }

        $saveKey  = $this->replacePlaceholders($this->saveKey, $replace);

        // 设置上传策略, 通过saveKey生成文件路径
        $policy = [
            'saveKey' => $saveKey,
        ];
        $token = $auth->uploadToken($config['bucket'], null, $this->expires, $policy);
        $data = [
            'token' => $token,
        ];

        return handleResult(200, "获取成功", [
            'optionType' => 'option',
            'option' => [
                'url' => $this->url,        //链接
                'name' => $this->name,      //文件在哪个字段
                'saveKey' => $saveKey,      //保存文件的路径规则
                'data' => $data,            //post请求数据
                'domain' => $config['domain'],
            ]
        ]);
    }
}
