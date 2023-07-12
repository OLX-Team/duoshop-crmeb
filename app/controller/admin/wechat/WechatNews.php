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

namespace app\controller\admin\wechat;

use crmeb\basic\BaseController;
use app\common\repositories\wechat\WechatNewsRepository;
use app\validate\admin\WechatNewsValidate;
use think\App;
use think\Request;

class WechatNews extends BaseController
{

    /**
     * @var WechatNewsRepository
     */
    protected $repositories;

    /**
     * WechatNews constructor.
     * @param App $app
     * @param WechatNewsRepository $repository
     */
    public function __construct(App $app,WechatNewsRepository $repository)
    {
        parent::__construct($app);
        $this->repositories = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where['cate_name'] = $this->request->param('cate_name');
        $result = $this->repositories->search($where, $page, $limit);
        return app('json')->success($result);
    }

    /**
     * 添加
     * @param WechatNewsValidate $validate
     * @return mixed
     * @author Qinii
     */
    public function create(WechatNewsValidate $validate)
    {
        $data =$this->checkParams($validate,true);
        $this->repositories->create($data,$this->request->merId(),$this->request->adminId());
        return app('json')->success('添加成功');
    }

    /**
     * 编辑
     * @param $id
     * @param WechatNewsValidate $validate
     * @return mixed
     * @author Qinii
     */
    public function update($id,WechatNewsValidate $validate)
    {
        $data =$this->checkParams($validate);
        if (!$this->repositories->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        $this->repositories->update($id,$data,$this->request->merId(),$this->request->adminId());
        return app('json')->success('编辑成功');
    }

    public function delete($id)
    {
        if (!$this->repositories->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        $this->repositories->delete($id,$this->request->merId());
        return app('json')->success('删除成功');
    }

    public function detail($id)
    {
        if (!$this->repositories->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repositories->git($id,$this->request->merId()));
    }
    /**
     * 验证
     * @param WechatNewsValidate $validate
     * @param bool $isCreate
     * @return array
     * @author Qinii
     */
    public function checkParams(WechatNewsValidate $validate)
    {
        $data = $this->request->params(['status','data']);
        $validate->check($data);
        return $data;
    }
}
