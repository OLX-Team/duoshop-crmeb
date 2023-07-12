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

use app\common\repositories\store\coupon\StoreCouponProductRepository;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\pionts\PointsProductRepository;
use app\common\repositories\store\StoreActivityRepository;
use app\common\repositories\user\UserRepository;
use crmeb\jobs\SendSmsJob;
use crmeb\jobs\SyncProductTopJob;
use crmeb\services\CopyCommand;
use crmeb\services\RedisCacheService;
use think\exception\ValidateException;
use think\facade\Log;
use app\common\repositories\BaseRepository;
use app\common\dao\store\product\SpuDao;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\store\StoreSeckillActiveRepository;
use app\common\repositories\user\UserVisitRepository;
use think\facade\Queue;

class SpuRepository extends BaseRepository
{
    public $dao;
    public $merchantFiled = 'mer_id,mer_name,mer_avatar,is_trader,mer_info,mer_keyword,type_id';
    public $productFiled  = 'S.product_id,S.store_name,S.image,activity_id,S.keyword,S.price,S.mer_id,spu_id,S.status,store_info,brand_id,cate_id,unit_name,S.star,S.rank,S.sort,sales,S.product_type,rate,reply_count,extension_type,S.sys_labels,S.mer_labels,P.delivery_way,P.delivery_free,S.ot_price,svip_price_type,stock,mer_svip_status';
    public function __construct(SpuDao $dao)
    {
        $this->dao = $dao;
    }

    public function create(array $param, int $productId, int $activityId, $productType = 0)
    {
        $data = $this->setparam($param, $productId, $activityId, $productType);
        return $this->dao->create($data);
    }

    public function baseUpdate(array $param, int $productId, int $activityId, $productType = 0)
    {
        if ($productType == 1) {
            $make = app()->make(StoreSeckillActiveRepository::class);
            $activityId = $make->getSearch(['product_id' => $productId])->value('seckill_active_id');
        }
        $where = [
            'product_id' => $productId,
            'activity_id' => $activityId,
            'product_type' => $productType,
        ];
        $ret = $this->dao->getSearch($where)->find();
        if (!$ret) {
            return $this->create($param, $productId, $activityId, $productType);
        } else {
            $data = $this->setparam($param, $productId, $activityId, $productType);

            $value = $data['mer_labels'];
            if (!empty($value)) {
                if (!is_array($value)) {
                    $data['mer_labels'] = ',' . $value . ',';
                } else {
                    $data['mer_labels'] = ',' . implode(',', $value) . ',';
                }
            }
            return $this->dao->update($ret->spu_id, $data);
        }
    }

    public function setparam(array $param, $productId, $activityId, $productType)
    {
        $data = [
            'product_id' => $productId,
            'product_type' => $productType ?? 0,
            'activity_id' => $activityId,
            'store_name' => $param['store_name'],
            'keyword'   => $param['keyword'] ?? '',
            'image'     => $param['image'],
            'ot_price' =>  $param['ot_price'] ?? 0,
            'price'     => $param['price'],
            'status'    => $productType == PointsProductRepository::PRODUCT_TYPE_POINTS ? 1 : 0,
            'rank'      => $param['rank'] ?? 0,
            'temp_id'   => $param['temp_id'],
            'sort'      => $param['sort'] ?? 0,
            'mer_labels' =>  $param['mer_labels'] ?? '',
        ];
        if (isset($param['mer_id'])) $data['mer_id'] = $param['mer_id'];
        return $data;
    }

    /**
     * TODO 修改排序
     * @param $productId
     * @param $activityId
     * @param $productType
     * @param $data
     * @author Qinii
     * @day 1/19/21
     */
    public function updateSort($productId, $activityId, $productType, $data)
    {
        $where = [
            'product_id' => $productId,
            'activity_id' => $activityId,
            'product_type' => $productType,
        ];
        $ret = $this->dao->getSearch($where)->find();
        if ($ret) $this->dao->update($ret['spu_id'], $data);
    }
    /**
     * TODO 移动端列表
     * @param $where
     * @param $page
     * @param $limit
     * @param $userInfo
     * @return array
     * @author Qinii
     * @day 12/18/20
     */
    public function getApiSearch($where, $page, $limit, $userInfo = null)
    {
        if (isset($where['keyword']) && !empty($where['keyword'])) {
            if (preg_match('/^(\/@[1-9]{1}).*\*\//', $where['keyword'])) {
                $command = app()->make(CopyCommand::class)->getMassage($where['keyword']);
                if (!$command || in_array($command['type'], [30, 40])) return ['count' => 0, 'list' => []];
                if ($userInfo && $command['uid']) app()->make(UserRepository::class)->bindSpread($userInfo, $command['uid']);
                $where['spu_id'] = $command['id'];
                unset($where['keyword']);
            } else {
                app()->make(UserVisitRepository::class)->searchProduct($userInfo ? $userInfo['uid'] : 0, $where['keyword'], (int)($where['mer_id'] ?? 0));
            }
        }
        $where['spu_status'] = 1;
        $where['mer_status'] = 1;
        $where['not_type'] = [20];
        $query = $this->dao->search($where);
        $query->with([
            'merchant' => function ($query) {
                $query->field($this->merchantFiled)->with(['type_name']);
            },
            'issetCoupon',
        ]);
        $productMake = app()->make(ProductRepository::class);
        $count = $query->count();

        $list = $query->page($page, $limit)->setOption('field', [])->field($this->productFiled)->select();
        $append = ['stop_time','svip_price','show_svip_info','is_svip_price'];
        if ($productMake->getUserIsPromoter($userInfo))
            $append[] = 'max_extension';
        $list->append($append);
        $list = $this->getBorderList($list);
        return compact('count', 'list');
    }

    public function getBorderList($list)
    {
        $make = app()->make(StoreActivityRepository::class);
        foreach ($list as $item) {
            $act = $make->getActivityBySpu(StoreActivityRepository::ACTIVITY_TYPE_BORDER,$item['spu_id'],$item['cate_id'],$item['mer_id']);
            $item['border_pic'] = $act['pic'] ?? '';
        }
        return $list;
    }


    /**
     * TODO 修改状态
     * @param array $data
     * @author Qinii
     * @day 12/18/20
     */
    public function changeStatus(int $id, int $productType)
    {
        $make = app()->make(ProductRepository::class);

        $status = 1;
        try {
            switch ($productType) {
                case 0:
                    $where = [
                        'activity_id' => 0,
                        'product_id' => $id,
                        'product_type' => $productType,
                    ];
                    break;
                case 1:
                    $_make = app()->make(StoreSeckillActiveRepository::class);
                    $res = $_make->getSearch(['product_id' => $id])->find();
                    $endday = strtotime($res['end_day']);
                    if ($res['status'] == -1 || $endday < time()) $status = 0;
                    $where = [
                        'activity_id' => $res['seckill_active_id'],
                        'product_id' => $id,
                        'product_type' => $productType,
                    ];
                    break;
                case 2:
                    $_make = app()->make(ProductPresellRepository::class);
                    $res = $_make->getWhere([$_make->getPk() => $id]);

                    $endttime = strtotime($res['end_time']);
                    if ($endttime <= time()) {
                        $status = 0;
                    } else {
                        if (
                            $res['product_status'] !== 1 ||
                            $res['status'] !== 1 ||
                            $res['action_status'] !== 1 ||
                            $res['is_del'] !== 0 ||
                            $res['is_show'] !== 1
                        ) {
                            $status = 0;
                        }
                    }
                    $where = [
                        'activity_id' => $id,
                        'product_id' => $res['product_id'],
                        'product_type' => $productType,
                    ];
                    break;
                case 3:
                    $_make = app()->make(ProductAssistRepository::class);
                    $res = $_make->getWhere([$_make->getPk() => $id]);

                    $endttime = strtotime($res['end_time']);
                    if ($endttime <= time()) {
                        $status = 0;
                    } else {
                        if (
                            $res['product_status'] !== 1 ||
                            $res['status'] !== 1 ||
                            $res['is_show'] !== 1 ||
                            $res['action_status'] !== 1 ||
                            $res['is_del'] !== 0
                        ) {
                            $status = 0;
                        }
                    }

                    $where = [
                        'activity_id' => $id,
                        'product_id' => $res['product_id'],
                        'product_type' => $productType,
                    ];
                    break;
                case 4:
                    $_make = app()->make(ProductGroupRepository::class);
                    $wher = $_make->actionShow();
                    $wher[$_make->getPk()] = $id;

                    $res = $_make->getWhere([$_make->getPk() => $id]);
                    $endttime = strtotime($res['end_time']);
                    if ($endttime <= time()) {
                        $status = 0;
                    } else {
                        if (
                            $res['product_status'] !== 1 ||
                            $res['status'] !== 1 ||
                            $res['is_show'] !== 1 ||
                            $res['action_status'] !== 1 ||
                            $res['is_del'] !== 0
                        ) {
                            $status = 0;
                        }
                    }

                    $where = [
                        'activity_id' => $id,
                        'product_id' => $res['product_id'],
                        'product_type' => $productType,
                    ];
                    break;
                default:
                    $where = [
                        'activity_id' => 0,
                        'product_id' => $id,
                        'product_type' => $productType,
                    ];
                    break;
            }
            $ret = $make->getWhere(['product_id' => $where['product_id']]);
            if (
                !$ret ||
                $ret['status'] !== 1 ||
                $ret['mer_status'] !== 1 ||
                $ret['is_del'] ||
                (in_array($productType, [0, 1,20]) && ($ret['is_show'] !== 1 || $ret['is_used'] !== 1))
            ) {
                $status = 0;
            }
            $result = $this->dao->getSearch($where)->find();
            if (!$result && $ret){
                $result = $this->create($ret->toArray(), $where['product_id'], $where['activity_id'], $productType);
            }
            if ($result) $this->dao->update($result['spu_id'], ['status' => $status]);
            if ($productType == 0) {
                Queue::push(SyncProductTopJob::class,[]);
                if ($status == 1) Queue(SendSmsJob::class, ['tempId' => 'PRODUCT_INCREASE', 'id' => $id]);
            }
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }
    }

    /**
     * TODO 平台编辑商品同步修改
     * @param int $id
     * @param int $productId
     * @param int $productType
     * @param array $data
     * @author Qinii
     * @day 12/18/20
     */
    public function changRank(int $id, int $productId, int $productType, array  $data)
    {
        $where = [
            'product_id' => $productId,
            'product_type' => $productType,
            'activity_id' => $id,
        ];
        $res = $this->dao->getWhere($where);
        if (!$res && $id) $this->changeStatus($id, $productType);
        $res = $this->dao->getWhere($where);
        if ($res) {
            $res->store_name = $data['store_name'];
            $res->rank = $data['rank'];
            $res->star = $data['star'] ?? 1;
            $res->save();
        }
    }

    /**
     * TODO 同步各类商品到spu表
     * @param array|null $productType
     * @author Qinii
     * @day 12/25/20
     */
    public function updateSpu(?array $productType)
    {
        if (!$productType) $productType = [0, 1, 2, 3, 4];
        $_product_make = app()->make(ProductRepository::class);
        $data = [];
        foreach ($productType as $value) {
            $ret = $_product_make->activitSearch($value);
            $data = array_merge($data, $ret);
        }
        $this->dao->findOrCreateAll($data);
    }

    /**
     * TODO 获取活动商品的一级分类
     * @param $type
     * @return mixed
     * @author Qinii +0
     * @day 1/12/21
     */
    public function getActiveCategory($type)
    {
        $pathArr = $this->dao->getActivecategory($type);
        $path = [];
        foreach ($pathArr as $item) {
            $path[] = explode('/', $item)[1];
        }
        $path = array_unique($path);
        $cat = app()->make(StoreCategoryRepository::class)->getSearch(['ids' => $path])->field('store_category_id,cate_name')->select();
        return $cat;
    }

    public function getSpuData($id, $productType, $merId)
    {
        try {
            switch ($productType) {
                case 0:
                    $where = [
                        'activity_id' => 0,
                        'product_id' => $id,
                        'product_type' => $productType,
                    ];
                    break;
                case 1:
                    $_make = app()->make(StoreSeckillActiveRepository::class);
                    $res = $_make->getSearch(['product_id' => $id])->find();
                    $where = [
                        'activity_id' => $res['seckill_active_id'],
                        'product_id' => $id,
                        'product_type' => $productType,
                    ];
                    break;
                case 2:
                    $_make = app()->make(ProductPresellRepository::class);
                    $res = $_make->getWhere([$_make->getPk() => $id]);
                    $where = [
                        'activity_id' => $id,
                        'product_id' => $res['product_id'],
                        'product_type' => $productType,
                    ];
                    break;
                case 3:
                    $_make = app()->make(ProductAssistRepository::class);
                    $res = $_make->getWhere([$_make->getPk() => $id]);
                    $where = [
                        'activity_id' => $id,
                        'product_id' => $res['product_id'],
                        'product_type' => $productType,
                    ];
                    break;
                case 4:
                    $_make = app()->make(ProductGroupRepository::class);
                    $where[$_make->getPk()] = $id;
                    $res = $_make->getWhere([$_make->getPk() => $id]);
                    $where = [
                        'activity_id' => $id,
                        'product_id' => $res['product_id'],
                        'product_type' => $productType,
                    ];
                    break;
                default:
                    $where = [
                        'activity_id' => 0,
                        'product_id' => $id,
                        'product_type' => 0,
                    ];
                    break;
            }
        } catch (\Exception $e) {
            throw new ValidateException('数据不存在');
        }
        if ($merId) $where['mer_id'] = $merId;
        $result = $this->dao->getSearch($where)->find();
        if (!$result) throw new ValidateException('数据不存在');
        return $result;
    }

    public function setLabels($id, $productType, $data, $merId = 0)
    {
        $field = isset($data['sys_labels']) ? 'sys_labels' : 'mer_labels';
        if ($data[$field])  app()->make(ProductLabelRepository::class)->checkHas($merId, $data[$field]);
        $ret = $this->getSpuData($id, $productType, $merId);
        $value = $data[$field] ? $data[$field] : '';
        $ret->$field = $value;
        $ret->save();
    }

    public function batchLabels($ids, $data,$merId)
    {
        $ids = is_array($ids) ? $ids : explode(',',$ids);
        foreach ($ids as $id) {
            $this->setLabels($id,0,$data,$merId);
        }
    }


    public function getApiSearchByCoupon($where, $page, $limit, $userInfo)
    {
        $coupon = app()->make(StoreCouponRepository::class)->search(null, [
            'status' => 1,
            'coupon_id' => $where['coupon_id']
        ])->find();
        $data['coupon'] = $coupon;
        if ($coupon) {
            switch ($coupon['type']) {
                case 0:
                    $where['mer_id'] = $coupon['mer_id'];
                    break;
                case 1:
                    $where['product_ids'] = app()->make(StoreCouponProductRepository::class)->search([
                        'coupon_id' => $where['coupon_id']
                    ])->column('product_id');
                    break;
                case 11:
                    $ids = app()->make(StoreCouponProductRepository::class)->search([
                        'coupon_id' => $where['coupon_id']
                    ])->column('product_id');
                    $where['cate_pid'] = $ids;
                    break;
                case 10:
                    break;
                case 12:
                    $ids = app()->make(StoreCouponProductRepository::class)->search([
                        'coupon_id' => $where['coupon_id']
                    ])->column('product_id');
                    $where['mer_ids'] = $ids;
                    break;
            }
            $where['is_coupon'] = 1;
            $where['order'] = 'star';
            $where['common'] = 1;
            $where['svip'] = ($coupon['send_type'] == StoreCouponRepository::GET_COUPON_TYPE_SVIP) ? 1 : '';
            $product = $this->getApiSearch($where, $page, $limit, $userInfo);
        }

        $data['count'] = $product['count'] ?? 0;
        $data['list'] = $product['list'] ?? [];
        return $data;
    }

    public function getHotRanking(int $cateId)
    {
        $RedisCacheService = app()->make(RedisCacheService::class);
        $prefix = env('queue_name','merchant').'_hot_ranking_';
        $ids = $RedisCacheService->handler()->get($prefix.'top_' . intval($cateId));
        $ids = $ids ? explode(',', $ids) : [];
        if (!count($ids)) {
            return [];
        }
        $ids = array_map('intval', $ids);
        $where['mer_status'] = 1;
        $where['status'] = 1;
        $where['is_del'] = 0;
        $where['product_type'] = 0;
        $where['order'] = 'sales';
        $where['spu_ids'] = $ids;
        $list = $this->dao->search($where)->setOption('field',[])->field('spu_id,S.image,S.price,S.product_type,P.product_id,P.sales,S.status,S.store_name,P.ot_price,P.cost')->select();
        if ($list) $list = $list->toArray();
        return $list;
    }

    /**
     * TODO
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 2022/9/22
     */
    public function makinList($where,$page, $limit)
    {
        $where['spu_status'] = 1;
        $where['mer_status'] = 1;
        $query = $this->dao->search($where);
        $query->with([
            'merchant' ,
            'issetCoupon',
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->setOption('field', [])->field($this->productFiled)->select();
        return compact('count','list');
    }
}
