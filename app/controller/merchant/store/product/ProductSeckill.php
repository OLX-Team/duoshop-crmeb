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

use app\common\repositories\store\product\ProductLabelRepository;
use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\store\StoreSeckillActiveRepository;
use app\common\repositories\store\StoreSeckillTimeRepository;
use think\App;
use crmeb\basic\BaseController;
use app\validate\merchant\StoreSeckillProductValidate as validate;
use app\common\repositories\store\product\ProductRepository as repository;
use think\exception\ValidateException;

class ProductSeckill extends BaseController
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
     * @Author:Qinii
     * @Date: 2020/5/18
     * @return mixed
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['cate_id','keyword','mer_cate_id','seckill_status','us_status','product_id','mer_labels']);
        $type = $this->request->param('type',1);
        $where = array_merge($where,$this->repository->switchType($type,$this->request->merId(),1));
        return app('json')->success($this->repository->getSeckillList($this->request->merId(),$where, $page, $limit));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        if(!$this->repository->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->getAdminOneProduct($id,null));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param validate $validate
     * @return mixed
     */
    public function create(validate $validate)
    {
        $data = $this->checkParams($validate);
        $data['start_time'] = (int)substr($data['start_time'],0,2);
        $data['end_time'] = (int)substr($data['end_time'],0,2);
        $merchant = $this->request->merchant();
        $data['product_type'] = 1;
        $data['mer_id'] = $this->request->merId();
        $data['status'] = 0;
        $data['is_gift_bag'] = 0;
        $data['mer_status'] = ($merchant['is_del'] || !$merchant['mer_state'] || !$merchant['status']) ? 0 : 1;
        $this->repository->create($data,1);
        return app('json')->success('添加成功');
    }


    /**
     * TODO 商品验证
     * @param $data
     * @author Qinii
     * @day 2020-08-01
     */
    public function check($data)
    {
        if($data['brand_id'] != 0 && !$this->repository->merBrandExists($data['brand_id']))
            throw new ValidateException('品牌不存在');
        if(!$this->repository->CatExists($data['cate_id']))
            throw new ValidateException('平台分类不存在');
        if(isset($data['mer_cate_id']) && !$this->repository->merCatExists($data['mer_cate_id'],$this->request->merId()))
            throw new ValidateException('不存在的商户分类');

        if($data['delivery_way'] == 2 && !$this->repository->merShippingExists($this->request->merId(),$data['temp_id']))
            throw new ValidateException('运费模板不存在');
        $make = app()->make(StoreSeckillTimeRepository::class);
        $_count = $make->getWhereCount(['start_time' => $data['start_time'],'end_time' => $data['end_time']]);
        if(!$_count) throw new ValidateException('时间段未开放活动');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param $id
     * @param validate $validate
     * @return mixed
     */
    public function update($id,validate $validate)
    {
        $data = $this->checkParams($validate);
        $merchant = $this->request->merchant();
        if(!$this->repository->merExists($this->request->merId(),$id)) return app('json')->fail('数据不存在');
        $this->check($data);
        $data['status'] = 0;
        $data['mer_status'] = ($merchant['is_del'] || !$merchant['mer_state'] || !$merchant['status']) ? 0 : 1;
        unset($data['old_product_id']);
        $data['is_gift_bag'] = 0;
        $this->repository->edit($id,$data,$this->request->merId(),1);
        return app('json')->success('编辑成功');
    }

    /**
     * TODO
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-08-07
     */
    public function delete($id)
    {
        if(!$this->repository->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        if($this->repository->getWhereCount(['product_id' => $id,'is_show' => 1,'status' => 1]))
            return app('json')->fail('商品上架中');
        $this->repository->delete($id);
        return app('json')->success('转入回收站');
    }


    public function destory($id)
    {
        if(!$this->repository->merDeleteExists($this->request->merId(),$id))
            return app('json')->fail('只能删除回收站的商品');
        $this->repository->destory($id);
        return app('json')->success('删除成功');
    }
    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param int $id
     * @return mixed
     */
    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if(!$this->repository->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        $this->repository->switchShow($id,  $status,'is_show',$this->request->merId());
        return app('json')->success('修改成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @return mixed
     */
    public function getStatusFilter()
    {
        return app('json')->success($this->repository->getFilter($this->request->merId(),'秒杀商品',1));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @Time: 14:39
     * @param validate $validate
     * @return array
     */
    public function checkParams(validate $validate)
    {
        $params = array_merge($this->repository::CREATE_PARAMS,["start_day","end_day", "start_time","end_time","once_count","all_pay_count", "once_pay_count","old_product_id"]);
        $data = $this->request->params($params);
        $data['product_type']  = 1;
        app()->make(ProductLabelRepository::class)->checkHas($this->request->merId(),$data['mer_labels']);
        $validate->check($data);
        $data['start_time'] = (int)substr($data['start_time'],0,2);
        $data['end_time'] = (int)substr($data['end_time'],0,2);
        return $data;
    }


    /**
     * TODO
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-07-03
     */
    public function restore($id)
    {
        if(!$this->repository->merDeleteExists($this->request->merId(),$id))
            return app('json')->fail('只能删除回收站的商品');
        $this->repository->restore($id);
        return app('json')->success('商品已恢复');
    }


    /**
     * TODO 获取可用时间段
     * @return mixed
     * @author Qinii
     * @day 2020-08-03
     */
    public function lst_time()
    {
        return app('json')->success(app()->make(StoreSeckillTimeRepository::class)->select());
    }

    public function updateSort($id)
    {
        $sort = $this->request->param('sort');
        app()->make(StoreSeckillActiveRepository::class);
        $this->repository->updateSort($id,$this->request->merId(),['sort' => $sort]);
        return app('json')->success('修改成功');
    }

    public function preview()
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
        return app('json')->success($this->repository->preview($data));
    }

    public function setLabels($id)
    {
        $data = $this->request->params(['mer_labels']);
//        if (empty($data['mer_labels'])) return app('json')->fail('标签为空');

        app()->make(SpuRepository::class)->setLabels($id,1,$data,$this->request->merId());
        return app('json')->success('修改成功');
    }
}
