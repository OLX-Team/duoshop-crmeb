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

use app\common\repositories\store\product\ProductAssistRepository as repository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\SpuRepository;
use crmeb\basic\BaseController;
use think\App;
use app\validate\merchant\StoreProductAssistValidate;
use think\exception\ValidateException;

class ProductAssist extends BaseController
{
    protected  $repository ;

    /**
     * Product constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app ,repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * TODO 列表
     * @return mixed
     * @author Qinii
     * @day 2020-10-12
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['product_status','keyword','is_show','type','presell_type','us_status','product_assist_id','mer_labels']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getMerchantList($where,$page,$limit));
    }

    /**
     * TODO 添加
     * @param StoreProductAssistValidate $validate
     * @return mixed
     * @author Qinii
     * @day 2020-10-12
     */
    public function create(StoreProductAssistValidate $validate)
    {
        $data = $this->checkParams($validate);
        $this->repository->create($this->request->merId(),$data);
        return app('json')->success('添加成功');
    }

    /**
     * TODO 详情
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-10-12
     */
    public function detail($id)
    {
        $data = $this->repository->detail($this->request->merId(),$id);
        return app('json')->success($data);
    }

    /**
     * TODO
     * @param $id
     * @param StoreProductAssistValidate $validate
     * @return mixed
     * @author Qinii
     * @day 2020-10-13
     */
    public function update($id,StoreProductAssistValidate $validate)
    {
        $data = $this->checkParams($validate->isUpdate());
        $this->repository->edit($id,$data);
        return app('json')->success('编辑成功');
    }


    public function delete($id)
    {
        $where = [
            $this->repository->getPk() => $id,
            'mer_id' => $this->request->merId()
        ];
        $this->repository->delete($where);
        return app('json')->success('删除成功');
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if(!$this->repository->detail($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['is_show' => $status]);
        app()->make(SpuRepository::class)->changeStatus($id,3);
        return app('json')->success('修改成功');
    }


    public function checkParams(StoreProductAssistValidate $validate)
    {
        $params = [
            "image", "slider_image", "store_name", "store_info", "product_id","is_show","temp_id","attrValue","guarantee_template_id",
            "start_time", "end_time", "assist_user_count", "assist_count", "status","pay_count","product_status","sort",'mer_labels','delivery_way','delivery_free',
        ];
        $data = $this->request->params($params);
        foreach ($data['attrValue'] as $datum) {
            if ($datum['stock'] > $datum['old_stock']) throw new ValidateException('限量不能大于库存');
        }
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

        app()->make(SpuRepository::class)->setLabels($id,3,$data,$this->request->merId());
        return app('json')->success('修改成功');
    }

}
