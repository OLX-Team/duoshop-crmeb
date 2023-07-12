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
use app\common\model\system\notice\SystemNotice;

class SystemNoticeDao extends BaseDao
{

    protected function getModel(): string
    {
        return SystemNotice::class;
    }

    public function search(array $where)
    {
        return SystemNotice::getDB()->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->whereLike('notice_title|notice_content', '%' . $where['keyword'] . '%');
        })->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
            getModelTime($query, $where['date'], 'create_time');
        })->where('is_del', 0);
    }
}
