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

namespace app\common\repositories\system\merchant;

use app\common\repositories\BaseRepository;
use crmeb\jobs\SendSmsJob;
use crmeb\services\SmsService;
use FormBuilder\Factory\Elm;
use app\common\dao\system\merchant\MerchantIntentionDao;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Route;

/**
 * @mixin MerchantIntentionDao
 */
class MerchantIntentionRepository extends BaseRepository
{

    public function __construct(MerchantIntentionDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('create_time DESC , status ASC')->with(['merchantCategory', 'merchantType'])->select();

        return compact('count', 'list');
    }

    public function detail($id, ?int $uid)
    {
        $where = ['mer_intention_id' => $id];
        if (!is_null($uid)) {
            $where['uid'] = $uid;
        }
        return $this->dao->search($where)->find();
    }

    public function updateIntention($id, array $data)
    {
        if ($this->dao->existsWhere(['mer_intention_id' => $id, 'status' => '1']))
            throw new ValidateException('当前状态不能修改');
        $data['images'] = implode(',', (array)$data['images']);
        $data['status'] = 0;
        $data['fail_msg'] = '';
        return $this->dao->update($id, $data);
    }

    public function markForm($id)
    {
        $data = $this->dao->get($id);
        $form = Elm::createForm(Route::buildUrl('systemMerchantIntentionMark', ['id' => $id])->build());
        $form->setRule([
            Elm::textarea('mark', '备注', $data['mark'])->required(),
        ]);
        return $form->setTitle('修改备注');
    }

    public function statusForm($id)
    {
        $form = Elm::createForm(Route::buildUrl('systemMerchantIntentionStatus', ['id' => $id])->build());
        $form->setRule([
            Elm::select('status', '审核状态', 1)->options([
                ['value' => 1, 'label' => '同意'],
                ['value' => 2, 'label' => '拒绝'],
            ])->control([
                [
                    'value' => 1,
                    'rule' => [
                        Elm::radio('create_mer', '自动创建商户', 1)->options([
                            ['value' => 1, 'label' => '创建'],
                            ['value' => 2, 'label' => '不创建'],
                        ])
                    ]
                ],
                [
                    'value' => 2,
                    'rule' => [
                        Elm::textarea('fail_msg', '失败原因', '信息填写有误')
                    ]
                ]
            ]),
        ]);
        return $form->setTitle('修改审核状态');
    }

    public function updateStatus($id, $data)
    {
        $create = $data['create_mer'] == 1;
        unset($data['create_mer']);
        $intention = $this->search(['mer_intention_id' => $id])->find();
        if (!$intention)
            throw new ValidateException('信息不存在');
        if ($intention->status)
            throw new ValidateException('状态有误,修改失败');
        $config = systemConfig(['broadcast_room_type', 'broadcast_goods_type']);

        $margin = app()->make(MerchantTypeRepository::class)->get($intention['mer_type_id']);
        $data['is_margin'] = $margin['is_margin'] ?? -1;
        $data['margin'] = $margin['margin'] ?? 0;
        $merData = [];
        if ($create) {
            $password = substr($intention['phone'], -6);
            $merData = [
                'mer_name' => $intention['mer_name'],
                'mer_phone' => $intention['phone'],
                'mer_account' => $intention['phone'],
                'category_id' => $intention['merchant_category_id'],
                'type_id' => $intention['mer_type_id'],
                'real_name' => $intention['name'],
                'status' => 1,
                'is_audit' => 1,
                'is_bro_room' => $config['broadcast_room_type'] == 1 ? 0 : 1,
                'is_bro_goods' => $config['broadcast_goods_type'] == 1 ? 0 : 1,
                'mer_password' => $password,
                'is_margin' => $margin['is_margin'] ?? -1,
                'margin' => $margin['margin'] ?? 0
            ];
            $data['fail_msg'] = '';
            $smsData = [
                'date' => date('m月d日', strtotime($intention->create_time)),
                'mer' => $intention['mer_name'],
                'phone' => $intention['phone'],
                'pwd' => $password ?? '',
                'site_name' => systemConfig('site_name'),
            ];
        }
        if ($data['status'] == 2) {
            $smsData = [
                'phone' => $intention['phone'],
                'date' => date('m月d日', strtotime($intention->create_time)),
                'mer' => $intention['mer_name'],
                'site' => systemConfig('site_name'),
            ];
        }

        Db::transaction(function () use ($config, $intention, $data, $create,$margin,$merData,$smsData) {
            if ($data['status'] == 1) {
                if ($create) {
                    $merchant = app()->make(MerchantRepository::class)->createMerchant($merData);
                    $data['mer_id'] = $merchant->mer_id;
                    Queue::push(SendSmsJob::class, ['tempId' => 'APPLY_MER_SUCCESS', 'id' => $smsData]);
                }
            } else {
                Queue::push(SendSmsJob::class, ['tempId' => 'APPLY_MER_FAIL', 'id' => $smsData]);
            }
            $intention->save($data);
        });
    }

}
