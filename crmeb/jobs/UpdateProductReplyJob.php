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


use app\common\repositories\store\product\ProductReplyRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use crmeb\interfaces\JobInterface;

class UpdateProductReplyJob implements JobInterface
{

    public function fire($job, $productId)
    {
        $productReplyRepository = app()->make(ProductReplyRepository::class);

        $total = $productReplyRepository->productTotalRate($productId);
        if (!$total) return $job->delete();
        if(!$total['total_rate']) {
            $rate = 5;
        } else {
            $rate = bcdiv($total['total_rate'], $total['total_count'], 1);
        }
        app()->make(ProductRepository::class)->update($productId, [
            'rate' => $rate,
            'reply_count' => $total['total_count']
        ]);
        $data = $productReplyRepository->getWhere(['product_id' => $productId], 'mer_id');
        $merchantRate = $productReplyRepository->merchantTotalRate($data['mer_id']);
        app()->make(MerchantRepository::class)->update($data['mer_id'], $merchantRate);
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
