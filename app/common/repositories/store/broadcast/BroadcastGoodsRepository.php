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


namespace app\common\repositories\store\broadcast;


use app\common\dao\store\broadcast\BroadcastGoodsDao;
use app\common\repositories\BaseRepository;
use crmeb\jobs\ApplyBroadcastGoodsJob;
use crmeb\services\DownloadImageService;
use crmeb\services\MiniProgramService;
use crmeb\services\SwooleTaskService;
use Exception;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Route;
use think\Model;

/**
 * Class BroadcastGoodsRepository
 * @package app\common\repositories\store\broadcast
 * @author xaboy
 * @day 2020/7/30
 * @mixin BroadcastGoodsDao
 */
class BroadcastGoodsRepository extends BaseRepository
{
    /**
     * @var BroadcastGoodsDao
     */
    protected $dao;

    public function __construct(BroadcastGoodsDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList($merId, array $where, $page, $limit)
    {
        $where['mer_id'] = $merId;
        $query = $this->dao->search($where)->with('product')->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    public function adminList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where)->with(['merchant' => function ($query) {
            $query->field('mer_name,mer_id,is_trader');
        },'product'])->order('BroadcastGoods.sort DESC,BroadcastGoods.create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }


    /**
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020/7/30
     */
    public function createForm(array $formData = [])
    {
        if (isset($formData['product_id'])) {
            $formData['product_id'] = [
                'id' => $formData['product_id'],
                'src' => $formData['cover_img']
            ];
        }
        return Elm::createForm((isset($formData['broadcast_goods_id']) ? Route::buildUrl('merchantBroadcastGoodsUpdate', ['id' => $formData['broadcast_goods_id']]) : Route::buildUrl('merchantBroadcastGoodsCreate'))->build(), [
            Elm::frameImage('product_id', '商品', '/' . config('admin.merchant_prefix') . '/setting/storeProduct?field=product_id')->width('60%')->height('536px')->props(['srcKey' => 'src'])->modal(['modal' => false])->appendValidate(Elm::validateObject()->message('请选择商品')),
            Elm::input('name', '商品名称')->required(),
            Elm::frameImage('cover_img', '商品图', '/' . config('admin.merchant_prefix') . '/setting/uploadPicture?field=cover_img&type=1')
                ->info('图片尺寸最大像素 300*300')->modal(['modal' => false])->width('896px')->height('480px')->props(['footer' => false])->required(),
            Elm::number('price', '价格')->min(0.01)->info('该价格只做展示,不会影响原商品价格')->required(),
        ])->setTitle('创建直播商品')->formData($formData);
    }

    public function updateForm($id)
    {
        return $this->createForm($this->dao->get($id)->toArray())->setTitle('编辑直播商品');
    }

    /**
     * @param $merId
     * @param array $data
     * @return mixed
     * @author xaboy
     * @day 2020/8/25
     */
    public function create($merId, array $data)
    {
        $data['status'] = request()->merchant()->is_bro_goods == 1 ? 0 : 1;
        $data['mer_id'] = $merId;
        return Db::transaction(function () use ($data) {
            $goods = $this->dao->create($data);
            if ($data['status'] == 1) {
                $res = $this->wxCreate($goods);
                $goods->goods_id = $res->goodsId;
                $goods->audit_id = $res->auditId;
                $goods->save();
            } else {
                SwooleTaskService::admin('notice', [
                    'type' => 'new_goods',
                    'data' => [
                        'title' => '新直播商品申请',
                        'message' => '您有1个新的直播商品审核，请及时处理！',
                        'id' => $goods->broadcast_goods_id
                    ]
                ]);
            }
            return $goods;
        });
    }

    public function batchCreate($merId, array $goodsList)
    {
        $status = request()->merchant()->is_bro_goods == 1 ? 0 : 1;
        $ids = Db::transaction(function () use ($goodsList, $status, $merId) {
            $ids = [];
            foreach ($goodsList as $goods) {
                $goods['status'] = $status;
                $goods['mer_id'] = $merId;
                $ids[] = $this->dao->create($goods)->broadcast_goods_id;
            }
            return $ids;
        });
        foreach ($ids as $id) {
            if ($status == 1) {
                Queue::push(ApplyBroadcastGoodsJob::class, $id);
            } else {
                SwooleTaskService::admin('notice', [
                    'type' => 'new_goods',
                    'data' => [
                        'title' => '新直播商品申请',
                        'message' => '您有1个新的直播商品审核，请及时处理！',
                        'id' => $id
                    ]
                ]);
            }
        }
    }

    /**
     * @param $id
     * @param array $data
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/8/25
     */
    public function update($id, array $data)
    {
        $goods = $this->dao->get($id);
        $status = request()->merchant()->is_bro_goods == 1 ? 0 : 1;
        if ($goods->status == 0) {
            $goods->save($data);
            if ($status == 1) {
                if ($goods->goods_id) {
                    $this->wxUpdate($goods->goods_id, $data);
                } else {
                    $res = $this->wxCreate($goods);
                    $goods->goods_id = $res->goodsId;
                    $goods->audit_id = $res->auditId;
                }
                $goods->save();
            } else {
                SwooleTaskService::admin('notice', [
                    'type' => 'new_goods',
                    'data' => [
                        'title' => '新直播商品申请',
                        'message' => '您有1个新的直播商品审核，请及时处理！',
                        'id' => $goods->broadcast_goods_id
                    ]
                ]);
            }
        } else {
            if ($status == 1 && $goods->goods_id) {
                $this->wxUpdate($goods->goods_id, $data);
            }
            $data['status'] = $status;
            $data['error_msg'] = '';
            $this->change($id, $data);
        }
        return $goods;
    }

    public function change($id, array $data)
    {
        return $this->dao->update($id, $data);
    }

    public function applyForm($id)
    {
        return Elm::createForm(Route::buildUrl('systemBroadcastGoodsApply', compact('id'))->build(), [
            Elm::radio('status', '审核状态', 1)->options([['value' => -1, 'label' => '未通过'], ['value' => 1, 'label' => '通过']])->control([
                ['value' => -1, 'rule' => [
                    Elm::textarea('msg', '未通过原因', '信息有误,请完善')->required()
                ]]
            ]),
        ])->setTitle('审核直播商品');
    }

    public function apply($id, $status, $msg = '')
    {
        $goods = $this->dao->get($id);
        Db::transaction(function () use ($msg, $status, $goods) {
            $goods->status = $status;
            if ($status == -1)
                $goods->error_msg = $msg;
            else {
                if ($goods->goods_id) {
                    $this->wxUpdate($goods->goods_id, $goods);
                } else {
                    $res = $this->wxCreate($goods);
                    $goods->goods_id = $res->goodsId;
                    $goods->audit_id = $res->auditId;
                }
                $goods->status = 1;
            }
            $goods->save();
            SwooleTaskService::merchant('notice', [
                'type' => 'goods_status_' . ($status == -1 ? 'fail' : 'success'),
                'data' => [
                    'title' => '直播商品审核通知',
                    'message' => $status == -1 ? '您的直播商品审核未通过!' : '您的直播商品审核已通过',
                    'id' => $goods->broadcast_goods_id
                ]
            ], $goods->mer_id);
        });
    }

    public function wxCreate($goods)
    {
        if ($goods['goods_id'])
            throw new ValidateException('商品已创建');

        $goods = $goods->toArray();
        $miniProgramService = MiniProgramService::create();
        $path = './public' . app()->make(DownloadImageService::class)->downloadImage($goods['cover_img'],'def','',1)['path'];
        $data = [
            'name' => $goods['name'],
            'priceType' => 1,
            'price' => floatval($goods['price']),
            'url' => 'pages/goods_details/index?source=1:' . $goods['broadcast_goods_id'] . ':' . $goods['product_id'] . '&id=' . $goods['product_id'],
            'coverImgUrl' => $miniProgramService->material()->uploadImage($path)->media_id,
        ];
        @unlink($path);
        try {
            return $miniProgramService->miniBroadcast()->create($data);
        } catch (Exception $e) {
            throw new ValidateException($e->getMessage());
        }
    }

    public function wxUpdate($id,$data)
    {
        $miniProgramService = MiniProgramService::create();
        $path = './public' . app()->make(DownloadImageService::class)->downloadImage($data['cover_img'],'def','',1)['path'];
        $params = [
            "goodsId" => $id,
            'name' => $data['name'],
            'priceType' => 1,
            'price' => floatval($data['price']),
            'coverImgUrl' => $miniProgramService->material()->uploadImage($path)->media_id,
        ];
        @unlink($path);
        try {
            return $miniProgramService->miniBroadcast()->update($params);
        } catch (Exception $e) {
            throw new ValidateException($e->getMessage());
        }
    }

    public function isShow($id, $isShow, bool $admin = false)
    {
        return $this->dao->update($id, [($admin ? 'is_show' : 'is_mer_show') => $isShow]);
    }

    public function mark($id, $mark)
    {
        return $this->dao->update($id, compact('mark'));
    }

    public function delete($id)
    {
        $goods = $this->dao->get($id);
        if ($goods->goods_id) {
            MiniProgramService::create()->miniBroadcast()->delete($goods->goods_id);
        }
        app()->make(BroadcastRoomGoodsRepository::class)->deleteGoods($id);
        $this->dao->delete($id);

    }

    public function syncGoodStatus()
    {
        $goodsIds = $this->dao->goodsStatusAll();
        if (!count($goodsIds)) return;
        $res = MiniProgramService::create()->miniBroadcast()->getGoodsWarehouse(array_keys($goodsIds))->toArray();
        foreach ($res['goods'] as $item) {
            if (isset($goodsIds[$item['goods_id']]) && $item['audit_status'] != $goodsIds[$item['goods_id']]) {
                $data = ['audit_status' => $item['audit_status']];
                if (in_array($item['audit_status'], [2, 3])) {
                    $data['status'] = $item['audit_status'] == 3 ? -1 : 2;
                    if (-1 == $data['status']) {
                        $data['error_msg'] = '微信审核未通过';
                    }
                }
                //TODO 同步商品审核状态
                $this->dao->updateGoods($item['goods_id'], $data);
            }
        }
    }
}
