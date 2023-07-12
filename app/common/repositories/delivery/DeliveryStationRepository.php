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
namespace app\common\repositories\delivery;

use app\common\dao\delivery\DeliveryStationDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\config\ConfigClassifyRepository;
use app\common\repositories\system\config\ConfigValueRepository;
use crmeb\services\DeliverySevices;
use FormBuilder\Factory\Elm;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Route;

class DeliveryStationRepository extends BaseRepository
{
    public function __construct(DeliveryStationDao $dao)
    {
        $this->dao = $dao;
    }

    public function deliveryForm()
    {
        $formData = systemConfig([
            'delivery_status',
            'uupt_appkey',
            'uupt_app_id',
            'uupt_open_id',
            'delivery_type',
            'dada_app_key',
            'dada_app_sercret',
            'dada_source_id',
        ]);
        $form = Elm::createForm(Route::buildUrl('systemDeliveryConfigSave')->build());
        $form->setRule([
            Elm::switches('delivery_status', '是否开启同城配送', $formData['delivery_status'])->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::radio('delivery_type', '配送类型', $formData['delivery_type'])
                ->setOptions([
                    ['value' => DeliverySevices::DELIVERY_TYPE_DADA, 'label' => '达达快送'],
                    ['value' => DeliverySevices::DELIVERY_TYPE_UU, 'label' => 'UU跑腿'],
                ])->control([
                    [
                        'value' => DeliverySevices::DELIVERY_TYPE_DADA,
                        'rule' => [
                            Elm::input('dada_app_key', 'AppKey (达达)')->value($formData['dada_app_key'])->required(),
                            Elm::input('dada_app_sercret', 'AppSercret (达达)')->value($formData['dada_app_sercret'])->required(),
                            Elm::input('dada_source_id', '商户ID (达达)')->value($formData['dada_source_id'])->required(),
                        ]
                    ],
                    [
                        'value' => DeliverySevices::DELIVERY_TYPE_UU,
                        'rule' => [
                            Elm::input('uupt_appkey', 'AppKey (UU跑腿)')->value($formData['uupt_appkey'])->required(),
                            Elm::input('uupt_app_id', 'AppId (UU跑腿)')->value($formData['uupt_app_id'])->required(),
                            Elm::input('uupt_open_id', 'OpenId (UU跑腿)')->value($formData['uupt_open_id'])->required(),
                        ]
                    ],
                ]),
        ]);
        return $form->setTitle('同城配送设置');
    }

    public function getBusiness()
    {
        $type = systemConfig('delivery_type');
        return DeliverySevices::create($type)->getBusiness();
    }

    /**
     * TODO 创建门店
     * @param array $data
     * @return mixed
     * @author Qinii
     * @day 2/14/22
     */
    public function save(array $data)
    {
        return Db::transaction(function () use($data){
            $data['origin_shop_id'] = 'Deliver'.$data['mer_id'].'_'.$this->getSn();
            DeliverySevices::create(systemConfig('delivery_type'))->addShop([$data]);
            return $this->dao->create($data);
        });

    }

    public function getSn()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        $orderId =  $msectime . random_int(10000, max(intval($msec * 10000) + 10000, 98369));
        return $orderId;
    }

    /**
     * TODO 更新门店
     * @param $id
     * @param $data
     * @return mixed
     * @author Qinii
     * @day 2/14/22
     */
    public function edit($id, $merId, $data)
    {
        $res = $this->dao->getSearch([$this->dao->getPk() => $id, 'mer_id' => $merId])->find();
        if (!$res) throw new ValidateException('门店不存在或不属于您');
        $type = systemConfig('delivery_type');

        $data['origin_shop_id'] = $res['origin_shop_id'];
        return Db::transaction(function () use($id, $type, $data, $res){
            if ($res['type'] == 2 && $data['type'] == 1) {
                DeliverySevices::create($type)->addShop($data);
            } else {
                DeliverySevices::create($type)->updateShop($data);
            }
            return $this->dao->update($id, $data);
        });
    }

    /**
     * TODO
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2/17/22
     */
    public function merList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('create_time DESC')->select();
        return compact('count', 'list');
    }

    public function sysList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where)->with([
            'merchant' => function($query) {
                $query->field('mer_id,mer_name');
            }
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('create_time DESC')->select();
        return compact('count', 'list');
    }

    public function detail(int $id, ?int $merId)
    {
        $where[$this->dao->getPk()] = $id;
        if ($merId) $where['mer_id'] = $merId;
        $res = $this->dao->getSearch($where)->with([
            'merchant' => function($query) {
                $query->field('mer_id,mer_name');
            }
        ])->find();

        if (!$res) throw new ValidateException('门店不存在');
        return $res;
    }

    public function destory($id, $merId)
    {
        $where = [
            $this->dao->getPk() => $id,
            'mer_id' => $merId,
        ];
        $res = $this->dao->getSearch($where)->find();
        if (!$res) throw new ValidateException('数据不存在');
//        $data = [
//            'origin_shop_id' => $res['origin_shop_id'],
//            'status' => 0,
//        ];
//        if ($res['type'] == DeliverySevices::DELIVERY_TYPE_DADA) {
//            try{
//                DeliverySevices::create($res['type'])->updateShop($data);
//            }catch (\Exception $exception) {
//            }
//        }
        return $this->dao->delete($id);
    }

    public function markForm($id, $merId)
    {
        $where = [
            $this->dao->getPk() => $id,
            'mer_id' => $merId,
        ];
        $formData = $this->dao->getWhere($where);
        $form = Elm::createForm(Route::buildUrl('merchantStoreDeliveryMark',['id' => $id])->build());
        $form->setRule([
            Elm::text('mark', '备注', $formData['mark']),
        ]);
        return $form->setTitle('备注');
    }

    public function getOptions($where)
    {
        return $this->dao->getSearch($where)->field('station_id value, station_name label')->order('create_time DESC')->select();
    }


    public function getCityLst()
    {
        $type = systemConfig('delivery_type');
        $key = 'delivery_get_city_lst_'.$type;
        if (!$data  = Cache::get($key)) {
            $data = DeliverySevices::create($type)->getCity([]);
            Cache::set($key, $data,3600);
        }
        return $data;
    }

    public function getBalance()
    {
        $type = systemConfig('delivery_type');
        if (!$type) return ['deliverBalance' => 0];
        return DeliverySevices::create(systemConfig('delivery_type'))->getBalance([]);
    }

    public function getRecharge()
    {
        return DeliverySevices::create(systemConfig('delivery_type'))->getRecharge([]);
    }

}
