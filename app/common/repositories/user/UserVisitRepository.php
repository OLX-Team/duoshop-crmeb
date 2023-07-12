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


namespace app\common\repositories\user;


use app\common\dao\user\UserVisitDao;
use app\common\repositories\BaseRepository;

/**
 * Class UserVisitRepository
 * @package app\common\repositories\user
 * @author xaboy
 * @day 2020/5/27
 * @mixin UserVisitDao
 */
class UserVisitRepository extends BaseRepository
{
    /**
     * @var UserVisitDao
     */
    protected $dao;

    /**
     * UserVisitRepository constructor.
     * @param UserVisitDao $dao
     */
    public function __construct(UserVisitDao $dao)
    {
        $this->dao = $dao;
    }

    public function getRecommend(?int $uid)
    {
        $data = $this->dao->search(['uid' => $uid, 'type' => 'product'])->with(['product' => function ($query) {
            $query->field('product_id,cate_id');
        }])->limit(7)->select();
        $i = [];
        if (is_array($data)) {
            foreach ($data as $item) {
                $i[] = $item['product']['cate_id'];
            }
        }
        return $i;
    }

    public function getHistory($uid,$page, $limit)
    {
        $query = $this->dao->search(['uid' => $uid, 'type' => 'product']);
        $query->with(['product'=>function($query){
            $query->field('product_id,image,store_name,slider_image,price,is_show,status,sales');
        }]);
        $count = $query->count();
        $list = $query->page($page,$limit)->select();
        return compact('count','list');
    }

    public function getSearchLog(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $query->with(['user' => function ($query) {
            $query->field('uid,nickname,avatar,user_type');
        }]);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('create_time DESC')->select();
        return compact('count', 'list');
    }


}
