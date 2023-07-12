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


use app\common\repositories\store\StoreActivityRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;
use think\queue\Job;
use app\common\repositories\store\product\ProductRepository;

class AutoChangeStatusActivityJob implements JobInterface
{

    public function fire($job, $data)
    {
        $make = app()->make(StoreActivityRepository::class);
        $make->getsearch(['is_status' => 1])->chunk(100,function($list){
            foreach ($list as $item) {
                try{
                    if (strtotime($item['end_time']) <= time()) {
                        $item->is_show = 0;
                        $item->status = -1;
                        $item->save();
                    } else if (!$item['status'] && strtotime($item['start_time']) <= time()) {
                        $item->is_show = 1;
                        $item->status = 1;
                        $item->save();
                    }
                }catch (\Exception $exception){
                    Log::info('自动同步活动状态失败:'.$exception->getMessage());
                }
            }
        });
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
