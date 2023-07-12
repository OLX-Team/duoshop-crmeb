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

use app\common\repositories\store\product\StoreDiscountRepository;
use app\validate\merchant\StoreDiscountsValidate;
use crmeb\basic\BaseController;
use think\App;
use think\exception\ValidateException;

class Discounts extends BaseController
{

    protected  $repository ;

    /**
     * Product constructor.
     * @param App $app
     * @param StoreDiscountRepository $repository
     */
    public function __construct(App $app ,StoreDiscountRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $status = $this->request->param('status');
        $where = $this->request->params(['keyword','store_name','title','type']);
        $where['is_show'] = $status;

        $where['mer_id'] = $this->request->merId();
        $data = $this->repository->getMerlist($where, $page, $limit);
        return app('json')->success($data);
    }

    public function create()
    {
        $data = $this->checkParams();
        $this->repository->save($data);
        return app('json')->success('添加成功');
    }

    public function update($id)
    {
        $data = $this->checkParams();
        if (!$this->repository->getWhere(['mer_id' => $data['mer_id'], $this->repository->getPk() => $id]))
            return app('json')->fail('数据不存在');
        $data['discount_id'] = $id;
        $this->repository->save($data);
        return app('json')->success('编辑成功');
    }

    public function detail($id)
    {
        $data = $this->repository->detail($id, $this->request->merId());
        if (!$data )  return app('json')->fail('数据不存在');
        return app('json')->success($data);
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status') == 1 ?: 0;

        if (!$this->repository->getWhere([$this->repository->getPk() => $id,'mer_id' => $this->request->merId()]))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['is_show' => $status]);
        return app('json')->success('修改成功');
    }

    public function delete($id)
    {
        if (!$this->repository->getWhere([$this->repository->getPk() => $id,'mer_id' => $this->request->merId()]))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['is_del' => 1]);
        return app('json')->success('删除成功');
    }

    public function checkParams()
    {
        $params = [
            ['title', ''],
            ['image', ''],
            ['type', 0],
            ['is_limit', 0],
            ['limit_num', 0],
            ['is_time', 0],
            ['time', []],
            ['sort', 0],
            ['free_shipping', 0],
            ['status', 0],
            ['is_show', 1],
            ['products', []],
            ['temp_id',0],
        ];
        $data = $this->request->params($params);
        app()->make(StoreDiscountsValidate::class)->check($data);

        if ($data['is_time'] && is_array($data['time'])) {
            if (empty($data['time']))  throw new ValidateException('开始时间必须填写');
            [$start, $end] = $data['time'];
            $start = strtotime($start);
            $end = strtotime($end);
            if($start > $end){
                throw new ValidateException('开始时间必须小于结束时间');
            }
            if($start < time() || $end < time()){
                throw new ValidateException('套餐时间不能小于当前时间');
            }
        }
        foreach ($data['products'] as $item) {
            if (!isset($item['items']))
                throw new ValidateException('请选择' . $item['store_name'] . '的规格');
            foreach ($item['attr'] as $attr) {
                if($attr['active_price'] > $attr['price']) throw new ValidateException('套餐价格高于原价');
            }
        }
        $data['mer_id'] = $this->request->merId();
        return $data;
    }


}
