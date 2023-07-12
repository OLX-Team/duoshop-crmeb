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

use app\common\model\store\product\ProductAssistSet;
use app\common\repositories\BaseRepository;
use app\common\dao\store\product\ProductAssistSetDao;
use app\common\repositories\store\order\StoreOrderRepository;
use think\exception\ValidateException;
use think\facade\Db;

class ProductAssistSetRepository extends BaseRepository
{
    public function __construct(ProductAssistSetDao $dao)
    {
        $this->dao = $dao;
    }

    public function getApiList(array $where,int $page,int $limit)
    {
        $query = $this->dao->getSearch($where)->order('create_time DESC')
            ->with([
            "assist",
            "assistSku",
            'product' => function($query){
                $query->field('product_id,image,store_name,status,unit_name,rank,mer_status,slider_image,mer_id,price');
            }]);
        $count = $query->count();
        $list = $query->page($page,$limit)->append(['check','stop_time'])->select()->each(function ($item)use($where){
            $order = $this->getOrderInfo($where['uid'],$item['product_assist_set_id']);
            return $item['order'] = $order ? $order : new \stdClass();
        });
        return compact('count','list');
    }

    public function getMerchantList(array $where,int $page,int $limit)
    {
        $query = $this->dao->getSearch($where)->order('create_time DESC')
            ->with(['assist.assistSku','product' => function($query){
                $query->field('product_id,image,store_name,status,unit_name,rank,mer_status,slider_image,mer_id');
            },'user' => function($query){
                $query->field('uid,nickname');
            }])
            ->append(['check','user_count']);
        $count = $query->count();
        $list = $query->page($page,$limit)->select();
        return compact('count','list');
    }

    public function getAdminList(array $where,int $page,int $limit)
    {
        $query = $this->dao->getSearch($where)->order('create_time DESC')
            ->with(['assist.assistSku','product' => function($query){
                $query->field('product_id,image,store_name,status,unit_name,rank,mer_status,slider_image,mer_id');
            },'merchant','user' => function($query){
                $query->field('uid,nickname');
            }])
            ->append(['check','user_count']);
        $count = $query->count();
        $list = $query->page($page,$limit)->select();
        return compact('count','list');
    }
    /**
     * TODO 发起助力活动
     * @param int $assistId
     * @param int $uid
     * @return \app\common\dao\BaseDao|array|\think\Model|null
     * @author Qinii
     * @day 2020-10-27
     */
    public function create(int $assistId,int $uid)
    {
        $where['product_assist_id'] = $assistId;
        $where['uid'] = $uid;
        $where['is_del'] = 0;
        $make = app()->make(StoreOrderRepository::class);
        $arr =  ['exsits_id' => $assistId,'product_type' => 3];
        $make->getTattendCount($arr,$uid)->count();

        $result = $this->dao->getSearch($where)->where('status','in',[1,10])->find();
        if($result){
            $order = $this->getOrderInfo($uid,$result['product_assist_set_id']);
            $paid = $order['paid'] ?? null;
            if(!$order || $result['status'] == 1 || !$paid) return $result;
        }
        $make = app()->make(ProductAssistRepository::class);
        $res = $make->checkAssist($assistId,$uid);
        $where['product_id'] = $res['product_id'];
        $where['assist_count'] = $res['assist_count'];
        $where['assist_user_count'] = $res['assist_user_count'];
        $where['mer_id'] = $res['mer_id'];
        $where['share_num'] = 1;
        $where['view_num'] = 1;
        $result = $this->dao->create($where);

        return $result;
    }

    /**
     * TODO 助力操作
     * @param int $id
     * @param $userInfo
     * @author Qinii
     * @day 2020-10-27
     */
    public function set(int $id,$userInfo)
    {
        $where = [
            "product_assist_set_id" => $id,
            "status" => 1,
        ];
        $result = $this->dao->getSearch($where)->find();
        if(!$result) throw new ValidateException('活动不存在或已关闭');

        $relation = $this->relation($result,$userInfo->uid);
        if(!$relation) throw new ValidateException('活动不存在或已关闭');
        if($relation == -1)throw new ValidateException('您的助力次数已达上限');
        if($relation == 10)throw new ValidateException('不能为自己助力');

        if($result['assist_count'] <= $result['yet_assist_coount']) {
            $result->yet_assist_count = $result->assist_count ;
            $result->status = 10;
            $result->save();
            throw new ValidateException('助力已完成');
        }

        $data = [
            "product_assist_set_id" => $id,
            'uid' => $userInfo->uid,
            'avatar_img' => $userInfo->avatar,
            'nickname' => $userInfo->nickname,
            "product_assist_id" => $result['product_assist_id']
        ];

        Db::transaction(function()use($id,$data,$result){

            $yet = $result->yet_assist_count + 1;

            if($yet >= $result['assist_count']){
                $yet = $result->assist_count;
                $result->status = 10;
            }
            $result->yet_assist_count = $yet;
            $result->save();

            $make = app()->make(ProductAssistUserRepository::class);
            $make->create($data);
        });
    }

    /**
     * TODO
     * @param int $id
     * @return array|\think\Model|null
     * @author Qinii
     * @day 2020-10-27
     */
    public function detail(int $id,$userInfo)
    {
        $where = [
            "product_assist_set_id" => $id,
        ];
        $res = $this->dao->getSearch($where)->with([
            'product.content' => function($query){
                $query->field('product_id,store_name,image,old_product_id');
            },
            'assist.assistSku.sku',
            'user' =>function($query){
                $query->field('uid,avatar,nickname');
            }
        ])->append(['stopTime'])->find();
        if(!$res) throw new ValidateException('数据丢失');
        $res['product']['unique'] = $res['assist']['assistSku'][0]['unique'];
        $relation = $this->relation($res,$userInfo->uid);
        $res['relation'] = $relation;
        $countData = app()->make(ProductAssistRepository::class)->getUserCount();
        $res['user_count_all'] = $countData['count'];
        $res['user_count_product'] = $res->assist->user_count;
        $order = $this->getOrderInfo($userInfo->uid,$id);
        if($relation == 10) $res['order'] = $order ? $order : new \stdClass();

        //已经参与活动了,不可在发起活动
        $where = [
            "product_assist_id" => $res['product_assist_id'],
            'uid' => $userInfo->uid,
            'status' => 1
        ];
        $res['create_status'] = true;
        if($res['uid'] !== $userInfo->uid) $this->dao->incNum(2,$id);

        return $res;
    }

    public function getOrderInfo(int $uid,int $assistSetId)
    {
        $result  = null;
        $order_make = app()->make(StoreOrderRepository::class);
        $tattend = [
            'activity_id' => $assistSetId,
            'product_type' => 3
            ];
        $order = $order_make->getTattendCount($tattend,$uid)->find();
        if($order){
            $result = [
                'paid' => $order['paid'],
                'order_id' => $order['order_id'],
                'group_order_id' => $order['group_order_id'],
            ];
        }

        return $result;
    }
    /**
     * TODO 用户于当前助力活动的关系
     * @param ProductAssistSetRepository $res
     * @param int $uid
     * @return bool
     * @author Qinii
     * @day 2020-10-27
     */
    public function relation(ProductAssistSet $res,int $uid)
    {
        if($res['status']  == -1 ) return false; // 活动过结束
        //过期 活动结束
        if($res->stop_time < time()) {
            $res->status = -1;
            $res->save();
            return false;
        }
        if($uid == $res['uid']){
            //发起者
            $relation = 10;
        }else{
            //不可助力
            $relation = -2;
            $make = app()->make(ProductAssistUserRepository::class);
            //$_count 有自己助力
            $_count = $make->getSearch(['product_assist_set_id' => $res['product_assist_set_id'],'uid' => $uid])->count();
            if(!$_count && $res['assist_count'] > $res['yet_assist_count']){
                $count = $make->getSearch(['product_assist_id' => $res['product_assist_id'],'uid' => $uid])->count();
                $relation = -1;
                //用户还可以助力
                if($count < $res['assist_user_count']) $relation = 1;
            }
        }
        return $relation;
    }

    public function cartCheck(array $data,$userInfo)
    {
        /**
         *  1 活动是否助力完成
         *  2 商品是否有效
         *  2 库存是否不足
         */
        if(!$data['is_new']) throw new ValidateException('助力商品不可加入购物车');
        if($data['cart_num'] != 1) throw new ValidateException('助力商品每次只能购买一件');
        $where[$this->dao->getPk()] = $data['product_id'];
        $where['uid'] = $userInfo->uid;
        $result = $this->dao->getSearch($where)->find();
        if(!$result) throw new ValidateException('请先发起您自己的助力活动');

        $order_make = app()->make(StoreOrderRepository::class);
        $tattend = [
            'activity_id' => $data['product_id'],
            'product_type' => 3,
        ];
        if($order_make->getTattendCount($tattend,$userInfo->uid)->count())
            throw new ValidateException('请勿重复下单');
        $make = app()->make(ProductAssistRepository::class);

        if($result['status'] == -1) throw new ValidateException('活动已结束');
        if($result['assist_count'] !== $result['yet_assist_count']) throw new ValidateException('快去邀请好友来助力吧');
        $res = $make->checkAssist($result['product_assist_id'],$userInfo->uid);
        $product = $res['product'];
        $sku = $product['assistSku'];
        $cart = null;
        return compact('product','sku','cart');
    }
}

