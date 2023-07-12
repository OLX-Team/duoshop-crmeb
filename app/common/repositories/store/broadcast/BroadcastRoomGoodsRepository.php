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


namespace app\common\repositories\store\broadcast;


use app\common\dao\store\broadcast\BroadcastRoomGoodsDao;
use app\common\repositories\BaseRepository;

/**
 * Class BroadcastRoomGoodsRepository
 * @package app\common\repositories\store\broadcast
 * @author xaboy
 * @day 2020/7/31
 * @mixin BroadcastRoomGoodsDao
 */
class BroadcastRoomGoodsRepository extends BaseRepository
{
    public function __construct(BroadcastRoomGoodsDao $dao)
    {
        $this->dao = $dao;
    }
}
