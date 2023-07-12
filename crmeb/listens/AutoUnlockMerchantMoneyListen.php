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


namespace crmeb\listens;


use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserBillRepository;
use crmeb\interfaces\ListenerInterface;
use crmeb\services\TimerService;
use think\facade\Db;

class AutoUnlockMerchantMoneyListen extends TimerService implements ListenerInterface
{
    public function handle($event): void
    {
        $this->tick(1000 * 60 * 20, function () {
            request()->clearCache();
            $userBill = app()->make(UserBillRepository::class);
            $timer = ((int)systemConfig('mer_lock_time'));
            $time = date('Y-m-d H:i:s', $timer ? strtotime("- $timer day") : time());
            $bills = $userBill->getTimeoutMerchantMoneyBill($time);
            $merchant = app()->make(MerchantRepository::class);
            foreach ($bills as $bill) {
                Db::transaction(function () use ($bill, $merchant) {
                    $merchant->addMoney($bill->mer_id, $bill->number);
                    $bill->status = 1;
                    $bill->save();
                });
            }
        });
    }
}
