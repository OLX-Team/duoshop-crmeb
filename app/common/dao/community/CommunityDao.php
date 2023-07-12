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


namespace app\common\dao\community;


use app\common\dao\BaseDao;
use app\common\model\community\Community;
use app\common\model\system\Relevance;
use app\common\repositories\system\RelevanceRepository;

class CommunityDao extends BaseDao
{

    protected function getModel(): string
    {
        return Community::class;
    }

    public function search(array $where)
    {
        $query = Community::hasWhere('author', function($query) use ($where){
            $query->when(isset($where['username']) && $where['username'] !==  '', function ($query) use($where) {
                $query->whereLike('real_name|phone|nickname',"%{$where['username']}%");
            });
            $query->where(true);
        });
        $query
            ->when(isset($where['keyword']) && $where['keyword'] !==  '', function ($query) use($where) {
                $query->whereLike('Community.title',"%{$where['keyword']}%");
            })
            ->when(isset($where['uid']) && $where['uid'] !==  '', function ($query) use($where) {
                $query->where('Community.uid',$where['uid']);
            })
            ->when(isset($where['uids']) && $where['uids'] !==  '', function ($query) use($where) {
                $query->whereIn('Community.uid',$where['uids']);
            })
            ->when(isset($where['topic_id']) && $where['topic_id'] !==  '', function ($query) use($where) {
                $query->where('Community.topic_id',$where['topic_id']);
            })
            ->when(isset($where['community_id']) && $where['community_id'] !==  '', function ($query) use($where) {
                $query->where('Community.community_id',$where['community_id']);
            })
            ->when(isset($where['not_id']) && $where['not_id'] !==  '', function ($query) use($where) {
                $query->whereNotIn('Community.community_id',$where['not_id']);
            })
            ->when(isset($where['community_ids']) && $where['community_ids'] !==  '', function ($query) use($where) {
                $query->whereIn('Community.community_id',$where['community_ids']);
            })
            ->when(isset($where['is_type']) && $where['is_type'] !==  '', function ($query) use($where) {
                $query->whereIn('Community.is_type',$where['is_type']);
            })
            ->when(isset($where['is_show']) && $where['is_show'] !==  '', function ($query) use($where) {
                $query->where('Community.is_show',$where['is_show']);
            })
            ->when(isset($where['status']) && $where['status'] !==  '', function ($query) use($where) {
                $query->where('Community.status',$where['status']);
            })
            ->when(isset($where['start']) && $where['start'] !==  '', function ($query) use($where) {
                $query->where('Community.start',$where['start']);
            })
            ->when(isset($where['is_del']) && $where['is_del'] !==  '', function ($query) use($where) {
                $query->where('Community.is_del',$where['is_del']);
            })
            ->when(isset($where['category_id']) && $where['category_id'] !==  '', function ($query) use($where) {
                $query->where('Community.category_id',$where['category_id']);
            })
            ->when(isset($where['spu_id']) && $where['spu_id'] !==  '', function ($query) use($where) {
                $id = Relevance::where('right_id', $where['spu_id'])
                    ->where('type',RelevanceRepository::TYPE_COMMUNITY_PRODUCT)
                    ->column('left_id');
                $query->where('community_id','in', $id);
            });

        $order = 'Community.create_time DESC';

        if (isset($where['order']) && $where['order'] == 'start') {
            $order = 'Community.start DESC,Community.create_time DESC';
        }

        $query->order($order);
        return $query;
    }

    public function uidExists(int $id, int $uid)
    {
        return $this->getModel()::getDb()->where('uid',$uid)->where($this->getPk(),$id)->count() > 0;
    }

    public function exists(int $id)
    {
        return $this->getModel()::getDb()->where('is_del',0)->where($this->getPk(),$id)->count() > 0;
    }

    public function destoryByUid($uid)
    {
        return $this->getModel()::getDb()->where('uid' ,$uid)->update(['is_del' =>  1]);
    }

    public function joinUser($where)
    {
        return Community::hasWhere('relevanceRight',function($query) use($where){
            $query->where('type',RelevanceRepository::TYPE_COMMUNITY_START)->where('left_id',$where['uid']);
        })
            ->when(isset($where['is_type']) && $where['is_type'] !==  '', function ($query) use($where) {
                $query->whereIn('Community.is_type',$where['is_type']);
            })
            ->when(isset($where['is_show']) && $where['is_show'] !==  '', function ($query) use($where) {
                $query->where('Community.is_show',$where['is_show']);
            })
            ->when(isset($where['status']) && $where['status'] !==  '', function ($query) use($where) {
                $query->where('Community.status',$where['status']);
            })
            ->when(isset($where['is_del']) && $where['is_del'] !==  '', function ($query) use($where) {
                $query->where('Community.is_del',$where['is_del']);
            });
    }
}
