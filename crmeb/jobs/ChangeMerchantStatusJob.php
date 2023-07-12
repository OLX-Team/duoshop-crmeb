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


namespace crmeb\jobs;


use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\service\StoreServiceRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;
use think\queue\Job;

class ChangeMerchantStatusJob implements JobInterface
{

    public function fire($job, $merId)
    {
        $merchant = app()->make(MerchantRepository::class)->get($merId);
        if ($merchant) {
            $where = [
                'mer_status' => ($merchant['is_del'] || !$merchant['mer_state'] || !$merchant['status']) ? 0 : 1
            ];
            app()->make(ProductRepository::class)->changeMerchantProduct($merId, $where);
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
