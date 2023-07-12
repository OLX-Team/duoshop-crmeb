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

namespace app\common\repositories\store\product;

use app\common\model\store\product\ProductLabel;
use app\common\model\user\User;
use app\common\repositories\community\CommunityRepository;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\GuaranteeRepository;
use app\common\repositories\store\GuaranteeTemplateRepository;
use app\common\repositories\store\GuaranteeValueRepository;
use app\common\repositories\store\order\StoreCartRepository;
use app\common\repositories\store\order\StoreOrderProductRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\parameter\ParameterValueRepository;
use app\common\repositories\store\StoreActivityRepository;
use app\common\repositories\store\StoreSeckillActiveRepository;
use app\common\repositories\store\StoreSeckillTimeRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserRelationRepository;
use app\common\repositories\user\UserVisitRepository;
use app\validate\merchant\StoreProductValidate;
use crmeb\jobs\ChangeSpuStatusJob;
use crmeb\jobs\DownloadProductCopyImage;
use crmeb\jobs\SendSmsJob;
use crmeb\services\QrcodeService;
use crmeb\services\RedisCacheService;
use crmeb\services\SwooleTaskService;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use app\common\repositories\BaseRepository;
use app\common\dao\store\product\ProductDao as dao;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\store\shipping\ShippingTemplateRepository;
use app\common\repositories\store\StoreBrandRepository;
use think\facade\Queue;
use think\facade\Route;
use think\contract\Arrayable;

/**
 * Class ProductRepository
 * @package app\common\repositories\store\product
 * @author xaboy
 * @mixin dao
 */
class ProductRepository extends BaseRepository
{

    protected $dao;
    const CREATE_PARAMS = [
        "image", "slider_image", "store_name", "store_info", "keyword", "bar_code", "guarantee_template_id", "cate_id",  "unit_name", "sort" , "is_show", "is_good", 'is_gift_bag', 'integral_rate', "video_link", "temp_id", "content", "spec_type", "extension_type", "attr", 'mer_labels', 'delivery_way', 'delivery_free','param_temp_id','extend',
        ["mer_cate_id",[]],
        ['refund_switch',1],
        ["brand_id",0],
        ['once_max_count',0],
        ['once_min_count',0],
        ['pay_limit', 0],
        ["attrValue",[]],
        ['give_coupon_ids',[]],
        ['type',0],
        ['svip_price',0],
        ['svip_price_type',0],
        ['params',[]],
        ['product_type',0],
    ];
    protected $admin_filed = 'Product.product_id,Product.mer_id,brand_id,spec_type,unit_name,mer_status,rate,reply_count,store_info,cate_id,Product.image,slider_image,Product.store_name,Product.keyword,Product.sort,U.rank,Product.is_show,Product.sales,Product.price,extension_type,refusal,cost,U.ot_price,stock,is_gift_bag,Product.care_count,Product.status,is_used,Product.create_time,Product.product_type,old_product_id,star,ficti,integral_total,integral_price_total,sys_labels,param_temp_id,mer_svip_status,svip_price,svip_price_type';
    protected $filed = 'Product.product_id,Product.mer_id,brand_id,unit_name,spec_type,mer_status,rate,reply_count,store_info,cate_id,Product.image,slider_image,Product.store_name,Product.keyword,Product.sort,Product.is_show,Product.sales,Product.price,extension_type,refusal,cost,U.ot_price,stock,is_gift_bag,Product.care_count,Product.status,is_used,Product.create_time,Product.product_type,old_product_id,integral_total,integral_price_total,mer_labels,Product.is_good,Product.is_del,type,param_temp_id,mer_svip_status,svip_price,svip_price_type';

    const  NOTIC_MSG  = [
            1  => [
                '0' => 'product_success',
                '1' => 'product_seckill_success',
                '2' => 'product_presell_success',
                '3' => 'product_assist_success',
                '4' => 'product_group_success',
                'msg' => '审核通过'
            ],
            -1 => [
                '0' => 'product_fail',
                '1' => 'product_seckill_fail',
                '2' => 'product_presell_fail',
                '3' => 'product_assist_fail',
                '4' => 'product_group_fail',
                'msg' => '审核失败'
            ],
            -2 => [
                '0' => 'product_fail',
                '1' => 'product_seckill_fail',
                '2' => 'product_presell_fail',
                '3' => 'product_assist_fail',
                '4' => 'product_group_fail',
                'msg' => '被下架'
            ],
        ];
    /**
     * ProductRepository constructor.
     * @param dao $dao
     */
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param int $id
     * @param int $merId
     * @return mixed
     */
    public function CatExists(int $id)
    {
        return (app()->make(StoreCategoryRepository::class))->merExists(0, $id);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/20
     * @param $ids
     * @param int $merId
     * @return bool
     */
    public function merCatExists($ids, int $merId)
    {
        if (!is_array($ids ?? '')) return true;
        foreach ($ids as $id) {
            if (!(app()->make(StoreCategoryRepository::class))->merExists($merId, $id))
                return false;
        }
        return true;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param int $merId
     * @param int $id
     * @return mixed
     */
    public function merShippingExists(int $merId, int $id)
    {
        $make = app()->make(ShippingTemplateRepository::class);
        return $make->merExists($merId, $id);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param int $id
     * @return mixed
     */
    public function merBrandExists(int $id)
    {
        $make = app()->make(StoreBrandRepository::class);
        return $make->meExists($id);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param int $merId
     * @param int $id
     * @return bool
     */
    public function merExists(?int $merId, int $id)
    {
        return $this->dao->merFieldExists($merId, $this->getPk(), $id);
    }

    public function merDeleteExists(int $merId, int $id)
    {
        return $this->dao->getDeleteExists($merId, $id);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/30
     * @param int $merId
     * @param int $id
     * @return bool
     */
    public function apiExists(?int $merId, int $id)
    {
        return $this->dao->apiFieldExists($merId, $this->getPk(), $id);
    }

    /**
     * @param int $merId
     * @param int $tempId
     * @return bool
     * @author Qinii
     */
    public function merTempExists(int $merId, int $tempId)
    {
        return $this->dao->merFieldExists($merId, 'temp_id', $tempId);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param array $data
     */
    public function create(array $data, int $productType = 0, $conType = 0)
    {
        if (!$data['spec_type']) {
            $data['attr'] = [];
            if (count($data['attrValue']) > 1) throw new ValidateException('单规格商品属性错误');
        }
        $content = [
            'content' => $conType ? json_encode($data['content']) : $data['content'] ,
            'type' => $conType
        ];
        $product = $this->setProduct($data);
        event('product.create.before', compact('data','productType','conType'));
        return Db::transaction(function () use ($data, $productType,$conType,$content,$product) {
            $activity_id = 0;
            $result = $this->dao->create($product);
            $settleParams = $this->setAttrValue($data, $result->product_id, $productType, 0);
            $settleParams['cate'] = $this->setMerCate($data['mer_cate_id'], $result->product_id, $data['mer_id']);
            $settleParams['attr'] = $this->setAttr($data['attr'], $result->product_id);
            if ($productType ==0 ) app()->make(ParameterValueRepository::class)->create($result->product_id, $data['params'] ?? [],$data['mer_id']);
            $this->save($result->product_id, $settleParams, $content,$product,$productType);
            if (in_array($productType, [0, 1])) {
                if ($productType == 1) { //秒杀商品
                    $dat = $this->setSeckillProduct($data);
                    $dat['product_id'] = $result->product_id;
                    $seckill = app()->make(StoreSeckillActiveRepository::class)->create($dat);
                    $activity_id = $seckill->seckill_active_id;
                }
                $product['price'] = $settleParams['data']['price'];
                $product['ot_price'] = $settleParams['data']['ot_price'];
                $product['mer_labels'] = $data['mer_labels'];
                app()->make(SpuRepository::class)->create($product, $result->product_id, $activity_id, $productType);
            }
            $product = $result;
            event('product.create',compact('product'));
            if (isset($data['is_copy']) && $data['is_copy']) Queue::push(DownloadProductCopyImage::class,['id' => $result->product_id, 'data' => $data]);
            return $result->product_id;
        });
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param int $id
     * @param array $data
     */
    public function edit(int $id, array $data, int $merId, int $productType, $conType = 0)
    {
        if (!$data['spec_type']) {
            $data['attr'] = [];
            if (count($data['attrValue']) > 1) throw new ValidateException('单规格商品属性错误');
        }
        event('product.update.before', compact('id','data','merId','productType','conType'));
        $spuData = $product = $this->setProduct($data);
        $settleParams = $this->setAttrValue($data, $id, $productType, 1);
        $settleParams['cate'] = $this->setMerCate($data['mer_cate_id'], $id, $merId);
        $settleParams['attr'] = $this->setAttr($data['attr'], $id);
        $content = [
            'content' =>  $conType ? json_encode($data['content']) : $data['content'] ,
            'type' => $conType
        ];
        $spuData['price'] = $settleParams['data']['price'];
        $spuData['mer_id'] = $merId;
        $spuData['mer_labels'] = $data['mer_labels'];

        Db::transaction(function () use ($id, $data,  $productType, $settleParams,$content,$product,$spuData,$merId) {
            $this->save($id, $settleParams, $content, $product, $productType);
            if ($productType == 1) { //秒杀商品
                $dat = $this->setSeckillProduct($data);
                app()->make(StoreSeckillActiveRepository::class)->updateByProduct($id, $dat);
            }
            if ($productType == 0) {
                $make = app()->make(ParameterValueRepository::class);
                $make->clear($id, 'product_id');
                $make->create($id, $data['params'] ?? [], $merId);
            }
            app()->make(SpuRepository::class)->baseUpdate($spuData, $id, 0, $productType);
            event('product.update',compact('id'));
            app()->make(SpuRepository::class)->changeStatus($id, $productType);
        });
    }

    public function freeTrial(int $id, array $data, int $merId)
    {
        if (!$data['spec_type']) {
            $data['attr'] = [];
            if (count($data['attrValue']) > 1) throw new ValidateException('单规格商品属性错误');
        }
        $res = $this->dao->get($id);
        $data['svip_price_type'] = $res['svip_price_type'];
        $settleParams = $this->setAttrValue($data, $id, 0, 1);
        $settleParams['cate'] = $this->setMerCate($data['mer_cate_id'], $id, $merId);
        $settleParams['attr'] = $this->setAttr($data['attr'], $id);
        $data['price'] = $settleParams['data']['price'];
        unset($data['attrValue'],$data['attr'],$data['mer_cate_id']);
        $ret = app()->make(SpuRepository::class)->getSearch(['product_id' => $id, 'product_type' => 0,])->find();
        Db::transaction(function () use ($id, $data, $settleParams,$ret) {
            $this->save($id, $settleParams, null, [], 0);
            app()->make(SpuRepository::class)->update($ret->spu_id,['price' => $data['price']]);
            Queue(SendSmsJob::class, ['tempId' => 'PRODUCT_INCREASE', 'id' => $id]);
        });
    }


    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param $id
     */
    public function destory($id)
    {
        (app()->make(ProductAttrRepository::class))->clearAttr($id);
        (app()->make(ProductAttrValueRepository::class))->clearAttr($id);
        (app()->make(ProductContentRepository::class))->clearAttr($id, null);
        (app()->make(ProductCateRepository::class))->clearAttr($id);
        $this->dao->delete($id, true);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/20
     * @param $id
     * @param $spec_type
     * @param $settleParams
     * @param $content
     * @return int
     */
    public function save($id, $settleParams, $content, $data = [], $productType = 0)
    {
        $res = $this->dao->get($id);

        $productAttrRepository = app()->make(ProductAttrRepository::class);
        $productAttrRepository->clearAttr($res->product_id);
        if (isset($settleParams['attr']))
            $productAttrRepository->insert($settleParams['attr']);

        $productCateRepository = app()->make(ProductCateRepository::class);
        $productCateRepository->clearAttr($res->product_id);
        if (isset($settleParams['cate']))
            $productCateRepository->insert($settleParams['cate']);

        $productAttrValueRepository = app()->make(ProductAttrValueRepository::class);
        $productAttrValueRepository->clearAttr($res->product_id);
        if (isset($settleParams['attrValue'])) {
            $arr = array_chunk($settleParams['attrValue'], 30);
            foreach ($arr as $item){
                $productAttrValueRepository->add($item,$res->type);
            }
        }
        if ($content){
            app()->make(ProductContentRepository::class)->clearAttr($res->product_id,$content['type']);
            $this->dao->createContent($res->product_id, $content);
        }

        if (isset($settleParams['data'])) $data =array_merge($data,$settleParams['data']);
        $this->dao->update($id,$data);
        if(isset($data['status']) && $data['status'] !== 1 ){
            $message = '您有1个新的'. ($productType ? '秒杀商品' : ($data['is_gift_bag'] ? '礼包商品' :'商品')) . '待审核';
            $type = $productType ? 'new_seckill' : ($data['is_gift_bag'] ? 'new_bag' :'new_product');
            SwooleTaskService::admin('notice', [
                'type' => $type,
                'data' => [
                    'title' => '商品审核',
                    'message' => $message,
                    'id' => $id
                ]
            ]);
        }
        return $res;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param int $id
     * @param array $data
     * @return int
     */
    public function adminUpdate(int $id, array $data)
    {
        Db::transaction(function () use ($id, $data) {
            app()->make(ProductContentRepository::class)->clearAttr($id, 0);
            $this->dao->createContent($id, ['content' => $data['content']]);
            unset($data['content']);
            $res = $this->dao->getWhere(['product_id' => $id], '*', ['seckillActive']);
            $activity_id = $res['seckillActive']['seckill_active_id'] ?? 0;
            app()->make(SpuRepository::class)->changRank($activity_id, $id, $res['product_type'], $data);
            unset($data['star']);
            return $this->dao->update($id, $data);
        });
    }

    /**
     *  格式化秒杀商品活动时间
     * @Author:Qinii
     * @Date: 2020/9/15
     * @param array $data
     * @return array
     */
    public function setSeckillProduct(array $data)
    {
        $dat = [
            'start_day' => $data['start_day'],
            'end_day' => $data['end_day'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => 1,
            'once_pay_count' => $data['once_pay_count'],
            'all_pay_count' => $data['all_pay_count'],
        ];
        if (isset($data['mer_id'])) $dat['mer_id'] = $data['mer_id'];
        return $dat;
    }

    /**
     *  格式商品主体信息
     * @Author:Qinii
     * @Date: 2020/9/15
     * @param array $data
     * @return array
     */
    public function setProduct(array $data)
    {
        $give_coupon_ids = '';
        if (isset($data['give_coupon_ids']) && !empty($data['give_coupon_ids'])) {
            $gcoupon_ids = array_unique($data['give_coupon_ids']);
            $give_coupon_ids = implode(',', $gcoupon_ids);
        }

        if(isset($data['integral_rate'])){
            $integral_rate = $data['integral_rate'];
            if($data['integral_rate'] < 0) $integral_rate = -1;
            if($data['integral_rate'] > 100) $integral_rate = 100;

        }

        $result = [
            'store_name' => $data['store_name'],
            'image' => $data['image'],
            'slider_image' => is_array($data['slider_image']) ? implode(',', $data['slider_image']) : '',
            'store_info' => $data['store_info'] ?? '',
            'keyword' => $data['keyword']??'',
            'brand_id' => $data['brand_id'] ?? 0,
            'cate_id' => $data['cate_id'] ?? 0,
            'unit_name' => $data['unit_name']??'件',
            'sort' => $data['sort'] ?? 0,
            'is_show' => $data['is_show'] ?? 0,
            'is_used' =>  (isset($data['status']) && $data['status'] == 1) ? 1 : 0,
            'is_good' => $data['is_good'] ?? 0,
            'video_link' => $data['video_link']??'',
            'temp_id' => $data['delivery_free'] ? 0 : ($data['temp_id'] ?? 0),
            'extension_type' => $data['extension_type']??0,
            'spec_type' => $data['spec_type'] ?? 0,
            'status' => $data['status']??0,
            'give_coupon_ids' => $give_coupon_ids,
            'mer_status' => $data['mer_status'],
            'guarantee_template_id' => $data['guarantee_template_id']??0,
            'is_gift_bag' => $data['is_gift_bag'] ?? 0,
            'integral_rate' =>  $integral_rate ?? 0,
            'delivery_way' => implode(',',$data['delivery_way']),
            'delivery_free' => $data['delivery_free'] ?? 0,
            'once_min_count' => $data['once_min_count'] ?? 0,
            'once_max_count' => $data['once_max_count'] ?? 0,
            'pay_limit' => $data['pay_limit'] ?? 0,
            'svip_price_type' => $data['svip_price_type'] ?? 0,
            'refund_switch' => $data['refund_switch'] ?? 0,
        ];
        if (isset($data['extend']))
            $result['extend'] = $data['extend']  ? json_encode($data['extend'], JSON_UNESCAPED_UNICODE) : '';
        if (isset($data['mer_id']))
            $result['mer_id'] = $data['mer_id'];
        if (isset($data['old_product_id']))
            $result['old_product_id'] = $data['old_product_id'];
        if (isset($data['product_type']))
            $result['product_type'] = $data['product_type'];
        if (isset($data['type']) && $data['type'])
            $result['type'] = $data['type'];
        if (isset($data['param_temp_id']))
            $result['param_temp_id'] = $data['param_temp_id'];
        return $result;
    }

    /**
     *  格式商品商户分类
     * @Author:Qinii
     * @Date: 2020/9/15
     * @param array $data
     * @param int $productId
     * @param int $merId
     * @return array
     */
    public function setMerCate(array $data, int $productId, int $merId)
    {
        $result = [];
        foreach ($data as $value) {
            $result[] = [
                'product_id' => $productId,
                'mer_cate_id' => $value,
                'mer_id' => $merId,
            ];
        }
        return $result;
    }

    /**
     *  格式商品规格
     * @Author:Qinii
     * @Date: 2020/9/15
     * @param array $data
     * @param int $productId
     * @return array
     */
    public function setAttr(array $data, int $productId)
    {
        $result = [];
        try{
            foreach ($data as $value) {
                $result[] = [
                    'type' => 0,
                    'product_id' => $productId,
                    "attr_name" => $value['value'] ?? $value['attr_name'],
                    'attr_values' => implode('-!-', $value['detail']),
                ];
            }
        } catch (\Exception $exception) {
            throw new ValidateException('商品规格格式错误');
        }
        return $result;
    }

    /**
     *  格式商品SKU
     * @Author:Qinii
     * @Date: 2020/9/15
     * @param array $data
     * @param int $productId
     * @return mixed
     */
    public function setAttrValue(array $data, int $productId, int $productType, int $isUpdate = 0)
    {
        $extension_status = systemConfig('extension_status');
        if ($isUpdate) {
            $product = app()->make(ProductAttrValueRepository::class)->search(['product_id' => $productId])->select()->toArray();
            $oldSku = $this->detailAttrValue($product, null);
        }
        $price = $stock = $ot_price = $cost = $svip_price = 0;
        try {
            foreach ($data['attrValue'] as $value) {
                $_svip_price = 0;
                $sku = '';
                if (isset($value['detail']) && !empty($value['detail']) && is_array($value['detail']))
                    $sku = implode(',', $value['detail']);

                $ot_price_ = $value['price'];
                if (isset($value['active_price'])) {
                    $sprice = $value['active_price'] < 0 ? 0 : $value['active_price'];
                } elseif (isset($value['presell_price'])) {
                    $sprice = $value['presell_price'] < 0 ? 0 : $value['presell_price'];
                } elseif (isset($value['assist_price'])) {
                    $sprice = $value['assist_price'] < 0 ? 0 : $value['assist_price'];
                } else {
                    $ot_price_ = $value['ot_price'];
                    $sprice = ($value['price'] < 0) ? 0 : $value['price'];
                }
                if (isset($data['svip_price_type']) && $data['svip_price_type'] == 2) {
                    $_svip_price = $value['svip_price'];
                    $svip_price = !$svip_price ?  $value['svip_price'] : (($svip_price > $value['svip_price']) ? $value['svip_price'] : $svip_price);
                }

                $cost   = !$cost ? $value['cost'] : (($cost > $value['cost']) ?$cost: $value['cost']);
                $price  = !$price ? $sprice : (($price > $sprice) ? $sprice : $price);
                $ot_price = !$ot_price ? $ot_price_ : (($ot_price > $ot_price_) ? $ot_price : $ot_price_);

                $unique = $this->setUnique($productId, $sku, $productType);
                if (isset($data['type']) && $data['type'] == 2) {
                    [$cdkey,$cdkeey_stock] = $this->setCdkey($value['cdkey'],$productId);
                    !$cdkeey_stock ?: $value['stock'] = $cdkeey_stock;
                }
                $array = [
                    'detail' => json_encode($value['detail'] ?? ''),
                    "bar_code" => $value["bar_code"] ?? '',
                    "image" => $value["image"] ?? '',
                    "cost" => $value['cost'] ? (($value['cost'] < 0) ? 0 : $value['cost']) : 0,
                    "price" => $value['price'] ? (($value['price'] < 0) ? 0 : $value['price']) : 0,
                    "volume" => isset($value['volume']) ? ($value['volume'] ? (($value['volume'] < 0) ? 0 : $value['volume']) : 0) :0,
                    "weight" => isset($value['weight']) ? ($value['weight'] ? (($value['weight'] < 0) ? 0 : $value['weight']) : 0) :0,
                    "stock" => $value['stock'] ? (($value['stock'] < 0) ? 0 : $value['stock']) : 0,
                    "ot_price" => $value['ot_price'] ? (($value['ot_price'] < 0) ? 0 : $value['ot_price']) : 0,
                    "extension_one" => $extension_status ? ($value['extension_one'] ?? 0) : 0,
                    "extension_two" => $extension_status ? ($value['extension_two'] ?? 0) : 0,
                    "product_id" => $productId,
                    "type" => 0,
                    "sku" => $sku,
                    "unique" => $unique,
                    'sales' => $isUpdate ? ($oldSku[$sku]['sales'] ?? 0) : 0,
                    'svip_price' => $_svip_price,
                ];

                if (isset($data['type']) && $data['type'] == 2) {
                    $array['cdkey'] = $cdkey;
                }

                $result['attrValue'][] = $array;
                $stock = $stock + intval($value['stock']);
            }
            $result['data'] = [
                'price' => $price ,
                'stock' => $stock,
                'ot_price'  => $ot_price,
                'cost'      => $cost,
                'svip_price' => $svip_price,
            ];
        } catch (\Exception $exception) {
            throw new ValidateException('规格错误 ：'.$exception->getMessage());
        }
        return $result;
    }

    public function setCdkey($data,$productId)
    {
        if (!isset($data['is_type'])) throw new ValidateException('请选择添加卡密');
        $stock = 0;
        if ($data['is_type']) {
            $stock = count($data['list']);
            foreach ($data['list'] as $datum) {
                if (!isset($datum['key']) || !$datum['key']) throw new ValidateException('请添加卡密内容');
                $cdkey[] = [
                    'is_type' => $data['is_type'],
                    'key' => $datum['key'],
                    'pwd' => $datum['pwd'],
                    'product_id' => $productId
                ];
            }
        } else {
            $cdkey[] = [
                'is_type' => $data['is_type'],
                'key' => $data['key'],
                'pwd' => '',
                'product_id' => $productId
            ];
        }
        return [$cdkey,$stock];
    }


    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param int $id
     * @param string $sku
     * @param int $type
     * @return string
     */
    public function setUnique(int $id, $sku, int $type)
    {
        return $unique = substr(md5($sku . $id), 12, 11) . $type;
        //        $has = (app()->make(ProductAttrValueRepository::class))->merUniqueExists(null, $unique);
        //        return $has ? false : $unique;
    }


    /**
     * TODO 后台管理需要的商品详情
     * @param int $id
     * @param int|null $activeId
     * @return array|\think\Model|null
     * @author Qinii
     * @day 2020-11-24
     */
    public function getAdminOneProduct(int $id, ?int $activeId, $conType = 0)
    {
        $with = ['attr', 'attrValue.productCdkey', 'oldAttrValue',  'merCateId.category', 'storeCategory', 'brand', 'temp', 'seckillActive',
            'content' => function($query) use($conType){
                $query->where('type',$conType);
            },
            'merchant'=> function($query){
                $query->field('mer_id,mer_avatar,mer_name,is_trader');
            },
            'guarantee.templateValue.value',

        ];

        $data = $this->dao->geTrashedtProduct($id)->with($with)->find();

        $data['delivery_way']  = empty($data['delivery_way']) ? [2] : explode(',',$data['delivery_way']);
        $data['extend']  = empty($data['extend']) ? [] : json_decode($data['extend']);
        $make_order = app()->make(StoreCouponRepository::class);
        $where = [['coupon_id', 'in', $data['give_coupon_ids']]];
        $data['coupon'] = $make_order->selectWhere($where, 'coupon_id,title')->toArray();
        $spu_make = app()->make(SpuRepository::class);

        $append = [];
        if ($data['product_type'] == 0) {
            $append = ['us_status', 'params'];
            $activeId = 0;
        }
        if ($data['product_type'] == 1){
            $activeId = $data->seckillActive->seckill_active_id;
            $make = app()->make(StoreOrderRepository::class);
            $append = ['us_status','seckill_status'];
        }
        if ($data['product_type'] == 2) $make = app()->make(ProductPresellSkuRepository::class);
        if ($data['product_type'] == 3) $make = app()->make(ProductAssistSkuRepository::class);
        if ($data['product_type'] == 4) $make = app()->make(ProductGroupSkuRepository::class);

        $spu_where = ['activity_id' => $activeId, 'product_type' => $data['product_type'], 'product_id' => $id];
        $spu = $spu_make->getSearch($spu_where)->find();
        $data['star'] = $spu['star'] ?? '';
        $data['mer_labels'] = $spu['mer_labels'] ?? '';
        $data['sys_labels'] = $spu['sys_labels'] ?? '';

        $data->append($append);
        $mer_cat = [];
        if (isset($data['merCateId'])) {
            foreach ($data['merCateId'] as $i) {
                $mer_cat[] = $i['mer_cate_id'];
            }
        }
        $data['mer_cate_id'] = $mer_cat;

        foreach ($data['attr'] as $k => $v) {
            $data['attr'][$k] = [
                'value' => $v['attr_name'],
                'detail' => $v['attr_values']
            ];
        }
        $attrValue = (in_array($data['product_type'], [3, 4])) ?  $data['oldAttrValue'] : $data['attrValue'];
        unset($data['oldAttrValue'], $data['attrValue']);
        $arr = [];
        if (in_array($data['product_type'], [1, 3])) $value_make = app()->make(ProductAttrValueRepository::class);
        foreach ($attrValue as $item) {
            if ($data['product_type'] == 1) {
                $value = $value_make->getSearch(['sku' => $item['sku'], 'product_id' => $data['old_product_id']])->find();
                $old_stock = $value['stock'];
                $item['sales'] = $make->skuSalesCount($item['unique']);
            }
            if ($data['product_type'] == 2) {
                $item['presellSku'] = $make->getSearch(['product_presell_id' => $activeId, 'unique' => $item['unique']])->find();
                if (is_null($item['presellSku'])) continue;
            }
            if ($data['product_type'] == 3) {
                $item['assistSku'] = $make->getSearch(['product_assist_id' => $activeId, 'unique' => $item['unique']])->find();
                if (is_null($item['assistSku']))
                    continue;
            }
            if ($data['product_type'] == 4) {
                $item['_sku'] = $make->getSearch(['product_group_id' => $activeId, 'unique' => $item['unique']])->find();
                if (is_null($item['_sku']))
                    continue;
            }
            $sku = explode(',', $item['sku']);
            $item['old_stock'] = $old_stock ?? $item['stock'];
            if (isset($item['productCdkey']) && count($item['productCdkey'])) {
                foreach ($item['productCdkey'] as $hasCdkey) {
                    if ($hasCdkey['is_type']) {
                        $cdkey['list'][] = $hasCdkey;
                    } else {
                        $cdkey['key'] = $hasCdkey['key'];
                    }
                    $cdkey['is_type'] = $hasCdkey['is_type'];
                }
                $item['cdkey'] = $cdkey;
            }
            unset($item['productCdkey']);
            foreach ($sku as $k => $v) {
                $item['value' . $k] = $v;
            }
            $arr[] = $item;
        }
        $data['attrValue'] = $arr;
        $content = $data['content']['content'] ?? '';
        if ($conType) $content = json_decode($content);
        unset($data['content']);
        $data['content'] = $content;
        return $data;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param $type
     * @param int|null $merId
     * @return array
     */
    public function switchType($type, ?int $merId = 0, $productType = 0)
    {
        $stock = 0;
        if ($merId) $stock = merchantConfig($merId, 'mer_store_stock');
        switch ($type) {
            case 1:
                $where = ['is_show' => 1, 'status' => 1,];
                break;
            case 2:
                $where = ['is_show' => 0, 'status' => 1];
                break;
            case 3:
                $where = ['is_show' => 1, 'stock' => 0, 'status' => 1];
                break;
            case 4:
                $where = ['stock' => $stock ? $stock : 0, 'status' => 1];
                break;
            case 5:
                $where = ['soft' => true];
                break;
            case 6:
                $where = ['status' => 0];
                break;
            case 7:
                $where = ['status' => -1];
                break;
            case 20:
                $where = ['status' => 1];
                break;
            default:
                //                $where = ['is_show' => 1, 'status' => 1];
                break;
        }
        if ($productType == 0) {
            $where['product_type'] = $productType;
            if (!$merId) $where['is_gift_bag'] = 0;
        }
        if ($productType == 1) {
            $where['product_type'] = $productType;
        }
        if ($productType == 10) {
            $where['is_gift_bag'] = 1;
        }
        if (!$merId) $where['star'] = '';
        return $where;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param int|null $merId
     * @return array
     */
    public function getFilter(?int $merId, $name = '', $productType = 0)
    {
        $result = [];
        $result[] = [
            'type' => 1,
            'name' => '出售中' . $name,
            'count' => $this->dao->search($merId, $this->switchType(1, $merId, $productType))->count()
        ];
        $result[] = [
            'type' => 2,
            'name' => '仓库中' . $name,
            'count' => $this->dao->search($merId, $this->switchType(2, $merId, $productType))->count()
        ];
        if ($merId) {
            $result[] = [
                'type' => 3,
                'name' => '已售罄' . $name,
                'count' => $this->dao->search($merId, $this->switchType(3, $merId, $productType))->count()
            ];
            $result[] = [
                'type' => 4,
                'name' => '警戒库存',
                'count' => $this->dao->search($merId, $this->switchType(4, $merId, $productType))->count()
            ];
        }
        $result[] = [
            'type' => 6,
            'name' => '待审核' . $name,
            'count' => $this->dao->search($merId, $this->switchType(6, $merId, $productType))->count()
        ];
        $result[] = [
            'type' => 7,
            'name' => '审核未通过' . $name,
            'count' => $this->dao->search($merId, $this->switchType(7, $merId, $productType))->count()
        ];
        if ($merId) {
            $result[] = [
                'type' => 5,
                'name' => '回收站' . $name,
                'count' => $this->dao->search($merId, $this->switchType(5, $merId, $productType))->count()
            ];
        }
        return $result;
    }

    /**
     * TODO 商户商品列表
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param int $merId
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getList(?int $merId, array $where, int $page, int $limit)
    {
        $query = $this->dao->search($merId, $where)->with(['merCateId.category', 'storeCategory', 'brand']);
        $count = $query->count();
        $data = $query->page($page, $limit)->setOption('field', [])->field($this->filed)->select();

        $data->append(['us_status']);

        $list = hasMany(
            $data ,
            'mer_labels',
            ProductLabel::class,
            'product_label_id',
            'mer_labels',
            ['status' => 1],
            'product_label_id,product_label_id id,label_name name'
        );

        return compact('count', 'list');
    }

    /**
     * TODO 商户秒杀商品列表
     * @param int|null $merId
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2020-08-04
     */
    public function getSeckillList(?int $merId, array $where, int $page, int $limit)
    {
        $make = app()->make(StoreOrderRepository::class);
        $query = $this->dao->search($merId, $where)->with(['merCateId.category', 'storeCategory', 'brand', 'attrValue ', 'seckillActive']);
        $count = $query->count();
        $data = $query->page($page, $limit)->setOption('field', [])->field($this->filed)->order('sort DESC')->select()
            ->each(function ($item) use ($make, $where) {
                $result = $this->getSeckillAttrValue($item['attrValue'], $item['old_product_id']);
                $item['stock'] = $result['stock'];
                return $item;
            });
        $data->append(['seckill_status', 'us_status']);

        $list = hasMany(
            $data ,
            'mer_labels',
            ProductLabel::class,
            'product_label_id',
            'mer_labels',
            ['status' => 1],
            'product_label_id,product_label_id id,label_name name'
        );


        return compact('count', 'list');
    }

    /**
     * TODO 平台商品列表
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param int $merId
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getAdminList(?int $merId, array $where, int $page, int $limit)
    {
        $query = $this->dao->search($merId, $where)->with([
            'merCateId.category',
            'storeCategory',
            'brand',
            'merchant',
        ]);
        $count = $query->count();
        $data = $query->page($page, $limit)->setOption('field', [])->field($this->admin_filed)->select();
        $data->append([ 'us_status']);
        $list = hasMany(
            $data ,
            'sys_labels',
            ProductLabel::class,
            'product_label_id',
            'sys_labels',
            ['status' => 1],
            'product_label_id,product_label_id id,label_name name'
        );

        return compact('count', 'list');
    }

    /**
     * TODO 平台秒杀商品列表
     * @param int|null $merId
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2020-08-04
     */
    public function getAdminSeckillList(?int $merId, array $where, int $page, int $limit)
    {
        $query = $this->dao->search($merId, $where)->with([
            'merCateId.category',
            'storeCategory',
            'brand',
            'merchant',
            'seckillActive',
            'attrValue',
        ]);
        $count = $query->count();
        $data = $query->page($page, $limit)
            ->field('Product.*,U.star,U.rank,U.sys_labels')
            ->select()
            ->each(function ($item) use ($where) {
                $result = $this->getSeckillAttrValue($item['attrValue'], $item['old_product_id']);
                $item['stock'] = $result['stock'];
                $item['sales'] = app()->make(StoreOrderRepository::class)->seckillOrderCounut($item['product_id']);
                return $item;
            });
        $data->append(['seckill_status', 'us_status']);

        $list = hasMany(
            $data ,
            'sys_labels',
            ProductLabel::class,
            'product_label_id',
            'sys_labels',
            ['status' => 1],
            'product_label_id,product_label_id id,label_name name'
        );

        return compact('count', 'list');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/28
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param $userInfo
     * @return array
     */
    public function getApiSearch($merId, array $where, int $page, int $limit, $userInfo)
    {
        $where = array_merge($where, $this->dao->productShow());
        //搜索记录
        if (isset($where['keyword']) && !empty($where['keyword']))
            app()->make(UserVisitRepository::class)->searchProduct(
                $userInfo ? $userInfo['uid'] : 0,
                $where['keyword'],
                (int)($where['mer_id'] ?? 0)
            );
        $query = $this->dao->search($merId, $where)->with(['merchant', 'issetCoupon']);
        $count = $query->count();
        $list = $query->page($page, $limit)->setOption('field', [])->field($this->admin_filed)->select();
        $append[] = 'max_extension';
        if ($this->getUserIsPromoter($userInfo)) $list->append($append);
        return compact('count', 'list');
    }

    /**
     * TODO 秒杀列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2020-08-04
     */
    public function getApiSeckill(array $where, int $page, int $limit)
    {
        $field = 'Product.product_id,Product.mer_id,is_new,U.keyword,brand_id,U.image,U.product_type,U.store_name,U.sort,U.rank,star,rate,reply_count,sales,U.price,cost,U.ot_price,stock,extension_type,care_count,unit_name,U.create_time';
        $make = app()->make(StoreOrderRepository::class);
        $res = app()->make(StoreSeckillTimeRepository::class)->getBginTime($where);
        $count = 0;
        $list = [];

        if ($res) {
            $where = [
                'start_time' => $res['start_time'],
                'end_time' => $res['end_time'],
                'day' => date('Y-m-d', time()),
                'star' => '',
                'mer_id' => $where['mer_id']
            ];
            $query = $this->dao->seckillSearch($where)->with(['seckillActive']);
            $count = $query->count();
            $list = $query->page($page, $limit)->setOption('field', [])->field($field)->select()
                ->each(function ($item) use ($make) {
                    $item['sales'] = $make->seckillOrderCounut($item['product_id']);
                    $item['stop']  = $item->end_time;
                    return $item;
                });
        }
        return compact('count', 'list');
    }

    /**
     * TODO 平台礼包列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2020-06-01
     */
    public function getBagList(array $where, int $page, int $limit)
    {
        $query = $this->dao->search(null, $where)->with(['merCateId.category', 'storeCategory', 'brand', 'merchant' => function ($query) {
            $query->field('mer_id,mer_avatar,mer_name,product_score,service_score,postage_score,status,care_count,is_trader');
        }]);
        $count = $query->count($this->dao->getPk());
        $list = $query->page($page, $limit)->setOption('field', [])->field($this->filed)->select();

        return compact('count', 'list');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/28
     * @param array $where
     * @return mixed
     */
    public function getBrandByCategory(array $where)
    {
        $mer_id = $where['mer_id'] ? $where['mer_id'] : null;
        unset($where['mer_id']);
        $query = $this->dao->search($mer_id, $where);
        return $query->group('brand_id')->column('brand_id');
    }

    /**
     * api 获取商品详情
     * @Author:Qinii
     * @Date: 2020/5/30
     * @param $id
     * @param $userInfo
     */
    public function detail(int $id, $userInfo)
    {
        $where = [
            'is_show' => 1,
            'status' => 1,
            'is_used' => 1,
            'mer_status' => 1,
            'product_id' => $id
        ];
        return $this->apiProductDetail($where, 0, null, $userInfo);
    }


    /**
     * TODO api秒杀商品详情
     * @param int $id
     * @author Qinii
     * @day 2020-08-05
     */
    public function seckillDetail(int $id,$userInfo)
    {
        $where = $this->seckillShow();
        $where['product_id'] = $id;
        return $this->apiProductDetail($where, 1, null,$userInfo);
    }

    public function apiProductDetail(array $where, int $productType, ?int $activityId, $userInfo = null)
    {
        $field = 'is_show,product_id,mer_id,image,slider_image,store_name,store_info,unit_name,price,cost,ot_price,stock,sales,video_link,product_type,extension_type,old_product_id,rate,guarantee_template_id,temp_id,once_max_count,pay_limit,once_min_count,integral_rate,delivery_way,delivery_free,type,cate_id,svip_price_type,svip_price,mer_svip_status';
        $with = [
            'attr',
            'content' => function($query) {
                $query->order('type ASC');
            },
            'attrValue',
            'oldAttrValue',
            'merchant' => function ($query) {
                $query->with(['type_name'])->append(['isset_certificate','services_type']);
            },
            'seckillActive' => function ($query) {
                $query->field('start_day,end_day,start_time,end_time,product_id');
            },
            'temp'
        ];

        $append = ['guaranteeTemplate','params'];
        $where['product_type'] = $productType;

        $res = $this->dao->getWhere($where, $field, $with);
        if (!$res)  return [];
        switch ($res['product_type']) {
            case 0:
                $append[] = 'max_integral';
                $append[] = 'show_svip_info';
                break;
            case 1:
                $_where = $this->dao->productShow();
                $_where['product_id'] = $res['old_product_id'];
                $oldProduct = $this->dao->getWhere($_where);
                $result = $this->getSeckillAttrValue($res['attrValue'], $res['old_product_id']);
                $res['attrValue'] = $result['item'];

                $res['stock'] = $result['stock'];
                $res['stop'] = strtotime(date('Y-m-d', time()) . $res['seckillActive']['end_time'] . ':00:00');
                $res['sales'] = app()->make(StoreOrderRepository::class)->seckillOrderCounut($where['product_id']);
                $res['quota'] = $this->seckillStock($where['product_id']);
                $res['old_status'] = $oldProduct ? 1 : 0;
                $append[] = 'seckill_status';
                break;
            default:
                break;
        }

        if ($userInfo) {
            try {
                $isRelation = app()->make(UserRelationRepository::class)->getUserRelation(['type_id' => $activityId ?? $where['product_id'], 'type' => $res['product_type']], $userInfo['uid']);
            } catch (\Exception $e) {
                $isRelation = false;
            }
            if ($this->getUserIsPromoter($userInfo) && $productType == 0) {
                $append[] = 'max_extension';
                $append[] = 'min_extension';
            }
        }

        $attr = $this->detailAttr($res['attr']);
        $attrValue = (in_array($res['product_type'], [3, 4])) ?  $res['oldAttrValue'] : $res['attrValue'];
        $sku  = $this->detailAttrValue($attrValue, $userInfo, $productType, $activityId);

        $res['isRelation'] = $isRelation ?? false;
        $care = false;
        if ($userInfo) {
            $care = app()->make(MerchantRepository::class)->getCareByUser($res['mer_id'], $userInfo->uid);
        }
        $res['merchant']['top_banner'] = merchantConfig($res['mer_id'], 'mer_pc_top');
        $res['merchant']['care'] = $care;
        $res['replayData'] = null;
        if (systemConfig('sys_reply_status')){
            $res['replayData'] = app()->make(ProductReplyRepository::class)->getReplyRate($res['product_id']);
            $append[] = 'topReply';
        }
        unset($res['attr'], $res['attrValue'], $res['oldAttrValue'], $res['seckillActive']);
        if (count($attr) > 1) {
            $firstSku = [];
            foreach ($attr as $item) {
                $firstSku[] = $item['attr_values'][0];
            }
            $firstSkuKey = implode(',', $firstSku);
            if (isset($sku[$firstSkuKey])) {
                $sku = array_merge([$firstSkuKey => $sku[$firstSkuKey]], $sku);
            }
        }
        $res['attr'] = $attr;
        $res['sku'] = $sku;
        $res->append($append);

        if ($res['content'] && $res['content']['type'] == 1) {
            $res['content']['content'] = json_decode($res['content']['content']);
        }

        $res['merchant']['recommend'] = $this->getRecommend($res['product_id'], $res['mer_id']);
        $spu = app()->make(SpuRepository::class)->getSpuData(
            $activityId ?: $res['product_id'],
            $productType,
            0
        );
        $res['spu_id'] = $spu->spu_id;
        if (systemConfig('community_status')) {
            $res['community'] = app()->make(CommunityRepository::class)->getDataBySpu($spu->spu_id);
        }
        //热卖排行
        if (systemConfig('hot_ranking_switch') && $res['spu_id']) {
            $hot = $this->getHotRanking($res['spu_id'], $res['cate_id']);
            $res['top_name'] = $hot['top_name'] ?? '';
            $res['top_num'] = $hot['top_num'] ?? 0;
            $res['top_pid'] = $hot['top_pid'] ?? 0;
        }
        //活动氛围图
        if (in_array($res['product_type'],[0,2,4])){
            $active =  app()->make(StoreActivityRepository::class)->getActivityBySpu(StoreActivityRepository::ACTIVITY_TYPE_ATMOSPHERE,$res['spu_id'],$res['cate_id'],$res['mer_id']);
            if ($active) $res['atmosphere_pic'] = $active['pic'];
        }

        return $res;
    }


    /**
     * TODO 热卖排行
     * @param int $spuId
     * @param int $cateId
     * @return array
     * @author Qinii
     */
    public function getHotRanking(int $spuId, int $cateId)
    {
        $data = [];
        //热卖排行
        $lv = systemConfig('hot_ranking_lv') ?:0;
        $categoryMake = app()->make(StoreCategoryRepository::class);
        $cate = $categoryMake->getWhere(['store_category_id' => $cateId]);
        if ($lv != 2 && $cate) $cateId = $lv == 1 ? $cate->pathIds[2] : $cate->pathIds[1];
        $RedisCacheService = app()->make(RedisCacheService::class);
        $prefix = env('QUEUE_NAME','merchant').'_hot_ranking_';
        $key = ( $prefix.'top_item_'.$cateId.'_'.$spuId);
        $k1 = $RedisCacheService->keys($key);
        if ($k1) {
            $top = $RedisCacheService->handler()->get($key);
            $top = json_decode($top);
            $data['top_name'] = $top[0];
            $data['top_num']  = $top[1];
            $data['top_pid']  = $cateId;
        }
        return $data;
    }

    /**
     * TODO 商户下的推荐
     * @param $productId
     * @param $merId
     * @return array
     * @author Qinii
     * @day 12/7/21
     */
    public function getRecommend($productId,$merId)
    {
        $make = app()->make(ProductCateRepository::class);
        $product_id = [];
        if ($productId) {
            $catId = $make->getSearch(['product_id' => $productId])->column('mer_cate_id');
            $product_id = $make->getSearch([])->whereIn('mer_cate_id',$catId)->column('product_id');
        }

        $query = $this->dao->getSearch([])
            ->where($this->dao->productShow())
            ->when($productId,function($query) use ($productId) {
                $query->where('product_id','<>',$productId);
            })
            ->when($product_id,function($query) use ($product_id) {
                $query->whereIn('product_id',$product_id);
            })
            ->where('mer_id',$merId)->limit(10);
        $data = [];
        $count = $query->count();

        if ($count < 3) {
            $productIds[] = $productId;
            $data = $this->dao->getSearch([])
                ->where($this->dao->productShow())
                ->whereNotIn('product_id',$productIds)
                ->where('is_good',1)
                ->where('mer_id', $merId)
                ->field('mer_id,product_id,store_name,image,price,is_show,status,is_gift_bag,is_good,sales,create_time')
                ->order('sort DESC,create_time DESC')
                ->limit((3 - $count))
                ->select()->toArray();
        } if ($count > 0 ){
            $count = $count  > 3 ? 3 : $count;
            $res = $query->setOption('field',[])->field('mer_id,product_id,store_name,image,price,is_show,status,is_gift_bag,is_good,sales,create_time')
                ->order('sort DESC,create_time DESC')
                ->limit($count)
                ->select()->toArray();
            $data = array_merge($data, $res);
        }
        return $data;
    }



    /**
     * TODO 单商品属性
     * @param $data
     * @return array
     * @author Qinii
     * @day 2020-08-05
     */
    public function detailAttr($data, $preview = 0, $user = null)
    {
        $attr = [];
        foreach ($data as $key => $item) {
            if ($item instanceof Arrayable) {
                $attr[$key] = $item->toArray();
            }
            $arr = [];
            if ($preview) {
                $item['attr_values'] = explode('-!-', $item['attr_values']);
                $attr[$key]['attr_values'] = $item['attr_values'];
            }
            $values = $item['attr_values'];
            foreach ($values as $i => $value) {
                $arr[] = [
                    'attr' => $value,
                    'check' => false
                ];
            }
            $attr[$key]['product_id'] = $item['product_id'];
            $attr[$key]['attr_name'] = $item['attr_name'];
            $attr[$key]['attr_value'] = $arr;
            $attr[$key]['attr_values'] = $values;
        }
        return $attr;
    }

    /**
     * TODO 获取秒杀商品的库存数
     * @param array $data
     * @param int $oldProductId
     * @return array
     * @author Qinii
     * @day 2020-11-12
     */
    public function getSeckillAttrValue($data, $oldProductId)
    {
        /**
         *  秒杀商品限购数量
         *  原商品库存 > 限购数
         *      销量 = 订单总数 - 退款退货 - （未发货且仅退款）
         *      限购数 = 限购数 - 销量
         *  原商品库存 < 限购数
         *      限购数 = 原商品库存
         */
        $make = app()->make(ProductAttrValueRepository::class);
        $order_make = app()->make(StoreOrderRepository::class);
        $stock = 0;
        $item = [];
        foreach ($data as $k => $value) {
            $where = [
                'sku' => $value['sku'],
                'product_id' => $oldProductId
            ];
            //愿商品库存信息
            $attr = $make->getWhere($where);
            if ($attr) {
                $value['stock'] = ($attr['stock'] < $value['stock']) ?
                    $attr['stock'] :
                    $value['stock'] - $order_make->seckillSkuOrderCounut($value['unique']);
            }
            $stock = $stock + $value['stock'];
            $item[] = $value;
        }
        return compact('item', 'stock');
    }
    /**
     * TODO 单商品sku
     * @param $data
     * @param $userInfo
     * @return array
     * @author Qinii
     * @day 2020-08-05
     */
    public function detailAttrValue($data, $userInfo, $productType = 0, $artiveId = null, $svipInfo = [])
    {
        $sku = [];
        $make_presll = app()->make(ProductPresellSkuRepository::class);
        $make_assist = app()->make(ProductAssistSkuRepository::class);
        $make_group = app()->make(ProductGroupSkuRepository::class);
        foreach ($data as $value) {
            $_value = [
                'sku'    => $value['sku'],
                'price'  => $value['price'],
                'stock'  => $value['stock'],
                'image'  => $value['image'],
                'weight' => $value['weight'],
                'volume' => $value['volume'],
                'sales'  => $value['sales'],
                'unique' => $value['unique'],
                'bar_code' => $value['bar_code'],
            ];
            if($productType == 0 ){
                $_value['ot_price'] = $value['ot_price'];
                $_value['svip_price'] = $value['svip_price'];
            }
            if ($productType == 2) {
                $_sku = $make_presll->getSearch(['product_presell_id' => $artiveId, 'unique' => $value['unique']])->find();
                if (!$_sku) continue;
                $_value['price'] = $_sku['presell_price'];
                $_value['stock'] = $_sku['stock'];
                $_value['down_price'] = $_sku['down_price'];
            }
            //助力
            if ($productType == 3) {
                $_sku = $make_assist->getSearch(['product_assist_id' => $artiveId, 'unique' => $value['unique']])->find();
                if (!$_sku) continue;
                $_value['price'] = $_sku['assist_price'];
                $_value['stock'] = $_sku['stock'];
            }
            //拼团
            if ($productType == 4) {
                $_sku = $make_group->getSearch(['product_group_id' => $artiveId, 'unique' => $value['unique']])->find();
                if (!$_sku) continue;
                $_value['price'] = $_sku['active_price'];
                $_value['stock'] = $_sku['stock'];
            }
            //推广员
            if ($this->getUserIsPromoter($userInfo)) {
                $_value['extension_one'] = $value->bc_extension_one;
                $_value['extension_two'] = $value->bc_extension_two;
            }
            $sku[$value['sku']] = $_value;
        }
        return $sku;
    }


    /**
     * TODO 秒杀商品库存检测
     * @param int $productId
     * @return bool|int
     * @author Qinii
     * @day 2020-08-05
     */
    public function seckillStock(int $productId)
    {
        $product = $this->dao->getWhere(['product_id' => $productId], '*', ['attrValue']);
        $count = app()->make(StoreOrderRepository::class)->seckillOrderCounut($productId);
        if ($product['stock'] > $count) {
            $make = app()->make(ProductAttrValueRepository::class);
            foreach ($product['attrValue'] as $item) {
                $attr = [
                    ['sku', '=', $item['sku']],
                    ['product_id', '=', $product['old_product_id']],
                    ['stock', '>', 0],
                ];
                if ($make->getWhereCount($attr)) return true;
            }
        }
        return false;
    }


    /**
     * @Author:Qinii
     * @Date: 2020/5/30
     * @param $userInfo
     * @return bool
     */
    public function getUserIsPromoter($userInfo)
    {
        return (isset($userInfo['is_promoter']) && $userInfo['is_promoter'] && systemConfig('extension_status')) ? true : false;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/30
     * @param $userInfo
     * @param int|null $merId
     * @param $page
     * @param $limit
     * @return array
     */
    public function recommend($userInfo, ?int $merId, $page, $limit)
    {
        $where = ['order' => 'sales'];
        if (!is_null($userInfo)) {
            $cate_ids = app()->make(UserVisitRepository::class)->getRecommend($userInfo['uid']);
            if ($cate_ids) $where = ['cate_ids' => $cate_ids];
        }
        $where = array_merge($where, $this->switchType(1, $merId, 0), $this->dao->productShow());
        $query = $this->dao->search($merId, $where);
        $count = $query->count();
        $list = $query->page($page, $limit)->setOption('field', [])->with(['issetCoupon', 'merchant'])->select();

        return compact('count', 'list');
    }

    /**
     * 检测是否有效
     * @Author:Qinii
     * @Date: 2020/6/1
     * @param $id
     * @return mixed
     */
    public function getOne($id)
    {
        $data = ($this->dao->getWhere([$this->dao->getPk() => $id]));
        if (!is_null($data) && $data->check()) return $data;
        return false;
    }

    /**
     * TODO 上下架 / 显示
     * @param $id
     * @param $status
     * @author Qinii
     * @day 2022/11/12
     */
    public function switchShow($id, $status, $field, $merId = 0)
    {
        $where['product_id'] = $id;
        if ($merId) $where['mer_id'] = $merId;
        $product = $this->dao->getWhere($where);
        if (!$product) throw new ValidateException('数据不存在');
        if ($status == 1 && $product['product_type'] == 2)
            throw new ValidateException('商品正在参与预售活动');
        if ($status == 1 && $product['product_type'] == 3)
            throw new ValidateException('商品正在参与助力活动');
        $this->dao->update($id,[$field => $status]);
        app()->make(SpuRepository::class)->changeStatus($id,$product->product_type);
    }

    public function batchSwitchShow($id, $status, $field, $merId = 0)
    {
        $where['product_id'] = $id;
        if ($merId) $where['mer_id'] = $merId;
        $products = $this->dao->getSearch([])->where('product_id','in', $id)->select();
        if (!$products)
            throw new ValidateException('数据不存在');
        foreach ($products as $product) {
            $product_type = $product['product_type'];
            if ($merId && $product['mer_id'] !== $merId)
                throw new ValidateException('商品不属于您');
            if ($status == 1 && $product['product_type'] == 2)
                throw new ValidateException('ID：'.$product->product_id . ' 商品正在参与预售活动');
            if ($status == 1 && $product['product_type'] == 3)
                throw new ValidateException('ID：'.$product->product_id . ' 商品正在参与助力活动');
        }
        $this->dao->updates($id,[$field => $status]);
        Queue::push(ChangeSpuStatusJob::class,['id' => $id,'product_type'=> $product_type]);
    }

    /**
     * TODO 商品审核
     * @param $id
     * @param $data
     * @author Qinii
     * @day 2022/11/14
     */
    public function switchStatus($id,$data)
    {
        $product = $this->getSearch([])->find($id);
        $this->dao->update($id, $data);
        $status = $data['status'];
        $type = self::NOTIC_MSG[$data['status']][$product['product_type']];
        $message = '您有1个' . ($product['product_type'] ? '秒杀商品' : '商品') . self::NOTIC_MSG[$data['status']]['msg'];
        SwooleTaskService::merchant('notice', [
            'type' => $type,
            'data' => [
                'title' => $status == -2 ? '下架提醒' : '审核结果',
                'message' => $message,
                'id' => $product['product_id']
            ]
        ], $product['mer_id']);
        app()->make(SpuRepository::class)->changeStatus($id,$product->product_type);
    }

    /**
     * TODO 审核操作
     * @param array $id
     * @param array $data
     * @param $product_type
     * @author Qinii
     * @day 2022/9/6
     */
    public function batchSwitchStatus(array $id,array $data)
    {
        $productData = $this->getSearch([])->where('product_id','in', $id)->select();
        foreach ($productData as $product) {
            $product_type = $product['product_type'];
            $type = self::NOTIC_MSG[$data['status']][$product['product_type']];
            $message = '您有1个' . ($product['product_type'] ? '秒杀商品' : '商品') . self::NOTIC_MSG[$data['status']]['msg'];
            SwooleTaskService::merchant('notice', [
                'type' => $type,
                'data' => [
                    'title' => $data['status'] == -2 ? '下架提醒' : '审核结果',
                    'message' => $message,
                    'id' => $product['product_id']
                ]
            ], $product['mer_id']);
        }
        $this->dao->updates($id, $data);
        Queue(ChangeSpuStatusJob::class, ['id' => $id, 'product_type' => $product_type]);
        event('product.status',compact('id','data'));
    }


    public function wxQrCode(int $productId, int $productType, User $user)
    {
        $name = md5('pwx' . $productId . $productType . $user->uid . $user['is_promoter'] . date('Ymd')) . '.jpg';
        $make = app()->make(QrcodeService::class);
        $link = '';
        switch ($productType) {
            case 0: //普通商品
                $link = '/pages/goods_details/index';
                break;
            case 1: //秒杀商品
                $link = '/pages/activity/goods_seckill_details/index';
                break;
            case 2: //预售商品
                $link = '/pages/activity/presell_details/index';
                break;
            case 3: //助力商品
                $link = 'pages/activity/assist_detail/index';
                break;
            case 4: //拼团商品
                $link = '/pages/activity/combination_details/index';
                break;
            case 40: //拼团商品2
                $link = '/pages/activity/combination_status/index';
                break;
            default:
                return false;
        }
        $link = $link . '?id=' . $productId . '&spid=' . $user['uid'];
        $key = 'p' . $productType . '_' . $productId . '_' . $user['uid'];
        return $make->getWechatQrcodePath($name, $link, false, $key);
    }

    public function routineQrCode(int $productId, int $productType, User $user)
    {
        //小程序
        $name = md5('sprt' . $productId . $productType . $user->uid . $user['is_promoter'] . date('Ymd')) . '.jpg';
        $make = app()->make(QrcodeService::class);
        $params = 'id=' . $productId . '&spid=' . $user['uid'];
        $link = '';
        switch ($productType) {
            case 0: //普通商品
                $link = 'pages/goods_details/index';
                break;
            case 1: //秒杀商品
                $link = 'pages/activity/goods_seckill_details/index';
                break;
            case 2: //预售商品
                $link = 'pages/activity/presell_details/index';
                break;
            case 4: //拼团商品
                $link = 'pages/activity/combination_details/index';
                break;
            case 40: //拼团商品2
                $link = 'pages/activity/combination_status/index';
                break;
        }

        return $make->getRoutineQrcodePath($name, $link, $params);
    }

    /**
     * TODO 礼包是否超过数量限制
     * @param $merId
     * @return bool
     * @author Qinii
     * @day 2020-06-25
     */
    public function checkMerchantBagNumber($merId)
    {
        $where = ['is_gift_bag' => 1];
        $promoter_bag_number = systemConfig('max_bag_number');
        $count = $this->dao->search($merId, $where)->count();
        if (is_null($promoter_bag_number) || ($promoter_bag_number > $count)) return true;
        return false;
    }

    public function orderProductIncStock($order, $cart, $productNum = null)
    {
        $productNum = $productNum ?? $cart['product_num'];
        Db::transaction(function () use ($order, $cart, $productNum) {
            $productAttrValueRepository = app()->make(ProductAttrValueRepository::class);
            if ($cart['product_type'] == '1') {
                $oldId = $cart['cart_info']['product']['old_product_id'];
                $productAttrValueRepository->incSkuStock($oldId, $cart['cart_info']['productAttr']['sku'], $productNum);
                $this->dao->incStock($oldId, $productNum);
            } else if ($cart['product_type'] == '2') {
                $presellSku = app()->make(ProductPresellSkuRepository::class);
                $presellSku->incStock($cart['cart_info']['productPresellAttr']['product_presell_id'], $cart['cart_info']['productPresellAttr']['unique'], $productNum);
                $productAttrValueRepository->incStock($cart['product_id'], $cart['cart_info']['productAttr']['unique'], $productNum);
                $this->dao->incStock($cart['product_id'], $productNum);
            } else if ($cart['product_type'] == '3') {
                app()->make(ProductAssistSkuRepository::class)->incStock($cart['cart_info']['productAssistAttr']['product_assist_id'], $cart['cart_info']['productAssistAttr']['unique'], $productNum);
                $productAttrValueRepository->incStock($cart['cart_info']['product']['old_product_id'], $cart['cart_info']['productAttr']['unique'], $productNum);
                $this->dao->incStock($cart['cart_info']['product']['old_product_id'], $productNum);
            } else if ($cart['product_type'] == '4') {
                app()->make(ProductGroupSkuRepository::class)->incStock($cart['cart_info']['activeSku']['product_group_id'], $cart['cart_info']['activeSku']['unique'], $productNum);
                $this->dao->incStock($cart['cart_info']['product']['old_product_id'], $productNum);
                $productAttrValueRepository->incStock($cart['cart_info']['product']['old_product_id'], $cart['cart_info']['productAttr']['unique'], $productNum);
            } else {
                if (isset($cart['cart_info']['product']['old_product_id']) && $cart['cart_info']['product']['old_product_id'] > 0) {
                    $oldId = $cart['cart_info']['product']['old_product_id'];
                    $productAttrValueRepository->incSkuStock($oldId, $cart['cart_info']['productAttr']['sku'], $productNum);
                    $this->dao->incStock($oldId, $productNum);
                } else {
                    $productAttrValueRepository->incStock($cart['product_id'], $cart['cart_info']['productAttr']['unique'], $productNum);
                    $this->dao->incStock($cart['product_id'], $productNum);
                }
                if ($cart->integral > 0) {
                    $totalIntegral = bcmul($productNum, $cart->integral, 0);
                    $this->dao->descIntegral($cart->product_id, $totalIntegral, bcmul(bcdiv($totalIntegral, $order->integral, 2), $order->integral_price, 2));
                }
            }
        });
    }


    public function fictiForm(int $id)
    {
        $form = Elm::createForm(Route::buildUrl('systemStoreProductAddFicti', ['id' => $id])->build());
        $res = $this->dao->getWhere(['product_id' => $id], 'ficti,sales');
        $form->setRule([
            Elm::input('number', '现有已售数量', $res['ficti'])->readonly(true),
            Elm::radio('type', '修改类型', 1)
                ->setOptions([
                    ['value' => 1, 'label' => '增加'],
                    ['value' => 2, 'label' => '减少'],
                ]),
            Elm::number('ficti', '修改已售数量', 0),
        ]);
        return $form->setTitle('修改已售数量');
    }

    /**
     * TODO 普通商品加入购物车检测
     * @param int $prodcutId
     * @param string $unique
     * @param int $cartNum
     * @author Qinii
     * @day 2020-10-20
     */
    public function cartCheck(array $data, $userInfo)
    {
        $cart = null;
        $where = $this->dao->productShow();
        $where['product_id'] = $data['product_id'];
        unset($where['is_gift_bag']);
        $product = $this->dao->search(null, $where)->find();

        if (!$product) throw new ValidateException('商品已下架');
        if ($product['type'] && !$data['is_new']) throw new ValidateException('虚拟商品不可加入购物车');
        $value_make = app()->make(ProductAttrValueRepository::class);
        $sku = $value_make->getOptionByUnique($data['product_attr_unique']);
        if (!$sku) throw new ValidateException('SKU不存在');
        //分销礼包
        if ($product['is_gift_bag']) {
            if (!systemConfig('extension_status')) throw new ValidateException('分销功能未开启');
            if (!$data['is_new']) throw new ValidateException('礼包商品不可加入购物车');
            if ($data['cart_num'] !== 1) throw new ValidateException('礼包商品只能购买一个');
            if ($userInfo->is_promoter) throw new ValidateException('您已经是分销员了');
        }

        //立即购买 限购
        if ($data['is_new']) {
            $cart_num = $data['cart_num'];
        }  else {
            //加入购物车
            //购物车现有
            $_num = $this->productOnceCountCart($where['product_id'],$data['product_attr_unique'], $userInfo->uid);
            $cart_num = $_num + $data['cart_num'];
        }
        if ($sku['stock'] < $cart_num ) throw new ValidateException('库存不足');
        //添加购物车
        if (!$data['is_new']) {
            $cart = app()->make(StoreCartRepository::class)->getCartByProductSku($data['product_attr_unique'], $userInfo->uid);
        }
        return compact('product', 'sku', 'cart');
    }

    /**
     * TODO 购物车单商品数量
     * @param $productId
     * @param $uid
     * @param $num
     * @author Qinii
     * @day 5/26/21
     */
    public function productOnceCountCart($productId,$product_attr_unique,$uid)
    {
        $make = app()->make(StoreCartRepository::class);
        $where = [
            'is_pay' => 0,
            'is_del' => 0,
            'is_new' => 0,
            'is_fail' => 0,
            'product_type' => 0,
            'product_id' => $productId,
            'uid' => $uid,
            'product_attr_unique' => $product_attr_unique,
        ];
        $cart_num = $make->getSearch($where)->sum('cart_num');
        return $cart_num;
    }

    /**
     * TODO 秒杀商品加入购物车检测
     * @param array $data
     * @param int $userInfo
     * @return array
     * @author Qinii
     * @day 2020-10-21
     */
    public function cartSeckillCheck(array $data, $userInfo)
    {
        if ($data['is_new'] !== 1) throw new ValidateException('秒杀商品不能加入购物车');
        if ($data['cart_num'] !== 1) throw new ValidateException('秒杀商品只能购买一个');

        $where = $this->dao->seckillShow();
        $where['product_id'] = $data['product_id'];
        $product = $this->dao->search(null, $where)->find();
        if (!$product) throw new ValidateException('商品已下架');
        if ($product->seckill_status !== 1) throw new ValidateException('该商品不在秒杀时间段内');
        $order_make = app()->make(StoreOrderRepository::class);
        $count = $order_make->seckillOrderCounut($data['product_id']);

        $value_make = app()->make(ProductAttrValueRepository::class);
        $sku = $value_make->getOptionByUnique($data['product_attr_unique']);

        if ($sku['stock'] <= $count)  throw new ValidateException('限购数量不足');
        $_sku = $value_make->getWhere(['sku' => $sku['sku'], 'product_id' => $product['old_product_id']]);
        if (!$_sku) throw new ValidateException('原商品SKU不存在');
        if ($_sku['stock'] <= 0) throw new ValidateException('原库存不足');

        if (!$order_make->getDayPayCount($userInfo->uid, $data['product_id']))
            throw new ValidateException('本次活动您购买数量已达到上限');
        if (!$order_make->getPayCount($userInfo->uid, $data['product_id']))
            throw new ValidateException('本次活动您该商品购买数量已达到上限');
        $cart = null;
        return compact('product', 'sku', 'cart');
    }

    /**
     * TODO 复制一条商品
     * @param int $productId
     * @param array $data
     * @return mixed
     * @author Qinii
     * @day 2020-11-19
     */
    public function productCopy(int $productId, array $data, $productType = 0)
    {
        $product = $this->getAdminOneProduct($productId, null);
        $product = $product->toArray();
        if ($data) {
            foreach ($data as $k => $v) {
                $product[$k] = $v;
            }
        }
        return $this->create($product, $productType);
    }

    public function existsProduct(int $id, $productType)
    {
        switch ($productType) {
            case 2:
                $make = app()->make(ProductPresellRepository::class);
                break;
            case 3:
                $make = app()->make(ProductAssistSetRepository::class);
                break;
            case 4:
                $make = app()->make(ProductGroupRepository::class);
                break;
            case 40:
                $make = app()->make(ProductGroupBuyingRepository::class);
                break;
            default:
                $make = $this->dao;
                break;
        }
        $where = [
            $make->getPk() => $id,
            'is_del' => 0
        ];
        return $make->getWhereCount($where);
    }

    public function updateSort(int $id, ?int $merId, array $data)
    {
        $where[$this->dao->getPk()] = $id;
        if ($merId) $where['mer_id'] = $merId;
        $ret = $this->dao->getWhere($where);
        if (!$ret) throw new  ValidateException('数据不存在');
        app()->make(ProductRepository::class)->update($ret['product_id'], $data);
        $make = app()->make(SpuRepository::class);
        $activityId = $ret['product_type'] ? $ret->seckillActive->seckill_active_id : 0;
        return $make->updateSort($ret['product_id'], $activityId, $ret['product_type'], $data);
    }

    /**
     * TODO 删除商户所有的
     * @param int $merId
     * @author Qinii
     * @day 5/15/21
     */
    public function clearMerchantProduct($merId)
    {
        //普通 秒杀
        $this->dao->clearProduct($merId);
        //助理
        app()->make(ProductAssistRepository::class)->clearProduct($merId);
        //拼团
        app()->make(ProductGroupRepository::class)->clearProduct($merId);
        //预售
        app()->make(ProductPresellRepository::class)->clearProduct($merId);
        //spu
        app()->make(SpuRepository::class)->clearProduct($merId);
    }

    /**
     * TODO 保障服务
     * @param $where
     * @return mixed
     * @author Qinii
     * @day 5/20/21
     */
    public function GuaranteeTemplate($where)
    {
        $data = app()->make(GuaranteeTemplateRepository::class)->getSearch($where)->with(
            [
                'templateValue' => [
                    'value' => function($query){
                        $query->field('guarantee_id,guarantee_name,guarantee_info');
                    }
                ],
            ])->find();
        return $data ?? [];
    }

    /**
     * TODO 添加到货通知
     * @param int $uid
     * @param string $unique
     * @param int $type
     * @author Qinii
     * @day 5/24/21
     */
    public function increaseTake(int $uid, string $unique,int $type,int $product_id)
    {
        $status = systemConfig('procudt_increase_status');
        if(!$status) throw new ValidateException('未开启到货通知');
        $make = app()->make(ProductTakeRepository::class);
        $where['product_id'] = $product_id;
        if($unique) $where['unique'] = $unique;
        $sku = app()->make(ProductAttrValueRepository::class)->getWhere($where);
        if(!$sku) throw new ValidateException('商品不存在');
        $data = [
            'product_id' => $sku['product_id'],
            'unique' => $unique ?: 1,
            'uid' => $uid,
            'status' => 0,
            'type' => $type
        ];
        $make->findOrCreate($data);
    }

    /**
     * TODO 添加 编辑 预览商品
     * @param array $data
     * @param int $productType
     * @return array
     * @author Qinii
     * @day 6/15/21
     */
    public function preview(array $data)
    {
        if (!isset($data['attrValue']) || !$data['attrValue']) {
            throw new ValidateException('缺少商品规格');
        }
        $productType = 0;
        $product = $this->setProduct($data);
        if(isset($data['start_day'])){ //秒杀
            $product['stop'] = time() + 3600;
            $productType = 1;
        }
        if(isset($data['presell_type'])){ //预售
            $product['start_time'] = $data['start_time'];
            $product['end_time'] = $data['end_time'];
            $product['presell_type'] = $data['presell_type'];
            $product['delivery_type'] = $data['delivery_type'];
            $product['delivery_day'] = $data['delivery_day'];
            $product['p_end_time'] =  $data['end_time'];
            $product['final_start_time'] =  $data['final_start_time'];
            $product['final_end_time'] =  $data['final_end_time'];
            $productType = 2;
        }

        if(isset($data['assist_count'])){
            //助力
            $product['assist_count'] = $data['assist_count'];
            $product['assist_user_count'] = $data['assist_user_count'];
            $product['price'] = $data['attrValue'][0]['assist_price'];
            $productType = 3;
        }

        if(isset($data['buying_count_num'])) {
            //
            $product['buying_count_num'] = $data['buying_count_num'];
            $product['pay_count'] = $data['pay_count'];
            $productType = 4;
        }

        $product['slider_image'] = explode(',',$product['slider_image']);
        $product['merchant'] = $data['merchant'];
        $product['content'] = ['content' => $data['content']];
        $settleParams = $this->setAttrValue($data, 0, $productType, 0);
        $settleParams['attr'] = $this->setAttr($data['attr'], 0);

        $product['price'] = $settleParams['data']['price'];
        $product['stock'] = $settleParams['data']['stock'];
        $product['cost'] = $settleParams['data']['cost'];
        $product['ot_price'] = $settleParams['data']['ot_price'];
        $product['product_type'] = $productType;
        foreach ($settleParams['attrValue'] as $k => $value) {
            $_value = [
                'sku'    => $value['sku'],
                'price'  => $value['price'],
                'stock'  => $value['stock'],
                'image'  => $value['image'],
                'weight' => $value['weight'],
                'volume' => $value['volume'],
                'sales'  => $value['sales'],
                'unique' => $value['unique'],
                'bar_code' => $value['bar_code'],
            ];
            $sku[$value['sku']] = $_value;
        }
        $preview_key = 'preview'.$data['mer_id'].$productType.'_'.time();
        unset($settleParams['data'],$settleParams['attrValue']);
        $settleParams['sku'] = $sku;
        $settleParams['attr'] = $this->detailAttr($settleParams['attr'],1);

        if(isset($data['guarantee_template_id'])) {
            $guarantee_id = app()->make(GuaranteeValueRepository::class)->getSearch(['guarantee_template_id' => $data['guarantee_template_id']])->column('guarantee_id');
            $product['guaranteeTemplate'] = app()->make(GuaranteeRepository::class)->getSearch(['status' => 1, 'is_del' => 0])->where('guarantee_id', 'in', $guarantee_id)->select();
        }
        if(isset($data['temp_id'])) {
            $product['temp'] = app()->make(ShippingTemplateRepository::class)->getSearch(['shipping_template_id' => $data['temp_id']])->find();
        }

        $ret = array_merge($product,$settleParams);

        Cache::set($preview_key,$ret);

        return compact('preview_key','ret');
    }


    /**
     * TODO 列表查看预览
     * @param array $data
     * @return array|\think\Model|null
     * @author Qinii
     * @day 7/9/21
     */
    public function getPreview(array $data)
    {
        switch($data['product_type'])
        {
            case 0:
                return $this->apiProductDetail(['product_id' => $data['id']], 0, 0);
                break;
            case 1:
                $ret = $this->apiProductDetail(['product_id' => $data['id']], 1, 0);
                $ret['stop'] = time() + 3600;
                break;
            case 2:
                $make = app()->make(ProductPresellRepository::class);
                $res = $make->getWhere([$make->getPk()=> $data['id']])->toArray();
                $ret = $this->apiProductDetail(['product_id' => $res['product_id']], 2, $data['id'])->toArray();
                $ret['ot_price'] = $ret['price'];
                $ret['start_time'] = $res['start_time'];
                $ret['p_end_time'] = $res['end_time'];
                $ret = array_merge($ret,$res);
                break;
            case 3:
                $make = app()->make(ProductAssistRepository::class);
                $res = $make->getWhere([$make->getPk()=> $data['id']])->toArray();
                $ret = $this->apiProductDetail(['product_id' => $res['product_id']], 3, $data['id'])->toArray();

                $ret = array_merge($ret,$res);
                foreach ($ret['sku'] as $value){
                    $ret['price'] = $value['price'];
                    $ret['stock'] = $value['stock'];
                }
                break;
            case 4:
                $make = app()->make(ProductGroupRepository::class);
                $res = $make->get($data['id'])->toArray();
                $ret = $this->apiProductDetail(['product_id' => $res['product_id']], 4, $data['id'])->toArray();
                $ret['ot_price'] = $ret['price'];
                $ret = array_merge($ret,$res);
                break;
            default:
                break;
        }
        return $ret;
    }

    public function setLabels($id, $data, $merId = 0)
    {
        $where['product_id'] = $id;
        $field = isset($data['sys_labels']) ? 'sys_labels' : 'mer_labels';
        if ($merId) $where['mer_id'] = $merId;
        app()->make(ProductLabelRepository::class)->checkHas($merId,$data[$field]);
        $ret = $this->dao->getWhere($where);

        $activeId = $ret->seckillActive->seckill_active_id ?? 0;

        $spu = ['activity_id' => $activeId, 'product_type' => $ret['product_type'], 'product_id' => $id];
        $ret = app()->make(SpuRepository::class)->getWhere($spu);
        if (!$ret) throw new ValidateException('数据不存在');
        $ret->$field = $data[$field];
        $ret->save();
    }

    public function getAttrValue(int $id, int $merId)
    {
        $data = $this->dao->getWhere(['product_id' => $id, 'mer_id' => $merId]);
        if (!$data) throw new ValidateException('数据不存在');
        return app()->make(ProductAttrValueRepository::class)->getSearch(['product_id' => $id])->select()->append(['is_svip_price']);
    }

    public function checkParams($data,$merId,$id = null)
    {
        if (!$data['pay_limit']) $data['once_max_count'] = 0;
        if ($data['brand_id'] > 0 && !$this->merBrandExists($data['brand_id']))
            throw new ValidateException('品牌不存在');
        if (!$this->CatExists($data['cate_id']))
            throw new ValidateException('平台分类不存在');
        if (isset($data['mer_cate_id']) && !$this->merCatExists($data['mer_cate_id'], $merId))
            throw new ValidateException('不存在的商户分类');
        if ($data['delivery_way'] == 2 && !$this->merShippingExists($merId, $data['temp_id']))
            throw new ValidateException('运费模板不存在');
        if (isset($data['type']) && $data['type'] == 1 && $data['extend']) {
            $key = ['email','text','number','date','time','idCard','mobile','image'];
            if (count($data['extend']) > 10) throw new ValidateException('附加表单不能超过10条');
            $title = [];
            foreach ($data['extend'] as $item) {
                if (empty($item['title']) )
                    throw new ValidateException('表单名称不能为空：'.$item['key']);
                if (in_array($item['title'],$title))
                    throw new ValidateException('表单名称不能重复：'.$item['title']);
                $title[] = $item['title'];
                if (!in_array($item['key'], $key))
                    throw new ValidateException('表单类型错误：'.$item['key']);
                $extend[] = [
                    'title' => $item['title'],
                    'key' => $item['key'] ,
                    'require' => $item['require'],
                ];
            }
        }
        app()->make(ProductLabelRepository::class)->checkHas($merId,$data['mer_labels']);
        $count = app()->make(StoreCategoryRepository::class)->getWhereCount(['store_category_id' => $data['cate_id'],'is_show' => 1]);
        if (!$count) throw new ValidateException('平台分类不存在或不可用');
        app()->make(StoreProductValidate::class)->check($data);
        $data['extend'] = $extend ?? [];
        //单次限购
        return $data;
    }


    public function isFormatAttr(array $data, int $productId, $productType = 0)
    {
        $sukValue = [];
        if ($productId) {
            $make =  app()->make(ProductAttrValueRepository::class);
            $_sukValue = $make->search(['product_id' => $productId])->with(['productCdkey'])->select()->toArray();
            foreach ($_sukValue as $value) {
                $value['cdkey'] = $value['productCdkey'] ?? [];
                unset($value['productCdkey']);
                $sukValue[$value['sku']] = $value;
            }
        }
        $valueNew = [];
        $count = 0;
        $attr = attr_format($data)[1];
        foreach ($attr as $item) {
            $detail = $item['detail'];
//            sort($item['detail'], SORT_STRING);
            $suk = implode(',', $item['detail']);
            foreach (array_keys($detail) as $k => $title) {
                if ($title == '') continue;
                $header[$k]['title'] = $title;
                $header[$k]['align'] = 'center';
                $header[$k]['minWidth'] = 120;
            }
            foreach (array_values($detail) as $k => $v) {
                if ($v == '') continue;
                $valueNew[$count]['value' . ($k + 1)] = $v;
                $header[$k]['key'] = 'value' . ($k + 1);
            }

            $valueNew[$count]['sku']    = $suk;
            $valueNew[$count]['detail'] = $detail;
            $valueNew[$count]['image']  = $sukValue[$suk]['image'] ?? '';
            $valueNew[$count]['price']  = $sukValue[$suk]['price'] ?? 0;
            $valueNew[$count]['cost']   = $sukValue[$suk]['cost'] ?? 0;
            $valueNew[$count]['stock']      = $sukValue[$suk]['stock'] ?? 0;
            $valueNew[$count]['bar_code']   = $sukValue[$suk]['bar_code'] ?? '';
            $valueNew[$count]['weight']     = $sukValue[$suk]['weight'] ?? 0;
            $valueNew[$count]['volume']     = $sukValue[$suk]['volume'] ?? 0;
            $valueNew[$count]['brokerage']  = $sukValue[$suk]['brokerage'] ?? 0;
            $valueNew[$count]['ot_price']   = $sukValue[$suk]['ot_price'] ?? 0;
            $valueNew[$count]['svip_price']   = $sukValue[$suk]['svip_price'] ?? 0;
            $valueNew[$count]['cdkey']      = $sukValue[$suk]['cdkey'] ?? [];
            $valueNew[$count]['brokerage_two'] = $sukValue[$suk]['brokerage_two'] ?? 0;

            $count++;
        }
        $header[] = ['title' => '图片', 'slot' => 'image', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '售价', 'slot' => 'price', 'align' => 'center', 'minWidth' => 95];
        $header[] = ['title' => '成本价', 'slot' => 'cost', 'align' => 'center', 'minWidth' => 95];
        $header[] = ['title' => '原价', 'slot' => 'ot_price', 'align' => 'center', 'minWidth' => 95];
        $header[] = ['title' => '库存', 'slot' => 'stock', 'align' => 'center', 'minWidth' => 95];
        $header[] = ['title' => '商品编号', 'slot' => 'bar_code', 'align' => 'center', 'minWidth' => 120];
        $header[] = ['title' => '重量(KG)', 'slot' => 'weight', 'align' => 'center', 'minWidth' => 95];
        $header[] = ['title' => '体积(m³)', 'slot' => 'volume', 'align' => 'center', 'minWidth' => 95];
        $header[] = ['title' => '操作', 'slot' => 'action', 'align' => 'center', 'minWidth' => 70];
       return ['attr' => $data, 'value' => $valueNew, 'header' => $header];
    }
}
