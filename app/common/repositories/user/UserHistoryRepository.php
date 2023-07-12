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

use think\facade\Log;
use app\common\repositories\BaseRepository;
use app\common\dao\user\UserHistoryDao;
use app\common\repositories\store\product\SpuRepository;

class UserHistoryRepository extends BaseRepository
{

    protected $dao;

    /**
     * UserHistoryRepository constructor.
     * @param UserHistoryDao $dao
     */
    public function __construct(UserHistoryDao $dao)
    {
        $this->dao = $dao;
    }

    public function getApiList($page,$limit,$uid,$type)
    {
        $with = [];
        if($type == 1){
            $with = ['spu'];
            $count = app()->make(UserHistoryRepository::class)->userTotalHistory($uid);
        }
        $query = $this->dao->search($uid,$type);
        $query->with($with)->order('update_time DESC');
        $count = $count ?? $query->count();
        $data = $query->page($page,$limit)->select();
        $res = $list = [];
        foreach ($data as $item) {
            if ($item['spu']) {
                $time = date('m月d日',strtotime($item['update_time']));
                $res[$time][] = $item;
            }
        }
        foreach ($res as $k => $v) {
            $list[] = ['date' => $k, 'list' => $v];
        }
        return compact('count','list');
    }

    public function getList($page,$limit,$uid,$type)
    {
        $with = [];
        if($type == 1)$with = ['spu'];

        $query = $this->dao->search($uid,$type);
        $query->with($with)->order('update_time DESC');
        $count = $query->count();
        $list = $query->page($page,$limit)->select();
        return compact('count','list');
    }

    public function createOrUpdate(array $data)
    {
        $make = app()->make(SpuRepository::class);
        $where['product_type'] = $data['product_type'];
        switch ($data['product_type']) {
            case 0:
                $where['product_id'] = $data['id'];
                break;
            case 1:
                $where['product_id'] = $data['id'];
                break;
            default:
                $where['activity_id'] = $data['id'];
                break;
        }
        try {
            $ret = $make->getSearch($where)->find();
            if ($ret && $ret['spu_id']) {
                $arr = [
                    'res_type' => $data['res_type'],
                    'res_id' => $ret['spu_id'],
                    'uid' => $data['uid']
                ];
                $this->dao->createOrUpdate($arr);
            }
            return $ret;
        } catch (\Exception $exception) {
            Log::info('浏览记录添加失败，ID：' . $data['id'] . '类型：' . $data['product_type']);
        }
    }

    /**
     * TODO 商品推荐列表
     * @param int|null $uid
     * @return array
     * @author Qinii
     * @day 4/9/21
     */
    public function getRecommend(?int $uid)
    {
        $ret = $this->dao->search($uid,1)->with(['spu.product'])->limit(10)->select();
        if(!$ret) return [];
        $i = [];
        foreach ($ret as $item){
            if(isset($item['spu']['product']['cate_id'])) $i[] = $item['spu']['product']['cate_id'];
        }
        if($i) $i = array_unique($i);
        return $i;
    }

    public function historyLst($where,$page,$limit)
    {
        $query = $this->dao->joinSpu($where);
        $query->with([
            'spu'
        ])->order('update_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->setOption('field',[])->field('uid,product_id,product_type,spu_id,image,store_name,price')
            ->select();
        return compact('count','list');
    }
}
