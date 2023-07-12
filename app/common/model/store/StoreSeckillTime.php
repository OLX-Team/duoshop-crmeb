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

namespace app\common\model\store;

use app\common\model\BaseModel;

class StoreSeckillTime extends BaseModel
{

    const ISTIME = [
        0   => '00:00',
        1   => '01:00',
        2   => '02:00',
        3   => '03:00',
        4   => '04:00',
        5   => '05:00',
        6   => '06:00',
        7   => '07:00',
        8   => '08:00',
        9   => '09:00',
        10  => '10:00',
        11  => '11:00',
        12  => '12:00',
        13  => '13:00',
        14  => '14:00',
        15  => '15:00',
        16  => '16:00',
        17  => '17:00',
        18  => '18:00',
        19  => '19:00',
        20  => '20:00',
        21  => '21:00',
        22  => '22:00',
        23  => '23:00',
        24  => '24:00',
    ];

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-07-30
     */
    public static function tablePk(): string
    {
        return 'seckill_time_id';
    }

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-07-30
     */
    public static function tableName(): string
    {
        return 'store_seckill_time';
    }
}
