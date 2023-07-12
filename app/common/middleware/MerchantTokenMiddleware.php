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

use app\common\repositories\system\merchant\MerchantAdminRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\Request;
use crmeb\exceptions\AuthException;
use crmeb\services\JwtTokenService;
use Firebase\JWT\ExpiredException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

class MerchantTokenMiddleware extends BaseMiddleware
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
            if(!$token) $token = trim($request->param('token',''));
            if (strpos($token, 'Bearer') === 0)
                $token = trim(substr($token, 6));
            if (!$token)
                throw new ValidateException('请登录');

            /**
             * @var MerchantAdminRepository $repository
             */
            $repository = app()->make(MerchantAdminRepository::class);
            $service = new JwtTokenService();
            try {
                $payload = $service->parseToken($token);
            } catch (ExpiredException $e) {
                $repository->checkToken($token);
                $payload = $service->decode($token);
            } catch (Throwable $e) {//Token 过期
                throw new AuthException('token 已过期');
            }
            if ('mer' != $payload->jti[1])
                throw new AuthException('无效的 token');

            $admin = $repository->get($payload->jti[0]);
            if (!$admin)
                throw new AuthException('账号不存在');
            if (!$admin['status'])
                throw new AuthException('账号已被禁用');

            /**
             * @var MerchantRepository $merchantRepository
             */
            $merchantRepository = app()->make(MerchantRepository::class);

            $merchant = $merchantRepository->get($admin->mer_id);

            if (!$merchant || !$merchant['status'])
                throw new AuthException('商户已被锁定');

        } catch (Throwable $e) {
            if ($force)
                throw  $e;
            $request->macro('isLogin', function () {
                return false;
            });
            $request->macros(['tokenInfo', 'adminId', 'adminInfo', 'token', 'merchant', 'merchantId'], function () {
                throw new AuthException('请登录');
            });
            return;
        }
        $repository->updateToken($token);

        $request->macro('isLogin', function () {
            return true;
        });
        $request->macro('tokenInfo', function () use (&$payload) {
            return $payload;
        });
        $request->macro('token', function () use (&$token) {
            return $token;
        });
        $request->macro('adminId', function () use (&$admin) {
            return $admin->merchant_admin_id;
        });
        $request->macro('adminInfo', function () use (&$admin) {
            return $admin;
        });
        $request->macro('merchantId', function () use (&$admin) {
            return $admin->mer_id;
        });
        $request->macro('merchant', function () use (&$merchant) {
            return $merchant;
        });
        $request->macro('userType', function () use (&$merchant) {
            return 3;
        });
    }

    public function after(Response $response)
    {
        // TODO: Implement after() method.
    }
}
