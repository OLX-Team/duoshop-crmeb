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

namespace app\controller\api\store\order;

use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\pionts\PointsProductRepository;
use app\common\repositories\store\product\ProductAssistRepository;
use app\common\repositories\store\product\ProductAssistSetRepository;
use app\common\repositories\store\product\ProductAttrValueRepository;
use app\common\repositories\store\product\ProductGroupRepository;
use app\common\repositories\store\product\ProductPresellRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\StoreDiscountProductRepository;
use app\common\repositories\store\product\StoreDiscountRepository;
use app\common\repositories\store\StoreSeckillActiveRepository;
use app\common\repositories\user\UserRepository;
use MongoDB\BSON\MaxKey;
use think\App;
use crmeb\basic\BaseController;
use app\validate\api\StoreCartValidate as validate;
use app\common\repositories\store\order\StoreCartRepository as repository;
use think\exception\ValidateException;

class StoreCart extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;

    /**
     * StoreBrand constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/28
     * @return mixed
     */
    public function lst()
    {
        $data = $this->repository->getList($this->request->userInfo());
        return app('json')->success($data);
    }

    /**
     * @param validate $validate
     * @return mixed
     * @author Qinii
     */
    public function create(validate $validate)
    {
        $data = $this->checkParams($validate);
        if(!in_array($data['product_type'],[0,1,2,3,4,20])) return app('json')->fail('商品类型错误');
        if ($data['cart_num'] <= 0) return app('json')->fail('购买数量有误');
        $user = $this->request->userInfo();
        event('user.cart.before',compact('user','data'));
        $data['source'] = $data['product_type'];
        $data['source_id'] = $data['product_id'];
        switch ($data['product_type'])
        {
            case 0:  //普通商品
                $result = app()->make(ProductRepository::class)->cartCheck($data,$this->request->userInfo());
                [$source, $sourceId, $pid] = explode(':', $this->request->param('source', '0'), 3) + ['', '', ''];
                $data['source'] = (in_array($source, [0, 1]) && $pid == $data['product_id']) ? $source : 0;
                if ($data['source'] > 0) $data['source_id'] = intval($sourceId);
                break;
            case 1:  //秒杀商品
                $result = app()->make(ProductRepository::class)->cartSeckillCheck($data,$this->request->userInfo());
                break;
            case 2:  //预售商品
                $result = app()->make(ProductPresellRepository::class)->cartCheck($data,$this->request->userInfo());
                $data['product_id'] = $result['product']['product_id'];
                break;
            case 3: //助力商品
                $result = app()->make(ProductAssistSetRepository::class)->cartCheck($data,$this->request->userInfo());
                $data['product_id'] = $result['product']['product_id'];
                break;
            case 4: //拼团商品
                $result = app()->make(ProductGroupRepository::class)->cartCheck($data,$this->request->userInfo());
                $data['product_id'] = $result['product']['product_id'];
                $data['source_id'] = $data['group_buying_id'];
                break;
            case 20://积分商品
                $result = app()->make(PointsProductRepository::class)->cartCheck($data,$this->request->userInfo());
                $data['product_id'] = $result['product']['product_id'];
                break;
        }

        unset($data['group_buying_id']);

        if ($cart = $result['cart']) {
            //更新购物车
            $cart_id = $cart['cart_id'];
            $cart_num = ['cart_num' => ($cart['cart_num'] + $data['cart_num'])];
            $storeCart = $this->repository->update($cart_id,$cart_num);
        } else {
            //添加购物车
            $data['uid'] = $this->request->uid();
            $data['mer_id'] = $result['product']['mer_id'];
            $cart = $storeCart = $this->repository->create($data);
        }
        event('user.cart', compact('user','storeCart'));
        return app('json')->success(['cart_id' => $cart['cart_id']]);
    }


    /**
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DbException
     * @author Qinii
     */
    public function change($id)
    {
        $where = $this->request->params(['cart_num']);
        $product_attr_unique = $this->request->param('product_attr_unique');
        if (intval($where['cart_num']) < 0)
            return app('json')->fail('数量必须大于0');
        if (!$cart = $this->repository->getOne($id, $this->request->uid()))
            return app('json')->fail('购物车信息不存在');
        if ($cart->product->once_count) {
            $cart_num = app()->make(ProductRepository::class)->productOnceCountCart($cart['product_id'], $this->request->uid());
            if (($cart_num - $cart['cart_num'] + $where['cart_num']) > $cart->product->once_count)
                return app('json')->fail('单次购买限制 ' . $cart->product->once_count . ' 件');
        }
        if (!$res = app()->make(ProductAttrValueRepository::class)->getOptionByUnique($product_attr_unique ?? $cart['product_attr_unique']))
            return app('json')->fail('SKU不存在');
        if ($res['stock'] < $where['cart_num'])
            return app('json')->fail('库存不足');
        if($product_attr_unique){
            $where['product_attr_unique'] = $product_attr_unique;
        }
        $this->repository->update($id, $where);
        return app('json')->success('修改成功');
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function batchDelete()
    {
        $ids = $this->request->param('cart_id');
        if(!count($ids))return app('json')->fail('参数错误');
        $this->repository->batchDelete($ids,$this->request->uid());
        return app('json')->success('删除成功');
    }


    /**
     * @return mixed
     * @author Qinii
     */
    public function cartCount()
    {
        return app('json')->success($this->repository->getCartCount($this->request->uid()));
    }

    /**
     * @param $data
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function check($data)
    {
        $product = app()->make(ProductRepository::class)->get($data['product_id']);
        if(!$product)
           throw new ValidateException('商品不存在');
        if( $data['cart_num'] < 0 )
            throw new ValidateException('数量必须大于0');
        if(!$res= app()->make(ProductAttrValueRepository::class)->getOptionByUnique($data['product_attr_unique']))
            throw new ValidateException('SKU不存在');
        if($res['product_id'] != $data['product_id'])
            throw new ValidateException('数据不一致');
        if($res['stock'] < $data['cart_num'])
            throw new ValidateException('库存不足');
        $data['is_new'] = 1;
        $data['uid'] = $this->request->uid();
        $data['mer_id'] = $product['mer_id'];
        return $data;
    }


    /**
     * @param validate $validate
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function again(validate $validate)
    {
        $param = $this->request->param('data',[]);
        foreach ($param as $data){
            $validate->check($data);
            $item[] = $this->check($data);
        }

        foreach ($item as $it){
            $it__id = $this->repository->create($it);
            $ids[] = $it__id['cart_id'];
        }
        return app('json')->success(['cart_id' => $ids]);
    }


    /**
     * @param validate $validate
     * @return array
     * @author Qinii
     * @day 2020-06-11
     */
    public function checkParams(validate $validate)
    {
        $data = $this->request->params(['product_id','product_attr_unique','cart_num','is_new',['product_type',0],['group_buying_id',0],['spread_id',0]]);
        $validate->check($data);
        if ($data['spread_id']) {
            if ($data['spread_id'] !== $this->request->userInfo()->uid){
                $user = app()->make(UserRepository::class)->get($data['spread_id']);
                if (!$user) $data['spread_id'] = 0;
            } else {
                $data['spread_id'] = 0;
            }
        }
        return $data;
    }

    /**
     * TODO 套餐购买
     * @return \think\response\Json
     * @author Qinii
     * @day 1/7/22
     */
    public function batchCreate()
    {
        $data = $this->request->params(['data','discount_id','is_new']);
        $productRepostory = app()->make(ProductRepository::class);
        if (!$data['discount_id'])
            return app('json')->fail('优惠套餐ID不能为空');
        if (!$data['is_new'])
            return app('json')->fail('套餐不能加入购物车');

        $cartData = app()->make(StoreDiscountRepository::class)->check($data['discount_id'], $data['data'], $this->request->userInfo());
        $cart_id = [];
        if ($cartData){
            foreach ($cartData as $datum) {
                $cart = $this->repository->create($datum);
                $cart_id[] = $cart['cart_id'];
            }
        }
        return app('json')->success(compact('cart_id'));
    }
}
