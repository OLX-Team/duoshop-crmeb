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


namespace app\controller\merchant\user;


use app\common\repositories\user\LabelRuleRepository;
use app\common\repositories\user\UserLabelRepository;
use app\validate\merchant\LabelRuleValidate;
use crmeb\basic\BaseController;
use think\App;
use think\exception\ValidateException;

class LabelRule extends BaseController
{
    protected $repository;

    public function __construct(App $app, LabelRuleRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getList()
    {
        $where = $this->request->params(['keyword', 'type']);
        $where['mer_id'] = $this->request->merId();
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    public function create()
    {
        $data = $this->checkParams();
        $data['mer_id'] = $this->request->merId();
        if (app()->make(UserLabelRepository::class)->existsName($data['label_name'], $data['mer_id'], 1))
            return app('json')->fail('标签名已存在');
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function update($id)
    {
        $data = $this->checkParams();
        $mer_id = $this->request->merId();
        if (!$label = $this->repository->getWhere(['label_rule_id' => $id, 'mer_id' => $mer_id]))
            return app('json')->fail('数据不存在');
        if (app()->make(UserLabelRepository::class)->existsName($data['label_name'], $mer_id, 1, $label->label_id))
            return app('json')->fail('标签名已存在');
        $this->repository->update(intval($id), $data);
        return app('json')->success('编辑成功');
    }

    public function delete($id)
    {
        if (!$this->repository->existsWhere(['label_rule_id' => $id, 'mer_id' => $this->request->merId()]))
            return app('json')->fail('数据不存在');
        $this->repository->delete(intval($id));
        return app('json')->success('删除成功');
    }

    public function sync($id)
    {
        if (!$this->repository->existsWhere(['label_rule_id' => $id, 'mer_id' => $this->request->merId()]))
            return app('json')->fail('数据不存在');
        $this->repository->syncUserNum(intval($id));
        return app('json')->success('更新成功');

    }

    /**
     * @return array
     * @author xaboy
     * @day 2020/10/21
     */
    public function checkParams()
    {
        $data = $this->request->params(['label_name', 'min', 'max', 'type', 'data']);
        app()->make(LabelRuleValidate::class)->check($data);
        if (!$data['type']) {
            if (false === filter_var($data['min'], FILTER_VALIDATE_INT) || false === filter_var($data['max'], FILTER_VALIDATE_INT)) {
                throw new ValidateException('数值必须为整数');
            }
        }
        return $data;
    }
}
