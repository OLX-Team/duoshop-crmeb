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

namespace app\common\dao\user;

use app\common\dao\BaseDao;
use app\common\model\user\FeedBackCategory as model;
use crmeb\traits\CategoresDao;

class FeedbackCateoryDao extends BaseDao
{

    use CategoresDao;

    /**
     * @return string
     * @author Qinii
     */
    protected function getModel(): string
    {
        return model::class;
    }

    /**
     * @return int
     * @author Qinii
     */
    public function getMaxLevel()
    {
        return 2;
    }

}
