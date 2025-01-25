<?php

namespace mulo\api\library\storage\gateways;

use mulo\api\library\storage\common\File;
use mulo\api\library\storage\common\Gateway;
use mulo\exception\MuloException;

use OSS\OssClient;
use OSS\Core\OssException;


/**
 * unicloud扩展存储
 * 
 * @todo 扩展存储是uniapp与七牛云合作的存储服务, cdn费用较低
 * @see https://doc.dcloud.net.cn/uniCloud/ext-storage/dev.html
 * 
 */
class UniextGateway extends Gateway
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

    /**
     * 
     * @param string $fileExt 文件扩展名(后端上传时设置)
     * 
     */
    function getUploadOption($fileExt = '')
    {
        $config = $this->config->getConfig();

        # saveKey
        $replace = [
            'dirs' => date('Ymd'),
            'etag' => $this->roundFileName(),
            // 'ext' => "." . $file->getFileExt(),
        ];
        $replace['ext'] = $fileExt ? ("." . $fileExt) : '';
        $saveKey  = $this->replacePlaceholders($this->saveKey, $replace);

        # request
        $http = \mulo\library\Http::src()
            ->setBeforeResponse(function ($res) {
                return $res['data'];
            });
        $res = $http->get($config['api'], [
            'domain' => $config['domain'],
            'cloudPath' => $saveKey,
        ], [
            'verify' => false, //不校验本地ssl证书
        ]);

        $uploadFileOptions = $res['uploadFileOptions'];
        // 设置上传策略, 通过saveKey生成文件路径

        return handleResult(200, "获取成功", [
            'optionType' => 'option',
            'option' => [
                'url' => $uploadFileOptions['url'],         //链接
                'name' => $uploadFileOptions['name'],       //文件在哪个字段
                'saveKey' => $saveKey,                      //保存文件的路径规则
                'data' => $uploadFileOptions['formData'],   //post请求数据
                'domain' => $config['domain'],              //图片访问域名
            ]
        ]);
    }
}
