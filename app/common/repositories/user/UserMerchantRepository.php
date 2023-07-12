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


namespace app\common\repositories\user;


use app\common\dao\user\UserMerchantDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\facade\Db;
use think\facade\Route;

/**
 * Class UserMerchantRepository
 * @package app\common\repositories\user
 * @author xaboy
 * @day 2020/10/20
 * @mixin UserMerchantDao
 */
class UserMerchantRepository extends BaseRepository
{
    /**
     * UserMerchantRepository constructor.
     * @param UserMerchantDao $dao
     */
    public function __construct(UserMerchantDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $make = app()->make(UserLabelRepository::class);
        $list = $query->setOption('field', [])->field('A.uid,A.user_merchant_id,B.avatar,B.nickname,B.user_type,A.last_pay_time,A.first_pay_time,A.label_id,A.create_time,A.last_time,A.pay_num,A.pay_price,B.phone,B.is_svip,B.svip_endtime')
            ->page($page, $limit)->order('A.user_merchant_id DESC')->select()->each(function ($item) use ($where, $make) {
                return $item->label = count($item['label_id']) ? $make->labels($item['label_id'], $where['mer_id']) : [];
            });
        return compact('count', 'list');
    }

    /**
     * @param $uid
     * @param $merId
     * @return \app\common\dao\BaseDao|\think\Model
     * @author xaboy
     * @day 2020/10/20
     */
    public function create($uid, $merId)
    {
        return $this->dao->create([
            'uid' => $uid,
            'mer_id' => $merId,
        ]);
    }

    /**
     * @param $uid
     * @param $mer_id
     * @return \app\common\dao\BaseDao|array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/10/20
     */
    public function getInfo($uid, $mer_id)
    {
        $user = $this->dao->getWhere(compact('uid', 'mer_id'));
        if (!$user) $user = $this->create($uid, $mer_id);
        return $user;
    }

    /**
     * @param $uid
     * @param $merId
     * @param $pay_price
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/10/21
     */
    public function updatePayTime($uid, $merId, $pay_price, $flag = true)
    {
        $user = $this->getInfo($uid, $merId);
        $time = date('Y-m-d H:i:s');
        $user->last_pay_time = $time;
        if ($flag)
            $user->pay_num++;
        $user->pay_price = bcadd($user->pay_price, $pay_price, 2);
        if (!$user->first_pay_time) $user->first_pay_time = $time;
        $user->save();
    }

    public function rmLabel($id)
    {
        return $this->dao->search(['label_id' => $id])->update([
            'A.label_id' => Db::raw('(trim(BOTH \',\' FROM replace(CONCAT(\',\',A.label_id,\',\'),\',' . $id . ',\',\',\')))')
        ]);
    }

    public function changeLabelForm($merId, $id)
    {
        $user = $this->dao->get($id);

        /** @var UserLabelRepository $make */
        $userLabelRepository = app()->make(UserLabelRepository::class);
        $data = $userLabelRepository->allOptions($merId);
        return Elm::createForm(Route::buildUrl('merchantUserChangeLabel', compact('id'))->build(), [
            Elm::selectMultiple('label_id', '用户标签', $userLabelRepository->intersection($user->label_id, $merId, 0))->options(function () use ($data) {
                $options = [];
                foreach ($data as $value => $label) {
                    $options[] = compact('value', 'label');
                }
                return $options;
            })
        ])->setTitle('修改用户标签');
    }
}
