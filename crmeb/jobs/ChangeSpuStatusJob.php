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

use app\common\repositories\store\product\SpuRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;

class ChangeSpuStatusJob implements JobInterface
{

    public function fire($job, $data)
    {
        try{
            $make = app()->make(SpuRepository::class);
            if (is_array($data['id'])){
                foreach ($data['id'] as $i) {
                    $make->changeStatus($i,$data['product_type']);
                }
            } else if(is_numeric($data['id'])){
                $make->changeStatus($data['id'],$data['product_type']);
            }
        }catch (\Exception $exception){
            Log::info($exception->getMessage());
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
