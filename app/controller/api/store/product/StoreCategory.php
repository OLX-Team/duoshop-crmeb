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

namespace app\controller\api\store\product;

use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\StoreCategoryRepository as repository;

class StoreCategory extends BaseController
{
    protected $repository;

    /**
     * ProductCategory constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/27
     * @return mixed
     */
    public function lst()
    {
        $data = $this->repository->getHot(0);
        $list = $this->repository->getApiFormatList(0,1);
        $ret =[];
        foreach ($list as $key => $value) {
            if (isset($value['children'])) {
                $level = [];
                foreach ($value['children'] as $child) {
                    if (isset($child['children'])) {
                        $level[] = $child;
                    }
                }
                if (isset($level) && !empty($level)) {
                    $value['children'] = $level;
                    $ret[] = $value;
                }
            }
        }
        $data['list'] = $ret;
        return app('json')->success($data);
    }

    public function children()
    {
        $pid = (int)$this->request->param('pid');

        return app('json')->success($this->repository->children($pid));
    }

    public function cateHotRanking()
    {
        $data = $this->repository->getSearch(['level' => systemConfig('hot_ranking_lv') ?:0, 'mer_id' => 0,'is_show' => 1])->order('sort DESC,create_time DESC')->select();
        return app('json')->success($data);
    }
}
