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

use app\common\dao\store\product\StoreDiscountDao;
use app\common\repositories\BaseRepository;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * @mixin StoreDiscountDao
 */
class StoreDiscountRepository extends BaseRepository
{
    public function __construct(StoreDiscountDao $dao)
    {
        $this->dao = $dao;
    }

    public function getApilist($where)
    {
        $query = $this->dao->getSearch($where)
                ->with([
                    'discountsProduct' => [
                        'product' => function($query){
                            $query->where('status',1)->where('is_show',1)->where('mer_status',1)->where('is_used',1)->with([
                                'attr',
                                'attrValue',
                            ]);
                        },
                        'productSku' => function($query)  {
                            $query->where('active_type', 10);
                        },
                    ]
                ])->order('sort DESC,create_time DESC');
        $data = $query->select();
        $list = [];
        if ($data) {
            foreach ($data->toArray() as $item) {
                $discountsProduct = $item['discountsProduct'];
                unset($item['discountsProduct']);
                $res = activeProductSku($discountsProduct, 'discounts');
                $item['count'] = count($res['data']);
                $count = count(explode(',',$item['product_ids']));
                if ((!$item['type'] && $count == $item['count']) || ($item['type'] && $count > 0)) {
                    $item['max_price'] = $res['price'];
                    $item['discountsProduct'] = $res['data'];
                    $list[] = $item;
                }
            }
        }
        $count = count($list);
        return compact('count', 'list');
    }

    public function getMerlist(array $where, int $page, int $limit)
    {
        $where['is_del'] = 0;
        $query = $this->dao->getSearch($where)->with(['discountsProduct'])->order('sort DESC,create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->each(function ($item){
            if ($item['is_time']) {
                $start_time = date('Y-m-d  H:i:s', $item['start_time']);
                $stop_time = date('Y-m-d  H:i:s', $item['stop_time']);
                unset($item['start_time'],$item['stop_time']);
                $item['start_time'] = $start_time;
                $item['stop_time'] = $stop_time;
            }
        });
        return compact('count', 'list');
    }

    public function getAdminlist(array $where, int $page, int $limit)
    {
        $where['is_del'] = 0;
        $query = $this->dao->getSearch($where)->with(['discountsProduct','merchant'])->order('sort DESC,create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->each(function ($item){
            if ($item['is_time']) {
                $start_time = date('Y-m-d  H:i:s', $item['start_time']);
                $stop_time = date('Y-m-d  H:i:s', $item['stop_time']);
                unset($item['start_time'],$item['stop_time']);
                $item['start_time'] = $start_time;
                $item['stop_time'] = $stop_time;
            }
        });
        return compact('count', 'list');
    }

    public function save($data)
    {
        $discountsData['title'] = $data['title'];
        $discountsData['image'] = $data['image'];
        $discountsData['type'] = $data['type'];
        $discountsData['is_limit'] = $data['is_limit'];
        $discountsData['limit_num'] = $data['is_limit'] ? $data['limit_num'] : 0;
        $discountsData['is_time'] = $data['is_time'];
        $discountsData['start_time'] = $data['is_time'] ? strtotime($data['time'][0]) : 0;
        $discountsData['stop_time'] = $data['is_time'] ? strtotime($data['time'][1]) : 0;
        $discountsData['sort'] = $data['sort'];
        $discountsData['free_shipping'] = $data['free_shipping'];
        $discountsData['status'] = $data['status'];
        $discountsData['is_show'] = $data['is_show'];
        $discountsData['mer_id'] = $data['mer_id'];
        $product_ids = [];
        $productRepository = app()->make(ProductRepository::class);

        foreach ($data['products'] as $product) {
            $productData = $productRepository->getSearch([])
                ->where('mer_id', $data['mer_id'])
                ->where('product_id', $product['product_id'])
                ->where('status', 1)
                ->find();
            if (!$productData) throw new ValidateException('商品「 '.$product['store_name'].' 」不存在或未审核');
            if ($productData['is_gift_bag']) throw new ValidateException('商品「 '.$product['store_name'].' 」分销礼包不能参与');
            if (in_array($product['product_id'], $product_ids))
                throw new ValidateException('商品「 '.$product['store_name'].' 」重复选择');

            if ($product['type']) {
                $product_ids = [];
                $product_ids[] = $product['product_id'];
                break;
            } else {
                $product_ids[] = $product['product_id'];
            }
        }

        $discountsData['product_ids'] = implode(',', $product_ids);
        return Db::transaction(function () use($data, $discountsData){
            if (isset($data['discount_id'])) {
                $discountsId = $data['discount_id'];
                $this->dao->update($discountsId, $discountsData);
                app()->make(StoreDiscountProductRepository::class)->clear($discountsId,'discount_id');
                app()->make(ProductSkuRepository::class)->clear($discountsId, ProductSkuRepository::ACTIVE_TYPE_DISCOUNTS);
            } else {
                $res = $this->dao->create($discountsData);
                $discountsId = $res['discount_id'];
            }
            return $this->saveProduct($discountsId, $data['products'], $data['mer_id']);
        });
    }


    /**
     * TODO 添加套餐商品
     * @param int $discountsId
     * @param array $data
     * @param int $merId
     * @author Qinii
     * @day 12/31/21
     */
    public function saveProduct(int $discountsId, array $data, int $merId)
    {
        $storeDiscountsProductsServices = app()->make(StoreDiscountProductRepository::class);
        $productSkuRepository = app()->make(ProductSkuRepository::class);
        foreach ($data as $item) {
            $productData = [];
            $productData['discount_id'] = $discountsId;
            $productData['product_id'] = $item['product_id'];
            $productData['store_name'] = $item['store_name'];
            $productData['image'] = $item['image'];
            $productData['type'] = $item['type'];
            $productData['temp_id'] = $item['temp_id'];
            $productData['mer_id'] = $merId;
            $discountProduct = $storeDiscountsProductsServices->create($productData);
            $productSkuRepository->save($discountsId, $item['product_id'], $item['items'],$discountProduct->discount_product_id);
        }
        return ;
    }

    /**
     * TODO 详情
     * @param int $id
     * @param int $merId
     * @return array|\think\Model|null
     * @author Qinii
     * @day 12/31/21
     */
    public function detail(int $id, int $merId)
    {
        $where[$this->dao->getPk()] = $id;

        if ($merId) {
            $where['mer_id'] = $merId;
        }
        $res = $this->dao->getSearch($where)
            ->with([
                'discountsProduct' => function($query){
                    $query->with([
                        'product.attrValue',
                        'productSku' => function($query) {
                            $query->where('active_type', 10);
                        }
                    ]);
                }
            ])
            ->find();
        if (!$res) throw new ValidateException('数据不存在');
        $res->append(['time']);
        $ret = activeProductSku($res['discountsProduct']);
        $res['discountsProduct'] = $ret['data'];
        return $res;
    }

    public function check($discountId, $products, $userInfo)
    {
        $discountData = $this->dao->get($discountId);
        if (!$discountData) throw new ValidateException('套餐活动已下架');
        if ($discountData['status'] !== 1 || $discountData['is_show'] !== 1 || $discountData['is_del'])
            throw new ValidateException('套餐活动已下架');
        if ($discountData['is_limit'] && $discountData['limit_num'] < 1) {
            throw new ValidateException('套餐已售罄');
        }
        if ($discountData['is_time']) {
            if ($discountData['start_time'] > time()) throw new ValidateException('套餐活动未开启');
            if ($discountData['stop_time'] < time()) throw new ValidateException('套餐活动已结束');
        }
        $make = app()->make(StoreDiscountProductRepository::class);
        $productSkuRepository = app()->make(ProductSkuRepository::class);
        $productId = [];
        $cartData = [];

        foreach ($products as $item) {
            if (in_array($item['product_id'], $productId))
                throw new ValidateException('套餐商品不能重复');
            if (!$item['product_id'])
                throw new ValidateException('商品ID不能为空');
            if (!$item['product_attr_unique'])
                throw new ValidateException('ID: '. $item['product_id'] .',商品SKU不能为空');
            if ($item['cart_num'] != 1)
                throw new ValidateException('套餐商品每单只能购买1件');
            if ($item['cart_num'] <= 0)
                throw new ValidateException('购买数量有误');

            $ret = $make->getWhere(['discount_id' => $discountId, 'product_id' => $item['product_id']]);
            if (!$ret) throw new ValidateException('商品ID:'.$item['product_id'].',不在套餐内');
            $sku = $productSkuRepository->getWhere(
                [
                    'unique' => $item['product_attr_unique'],
                    'active_product_id' => $ret['discount_product_id'],
                ],
                '*',
                ['attrValue']
            );

            if (!$sku)
                throw new ValidateException('商品ID:'.$item['product_id'].'的SKU不在套餐内');
            if (!$sku['attrValue']['stock'])
                throw new ValidateException('商品ID:'.$item['product_id'].'的库存不足');
            $productId[] = $item['product_id'];

            $item['uid'] = $userInfo->uid;
            $item['mer_id'] = $discountData['mer_id'];
            $item['product_type'] = 10;
            $item['source'] = 10;
            $item['source_id'] = $discountId;

            $cartData[] = $item;
        }

        if ($discountData['type'] == 1){
            if (!in_array($discountData['product_ids'], $productId))
                throw new ValidateException('此套餐必须包含主商品');
        }
        return $cartData;
    }

}
