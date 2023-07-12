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


namespace app\controller\merchant\system;


use app\common\repositories\store\MerchantTakeRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\serve\ServeOrderRepository;
use app\common\repositories\user\UserBillRepository;
use app\validate\merchant\MerchantTakeValidate;
use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantRepository;
use app\validate\merchant\MerchantUpdateValidate;
use crmeb\jobs\ChangeMerchantStatusJob;
use think\App;
use think\facade\Queue;

/**
 * Class Merchant
 * @package app\controller\merchant\system
 * @author xaboy
 * @day 2020/6/25
 */
class Merchant extends BaseController
{
    /**
     * @var MerchantRepository
     */
    protected $repository;

    /**
     * Merchant constructor.
     * @param App $app
     * @param MerchantRepository $repository
     */
    public function __construct(App $app, MerchantRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @author xaboy
     * @day 2020/6/25
     */
    public function updateForm()
    {
        return app('json')->success(formToData($this->repository->merchantForm($this->request->merchant()->toArray())));
    }

    /**
     * @param MerchantUpdateValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public function update(MerchantUpdateValidate $validate, MerchantTakeValidate $takeValidate, MerchantTakeRepository $repository)
    {

        $type = $this->request->param('type',1);
        $merchant = $this->request->merchant();
        if ($type == 2) {
            $data = $this->request->params([
                'mer_info',
                'mer_certificate',
                'service_phone',
                'mer_avatar',
                'mer_banner',
                'mer_state',
                'mini_banner',
                'mer_keyword',
                'mer_address',
                'long',
                'lat',
                ['delivery_way',[2]],
            ]);
            $validate->check($data);
            $sys_bases_status = systemConfig('sys_bases_status') === '0' ? 0 : 1;
            if ($sys_bases_status && empty($data['mer_certificate']))
                return app('json')->fail('店铺资质不可为空');

            app()->make(ConfigValueRepository::class)->setFormData([
                'mer_certificate' => $data['mer_certificate']
            ], $this->request->merId());
            unset($data['mer_certificate']);

            foreach ($data['delivery_way'] as $datum) {
                if ($datum == 1) {
                    $takeData = $this->request->params(['mer_take_status', 'mer_take_name', 'mer_take_phone', 'mer_take_address', 'mer_take_location', 'mer_take_day', 'mer_take_time']);
                    $takeValidate->check($takeData);
                    $repository->set($this->request->merId(), $takeData);
                    break;
                }
            }
            $delivery_way = implode(',',$data['delivery_way']);
            if (count($data['delivery_way']) == 1 && $data['delivery_way'] != $merchant->delivery_way) {
                app()->make(ProductRepository::class)->getSearch([])
                    ->where('mer_id',$merchant->mer_id)
                    ->update(['delivery_way' => $delivery_way]);
            }

            $data['delivery_way'] = $delivery_way;

        } else {
            $data = $this->request->params(['mer_state']);

            if ($merchant->is_margin == 1 && $data['mer_state'] == 1)
                return app('json')->fail('开启店铺前请先支付保证金');

            if ($data['mer_state'] && !$merchant->sub_mchid && systemConfig('open_wx_combine'))
                return app('json')->fail('开启店铺前请先完成微信子商户入驻');
        }
        $merchant->save($data);

        Queue::push(ChangeMerchantStatusJob::class, $this->request->merId());
        return app('json')->success('修改成功');
    }


    /**
     * @return mixed
     * @author xaboy
     * @day 2020/7/21
     */
    public function info(MerchantTakeRepository $repository)
    {
        $merchant = $this->request->merchant();
        $append = ['merchantCategory', 'merchantType', 'mer_certificate','margin_remind_status'];
        if ($merchant->is_margin == -10)
            $append[] = 'refundMarginOrder';

        $data = $merchant->append($append)->hidden(['mark', 'reg_admin_id', 'sort'])->toArray();
        $delivery = $repository->get($this->request->merId()) + systemConfig(['tx_map_key']);
        $data = array_merge($data,$delivery);
        $data['sys_bases_status'] = systemConfig('sys_bases_status') === '0' ? 0 : 1;

        return app('json')->success($data);
    }

    /**
     * @param MerchantTakeRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/8/1
     */
    public function takeInfo(MerchantTakeRepository $repository)
    {
        $merId = $this->request->merId();
        return app('json')->success($repository->get($merId) + systemConfig(['tx_map_key']));
    }

    /**
     * @param MerchantTakeValidate $validate
     * @param MerchantTakeRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/8/1
     */
    public function take(MerchantTakeValidate $validate, MerchantTakeRepository $repository)
    {
        $data = $this->request->params(['mer_take_status', 'mer_take_name', 'mer_take_phone', 'mer_take_address', 'mer_take_location', 'mer_take_day', 'mer_take_time']);
        $validate->check($data);
        $repository->set($this->request->merId(), $data);
        return app('json')->success('设置成功');
    }


    public function getMarginQrCode()
    {
        $data['pay_type'] = 1;
        $data['type'] = $this->request->param('type',10);
        $res = app()->make(ServeOrderRepository::class)->QrCode($this->request->merId(), 'margin', $data);
        return app('json')->success($res);
    }

    public function getMarginLst()
    {
        [$page, $limit] = $this->getPage();
        $where = [
            'mer_id' => $this->request->merId(),
            'category' => 'mer_margin'
        ];
        $data = app()->make(UserBillRepository::class)->getLst($where, $page, $limit);
        return app('json')->success($data);
    }
}
