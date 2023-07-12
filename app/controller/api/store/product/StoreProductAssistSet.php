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

namespace app\controller\api\store\product;

use app\common\repositories\store\product\ProductAssistSetRepository;
use app\common\repositories\store\product\ProductAssistUserRepository;
use think\App;
use crmeb\basic\BaseController;

class StoreProductAssistSet extends BaseController
{
    protected $repository;
    protected $userInfo;

    /**
     * StoreProductPresell constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, ProductAssistSetRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->userInfo = $this->request->isLogin() ? $this->request->userInfo() : null;
    }


    /**
     * TODO 个人助力列表
     * @return mixed
     * @author Qinii
     * @day 2020-11-25
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where['uid'] = $this->request->uid();
        return app('json')->success($this->repository->getApiList($where,$page, $limit));
    }

    public function detail($id)
    {
        $data = $this->repository->detail($id,$this->userInfo);
        return  app('json')->success($data);
    }

    /**
     * TODO 发起助力
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-10-28
     */
    public function create($id)
    {
//        if($this->userInfo->user_type == 'wechat' && !$this->userInfo->subscribe){
//            return  app('json')->fail('请先关注公众号');
//        }
        $data = $this->repository->create($id,$this->request->uid());
        return  app('json')->success($data);
    }

    /**
     * TODO 帮好友助力
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-10-28
     */
    public function set($id)
    {
        $this->repository->set($id,$this->userInfo);
        return  app('json')->success('助力成功');
    }

    public function delete($id)
    {
        $res = $this->repository->getWhere(['product_assist_set_id' => $id,'uid' => $this->request->uid()]);

        if(!$res)return  app('json')->fail('信息错误');
        $this->repository->update($id,['status' => -1]);
        return  app('json')->success('取消成功');
    }

    /**
     * TODO 助力列表
     * @param $id
     * @param ProductAssistUserRepository $repository
     * @return mixed
     * @author Qinii
     * @day 2020-10-28
     */
    public function userList($id,ProductAssistUserRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $where['product_assist_set_id'] = $id;
        if(!$this->repository->get($id))  return  app('json')->fail('数据丢失');
        return app('json')->success($repository->userList($where,$page, $limit));
    }

    public function shareNum($id)
    {
        $this->repository->incNum(1,$id);
        return app('json')->success('oks');
    }
}
