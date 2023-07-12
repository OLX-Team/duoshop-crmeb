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


use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\system\groupData\GroupRepository;
use app\common\repositories\system\serve\ServeOrderRepository;
use app\common\repositories\user\MemberinterestsRepository;
use app\common\repositories\user\UserBrokerageRepository;
use app\common\repositories\user\UserOrderRepository;
use app\validate\admin\UserBrokerageValidate;
use crmeb\basic\BaseController;
use think\App;

class Svip extends BaseController
{
    protected $repository;

    public function __construct(App $app, UserBrokerageRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * TODO 购买会员套餐的列表
     * @param GroupRepository $groupRepository
     * @param GroupDataRepository $groupDataRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/4
     */
    public function getTypeLst(GroupRepository $groupRepository,GroupDataRepository  $groupDataRepository)
    {
        [$page, $limit] = $this->getPage();
        $group_id = $groupRepository->getSearch(['group_key' => 'svip_pay'])->value('group_id');
        $lst = $groupDataRepository->getGroupDataLst(0, intval($group_id), $page, $limit);
        return app('json')->success($lst);
    }

    /**
     * TODO 添加够没类型
     * @param GroupRepository $groupRepository
     * @param GroupDataRepository $groupDataRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/4
     */
    public function createTypeCreateForm(GroupRepository $groupRepository, GroupDataRepository  $groupDataRepository)
    {
        $group_id = $groupRepository->getSearch(['group_key' => 'svip_pay'])->value('group_id');
        $data = $groupDataRepository->reSetDataForm($group_id, null, null);
        return app('json')->success(formToData($data));
    }

    /**
     * TODO 编辑会员购买类型
     * @param $id
     * @param GroupRepository $groupRepository
     * @param GroupDataRepository $groupDataRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/8
     */
    public function updateTypeCreateForm($id, GroupRepository $groupRepository, GroupDataRepository  $groupDataRepository)
    {
        $group_id = $groupRepository->getSearch(['group_key' => 'svip_pay'])->value('group_id');
        $data = $groupDataRepository->reSetDataForm($group_id, $id, null);
        return app('json')->success(formToData($data));
    }


    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/8
     */
    public function getInterestsLst(MemberinterestsRepository $memberinterestsRepository)
    {
        $data = $memberinterestsRepository->getInterestsByLevel($memberinterestsRepository::TYPE_SVIP);
        return app('json')->success($data);
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

    /**
     * TODO 会员购买记录
     * @param UserOrderRepository $userOrderRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/12
     */
    public function payList(UserOrderRepository $userOrderRepository)
    {
        [$page, $limit] = $this->getPage();
        $type = $this->request->param('svip_type','');
        $where = $this->request->params(['pay_type','title','date','nickname','keyword']);
        if($type) $where['type'] = $userOrderRepository::TYPE_SVIP.$type;
        $data = $userOrderRepository->getList($where,$page, $limit);
        return app('json')->success($data);
    }
}
