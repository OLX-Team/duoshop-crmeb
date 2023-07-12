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
use crmeb\services\WechatUserTagService;
use FormBuilder\Exception\FormBuilderException;
use think\App;

/**
 * Class WechatTag
 * @package app\controller\admin\wechat
 * @author xaboy
 * @day 2020-04-27
 */
class WechatTag extends BaseController
{
    /**
     * @var WechatUserTagService
     */
    protected $service;

    /**
     * WechatTag constructor.
     * @param App $app
     * @param WechatUserTagService $service
     */
    public function __construct(App $app, WechatUserTagService $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    public function lst()
    {
        return app('json')->success($this->service->lst());
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-27
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->service->form()));
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020-04-27
     */
    public function create()
    {
        $name = $this->request->param('tag_name');
        if (!$name) return app('json')->fail('请输入标签名称');
        $this->service->create($name);
        return app('json')->success('添加成功');
    }

    /**
     * @param $id
     * @return mixed
     * @author xaboy
     * @day 2020-04-27
     */
    public function update($id)
    {
        $name = $this->request->param('tag_name');
        if (!$name) return app('json')->fail('请输入标签名称');
        $this->service->update($id, $name);
        return app('json')->success('编辑成功');
    }

    /**
     * @param $id
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-27
     */
    public function updateForm($id)
    {
        return app('json')->success(formToData($this->service->form($id, '')));
    }

    /**
     * @param $id
     * @return mixed
     * @author xaboy
     * @day 2020-04-27
     */
    public function delete($id)
    {
        $this->service->delete($id);
        return app('json')->success('删除成功');
    }
}
