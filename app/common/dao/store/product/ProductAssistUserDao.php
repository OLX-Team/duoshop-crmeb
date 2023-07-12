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

namespace app\common\dao\store\product;

use app\common\dao\BaseDao;
use app\common\model\store\product\ProductAssistUser;

class ProductAssistUserDao extends BaseDao
{
    protected function getModel(): string
    {
        return ProductAssistUser::class;
    }


    public function userCount(int $limit = 3)
    {
        $count = $this->getModel()::getDB()->count("*");
        $list = $this->getModel()::getDB()->limit(3)->order('create_time DESC')->select();
        return compact('count','list');
    }
}

