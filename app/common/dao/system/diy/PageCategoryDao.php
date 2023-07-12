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


namespace app\common\dao\system\diy;

use app\common\dao\BaseDao;
use app\common\model\system\diy\PageCategory;
use app\common\model\system\diy\PageLink;

class PageCategoryDao extends BaseDao
{

    protected function getModel(): string
    {
        return PageCategory::class;
    }

}
