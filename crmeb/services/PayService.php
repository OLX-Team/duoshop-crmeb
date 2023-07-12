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
use think\facade\Cache;

class PayService
{
    protected $type;
    protected $options;
    protected $affect;

    public function __construct(string $type, array $options, string $affect = 'order')
    {
        $this->type = $type;
        $this->affect = $affect;
        $this->options = $options;
    }

    public function pay(?User $user)
    {
        $method = 'pay' . ucfirst($this->type);
        if (!method_exists($this, $method)) {
            throw new ValidateException('不支持该支付方式');
        }
        return $this->{$method}($user);
    }

    public function payWeixin(User $user)
    {
        $wechatUserRepository = app()->make(WechatUserRepository::class);
        $openId = $wechatUserRepository->idByOpenId($user['wechat_user_id']);
        if (!$openId)
            throw new ValidateException('请关联微信公众号!');
        $config = WechatService::create()->jsPay($openId, $this->options['order_sn'], $this->options['pay_price'], $this->options['attach'], $this->options['body']);
        return compact('config');
    }

    public function payWeixinQr(?User $user)
    {
        $config = WechatService::create()->paymentPrepare('', $this->options['order_sn'], $this->options['pay_price'], $this->options['attach'], $this->options['body'], '', 'NATIVE');
        return ['config' => $config['code_url']];
    }

    public function payRoutine(User $user)
    {
        $wechatUserRepository = app()->make(WechatUserRepository::class);
        $openId = $wechatUserRepository->idByRoutineId($user['wechat_user_id']);
        if (!$openId)
            throw new ValidateException('请关联微信小程序!');
        $config = MiniProgramService::create()->jsPay($openId, $this->options['order_sn'], $this->options['pay_price'], $this->options['attach'], $this->options['body']);
        return compact('config');
    }

    public function payH5(User $user)
    {
        $config = WechatService::create()->paymentPrepare(null, $this->options['order_sn'], $this->options['pay_price'], $this->options['attach'], $this->options['body'], '', 'MWEB');
        return compact('config');
    }

    public function payWeixinApp(User $user)
    {
        $config = WechatService::create()->jsPay(null, $this->options['order_sn'], $this->options['pay_price'], $this->options['attach'], $this->options['body'], '', 'APP');
        return compact('config');
    }

    public function payAlipay(User $user)
    {
        $url = AlipayService::create($this->affect)->wapPaymentPrepare($this->options['order_sn'], $this->options['pay_price'], $this->options['body'], $this->options['return_url']);
        $pay_key = md5($url);
        Cache::store('file')->set('pay_key' . $pay_key, $url, 3600);
        return ['config' => $url, 'pay_key' => $pay_key];
    }

    public function payAlipayQr(? User $user)
    {
        $url = AlipayService::create($this->affect)->qrPaymentPrepare($this->options['order_sn'], $this->options['pay_price'], $this->options['body']);
        return ['config' => $url];
    }

    public function payAlipayApp(User $user)
    {
        $config = AlipayService::create($this->affect)->appPaymentPrepare($this->options['order_sn'], $this->options['pay_price'], $this->options['body']);
        return compact('config');
    }
}
