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

namespace app\controller\merchant\store\shipping;

use app\common\repositories\store\CityAreaRepository;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\shipping\CityRepository as repository;
use think\facade\Log;

class City extends BaseController
{
    protected $repository;

    /**
     * City constructor.
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
     * @Date: 2020/5/8
     * @Time: 14:40
     * @return mixed
     */
    public function lst()
    {
        return app('json')->success($this->repository->getFormatList([['is_show', '=', 1],['level','<',2]]));
    }

    public function lstV2($pid)
    {
        return app('json')->success(app()->make(CityAreaRepository::class)->getChildren(intval($pid)));
    }

    public function cityList()
    {
        $address = $this->request->param('address');
        if (!$address)
            return app('json')->fail('地址不存在');
        $make = app()->make(CityAreaRepository::class);
        $city = $make->search(compact('address'))->order('id DESC')->find();
        if (!$city){
            Log::info('用户定位对比失败，请在城市数据中增加:'.var_export($address,true));
            return app('json')->fail('地址不存在');
        }
        return app('json')->success($make->getCityList($city));
    }


    /**
     * @return mixed
     * @author Qinii
     */
    public function getlist()
    {
        return app('json')->success($this->repository->getFormatList(['is_show' => 1]));
    }
}
