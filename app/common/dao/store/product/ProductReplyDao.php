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
use app\common\model\store\product\ProductReply;
use crmeb\jobs\UpdateProductReplyJob;
use think\db\BaseQuery;
use think\db\exception\DbException;
use think\facade\Db;
use think\facade\Queue;

/**
 * Class ProductReplyDao
 * @package app\common\dao\store\product
 * @author xaboy
 * @day 2020/5/30
 */
class ProductReplyDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/5/30
     */
    protected function getModel(): string
    {
        return ProductReply::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020/6/1
     */
    public function search(array $where)
    {
        return ProductReply::getDB()->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('mer_id', $where['mer_id']);
        })->when(isset($where['is_reply']) && $where['is_reply'] !== '', function ($query) use ($where) {
            $query->where('is_reply', $where['is_reply']);
        })->when(isset($where['is_virtual']) && $where['is_virtual'] !== '', function ($query) use ($where) {
            $query->where('is_virtual', $where['is_virtual']);
        })->when(isset($where['nickname']) && $where['nickname'] !== '', function ($query) use ($where) {
            $query->whereLike('nickname', "%{$where['nickname']}%");
        })->when(isset($where['product_id']) && $where['product_id'] !== '', function ($query) use ($where) {
            $query->where('product_id', $where['product_id']);
        })->when(isset($where['product_type']) && $where['product_type'] !== '', function ($query) use ($where) {
            $query->where('product_type', 'product_type');
        })->when(isset($where['is_del']) && $where['is_del'] !== '', function ($query) use ($where) {
            $query->where('is_del', $where['is_del']);
        })->order('sort DESC');
    }

    public function searchJoinQuery(array $where)
    {
        return ProductReply::getDB()->alias('A')
            ->join('StoreProduct B', 'A.product_id = B.product_id')
            ->when(isset($where['is_reply']) && $where['is_reply'] !== '', function ($query) use ($where) {
                $query->where('A.is_reply', $where['is_reply']);
            })
            ->when(isset($where['nickname']) && $where['nickname'] !== '', function ($query) use ($where) {
                $query->whereLike('A.nickname', "%{$where['nickname']}%");
            })
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->where(function ($query) use ($where) {
                    $query->where('B.store_name', 'like', "%{$where['keyword']}%")
                        ->whereOr('B.product_id', $where['keyword']);
                });
            })
            ->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date'], 'A.create_time');
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
                $query->where('A.mer_id', $where['mer_id']);
            })
            ->when(isset($where['product_id']) && $where['product_id'] !== '', function ($query) use ($where) {
                $query->where('A.product_id', $where['product_id']);
            })
            ->order('A.sort DESC, A.create_time DESC')
            ->where('A.is_del', 0)
            ->field('A.reply_id,A.is_reply,A.uid,A.product_score,A.service_score,A.postage_score,A.comment,A.pics,A.create_time,A.merchant_reply_content,A.nickname,A.avatar,B.store_name,B.image,B.product_id,A.sort');
    }

    /**
     * @param array $data
     * @return int
     * @author xaboy
     * @day 2020/5/30
     */
    public function insertAll(array $data)
    {
        return ProductReply::getDB()->insertAll($data);
    }

    /**
     * @param int $id
     * @return bool
     * @author xaboy
     * @day 2020/5/30
     */
    public function exists(int $id)
    {
        return ProductReply::getDB()->where($this->getPk(), $id)->where('is_del', 0)->count() > 0;
    }

    /**
     * @param $merId
     * @param int $id
     * @return bool
     * @author xaboy
     * @day 2020/6/28
     */
    public function merExists($merId, int $id)
    {
        return ProductReply::getDB()->where($this->getPk(), $id)->where('is_del', 0)->where('mer_id', $merId)->count() > 0;
    }

    /**
     * @param int $id
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020/5/30
     */
    public function delete(int $id)
    {
        $reply = ProductReply::getDB()->where('reply_id', $id)->find();
        $reply->is_del = 1;
        $reply->save();
        Queue::push(UpdateProductReplyJob::class, $reply['product_id']);
    }

    /**
     * 返回评论数
     * @Author:Qinii
     * @Date: 2020/6/2
     * @param int $productId
     * @param array $where
     * @return mixed
     */
    public function getProductReplay(int $productId, $where = [0, 5])
    {
        return $this->getModel()::getDB()->where('product_id', $productId)->whereBetween('rate', $where)->select();
    }

    public function productTotalRate($productId)
    {
        return ProductReply::getDB()->where('product_id', $productId)->where('is_del', 0)->field('sum(rate) as total_rate,count(reply_id) as total_count')->find();
    }

    /**
     * 计算商铺平均分
     * @param $merId
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function merchantTotalRate($merId)
    {
        return ($this->getModel()::getDB())->where('mer_id', $merId)->field('avg(product_score) product_score ,avg(service_score) service_score,avg(postage_score) postage_score')->find()->toArray();
    }

    public function clear($id,$field)
    {
        $this->getModel()::getDB()->where($field, $id)->delete();
    }

}

