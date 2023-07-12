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
use app\common\dao\user\UserOrderDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use crmeb\jobs\SendSmsJob;
use crmeb\services\PayService;
use FormBuilder\Factory\Elm;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

/**
 * Class LabelRuleRepository
 * @package app\common\repositories\user
 * @author xaboy
 * @day 2020/10/20
 * @mixin LabelRuleDao
 */
class UserOrderRepository extends BaseRepository
{

    //付费会员
    const TYPE_SVIP = 'S-';

    /**
     * LabelRuleRepository constructor.
     * @param LabelRuleDao $dao
     */
    public function __construct(UserOrderDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->with([
            'user' => function($query){
                $query->field('uid,nickname,avatar,phone,is_svip,svip_endtime');
            }
        ])->order('create_time DESC')->page($page, $limit)->select()->toArray();
        return compact('count', 'list');
    }

    /**
     * @param $data
     * @return mixed
     * @author xaboy
     * @day 2020/10/21
     */
    public function add($res, $user, $params)
    {
        $order_sn = app()->make(StoreOrderRepository::class)->getNewOrderId(StoreOrderRepository::TYPE_SN_USER_ORDER);
        $data = [
            'title'     => $res['value']['svip_name'],
            'link_id'   => $res->group_data_id,
            'order_sn'  => $order_sn,
            'pay_price' => $res['value']['price'],
            'order_info' => json_encode($res['value'],JSON_UNESCAPED_UNICODE),
            'uid'        => $user->uid,
            'order_type' => self::TYPE_SVIP.$res['value']['svip_type'],
            'pay_type'   => $res['value']['price'] == 0 ? 'free' : $params['pay_type'],
            'status'     => 1,
            'other'     => $user->is_svip == -1 ? 'first' : '',
        ];
        $body = [
            'order_sn' => $order_sn,
            'pay_price' => $data['pay_price'],
            'attach' => 'user_order',
            'body' =>'付费会员'
        ];
        $type = $params['pay_type'];
        if (in_array($type, ['weixin', 'alipay'], true) && $params['is_app']) {
            $type .= 'App';
        }
        if ($params['return_url'] && $type === 'alipay') $body['return_url'] = $params['return_url'];
        $info = $this->dao->create($data);
        if ($data['pay_price']){
            try {
                $service = new PayService($type,$body, 'user_order');
                $config = $service->pay($user);
                return app('json')->status($type, $config + ['order_id' => $info->order_id]);
            } catch (\Exception $e) {
                return app('json')->status('error', $e->getMessage(), ['order_id' => $info->order_id]);
            }
        } else {
            $res = $this->paySuccess($data);
            return app('json')->status('success', ['order_id' => $info->order_id]);
        }
    }

    public function paySuccess($data)
    {
        /*
          array (
            'order_sn' => 'wxs167090166498470921',
            'data' =>
            EasyWeChat\Support\Collection::__set_state(array(
               'items' =>
              array (
                'appid' => 'wx4409eaedbd62b213',
                'attach' => 'user_order',
                'bank_type' => 'OTHERS',
                'cash_fee' => '1',
                'fee_type' => 'CNY',
                'is_subscribe' => 'N',
                'mch_id' => '1288093001',
                'nonce_str' => '6397efa100165',
                'openid' => 'oOdvCvjvCG0FnCwcMdDD_xIODRO0',
                'out_trade_no' => 'wxs167090166498470921',
                'result_code' => 'SUCCESS',
                'return_code' => 'SUCCESS',
                'sign' => '125C56DE030A461E45D421E44C88BC30',
                'time_end' => '20221213112118',
                'total_fee' => '1',
                'trade_type' => 'JSAPI',
                'transaction_id' => '4200001656202212131458556229',
              ),
        )),
         */
        $res = $this->dao->getWhere(['order_sn' => $data['order_sn']]);
        $type = explode('-',$res['order_type'])[0].'-';
        // 付费会员充值
        if ($type == self::TYPE_SVIP) {
            return Db::transaction(function () use($data, $res) {
                $res->paid = 1;
                $res->pay_time = date('y_m-d H:i:s', time());
                $res->save();
                return $this->payAfter($res, $res);
            });
        }
    }

    public function payAfter($data, $ret)
    {
        $info = json_decode($data['order_info']);
        $user = app()->make(UserRepository::class)->get($ret['uid']);
        $day = $info->svip_type == 3 ? 0 : $info->svip_number;
        $endtime = ($user['svip_endtime'] && $user['is_svip'] != 0) ? $user['svip_endtime'] : date('Y-m-d H:i:s',time());
        $svip_endtime =  date('Y-m-d H:i:s',strtotime("$endtime  +$day day" ));

        $user->is_svip = $info->svip_type;
        $user->svip_endtime = $svip_endtime;
        $user->save();
        $ret->status = 1;
        $ret->pay_time = date('Y-m-d H:i:s',time());
        $ret->end_time = $svip_endtime;
        $ret->save();
        $date = $info->svip_type == 3 ? '终身会员' : $svip_endtime;
        if ($user->phone) Queue::push(SendSmsJob::class,['tempId' => 'SVIP_PAY_SUCCESS','id' => ['phone' => $user->phone, 'date' => $date]]);
        return ;
    }
}
