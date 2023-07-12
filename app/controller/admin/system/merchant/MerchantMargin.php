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


namespace app\controller\admin\system\merchant;

use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\system\serve\ServeOrderRepository;
use app\common\repositories\user\UserBillRepository;
use crmeb\basic\BaseController;
use think\App;

class MerchantMargin extends BaseController
{

    /**
     * MerchantMargin constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * TODO
     * @param ServeOrderRepository $orderRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 1/26/22
     */
    public function lst(MerchantRepository $orderRepository)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['date','keyword','is_trader','category_id','type_id']);
        $where['margin'] = 10;
        $data = $orderRepository->getPaidToMarginLst($where, $page, $limit);
        return app('json')->success($data);
    }

    public function getMarginLst($id)
    {
        [$page, $limit] = $this->getPage();
        $where = [
            'mer_id' => $id,
            'category' => 'mer_margin'
        ];
        $data = app()->make(UserBillRepository::class)->getLst($where, $page, $limit);
        return app('json')->success($data);
    }

    /**
     * TODO 扣除保证金
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 2023/4/25
     */
    public function setMarginForm($id)
    {
        $data = app()->make(MerchantRepository::class)->setMarginForm($id);
        return app('json')->success(formToData($data));
    }

    public function setMargin()
    {
        $data = $this->request->params(['mer_id','number',['type','mer_margin'],'mark']);
        $data['title'] = '保证金扣除';
        if ($data['number'] < 0)
            return app('json')->fail('扣除金额不能小于0');
        app()->make(MerchantRepository::class)->setMargin($data);
        return app('json')->success('扣除保证金成功');
    }

    public function localMarginForm($id)
    {
        $data = app()->make(MerchantRepository::class)->localMarginForm($id);
        return app('json')->success(formToData($data));
    }

    public function localMarginSet($id)
    {
        $data = $this->request->params(['number','mark','status']);
        app()->make(MerchantRepository::class)->localMarginSet($id, $data);
        return app('json')->success('操作成功');
    }
}
