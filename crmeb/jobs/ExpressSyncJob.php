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

use app\common\repositories\store\shipping\ExpressRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;

class ExpressSyncJob implements JobInterface
{

    public function fire($job, $data)
    {
        try{
            app()->make(ExpressRepository::class)->syncExportAll();
        }catch (\Exception $exception){
            Log::info(var_export($exception, 1));
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
