<?php

declare(strict_types=1);

namespace mulo\traits;

use mulo\exception\MuloException;

/**
 * 统一 md5 方式可解密签名校验，客服使用
 */
trait UnifiedToken
{

    protected $expired = 86400000;

    /**
     * 获取加密 token
     * 
     * @param string $content 要加密的数据
     */
    public function getUnifiedToken($content)
    {
        $custom_sign = mulo_config('basic.site.sign') ?: 'mulo';
        return base64_encode(md5(md5($content) . $custom_sign) . '.' . $content . '.' . time());
    }


    /**
     * 获取被加密数据
     */
    public function getUnifiedContent($token)
    {
        $custom_sign = mulo_config('basic.site.sign') ?: 'mulo';

        $token_str = base64_decode($token);
        $tokenArr = explode('.', $token_str);

        $sign = $tokenArr[0] ?? '';
        $content = $tokenArr[1] ?? 0;
        $time = $tokenArr[2] ?? 0;
        $time = intval($time);

        if ($content && $sign) {
            if (md5(md5($content) . $custom_sign) == $sign && ($time + $this->expired) > time()) {
                return $content;
            }

            return false;
        }

        return false;
    }
}
