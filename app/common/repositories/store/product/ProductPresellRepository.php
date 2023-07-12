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

use app\common\dao\store\product\ProductPresellDao;
use app\common\model\store\product\ProductLabel;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\order\StoreOrderProductRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use crmeb\jobs\ChangeSpuStatusJob;
use crmeb\services\SwooleTaskService;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * @mixin ProductPresellDao
 */
class ProductPresellRepository extends BaseRepository
{
    public function __construct(ProductPresellDao $dao)
    {
        $this->dao = $dao;
    }

    public function create(int $merId, array $data)
    {
        $product_make = app()->make(ProductRepository::class);
        $productRes = $product_make->get($data['product_id']);
        if ($productRes['product_type'] !== 0) throw new ValidateException('商品正在参与其他活动，不可添加');

        $presell = [
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'presell_type' => $data['presell_type'],
            'final_start_time' => $data['final_start_time'] ?? '',
            'final_end_time' => $data['final_end_time'] ?? '',
            'status' => 0,
            'is_show' => $data['is_show'] ?? 1,
            'pay_count' => $data['pay_count'],
            'delivery_type' => $data['delivery_type'],
            'delivery_day' => $data['delivery_day'],
            'product_id' => $data['product_id'],
            'store_name' => $data['store_name'],
            'store_info' => $data['store_info'],
            'mer_id' => $merId,
            'product_status' => 0,
            'delivery_way' => $data['delivery_way'],
            'delivery_free' => $data['delivery_free'],
        ];

        $product = [
            'image' => $data['image'],
            'slider_image' => implode(',', $data['slider_image']),
            'temp_id' => $data['temp_id'],
            'is_show' => 0,
            'product_type' => 2,
            'sort' => $data['sort'],
            'delivery_way' => $data['delivery_way'],
            'delivery_free' => $data['delivery_free'],
        ];
        Db::transaction(function () use ($presell, $product, $data, $product_make) {
            $sku_make = app()->make(ProductPresellSkuRepository::class);
            event('product.presellCreate.before',compact('data'));
            $productPresell = $this->dao->create($presell);

            $res = $this->sltSku($data, $productPresell->product_presell_id, $data['product_id']);
            $sku_make->insertAll($res['sku']);
            $product_make->update($presell['product_id'], $product);
            $this->dao->update($productPresell->product_presell_id, ['price' => $res['price']]);

            $data['mer_id'] = $presell['mer_id'];
            $data['price'] = $res['price'];
            app()->make(SpuRepository::class)->create($data, $data['product_id'], $productPresell->product_presell_id, 2);
            event('product.presellCreate',compact('productPresell'));
            queue(ChangeSpuStatusJob::class, ['id' => $presell['product_id'], 'product_type' => 0]);
            SwooleTaskService::admin('notice', [
                'type' => 'new_presell',
                'data' => [
                    'title' => '商品审核',
                    'message' => '您有一个新的预售商品待审核',
                    'id' => $productPresell->product_presell_id,
                    'type' => $data['presell_type'],
                ]
            ]);
        });
    }

    /**
     * TODO 检测是否每个sku的预售价格
     * @param array $data
     * @param int $presellType
     * @return array
     * @author Qinii
     * @day 2020-10-12
     */
    public function sltSku(array $data, int $presellId, int $productId)
    {
        $make = app()->make(ProductAttrValueRepository::class);
        $sku = [];
        $price = 0;
        foreach ($data['attrValue'] as $item) {
            if ($item['product_id'] !== $productId)  throw new ValidateException('商品ID不一致');
            $skuData = $make->getWhere(['unique' => $item['unique'], 'product_id' => $productId]);
            if (!$skuData) throw new ValidateException('SKU不存在');
            if (bccomp($item['presell_price'], $skuData['price'], 2) == 1) throw new ValidateException('预售价格不得大于原价');
            if (!$item['presell_price'] || $item['presell_price'] < 0) throw new ValidateException('请正确填写预售金额');
            if ($data['presell_type'] == 2) {
                if (!$item['down_price'] || $item['down_price'] < 0) throw new ValidateException('请正确填写订金金额');
                $_price = bccomp($item['down_price'], bcmul($item['presell_price'], 0.2, 2), 2);
                if ($_price == 1) throw new ValidateException('订金金额不得超过预售价20%');
            }
            $sku[] = [
                'product_presell_id' => $presellId,
                'product_id' => $data['product_id'],
                'unique' => $item['unique'],
                'stock' => $item['stock'],
                'stock_count' => $item['stock'],
                'presell_price' => $item['presell_price'],
                'down_price' => $data['presell_type'] == 1 ? $item['presell_price'] :  $item['down_price'],
                'final_price' => bcsub($item['presell_price'], $item['down_price'], 2)
            ];
            $price = ($price == 0) ? $item['presell_price'] : (($price > $item['presell_price']) ? $item['presell_price'] : $price);
        }
        return compact('sku', 'price');
    }


    /**
     * TODO 商户后台列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2020-10-12
     */
    public function getMerchantList(array $where, int $page, int $limit)
    {
        $query = $this->dao->search($where)
            ->with(['product'])
            ->append(['presell_status', 'seles', 'tattend_one', 'tattend_two', 'stock_count', 'stock', 'us_status'])
            ->order('Product.sort DESC,Product.create_time DESC');
        $count = $query->count();
        $data = $query->page($page, $limit)->setOption('field', [])->field('ProductPresell.*,U.mer_labels')
            ->select();

        $list = hasMany(
            $data ,
            'mer_labels',
            ProductLabel::class,
            'product_label_id',
            'mer_labels',
            ['status' => 1],
            'product_label_id,product_label_id id,label_name name'
        );
        $stat = $this->stat($where['mer_id']);
        return compact('stat', 'count', 'list');
    }

    /**
     * TODO 平台列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2020-10-19
     */
    public function getAdminList(array $where, int $page, int $limit)
    {
        $query = $this->dao->search($where)->with([
            'product',
            'merchant' => function ($query) {
                $query->field('mer_id,mer_avatar,mer_name,is_trader');
            }
        ]);
        $count = $query->count();
        $data = $query->page($page, $limit)->field('ProductPresell.*,U.star,U.rank,U.sys_labels')
            ->select()->append(['presell_status', 'seles', 'tattend_one', 'tattend_two', 'stock', 'stock_count', 'us_status']);

        $list = hasMany(
            $data ,
            'sys_labels',
            ProductLabel::class,
            'product_label_id',
            'sys_labels',
            ['status' => 1],
            'product_label_id,product_label_id id,label_name name'
        );

        $stat = $this->stat(null);
        return compact('stat', 'count', 'list');
    }

    /**
     * TODO 移动端列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2020-10-19
     */
    public function getApiList(array $where, int $page, int $limit)
    {
        $where = array_merge($where, $this->dao->actionShow());
        $query = $this->dao->search($where)->where('ProductPresell.is_del', 0)
            ->with([
                'product',
                'merchant' => function ($query) {
                    $query->field('mer_id,mer_avatar,mer_name,is_trader');
                }
            ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->append(['coupon', 'tattend_one', 'tattend_two', 'seles']);
        return compact('count', 'list');
    }

    /**
     * TODO merchant / admin 详情
     * @param int $merId
     * @param int $id
     * @return array
     * @author Qinii
     * @day 2020-10-13
     */
    public function detail(?int $merId, int $id)
    {
        $where[$this->dao->getPk()] = $id;
        $where['is_del'] = 0;
        if ($merId) $where['mer_id'] = $merId;
        $data = $this->dao->getWhere($where, '*', [
            'product' => ['attr', 'attrValue', 'content'],
            'merchant' => function ($query) {
                $query->field('mer_id,mer_avatar,mer_name,is_trader');
            }
        ]);
        if (!$data) throw new ValidateException('数据不存在');
        $data->append(['presell_status', 'tattend_one', 'tattend_two', 'stock', 'stock_count','us_status']);
        if (!$data['product']) throw new ValidateException('该商品已不存在');

        $spu_where = ['activity_id' => $id, 'product_type' => 2, 'product_id' => $data['product']['product_id']];
        $spu = app()->make(SpuRepository::class)->getSearch($spu_where)->find();
        $data['star'] = $spu['star'] ?? '';
        $data['mer_labels'] = $spu['mer_labels'] ?? '';
        $data['product']['delivery_way']  = empty($data['product']['delivery_way']) ? [] : explode(',',$data['product']['delivery_way']);
        $sku_make = app()->make(ProductPresellSkuRepository::class);
        foreach ($data['product']['attrValue'] as $key => $item) {
            $sku = explode(',', $item['sku']);
            $item['old_stock'] = $item['stock'];
            $item['presellSku'] = $sku_make->getSearch(['product_presell_id' => $id, 'unique' => $item['unique']])->find();
            if (!$merId && !$item['presellSku']) continue;
            foreach ($sku as $k => $v) {
                $item['value' . $k] = $v;
            }
            $data['product']['attrValue'][$key] = $item;
        }
        foreach ($data['product']['attr'] as $k => $v) {
            $data['product']['attr'][$k] = [
                'value'  => $v['attr_name'],
                'detail' => $v['attr_values']
            ];
        }
        return $data;
    }

    /**
     * TODO 移动端 详情
     * @param int $id
     * @return array|\think\Model|null
     * @author Qinii
     * @day 2020-10-19
     */
    public function apiDetail(int $id, $userInfo)
    {
        $where = $this->dao->actionShow();
        $where['product_presell_id'] = $id;
        $data = $this->dao->search($where)->append(['presell_status', 'tattend_one', 'tattend_two', 'seles'])->find();
        if (!$data){
            app()->make(SpuRepository::class)->changeStatus($id,2);
            throw new ValidateException('商品已下架');
        }
        if ($data['pay_count'] && $userInfo->uid) {
            $_count = app()->make(StoreOrderRepository::class)->getTattendCount([
                'activity_id' => $id,
                'product_type' => 2,
                'type' => 1,
            ], $userInfo->uid)->sum('total_num');
            $data['self_count'] = ($_count >= $data['pay_count']) ? 0 : ($data['pay_count'] - $_count);
        }
        $make = app()->make(ProductRepository::class);
        $data['product'] = $make->apiProductDetail(['product_id' => $data['product_id']], 2, $id,$userInfo);
        return $data;
    }

    /**
     * TODO 统计数量
     * @param int|null $merId
     * @return array
     * @author Qinii
     * @day 2020-10-13
     */
    public function stat(?int $merId)
    {
        $where['product_type'] = 2;
        if ($merId) {
            $where['mer_id'] = $merId;
        }else{
            $where['star'] = '';
        }
        $where['presell_type'] = 1;
        $all = $this->dao->search($where)->count();

        $where['presell_type'] = 2;
        $down = $this->dao->search($where)->count();

        return compact('all', 'down');
    }

    /**
     * TODO 商户编辑
     * @param int $id
     * @param array $data
     * @author Qinii
     * @day 2020-10-13
     */
    public function edit(int $id, array $data)
    {
        $presell = [
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'presell_type' => $data['presell_type'],
            'final_start_time' => $data['final_start_time'] ?? '',
            'final_end_time' => $data['final_end_time'] ?? '',
            'status' => $data['status'],
            'is_show' => $data['is_show'] ?? 1,
            'pay_count' => $data['pay_count'],
            'delivery_type' => $data['delivery_type'],
            'delivery_day' => $data['delivery_day'],
            'store_name' => $data['store_name'],
            'store_info' => $data['store_info'],
            'product_status' => 0,
        ];
        $product = [
            'image' => $data['image'],
            'slider_image' => implode(',', $data['slider_image']),
            'temp_id' => $data['temp_id'],
            'is_show' => 0,
            'product_type' => 2,
            'sort' => $data['sort'],
            'guarantee_template_id'=>$data['guarantee_template_id'],
            'delivery_way' => implode(',',$data['delivery_way']),
            'delivery_free' => $data['delivery_free'],
        ];
        Db::transaction(function () use ($id, $presell, $product, $data) {
            $product_make = app()->make(ProductRepository::class);
            $sku_make = app()->make(ProductPresellSkuRepository::class);
            event('product.presellUpdate.before',compact('id','data'));
            $resData = $this->dao->get($id);
            if ($resData->presell_status !== 0) throw new ValidateException('活动已不可编辑');
            $res = $this->sltSku($data, $id, $resData['product_id']);

            $presell['price'] = $res['price'];
            $this->dao->update($id, $presell);

            $sku_make->clear($id);
            $sku_make->insertAll($res['sku']);

            $product_make->update($resData['product_id'], $product);
            $data['price'] = $res['price'];
            app()->make(SpuRepository::class)->baseUpdate($data, $resData['product_id'], $id, 2);
            event('product.presellUpdate',compact('id'));
            SwooleTaskService::admin('notice', [
                'type' => 'new_presell',
                'data' => [
                    'title' => '商品审核',
                    'message' => '您有一个新的预售商品待审核',
                    'id' => $id,
                    'type' => $data['presell_type']
                ]
            ]);
        });
    }

    /**
     * TODO 删除预售信息
     * @param array $where
     * @author Qinii
     * @day 2020-10-17
     */
    public function delete(array $where)
    {
        $data = $this->dao->getWhere($where, '*', ['product']);
        if (!$data) throw new ValidateException('数据不存在');
        Db::transaction(function () use ($data) {
            $data->is_del = 1;
            $data->action_status = -1;
            $data->save();
            $data->product->product_type = 0;
            $data->product->save();
            $productPresell = $data;
            event('product.presellDelete',compact('productPresell'));
            app()->make(SpuRepository::class)->changeStatus($data[$this->getPk()],2);
        });
    }

    /**
     * TODO 预售商品加入购物车检测
     * @param array $data
     * @param $userInfo
     * @author Qinii
     * @day 2020-10-21
     */
    public function cartCheck(array $data, $userInfo)
    {
        /**
         * 1 查询出商品信息；
         * 2 商品是否存在
         * 3 购买是否超过限制
         * 4 库存检测
         */
        if (!$data['is_new']) throw new ValidateException('预购商品不可加入购物车');

        $where = $this->dao->actionShow();
        $where[$this->dao->getPk()] = $data['product_id'];
        $where['product_type'] = 2;
        $presell = $this->dao->search($where)->with('product')->find();
        if (!$presell) throw new ValidateException('商品已下架');
        if ($presell['presell_status'] !== 1) throw new ValidateException('请在活动时间内购买');
        if ($presell['pay_count'] !== 0) {
            $make = app()->make(StoreOrderRepository::class);
            $tattend = [
                'activity_id' => $data['product_id'],
                'product_type' => 2,
                'type' => 1,
            ];
            $count = $make->getTattendCount($tattend, $userInfo->uid)->sum('total_num');
            if ($count >= $presell['pay_count']) throw new ValidateException('您的本次活动购买数量上限');
            if (($presell['pay_count'] - $count)  < $data['cart_num']) throw new ValidateException('您的本次活动购买数量不足');
        }

        $sku_make = app()->make(ProductPresellSkuRepository::class);
        $_where = ['unique' => $data['product_attr_unique'], $this->dao->getPk() => $data['product_id']];
        $presellSku = $sku_make->getWhere($_where, '*', ['sku']);

        if (($presellSku['stock'] < $data['cart_num']) || ($presellSku['sku']['stock'] < $data['cart_num']))
            throw new ValidateException('库存不足');
        $product = $presell['product'];
        $sku = $presellSku['sku'];
        $cart = null;

        return compact('product', 'sku', 'cart');
    }


    public function get(int $id)
    {
        $data = $this->dao->get($id);
        if (!$data) throw new ValidateException('数据不存在');
        $res = app()->make(ProductRepository::class)->getAdminOneProduct($data['product_id'], $id);
        $res['store_name'] = $data['store_name'];
        $res['store_info'] = $data['store_info'];
        $res['presell_type'] = $data['presell_type'];
        $res['product_presell_id'] = $data['product_presell_id'];
        return $res;
    }

    public function updateProduct(int $id, array $data)
    {
        $this->dao->update($id, ['store_name' => $data['store_name']]);
        $res = $this->dao->get($id);
        $res->store_name = $data['store_name'];
        $res->save();
        app()->make(SpuRepository::class)->changRank($id, $res['product_id'], 2, $data);
        unset($data['store_name'], $data['star']);
        app()->make(ProductRepository::class)->adminUpdate($res['product_id'], $data);
    }


    /**
     * TODO 关闭过期
     * @param array|null $where
     * @author Qinii
     * @day 2020-11-23
     */
    public function checkStatus(?array $where)
    {
        $where['action_status'] = 1;
        $where['type'] = 3;
        $this->dao->search($where)->select()->each(function ($data) {
            foreach ($data as $item) {
                $item->action_status = -1;
                $item->save();
                $item->product->product_type = 0;
                $item->product->save();
                queue(ChangeSpuStatusJob::class, ['id' => $item->product_presell_id, 'product_type' => 2]);
            }
        });
    }

    public function updateSort(int $id, ?int $merId, array $data)
    {
        $where[$this->dao->getPk()] = $id;
        if ($merId) $where['mer_id'] = $merId;
        $ret = $this->dao->getWhere($where);
        if (!$ret) throw new  ValidateException('数据不存在');
        app()->make(ProductRepository::class)->update($ret['product_id'], $data);
        $make = app()->make(SpuRepository::class);
        return $make->updateSort($ret['product_id'], $id, 2, $data);
    }

    public function switchStatus($id, $data)
    {
        $data['product_status'] = $data['status'];
        $ret = $this->dao->get($id);
        if (!$ret)
            throw new ValidateException('数据不存在');
        event('product.presellStatus.before', compact('id', 'data'));

        $this->dao->update($id, $data);
        event('product.presellStatus', compact('id', 'data'));

        $type = ProductRepository::NOTIC_MSG[$data['status']][2];
        $message = '您有1个预售'. ProductRepository::NOTIC_MSG[$data['status']]['msg'];
        SwooleTaskService::merchant('notice', [
            'type' => $type,
            'data' => [
                'title' => $data['status'] == -2 ? '下架提醒' : '审核结果',
                'message' => $message,
                'id' => $id
            ]
        ], $ret->mer_id);

        app()->make(SpuRepository::class)->changeStatus($id,2);
    }
}
