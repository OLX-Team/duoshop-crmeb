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

namespace app\controller\merchant\store\shipping;

use think\App;
use crmeb\basic\BaseController;
use app\validate\merchant\ShippingTemplateValidate as validate;
use app\common\repositories\store\shipping\ShippingTemplateRepository as repository;

class ShippingTemplate extends BaseController
{
    protected $repository;

    /**
     * ShippingTemplate constructor.
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
     * @Date: 2020/5/8
     * @Time: 14:39
     * @return mixed
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['type','name']);
        return app('json')->success($this->repository->search($this->request->merId(),$where, $page, $limit));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @return mixed
     */
    public function getList()
    {
        return app('json')->success($this->repository->getList($this->request->merId()));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @Time: 14:39
     * @param validate $validate
     * @return mixed
     */
    public function create(validate $validate)
    {
        $data = $this->checkParams($validate);
        $data['mer_id'] = $this->request->merId();
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @Time: 14:39
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        if(!$this->repository->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->getOne($id));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @Time: 14:39
     * @param $id
     * @param validate $validate
     * @return mixed
     */
    public function update($id,validate $validate)
    {
        $data = $this->checkParams($validate);
        if(!$this->repository->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id,$data,$this->request->merId());

        return app('json')->success('编辑成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @Time: 14:39
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if(!$this->repository->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        if($this->repository->merDefaultExists($this->request->merId(),$id))
            return app('json')->fail('默认模板不能删除');
        if($this->repository->getProductUse($this->request->merId(),$id))
            return app('json')->fail('模板使用中，不能删除');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
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
        $data = $this->request->params(['name','type','appoint','undelivery','region','free','undelives','sort','info']);
        $validate->check($data);
        return $data;
    }
}
