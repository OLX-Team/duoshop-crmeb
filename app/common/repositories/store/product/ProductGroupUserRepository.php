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

use app\common\repositories\BaseRepository;
use app\common\dao\store\product\ProductGroupUserDao;
use crmeb\services\LockService;
use think\exception\ValidateException;

class ProductGroupUserRepository extends BaseRepository
{
    protected $dao;

    /**
     * ProductGroupRepository constructor.
     * @param ProductGroupUserDao $dao
     */
    public function __construct(ProductGroupUserDao $dao)
    {
        $this->dao = $dao;
    }

    public function create($userInfo, $data)
    {
        $_where = [
            'product_group_id' => $data['product_group_id'],
            'group_buying_id' => $data['group_buying_id'],
            'uid' => $userInfo->uid,
        ];
        $user = $this->getWhere($_where);
        if ($user) {
            throw new ValidateException('您已经参加过此团');
        }

        $data = [
            'product_group_id' => $data['product_group_id'],
            'group_buying_id' => $data['group_buying_id'],
            'is_initiator' => $data['is_initiator'],
            'order_id' => $data['order_id'],
            'uid' => $userInfo->uid,
            'nickname' => $userInfo->nickname,
            'avatar' => $userInfo->avatar,
        ];
        return app()->make(LockService::class)->exec('order.group_buying', function () use ($data) {
            $this->dao->create($data);
        });
    }

    /**
     * TODO 团员列表
     * @param $id
     * @return array
     * @author Qinii
     * @day 1/12/21
     */
    public function getAdminList($where,$page,$limit)
    {
        $query = $this->dao->getSearch($where)->where('uid','<>',0)->where('is_del',0)->with([
            'orderInfo' => function($query){
                $query->field('order_id,order_sn,pay_price,status');
            },
        ])->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page,$limit)->select();
        return compact('count','list');
    }

    /**
     * TODO 团员列表
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 1/12/21
     */
    public function getApiList($where,$page,$limit)
    {
        $query = $this->dao->getSearch($where)->where('uid','<>',0)->where('is_del',0)->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page,$limit)->hidden(['uid','order_id','is_del'])->select();
        return compact('count','list');
    }


    /**
     * TODO 转移团长
     * @param $groupId
     * @return bool
     * @author Qinii
     * @day 1/13/21
     */
    public function changeInitator(int $groupId,$uid)
    {
        $user = $this->dao->getSearch(['group_buying_id' => $groupId])
            ->where('uid','<>',0)
            ->where('is_del',0)
            ->where('uid','<>',$uid)
            ->order('create_time ASC')->find();
        if($user) {
            $user->is_initiator = 1;
            $user->save();
        }
    }

}
