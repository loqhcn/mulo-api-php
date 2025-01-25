<?php

namespace mulo\api\logic\trade;


use mulo\api\traits\DefineHook;
use mulo\exception\MuloException;
use think\facade\Log;
use Yansongda\Pay\Pay;


/**
 * 导出用于支付的相关参数
 * 
 * @todo 对接支付平台
 *
 * @link 文档 https://pay.yansongda.cn/
 * 
 */
class PayService
{

    use DefineHook;

    /** @var array 付款订单 */
    public $order = null;

    /** @var string 支付方式 */
    public $payType = null;

    /** @var string 支付场景(h5,app,miniapp:小程序) */
    public $scene = null;

    /** @var array 支付配置 */
    public $config = [
        'alipay' => [
            'default' => [
                // 必填-支付宝分配的 app_id
                'app_id' => '2016082000295641',
                // 必填-应用私钥 字符串或路径
                'app_secret_cert' => '89iZ2iC16H6/6a3YcP+hDZUjiNGQx9cuwi9eJyykvcwhD...',
                // 必填-应用公钥证书 路径
                'app_public_cert_path' => '/Users/yansongda/pay/cert/appCertPublicKey_2016082000295641.crt',
                // 必填-支付宝公钥证书 路径
                'alipay_public_cert_path' => '/Users/yansongda/pay/cert/alipayCertPublicKey_RSA2.crt',
                // 必填-支付宝根证书 路径
                'alipay_root_cert_path' => '/Users/yansongda/pay/cert/alipayRootCert.crt',
                'return_url' => 'https://yansongda.cn/alipay/return',
                'notify_url' => 'https://yansongda.cn/alipay/notify',
                // 选填-第三方应用授权token
                'app_auth_token' => '',
                // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                'service_provider_id' => '',
                // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                'mode' => Pay::MODE_NORMAL,
            ],
        ],
        // 'logger' => [ // optional
        //     'enable' => false,
        //     'file' => './logs/alipay.log',
        //     'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
        //     'type' => 'single', // optional, 可选 daily.
        //     'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        // ],
        // 'http' => [ // optional
        //     'timeout' => 5.0,
        //     'connect_timeout' => 5.0,
        //     // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
        // ],
    ];

    function __construct() {}

    /**
     * 构建链式方法
     * @param array $row 付款订单
     * 
     */
    static function src($row = null)
    {
        $obj = new self();
        $obj->setOrder($row);
        return $obj;
    }

    /**
     * 设置付款订单
     * @param array $order 付款订单model-row
     * 
     * 
     */
    function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * 设置配置
     * @param array $config 配置(参考yansongda/pay)
     * @link https://pay.yansongda.cn/
     * 
     */
    function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }


    /**
     * 设置支付方式
     * @param string $payType 支付方式(alipay,weixin,douyin)
     * 
     */
    function setPayType($payType)
    {
        $this->payType = $payType;
        return $this;
    }

    /**
     * 设置应用场景
     * @param string $scene 应用场景
     * - h5
     * - app
     * - miniapp(小程序)
     * 
     */
    function setScene($scene)
    {
        $this->scene = $scene;
        return $this;
    }


    /**
     * 输出支付参数
     * 
     */
    function dest()
    {
        $order = $this->order;
        $title = $order['title'] ?: '开通服务';

        Pay::config($this->config);

        $result = Pay::alipay()->h5([
            'out_trade_no' => $order['order_sn'],
            'total_amount' => $order['money'], //$order['money']
            'subject' => $title,
        ]);

        $result = (string)$result->getBody();

        return handleResult(200, "success", [
            'result' => $result
        ]);
    }

    function notify()
    {
        Pay::config($this->config);

        $params = input('post.');




        try {
            $data = Pay::alipay()->callback($params); // 是的，验签就这么简单！
            Log::write("验签成功", 'pay');
            // 请自行对 trade_status 进行判断及其它逻辑进行判断，在支付宝的业务通知中，只有交易通知状态为 TRADE_SUCCESS 或 TRADE_FINISHED 时，支付宝才会认定为买家付款成功。
            // 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号；
            // 2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）；
            // 3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）；
            // 4、验证app_id是否为该商户本身。
            // 5、其它业务逻辑情况
        } catch (\Throwable $e) {

            Log::write("验签失败" . $e->getMessage(), 'pay');
            return handleResult(0, '', [
                'result' => 'fail',
            ]);
        }

        // 支付宝
        $order_sn = $params['out_trade_no'];

        // 返回给对应平台的结果
        return handleResult(200, '', [
            'order_sn' => $order_sn,
            'data' => $data,
            'result' => 'success',
        ]);
    }
}
