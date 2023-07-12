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

namespace app\controller\admin\store;

use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use think\App;
use crmeb\basic\BaseController;
use app\validate\merchant\StoreProductAdminValidate as validate;
use app\common\repositories\store\product\ProductRepository as repository;
use think\facade\Queue;

class StoreProductSeckill extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;


    /**
     * StoreProduct constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, repository $repository)
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
        $where = $this->request->params(['cate_id', 'keyword', 'mer_cate_id', 'pid', 'seckill_status', 'is_trader', 'us_status', 'star', 'product_id', 'sys_labels']);
        $mer_id = $this->request->param('mer_id');
        $merId  = $mer_id ?: null;
        $type = $this->request->param('type',1);
        $where_ = $this->repository->switchType($type, $merId, 1);
        unset($where_['star']);
        $where = array_merge($where, $where_);
        return app('json')->success($this->repository->getAdminSeckillList($merId, $where, $page, $limit));
    }


    /**
     * TODO
     * @return mixed
     * @author Qinii
     * @day 2020-08-04
     */
    public function getStatusFilter()
    {
        return app('json')->success($this->repository->getFilter(null, '秒杀商品', 1));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        if (!$this->repository->merExists(null, $id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->getAdminOneProduct($id, null));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param $id
     * @param validate $validate
     * @return mixed
     */
    public function update($id, validate $validate)
    {
        $data = $this->checkParams($validate);
        $this->repository->adminUpdate($id, $data);
        return app('json')->success('编辑成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param int $id
     * @return mixed
     */
    public function switchStatus()
    {
        $id = $this->request->param('id');
        $data = $this->request->params(['status', 'refusal']);
        if ($data['status'] == -1 && empty($data['refusal']))
            return app('json')->fail('请填写拒绝理由');
        if (is_array($id)) {
            $this->repository->batchSwitchStatus($id, $data);
        } else {
            $this->repository->switchStatus($id, $data);
        }
        return app('json')->success('操作成功');
    }

    /**
     * TODO 是否隐藏
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-07-17
     */
    public function changeUsed($id)
    {
        if (!$this->repository->merExists(null, $id))
            return app('json')->fail('数据不存在');
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        $this->repository->switchShow($id, $status,'is_used');
        return app('json')->success('修改成功');
    }


    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param validate $validate
     * @return array
     */
    public function checkParams(validate $validate)
    {
        $data = $this->request->params(['is_hot', 'is_best', 'is_benefit', 'is_new', 'store_name', 'keyword', 'content', 'rank', 'star']);
        $validate->check($data);
        return $data;
    }

    /**
     * TODO
     * @author Qinii
     * @day 2020-06-24
     */
    public function checkProduct()
    {
        Queue::push(CheckProductExtensionJob::class, []);
        return app('json')->success('后台已开始检测');
    }

    public function lists()
    {
        $make = app()->make(MerchantRepository::class);
        $data = $make->selectWhere(['status' => 1, 'mer_state' => 1, 'is_del' => 0], 'mer_id,mer_name');
        return app('json')->success($data);
    }

    public function setLabels($id)
    {
        $data = $this->request->params(['sys_labels']);
        //        if (empty($data['sys_labels'])) return app('json')->fail('标签为空');

        app()->make(SpuRepository::class)->setLabels($id, 1, $data, 0);
        return app('json')->success('修改成功');
    }
}
