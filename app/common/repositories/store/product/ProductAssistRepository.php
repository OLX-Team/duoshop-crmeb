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

use app\common\dao\store\product\ProductAssistDao;
use app\common\model\store\product\ProductLabel;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\order\StoreOrderProductRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use crmeb\services\SwooleTaskService;
use think\exception\ValidateException;
use think\facade\Db;

class ProductAssistRepository extends BaseRepository
{
    public function __construct(ProductAssistDao $dao)
    {
        $this->dao = $dao;
    }

    public function create(int $merId,array $data)
    {
        $product_make = app()->make(ProductRepository::class);
        $product = [
            'image' => $data['image'],
            'store_name' => $data['store_name'],
            'store_info' => $data['store_info'],
            'slider_image' => $data['slider_image'],
            'temp_id' => $data['temp_id'],
            'is_show' => 0,
            'product_type' => 3,
            'status'    => 1,
            'old_product_id'    => $data['product_id'],
            'guarantee_template_id'=>$data['guarantee_template_id'],
            'sales' => 0,
            'rate'  => 3,
            'integral_rate' => 0,
            'delivery_way' => $data['delivery_way'],
            'delivery_free' => $data['delivery_free'],
        ];

        Db::transaction(function()use($data,$product_make,$product,$merId){
            event('product.assistCreate.before',compact('data'));
            $product_id = $product_make->productCopy($data['product_id'],$product,3);
            $assist = [
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time'],
                'status'     => 0,
                'is_show'    => $data['is_show'] ?? 1,
                'product_id' => $product_id,
                'store_name' => $data['store_name'],
                'store_info' => $data['store_info'],
                'pay_count' => $data['pay_count'],
                'mer_id'     => $merId,
                'assist_count' => $data['assist_count'],
                'assist_user_count' => $data['assist_user_count'],
                'product_status' => 0,
            ];

            $sku_make = app()->make(ProductAssistSkuRepository::class);

            $productAssist = $this->dao->create($assist);

            $sku = $this->sltSku($data,$productAssist->product_assist_id,$data['product_id']);

            $sku_make->insertAll($sku);
            $data['price'] = $sku[0]['assist_price'];
            $data['mer_id'] = $merId;
            app()->make(SpuRepository::class)->create($data,$product_id,$productAssist->product_assist_id,3);
            event('product.assistCreate',compact('productAssist'));
            SwooleTaskService::admin('notice', [
                'type' => 'new_assist',
                'data' => [
                    'title' => '商品审核',
                    'message' => '您有一个新的助力商品待审核',
                    'id' => $productAssist->product_assist_id
                ]
            ]);
        });
    }

    /**
     * TODO 检测是否每个sku的价格
     * @param array $data
     * @param int $presellType
     * @return array
     * @author Qinii
     * @day 2020-10-12
     */
    public function sltSku(array $data,int $assistId,int $productId)
    {
        $make = app()->make(ProductAttrValueRepository::class);
        $sku = [];
        if(count($data['attrValue']) > 1) throw new ValidateException('助力商品只能选择一个SKU');
        $item = $data['attrValue'][0];

        if(!isset($item['assist_price']))throw new ValidateException('请输入助力价格');
        $skuData = $make->getWhere(['unique' => $item['unique'],'product_id' => $productId]);
        if(!$skuData) throw new ValidateException('SKU不存在');
        if($skuData['stock'] < $item['stock']) throw new ValidateException('限购数量不得大于库存');
        if(bccomp($item['assist_price'],$skuData['price'],2) == 1) throw new ValidateException('助力价格不得大于原价');
        $sku[] = [
            'product_assist_id' => $assistId,
            'product_id' => $productId,
            'unique' => $item['unique'],
            'stock' => $item['stock'],
            'assist_price' => $item['assist_price'],
            'stock_count' => $item['stock'],
        ];

        return $sku;
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
    public function getMerchantList(array $where,int $page,int $limit)
    {
        $query = $this->dao->search($where)
            ->with(['assistSku','product'])
            ->append(['assist_status','all','pay', 'success','us_status','stock_count','stock'])
            ->order('Product.sort DESC,Product.create_time DESC');
        $count = $query->count();
        $data = $query->page($page,$limit)->setOption('field', [])->field('ProductAssist.*,U.mer_labels')
            ->select()->each(function($item){
                $item['product']['store_name'] = $item['store_name'];
                $item['product']['store_info'] = $item['store_info'];
                return $item;
            });
        $list = hasMany(
            $data ,
            'mer_labels',
            ProductLabel::class,
            'product_label_id',
            'mer_labels',
            ['status' => 1],
            'product_label_id,product_label_id id,label_name name'
        );

        return compact('count','list');
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
    public function getAdminList(array $where,int $page,int $limit)
    {
        $query = $this->dao->search($where)
            ->append(['assist_status','all','pay', 'success','us_status','star','stock_count','stock'])
            ->with(['product','assistSku','merchant' => function($query){
                $query->field('mer_id,mer_avatar,mer_name,is_trader');
            }]);
        $count = $query->count();
        $data = $query->page($page,$limit)->field('ProductAssist.*,U.star,U.rank,U.sys_labels')->select()
            ->each(function($item){
                $item['product']['store_name'] = $item['store_name'];
                $item['product']['store_info'] = $item['store_info'];
            return $item;
        });

        $list = hasMany(
            $data ,
            'sys_labels',
            ProductLabel::class,
            'product_label_id',
            'sys_labels',
            ['status' => 1],
            'product_label_id,product_label_id id,label_name name'
        );

        return compact('count','list');
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
    public function getApiList(array $where, int $page,int $limit)
    {
        $where = array_merge($where,$this->dao->assistShow());
        $query = $this->dao->search($where)->where('ProductAssist.is_del',0)
            ->append(['assist_status','user_count'])
            ->with(['assistSku','product','merchant' => function($query){
                $query->field('mer_id,mer_avatar,mer_name,is_trader');
            }]);
        $count = $query->count();
        $list = $query->page($page,$limit)->select();
        return compact('count','list');
    }

    /**
     * TODO merchant 详情
     * @param int $merId
     * @param int $id
     * @return array
     * @author Qinii
     * @day 2020-10-13
     */
    public function detail(?int $merId,int $id)
    {
        $where[$this->dao->getPk()] = $id;
        $where['is_del'] = 0;
        if($merId)$where['mer_id'] = $merId;
        $data = $this->dao->getWhere($where,'*',
            [
                'product' => ['content','attr','oldAttrValue'],
                'assistSku',
                'merchant'=> function($query){
                    $query->field('mer_id,mer_avatar,mer_name,is_trader');
                }
            ])
            ->append(['assist_status','all','pay','success','us_status'])->toArray();

        if(!$data) throw new ValidateException('数据不存在');
        if(!$data['product']) throw new ValidateException('该商品已不存在');

        $spu_where = ['activity_id' => $id, 'product_type' => 3, 'product_id' => $data['product']['product_id']];
        $spu = app()->make(SpuRepository::class)->getSearch($spu_where)->find();
        $data['star'] = $spu['star'] ?? '';
        $data['mer_labels'] = $spu['mer_labels'] ?? '';

        $sku_make = app()->make(ProductAssistSkuRepository::class);
        $data['product']['delivery_way']  = empty($data['product']['delivery_way']) ? [] : explode(',',$data['product']['delivery_way']);
        foreach ($data['product']['oldAttrValue'] as $key => $item) {
            $sku = explode(',', $item['sku']);
            $item['old_stock'] = $item['stock'];
            $item['assistSku'] = $sku_make->getSearch([$this->dao->getPk() => $id,'unique' => $item['unique']])->find();
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
        unset($data['product']['oldAttrValue']);

        $data['product']['store_name'] = $data['store_name'];
        $data['product']['store_info'] = $data['store_info'];
        return $data;
    }


    /**
     * TODO 移动端 详情
     * @param int $id
     * @return array|\think\Model|null
     * @author Qinii
     * @day 2020-10-19
     */
    public function apiDetail(int $id)
    {
        $where = $this->dao->assistShow();
        $where[$this->dao->getPk()] = $id;
        $data = $this->dao->search($where)->append(['assist_status'])->find();
        if(!$data) {
            app()->make(SpuRepository::class)->changeStatus($id,3);
            throw new ValidateException('商品已下架');
        }
        $make = app()->make(ProductRepository::class);
        $data['product'] = $make->apiProductDetail(['product_id' => $data['product_id']],3,$id);
        $data['product']['store_name'] = $data['store_name'];
        $data['product']['store_info'] = $data['store_info'];
        return $data;
    }


    /**
     * TODO 商户编辑 Daft Punk FKJ Else
     * @param int $id
     * @param array $data
     * @author Qinii
     * @day 2020-10-13
     */
    public function edit(int $id,array $data)
    {

        $resultData = [
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'assist_user_count' => $data['assist_user_count'],
            'assist_count' => $data['assist_count'],
            'status' => $data['status'] ,
            'is_show' => $data['is_show'] ?? 1,
            'store_name' => $data['store_name'],
            'pay_count' => $data['pay_count'],
            'store_info' => $data['store_info'],
        ];

        $product = [
            'image' => $data['image'],
            'slider_image' => implode(',', $data['slider_image']),
            'temp_id' => $data['temp_id'],
            'product_type' => 3,
            'guarantee_template_id'=>$data['guarantee_template_id'],
            'delivery_way' => implode(',',$data['delivery_way']),
            'delivery_free' => $data['delivery_free'],
            'sort' => $data['sort'],
        ];
        Db::transaction(function()use($id,$resultData,$product,$data){
            $res = $this->dao->get($id);
            event('product.assistUpdate.before',compact('id','data'));
            $this->dao->update($id,$resultData);

            $sku_make = app()->make(ProductAssistSkuRepository::class);

            $sku = $this->sltSku($data,$id,$res->product->old_product_id);
            $sku_make->clear($id);
            $sku_make->insertAll($sku);

            $product_make = app()->make(ProductRepository::class);
            $product_make->update($res['product_id'],$product);
            $data['price'] = $sku[0]['assist_price'];
            app()->make(SpuRepository::class)->baseUpdate($data,$res['product_id'],$id,3);
            event('product.assistUpdate',compact('id'));
            SwooleTaskService::admin('notice', [
                'type' => 'new_assist',
                'data' => [
                    'title' => '商品审核',
                    'message' => '您有一个新的助力商品待审核',
                    'id' => $id
                ]
            ]);
        });
    }

    /**
     * TODO 删除信息
     * @param array $where
     * @author Qinii
     * @day 2020-10-17
     */
    public function delete(array $where)
    {
        $productAssist = $this->dao->getWhere($where,'*',['product']);
        if(!$productAssist) throw new ValidateException('数据不存在');
        Db::transaction(function()use($productAssist){
            $productAssist->is_del = 1;
            $productAssist->save();
            event('product.assistDelete',compact('productAssist'));
//            queue(ChangeSpuStatusJob::class, ['id' => $productAssist[$this->getPk()], 'product_type' => 3]);
            app()->make(SpuRepository::class)->changeStatus($productAssist[$this->getPk()],3);
        });
    }

    public function get(int $id)
    {
        $data = $this->dao->getWhere([$this->dao->getPk() => $id],'*',['assistSku.sku'])->toArray();
        $res = app()->make(ProductRepository::class)->getAdminOneProduct($data['product_id'],$id);
        $res['product_assist_id'] = $data['product_assist_id'];
        return $res;
    }

    public function updateProduct(int $id,array $data)
    {
        $this->dao->update($id,['store_name' => $data['store_name']]);
        $res = $this->dao->get($id);
        $res->store_name = $data['store_name'];
        $res->save();
        app()->make(SpuRepository::class)->changRank($id,$res['product_id'],3,$data);
        unset($data['star']);
        app()->make(ProductRepository::class)->adminUpdate($res['product_id'],$data);

    }


    public function checkAssist($id,$uid)
    {
        $where = $this->dao->assistShow();
        $where[$this->dao->getPk()] = $id;
        $data = $this->dao->search($where)->with(['product','assistSku.sku'])->append(['assist_status'])->find();
        if (!$data) throw new ValidateException('商品已下架或不在活动时间内');
        if($data['pay_count']){
            $make = app()->make(StoreOrderRepository::class);
            $arr =  ['exsits_id' => $id,'product_type' => 3];
            $_counot = $make->getTattendCount($arr,$uid)->count();
            if($_counot >= $data['pay_count']) throw new ValidateException('您已达到购买次数上限');
        }
        if(!$data) throw new ValidateException('商品不在活动时间内');
        if($data['assist_status'] !== 1)
            throw new ValidateException('商品不在活动时间内');
        if(!isset($data['assistSku'][0]['sku']))
            throw new ValidateException('商品SKU不存在');
        if($data['assistSku'][0]['stock'] < 1 || $data['assistSku'][0]['sku']['stock'] < 1)
            throw new ValidateException('商品库存不足');
        return $data;
    }

    /**
     * TODO
     * @return mixed
     * @author Qinii
     * @day 2020-11-24
     */
    public function getUserCount()
    {
        $_data = app()->make(ProductAssistUserRepository::class)->userCount();
        $_data1 = app()->make(ProductAssistSetRepository::class)->userCount();
        $data['count'] = $_data['count'] + $_data1['count'];
        $data['list'] = $_data['list'];
        return $data;
    }

    /**
     * TODO 助力商品加入购物车检测
     * @param array $data
     * @param $userInfo
     * @author Qinii
     * @day 2020-10-21
     */
    public function cartCheck(array $data,$userInfo)
    {
        /**
         * 1 查询出商品信息；
         * 2 商品是否存在
         * 3 购买是否超过限制
         * 4 库存检测
         */
        if(!$data['is_new']) throw new ValidateException('助力商品不可加入购物车');

        $where = $this->dao->assistShow();
        $where[$this->dao->getPk()] = $data['product_id'];
        $result = $this->dao->search($where)->with('product')->find();
        if (!$result) throw new ValidateException('商品已下架');

        if($result['pay_count'] !== 0){
            $make = app()->make(StoreOrderRepository::class);
            $tattend = [
                'activity_id' => $data['product_id'],
                'product_type' => 3,
            ];
            $count = $make->getTattendCount($tattend,$userInfo->uid)->count();
            if ($count >= $result['pay_count']) throw new ValidateException('您的本次活动购买数量上限');
        }

        $sku_make = app()->make(ProductAssistSkuRepository::class);
        $_where = ['unique' => $data['product_attr_unique'], $this->dao->getPk() => $data['product_id']];
        $presellSku = $sku_make->getWhere($_where,'*',['sku']);

        if(($presellSku['stock'] < $data['cart_num']) || ($presellSku['sku']['stock'] < $data['cart_num']))
            throw new ValidateException('库存不足');
        $product = $result['product'];
        $sku = $presellSku['sku'];
        $cart = null;
        return compact('product','sku','cart');
    }

    public function updateSort(int $id,?int $merId,array $data)
    {
        $where[$this->dao->getPk()] = $id;
        if($merId) $where['mer_id'] = $merId;
        $ret = $this->dao->getWhere($where);
        if(!$ret) throw new  ValidateException('数据不存在');
        app()->make(ProductRepository::class)->update($ret['product_id'],$data);
        $make = app()->make(SpuRepository::class);
        return $make->updateSort($ret['product_id'],$ret[$this->dao->getPk()],3,$data);
    }

    public function switchStatus($id, $data)
    {
        $data['product_status'] = $data['status'];
        $ret = $this->dao->get($id);
        if (!$ret)
            throw new ValidateException('数据不存在');
        event('product.assistStatus.before', compact('id', 'data'));
        $this->dao->update($id, $data);
        event('product.assistStatus', compact('id', 'data'));

        $type = ProductRepository::NOTIC_MSG[$data['status']][3];
        $message = '您有1个助力'. ProductRepository::NOTIC_MSG[$data['status']]['msg'];
        SwooleTaskService::merchant('notice', [
            'type' => $type,
            'data' => [
                'title' => $data['status'] == -2 ? '下架提醒' : '审核结果',
                'message' => $message,
                'id' => $id
            ]
        ], $ret->mer_id);
        app()->make(SpuRepository::class)->changeStatus($id,3);
    }

}
