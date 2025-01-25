<?php

namespace mulo\api\logic;

use LogicException;
use mulo\api\library\ModelDb;
use mulo\api\logic\trade\PayService;
use mulo\api\logic\trade\TradePayOrder;
use mulo\api\logic\user\UserWallet;
use mulo\exception\MuloException;

use mulo\api\traits\DefineHook;
use PDO;

/**
 * 交易逻辑模型
 * 
 */
class TradeModelApi
{

    use DefineHook;

    static $noNeedLoginApi = ['payUrl', 'notify'];
    public $authInfo = [];
    public $modelData = [];
    public $tool;

    /** @var array 应用信息 */
    public $app = null;


    public $models = [
        'pay_order' => 'ysx_pay_order',
        'user_wallet_log' => 'ysx_user_wallet_log',
    ];


    function __construct() {}

    static function src()
    {
        return new self();
    }

    /**
     * 设置应用信息
     * 
     */
    function setApp($app)
    {
        $this->app = $app;
        return $this;
    }

    function setAuthInfo($authInfo)
    {
        $this->authInfo = $authInfo;
        return $this;
    }



    /**
     * 处理接口
     * 
     * @api detail 付款订单详情(也可以使用DataModelApi的row)
     * @api pay    支付
     * @api create 创建
     * 
     */
    function handle($api = 'detail')
    {
        $data = [
            'api' => $api,
        ];

        # TODO 创建支付
        if ($api == 'create') {
            // 对一条数据进行支付
            $id = input('id');
            $ext = input('ext/a', []);
            $modelName = input('model');
            if (!$id) {
                throw new MuloException("api 输入id");
            }
            if (!$modelName) {
                throw new MuloException("api 输入model");
            }

            $row = ModelDb::model($modelName)->where([
                'id' => $id
            ])->find();
            if (!$row) {
                throw new MuloException("数据不存在", 0, [
                    'id' => $id,
                    'model' => $modelName
                ]);
            }
            if (!isset($row['money'])) {
                throw new MuloException("未设置价格无法下单", 0, [
                    'id' => $id,
                    'model' => $modelName,
                    'todo' => '请创建该模型的`money`字段'
                ]);
            }


            // 创建支付订单
            $payOrderTool = TradePayOrder::src();
            $createPayRet = $payOrderTool
                ->setHooks($this->hooks)
                ->setPayRow($modelName, $row)
                ->setUserId($this->authInfo['userId'])
                ->setPrice($row['money'])
                ->setExt($ext)
                ->dest();



            $data['row'] = $createPayRet['row'];
            $data['pay_order_id'] = $createPayRet['pay_order_id'];
        }

        # TODO 加载订单详情
        if ($api == 'detail') {
        }

        # TODO 立即支付
        if ($api == 'pay') {
            $todo = 'undo'; //定义执行操作

            $payOrderId = input('id', 0);
            $payType = input('pay_type', '');
            # 输入参数
            if (!$payOrderId) {
                throw new MuloException("api参数无订单号");
            }
            if (!$payType) {
                throw new MuloException("api参数无付款类型");
            }

            # 读取订单
            $payOrderModelData = modelTool()->getModel($this->models['pay_order']);
            $row = ModelDb::model($payOrderModelData)->where([
                'user_id' => $this->authInfo['userId'],
                'id' => $payOrderId
            ])->find();
            if (!$row) {
                throw new MuloException("未找到付款订单", 0, [
                    'id' => $payOrderId
                ]);
            }

            if ($row['status'] != TradePayOrder::STATUS_CREATE) {
                if ($row['status'] == TradePayOrder::STATUS_PAYED) {
                    throw new MuloException("该订单已付款", 0, [
                        'status' => $row['status']
                    ]);
                }

                throw new MuloException("当前状态不可支付", 0, [
                    'status' => $row['status']
                ]);
            }

            $isPayed = false;
            // TODO -- 自定义付款处理
            $ret = $this->handleHook('trade.handle.pay.handle', [
                // 是否已处理
                'isPayed' => false,
                // 付款数据
                'row' => $row,
                // 付款类型
                'payType' => $payType,
                // 付款后操作
                'todo' => $todo,
            ]);

            $row = $ret['row'];
            $isPayed = $ret['isPayed'];
            $todo = $ret['todo'];


            if (!$ret['isPayed']) {
                # TODO -- 余额付款
                if ($payType == 'money') {

                    // 付款
                    $userWalletRet = UserWallet::src($this->authInfo['userId'])
                        ->setTitle('余额付款')
                        ->change('money', 0 - $row['money'], UserWallet::TYPE_MONEY_PAY);
                    if ($userWalletRet['code'] != 200) {
                        throw new MuloException($userWalletRet['msg'], $userWalletRet['code'], $userWalletRet['data']);
                    }
                    // 支付成功
                    $isPayed = true;
                    $todo = 'success';
                }
                # TODO -- 积分付款
                else if ($payType == 'score') {
                    // 付款
                    $userWalletRet = UserWallet::src($this->authInfo['userId'])
                        ->setTitle('积分付款')
                        ->change('score', 0 - $row['money'], UserWallet::TYPE_MONEY_PAY);
                    if ($userWalletRet['code'] != 200) {
                        throw new MuloException($userWalletRet['msg'], $userWalletRet['code'], $userWalletRet['data']);
                    }

                    $isPayed = true;
                    $todo = 'success';
                }
                # TODO -- 支付宝付款
                else if ($payType == 'alipay') {

                    // $config = $this->handleHook('trade.handle.pay.alipay.config', [], 'param');
                    // $scene = input('scene', 'h5');
                    // $options = PayService::src($row)
                    //     ->setConfig($config)
                    //     ->setPayType($payType)
                    //     ->setScene($scene)
                    //     ->dest();

                    // \GuzzleHttp\Psr7\Response::class

                    // throw new MuloException("dev", 0, [
                    //     'options' => $options
                    // ]);

                    // 调用hook处理
                    $todo = 'url';
                    $data['tip'] = "跳转链接支付";
                    $data['payData'] = request()->domain()
                        . $this->app['trade']['route']
                        . "?api=payUrl&pay_type={$payType}&order_sn={$row['order_sn']}";
                } else {
                    throw new MuloException("暂不支持的支付方式:{$payType}", 0, [
                        'pay_type' => $payType
                    ]);
                }
            }

            if ($isPayed) {
                TradePayOrder::src()->setHooks($this->hooks)->onPaySuccess($row['id'], $payType);
            }

            $data['todo'] = $todo;

            $data = $this->handleHook('trade.handle.pay.end', $data);
        }

        # TODO 跳转链接付款
        if ($api == 'payUrl') {

            $orderSn = input('order_sn', 0);
            $payType = input('pay_type', '');
            # 输入参数
            if (!$orderSn) {
                throw new MuloException("api参数无订单号");
            }
            if (!$payType) {
                throw new MuloException("api参数无付款类型");
            }

            # 读取订单
            $payOrderModelData = modelTool()->getModel($this->models['pay_order']);
            $row = ModelDb::model($payOrderModelData)->where([
                // 'user_id' => $this->authInfo['userId'],
                'order_sn' => $orderSn
            ])->find();
            if (!$row) {
                throw new MuloException("未找到付款订单", 0, [
                    'order_sn' => $orderSn
                ]);
            }

            if ($row['status'] != TradePayOrder::STATUS_CREATE) {
                if ($row['status'] == TradePayOrder::STATUS_PAYED) {
                    throw new MuloException("该订单已付款", 0, [
                        'status' => $row['status']
                    ]);
                }

                throw new MuloException("当前状态不可支付", 0, [
                    'status' => $row['status']
                ]);
            }

            $config = $this->handleHook('trade.handle.pay.alipay.config', [], 'param');
            $scene = input('scene', 'h5');
            $ret = PayService::src($row)
                ->setConfig($config)
                ->setPayType($payType)
                ->setScene($scene)
                ->dest();
            if ($ret['code'] != 200) {
                // 错误页面

            }

            return response($ret['data']['result']);
        }


        # TODO 付款成功通知
        if ($api == 'notify') {
            $payType =  input('param.pay_type', input('pay_type', 'alipay'));
            $config = $this->handleHook('trade.handle.pay.alipay.config', [], 'param');
            $scene = input('scene', 'h5');
            $ret = PayService::src()
                ->setConfig($config)
                ->setPayType($payType)
                ->notify();

            if ($ret['code'] == 200) {
                // 支付成功
                $payOrderModelData = modelTool()->getModel($this->models['pay_order']);
                $row = ModelDb::model($payOrderModelData)->where([
                    'order_sn' => $ret['data']['order_sn']
                ])->find();

                try {
                    if (!$row) {
                        throw new LogicException('未找到订单', 0, [
                            'order_sn' => $ret['data']['order_sn']
                        ]);
                    }

                    // 支付成功
                    TradePayOrder::src()->setHooks($this->hooks)->onPaySuccess($row['id'], 'alipay');
                } catch (\Throwable $th) {
                    //throw $th;
                    $msg = $th->getMessage();
                    Log::write("
                        支付结果无法处理:
                        $msg
                    ", 'logic_error');
                }
            }

            return response($ret['data']['result']);
        }


        // $data = $this->handleHook('trade.handle.end', $data);

        return result(200, $data);
    }
}
