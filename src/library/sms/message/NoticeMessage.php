<?php

declare(strict_types=1);

namespace mulo\library\sms\message;

use Overtrue\EasySms\Contracts\GatewayInterface;
use mulo\exception\MuloException;
use app\admin\model\Sms;

/**
 * 短信通知消息类
 */
class NoticeMessage extends Message
{
    protected $params = [];

    /**
     * 获取数据，包含验证码
     *
     * @param GatewayInterface $gateway
     * @return void
     */
    public function getData(GatewayInterface $gateway = null)
    {
        $data = is_callable($this->data) ? call_user_func($this->data, $gateway) : $this->data;
        if (!$data) {
            $gatewayConfig = $this->getGatewayConfig($gateway->getName());
            $data = [
                'sign_name' => $gatewayConfig['sign_name'] ?? '',
            ];

            $data = array_merge($data, $this->params['data']);
        }

        return $data;
    }


    /**
     * 获取模板
     *
     * @param GatewayInterface $gateway
     * @return string
     */
    public function getTemplate(GatewayInterface $gateway = null)
    {
        return $this->params['template'];
    }


    public function setParams($params)
    {
        $this->params = $params;

        if (isset($params['default_content']) && !empty($params['default_content'])) {
            $this->setDefaultContent($params['default_content']);
        }

        return $this;
    }

}
