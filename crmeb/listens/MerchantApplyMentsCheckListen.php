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

use app\common\repositories\system\merchant\MerchantApplymentsRepository;
use crmeb\services\TimerService;
use Swoole\Timer;
use think\facade\Log;
use crmeb\interfaces\ListenerInterface;

class MerchantApplyMentsCheckListen extends TimerService implements ListenerInterface
{
    public function handle($event): void
    {
        //申请状态: 0.平台未提交，-1.平台驳回，10.平台提交审核中，11.需用户操作 ，20.已完成，30.已冻结，40.驳回
        $make =  app()->make(MerchantApplymentsRepository::class);

        $this->tick(1000 * 60 * 30, function () use($make) {
            $ret = $make->getSearch(['is_del' => 0])->where('status','in',[10,11,30])->select();
            try {
                foreach ($ret as $item) {
                    $make->check($item['mer_id']);
                }
            } catch (\Exception $e) {
                Log::info('自动查询分账商户审核失败' . date('Y-m-d H:i:s', time()));
            }
        });
    }
}
