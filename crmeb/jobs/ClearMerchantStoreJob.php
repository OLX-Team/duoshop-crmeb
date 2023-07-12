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

use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use app\common\repositories\store\GuaranteeTemplateRepository;
use app\common\repositories\store\GuaranteeValueRepository;
use app\common\repositories\store\parameter\ParameterRepository;
use app\common\repositories\store\parameter\ParameterTemplateRepository;
use app\common\repositories\store\parameter\ParameterValueRepository;
use app\common\repositories\store\product\ProductCateRepository;
use app\common\repositories\store\product\ProductLabelRepository;
use app\common\repositories\store\product\ProductReplyRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\StoreDiscountProductRepository;
use app\common\repositories\store\product\StoreDiscountRepository;
use app\common\repositories\store\shipping\ShippingTemplateRepository;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;

class ClearMerchantStoreJob implements JobInterface
{

    public function fire($job, $data)
    {
        try{
            /**
             * 商户商品分类
             * 商户商品
             * 商品参数
             * 商品服务模板
             * 商品标签
             * 商品评价
             * 优惠套餐
             * 运费模板
             * 商户优惠券
             * 商户组合数据
             * 配置
             * 商户图片及源文件
             */
            $merId = (int)$data['mer_id'];
            app()->make(ProductRepository::class)->clearMerchantProduct($merId);
            $servers = [
                app()->make(ProductCateRepository::class),
                app()->make(StoreCategoryRepository::class),
                app()->make(ParameterRepository::class),
                app()->make(ParameterTemplateRepository::class),
                app()->make(ParameterValueRepository::class),
                app()->make(GuaranteeTemplateRepository::class),
                app()->make(GuaranteeValueRepository::class),
                app()->make(ProductLabelRepository::class),
                app()->make(ProductReplyRepository::class),
                app()->make(StoreDiscountRepository::class),
                app()->make(StoreDiscountProductRepository::class),
                app()->make(StoreCouponRepository::class),
                app()->make(StoreCouponUserRepository::class),
                app()->make(GroupDataRepository::class),
                app()->make(ConfigValueRepository::class),
                app()->make(ShippingTemplateRepository::class),
            ];
            foreach ($servers as $server) {
                $server->clear($merId,'mer_id');
            }
        }catch (\Exception $e){
            Log::info('商户ID：'.$data['mer_id'].'清除出错：'.$e->getMessage());
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
