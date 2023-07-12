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

namespace app\controller\api\community;

use crmeb\basic\BaseController;
use crmeb\traits\CategoresRepository;
use think\App;
use app\validate\admin\StoreCategoryValidate;
use app\common\repositories\community\CommunityCategoryRepository as repository;
use think\exception\ValidateException;

class CommunityCategory extends BaseController
{
    /**
     * @var CommunityCategoryRepository
     */
    protected $repository;

    /**
     * User constructor.
     * @param App $app
     * @param  $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        if (!systemConfig('community_status') ) throw  new ValidateException('未开启社区功能');
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 10/27/21
     */
    public function lst()
    {
        return app('json')->success($this->repository->getApiList());
    }
}
