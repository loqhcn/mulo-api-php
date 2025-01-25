<?php

declare(strict_types=1);

namespace mulo\auth\lib;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\ConstraintViolationException;


use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Validation\Constraint\RelatedTo;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use think\facade\Env;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Validation\Constraint\HasClaimWithValue;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;

class Jwt
{

    

    /**
     * 生成token
     * 
     * @param string $tokenString 令牌
     * 
     * @return array [token:令牌,endtime:过期时间]
     * - token:string 令牌
     * - scene:string 令牌使用场景
     * - endtime:int 过期时间
     */
    static function getToken($uid, $data = [], $scene = 'user')
    {
        
        $config = Configuration::forSymmetricSigner(new Sha256(), Key\InMemory::plainText($key));

        $now = new \DateTimeImmutable();
        $endTime = $now->modify('+10080 minute'); //7天
        $identifiedBy = $uid . time();

        // var_dump($endTime->getTimestamp());exit;
        $token = $config->builder()
            ->issuedBy('mulo') // 设置发行人 (iss claim)
            ->permittedFor('mulo-model') // 设置接收人 (aud claim)
            ->identifiedBy($identifiedBy, true) // 设置 JWT ID (jti claim)  用于token的唯一标记
            ->relatedTo('mulo')  // 配置令牌的主题（子声明）
            ->issuedAt($now) // 设置签发时间 (iat claim)
            ->canOnlyBeUsedAfter($now->modify('+0 minute')) // 设置生效时间 (nbf claim)
            ->expiresAt($endTime) // 设置过期时间 (exp claim)
            ->withClaim('uid', $uid) // 自定义的 UID claim
            ->withClaim('scene', $scene) // 自定义的 UID的场景
            ->withClaim('data', json_encode($data, JSON_UNESCAPED_UNICODE)) // 自定义的 UID claim
            ->getToken($config->signer(), $config->signingKey());

        return [
            'token' => $token->toString(),
            'scene' => $scene,
            'endtime' => $endTime->getTimestamp(),
        ];
    }

    /**
     * 验证token
     * @param string $tokenString 令牌
     * 
     * @link 文档 https://lcobucci-jwt.readthedocs.io/en/latest/validating-tokens/
     * 
     */
    static function verifyToken($tokenString, $scene = 'user')
    {
        $key = Env::get('JWT.KEY', '');
        $parser = new Parser(new JoseEncoder());
        $signingKey = Key\InMemory::plainText($key);

        try {
            $token = $parser->parse($tokenString);
        } catch (\Throwable $th) {
            return ['status' => 'fail', 'msg' => 'token 签名验证失败', 'data' => [
                'exception' => $th->getMessage(),
            ]];
        }

        $validator = new Validator();
        $now = new \DateTimeImmutable();

        # 获取token数据
        $claims = $token->claims()->all();

        # 验证Token
        if (!$validator->validate($token, new IssuedBy('mulo'))) {
            return ['status' => 'fail', 'msg' => 'token 发行人验证失败'];
        }

        if ($scene && !$validator->validate($token, new HasClaimWithValue('scene', $scene))) {
            return ['status' => 'fail', 'msg' => 'scene 使用场景验证失败', 'data' => [
                'scene' => $scene,
                'current_scene' => $claims['scene'] ?? '',
            ]];
        }

        if (!$validator->validate($token, new SignedWith(new Sha256(), $signingKey))) {
            return ['status' => 'fail', 'msg' => 'token 签名验证失败', 'data' => []];
        }
        // 验证过期或未生效(生成时为立即生效则无需判断未生效)
        $clock = new SystemClock($now->getTimezone());
        if (!$validator->validate($token, new StrictValidAt($clock))) {
            return ['status' => 'fail', 'msg' => 'token 已过期'];
        }

        # 获取token数据
        // $claims = $token->claims()->all();

        return ['status' => 'success', 'msg' => '验证成功', 'data' => [
            'uid' => $claims['uid'],
            'scene' => $claims['scene'] ?? '',
            'ident' => $claims['jti'],
            'data' => (isset($claims['data']) && $claims['data']) ? json_decode($claims['data'], true) : [],
            // 'claims' => $claims,
        ]];
    }

    static function getConfig()
    {
        $key = Env::get('JWT.KEY', 'cfc3479cmuloapid9cf9925a244b8b13');
        $issuedBy = Env::get('JWT.KEY', 'mulo');
        $permittedFor = Env::get('JWT.KEY', 'mulo-model');
        $relatedTo = Env::get('JWT.KEY', 'mulo');

        return [
            'key'=>$key,
            'issuedBy'=>$issuedBy,
            'permittedFor'=>$permittedFor,
            'relatedTo'=>$relatedTo,
        ];
    }
}
