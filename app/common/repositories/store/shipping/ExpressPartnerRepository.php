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

namespace app\common\repositories\store\shipping;

use app\common\repositories\BaseRepository;
use app\common\dao\store\shipping\ExpressPartnerDao as dao;
use crmeb\services\CrmebServeServices;
use FormBuilder\Factory\Elm;
use think\facade\Db;
use think\facade\Route;

class ExpressPartnerRepository extends BaseRepository
{

    /**
     * ExpressRepository constructor.
     * @param dao $dao
     */
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }

}
