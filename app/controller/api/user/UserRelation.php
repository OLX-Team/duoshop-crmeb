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

namespace app\controller\api\user;


use app\common\repositories\store\product\ProductRepository;
use crmeb\basic\BaseController;
use app\common\repositories\user\UserRelationRepository as repository;
use think\App;

class UserRelation extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;

    /**
     * UserRelation constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function create()
    {
        $params = $this->request->params(['type_id', 'type']);
        $params['uid'] = $this->request->uid();
        if (!$params['type_id'])
            return app('json')->fail('请选择'. ($params['type'] == 1 ? '商品' : '商户'));
        if (!in_array($params['type'], [0,1,2,3,4,10]))
            return app('json')->fail('参数错误');
        if (!$this->repository->fieldExists($params))
            return app('json')->fail('数据不存在');
        if ($this->repository->getUserRelation($params,$this->request->uid()))
            return app('json')->fail('您已经关注过了');
        $this->repository->create($params);
        return app('json')->success('关注成功');
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function productList()
    {
        [$page, $limit] = $this->getPage();
        $where = ['uid'=>$this->request->uid(),'type'=>1];
        return app('json')->success($this->repository->search($where, $page,$limit));
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function merchantList()
    {
        [$page, $limit] = $this->getPage();
        $where = ['uid'=>$this->request->uid(),'type'=>10];
        return app('json')->success($this->repository->search($where, $page,$limit));
    }

    /**
     * TODO 收藏列表的删除
     * @return \think\response\Json
     * @author Qinii
     * @day 7/12/21
     */
    public function batchDelete()
    {
        $ids = $this->request->param('type_id');
        $type = $this->request->param('type',1);
        if(empty($ids))  return app('json')->fail('请选择'. ($type == 1 ? '商品' : '商户'));
        $this->repository->batchDestory($ids,$this->request->uid(),$type);
        return app('json')->success('已取消关注');
    }

    /**
     * TODO 商品详情中的取消收藏
     * @return \think\response\Json
     * @author Qinii
     * @day 7/12/21
     */
    public function delete()
    {
        $params = $this->request->params(['type_id','type']);
        if (!$this->repository->getUserRelation($params,$this->request->uid()))
            return app('json')->fail('信息不存在');
        $spu = $this->repository->getSpu($params);
        $params['type_id'] = $spu->spu_id;
        $this->repository->batchDestory([$params['type_id']],$this->request->uid());
        return app('json')->success('已取消关注');
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function batchCreate()
    {
        $params = $this->request->params(['type_id','type']);
        if(!count($params['type_id']) ||  !in_array($params['type'], [1,10]))
            return app('json')->fail('请选择商品');
        $this->repository->batchCreate($this->request->uid(),$params);
        return app('json')->success('收藏成功');
    }

}
