<?php

declare(strict_types=1);

namespace mulo\library\sms\message;

use Overtrue\EasySms\Contracts\GatewayInterface;
use mulo\exception\MuloException;
use Overtrue\EasySms\Message as EasySmsMessage;
use app\admin\model\Sms;

/**
 * 短信消息基类，继承与 easywechat 的 messageInterface 接口
 */
class Message extends EasySmsMessage
{
    /**
     * 发送类型
     */
    protected $event;

    /**
     * 手机号
     */
    protected $mobile;


    protected $defaultContent = null;


    /**
     * 初始化
     *
     * @param string $mobile
     * @param string $event
     * @return $this
     */
    public function init($mobile, $event)
    {
        // 触发事件
        $this->event = $event;

        $this->mobile = $mobile;

        return $this;
    }


    /**
     * 获取发送内容
     *
     * @param GatewayInterface $gateway
     * @return string
     */
    public function getContent(GatewayInterface $gateway = null)
    {
        $content = is_callable($this->content) ? call_user_func($this->content, $gateway) : $this->content;
        if (!$content) {
            $gatewayConfig = $this->getGatewayConfig($gateway->getName());
            $templates = array_column($gatewayConfig['template'], null, 'event');
            // [                    // templates 格式
            //     'mobilelogin' => [
            //         'event' => 'mobilelogin',
            //         'value' => 'SMS135465sd'
            //     ]
            // ]
            $content = isset($templates[$this->event]) && $templates[$this->event] ? ($templates[$this->event]['content'] ?? null) : null;      // 因为目前后台设置，这里是 null
            
            $data = $this->getData($gateway);

            if (!$content && $this->defaultContent) {
                $content = $this->defaultContent;
                // 尝试使用默认 content
                foreach ($data as $key => $value) {         // 这里有问题，这里的 data 和 defaultContent 对不上
                    $content = str_replace('{' . $key . '}', (string)$value, $content);
                }
            }

            // 部分发送渠道 content 上追加 短信签名
            if ($content && in_array($gateway->getName(), ['smsbao'])) {
                if ('【' != mb_substr($content, 0, 1) && !empty($gatewayConfig['sign_name'])) {
                    $content = '【' . $gatewayConfig['sign_name'] . '】' . $content;
                }
            }
        }

        return $content;
    }


    /**
     * 获取模板
     *
     * @param GatewayInterface $gateway
     * @return string
     */
    public function getTemplate(GatewayInterface $gateway = null)
    {
        $template = is_callable($this->template) ? call_user_func($this->template, $gateway) : $this->template;
        if (!$template) {
            $gatewayConfig = $this->getGatewayConfig($gateway->getName());
            $templates = array_column($gatewayConfig['template'], null, 'event');
            $template = isset($templates[$this->event]) && $templates[$this->event] ? ($templates[$this->event]['value'] ?? null) : null;
        }

        return $template;
    }



    /**
     * 获取发送 gateway 的配置
     *
     * @param string $gateway_name
     * @return void
     */
    public function getGatewayConfig($gateway_name = '')
    {
        if (config('?easysms.gateways.' . $gateway_name)) {
            return config('easysms.gateways.' . $gateway_name);
        }

        return [];
    }


    public function setDefaultContent($content)
    {
        $this->defaultContent = $content;
        return $this;
    }
}
