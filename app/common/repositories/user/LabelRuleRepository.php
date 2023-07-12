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


use app\common\dao\user\LabelRuleDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\facade\Db;

/**
 * Class LabelRuleRepository
 * @package app\common\repositories\user
 * @author xaboy
 * @day 2020/10/20
 * @mixin LabelRuleDao
 */
class LabelRuleRepository extends BaseRepository
{
    /**
     * LabelRuleRepository constructor.
     * @param LabelRuleDao $dao
     */
    public function __construct(LabelRuleDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->with(['label'])->order('label_rule_id DESC')->page($page, $limit)->select()->toArray();
        return compact('count', 'list');
    }

    /**
     * @param $data
     * @return mixed
     * @author xaboy
     * @day 2020/10/21
     */
    public function create($data)
    {
        return Db::transaction(function () use ($data) {
            $labelName = $data['label_name'];
            unset($data['label_name']);
            $label = app()->make(UserLabelRepository::class)->create([
                'label_name' => $labelName,
                'mer_id' => $data['mer_id'],
                'type' => 1
            ]);
            $data['label_id'] = $label->label_id;
            return $this->dao->create($data);
        });
    }

    /**
     * @param $id
     * @param $data
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/10/21
     */
    public function update($id, $data)
    {
        $rule = $this->dao->get($id);

        return Db::transaction(function () use ($data, $rule) {
            $labelName = $data['label_name'];
            unset($data['mer_id'], $data['label_name']);
            app()->make(UserLabelRepository::class)->update($rule->label_id, ['label_name' => $labelName]);
            return $rule->save($data);
        });
    }

    /**
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/10/21
     */
    public function delete($id)
    {
        $rule = $this->dao->get($id);
        return Db::transaction(function () use ($rule) {
            app()->make(UserLabelRepository::class)->delete($rule->label_id);
            app()->make(UserRepository::class)->rmLabel($rule->label_id);
            return $rule->delete();
        });
    }

    /**
     * @param $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/10/21
     */
    public function syncUserNum($id)
    {
        $rule = $this->dao->get($id);
        $rule->update_time = date('Y-m-d H:i:s');
        $ids = [];
        $userMerchantRepository = app()->make(UserMerchantRepository::class);
        //订单金额
        if ($rule->type == 1) {
            $ids = $userMerchantRepository->priceUserIds($rule->mer_id, $rule->min, $rule->max);
            //订单数
        } else if ($rule->type == 0) {
            $ids = $userMerchantRepository->numUserIds($rule->mer_id, $rule->min, $rule->max);
        }
        $rule->user_num = count($ids);
        $idList = array_chunk($ids, 50);
        Db::transaction(function () use ($rule, $idList, $userMerchantRepository) {
            $userMerchantRepository->rmLabel($rule->label_id);
            foreach ($idList as $ids) {
                $userMerchantRepository->search(['uids' => $ids])->update([
                    'A.label_id' => Db::raw('trim(BOTH \',\' FROM CONCAT(IFNULL(A.label_id,\'\'),\',' . $rule->label_id . '\'))')
                ]);
            }
            $rule->save();
        });
    }

}
