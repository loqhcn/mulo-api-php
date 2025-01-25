<?php

namespace mulo\api\logic\activity;

use mulo\api\library\ModelDb;
use mulo\api\library\ModelTool;
use mulo\exception\MuloException;

/**
 * 抽奖类
 * 
 * @example 抽取列表中的一个
 * 
 * 
 */
class DrawOneActivity
{


    public $list = [];

    function __construct() {}

    /**
     * 
     * @return self
     */
    static function src(array $list = [])
    {
        $obj =  new self();
        if (!empty($list)) {
            $obj->setList($list);
        }
        return $obj;
    }

    function setList($list)
    {
        $this->list = $list;
    }

    /**
     * 通过次数控制
     * @todo 通过radio字段设置出现频率
     * 
     * @return array|null
     */
    function drawByTimes($times)
    {

        // 排序, 频率低的在前面
        usort($this->list, function ($a, $b) {
            return $b['ratio'] <=> $a['ratio'];
        });

        foreach ($this->list as $key => $li) {
            if ($times > 0 &&  $times % $li['ratio'] == 0) {
                return $li;
            }

            // 基础概率
            if ($li['ratio'] == 1) {
                return $li;
            }
        }

        return null;
    }

    /**
     * 输出支付
     * 
     */
    function dest()
    {
        $awardIndex = array_rand($this->list);
        return $this->list[$awardIndex];
    }
}
