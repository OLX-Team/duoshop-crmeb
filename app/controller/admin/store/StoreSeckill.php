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

use think\App;
use crmeb\basic\BaseController;
use app\validate\admin\StoreSeckillValidate;
use app\common\repositories\store\StoreSeckillTimeRepository as repository;

class StoreSeckill extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;

    /**
     * Express constructor.
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
        $where = $this->request->params(['title','status']);
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * TODO
     * @return mixed
     * @author Qinii
     * @day 2020-08-01
     */
    public function select()
    {
        return app('json')->success($this->repository->select());
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/13
     * @return mixed
     */
    public function create(StoreSeckillValidate $validate)
    {
        $data = $this->checkParams($validate);
        if(!$this->repository->checkTime($data,null))
            return app('json')->fail('时间段不可重叠');
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * TODO
     * @param $id
     * @param StoreSeckillValidate $validate
     * @return mixed
     * @author Qinii
     * @day 2020-07-31
     */
    public function update($id,StoreSeckillValidate $validate)
    {
        $data = $this->checkParams($validate);
        if(!$this->repository->checkTime($data,$id))
            return app('json')->fail('时间段不可重叠');
        $this->repository->update($id,$data);
        return app('json')->success('编辑成功');
    }

    /**
     * TODO
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-07-31
     */
    public function delete($id)
    {
        if(!$this->repository->get($id))
            return app('json')->fail('数据不存在');

        $this->repository->delete($id);
        return app('json')->success('删除成功');

    }

    /**
     * TODO
     * @return mixed
     * @author Qinii
     * @day 2020-07-31
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form()));
    }

    /**
     * TODO
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-07-31
     */
    public function updateForm($id)
    {
        return app('json')->success(formToData($this->repository->updateForm($id)));
    }

    /**
     * TODO
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-07-31
     */
    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if(!$this->repository->get($id))
            return app('json')->fail('数据不存在');

        $this->repository->update($id, ['status' =>$status]);
        return app('json')->success('修改成功');
    }

    public function checkParams(StoreSeckillValidate $validate)
    {
        $data = $this->request->params(['start_time','end_time','status','title','pic']);
        $validate->check($data);
        return $data;
    }
}
