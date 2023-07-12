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

namespace app\controller\merchant\store\product;

use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\validate\merchant\StoreProductValidate as validate;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\product\ProductCopyRepository as repository;

class ProductCopy extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;

    /**
     * ProductCopy constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app ,repository $repository)
    {
        $this->repository = $repository;
        parent::__construct($app);
    }

    /**
     * TODO 列表
     * @return mixed
     * @author Qinii
     * @day 2020-08-14
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();

        $where['mer_id'] = $this->request->param('mer_id');
        $mer_id = $this->request->merId();
        if ($mer_id){
            $where['mer_id'] = $this->request->merId();
        }
        $where['type'] = $this->request->param('type','copy');

        return app('json')->success($this->repository->getList($where,$page, $limit));
    }

    /**
     * TODO
     * @return mixed
     * @author Qinii
     * @day 2020-08-07
     */
    public function count()
    {
        $count = $this->request->merchant()->copy_product_num;
        return app('json')->success(['count' => $count]);
    }

    /**
     * TODO 复制商品
     * @return mixed
     * @author Qinii
     * @day 2020-08-06
     */
    public function get()
    {
        $status = systemConfig('copy_product_status');
        if($status == 0) return app('json')->fail('请前往平台后台-设置-第三方接口-开启采集');
        $num = app()->make(MerchantRepository::class)->getCopyNum($this->request->merId());
        if($num <= 0) return app('json')->fail('复制商品次数已用完');
        $url = $this->request->param('url');
        if (!$url) return app('json')->fail('请输入采集链接');
        $res = $this->repository->getProduct($url,$this->request->merId());
        return app('json')->success($res);
    }

}
