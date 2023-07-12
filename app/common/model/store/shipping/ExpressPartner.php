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

namespace app\common\model\store\shipping;

use app\common\model\BaseModel;

class ExpressPartner extends BaseModel
{
    /**
     * @Author:Qinii
     * @return string
     */
    public  static function tablePk():string
    {
        return 'id';
    }

    /**
     * @Author:Qinii
     * @return string
     */
    public static function tableName():string
    {
        return 'express_partner';
    }

    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }

    public function searchExpressIdAttr($query,$value)
    {
        $query->where('express_id',$value);
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }

}
