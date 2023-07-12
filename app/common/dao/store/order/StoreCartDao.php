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


namespace app\common\dao\store\order;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\store\order\StoreCart;
use app\common\model\user\UserAddress;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\Relation;

class StoreCartDao extends BaseDao
{

    protected function getModel(): string
    {
        return StoreCart::class;
    }

    public function test()
    {
        return StoreCart::getDB()->with(['product' => function (Relation $query) {
            $query->where('store_name', '儿童节礼物');
        }])->select();
    }

    /**
     * @param array $ids
     * @param $uid
     * @param int|null $merId
     * @return array
     * @author xaboy
     * @day 2020/6/5
     */
    public function validIntersection(array $ids, $uid, int $merId = null): array
    {
        return StoreCart::getDB()->whereIn('cart_id', $ids)
            ->when($merId, function ($query, $merId) {
                $query->where('mer_id', $merId);
            })
            ->where('is_del', 0)->where('is_fail', 0)->where('is_pay', 0)->where('uid', $uid)->column('cart_id');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/6/1
     * @param int $uid
     * @return mixed
     */
    public function getAll(int $uid)
    {
        $query = ($this->getModel())::where(['uid' => $uid, 'is_del' => 0, 'is_new' => 0, 'is_pay' => 0])
            ->with([
                'product' => function ($query) {
                    $query->field('product_id,image,store_name,is_show,status,is_del,unit_name,price,mer_status,is_used,product_type,once_max_count,once_min_count,pay_limit,mer_svip_status,svip_price_type');
                },
                'productAttr' => function ($query) {
                    $query->field('product_id,stock,price,unique,sku,image,svip_price');
                },
                'merchant' => function ($query) {
                    $query->field('mer_id,mer_name,mer_state,mer_avatar,is_trader,type_id')->with(['type_name']);
                }
            ])->select();

        return $query;
    }

    public function cartIbByData(array $ids, int $uid, ?UserAddress $address)
    {
        return StoreCart::getDb()->where('uid', $uid)->with([
            'product' => function (Relation $query) use ($address) {
                $query->field('product_id,cate_id,image,store_name,is_show,status,is_del,unit_name,price,mer_status,temp_id,give_coupon_ids,is_gift_bag,is_used,product_type,old_product_id,integral_rate,delivery_way,delivery_free,type,extend,pay_limit,once_max_count,once_min_count,mer_svip_status,svip_price_type,refund_switch');
                if ($address) {
                    $cityIds = array_filter([$address->province_id, $address->city_id, $address->district_id, $address->street_id]);
                    $query->with([
                        'temp' => [
                            'region' => function (Relation $query) use ($cityIds) {
                                $query->where(function ($query) use ($cityIds) {
                                    foreach ($cityIds as $v) {
                                        $query->whereOr('city_id', 'like', "%/{$v}/%");
                                    }
                                    $query->whereOr('city_id', '0');
                                })->order('shipping_template_region_id DESC')->withLimit(1);
                            },
                            'undelives' => function ($query) use ($cityIds) {
                                foreach ($cityIds as $v) {
                                    $query->whereOr('city_id', 'like', "%/{$v}/%");
                                }
                            },
                            'free' => function (Relation $query) use ($cityIds) {
                            foreach ($cityIds as $v) {
                                $query->whereOr('city_id', 'like', "%/{$v}/%");
                            }
                            $query->order('shipping_template_free_id DESC')->withLimit(1);
                    }]]);
                }
            },
            'productAttr' => function (Relation $query) {
                $query->field('value_id,image,extension_one,extension_two,product_id,stock,price,unique,sku,volume,weight,ot_price,cost,svip_price')
                    ->append(['bc_extension_one', 'bc_extension_two']);
            },
            'merchant' => function (Relation $query) use ($uid) {
                $query->field('mer_id,mer_name,mer_state,mer_avatar,delivery_way,commission_rate,category_id')
                    ->with([
                    'coupon' => function ($query) use ($uid) {
                        $query->where('uid', $uid);
                    },
                    'config' => function ($query) {
                        $query->whereIn('config_key', ['mer_integral_status', 'mer_integral_rate', 'mer_store_stock', 'mer_take_status', 'mer_take_name', 'mer_take_phone', 'mer_take_address', 'mer_take_location', 'mer_take_day', 'mer_take_time']);
                    },
                    'merchantCategory'
                    ]);
        }])->whereIn('cart_id', $ids)->order('product_type DESC,cart_id DESC')->select();
    }

    /**
     * @param array $cartIds
     * @param int $uid
     * @author Qinii
     */
    public function batchDelete(array $cartIds, int $uid)
    {
        return ($this->getModel()::getDB())->where('uid', $uid)->whereIn('cart_id', $cartIds)->delete();
    }

    /**
     * @param int $uid
     * @return mixed
     * @author Qinii
     */
    public function getCartCount(int $uid)
    {
        $data = ($this->getModel()::getDB())->where(['uid' => $uid, 'is_del' => 0, 'is_new' => 0, 'is_pay' => 0])->field('SUM(cart_num) as count')->select();
        $data[0]['count'] = $data[0]['count'] ? $data[0]['count'] : 0;
        return $data;
    }

    /**
     * @param $source
     * @param array|null $ids
     * @author xaboy
     * @day 2020/8/31
     */
    public function getSourcePayInfo($source, ?array $ids = null)
    {
        return StoreCart::getDB()->alias('A')->where('A.source', $source)->where('A.is_pay', 1)->when($ids, function ($query, $ids) {
            $query->whereIn('A.source_id', $ids);
        })->leftJoin('StoreOrderProduct B', 'A.cart_id = B.cart_id')
            ->field('sum(B.product_num) as pay_num,sum(B.product_price) as pay_price,A.source_id')->group('A.source_id')->select();
    }
}
