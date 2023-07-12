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


use app\common\model\user\User;
use app\common\repositories\wechat\WechatUserRepository;
use think\exception\ValidateException;

class CombinePayService
{
    protected $type;
    protected $options;

    public function __construct(string $type, array $options)
    {
        $this->type = $type;
        $this->options = $options;
    }

    public function pay(User $user)
    {
        $method = 'payCombine' . ucfirst($this->type);
        if (!method_exists($this, $method)) {
            throw new ValidateException('不支持该支付方式');
        }
        return $this->{$method}($user);
    }

    public function payCombineWeixin(User $user)
    {
        $wechatUserRepository = app()->make(WechatUserRepository::class);
        $openId = $wechatUserRepository->idByOpenId($user['wechat_user_id']);
        if (!$openId)
            throw new ValidateException('请关联微信公众号!');
        $config = WechatService::create()->combinePay()->payJs($openId, $this->options);
        return compact('config');
    }

    public function payCombineWeixinQr(User $user)
    {
        $config = WechatService::create()->combinePay()->payNative($this->options);
        return ['config' => $config['code_url']];
    }

    public function payCombineRoutine(User $user)
    {
        $wechatUserRepository = app()->make(WechatUserRepository::class);
        $openId = $wechatUserRepository->idByRoutineId($user['wechat_user_id']);
        if (!$openId)
            throw new ValidateException('请关联微信小程序!');
        $config = MiniProgramService::create()->combinePay()->payJs($openId, $this->options);
        return compact('config');
    }

    public function payCombineH5(User $user)
    {
        $config = WechatService::create()->combinePay()->payH5($this->options, 'Wap');
        return ['config' => ['mweb_url' => $config['h5_url']]];
    }

    public function payCombineWeixinApp(User $user)
    {
        $config = WechatService::create()->combinePay()->payApp($this->options);
        return compact('config');
    }
}
