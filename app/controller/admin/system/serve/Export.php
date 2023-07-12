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

namespace app\controller\admin\system\serve;

use app\common\repositories\store\product\ProductCopyRepository;
use app\common\repositories\system\serve\ServeDumpRepository;
use crmeb\services\CrmebServeServices;
use crmeb\services\ExpressService;
use crmeb\basic\BaseController;
use think\App;

/**
 * 一号通平台物流服务
 * Class Export
 * @package app\controller\admin\v1\serve
 */
class Export extends BaseController
{

    protected $services;
    /**
     * Export constructor.
     * @param App $app
     * @param ExpressService $services
     */
    public function __construct(App $app, ExpressService $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 物流公司
     * @return mixed
     */
    public function getExportAll(CrmebServeServices $services)
    {
        [$page, $limit] = $this->getPage();
        $ret = $services->express()->express(1);
        $data['count'] = $ret['count'];
        $data['list']  = $ret['data'];
        return app('json')->success($data);
    }

    /**
     *
     * 获取面单信息
     * @param string $com
     * @return mixed
     */
    public function getExportTemp(CrmebServeServices $services)
    {
        $com = $this->request->param('com');
        if(!$com) return app('json')->fail('请输入快递公司编号');
        return app('json')->success($services->express()->temp($com));
    }


    public function dumpLst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['date','mer_id']);
        if($this->request->merId()) $where['mer_id'] = $this->request->merId();
        $where['type'] = 'mer_dump';
        $make = app()->make(ProductCopyRepository::class);
        $data = $make->getList($where, $page, $limit);

        return app('json')->success($data);
    }


}
