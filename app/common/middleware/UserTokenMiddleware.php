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


namespace app\common\middleware;

use app\common\repositories\user\UserRepository;
use app\Request;
use crmeb\exceptions\AuthException;
use crmeb\services\JwtTokenService;
use Firebase\JWT\ExpiredException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

class UserTokenMiddleware extends BaseMiddleware
{

    /**
     * @param Request $request
     * @throws Throwable
     * @author xaboy
     * @day 2020-04-10
     */
    public function before(Request $request)
    {
        $force = $this->getArg(0, true);
        try {
            $token = trim($request->header('X-Token'));
            if (strpos($token, 'Bearer') === 0)
                $token = trim(substr($token, 6));
            if (!$token)
                throw new ValidateException('请登录');

            /**
             * @var UserRepository $repository
             */
            $repository = app()->make(UserRepository::class);
            $service = new JwtTokenService();
            try {
                $payload = $service->parseToken($token);
            } catch (ExpiredException $e) {
                $repository->checkToken($token);
                $payload = $service->decode($token);
            } catch (Throwable $e) {//Token 过期
                throw new AuthException('token 已过期');
            }
            if ('user' != $payload->jti[1])
                throw new AuthException('无效的 token');

            $user = $repository->get($payload->jti[0]);
            if (!$user)
                throw new AuthException('用户不存在');
            if (!$user['status'])
                throw new AuthException('用户已被禁用');
            if ($user['cancel_time'])
                throw new AuthException('用户不存在');

        } catch (Throwable $e) {
            if ($force)
                throw  $e;
            $request->macro('isLogin', function () {
                return false;
            });
            $request->macros(['tokenInfo', 'uid', 'userInfo', 'token'], function () {
                throw new AuthException('请登录');
            });
            return;
        }
        $repository->updateToken($token);

        $request->macro('isLogin', function () {
            return true;
        });
        $request->macro('userType', function () {
            return 1;
        });
        $request->macro('tokenInfo', function () use (&$payload) {
            return $payload;
        });
        $request->macro('token', function () use (&$token) {
            return $token;
        });
        $request->macro('uid', function () use (&$user) {
            return $user->uid;
        });
        $request->macro('userInfo', function () use (&$user) {
            return $user;
        });
    }

    public function after(Response $response)
    {
        // TODO: Implement after() method.
    }
}
