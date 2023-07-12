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


namespace app\common\repositories\system\notice;


use app\common\dao\system\notice\SystemNoticeLogDao;
use app\common\repositories\BaseRepository;

/**
 * Class SystemNoticeLogRepository
 * @package app\common\repositories\system\notice
 * @author xaboy
 * @day 2020/11/6
 * @mixin SystemNoticeLogDao
 */
class SystemNoticeLogRepository extends BaseRepository
{
    public function __construct(SystemNoticeLogDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('A.notice_log_id DESC')->select();
        return compact('count', 'list');
    }

}
