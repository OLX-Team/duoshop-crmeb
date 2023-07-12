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


namespace app\common\dao\system\notice;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\system\notice\SystemNoticeLog;

/**
 * Class SystemNoticeLogDao
 * @package app\common\dao\system\notice
 * @author xaboy
 * @day 2020/11/6
 */
class SystemNoticeLogDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/11/6
     */
    protected function getModel(): string
    {
        return SystemNoticeLog::class;
    }

    /**
     * @param $id
     * @param $merId
     * @return int
     * @throws \think\db\exception\DbException
     * @author xaboy
     * @day 2020/11/6
     */
    public function read($id, $merId)
    {
        return SystemNoticeLog::getDB()->where('notice_log_id', $id)->where('mer_id', $merId)->update(['is_read' => 1, 'read_time' => date('Y-m-d H:i:s')]);
    }

    public function unreadCount($merId)
    {
        return SystemNoticeLog::getDB()->where('mer_id', $merId)->where('is_read', 0)->count();
    }

    /**
     * @param $id
     * @param $merId
     * @return int
     * @throws \think\db\exception\DbException
     * @author xaboy
     * @day 2020/11/6
     */
    public function del($id, $merId)
    {
        return SystemNoticeLog::getDB()->where('notice_log_id', $id)->where('mer_id', $merId)->delete();
    }

    public function search(array $where)
    {
        return SystemNoticeLog::getDB()->alias('A')->join('SystemNotice B', 'A.notice_id = B.notice_id')->where('mer_id', $where['mer_id'])->when(isset($where['is_read']) && $where['is_read'] !== '', function ($query) use ($where) {
            $query->where('A.is_read', intval($where['is_read']));
        })->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
            getModelTime($query, $where['date'], 'B.create_time');
        })->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->whereLike('B.notice_title|B.notice_content', "%{$where['keyword']}%");
        })->where('A.is_del', 0);
    }
}
