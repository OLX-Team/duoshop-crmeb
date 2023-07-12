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


namespace app\common\repositories\user;


use app\common\dao\user\UserSpreadLogDao;
use app\common\repositories\BaseRepository;

/**
 * @mixin UserSpreadLogDao
 */
class UserSpreadLogRepository extends BaseRepository
{
    public function __construct(UserSpreadLogDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->with(['spread' => function ($query) {
            $query->field('uid,nickname,avatar');
        }])->select();

        return compact('count', 'list');
    }
}
