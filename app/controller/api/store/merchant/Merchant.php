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

namespace app\controller\api\store\merchant;

use app\common\repositories\user\UserMerchantRepository;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantRepository as repository;

class Merchant extends BaseController
{
    protected $repository;
    protected $userInfo;

    /**
     * ProductCategory constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->userInfo =$this->request->isLogin() ? $this->request->userInfo():null;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/27
     * @return mixed
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword', 'order', 'is_best', 'location', 'category_id', 'type_id','is_trader']);
        return app('json')->success($this->repository->getList($where, $page, $limit, $this->userInfo));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/29
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        if (!$this->repository->apiGetOne($id))
            return app('json')->fail('店铺已打烊');

        if ($this->request->isLogin()) {
            app()->make(UserMerchantRepository::class)->updateLastTime($this->request->uid(), intval($id));
        }

        return app('json')->success($this->repository->detail($id, $this->userInfo));
    }

    public function systemDetail()
    {
        $config = systemConfig(['site_logo', 'site_name','login_logo']);
        return app('json')->success([
            'mer_avatar' => $config['login_logo'],
            'mer_name' => $config['site_name'],
            'mer_id' => 0,
        ]);
    }



    /**
     * @Author:Qinii
     * @Date: 2020/5/29
     * @param $id
     * @return mixed
     */
    public function productList($id)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword','order','mer_cate_id','cate_id', 'order', 'price_on', 'price_off', 'brand_id', 'pid']);
        if(!$this->repository->apiGetOne($id)) return app('json')->fail(' 店铺已打烊');
        return app('json')->success($this->repository->productList($id,$where, $page, $limit,$this->userInfo));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/29
     * @param int $id
     * @return mixed
     */
    public function categoryList($id)
    {
        if(!$this->repository->merExists((int)$id))
            return app('json')->fail('店铺已打烊');
        return app('json')->success($this->repository->categoryList($id));
    }

    public function qrcode($id)
    {
        if(!$this->repository->merExists($id))
            return app('json')->fail('店铺已打烊');
        $url = $this->request->param('type') == 'routine' ? $this->repository->routineQrcode(intval($id)) : $this->repository->wxQrcode(intval($id));
        return app('json')->success(compact('url'));
    }

    public function localLst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword', 'order', 'is_best', 'location', 'category_id', 'type_id']);
        $where['delivery_way'] = 1;
        return app('json')->success($this->repository->getList($where, $page, $limit, $this->userInfo));
    }

}
