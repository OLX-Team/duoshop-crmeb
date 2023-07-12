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
use app\common\repositories\store\product\SpuRepository;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\product\ProductGroupRepository;
use app\validate\merchant\StoreProductGroupValidate;
use think\exception\ValidateException;

class ProductGroup extends BaseController
{
    protected  $repository ;

    /**
     * ProductGroup constructor.
     * @param App $app
     * @param ProductGroupRepository $repository
     */
    public function __construct(App $app ,ProductGroupRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['product_status','keyword','active_type','status','us_status','product_group_id','mer_labels']);
        $where['is_show'] = $where['status'];
        unset($where['status']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getMerchantList($where,$page,$limit));
    }

    public function create(StoreProductGroupValidate $validate)
    {
        $data = $this->checkParams($validate);
        $this->repository->create($this->request->merId(),$data);
        return app('json')->success('添加成功');
    }

    public function detail($id)
    {
        $where = [
            $this->repository->getPk() => $id,
            'mer_id' => $this->request->merId()
        ];
        if(!$this->repository->getWhere($where))
            return app('json')->fail('数据不存在');
        $data = $this->repository->detail($this->request->merId(),$id);
        return app('json')->success($data);
    }

    public function delete($id)
    {
        $where = [
            $this->repository->getPk() => $id,
            'mer_id' => $this->request->merId()
        ];
        if(!$this->repository->getWhere($where))
            return app('json')->fail('数据不存在');
        $this->repository->update($id,['is_del' => 1]);
        event('product.groupDelete',compact('id'));
        app()->make(SpuRepository::class)->changeStatus($id,4);
        return app('json')->success('删除成功');
    }

    public function update($id,StoreProductGroupValidate $validate)
    {
        $data = $this->checkParams($validate);
        $where = [
            $this->repository->getPk() => $id,
            'mer_id' => $this->request->merId()
        ];
        if(!$this->repository->getWhere($where))
            return app('json')->fail('数据不存在');

        $this->repository->edit($id,$data);
        return app('json')->success('编辑成功');
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if(!$this->repository->detail($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['is_show' => $status]);
       app()->make(SpuRepository::class)->changeStatus($id,4);
        return app('json')->success('修改成功');
    }

    public function checkParams(StoreProductGroupValidate $validate)
    {
        $params = [
            "image", "slider_image", "store_name", "store_info", "product_id","is_show","temp_id","once_pay_count","guarantee_template_id",
            "start_time", "end_time", "buying_num", "buying_count_num", "status","pay_count","time","ficti_status","ficti_num","attrValue",
            'unit_name','content','sort','mer_labels','delivery_way','delivery_free'
        ];
        $data = $this->request->params($params);
        if($data['buying_count_num'] < 2) throw new ValidateException('开团人数不得少于2人');
        if($data['end_time'] < $data['start_time']) throw new ValidateException('活动开始时间必须大于结束时间');
        $validate->check($data);
        return $data;
    }

    public function updateSort($id)
    {
        $sort = $this->request->param('sort');
        $this->repository->updateSort($id,$this->request->merId(),['sort' => $sort]);
        return app('json')->success('修改成功');
    }

    public function preview(ProductRepository $repository)
    {
        $data = $this->request->param();
        $data['merchant'] = [
            'mer_name' => $this->request->merchant()->mer_name,
            'is_trader' => $this->request->merchant()->is_trader,
            'mer_avatar' => $this->request->merchant()->mer_avatar,
            'product_score' => $this->request->merchant()->product_score,
            'service_score' => $this->request->merchant()->service_score,
            'postage_score' => $this->request->merchant()->postage_score,
            'service_phone' => $this->request->merchant()->service_phone,
            'care_count' => $this->request->merchant()->care_count,
            'type_name' => $this->request->merchant()->type_name->type_name ?? '',
            'care' => true,
            'recommend' => $this->request->merchant()->recommend,
        ];
        $data['mer_id'] = $this->request->merId();
        $data['status'] =  1;
        $data['mer_status'] = 1;
        $data['rate'] = 3;
        return app('json')->success($repository->preview($data));
    }

    public function setLabels($id)
    {
        $data = $this->request->params(['mer_labels']);
//        if (empty($data['mer_labels'])) return app('json')->fail('标签为空');

        app()->make(SpuRepository::class)->setLabels($id,4,$data,$this->request->merId());
        return app('json')->success('修改成功');
    }
}
