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


namespace crmeb\services\alipay;


use Payment\Contracts\IPayNotify;
use think\exception\ValidateException;
use think\facade\Log;

class AlipayNotify implements IPayNotify
{
    private $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function handle(string $channel, string $notifyType, string $notifyWay, array $notifyData)
    {
        Log::info('支付宝支付回调' . var_export($notifyData, 1));
        if (!in_array($notifyData['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED']))
            throw new ValidateException('未支付');
        try {
            event('pay_success_' . $this->type, ['order_sn' => $notifyData['out_trade_no'], 'data' => $notifyData]);
            return true;
        } catch (\Exception$e) {
            Log::info('支付宝支付回调失败:' . $e->getMessage());
        }
        return false;
    }
}
