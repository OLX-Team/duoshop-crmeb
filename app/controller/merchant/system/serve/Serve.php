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


namespace app\controller\merchant\system\serve;

use app\common\repositories\system\serve\ServeMealRepository;
use app\common\repositories\system\serve\ServeOrderRepository;
use crmeb\basic\BaseController;
use think\App;
use think\facade\Cache;

class Serve extends BaseController
{
    /**
     * @var ServeOrderRepository
     */
    protected $repository;

    /**
     * Merchant constructor.
     * @param App $app
     * @param ServeOrderRepository $repository
     */
    public function __construct(App $app, ServeOrderRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getQrCode()
    {
        $sms_info = Cache::get('serve_account');
        if (!$sms_info) {
            return app('json')->fail('平台未登录一号通');
        }
        $data = $this->request->params(['meal_id','pay_type']);
        $ret = $this->repository->QrCode($this->request->merId(),'meal', $data);
        return app('json')->success($ret);
    }

    public function meal()
    {
        $sms_info = Cache::get('serve_account');
        if (!$sms_info) {
            return app('json')->fail('平台未登录一号通');
        }

        [$page, $limit] = $this->getPage();
        $type = $this->request->param( 'type','copy');

        if ($type == 'copy' && systemConfig('copy_product_status') != 2) {
            return app('json')->fail('平台未开启一号通商品复制');
        }

        if ($type == 'dump' && systemConfig('crmeb_serve_dump') != 1) {
            return app('json')->fail('平台未开启一号通电子面单');
        }

        $where['type'] = $type == 'copy' ? 1 : 2;
        $where['status'] = 1;

        $data = app()->make(ServeMealRepository::class)->getList($where, $page, $limit);
        return app('json')->success($data);
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['status', 'type']);
        $where['mer_id'] = $this->request->merId();
        $data = $this->repository->getList($where, $page, $limit);
        return app('json')->success($data);
    }

}
