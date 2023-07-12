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


namespace app\common\repositories\store\product;


use app\common\dao\BaseDao;
use app\common\dao\store\product\ProductReplyDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\order\StoreOrderProductRepository;
use app\common\repositories\store\order\StoreOrderStatusRepository;
use app\common\repositories\user\UserBrokerageRepository;
use crmeb\jobs\UpdateProductReplyJob;
use crmeb\services\SwooleTaskService;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use function Symfony\Component\String\b;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route;
use think\Model;
use think\facade\Queue;

/**
 * Class ProductReplyRepository
 * @package app\common\repositories\store\product
 * @author xaboy
 * @day 2020/5/30
 * @mixin ProductReplyDao
 */
class ProductReplyRepository extends BaseRepository
{
    /**
     * ProductReplyRepository constructor.
     * @param ProductReplyDao $dao
     */
    public function __construct(ProductReplyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/1
     */
    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->searchJoinQuery($where)->order('A.sort DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * TODO
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 2020-06-30
     */
    public function getApiList($where, $page, $limit)
    {
        if (systemConfig('sys_reply_status') === '0') {
            $count = 0;
            $list = [];
        } else {
            $query = $this->dao->search($where)->where('is_del', 0)
                ->when($where['type'] !== '', function ($query) use ($where) {
                    $query->where($this->switchType($where['type']));
                })
                ->with(['orderProduct' => function ($query) {
                    $query->field('order_product_id,cart_info');
                }])
                ->order('sort DESC,create_time DESC');
            $count = $query->count();
            $list = $query->page($page, $limit)->hidden(['is_virtual'])->select()->each(function ($item) {
                $item['sku'] = $item['orderProduct']['cart_info']['productAttr']['sku'] ?? '';
                if (mb_strlen($item['nickname']) > 1) {
                    $str = mb_substr($item['nickname'],0,1) . '*';
                    if (mb_strlen($item['nickname']) > 2) {
                        $str .= mb_substr($item['nickname'], -1,1);
                    }
                    $item['nickname'] = $str;
                }
                unset($item['orderProduct']);
                return $item;
            });
        }

        $product = ['product_id' => $where['product_id'], 'is_del' => 0];
        $stat = [
            'count' => $this->dao->search($product)->count(),
            'best' => $this->dao->search($product)->where($this->switchType('best'))->count(),
            'middle' => $this->dao->search($product)->where($this->switchType('middle'))->count(),
            'negative' => $this->dao->search($product)->where($this->switchType('negative'))->count(),
        ];

        $rate = ($stat['count'] > 0) ? bcdiv($stat['best'], $stat['count'], 2) * 100 . '%' : 100 . '%';
        $ret = app()->make(ProductRepository::class)->get($where['product_id']);
        $star = (($ret['rate'] == 0) ? 0 : ($ret['rate'] / 5) * 100) . '%';
        return compact('rate', 'star', 'count', 'stat', 'list');
    }

    /**
     * TODO
     * @param $type
     * @author Qinii
     * @day 2020-06-30
     */
    public function switchType($type)
    {
        switch ($type) {
            case 'best':
                $where = [['rate', '>=', 4], ['rate', '<=', 5]];
                break;
            case 'middle':
                $where = [['rate', '>=', 2], ['rate', '<', 4]];
                break;
            case 'negative':
                $where = [['rate', '<', 2]];
                break;
            default:
                $where = [];
                break;
        }
        return $where;
    }

    /**
     * @param int $productId
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020/5/30
     */
    public function form(?int $productId)
    {
        $rule = [];
        if ($productId) {
            $rule[] = Elm::hidden('product_id', [['id' => $productId]]);
        } else {
            $rule[] = Elm::frameImage('product_id', '商品', '/' . config('admin.admin_prefix') . '/setting/storeProduct?field=product_id')->width('60%')->height('536px')->props(['srcKey' => 'src'])->modal(['modal' => false]);
        }
        $rule[] = Elm::input('nickname', '用户昵称')->required();
        $rule[] = Elm::input('comment', '评价文字')->type('textarea');
        $rule[] = Elm::rate('product_score', '商品分数', 5)->col(8)->max(5);
        $rule[] = Elm::rate('service_score', '服务分数', 5)->col(8)->max(5);
        $rule[] = Elm::rate('postage_score', '物流分数', 5)->col(8)->max(5);
        $rule[] = Elm::frameImage('avatar', '用户头像', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=avatar&type=1')->width('896px')->height('480px')->props(['footer' => false])->modal(['modal' => false]);
        $rule[] = Elm::frameImages('pics', '评价图片', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=pics&type=2')->maxLength(6)->width('896px')->height('480px')->spin(0)->modal(['modal' => false])->props(['footer' => false]);
        return Elm::createForm(Route::buildUrl('systemProductReplyCreate')->build(), $rule)->setTitle('添加虚拟评价');
    }

    public function replyForm(int $replyId, $merId = 0)
    {
        return Elm::createForm(Route::buildUrl($merId ? 'merchantProductReplyReply' : 'systemProductReplyReply', ['id' => $replyId])->build(), [
            Elm::textarea('content', '回复内容')->required()
        ])->setTitle('评价回复');
    }

    /**
     * @param array $productIds
     * @param array $data
     * @return int
     * @author xaboy
     * @day 2020/5/30
     */
    public function createVirtual(array $productIds, array $data)
    {

        //todo 虚拟产品 sku
        $data['is_virtual'] = 1;
        $data['product_type'] = 0;
        $data['uid'] = 0;
        $data['rate'] = ($data['product_score'] + $data['service_score'] + $data['postage_score']) / 3;
        $data['pics'] = implode(',', $data['pics']);
        $productRepository = app()->make(ProductRepository::class);
        $productIds = $productRepository->intersectionKey($productIds);
        $list = [];
        foreach ($productIds as $productId) {
            $data['product_id'] = $productId;
            $data['mer_id'] = $productRepository->productIdByMerId($productId);
            $list[] = $data;
        }
        $this->dao->insertAll($list);
        foreach ($productIds as $productId) {
            Queue::push(UpdateProductReplyJob::class, $productId);
        }
    }

    /**
     * @Author:Qinii
     * @Date: 2020/6/2
     * @param int $productId
     * @return array
     */
    public function getReplyRate(int $productId)
    {

        $res = $this->selectWhere(['product_id' => $productId,'is_del' =>0]);
        $best = $res->where('rate', '>=', 4)->where('rate', '<=', 5)->count();
        $count = $res->count();
        $rate = '';
        if ($best && $count) $rate = bcdiv($best, $count, 2) * 100 . '%';
        return compact('best', 'rate', 'count');
    }

    public function reply(array $data)
    {
        $storeOrderProductRepository = app()->make(StoreOrderProductRepository::class);
        $orderProduct = $storeOrderProductRepository->userOrderProduct($data['order_product_id'], $data['uid']);
        if (!$orderProduct || !$orderProduct->orderInfo)
            throw new ValidateException('订单不存在');
        if ($orderProduct->is_reply)
            throw new ValidateException('该商品已评价');
        $data['product_id'] = $orderProduct['product_id'];
        $data['unique'] = $orderProduct['cart_info']['productAttr']['unique'];
        $data['mer_id'] = $orderProduct->orderInfo['mer_id'];
        $data['product_type'] = $orderProduct['cart_info']['product']['product_type'];
        $data['rate'] = ($data['product_score'] + $data['service_score'] + $data['postage_score']) / 3;
        Db::transaction(function () use ($data, $orderProduct, $storeOrderProductRepository) {
            $this->dao->create($data);
            $orderProduct->is_reply = 1;
            $orderProduct->save();
            if (!$storeOrderProductRepository->noReplyProductCount($orderProduct->orderInfo->order_id)) {
                $orderProduct->orderInfo->status = 3;
                $orderProduct->orderInfo->save();
                //TODO 交易完成
                //订单记录
                $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
                $orderStatus = [
                    'order_id' => $orderProduct->orderInfo->order_id,
                    'order_sn' => $orderProduct->orderInfo->order_sn,
                    'type' => $storeOrderStatusRepository::TYPE_ORDER,
                    'change_message' => '交易完成',
                    'change_type' => $storeOrderStatusRepository::ORDER_STATUS_OVER,
                ];
                $storeOrderStatusRepository->createSysLog($orderStatus);
            }
        });
        SwooleTaskService::merchant('notice', [
            'type' => 'reply',
            'data' => [
                'title' => '新评价',
                'message' => '您有一条新的商品评价',
                'id' => $data['product_id']
            ]
        ], $data['mer_id']);
        app()->make(UserBrokerageRepository::class)->incMemberValue($data['uid'], 'member_reply_num', $data['order_product_id']);
        Queue::push(UpdateProductReplyJob::class, $orderProduct->product_id);
    }

}
