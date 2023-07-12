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


namespace app\common\dao\store;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\store\StoreActivity;
use app\common\repositories\system\RelevanceRepository;

/**
 *
 * Class StoreActivityDao
 * @package app\common\dao\system\merchant
 */
class StoreActivityDao extends BaseDao
{
    protected function getModel(): string
    {
        return StoreActivity::class;
    }

    public function search(array $where = [])
    {
        $where['is_del'] = 0;
        return $this->getSearch($where);
    }
}
