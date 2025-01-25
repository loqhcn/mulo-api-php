<?php

namespace mulo\api\logic\trade;

use mulo\api\library\ModelDb;
use mulo\api\library\ModelTool;

use mulo\api\traits\DefineHook;
use mulo\exception\MuloException;

/**
 * 交易逻辑模型-支付订单
 * 
 * @example 创建支付订单
 * @example 支付成功处理
 * 
 */
class TradePayOrder
{

    use DefineHook;


    # TODO hook 钩子类型定义

    /**支付订单-创建后 */
    const HOOK_PAY_CREATE = 'pay_create';
    /**支付订单-成功付款后 */
    const HOOK_PAY_PAYED = 'pay_payed';
    /**支付订单-关闭 */
    const HOOK_PAY_CLOSE = 'pay_close';

    # TODO status 状态定义

    /**待付款(刚创建) */
    const STATUS_CREATE = 0;
    const STATUS_PAYED = 200;

    public $modelData = [];

    /** @var ModelTool 模型处理工具 */
    public $tool;
    public $row = null;

    # 付款信息
    /** @var string 付款模型名  */
    public $payRowModelName = '';
    /** @var array 对应模型的数据  */
    public $payRow = null;
    /**  付款人  */
    public $payUserId = null;
    /**  付款价格  */
    public $payPrice = 0;
    /**  扩展  */
    public $payExt = null;
    /**  标题  */
    public $title = "";


    public $models = [
        'pay_order' => 'ysx_pay_order',
    ];


    function __construct()
    {
        $this->tool = modelTool();
    }

    static function src()
    {
        return new self();
    }



    /**
     * 设置对应
     * 
     * @param array $targetModelData 对应模型类型
     * @param array $row             对应模型类型的数据
     * 
     */
    function setRow()
    {
        return $this;
    }

    function getOrderBySn($orderSn)
    {
        $payOrderModelData = $this->tool->getModel($this->models['pay_order']);
        $payOrder = ModelDb::model($payOrderModelData)
            ->where([
                'order_sn' => $orderSn
            ])
            ->find();
        return $payOrder;
    }

    /**
     * 设置支付成功
     * @param int $id 付款订单ID
     * @param string $payType 付款方式
     * 
     * 
     */
    function onPaySuccess($id,string $payType='money')
    {

        $payOrderModelData = $this->tool->getModel($this->models['pay_order']);

        $payOrder = ModelDb::model($payOrderModelData)->find($id);
        if (!$payOrder) {
            return handleResult(0, "未找到该支付订单" . $id);
        }

        // $childModelData = $this->tool->getModel();
        $row = ModelDb::model($payOrder['type'])->find($payOrder['data_id']);

        # 更新状态
        ModelDb::model($payOrderModelData)->where([
            'id' => $id
        ])->update([
            'pay_type'=>$payType,
            'status' => static::STATUS_PAYED
        ]);

        $payOrder = ModelDb::model($payOrderModelData)->where([
            'id' => $id
        ])->find();

        # 付款后处理
        $this->handleHook('trade.handle.pay.success', [
            'payOrder' => $payOrder,
            'row' => $row
        ], null);

        return handleResult(200, "处理成功", [
            'payOrder' => $payOrder,
            'row' => $row
        ]);
    }


    /**
     * 设置进行付款数据
     * 
     */
    function setPayRow($modelName, $row)
    {

        $this->payRowModelName = $modelName;
        $this->payRow = $row;

        return $this;
    }


    function setUserId($userId)
    {
        $this->payUserId = $userId;
        return $this;
    }

    function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置付款价格
     * 
     */
    function setPrice($price)
    {
        $this->payPrice = $price;
        return $this;
    }

    /**
     * 设置付款价格
     * 
     */
    function setExt(array $ext)
    {
        $this->payExt = $ext;
        return $this;
    }


    /**
     * 输出支付
     * 
     */
    function dest()
    {


        # 创建付款订单信息
        $userId = $this->payUserId ?  $this->payUserId : ($this->payRow['user_id'] ?? 0);
        $orderSn = $this->createOrderSn();
        $payOrderData = [
            'title' => $this->title,
            'user_id' => $userId,
            'order_sn' => $orderSn,
            'type' => $this->payRowModelName,
            'data_id' => $this->payRow['id'],
            'status' => static::STATUS_CREATE,
            'ext' => $this->payExt ? json_encode($this->payExt, JSON_UNESCAPED_UNICODE) : null,
            'money' =>  $this->payPrice,
        ];

        $ret = $this->handleHook('trade.handle.create.dest.before_save', [
            'payOrderData' => $payOrderData,
            'payRow' => $this->payRow,
            'model'=>$this->payRowModelName,
        ]);
        $payOrderData = $ret['payOrderData'];

        // 验证支付


        # 保存付款订单
        $payOrderModelData = $this->tool->getModel($this->models['pay_order']);
        $payOrderId = ModelDb::model($payOrderModelData)->add($payOrderData);
        $row = ModelDb::model($payOrderModelData)->find($payOrderId);

        return [
            'pay_order_id' => $payOrderId,
            'row' => $row,
        ];
    }

    function createOrderSn()
    {
        return 'HOME' . $this->payRow['id'] . date('mdHi') . rand(1000, 9999);
    }
}
