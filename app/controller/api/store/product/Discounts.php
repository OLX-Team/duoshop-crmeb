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

use app\common\repositories\store\product\StoreDiscountProductRepository;
use app\common\repositories\store\product\StoreDiscountRepository;
use crmeb\basic\BaseController;
use think\App;

class Discounts extends BaseController
{

    protected  $repository ;

    /**
     * Product constructor.
     * @param App $app
     * @param StoreDiscountRepository $repository
     */
    public function __construct(App $app ,StoreDiscountRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        $id = $this->request->param('product_id',0);
        $where = [
            'status' => 1,
            'is_show'=> 1,
            'end_time' => 1,
            'is_del' => 0,
        ];

        if ($id){
            $discount_id = app()->make(StoreDiscountProductRepository::class)
                ->getSearch(['product_id' => $id])
                ->column('discount_id');
            $where['discount_id'] = $discount_id;
        }
        $data = $this->repository->getApilist($where);
        return app('json')->success($data);
    }


}
