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


use app\common\repositories\store\ExcelRepository;
use app\common\repositories\user\UserHistoryRepository;
use app\common\repositories\user\UserSignRepository;
use app\common\repositories\user\UserSpreadLogRepository;
use app\common\repositories\user\UserVisitRepository;
use crmeb\basic\BaseController;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserGroupRepository;
use app\common\repositories\user\UserLabelRepository;
use app\common\repositories\user\UserRepository;
use app\common\repositories\wechat\WechatNewsRepository;
use app\common\repositories\wechat\WechatUserRepository;
use app\validate\admin\UserNowMoneyValidate;
use app\validate\admin\UserValidate;
use crmeb\services\ExcelService;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;

/**
 * Class User
 * @package app\controller\admin\user
 * @author xaboy
 * @day 2020-05-07
 */
class User extends BaseController
{
    /**
     * @var UserRepository
     */
    protected $repository;

    /**
     * User constructor.
     * @param App $app
     * @param UserRepository $repository
     */
    public function __construct(App $app, UserRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-07
     */
    public function lst()
    {
        /*
         * 昵称，分组，标签，地址，性别，
         */
        $where = $this->request->params([
            'label_id',
            'user_type',
            'sex',
            'is_promoter',
            'country',
            'pay_count',
            'user_time_type',
            'user_time',
            'nickname',
            'province',
            'city',
            'group_id',
            'phone',
            'uid',
            ]);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    public function spreadList($uid)
    {
        $where = $this->request->params(['level', 'keyword', 'date']);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getLevelList($uid, $where, $page, $limit));
    }

    public function spreadOrder($uid)
    {
        $where = $this->request->params(['level', 'keyword', 'date']);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->subOrder($uid, $page, $limit, $where));
    }

    public function clearSpread($uid)
    {
        $this->repository->update($uid, ['spread_uid' => 0]);
        return app('json')->success('清除成功');
    }


    public function createForm()
    {
        return app('json')->success(formToData($this->repository->createForm()));
    }

    public function create(UserValidate $validate)
    {
        $data = $this->request->params([
            'account',
            'pwd',
            'repwd',
            'nickname',
            'avatar',
            'real_name',
            'phone',
            'sex',
            'status',
            'card_id',
            ['is_promoter', 0]
        ]);

        $validate->scene('create')->check($data);
        $data['pwd'] = $this->repository->encodePassword($data['repwd']);
        unset($data['repwd']);
        if ($data['is_promoter']) $data['promoter_time'] = date('Y-m-d H:i:s');
        $this->repository->create('h5',$data);

        return app('json')->success('添加成功');
    }

    public function changePasswordForm($id)
    {
        return app('json')->success(formToData($this->repository->changePasswordForm($id)));
    }

    public function changePassword($id)
    {
        $data = $this->request->params([
            'pwd',
            'repwd',
        ]);
        if (!$data['pwd'] || !$data['repwd'])
            return app('json')->fail('密码不能为空');
        if ($data['pwd'] !== $data['repwd'])
            return app('json')->fail('密码不一致');
        $data['pwd'] = $this->repository->encodePassword($data['repwd']);
        unset($data['repwd']);
        $this->repository->update($id,$data);

        return app('json')->success('修改成功');
    }


    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-09
     */
    public function updateForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->userForm($id)));
    }

    /**
     * @param $id
     * @param UserValidate $validate
     * @param UserLabelRepository $labelRepository
     * @param UserGroupRepository $groupRepository
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-09
     */
    public function update($id, UserValidate $validate, UserLabelRepository $labelRepository, UserGroupRepository $groupRepository)
    {
        $data = $this->request->params(['real_name', 'phone', 'birthday', 'card_id', 'addres', 'mark', 'group_id', ['label_id', []], ['is_promoter', 0], ['status', 0]]);
        $validate->check($data);
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        if ($data['group_id'] && !$groupRepository->exists($data['group_id']))
            return app('json')->fail('分组不存在');
        $label_id = (array)$data['label_id'];
        foreach ($label_id as $k => $value) {
            $label_id[$k] = (int)$value;
            if (!$labelRepository->exists((int)$value))
                return app('json')->fail('标签不存在');
        }
        $data['label_id'] = implode(',', $label_id);
        if ($data['is_promoter'])
            $data['promoter_time'] = date('Y-m-d H:i:s');
        if(!$data['birthday']) unset($data['birthday']);
        $this->repository->update($id, $data);

        return app('json')->success('编辑成功');
    }


    /**
     * @param $id
     * @param UserLabelRepository $labelRepository
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-08
     */
    public function changeLabel($id, UserLabelRepository $labelRepository)
    {
        $label_id = (array)$this->request->param('label_id', []);
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        foreach ($label_id as $k => $value) {
            $label_id[$k] = (int)$value;
            if (!$labelRepository->exists((int)$value))
                return app('json')->fail('标签不存在');
        }
        $label_id = implode(',', $label_id);
        $this->repository->update($id, compact('label_id'));
        return app('json')->success('修改成功');
    }

    /**
     * @param UserLabelRepository $labelRepository
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-08
     */
    public function batchChangeLabel(UserLabelRepository $labelRepository)
    {
        $label_id = (array)$this->request->param('label_id', []);
        $ids = (array)$this->request->param('ids', []);
        if (!count($ids))
            return app('json')->fail('数据不存在');
        foreach ($label_id as $k => $value) {
            $label_id[$k] = (int)$value;
            if (!$labelRepository->exists((int)$value))
                return app('json')->fail('标签不存在');
        }
        $this->repository->batchChangeLabelId($ids, $label_id);
        return app('json')->success('修改成功');
    }


    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-08
     */
    public function changeLabelForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->changeLabelForm($id)));
    }


    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-08
     */
    public function batchChangeLabelForm()
    {
        $ids = $this->request->param('ids', '');
        $ids = array_filter(explode(',', $ids));
        if (!count($ids))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->changeLabelForm($ids)));
    }


    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-08
     */
    public function batchChangeGroupForm()
    {
        $ids = $this->request->param('ids', '');
        $ids = array_filter(explode(',', $ids));
        if (!count($ids))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->changeGroupForm($ids)));
    }

    /**
     * @param $id
     * @param UserGroupRepository $groupRepository
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-07
     */
    public function changeGroup($id, UserGroupRepository $groupRepository)
    {
        $group_id = (int)$this->request->param('group_id', 0);
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        if ($group_id && !$groupRepository->exists($group_id))
            return app('json')->fail('分组不存在');
        $this->repository->update($id, compact('group_id'));
        return app('json')->success('修改成功');
    }

    /**
     * @param UserGroupRepository $groupRepository
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-07
     */
    public function batchChangeGroup(UserGroupRepository $groupRepository)
    {
        $group_id = (int)$this->request->param('group_id', 0);
        $ids = (array)$this->request->param('ids', []);
        if (!count($ids))
            return app('json')->fail('数据不存在');
        if ($group_id && !$groupRepository->exists($group_id))
            return app('json')->fail('分组不存在');
        $this->repository->batchChangeGroupId($ids, $group_id);
        return app('json')->success('修改成功');
    }

    /**
     * @param $id
     * @return mixed
     * @throws FormBuilderException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-07
     */
    public function changeGroupForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->changeGroupForm($id)));
    }

    /**
     * @param $id
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-05-07
     */
    public function changeNowMoneyForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->changeNowMoneyForm($id)));
    }

    public function changeIntegralForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->changeIntegralForm($id)));
    }

    /**
     * @param $id
     * @param UserNowMoneyValidate $validate
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-07
     */
    public function changeNowMoney($id, UserNowMoneyValidate $validate)
    {
        $data = $this->request->params(['now_money', 'type']);
        $validate->check($data);
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->changeNowMoney($id, $this->request->adminId(), $data['type'], $data['now_money']);

        return app('json')->success('修改成功');
    }

    public function changeIntegral($id, UserNowMoneyValidate $validate)
    {
        $data = $this->request->params(['now_money', 'type']);
        $validate->check($data);
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->changeIntegral($id, $this->request->adminId(), $data['type'], $data['now_money']);

        return app('json')->success('修改成功');
    }

    /**
     * @param WechatNewsRepository $wechatNewsRepository
     * @param WechatUserRepository $wechatUserRepository
     * @return mixed
     * @author xaboy
     * @day 2020-05-11
     */
    public function sendNews(WechatNewsRepository $wechatNewsRepository, WechatUserRepository $wechatUserRepository)
    {
        $ids = $this->request->param('ids');
        if (!is_array($ids)) $ids = explode(',', $this->request->param('ids'));
        $ids = array_filter(array_unique($ids));
        $news_id = (int)$this->request->param('news_id', 0);
        if (!$news_id)
            return app('json')->fail('请选择图文消息');
        if (!$wechatNewsRepository->exists($news_id))
            return app('json')->fail('数据不存在');
        if (!count($ids))
            return app('json')->fail('请选择微信用户');
        $wechatUserRepository->sendNews($news_id, $ids);
        return app('json')->success('发送成功');
    }

    public function promoterList()
    {
        $where = $this->request->params(['keyword', 'date', 'brokerage_level']);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->promoterList($where, $page, $limit));
    }

    public function promoterCount()
    {
        $where = $this->request->params(['keyword', 'date', 'brokerage_level']);
        return app('json')->success($this->repository->promoterCount($where));
    }

    public function detail($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->userOrderDetail($id));
    }

    public function order($id, StoreOrderRepository $repository)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        [$page, $limit] = $this->getPage();
        return app('json')->success($repository->userList($id, $page, $limit));
    }

    public function coupon($id, StoreCouponUserRepository $repository)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        [$page, $limit] = $this->getPage();
        return app('json')->success($repository->userList(['uid' => $id], $page, $limit));
    }

    public function bill($id, UserBillRepository $repository)
    {
        if (!$this->repository->exists(intval($id)))
            return app('json')->fail('数据不存在');
        [$page, $limit] = $this->getPage();
        return app('json')->success($repository->userList([
            'now_money' => 0,
            'status' => 1
        ], $id, $page, $limit));
    }

    public function spreadLog($id)
    {
        if (!$this->repository->exists((int)$id))
            return app('json')->fail('数据不存在');
        [$page, $limit] = $this->getPage();
        return app('json')->success(app()->make(UserSpreadLogRepository::class)->getList(['uid' => $id], $page, $limit));
    }

    public function spreadForm($id)
    {
        if (!$this->repository->exists((int)$id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->changeSpreadForm($id)));
    }

    public function spread($id)
    {
        if (!$this->repository->exists((int)$id))
            return app('json')->fail('数据不存在');
        $spid = $this->request->param('spid');
        $spid = (int)($spid['id'] ?? $spid);
        if ($spid == $id)
            return app('json')->fail('不能选自己');
        if ($spid && !$this->repository->exists($spid))
            return app('json')->fail('推荐人不存在');
        $this->repository->changeSpread($id, $spid, $this->request->adminId());
        return app('json')->success('修改成功');
    }

    public function searchLog()
    {
        $where = $this->request->params(['date', 'keyword', 'nickname', 'user_type']);
        $merId = $this->request->merId();
        $where['type'] = ['searchMerchant', 'searchProduct'];
        if ($merId) {
            $where['mer_id'] = $merId;
        }
        [$page, $limit] = $this->getPage();
        return app('json')->success(app()->make(UserVisitRepository::class)->getSearchLog($where, $page, $limit));
    }

    public function exportSearchLog()
    {
        $where = $this->request->params(['date', 'keyword', 'nickname', 'user_type']);
        $merId = $this->request->merId();
        $where['type'] = ['searchMerchant', 'searchProduct'];
        if ($merId) {
            $where['mer_id'] = $merId;
        }
        [$page, $limit] = $this->getPage();
        $data = app()->make(ExcelService::class)->searchLog($where, $page, $limit);
        return app('json')->success($data);

    }

    public function memberForm($id)
    {
        return app('json')->success(formToData($this->repository->memberForm($id,1)));
    }

    public function memberSave($id)
    {
        $data = $this->request->params(['member_level']);
        if (!$this->repository->exists((int)$id))
            return app('json')->fail('数据不存在');
        $this->repository->updateLevel($id, $data, 1);
        return app('json')->success('修改成功');
    }

    public function spreadLevelForm($id)
    {
        return app('json')->success(formToData($this->repository->memberForm($id,0)));
    }

    public function spreadLevelSave($id)
    {
        $brokerage_level = $this->request->params(['brokerage_level']);
        if (!$this->repository->exists((int)$id))
            return app('json')->fail('数据不存在');
        $this->repository->updateLevel($id, $brokerage_level, 0);
        return app('json')->success('修改成功');
    }

    public function svipForm($id)
    {
        return app('json')->success(formToData($this->repository->svipForm($id)));
    }

    public function svipUpdate($id)
    {
        $data = $this->request->params(['is_svip','add_time','type']);
        $this->repository->svipUpdate($id, $data,$this->request->adminId());
        return app('json')->success('修改成功');
    }

    /**
     * TODO 积分记录
     * @param $id
     * @author Qinii
     * @day 2023/4/25
     */
    public function integralList($id, UserBillRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $data = $repository->userList(['category' => 'integral'], $id, $page, $limit);
        return app('json')->success($data);
    }


    /**
     * TODO 签到记录
     * @param $id
     * @param UserSignRepository $signRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 2023/4/25
     */
    public function sign_log($id, UserSignRepository $signRepository)
    {
        [$page,$limit] = $this->getPage();
        $where = ['uid' => $id];
        $data =$signRepository->getList($where,$page,$limit);
        return app('json')->success($data);
    }

    public function history($id, UserHistoryRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        return app('json')->success($repository->getList( $page, $limit, $id, 1));
    }
}
