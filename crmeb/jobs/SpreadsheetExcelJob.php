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

use crmeb\interfaces\JobInterface;
use think\facade\Log;
use think\queue\Job;
use crmeb\services\ExcelService;

class SpreadsheetExcelJob implements JobInterface
{

    public function fire($job, $data)
    {
        try{
            app()->make(ExcelService::class)->getAll($data);
        }catch (\Exception $e){
            Log::info('导出文件:'.$data['type'].'; error : ' . $e->getMessage());
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
