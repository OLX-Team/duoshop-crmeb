<?php

// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------


namespace crmeb\services;


use crmeb\exceptions\AuthException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use think\facade\Config;
use UnexpectedValueException;

class JwtTokenService
{
    /**
     * @param int $id
     * @param string $type
     * @param $exp
     * @param array $params
     * @return array
     * @author xaboy
     * @day 2020/10/13
     */
    public function createToken(int $id, string $type, $exp, array $params = [])
    {
        $time = time();
        $host = app('request')->host();
        $params += [
            'iss' => $host,
            'aud' => $host,
            'iat' => $time,
            'nbf' => $time,
            'exp' => $exp,
        ];
        $params['jti'] = [$id, $type];
        $token = JWT::encode($params, Config::get('app.app_key', 'default'));
        $params['token'] = $token;
        $params['out'] = $exp - time();
        return $params;
    }

    /**
     * @param string $token
     * @return object
     * @throws SignatureInvalidException    Provided JWT was invalid because the signature verification failed
     * @throws BeforeValidException         Provided JWT is trying to be used before it's eligible as defined by 'nbf'
     * @throws BeforeValidException         Provided JWT is trying to be used before it's been created as defined by 'iat'
     * @throws ExpiredException             Provided JWT has since expired, as defined by the 'exp' claim
     * @throws UnexpectedValueException     Provided JWT was invalid
     * @author xaboy
     * @day 2020-04-09
     */
    public function parseToken(string $token)
    {
        return JWT::decode($token, Config::get('app.app_key', 'default'), array('HS256'));
    }

    /**
     * @param string $token
     * @return object
     * @author xaboy
     * @day 2020-04-10
     */
    public function decode(string $token)
    {
        $tks = explode('.', $token);
        if (count($tks) != 3)
            throw new AuthException('Invalid token');

        if (null === $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($tks[1])))
            throw new AuthException('Invalid token');

        return $payload;
    }
}
