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


namespace app\common\dao\store\broadcast;


use app\common\dao\BaseDao;
use app\common\model\store\broadcast\BroadcastGoods;
use app\common\repositories\system\merchant\MerchantRepository;
use think\db\BaseQuery;
use think\db\exception\DbException;

/**
 * Class BroadcastGoodsDao
 * @package app\common\dao\store\broadcast
 * @author xaboy
 * @day 2020/7/29
 */
class BroadcastGoodsDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/7/29
     */
    protected function getModel(): string
    {
        return BroadcastGoods::class;
    }

    /**
     * @param int $id
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020/7/30
     */
    public function delete(int $id)
    {
        return $this->update($id, ['is_del' => 1]);
    }

    /**
     * @param int $id
     * @return bool
     * @author xaboy
     * @day 2020/7/30
     */
    public function exists(int $id)
    {
        return $this->existsWhere(['broadcast_goods_id' => $id, 'is_del' => 0]);
    }

    /**
     * @param int $id
     * @param int $merId
     * @return bool
     * @author xaboy
     * @day 2020/7/30
     */
    public function merExists(int $id, int $merId)
    {
        return $this->existsWhere(['broadcast_goods_id' => $id, 'is_del' => 0, 'is_mer_del' => 0, 'mer_id' => $merId]);
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020/7/30
     */
    public function search(array $where)
    {
        if (isset($where['is_trader']) && $where['is_trader'] !== '') {
            $query = BroadcastGoods::hasWhere('merchant', function ($query) use ($where) {
                $query->where('is_trader', $where['is_trader']);
            });
        } else {
            $query = BroadcastGoods::getDB()->alias('BroadcastGoods');
        }
        $query->when(isset($where['mer_id']), function ($query) use ($where) {
            $query->where('BroadcastGoods.mer_id', $where['mer_id']);
        })->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->whereLike('goods_id|mark|name|broadcast_goods_id', "%{$where['keyword']}%");
        })->when(isset($where['valid']) && $where['valid'] !== '', function ($query) use ($where) {
            $query->where('BroadcastGoods.is_show', 1);
        })->when(isset($where['mer_valid']) && $where['mer_valid'] !== '', function ($query) use ($where) {
            $query->where('BroadcastGoods.is_show', 1)->where('BroadcastGoods.is_mer_show', 1);
        })->when(isset($where['broadcast_goods_id']) && $where['broadcast_goods_id'] !== '', function ($query) use ($where) {
            $query->where('BroadcastGoods.broadcast_goods_id', $where['broadcast_goods_id']);
        })->when(isset($where['status_tag']) && $where['status_tag'] !== '', function ($query) use ($where) {
            if ($where['status_tag'] == 1) {
                $query->where('BroadcastGoods.status', 2);
            } else if ($where['status_tag'] == -1) {
                $query->where('BroadcastGoods.status', -1);
            } else if ($where['status_tag'] == 0) {
                $query->whereIn('BroadcastGoods.status', [0, 1]);
            }
        })->where('BroadcastGoods.is_del', 0)->where('BroadcastGoods.is_mer_del', 0);
        return $query;
    }

    public function goodsStatusAll()
    {
        return BroadcastGoods::getDB()->where('goods_id', '>', 0)->whereIn('audit_status', [0, 1])->column('audit_status', 'goods_id');
    }

    public function updateGoods($goods_id, $data)
    {
        return BroadcastGoods::getDB()->where('goods_id', $goods_id)->update($data);
    }

    public function goodsList($merId, array $ids)
    {
        return BroadcastGoods::getDB()->whereIn('broadcast_goods_id', $ids)->where('mer_id', $merId)->where('is_show', 1)->where('is_mer_show', 1)->where('is_del', 0)->where('status', 2)->select();
    }

    public function merDelete(int $id)
    {
        return $this->update($id, ['is_mer_del' => 1]);
    }
}
