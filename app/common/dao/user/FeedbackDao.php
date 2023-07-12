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


namespace app\common\dao\user;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\user\Feedback;
use think\db\BaseQuery;

/**
 * Class FeedbackDao
 * @package app\common\dao\user
 * @author xaboy
 * @day 2020/5/28
 */
class FeedbackDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/5/28
     */
    protected function getModel(): string
    {
        return Feedback::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020/5/28
     */
    public function search(array $where)
    {
        return Feedback::getDB()->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
            $query->where('uid', $where['uid']);
        })->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->whereLike('content|reply|remake|realname|contact', '%'.$where['keyword'].'%');
        })->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('type',$where['type']);
        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            $query->where('status', $where['status']);
        })->when(isset($where['realname']) && $where['realname'] !== '', function ($query) use ($where) {
            $query->where('realname','like', '%'.$where['realname'].'%');
        })->when(isset($where['is_del']) && $where['is_del'] !== '', function ($query) use ($where) {
            $query->where('is_del',$where['is_del']);
        })->order('create_time DESC');
    }

    /**
     * @param $id
     * @param $uid
     * @return bool
     * @author xaboy
     * @day 2020/5/28
     */
    public function uidExists($id, $uid): bool
    {
        return Feedback::getDB()->where($this->getPk(), $id)->where('uid', $uid)->where('is_del', 0)->count($this->getPk()) > 0;
    }

    public function merExists(int $id)
    {
        return $this->getModel()::getDB()->where($this->getPk(), $id)->where('is_del', 0)->count() > 0;
    }
}
