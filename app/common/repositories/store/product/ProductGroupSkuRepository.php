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
use app\common\dao\store\product\ProductGroupSkuDao;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * @mixin ProductGroupSkuDao
 */
class ProductGroupSkuRepository extends BaseRepository
{
    protected $dao;

    /**
     * ProductGroupRepository constructor.
     * @param ProductGroupDao $dao
     */
    public function __construct(ProductGroupSkuDao $dao)
    {
        $this->dao = $dao;
    }

}
