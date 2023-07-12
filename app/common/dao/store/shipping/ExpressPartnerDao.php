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

namespace app\common\dao\store\shipping;

use app\common\dao\BaseDao;
use app\common\model\store\shipping\ExpressPartner as model;

class ExpressPartnerDao  extends BaseDao
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 7/23/21
     */
    protected function getModel(): string
    {
        return model::class;
    }
}
