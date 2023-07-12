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
use app\common\dao\store\shipping\CityDao as dao;

class CityRepository extends BaseRepository
{

    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return array
     */
    public function getFormatList(array $where)
    {
        return  formatCategory($this->dao->getAll($where)->toArray(), 'city_id','parent_id');
    }




}
