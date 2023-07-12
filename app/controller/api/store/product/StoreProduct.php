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

use app\common\repositories\store\PriceRuleRepository;
use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\user\UserMerchantRepository;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\product\ProductRepository as repository;
use think\facade\Cache;

class StoreProduct extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;
    protected $userInfo = null;

    /**
     * StoreProduct constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->userInfo = $this->request->isLogin() ? $this->request->userInfo() : null;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/28
     * @return mixede
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword', 'cate_id', 'order', 'price_on', 'price_off', 'brand_id', 'pid','star']);
        $data = $this->repository->getApiSearch(null, $where, $page, $limit, $this->userInfo);
        return app('json')->success($data);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/30
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        $data = $this->repository->detail($id, $this->userInfo);
        if (!$data){
            app()->make(SpuRepository::class)->changeStatus($id,0);
            return app('json')->fail('商品已下架');
        }

        if ($this->request->isLogin()) {
            app()->make(UserMerchantRepository::class)->updateLastTime($this->request->uid(), $data->mer_id);
        }

        return app('json')->success($data);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/30
     * @return mixed
     */
    public function recommendList()
    {
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->recommend($this->userInfo, null, $page, $limit));
    }

    public function qrcode($id)
    {
        $id = (int)$id;
        $param = $this->request->params(['type', ['product_type', 0]]);
        $param['product_type'] = (int)$param['product_type'];
        if (!$id || !$product = $this->repository->existsProduct($id, $param['product_type']))
            return app('json')->fail('商品不存在');

        if ($param['type'] == 'routine') {
            $url = $this->repository->routineQrCode($id, $param['product_type'], $this->request->userInfo());
        }else{
            $url = $this->repository->wxQrCode($id, $param['product_type'], $this->request->userInfo());
        }

        if (!$url) return app('json')->fail('二维码生成失败');
        return app('json')->success(compact('url'));
    }

    public function getBagList()
    {
        if(!systemConfig('extension_status'))  return app('json')->fail('活动未开启');
        [$page, $limit] = $this->getPage();
        $where = $this->repository->bagShow();
        return app('json')->success($this->repository->getBagList($where, $page, $limit));
    }

    public function getBagrecomm()
    {
        $where = $this->repository->bagShow();
        $where['is_best'] = 1;
        return app('json')->success($this->repository->selectWhere($where)->append(['merchant']));
    }

    public function getBagExplain()
    {
        if(!systemConfig('extension_status'))  return app('json')->fail('活动未开启');
        $data = [
            'explain' => systemConfig('promoter_explain'),
            'data' => app()->make(GroupDataRepository::class)->groupData('promoter_config', 0),
        ];
        return app('json')->success($data);
    }

    public function hot($type)
    {
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getApiSearch(null, ['hot_type' => $type, 'is_gift_bag' => 0, 'is_used' => 1], $page, $limit, $this->userInfo));
    }

    public function guaranteeTemplate($id)
    {
        $where = [
            'guarantee_template_id' => $id,
            'status' => 1,
        ];
        $data = $this->repository->GuaranteeTemplate($where);
        return app('json')->success($data);
    }

    public function setIncreaseTake()
    {
        $product_id = $this->request->param('product_id');
        $unique = $this->request->param('unique');
        $type = $this->request->param('type');
        if($type == 1 && !$this->userInfo['phone']) return app('json')->fail('请先绑定手机号');
        $this->repository->increaseTake($this->request->uid(),$unique,$type,$product_id);
        return app('json')->success('订阅成功');
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 6/15/21
     */
    public function preview()
    {
        $param = $this->request->params(['key','id','product_type']);
        $data = [];
        if($param['key']){
            $data = Cache::get($param['key']);
            Cache::delete($param['key']);
        }elseif($param['id']){
            $data = $this->repository->getPreview($param);
        }
        if(!$data) return app('json')->fail('数据不存在');
        return app('json')->success($data);
    }

    public function priceRule($id)
    {
        $path = app()->make(StoreCategoryRepository::class)->query(['store_category_id' => $id, 'mer_id' => 0])->value('path');
        if ($path && $path !== '/') {
            $ids = explode('/', trim($path, '/'));
            $ids[] = $id;
        } else {
            $ids[] = $id;
        }
        $rule = app()->make(PriceRuleRepository::class)->search(['cate_id' => $ids, 'is_show' => 1])
            ->order('sort DESC,rule_id DESC')->find();
        if ($rule) {
            return app('json')->success($rule->toArray());
        }
        return app('json')->fail('规则不存在');
    }
}
