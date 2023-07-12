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

use app\common\repositories\store\pionts\PointsProductRepository;
use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\store\product\StoreDiscountProductRepository;
use app\common\repositories\store\product\StoreDiscountRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use crmeb\basic\BaseController;
use think\App;

class PointsProduct extends BaseController
{

    protected  $repository ;

    /**
     * Product constructor.
     * @param App $app
     * @param StoreDiscountRepository $repository
     */
    public function __construct(App $app ,PointsProductRepository  $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function home()
    {
        $banner = app()->make(GroupDataRepository::class)->groupData('points_mall_banner',0,1,20);
        $district = app()->make(GroupDataRepository::class)->groupData('points_mall_district',0,1,40);
        return app('json')->success(compact('banner','district'));
    }

    public function points_mall_scope()
    {
        [$page, $limit] = $this->getPage();
        $scope = app()->make(GroupDataRepository::class)->groupData('points_mall_scope',0,$page,$limit);
//        foreach ($scope as $k => $v) {
//            if ($v['min'] == 0) {
//                $scope[$k]['title'] = $v['max']. '积分以下';
//            } elseif($v['max'] == 0) {
//                $scope[$k]['title'] = $v['min']. '积分以上';
//            } else {
//                $scope[$k]['title'] = $v['min'].'~'. $v['max'].'积分';
//            }
//        }
        return app('json')->success($scope);
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['scope',['order','sort'],'price','sales','keyword','cate_id']);
        if ($this->request->param('is_hot',0)) $where['hot_type'] = 'hot';
        $data = $this->repository->getApiSearch($where, $page, $limit);
        return app('json')->success($data);
    }

    public function detail($id)
    {
        $data = $this->repository->show($id);
        return app('json')->success($data);
    }

}
