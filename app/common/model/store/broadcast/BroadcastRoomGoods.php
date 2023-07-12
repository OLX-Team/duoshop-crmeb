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

class BroadcastRoomGoods extends BaseModel
{

    public static function tablePk(): ?string
    {
        return null;
    }

    public static function tableName(): string
    {
        return 'broadcast_room_goods';
    }

    public function goods()
    {
        return $this->hasOne(BroadcastGoods::class, 'broadcast_goods_id', 'broadcast_goods_id');
    }

    public function room()
    {
        return $this->hasOne(BroadcastRoom::class, 'broadcast_room_id', 'broadcast_room_id');
    }
}
