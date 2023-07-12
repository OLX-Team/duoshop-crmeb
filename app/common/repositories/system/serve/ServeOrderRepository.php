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

namespace app\common\repositories\system\serve;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\AddShortUrlResponseBody\data;
use app\common\dao\system\serve\ServeOrderDao;
use app\common\model\system\serve\ServeOrder;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\product\ProductCopyRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserRepository;
use crmeb\services\CombinePayService;
use crmeb\services\PayService;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

class ServeOrderRepository extends BaseRepository
{
    protected $dao;

    public function __construct(ServeOrderDao $dao)
    {
        $this->dao = $dao;
    }
    //复制商品
    const TYPE_COPY_PRODUCT = 1;
    //电子面单
    const TYPE_DUMP = 2;
    //保证金 margin
    const TYPE_MARGIN = 10;
    //补缴
    const TYPE_MARGIN_MAKE_UP = 11;
    //同城配送delivery
    const TYPE_DELIVERY = 20;

    const PAY_TYPE_WEIXIN = 1;
    const PAY_TYPE_ALIPAY= 2;
    const PAY_TYPE_SYS = 3;


    /**
     * TODO 购买一号通 支付
     * @param $merId
     * @param $data
     * @return array
     * @author Qinii
     * @day 1/26/22
     */
    public function meal($merId, $data)
    {
        $ret = app()->make(ServeMealRepository::class)->get($data['meal_id']);
        if(!$ret)  throw new ValidateException('数据不存在');
        $key = 'Meal_'.$merId.'_'.$data['meal_id'].'_'.date('YmdH',time());
        $arr = [
            'meal_id' => $ret['meal_id'],
            'name'    => $ret['name'],
            'num'     => $ret['num'],
            'price'   => $ret['price'],
            'type'    => $ret['type'],
        ];
        $param = [
            'status' => 0,
            'is_del' => 0,
            'mer_id' => $merId,
            'type'   => $ret['type'],
            'meal_id'=> $ret['meal_id'],
            'pay_type' => $data['pay_type'],
            'attach' => 'meal',
            'order_info' => json_encode($arr,JSON_UNESCAPED_UNICODE),
            'pay_price' => $ret['price'],
        ];
        return compact('key', 'param');
    }

    /**
     * TODO 商户保证金 支付
     * @param $merId
     * @param $data
     * @return array
     * @author Qinii
     * @day 1/26/22
     */
    public function margin($merId, $data)
    {
        $ret = app()->make(MerchantRepository::class)->get($merId);
        if ($ret['is_margin'] !== 1 && $data['type'] == 10)
            throw new ValidateException('此商户无需支付保证金');
        if ($data['type'] == self::TYPE_MARGIN_MAKE_UP) {
            $margin = bcsub($ret->ot_margin,$ret->margin,2);
        } else {
            $margin = $ret['margin'];
        }
        $key = 'Margin_'.$merId.'_'.date('YmdH',time());
        $arr = [
            'type_id'   => $ret['type_id'],
            'is_margin' => $ret['is_margin'],
            'margin'    => $margin,
        ];
        $param = [
            'status' => 0,
            'is_del' => 0,
            'mer_id' => $merId,
            'type'   => self::TYPE_MARGIN,
            'meal_id'=> $ret['type_id'],
            'pay_type' => $data['pay_type'],
            'attach' => 'meal',
            'order_info' => json_encode($arr,JSON_UNESCAPED_UNICODE),
            'pay_price' => $margin
        ];
        return compact('key', 'param');
    }

    public function delivery($merId, $data)
    {
        $key = 'Delivery_'.$merId.'_'.md5(date('YmdH',time()).$data['price']);
        $arr = ['price' => $data['price']];
        $param = [
            'status' => 0,
            'is_del' => 0,
            'mer_id' => $merId,
            'type'   => 20,
            'meal_id'=> 0,
            'pay_type' => $data['pay_type'],
            'attach' => 'meal',
            'order_info' => json_encode($arr,JSON_UNESCAPED_UNICODE),
            'pay_price' => $data['price'],
        ];

        return compact('key', 'param');
    }


    public function QrCode(int $merId, string $type, array $data)
    {
        $res = $this->{$type}($merId, $data);
        $key = $res['key'];
        $param = $res['param'];

        if(!$result = Cache::store('file')->get($key)){
            $order_sn = app()->make(StoreOrderRepository::class)->getNewOrderId(StoreOrderRepository::TYPE_SN_SERVER_ORDER);
            $param['order_sn'] = $order_sn;
            $param['body'] = $order_sn;
            $payType = $data['pay_type'] == 1 ? 'weixinQr' : 'alipayQr';
            $service = new PayService($payType,$param);
            $code = $service->pay(null);

            $endtime = time() + 1800 ;
            $result = [
                'config' => $code['config'],
                'endtime'=> date('Y-m-d H:i:s',$endtime),
                'price'  => $param['pay_price']
            ];
            Cache::store('file')->set($key,$result,30);
            $param['key'] = $key;
            Cache::store('file')->set($order_sn,$param,60 * 24);
        }

        return $result;
    }

    public function paySuccess($data)
    {
        $get = $this->dao->getWhere(['order_sn' => $data['order_sn']]);
        if(!$get){
            $dat = Cache::store('file')->get($data['order_sn']);
            $key = $dat['key'];
            unset($dat['attach'],$dat['body'],$dat['key']);

            $dat['status'] = 1;
            $dat['pay_time'] = date('y_m-d H:i:s', time());
            Db::transaction(function () use($data, $dat,$key){
                $res = $this->dao->create($dat);
                $this->payAfter($dat,$res);
                Cache::store('file')->delete($data['order_sn']);
                Cache::store('file')->delete($key);
            });
        }
    }

    public function payAfter($dat, $ret = null)
    {
        $info = json_decode($dat['order_info']);
        switch ($dat['type']) {
            case self::TYPE_COPY_PRODUCT:
                app()->make(ProductCopyRepository::class)->add([
                    'type'=> 'pay_copy',
                    'num' => $info->num,
                    'info' => $dat['order_info'],
                    'message' =>  '购买复制商品套餐' ,
                ], $dat['mer_id']);
                break;
            case self::TYPE_DUMP:
                app()->make(ProductCopyRepository::class)->add([
                    'type'=>'pay_dump',
                    'num' => $info->num,
                    'info'=>$dat['order_info'],
                    'message' => '购买电子面单套餐',
                    ], $dat['mer_id']);
                break;
            case self::TYPE_MARGIN:
                $res = app()->make(MerchantRepository::class)->get($dat['mer_id']);
                if ($res['is_margin'] == 1) {
                    $margin = $res->margin;
                    $ot_margin = $res->margin;
                    $_margin = $res->margin;
                } else {
                    $margin =  $res->ot_margin;
                    $ot_margin = $res->ot_margin;
                    $_margin = bcsub( $res->ot_margin,$res->margin,2);
                }
                if (bccomp($_margin, $dat['pay_price'],  2) === 0) {
                    $res->ot_margin = $ot_margin;
                    $res->margin =$margin;
                    $res->margin_remind_time = '';
                    $res->is_margin = 10;
                } else {
                    //支付金额与订单金额不一致
                    $res->is_margin = 20;
                }
                $res->save();
                $bill = [
                    'title' => '线上缴纳保证金',
                    'mer_id' => $dat['mer_id'],
                    'number' => $dat['pay_price'],
                    'mark'   => '缴纳保证金',
                    'balance'=> $res->margin,
                ];
                app()->make(UserBillRepository::class)->bill(0, 'mer_margin', 'mer_margin', 1, $bill);
                break;
            case self::TYPE_MARGIN_MAKE_UP:
                $res = app()->make(MerchantRepository::class)->get($dat['mer_id']);
                if (bccomp($res['margin'] , $dat['pay_price'],  2) === 0) {
                    $res->margin = bcadd($res['margin'],$dat['pay_price'],2);
                    $res->margin_remind_time = '';
                    $res->is_margin = 10;
                } else {
                    //支付金额与订单金额不一致
                    $res->is_margin = 20;
                }
                $res->save();
                $bill = [
                    'mer_id' => $dat['mer_id'],
                    'number' => $dat['pay_price'],
                    'mark'   => '线上补缴保证金',
                    'balance'=> $res->margin,
                ];
                app()->make(UserBillRepository::class)->bill(0, 'mer_margin', 'pay_margin', 1,$bill);
                break;
            case self::TYPE_DELIVERY:
                $res = app()->make(MerchantRepository::class)->get($dat['mer_id']);
                if ($res) {
                    $res->delivery_balance = bcadd($res->delivery_balance, $dat['pay_price'],2);
                    $res->save();
                } else {
                    Log::info('同城配送充值异常 ：'. json_encode($dat));
                }
                break;
            default:
                break;
        }
        return ;
    }


    public function getList(array $where, int $page, int $limit)
    {
        $where['is_del'] = 0;
        $query = $this->dao->search($where)->with([
            'merchant' => function($query){
                $query->with(['merchantType']);
                $query->field('mer_id,mer_name,is_trader,mer_avatar,type_id,mer_phone,mer_address,is_margin,margin,real_name,ot_margin');
            }
        ])->order('ServeOrder.create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count','list');
    }
}
