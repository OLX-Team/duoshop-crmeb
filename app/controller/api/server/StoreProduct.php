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
namespace app\controller\api\server;

use app\common\repositories\store\order\StoreCartRepository;
use app\common\repositories\store\product\ProductLabelRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\service\StoreServiceRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\validate\merchant\StoreProductValidate;
use crmeb\basic\BaseController;
use crmeb\services\UploadService;
use think\App;
use think\exception\HttpResponseException;
use think\exception\ValidateException;

class StoreProduct extends BaseController
{
    protected $merId;
    protected $repository;

    public function __construct(App $app, ProductRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->merId = $this->request->route('merId');
    }

    /**
     * TODO 头部统计
     * @param $merId
     * @return \think\response\Json
     * @author Qinii
     * @day 8/24/21
     */
    public function title($merId)
    {
        return app('json')->success($this->repository->getFilter($merId, '', 0));
    }

    /**
     * TODO 列表
     * @param $merId
     * @return \think\response\Json
     * @author Qinii
     * @day 8/24/21
     */
    public function lst($merId)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['cate_id', 'keyword', 'mer_cate_id', 'is_gift_bag', 'status', 'us_status', 'product_id', 'mer_labels',['order','sort']]);
        $type = $this->request->param('type',1);
        $where = array_merge($where, $this->repository->switchType($type, $merId, 0));
        return app('json')->success($this->repository->getList($merId, $where, $page, $limit));
    }

    /**
     * TODO 添加
     * @param $merId
     * @param StoreProductValidate $validate
     * @return \think\response\Json
     * @author Qinii
     * @day 8/24/21
     */
    public function create($merId, StoreProductValidate $validate)
    {
        $res = $this->request->params($this->repository::CREATE_PARAMS);
        $data = $this->repository->checkParams($res,$merId);
        $data['mer_id'] = $merId;
        $data['is_gift_bag'] = 0;
        $merchant = app()->make(MerchantRepository::class)->get($merId);
        $data['status'] = $merchant->is_audit ? 0 : 1;
        $data['mer_status'] = ($merchant['is_del'] || !$merchant['mer_state'] || !$merchant['status']) ? 0 : 1;
        $data['rate'] = 3;
        $this->repository->create($data, 0, 1);
        return app('json')->success('添加成功');
    }

    /**
     * TODO 编辑
     * @param $merId
     * @param $id
     * @param StoreProductValidate $validate
     * @return \think\response\Json
     * @author Qinii
     * @day 8/24/21
     */
    public function update($merId, $id, StoreProductValidate $validate)
    {
        $res = $this->request->params($this->repository::CREATE_PARAMS);
        $data = $this->repository->checkParams($res,$merId,$id);

        $merchant = app()->make(MerchantRepository::class)->get($merId);
        if (!$this->repository->merExists($merId, $id))
            return app('json')->fail('数据不存在');
        $pro = $this->repository->getWhere(['product_id' => $id]);
        if ($pro->status == -2) {
            $data['status'] = 0;
        } else {
            $data['status'] = $merchant->is_audit ? 0 : 1;
        }
        $data['mer_status'] = ($merchant['is_del'] || !$merchant['mer_state'] || !$merchant['status']) ? 0 : 1;
        $data['mer_id'] = $merId;
        $this->repository->edit($id, $data, $merId, 0, 1);
        return app('json')->success('编辑成功');
    }

    /**
     * TODO 详情
     * @param $merId
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 8/24/21
     */
    public function detail($merId, $id)
    {
        if (!$this->repository->merExists($merId, $id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->getAdminOneProduct($id, 0, 1));
    }

    /**
     * TODO 修改状态
     * @param $merId
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 8/24/21
     */
    public function switchStatus($merId, $id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->merExists($merId, $id))
            return app('json')->fail('数据不存在');
        $this->repository->switchShow($id,$status, 'is_show',$merId);
        return app('json')->success('修改成功');
    }

    /**
     * TODO 加入回收站
     * @param $merId
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 8/24/21
     */
    public function delete($merId, $id)
    {
        if (!$this->repository->merExists($merId, $id))
            return app('json')->fail('数据不存在');
        if ($this->repository->getWhereCount(['product_id' => $id, 'is_show' => 1, 'status' => 1]))
            return app('json')->fail('商品上架中');
        $this->repository->delete($id);
        return app('json')->success('转入回收站');
    }

    public function config($merId)
    {
        $data['extension_status'] = systemConfig('extension_status');
        $data['integral_status'] = 0;
        $data['integral_rate'] = 0;
        if(systemConfig('integral_status') && merchantConfig($merId,'mer_integral_status')) {
            $data['integral_status'] = 1;
            $data['integral_rate'] = merchantConfig($merId,'mer_integral_rate');
        }
        $merchant = app()->make(MerchantRepository::class)->get($merId);
        $data['delivery_way'] = $merchant->delivery_way;
        return app('json')->success($data);
    }

    public function restore($id)
    {
        if (!$this->repository->merDeleteExists($this->merId, $id))
            return app('json')->fail('只能删除回收站的商品');
        $this->repository->restore($id);
        return app('json')->success('商品已恢复');
    }

    public function destory($id)
    {
        if (!$this->repository->merDeleteExists($this->merId, $id))
            return app('json')->fail('只能删除回收站的商品');
        if (app()->make(StoreCartRepository::class)->getProductById($id))
            return app('json')->fail('商品有被加入购物车不可删除');
        $this->repository->destory($id);
        return app('json')->success('删除成功');
    }

    public function updateGood($id)
    {
        $is_good = $this->request->param('is_good', 0) == 1 ? 1 : 0;
        if (!$this->repository->merExists($this->merId, $id))
            return app('json')->fail('数据不存在');

        $this->repository->update($id, ['is_good' => $is_good]);
        return app('json')->success('修改成功');
    }
}
