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
use app\common\model\store\product\ProductGroup;
use app\common\repositories\store\product\SpuRepository;

class ProductGroupDao extends  BaseDao
{
    public function getModel(): string
    {
        return ProductGroup::class;
    }

    public function search($where)
    {
        $query = ProductGroup::hasWhere('product',function($query)use($where){
            $query->where('status',1);
            $query->when(isset($where['keyword']) && $where['keyword'] !== '',function($query)use($where){
                $query->whereLike('store_name',"%{$where['keyword']}%");
            });
        });
        $query->when(isset($where['is_show']) && $where['is_show'] !== '',function($query)use($where){
                $query->where('ProductGroup.is_show',$where['is_show']);
            })
            ->when(isset($where['product_status']) && $where['product_status'] !== '',function($query)use($where){
                if($where['product_status'] == -1){
                    $query->where('ProductGroup.product_status','in',[-1,-2]);
                }else{
                    $query->where('ProductGroup.product_status',$where['product_status']);
                }
            })
            ->when(isset($where['status']) && $where['status'] !== '',function($query)use($where){
                $query->where('ProductGroup.status',$where['status']);
            })
            ->when(isset($where['end_time']) && $where['end_time'] !== '',function($query)use($where){
                $query->whereTime('ProductGroup.end_time','>',$where['end_time']);
            })
            ->when(isset($where['active_type']) && $where['active_type'] !== '',function($query)use($where){
                $query->where('ProductGroup.action_status',$where['active_type']);
            })
            ->when(isset($where['is_trader']) && $where['is_trader'] !== '',function($query)use($where){
                $query->join('Merchant M','M.mer_id = ProductGroup.mer_id')->where('is_trader',$where['is_trader']);
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '',function($query)use($where){
                $query->where('ProductGroup.mer_id',$where['mer_id']);
            })
            ->when(isset($where['product_group_id']) && $where['product_group_id'] !== '',function($query)use($where){
                $query->where('ProductGroup.product_group_id',$where['product_group_id']);
            })
             ->when(isset($where['store_category_id']) && $where['store_category_id'] !== '',function($query)use($where){
                 $query->join('StoreCategory C','Product.cate_id = C.store_category_id')
                     ->whereLike('path',"/{$where['store_category_id']}/%");
             })
            ->when(isset($where['us_status']) && $where['us_status'] !== '',function($query)use($where){
                if($where['us_status'] == 0) {
                    $query->where('ProductGroup.is_show',0)->where('ProductGroup.status',1)->where('ProductGroup.product_status',1);
                }
                if($where['us_status'] == 1) {
                    $query->where('ProductGroup.is_show',1)->where('ProductGroup.status',1)->where('ProductGroup.product_status',1);
                }
                if($where['us_status'] == -1) {
                    $query->where(function($query){
                        $query->where('ProductGroup.status',0)->whereOr('ProductGroup.product_status','<>',1);
                    });
                }
            });

        $query->join('StoreSpu U','ProductGroup.product_group_id = U.activity_id')->where('U.product_type',4);

        $query->when(isset($where['star']) && $where['star'] !== '',function($query)use($where){
                $query->where('U.star',$where['star']);
            })
            ->when(isset($where['level']) && $where['level'] !== '',function($query)use($where) {
                $query->where('U.star',$where['level']);
            })
            ->when(isset($where['mer_labels']) && $where['mer_labels'] !== '', function ($query) use ($where) {
                $query->whereLike('U.mer_labels', "%,{$where['mer_labels']},%");
            })
            ->when(isset($where['sys_labels']) && $where['sys_labels'] !== '', function ($query) use ($where) {
                $query->whereLike('U.sys_labels', "%,{$where['sys_labels']},%");
            });
        if(isset($where['order'])) {
            switch ($where['order']) {
                case 'sort':
                    $order = 'U.sort DESC';
                    break;
                case 'rank':
                    $order = 'U.rank DESC';
                    break;
                case 'star':
                    $order = 'U.star DESC,U.rank DESC';
                    break;
                default:
                    $order = 'U.star DESC,U.rank DESC,U.sort DES';
                    break;
            }
            $query->order($order.',ProductGroup.create_time DESC');
        }

        return $query->where('ProductGroup.is_del',0);
    }

    public function actionShow()
    {
        return [
            'is_show' => 1,
            'action_status' => 1,
            'product_status' => 1,
            'status' => 1,
            'end_time' => time()
        ];
    }

    public function category()
    {
        $query = ProductGroup::alias('G')->join('StoreProduct P','G.product_id = P.product_id')
            ->join('StoreCategory C','P.cate_id = C.store_category_id');
        $query->where('G.is_show',1)->where('G.action_status',1)->where('G.product_status',1);
        $query->group('G.product_id');
        return $query->column('path');
    }

    /**
     * TODO
     * @author Qinii
     * @day 1/27/21
     */
    public function valActiveStatus()
    {
        $query = $this->getModel()::getDB()->whereTime('end_time','<=',time())->where('action_status',1);
        $id = $query->column($this->getPk());
        if($id) {
            $this->getModel()::getDB()->where($this->getPk(),'in',$id)->update(['action_status' => -1]);
            $where = [
                'product_type' => 4,
                'activity_ids' => $id
            ];
            app()->make(SpuRepository::class)->getSearch($where)->update(['status' => 0]);
        }
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
}
