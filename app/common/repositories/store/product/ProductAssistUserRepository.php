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

namespace app\common\repositories\store\product;

use app\common\repositories\BaseRepository;
use app\common\dao\store\product\ProductAssistUserDao;
use think\exception\ValidateException;

class ProductAssistUserRepository extends BaseRepository
{
    public function __construct(ProductAssistUserDao $dao)
    {
        $this->dao = $dao;
    }

    public function userList(array $where,int $page ,int $limit)
    {
        $query = $this->dao->getSearch($where);
        $count = $query->count();
        $list = $query->page($page,$limit)->select();

        return compact('count','list');
    }
}
