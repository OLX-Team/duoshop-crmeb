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
namespace app\common\dao\store\product;

use app\common\dao\BaseDao;
use app\common\model\store\product\ProductCate;
use app\common\model\store\product\Spu;
use app\common\model\store\StoreCategory;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use crmeb\services\VicWordService;

class SpuDao extends  BaseDao
{
    public function getModel(): string
    {
        return Spu::class;
    }

    public function search($where)
    {
        $order = 'P.sort DESC';
        if(isset($where['order'])){
            if(in_array($where['order'], ['is_new', 'price_asc', 'price_desc', 'rate', 'sales','ot_price','ot_price_desc','ot_price_asc'])){
                switch ($where['order']) {
                    case 'price_asc':
                        $order = 'S.price ASC';
                        break;
                    case 'price_desc':
                        $order = 'S.price DESC';
                        break;
                    case 'ot_price_asc':
                        $order = 'S.ot_price ASC';
                        break;
                    case 'ot_price_desc':
                        $order = 'S.ot_price DESC';
                        break;
                    default:
                        $order = 'P.'.$where['order'] . ' DESC';
                        break;
                }
            }elseif($where['order'] == 'star'){
                $order = 'S.star DESC,S.rank DESC';
            }else{
                $order = 'S.'. (($where['order'] !== '') ? $where['order']: 'star' ).' DESC';
            }
        }

        $order .= ',S.create_time DESC';
        if(isset($where['order']) && $where['order'] === 'none'){
            $order = '';
        }
        $query = Spu::getDB()->alias('S')->join('StoreProduct P','S.product_id = P.product_id', 'left');
        $query->when(isset($where['is_del']) && $where['is_del'] !== '',function($query)use($where){
                $query->where('P.is_del',$where['is_del']);
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '',function($query)use($where){
                $query->where('P.mer_id',$where['mer_id']);
            })
            ->when(isset($where['mer_ids']) && $where['mer_ids'] !== '',function($query)use($where){
                $query->whereIn('P.mer_id',$where['mer_ids']);
            })
            ->when(isset($where['keyword']) && $where['keyword'] !== '',function($query)use($where){
                if (is_numeric($where['keyword'])) {
                    $query->whereLike("S.store_name|S.keyword|S.product_id", "%{$where['keyword']}%");
                } else {
                    $word = app()->make(VicWordService::class)->getWord($where['keyword']);
                    $query->where(function ($query) use ($word) {
                        foreach ($word as $item) {
                            $query->whereOr('S.store_name|S.keyword', 'LIKE', "%$item%");
                        }
                    });
                }
            })
            ->when(isset($where['is_trader']) && $where['is_trader'] !== '',function($query)use($where){
                $merId = app()->make(MerchantRepository::class)->search([
                    'is_trader' => $where['is_trader'],
                    'status' => 1,
                    'mer_state' => 1,
                    'is_del' => 1,
                ])->column('mer_id');

                $query->whereIn('P.mer_id',$merId);
            })
            ->when(isset($where['cate_pid']) && $where['cate_pid'], function ($query) use ($where) {
                $storeCategoryRepository = app()->make(StoreCategoryRepository::class);
                if (is_array($where['cate_pid'])) {
                    $cateIds = $storeCategoryRepository->selectChildrenId($where['cate_pid']);
                } else {
                    $cateIds = $storeCategoryRepository->findChildrenId((int)$where['cate_pid']);
                    $where['cate_pid'] = [$where['cate_pid']];
                }
                $cate = array_merge($cateIds, $where['cate_pid']);
                $query->whereIn('P.cate_id', $cate);
            })
            ->when(isset($where['cate_id']) && $where['cate_id'] !== '', function ($query) use ($where) {
                is_array($where['cate_id']) ? $query->whereIn('P.cate_id', $where['cate_id']) : $query->where('P.cate_id', $where['cate_id']);
            })
            ->when(isset($where['spu_id']) && $where['spu_id'] !== '', function ($query) use ($where) {
                $query->where('S.spu_id',$where['spu_id']);
            })
            ->when(isset($where['spu_ids']) && $where['spu_ids'] !== '', function ($query) use ($where) {
                $query->whereIn('S.spu_id',$where['spu_ids']);
            })
            ->when(isset($where['product_ids']) && !empty($where['product_ids']), function ($query) use ($where) {
                $query->whereIn('P.product_id',$where['product_ids']);
            })
            ->when(isset($where['is_stock']) && !empty($where['is_stock']), function ($query) use ($where) {
                $query->where('P.stock','>',0);
            })
            ->when(isset($where['is_coupon']) && !empty($where['is_coupon']), function ($query) use ($where) {
                $query->whereIn('P.product_type','0,2');
            })
            ->when(isset($where['common']) && $where['common'] !== '', function ($query) use ($where) {
                $query->whereIn('S.product_type', [0, 1]);
            })
            ->when(isset($where['price_on']) && $where['price_on'] !== '',function($query)use($where){
                $query->where('S.price','>=',$where['price_on']);
            })
            ->when(isset($where['price_off']) && $where['price_off'] !== '',function($query)use($where){
                $query->where('S.price','<=',$where['price_off']);
            })
            ->when(isset($where['brand_id']) && $where['brand_id'] !== '', function ($query) use ($where) {
                $query->whereIn('P.brand_id', array_map('intval', explode(',', $where['brand_id'])));
            })
            ->when(isset($where['is_gift_bag']) && $where['is_gift_bag'] !== '',function($query)use($where){
                $query->where('P.is_gift_bag',$where['is_gift_bag']);
            })
            ->when(isset($where['product_type']) && $where['product_type'] !== '',function($query)use($where){
                $query->where('S.product_type',$where['product_type']);
            })
            ->when(isset($where['not_type']) && $where['not_type'] !== '',function($query)use($where){
                $query->whereNotIn('S.product_type',$where['not_type']);
            })
            ->when(isset($where['action']) && $where['action'] !== '',function($query)use($where){
                $query->where('S.product_type','>',0)->where('S.mer_id','<>',0);
            })
            ->when(isset($where['mer_cate_id']) && $where['mer_cate_id'] !== '',function($query)use($where){
                $ids = (StoreCategory::where('path','like','%/'.$where['mer_cate_id'].'/%'))->column('store_category_id');
                $ids[] = intval($where['mer_cate_id']);
                $ids = array_unique($ids);
                $productId = ProductCate::where('mer_cate_id', 'in', $ids)->column('product_id');
                $productId = array_unique($productId);
                $query->where('P.product_id','in',$productId);
            })
            ->when(isset($where['mer_status']) && $where['mer_status'] !== '',function($query)use($where){
                $query->where('mer_status',$where['mer_status']);
            })
            ->when(isset($where['spu_status']) && $where['spu_status'] !== '',function($query)use($where){
                $query->where('S.status',$where['spu_status']);
            })
            ->when(isset($where['sys_labels']) && $where['sys_labels'] !== '',function($query)use($where){
                $query->whereLike('S.sys_labels',"%,{$where['sys_labels']},%");
            })
            ->when(isset($where['mer_labels']) && $where['mer_labels'] !== '',function($query)use($where){
                $query->whereLike('S.mer_labels',"%,{$where['mer_labels']},%");
            })
            ->when(isset($where['pid']) && $where['pid'] !== '', function ($query) use ($where) {
                $query->join('StoreCategory CT','P.cate_id = CT.store_category_id')->where('CT.pid',$where['pid']);
            })
            ->when(isset($where['delivery_way']) && $where['delivery_way'] !== '', function ($query) use ($where) {
                $query->whereLike('P.delivery_way',"%{$where['delivery_way']}%");
            })
            ->when(isset($where['scope']) && $where['scope'] !== '', function ($query) use ($where) {
                $scope = explode(',', $where['scope']);
                if ($scope[1] <= 0) {
                    $query->where('S.ot_price','<',$where['scope']);
                } else {
                    $query->where('S.ot_price','between',$scope);
                }
            })
            ->when(isset($where['hot_type']) && $where['hot_type'] !== '', function ($query) use ($where) {
                if ($where['hot_type'] == 'new') $query->where('P.is_new', 1);
                else if ($where['hot_type'] == 'hot') $query->where('P.is_hot', 1);
                else if ($where['hot_type'] == 'best') $query->where('P.is_best', 1);
                else if ($where['hot_type'] == 'good') $query->where('P.is_benefit', 1);
            })
            ->when(isset($where['svip']) && $where['svip'] !== '',function($query)use($where){
                $query->where('svip_price_type','>',0)->where('mer_svip_status',1);
            });
        return $query->order($order);
    }

    public function findOrCreateAll(array $where)
    {
        foreach ($where as $item) {
            $item['activity_id'] = $item['activity_id'] ?? 0;
            $data = $this->getModel()::getDB()->where('product_id', $item['product_id'])
                ->where('product_type', $item['product_type'])
                ->where('activity_id', $item['activity_id'])
                ->find();
            if (!$data) $this->create($item);
        }
    }

    public function delProduct($id, $isDel = 1)
    {
        $this->getModel()::getDb()->where('product_id', $id)->update(['is_del' => $isDel]);
    }

    public function getActivecategory($type)
    {
        $query = Spu::getDB()->alias('S')->join('StoreProduct P','S.product_id = P.product_id')
            ->join('StoreCategory C','C.store_category_id = P.cate_id');

        $query->where('S.status',1)->where('S.product_type',$type)->where('C.is_show',1);
        return $query->group('S.product_id')->column('C.path');
    }

    /**
     * TODO 软删除商户的所有商品
     * @param $merId
     * @author Qinii
     * @day 5/15/21
     */
    public function clearProduct($merId)
    {

        $this->getModel()::getDb()->where('mer_id', $merId)->update(['is_del' => 1]);
    }

    public function clear(int $id, string $field)
    {
        $this->getModel()::getDB()->where($field, $id)->delete();
    }
}
