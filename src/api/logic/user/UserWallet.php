<?php

namespace mulo\api\logic\user;

use mulo\api\library\ModelDb;
use mulo\api\library\ModelTool;


/**
 * 用户资金账户处理
 * 
 * @example 创建支付订单
 * @example 支付成功处理
 * 
 */
class UserWallet
{

    /** 余额付款 */
    const TYPE_MONEY_PAY = 'money_pay';
    /** 积分付款 */
    const TYPE_SCORE_PAY = 'score_pay';
    /** 积分购买 */
    const TYPE_SCORE_RECHARGE = 'score_recharge';



    public $models = [
        'user' => 'ysx_user',
        'wallet_log' => 'ysx_user_wallet_log',
    ];

    /** 用户(id|array) */
    public $user = null;
    /** 变动明细标题 */
    public $title = '';
    public $itemId = '';
    public $itemType = '';
    public $ext = [];


    function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * 创建链式方法
     * @param array|int $user 用户|用户ID
     * 
     */
    static function src($user)
    {
        return new self($user);
    }

    function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }

    function setItemId($itemId, $type = '')
    {
        $this->itemId = $itemId;
        $this->itemType = $type;
        return $this;
    }


    function setExt(array $ext)
    {
        $this->ext = $ext;
        return $this;
    }

    /**
     * 更新余额
     * 
     * @param string    $field      资金类型(money,score...)
     * @param float     $money      变动金额
     * @param string    $type       变动类型
     * 
     */
    function change($field, $money, $type = '')
    {
        $money_text = $money;
        $money = floatval($money);

        $userId = is_array($this->user) ? $this->user['id'] : $this->user;

        // 变动前后
        $before = null;
        $after = null;

        $userModelData = modelTool()->getModel($this->models['user']);
        $user = ModelDb::model($userModelData)->find($userId);
        if (!$user) {
            return handleResult(771, "未找到用户", [
                'userId' => $userId,
                'field' => $field,
                'money' => $money
            ]);
        }

        $before = $user[$field] ?? 0;

        # 验证余额
        if ($money < 0) {
            if ($user[$field] < abs($money)) {
                return handleResult(781, "余额不足", [
                    'field' => $field,
                    'money' => $money
                ]);
            }
        }
        $after = bcadd($before, $money, 2);

        if ($money != 0) {
            # 变动金额
            ModelDb::model($userModelData)->where([
                'id' => $userId
            ])->update([
                $field => $after
            ]);

            # 保存日志
            $logModelData = modelTool()->getModel($this->models['wallet_log']);
            $logId = ModelDb::model($logModelData)->save([
                'title' => $this->title,
                'user_id' => $userId,
                'type' => $type,
                'money_type' => $field,
                'money' => $money,
                'money_before' => $before,
                'money_after' => $after,
                'item_id' => $this->itemId,
                'ext' => empty($this->ext) ? null : (json_encode($this->ext, JSON_UNESCAPED_UNICODE))
            ]);
        }

        return handleResult(200, '成功', [
            'money' => $money,
            'before' => $before,
            'after' => $after,
            'logId' => $logId,
        ]);
    }
}
