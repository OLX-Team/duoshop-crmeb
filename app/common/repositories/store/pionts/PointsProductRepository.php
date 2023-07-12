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


namespace app\common\repositories\store\pionts;

use app\common\model\user\User;
use app\common\repositories\community\CommunityRepository;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\GuaranteeRepository;
use app\common\repositories\store\GuaranteeTemplateRepository;
use app\common\repositories\store\GuaranteeValueRepository;
use app\common\repositories\store\order\StoreCartRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\parameter\ParameterValueRepository;
use app\common\repositories\store\product\ProductAssistSkuRepository;
use app\common\repositories\store\product\ProductAttrRepository;
use app\common\repositories\store\product\ProductAttrValueRepository;
use app\common\repositories\store\product\ProductCateRepository;
use app\common\repositories\store\product\ProductContentRepository;
use app\common\repositories\store\product\ProductGroupSkuRepository;
use app\common\repositories\store\product\ProductPresellSkuRepository;
use app\common\repositories\store\product\ProductReplyRepository;
use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\store\StoreActivityRepository;
use app\common\repositories\store\StoreSeckillActiveRepository;
use app\common\repositories\store\StoreSeckillTimeRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserRelationRepository;
use app\common\repositories\user\UserVisitRepository;
use app\validate\merchant\StoreProductValidate;
use crmeb\jobs\ChangeSpuStatusJob;
use crmeb\jobs\SendSmsJob;
use crmeb\services\QrcodeService;
use crmeb\services\RedisCacheService;
use crmeb\services\SwooleTaskService;
use FormBuilder\Factory\Elm;
use think\contract\Arrayable;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use app\common\repositories\BaseRepository;
use app\common\dao\store\product\ProductDao as dao;
use app\common\repositories\store\shipping\ShippingTemplateRepository;
use think\facade\Queue;

class PointsProductRepository extends BaseRepository
{

    protected $dao;
    const CREATE_PARAMS = [
        "image", "slider_image", "store_name", "store_info", "keyword", "bar_code", "guarantee_template_id", "cate_id", "unit_name", "sort" , "is_show", 'integral_rate', "video_link", "content", "spec_type", "attr",  'delivery_way', 'delivery_free','param_temp_id',
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
    ];
    protected $admin_filed = 'Product.product_id,Product.mer_id,brand_id,spec_type,unit_name,mer_status,rate,reply_count,store_info,cate_id,Product.image,slider_image,Product.store_name,Product.keyword,Product.sort,U.rank,Product.is_show,Product.sales,Product.price,extension_type,refusal,cost,U.ot_price,stock,is_gift_bag,Product.care_count,Product.status,is_used,Product.create_time,Product.product_type,old_product_id,star,ficti,integral_total,integral_price_total,sys_labels,param_temp_id';
    protected $filed = 'Product.product_id,Product.mer_id,brand_id,unit_name,spec_type,mer_status,rate,reply_count,store_info,cate_id,Product.image,slider_image,Product.store_name,Product.keyword,Product.sort,Product.is_show,Product.sales,Product.price,extension_type,refusal,cost,U.ot_price,stock,is_gift_bag,Product.care_count,Product.status,is_used,Product.create_time,Product.product_type,old_product_id,integral_total,integral_price_total,mer_labels,Product.is_good,Product.is_del,type,param_temp_id,mer_svip_status,svip_price,svip_price_type';

    //积分商品
    const PRODUCT_TYPE_POINTS = 20;
    /**
     * ProductRepository constructor.
     * @param dao $dao
     */
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }

    public function merExists(?int $merId, int $id)
    {
        return $this->dao->merFieldExists($merId, $this->getPk(), $id);
    }


    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param array $data
     */
    public function create(array $data, int $productType = 0)
    {
        if (!$data['spec_type']) {
            $data['attr'] = [];
            if (count($data['attrValue']) > 1)
                throw new ValidateException('单规格商品属性错误');
        }
        $content = ['content' => $data['content'] , 'type' => 0];
        $product = $this->setProduct($data);
        return Db::transaction(function () use ($data, $productType,$content,$product) {
            $activity_id = 0;
            $result = $this->dao->create($product);
            $settleParams = $this->setAttrValue($data, $result->product_id, $productType, 0);
            $settleParams['attr'] = $this->setAttr($data['attr'], $result->product_id);
            $this->save($result->product_id, $settleParams, $content,$product,$productType);
            $product['price'] = $settleParams['data']['price'];
            $product['ot_price'] = $settleParams['data']['ot_price'];
            app()->make(SpuRepository::class)->create($product, $result->product_id, $activity_id, $productType);
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
            if (count($data['attrValue']) > 1)
                throw new ValidateException('单规格商品属性错误');
        }
        $spuData = $product = $this->setProduct($data);
        $settleParams = $this->setAttrValue($data, $id, $productType, 1);
        $settleParams['attr'] = $this->setAttr($data['attr'], $id);
        $content = ['content' => $data['content'] , 'type' => $conType];
        $spuData['price'] = $settleParams['data']['price'];
        $spuData['ot_price'] = $settleParams['data']['ot_price'];
        $SpuRepository = app()->make(SpuRepository::class);
        Db::transaction(function () use ($id, $data, $productType, $settleParams,$content,$product,$spuData,$merId,$SpuRepository) {
            $this->save($id, $settleParams, $content, $product, $productType);
            $SpuRepository->baseUpdate($spuData, $id, 0, $productType);
            $SpuRepository->changeStatus($id, $productType);
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
        (app()->make(SpuRepository::class))->delProduct($id);
        $this->dao->destory($id);
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
        $ProductAttrRepository = app()->make(ProductAttrRepository::class);
        $ProductAttrValueRepository = app()->make(ProductAttrValueRepository::class);

        $ProductAttrRepository->clearAttr($id);
        $ProductAttrValueRepository->clearAttr($id);

        if (isset($settleParams['attr']))
            $ProductAttrRepository->insert($settleParams['attr']);

        if (isset($settleParams['attrValue'])) {
            $arr = array_chunk($settleParams['attrValue'], 30);
            foreach ($arr as $item){
                $ProductAttrValueRepository->insertAll($item);
            }
        }
        if ($content){
            app()->make(ProductContentRepository::class)->clearAttr($id,$content['type']);
            $this->dao->createContent($id, $content);
        }


        if (isset($settleParams['data'])) {
            $data['price'] = $settleParams['data']['price'];
            $data['ot_price'] = $settleParams['data']['ot_price'];
            $data['cost'] = $settleParams['data']['cost'];
            $data['stock'] = $settleParams['data']['stock'];
        }
        $res = $this->dao->update($id, $data);
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
            'is_used' => $data['is_used'] ?? ((isset($data['status']) && $data['status'] == 1) ? 1 : 0),
            'is_good' => $data['is_good'] ?? 0,
            'is_hot' => $data['is_hot'] ?? 0,
            'video_link' => $data['video_link']??'',
            'temp_id' => $data['delivery_free'] ? 0 : ($data['temp_id'] ?? 0),
            'extension_type' => $data['extension_type']??0,
            'spec_type' => $data['spec_type'] ?? 0,
            'status' => $data['status'] ?? 0,
            'guarantee_template_id' => $data['guarantee_template_id']??0,
            'is_gift_bag' => 0,
            'integral_rate' =>  $integral_rate ?? 0,
            'delivery_way' => implode(',',$data['delivery_way']),
            'delivery_free' => $data['delivery_free'] ?? 0,
            'once_min_count' => $data['once_min_count'] ?? 0,
            'once_max_count' => $data['once_max_count'] ?? 0,
            'pay_limit' => $data['pay_limit'] ?? 0,
            'svip_price_type' => $data['svip_price_type'] ?? 0,
        ];
        if (isset($data['product_type']))
            $result['product_type'] = $data['product_type'];
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
        $price = $stock = $ot_price = $cost = 0;
        try {
            foreach ($data['attrValue'] as $value) {
                $_svip_price = 0;
                $sku = '';
                if (isset($value['detail']) && !empty($value['detail']) && is_array($value['detail'])) {
                    $sku = implode(',', $value['detail']);
                }

                $cost   = !$cost ? $value['cost'] : (($cost > $value['cost']) ?$cost: $value['cost']);
                $price  = !$price ? $value['price'] : (($price > $value['price']) ? $value['price'] : $price);
                $ot_price = !$ot_price ? $value['ot_price'] : (($ot_price < $value['ot_price']) ? $ot_price : $value['ot_price']);

                $unique = $this->setUnique($productId, $sku, $productType);
                $result['attrValue'][] = [
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
                    "type" => self::PRODUCT_TYPE_POINTS,
                    "sku" => $sku,
                    "unique" => $unique,
                    'sales' => $isUpdate ? ($oldSku[$sku]['sales'] ?? 0) : 0,
                    'svip_price' => $_svip_price,
                ];
                $stock = $stock + intval($value['stock']);
            }
            $result['data'] = [
                'price' => $price ,
                'stock' => $stock,
                'ot_price'  => $ot_price,
                'cost'      => $cost,
                'svip_price' => 0,
            ];
        } catch (\Exception $exception) {
            throw new ValidateException('规格错误 ：'.$exception->getMessage());
        }
        return $result;
    }

    /**
     * TODO 单商品sku
     * @param $data
     * @param $userInfo
     * @return array
     * @author Qinii
     * @day 2020-08-05
     */
    public function detailAttrValue($data)
    {
        $sku = [];
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
                'ot_price' => (int)$value['ot_price'],
            ];
            $sku[$value['sku']] = $_value;
        }
        return $sku;
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
    public function detail(int $id)
    {
        $with = [
            'attr',
            'attrValue',
            'storeCategory',
            'content',
        ];
        $data = $this->dao->geTrashedtProduct($id)->with($with)->find();
        if (!$data) {
            throw new ValidateException('数据不存在');
        }
        $spu = app()->make(SpuRepository::class)->getSearch([
            'activity_id' => 0,
            'product_type' => $data['product_type'],
            'product_id' => $id
        ])->find();

        $data['star'] = $spu['star'] ?? '';
        foreach ($data['attr'] as $k => $v) {
            $data['attr'][$k] = [
                'value' => $v['attr_name'],
                'detail' => $v['attr_values']
            ];
        }
        foreach($data['attrValue'] as $key => $item) {
            $sku = explode(',', $item['sku']);
            $item['old_stock'] = $old_stock ?? $item['stock'];
            foreach ($sku as $k => $v) {
                $item['value' . $k] = $v;
            }
            $data['attrValue'][$key] = $item;
        }
        $content = $data['content']['content'] ?? '';
        unset($data['content']);
        $data['content'] = $content;
        return $data;
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
        $where['product_type'] = self::PRODUCT_TYPE_POINTS;
        $query = $this->dao->search($merId, $where)->with([
            'merCateId.category',
            'storeCategory',
            'brand',
            'merchant',
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('Product.sort DESC,Product.create_time DESC')->select();
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
    public function getApiSearch(array $where, int $page, int $limit)
    {
        $where['product_type'] = self::PRODUCT_TYPE_POINTS;
        $where['spu_status'] = 1;
        $where['mer_status'] = 1;
        $SpuRepository = app()->make(SpuRepository::class);
        $query = $SpuRepository->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * TODO 上下架 / 显示
     * @param $id
     * @param $status
     * @author Qinii
     * @day 2022/11/12
     */
    public function switchShow($id, $status, $field)
    {
        $this->dao->update($id,[$field => $status]);
        app()->make(SpuRepository::class)->changeStatus($id,self::PRODUCT_TYPE_POINTS);
    }

    public function batchSwitchShow($id, $status, $field, $merId = 0)
    {
        $where['product_id'] = $id;
        if ($merId) $where['mer_id'] = $merId;
        $products = $this->dao->getSearch([])->where('product_id','in', $id)->select();
        if (!$products)
            throw new ValidateException('数据不存在');
        $this->dao->updates($id,[$field => $status]);
        Queue::push(ChangeSpuStatusJob::class,['id' => $id,'product_type'=> self::PRODUCT_TYPE_POINTS]);
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


    public function updateSort(int $id, ?int $merId, array $data)
    {
        $where[$this->dao->getPk()] = $id;
        if ($merId) $where['mer_id'] = $merId;
        $ret = $this->dao->getWhere($where);
        if (!$ret) throw new  ValidateException('数据不存在');
        $this->dao->update($ret['product_id'], $data);
        $make = app()->make(SpuRepository::class);
        $activityId = $ret['product_type'] ? $ret->seckillActive->seckill_active_id : 0;
        return $make->updateSort($ret['product_id'], $activityId, $ret['product_type'], $data);
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

    public function show($id)
    {

        $field = 'is_show,product_id,mer_id,image,slider_image,store_name,store_info,unit_name,price,cost,ot_price,stock,sales,video_link,product_type,extension_type,old_product_id,rate,guarantee_template_id,temp_id,once_max_count,pay_limit,once_min_count,integral_rate,delivery_way,delivery_free,type,cate_id,svip_price_type,svip_price,mer_svip_status';
        $with = [
            'attr',
            'content' => function($query) {
                $query->order('type ASC');
            },
            'attrValue',
        ];
        $res = $this->dao->getWhere(['product_id' => $id], $field, $with);
        if (!$res)  return [];
        $attr = $this->detailAttr($res['attr']);
        $attrValue =$res['attrValue'];
        $sku  = $this->detailAttrValue($attrValue, null, 20, 0);
        $res['isRelation'] = $isRelation ?? false;
        unset($res['attr'], $res['attrValue']);
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

        return $res;
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

    public function cartCheck(array $data, $userInfo)
    {
        $cart = null;
        $where = $this->dao->productShow();
        $where['product_id'] = $data['product_id'];
        $where['product_type'] = self::PRODUCT_TYPE_POINTS;
        unset($where['is_gift_bag']);
        $product = $this->dao->search(null, $where)->find();

        if (!$product) throw new ValidateException('商品已下架');
        if (!$data['is_new']) throw new ValidateException('积分商品不可加入购物车');
        $value_make = app()->make(ProductAttrValueRepository::class);
        $sku = $value_make->getOptionByUnique($data['product_attr_unique']);
        if (!$sku) throw new ValidateException('SKU不存在');
        if ($sku['stock'] < $data['cart_num'] ) throw new ValidateException('库存不足');
        return compact('product', 'sku', 'cart');
    }
}
