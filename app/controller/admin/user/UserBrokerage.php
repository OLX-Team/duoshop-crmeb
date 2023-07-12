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


namespace app\controller\admin\user;


use app\common\repositories\user\UserBrokerageRepository;
use app\validate\admin\UserBrokerageValidate;
use crmeb\basic\BaseController;
use think\App;

class UserBrokerage extends BaseController
{
    protected $repository;

    public function __construct(App $app, UserBrokerageRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function options()
    {
        $where = $this->request->params(['type']);
        return app('json')->success($this->repository->options($where));
    }

    public function getLst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['brokerage_name','type']);
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }


    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form()));
    }

    public function create()
    {
        $data = $this->checkParams();
        if ($this->repository->fieldExists('brokerage_level', $data['brokerage_level'],null, $data['type'])) {
            return app('json')->fail('会员等级已存在');
        }

        if ($data['type']) {
            $data['brokerage_rule'] = [
                'image' => $data['image'],
                'value' => $data['value'],
            ];
        }
        unset($data['image'], $data['value']);

        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function updateForm($id)
    {
        return app('json')->success(formToData($this->repository->form($id)));
    }

    public function update($id)
    {
        $id = (int)$id;
        $data = $this->checkParams();
        if (!$id || !$this->repository->get($id)) {
            return app('json')->fail('数据不存在');
        }
        if ($this->repository->fieldExists('brokerage_level', $data['brokerage_level'], $id, $data['type'])) {
            return app('json')->fail('会员等级已存在');
        }

        if ($data['type']) {
            $data['brokerage_rule'] = [
                'image' => $data['image'],
                'value' => $data['value'],
            ];
        }
        unset($data['image'], $data['value']);

        $data['brokerage_rule'] = json_encode($data['brokerage_rule'], JSON_UNESCAPED_UNICODE);
        $this->repository->update($id, $data);
        return app('json')->success('修改成功');
    }

    public function detail($id)
    {
        $id = (int)$id;
        if (!$id || !$brokerage = $this->repository->get($id)) {
            return app('json')->fail('数据不存在');
        }
        return app('json')->success($brokerage->toArray());
    }

    public function delete($id)
    {
        $id = (int)$id;
        if (!$id || !$brokerage = $this->repository->get($id)) {
            return app('json')->fail('数据不存在');
        }
        if ($brokerage->user_num > 0) {
            return app('json')->fail('该等级下有数据，不能进行删除操作！');
        }
        $brokerage->delete();
        return app('json')->success('删除成功');
    }

    public function checkParams()
    {
        $data = $this->request->params(['brokerage_level', 'brokerage_name', 'brokerage_icon', 'brokerage_rule', 'extension_one', 'extension_two', 'image', 'value', ['type',0]]);
        app()->make(UserBrokerageValidate::class)->check($data);
        return $data;
    }
}
