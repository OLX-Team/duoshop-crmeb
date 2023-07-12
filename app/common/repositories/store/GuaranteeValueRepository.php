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
namespace app\common\repositories\store;

use app\common\dao\store\GuaranteeValueDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\facade\Route;

class GuaranteeValueRepository extends BaseRepository
{
    /**
     * @var GuaranteeValueDao
     */
    protected $dao;


    /**
     * GuaranteeRepository constructor.
     * @param GuaranteeValueDao $dao
     */
    public function __construct(GuaranteeValueDao $dao)
    {
        $this->dao = $dao;
    }
}
