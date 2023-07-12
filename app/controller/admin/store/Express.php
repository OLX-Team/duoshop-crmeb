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

use crmeb\jobs\ExpressSyncJob;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\shipping\ExpressRepository as repository;
use think\facade\Queue;

class Express extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;

    /**
     * City constructor.
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
     * @Date: 2020/5/13
     * @return mixed
     */
    public function lst()
    {
        [$page , $limit] = $this->getPage();
        $where = $this->request->params(['keyword','code']);
        $mer_id = $this->request->merId();
        if($mer_id) $where['is_show'] =  1;
        return app('json')->success($this->repository->search($where, $page, $limit));
    }

    public function detail($id)
    {
        return app('json')->success($this->repository->get($id));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/13
     * @return mixed
     */
    public function create()
    {
        $data = $this->request->params(['name','code','is_show','sort']);
        if(empty($data['name']))
            return app('json')->fail('名称不可为空');
        if($this->repository->codeExists($data['code'],null))
            return app('json')->fail('编码重复');
        if($this->repository->nameExists($data['name'],null))
            return app('json')->fail('名称重复');
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/13
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->params(['name','code','is_show','sort']);
        if(!$this->repository->fieldExists($id))
            return app('json')->fail('数据不存在');
        if(empty($data['name']))
            return app('json')->fail('名称不可为空');
        if($this->repository->codeExists($data['code'],$id))
            return app('json')->fail('编码重复');
        if($this->repository->nameExists($data['name'],$id))
            return app('json')->fail('名称重复');

        $this->repository->update($id,$data);
        return app('json')->success('编辑成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/13
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if(!$this->repository->fieldExists($id))
            return app('json')->fail('数据不存在');

        $this->repository->delete($id);
        return app('json')->success('删除成功');

    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/22
     * @return mixed
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form($this->request->merId())));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/22
     * @param $id
     * @return mixed
     */
    public function updateForm($id)
    {
        if(!$this->repository->fieldExists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm($this->request->merId(),$id)));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/22
     * @param int $id
     * @return mixed
     */
    public function switchStatus($id)
    {
        $status = $this->request->param('is_show', 0) == 1 ? 1 : 0;
        if(!$this->repository->fieldExists($id))
            return app('json')->fail('数据不存在');

        $this->repository->switchStatus($id, ['is_show' =>$status]);
        return app('json')->success('修改成功');
    }

    /**
     * TODO 同步信息
     * @return \think\response\Json
     * @author Qinii
     * @day 7/23/21
     */
    public function syncAll()
    {
        Queue::push(ExpressSyncJob::class,[]);
        return app('json')->success('后台同步中，请稍后来查看～');
    }

    public function partnerForm($id)
    {
        $merId = $this->request->merId();
        return app('json')->success(formToData($this->repository->partnerForm($id,$merId)));
    }

    public function partner($id)
    {
        $data = $this->request->params(['account','key','net_name']);

        if (!$expressInfo = $this->repository->get($id))
            return app('json')->fail('编辑的记录不存在!');
        if ($expressInfo['partner_id'] == 1 && !$data['account'])
            return app('json')->fail('请输入月结账号');
        if ($expressInfo['partner_key'] == 1 && !$data['key'])
            return app('json')->fail('请输入月结密码');
        if ($expressInfo['net'] == 1 && !$data['net_name'])
            return app('json')->fail('请输入取件网点');
        if ($expressInfo['check_man'] == 1 && !$data['check_man'])
            return app('json')->fail('请输入承载快递员名称');
        if ($expressInfo['partner_name'] == 1 && !$data['partner_name'])
            return app('json')->fail('请输入客户账户名称');
        if ($expressInfo['is_code'] == 1 && !$data['code'])
            return app('json')->fail('请输入承载编号');

        $data['express_id'] = $id;
        $data['mer_id'] = $this->request->merId();

        $this->repository->updatePartne($data);
        return app('json')->success('修改成功');
    }

    public function options()
    {
        return app('json')->success($this->repository->options());
    }
}
