<?php

namespace mulo\api\library\storage;

use mulo\api\library\storage\common\File;
use mulo\exception\MuloException;
use Overtrue\EasySms\Contracts\GatewayInterface;

/**
 * 交易逻辑模型-支付订单
 * 
 * @example 创建支付订单
 * @example 支付成功处理
 * 
 */
class Storage
{

    public $gateway = '';
    public $config = null;

    /**
     * 构建链式方法
     */
    static function src($gateway, $config)
    {
        $obj = new self();

        $obj->gateway = $gateway;
        $obj->config = $config;

        return $obj;
    }

    /**
     * 切换存储
     * 
     */
    function setStorage() {}



    /**
     * 上传
     * 
     */
    function upload($file)
    {
        $fileObj = new File($file);
        $gatewayInstance = $this->getGatewayIns();
        $res = $gatewayInstance->upload($fileObj);

        return $res;
    }

    /**
     * 获取上传选项
     * 
     * 
     */
    function getUploadOption()
    {
        $gatewayInstance = $this->getGatewayIns();
        $res = $gatewayInstance->getUploadOption();
        return $res;
    }

    function getGatewayIns() {
        $className = $this->formatGatewayClassName($this->gateway);
        $gatewayInstance = $this->makeGateway($className, $this->config);
        return $gatewayInstance;
    }

    /**
     * Make gateway instance.
     *
     * @param string $gateway
     * @param array  $config
     *
     * @return GatewayInterface
     *
     * @throws \Exception
     */
    protected function makeGateway($gateway, $config)
    {
        if (!\class_exists($gateway)) {
            throw new \Exception(\sprintf('Class "%s" 是一个无效的 storage gateway.', $gateway));
        }

        return new $gateway($config);
    }

    /**
     * Format gateway name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function formatGatewayClassName($name)
    {
        if (\class_exists($name)) {
            return $name;
        }

        $name = \ucfirst(\str_replace(['-', '_', ''], '', $name));

        return __NAMESPACE__ . "\\gateways\\{$name}Gateway";
    }
}
