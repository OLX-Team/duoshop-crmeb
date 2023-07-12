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


namespace app\common\model\store\broadcast;


use app\common\model\BaseModel;
use app\common\model\system\merchant\Merchant;

/**
 * Class BroadcastRoom
 * @package app\common\model\store\broadcast
 * @author xaboy
 * @day 2020/7/29
 */
class BroadcastRoom extends BaseModel
{

    /**
     * @return string|null
     * @author xaboy
     * @day 2020/7/29
     */
    public static function tablePk(): ?string
    {
        return 'broadcast_room_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020/7/29
     */
    public static function tableName(): string
    {
        return 'broadcast_room';
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }

    public function broadcast()
    {
        return $this->hasMany(BroadcastRoomGoods::class)->where('on_sale', 1);
    }
}
