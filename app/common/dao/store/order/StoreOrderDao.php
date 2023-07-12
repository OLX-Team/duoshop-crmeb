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
use app\common\model\store\order\StoreOrder;
use app\common\model\store\order\StoreOrderProduct;
use app\common\model\store\order\StoreOrderStatus;
use app\common\model\user\User;
use app\common\repositories\store\order\StoreOrderStatusRepository;
use app\common\repositories\store\product\ProductAssistSetRepository;
use app\common\repositories\store\product\ProductGroupBuyingRepository;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\Model;

/**
 * Class StoreOrderDao
 * @package app\common\dao\store\order
 * @author xaboy
 * @day 2020/6/8
 */
class StoreOrderDao extends BaseDao
{
    //订单状态（0：待发货；1：待收货；2：待评价；3：已完成； 9: 拼团中 10:  待付尾款 11:尾款超时未付 -1：已退款）
    const ORDER_STATUS_BE_SHIPPED = 0;
    const ORDER_STATUS_BE_RECEIVE = 1;
    const ORDER_STATUS_REPLY = 2;
    const ORDER_STATUS_SUCCESS = 3;
    const ORDER_STATUS_SPELL = 9;
    const ORDER_STATUS_TAIL = 10;
    const ORDER_STATUS_TAIL_FAIL = 11;
    const ORDER_STATUS_REFUND = -1;


    /**
     * @return string
     * @author xaboy
     * @day 2020/6/8
     */
    protected function getModel(): string
    {
        return StoreOrder::class;
    }

    /**
     * @param array $where
     * @param int $sysDel
     * @return BaseQuery
     * @author xaboyCRMEB
     * @day 2020/6/16
     */
    public function search(array $where, $sysDel = 0)
    {
        $query = StoreOrder::hasWhere('merchant', function ($query) use ($where) {
            if (isset($where['is_trader']) && $where['is_trader'] !== '') {
                $query->where('is_trader', $where['is_trader']);
            }
            $query->where('is_del',0);
        });
        $query->where('StoreOrder.activity_type','<>',20);
        $query->when(($sysDel !== null), function ($query) use ($sysDel) {
            $query->where('is_system_del', $sysDel);
        });
        $query->when(isset($where['order_type']) && $where['order_type'] >= 0 && $where['order_type'] !== '', function ($query) use ($where) {
                if ($where['order_type'] == 2) {
                    $query->where('is_virtual', 1);
                } else if($where['order_type'] == 3) { // 卡密商品
                    $query->where('is_virtual',2);
                } else {
                    $query->where('order_type', $where['order_type']);
                }
            });
        $query->when(isset($where['activity_type']) && $where['activity_type'] !== '', function ($query) use ($where) {
                $query->where('StoreOrder.activity_type', $where['activity_type']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                switch ($where['status']) {
                    case 0 :
                        $query->whereIn('StoreOrder.status', [0, 9]);
                        break;
                    case -2 :
                        $query->where('paid', 1)->whereNotIn('StoreOrder.status', [10, 11]);
                        break;
                    case 10 :
                        $query->where('paid', 1)->whereIn('StoreOrder.status', [10, 11]);
                        break;
                    default:
                        $query->where('StoreOrder.status', $where['status']);
                        break;
                }
            })
            ->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
                $query->where('uid', $where['uid']);
            })
            ->when(isset($where['is_user']) && $where['is_user'] !== '', function ($query) use ($where) {
                $query->where(function($query) {
                    $query->where('order_type',0)->whereOr(function($query){
                        $query->where('order_type',1)->where('main_id',0);
                    });
                });
            })
            //待核销订单
            ->when(isset($where['is_verify']) && $where['is_verify'], function ($query) use ($where) {
                $query->where('StoreOrder.order_type', 1)->where('StoreOrder.status',0);
            })
            ->when(isset($where['pay_type']) && $where['pay_type'] !== '', function ($query) use ($where) {
                $query->where('StoreOrder.pay_type', $where['pay_type']);
            })
            ->when(isset($where['order_ids']) && $where['order_ids'] !== '', function ($query) use ($where) {
                $query->whereIn('order_id', $where['order_ids']);
            })
            ->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use ($where) {
                $query->where('order_id', $where['order_id']);
            })
            ->when(isset($where['take_order']) && $where['take_order'] != '', function ($query) use ($where) {
                $query->where('order_type', 1)->whereNotNull('verify_time');
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
                $query->where('StoreOrder.mer_id', $where['mer_id']);
            })
            ->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date'], 'StoreOrder.create_time');
            })
            ->when(isset($where['verify_date']) && $where['verify_date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['verify_date'], 'verify_time');
            })
            ->when(isset($where['order_sn']) && $where['order_sn'] !== '', function ($query) use ($where) {
                $query->where('order_sn', 'like', '%' . $where['order_sn'] . '%');
            })
            ->when(isset($where['paid']) && $where['paid'] !== '', function ($query) use ($where) {
                $query->where('StoreOrder.paid', $where['paid']);
            })
            ->when(isset($where['is_del']) && $where['is_del'] !== '', function ($query) use ($where) {
                $query->where('StoreOrder.is_del', $where['is_del']);
            })
            ->when(isset($where['service_id']) && $where['service_id'] !== '', function ($query) use ($where) {
                $query->where('service_id', $where['service_id']);
            })
            ->when(isset($where['username']) && $where['username'] !== '', function ($query) use ($where) {
                $query->join('User U','StoreOrder.uid = U.uid')
                    ->where(function($query) use($where) {
                       $query->where('nickname', 'like', "%{$where['username']}%")
                           ->whereOr('phone', 'like', "%{$where['username']}%")
                           ->whereOr('StoreOrder.user_phone', 'like', "%{$where['username']}%");
                    });
            })
            ->when(isset($where['store_name']) && $where['store_name'] !== '', function ($query) use ($where) {
                $orderId = StoreOrderProduct::alias('op')
                    ->join('storeProduct sp','op.product_id = sp.product_id')
                    ->whereLike('store_name',"%{$where['store_name']}%")
                    ->when((isset($where['sp.mer_id']) && $where['mer_id'] !== ''),function($query) use($where){
                        $query->where('mer_id',$where['mer_id']);
                    })->column('order_id');
                $query->whereIn('order_id',$orderId ?: '' );
            })
            ->when(isset($where['search']) && $where['search'] !== '', function ($query) use ($where) {
                $orderId = StoreOrderProduct::alias('op')
                    ->join('storeProduct sp','op.product_id = sp.product_id')
                    ->whereLike('store_name',"%{$where['search']}%")
                    ->when((isset($where['sp.mer_id']) && $where['mer_id'] !== ''),function($query) use($where){
                        $query->where('mer_id',$where['mer_id']);
                    })->column('order_id');
                $query->where(function($query) use($orderId,$where){
                    $query->whereIn('order_id',$orderId ? $orderId : '')
                        ->whereOr('order_sn','like',"%{$where['search']}%")
                        ->whereOr('StoreOrder.user_phone','like',"%{$where['search']}%");
                });
            })
            ->when(isset($where['group_order_sn']) && $where['group_order_sn'] !== '', function ($query) use ($where) {
                $query->join('StoreGroupOrder GO','StoreOrder.group_order_id = GO.group_order_id')->where('group_order_sn',$where['group_order_sn']);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where(function ($query) use ($where) {
                    $query->whereLike('StoreOrder.real_name|StoreOrder.user_phone|order_sn', "%" . $where['keywords'] . "%");
                });
            })
            ->when(isset($where['filter_delivery']) && $where['filter_delivery'] !== '', function ($query) use ($where) {
                //1 快递 2 配送 3 虚拟
                //按发货方式：1快递订单、2配送订单、4核销订单、3虚拟发货、6自动发货
                switch ($where['filter_delivery']) {
                    case 1:
                        $query->whereIn('delivery_type','1,4');
                        break;
                    case 2:
                        $query->whereIn('delivery_type','2,5');
                        break;
                    case 3:
                        $query->whereIn('delivery_type','3');
                        break;
                    case 4:
                        $query->where('order_type',1);
                        break;
                    case 6:
                        $query->whereIn('delivery_type','6');
                        break;
                }
            })
            ->when(isset($where['filter_product']) && $where['filter_product'] !== '', function ($query) use ($where) {
                // 1 实物商品、2虚拟商品、3卡密商品
                switch ($where['filter_product']) {
                    case 1:
                        $query->where('is_virtual',0);
                        break;
                    case 2:
                        $query->where('is_virtual',1);
                        break;
                    case 3:
                        $query->where('is_virtual',2);
                        break;
                }
            })
           ->order('StoreOrder.create_time DESC');

        return $query;
    }

    public function searchAll(array $where, $sysDel = 0, $is_points = null)
    {
        $query = StoreOrder::getDB()->alias('StoreOrder');
        $query->when(($sysDel !== null), function ($query) use ($sysDel) {
            $query->where('is_system_del', $sysDel);
        });
        $query->when(!is_null($is_points), function ($query) use($is_points){
            if ($is_points) {
                $query->where('activity_type', 20);
            } else {
                $query->where('activity_type', '<>',20);
            }
        });
        $query->when(isset($where['order_type']) && $where['order_type'] >= 0 && $where['order_type'] !== '', function ($query) use ($where) {
            if ($where['order_type'] == 2) {
                $query->where('is_virtual', 1);
            } else if($where['order_type'] == 0){ //实体发货订单
                $query->where('order_type', 0)->where('is_virtual',0);
            } else if($where['order_type'] == 3) { //发货订单
                $query->where('order_type', 0);
            } else {
                $query->where('order_type', $where['order_type']);
            }
        });
        $query->when(isset($where['activity_type']) && $where['activity_type'] !== '', function ($query) use ($where) {
            $query->where('activity_type', $where['activity_type']);
        })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                switch ($where['status']) {
                    case 0 :
                        $query->whereIn('StoreOrder.status', [0, 9])->where('paid', 1)->where('is_del', 0);
                        break;
                    case -2 :
                        $query->where('paid', 1)->whereNotIn('StoreOrder.status', [10, 11])->where('is_del', 0);
                        break;
                    case -3 :
                        $query->where('paid', 0)->where('is_del', 0);
                        break;
                    case 10 :
                        $query->where('paid', 1)->whereIn('StoreOrder.status', [10, 11])->where('is_del', 0);
                        break;
                    default:
                        $query->where('StoreOrder.status', $where['status'])->where('is_del', 0);
                        break;
                }

            })
            ->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
                $query->where('uid', $where['uid']);
            })
            ->when(isset($where['is_user']) && $where['is_user'] !== '', function ($query) use ($where) {
                $query->where(function($query) {
                    $query->where('order_type',0)->whereOr(function($query){
                        $query->where('order_type',1)->where('main_id',0);
                    });
                });
            })
            //待核销订单
            ->when(isset($where['is_verify']) && $where['is_verify'], function ($query) use ($where) {
                $query->where('StoreOrder.order_type', 1)->where('StoreOrder.status',0);
            })
            ->when(isset($where['pay_type']) && $where['pay_type'] !== '', function ($query) use ($where) {
                $query->where('StoreOrder.pay_type', $where['pay_type']);
            })
            ->when(isset($where['order_ids']) && $where['order_ids'] !== '', function ($query) use ($where) {
                $query->whereIn('order_id', $where['order_ids']);
            })
            ->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use ($where) {
                $query->where('order_id', $where['order_id']);
            })
            ->when(isset($where['take_order']) && $where['take_order'] != '', function ($query) use ($where) {
                $query->where('order_type', 1)->whereNotNull('verify_time');
            })
            ->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date'], 'StoreOrder.create_time');
            })
            ->when(isset($where['order_sn']) && $where['order_sn'] !== '', function ($query) use ($where) {
                $query->where('order_sn', 'like', '%' . $where['order_sn'] . '%');
            })
            ->when(isset($where['paid']) && $where['paid'] !== '', function ($query) use ($where) {
                $query->where('StoreOrder.paid', $where['paid']);
            })
            ->when(isset($where['is_del']) && $where['is_del'] !== '', function ($query) use ($where) {
                $query->where('StoreOrder.is_del', $where['is_del']);
            })
            ->when(isset($where['service_id']) && $where['service_id'] !== '', function ($query) use ($where) {
                $query->where('service_id', $where['service_id']);
            })
            ->when(isset($where['username']) && $where['username'] !== '', function ($query) use ($where) {
                $query->join('User U','StoreOrder.uid = U.uid')
                    ->where(function($query) use($where) {
                        $query->where('nickname', 'like', "%{$where['username']}%")
                            ->whereOr('phone', 'like', "%{$where['username']}%")
                            ->whereOr('user_phone', 'like', "%{$where['username']}%");
                    });
            })
            ->when(isset($where['nickname']) && $where['nickname'] !== '', function ($query) use ($where) {
                $query->whereLike('StoreOrder.real_name', "%" . $where['nickname'] . "%");
            })
            ->when(isset($where['phone']) && $where['phone'] !== '', function ($query) use ($where) {
                $query->join('User U','StoreOrder.uid = U.uid')->where('phone', 'like', "%{$where['phone']}%");
            })
            ->when(isset($where['store_name']) && $where['store_name'] !== '', function ($query) use ($where) {
                $orderId = StoreOrderProduct::alias('op')
                    ->join('storeProduct sp','op.product_id = sp.product_id')
                    ->whereLike('store_name',"%{$where['store_name']}%")
                    ->when((isset($where['sp.mer_id']) && $where['mer_id'] !== ''),function($query) use($where){
                        $query->where('mer_id',$where['mer_id']);
                    })->column('order_id');
                $query->whereIn('order_id',$orderId ?: '' );
            })
            ->when(isset($where['search']) && $where['search'] !== '', function ($query) use ($where) {
                $orderId = StoreOrderProduct::alias('op')
                    ->join('storeProduct sp','op.product_id = sp.product_id')
                    ->whereLike('store_name',"%{$where['search']}%")
                    ->when((isset($where['sp.mer_id']) && $where['mer_id'] !== ''),function($query) use($where){
                        $query->where('mer_id',$where['mer_id']);
                    })->column('order_id');
                $query->where(function($query) use($orderId,$where){
                    $query->whereIn('order_id',$orderId ? $orderId : '')
                        ->whereOr('order_sn','like',"%{$where['search']}%")
                        ->whereOr('user_phone','like',"%{$where['search']}%");
                });
            })
            ->when(isset($where['product_id']) && $where['product_id'] !== '', function ($query) use ($where) {
                $orderId = StoreOrderProduct::alias('op')
                    ->join('storeProduct sp','op.product_id = sp.product_id')
                    ->where('sp.product_id',$where['product_id'])
                    ->column('order_id');
                $query->whereIn('order_id',$orderId ? $orderId : '');
            })
            ->when(isset($where['group_order_sn']) && $where['group_order_sn'] !== '', function ($query) use ($where) {
                $query->join('StoreGroupOrder GO','StoreOrder.group_order_id = GO.group_order_id')->where('group_order_sn',$where['group_order_sn']);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where(function ($query) use ($where) {
                    $query->whereLike('StoreOrder.real_name|StoreOrder.user_phone|order_sn', "%" . $where['keywords'] . "%");
                });
            })
            ->order('StoreOrder.create_time DESC');

        return $query;
    }
    /**
     * @param $id
     * @param $uid
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/11
     */
    public function userOrder($id, $uid)
    {
        return StoreOrder::getDB()->where('order_id', $id)->where('uid', $uid)->where('is_del', 0)->where('paid', 1)->where('is_system_del', 0)->find();
    }

    /**
     * @param array $where
     * @param $ids
     * @return BaseQuery
     * @author xaboy
     * @day 2020/6/26
     */
    public function usersOrderQuery(array $where, $ids, $uid)
    {
        return StoreOrder::getDB()->where(function ($query) use ($uid, $ids) {
            $query->whereIn('uid', $ids)
                ->whereOr(function ($query) use ($uid) {
                if ($uid) {
                    $query->where('uid', $uid)->where('is_selfbuy', 1);
                }
            });
        })->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
            getModelTime($query, $where['date'], 'pay_time');
        })->when(isset($where['create_time']) && $where['create_time'] !== '', function ($query) use ($where) {
            getModelTime($query, $where['create_time'], 'create_time');
        })->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $_uid = User::where('nickname', 'like', "%{$where['keyword']}%")->column('uid');
            $orderId = StoreOrderProduct::alias('op')
                ->join('storeProduct sp','op.product_id = sp.product_id')
                ->whereLike('store_name',"%{$where['keyword']}%")
                ->column('order_id');
            $query->where(function($query) use($orderId,$where,$_uid){
                $query->whereLike('order_id|order_sn', "%{$where['keyword']}%")->whereOr('order_id','in',$orderId)->whereOr('uid','in',$_uid);
            });
        })->where('paid', 1)->order('pay_time DESC');
    }

    /**
     * @param $field
     * @param $value
     * @param int|null $except
     * @return bool
     * @author xaboy
     * @day 2020/6/11
     */
    public function fieldExists($field, $value, ?int $except = null): bool
    {
        return ($this->getModel()::getDB())->when($except, function ($query) use ($field, $except) {
                $query->where($field, '<>', $except);
            })->where($field, $value)->count() > 0;
    }

    /**
     * @param $id
     * @return mixed
     * @author xaboy
     * @day 2020/6/12
     */
    public function getMerId($id)
    {
        return StoreOrder::getDB()->where('order_id', $id)->value('mer_id');
    }

    /**
     * @param array $where
     * @return bool
     * @author Qinii
     * @day 2020-06-12
     */
    public function merFieldExists(array $where)
    {
        return ($this->getModel()::getDB())->where($where)->count() > 0;
    }

    /**
     * TODO
     * @param $reconciliation_id
     * @return mixed
     * @author Qinii
     * @day 2020-06-15
     */
    public function reconciliationUpdate($reconciliation_id)
    {
        return ($this->getModel()::getDB())->whereIn('reconciliation_id', $reconciliation_id)->update(['reconciliation_id' => 0]);
    }

    public function dayOrderNum($day, $merId = null)
    {
        return StoreOrder::getDB()->where('paid', 1)->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        })->when($day, function ($query, $day) {
            getModelTime($query, $day, 'pay_time');
        })->count();
    }

    public function dayOrderPrice($day, $merId = null)
    {
        return getModelTime(StoreOrder::getDB()->where('paid', 1)->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        }), $day, 'pay_time')->sum('pay_price');
    }

    public function dateOrderPrice($date, $merId = null)
    {
        return StoreOrder::getDB()->where('paid', 1)->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        })->when($date, function ($query, $date) {
            getModelTime($query, $date, 'pay_time');
        })->sum('pay_price');
    }

    public function dateOrderNum($date, $merId = null)
    {
        return StoreOrder::getDB()->where('paid', 1)->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        })->when($date, function ($query, $date) {
            getModelTime($query, $date, 'pay_time');
        })->count();
    }

    public function dayOrderUserNum($day, $merId = null)
    {
        return StoreOrder::getDB()->where('paid', 1)->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        })->when($day, function ($query, $day) {
            getModelTime($query, $day, 'pay_time');
        })->group('uid')->count();
    }

    public function orderUserNum($date, $paid = null, $merId = null)
    {
        return StoreOrder::getDB()->when($paid, function ($query, $paid) {
            $query->where('paid', $paid);
        })->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        })->when($date, function ($query, $date) use ($paid) {
            if (!$paid) {
                getModelTime($query, $date);
//                $query->where(function ($query) use ($date) {
//                    $query->where(function ($query) use ($date) {
//                        $query->where('paid', 1);
//                        getModelTime($query, $date, 'pay_time');
//                    })->whereOr(function ($query) use ($date) {
//                        $query->where('paid', 0);
//                        getModelTime($query, $date);
//                    });
//                });
            } else
                getModelTime($query, $date, 'pay_time');
        })->group('uid')->count();
    }

    public function orderUserGroup($date, $paid = null, $merId = null)
    {
        return StoreOrder::getDB()->when($paid, function ($query, $paid) {
            $query->where('paid', $paid);
        })->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        })->when($date, function ($query, $date) {
            getModelTime($query, $date, 'pay_time');
        })->group('uid')->field(Db::raw('uid,sum(pay_price) as pay_price,count(order_id) as total'))->select();
    }

    public function oldUserNum(array $ids, $merId = null)
    {
        return StoreOrder::getDB()->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        })->whereIn('uid', $ids)->where('paid', 1)->group('uid')->count();
    }

    public function oldUserIds(array $ids, $merId = null)
    {
        return StoreOrder::getDB()->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        })->whereIn('uid', $ids)->where('paid', 1)->group('uid')->column('uid');
    }

    public function orderPrice($date, $paid = null, $merId = null)
    {
        return StoreOrder::getDB()->when($paid, function ($query, $paid) {
            $query->where('paid', $paid);
        })->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        })->when($date, function ($query, $date) use ($paid) {
            if (!$paid) {
                $query->where(function ($query) use ($date) {
                    $query->where(function ($query) use ($date) {
                        $query->where('paid', 1);
                        getModelTime($query, $date, 'pay_time');
                    })->whereOr(function ($query) use ($date) {
                        $query->where('paid', 0);
                        getModelTime($query, $date);
                    });
                });
            } else
                getModelTime($query, $date, 'pay_time');
        })->sum('pay_price');
    }

    public function orderGroupNum($date, $merId = null)
    {
        $field = Db::raw('sum(pay_price) as pay_price,count(*) as total,count(distinct uid) as user,pay_time,from_unixtime(unix_timestamp(pay_time),\'%m-%d\') as `day`');
        if ($date == 'year'){
            $field = Db::raw('sum(pay_price) as pay_price,count(*) as total,count(distinct uid) as user,pay_time,from_unixtime(unix_timestamp(pay_time),\'%m\') as `day`');
        }
        $query = StoreOrder::getDB()->field($field)
            ->where('paid', 1)->when($date, function ($query, $date) {
                getModelTime($query, $date, 'pay_time');
            })->when($merId, function ($query, $merId) {
                $query->where('mer_id', $merId);
            });
        return $query->order('pay_time ASC')->group('day')->select();
    }

    public function orderGroupNumPage($where, $page, $limit, $merId = null)
    {
        return StoreOrder::getDB()->when(isset($where['dateRange']), function ($query) use ($where) {
            getModelTime($query, date('Y/m/d 00:00:00', $where['dateRange']['start']) . '-' . date('Y/m/d 00:00:00', $where['dateRange']['stop']), 'pay_time');
        })->field(Db::raw('sum(pay_price) as pay_price,count(*) as total,count(distinct uid) as user,pay_time,from_unixtime(unix_timestamp(pay_time),\'%m-%d\') as `day`'))
            ->where('paid', 1)->when($merId, function ($query, $merId) {
                $query->where('mer_id', $merId);
            })->order('pay_time DESC')->page($page, $limit)->group('day')->select();
    }

    public function dayOrderPriceGroup($date, $merId = null)
    {
        return StoreOrder::getDB()->field(Db::raw('sum(pay_price) as price, from_unixtime(unix_timestamp(pay_time),\'%H:%i\') as time'))
            ->where('paid', 1)->when($date, function ($query, $date) {
                getModelTime($query, $date, 'pay_time');
            })->when($merId, function ($query, $merId) {
                $query->where('mer_id', $merId);
            })->group('time')->select();
    }

    public function dayOrderNumGroup($date, $merId = null)
    {
        return StoreOrder::getDB()->field(Db::raw('count(*) as total, from_unixtime(unix_timestamp(pay_time),\'%H:%i\') as time'))
            ->where('paid', 1)->when($date, function ($query, $date) {
                getModelTime($query, $date, 'pay_time');
            })->when($merId, function ($query, $merId) {
                $query->where('mer_id', $merId);
            })->group('time')->select();
    }

    public function dayOrderUserGroup($date, $merId = null)
    {
        return StoreOrder::getDB()->field(Db::raw('count(DISTINCT uid) as total, from_unixtime(unix_timestamp(pay_time),\'%H:%i\') as time'))
            ->where('paid', 1)->when($date, function ($query, $date) {
                getModelTime($query, $date, 'pay_time');
            })->when($merId, function ($query, $merId) {
                $query->where('mer_id', $merId);
            })->group('time')->select();
    }

    /**
     * 获取当前时间到指定时间的支付金额 管理员
     * @param string $start 开始时间
     * @param string $stop 结束时间
     * @return mixed
     */
    public function chartTimePrice($start, $stop, $merId = null)
    {
        return StoreOrder::getDB()->where('paid', 1)
            ->where('pay_time', '>=', $start)
            ->where('pay_time', '<', $stop)
            ->when($merId, function ($query, $merId) {
                $query->where('mer_id', $merId);
            })
            ->field('sum(pay_price) as num,FROM_UNIXTIME(unix_timestamp(pay_time), \'%Y-%m-%d\') as time')
            ->group('time')
            ->order('pay_time ASC')->select()->toArray();
    }

    /**
     * @param $date
     * @param null $merId
     * @return mixed
     */
    public function chartTimeNum($date, $merId = null)
    {
        return StoreOrder::getDB()->where('paid', 1)->when($date, function ($query) use ($date) {
            getModelTime($query, $date, 'pay_time');
        })->when($merId, function ($query, $merId) {
            $query->where('mer_id', $merId);
        })->field('count(order_id) as num,FROM_UNIXTIME(unix_timestamp(pay_time), \'%Y-%m-%d\') as time')
            ->group('time')
            ->order('pay_time ASC')->select()->toArray();
    }

    /**
     * @param $end
     * @return mixed
     * @author xaboy
     * @day 2020/9/16
     */
    public function getFinishTimeoutIds($end)
    {
        return StoreOrderStatus::getDB()->alias('A')->leftJoin('StoreOrder B', 'A.order_id = B.order_id')
            ->where('A.change_type', 'take')
            ->where('A.change_time', '<', $end)->where('B.paid', 1)->where('B.status', 2)
            ->column('A.order_id');
    }


    /**
     * TODO 参与人数
     * @param array $data
     * @param int|null $uid
     * @return BaseQuery
     * @author Qinii
     * @day 2020-11-11
     */
    public function getTattendCount(array $data,?int $uid)
    {
        $query = StoreOrder::hasWhere('orderProduct',function($query)use($data,$uid){
            $query->when(isset($data['activity_id']),function ($query)use($data){
                    $query->where('activity_id',$data['activity_id']);
                })
                ->when(isset($data['product_sku']),function ($query)use($data){
                    $query->where('product_sku',$data['product_sku']);
                })
                ->when(isset($data['product_id']),function ($query)use($data){
                    $query->where('product_id',$data['product_id']);
                })
                ->when(isset($data['exsits_id']),function ($query)use ($data){
                    switch ($data['product_type']){
                        case 3:
                            $make = app()->make(ProductAssistSetRepository::class);
                            $id = 'product_assist_id';
                            break;
                        case 4:
                            $make = app()->make(ProductGroupBuyingRepository::class);
                            $id = 'product_group_id';
                            break;
                    }
                    $where = [$id => $data['exsits_id']];
                    $activity_id = $make->getSearch($where)->column($make->getPk());
                    if($activity_id) {
                        $id = array_unique($activity_id);
                        $query->where('activity_id','in',$id);
                    }else{
                        $query->where('activity_id','<',0);
                    }
                })
                ->where('product_type',$data['product_type']);
            if($uid) $query->where('uid',$uid);
        });
        $query->where('activity_type',$data['product_type']);
        switch($data['product_type'])
        {
            case 0:
                $query->where(function($query){
                    $query->where(function($query){
                        $query->where('paid',1);
                    })->whereOr(function($query){
                        $query->where('paid',0)->where('is_del',0);
                    });
                });
                break;
            case 1: //秒杀
                $query->where(function($query){
                    $query->where(function($query){
                        $query->where('paid',1);
                    })->whereOr(function($query){
                        $query->where('paid',0)->where('is_del',0);
                    });
                })->when(isset($data['day']), function ($query) use ($data) {
                    $query->whereDay('StoreOrder.create_time', $data['day']);
                });
                break;
            case 2: //预售

                /**
                 * 第一阶段参与人数：所有人
                 * 第二阶段参与人数：支付了第一阶段
                 */
                //第二阶段
                if($data['type'] == 1){
                    $query->where(function($query){
                        $query->where('paid',1)->whereOr(function($query){
                            $query->where('paid',0)->where('is_del',0);
                        });
                    });
                }
                if($data['type'] == 2) $query->where('paid',1)->where('status','in',[0,1,2,3,-1]);
                break;
            case 3: //助力
                $query->where(function($query){
                    $query->where('paid',1)->whereOr(function($query){
                        $query->where('paid',0)->where('is_del',0);
                    });
                });
                break;
            case 4: //
                $query->where(function($query){
                    $query->where('paid',1)->whereOr(function($query){
                        $query->where('paid',0)->where('is_del',0);
                    })
                    ->where('status','>',-1);
                });
                break;
        }
        return $query;
    }

    /**
     *  未使用
     * TODO 成功支付人数
     * @param int $productType
     * @param int $activityId
     * @param int|null $uid
     * @param int|null $status
     * @author Qinii
     * @day 2020-10-30
     */
    public function getTattendSuccessCount($data,?int $uid)
    {
        $query = StoreOrder::hasWhere('orderProduct',function($query)use($data,$uid){
            $query->when(isset($data['activity_id']),function ($query)use($data){
                    $query->where('activity_id',$data['activity_id']);
                })
                ->when(isset($data['product_sku']),function ($query)use($data){
                    $query->where('product_sku',$data['product_sku']);
                })
                ->when(isset($data['product_id']),function ($query)use($data){
                    $query->where('product_id',$data['product_id']);
                })
                ->when(isset($data['exsits_id']),function ($query)use ($data){
                    switch ($data['product_type']){
                        case 3:
                            $make = app()->make(ProductAssistSetRepository::class);
                            $id = 'product_assist_id';
                            break;
                        case 4:
                            $make = app()->make(ProductGroupBuyingRepository::class);
                            $id = 'product_group_id';
                            break;
                    }
                    $where = [$id => $data['exsits_id']];
                    $activity_id = $make->getSearch($where)->column($make->getPk());
                    if($activity_id) {
                        $id = array_unique($activity_id);
                        $query->where('activity_id','in',$id);
                    }else{
                        $query->where('activity_id','<',0);
                    }
                })
                ->where('product_type',$data['product_type']);
            if($uid) $query->where('uid',$uid);
        });
        $query->where('activity_type',$data['product_type'])->where('paid',1);

        switch($data['product_type'])
        {
            case 1: //秒杀
                $query->where(function($query){
                    $query->where(function($query){
                        $query->where('paid',1);
                    });
                })->when(isset($data['day']), function ($query) use ($data) {
                    $query->whereDay('StoreOrder.create_time', $data['day']);
                });
                break;
            case 2: //预售
                if($data['type'] == 1){    //第一阶段
                    $query->where('status','in',[0,1,2,3,10]);
                } else {        //第二阶段
                    $query->where('status','in',[0,1,2,3]);
                }
                break;
            case 3: //助力
                break;
            case 4:
                break;
        }
        return $query;
    }


    /**
     * TODO 获取退款单数量
     * @param $where
     * @return mixed
     * @author Qinii
     * @day 1/4/21
     */
    public function getSeckillRefundCount($where,$type = 1)
    {
        $query = StoreOrderProduct::getDB()->alias('P')->join('StoreRefundOrder R','P.order_id = R.order_id');
        $query->join('StoreOrder O','O.order_id = P.order_id');
        $query
            ->when(isset($where['activity_id']),function ($query)use($where){
                $query->where('P.activity_id',$where['activity_id']);
            })
            ->when(isset($where['product_sku']),function ($query)use($where){
                $query->where('P.product_sku',$where['product_sku']);
            })
            ->when(isset($where['day']), function ($query) use ($where) {
                $query->whereDay('P.create_time', $where['day']);
            })
            ->when($type == 1, function ($query) use ($where) {
                $query->where('O.verify_time',null)->where('O.delivery_type',null);
            },function ($query){
                $query ->where('R.refund_type',2);
            })
            ->where('P.product_type',1)->where('R.status',3);
        return $query->count();
    }


    /**
     * TODO 用户的某个商品购买数量
     * @param int $uid
     * @param int $productId
     * @return int
     * @author Qinii
     * @day 2022/9/26
     */
    public function getMaxCountNumber(int $uid, int $productId)
    {
        return StoreOrder::hasWhere('orderProduct',function($query) use($productId){
            $query->where('product_id', $productId);
        })
        ->where(function($query) {
            $query->where('is_del',0)->whereOr(function($query){
                $query->where('is_del',1)->where('paid',1);
            });
        })->where('StoreOrder.uid',$uid)->count()
       ;
    }
}
