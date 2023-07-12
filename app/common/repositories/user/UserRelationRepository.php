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

use app\common\dao\user\UserRelationDao as dao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\ProductAssistRepository;
use app\common\repositories\store\product\ProductGroupRepository;
use app\common\repositories\store\product\ProductPresellRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Class UserRelationRepository
 * @package app\common\repositories\user
 * @mixin dao
 */
class UserRelationRepository extends BaseRepository
{

    protected $dao;

    /**
     * UserRelationRepository constructor.
     * @param dao $dao
     */
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $params
     * @return bool
     * @author Qinii
     */
    public function fieldExists($params)
    {
        switch ($params['type']) {
            case 0: //普通商品，
                return app()->make(ProductRepository::class)->apiExists(0, $params['type_id']);
                break;
            case 1: //秒杀商品
                return app()->make(ProductRepository::class)->apiExists(0, $params['type_id']);
                break;
            case 2: //预售商品
                return app()->make(ProductPresellRepository::class)->getWhereCount(['product_presell_id' => $params['type_id']]);
                break;
            case 3: //助力商品
                return app()->make(ProductAssistRepository::class)->getWhereCount(['product_assist_id' => $params['type_id']]);
                break;
            case 4: //拼团商品
                return app()->make(ProductGroupRepository::class)->getWhereCount(['product_group_id' => $params['type_id']]);
                break;
            case 10: //商铺
                return app()->make(MerchantRepository::class)->apiGetOne($params['type_id']);
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * @param array $params
     * @param int $uid
     * @return bool
     * @author Qinii
     */
    public function getUserRelation(array $params, int $uid)
    {
        if(in_array($params['type'],[0,1,2,3,4])) {
            $spu = $this->getSpu($params);;
            $params['type_id'] = $spu['spu_id'];
            $params['type'] = 1;
        }
        return ($this->dao->apiFieldExists('type_id', $params['type_id'], $params['type'], $uid)->count()) > 0;
    }

    /**
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array|bool
     * @author Qinii
     */
    public function search(array $where, int $page, int $limit)
    {
        $with = [];
        if($where['type'] == 1) $with = [
            'spu'
        ];
        if($where['type'] == 10) $with = [
            'merchant' => function($query){
                $query->field('mer_id,type_id,mer_name,mer_avatar,sales,mer_info,care_count,status,is_del,mer_state');
            }
        ];
        $query = $this->dao->search($where);
        $query->with($with)->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        foreach ($list as &$item) {
            if ($item['type'] == 1) {
                if(isset($item['spu']['product_type']) && $item['spu']['product_type'] == 1){
                    $item['spu']['stop_time'] = $item->stop_time;
                    unset($item['spu']['seckillActive']);
                }
            } else {
                if (isset($item['merchant']) && $item['merchant']) {
                    $item['merchant']['showProduct'] = $item['merchant']['AllRecommend'];
                }
            }
        }
        return compact('count', 'list');
    }


    /**
     * @param int $uid
     * @param array $data
     * @author Qinii
     */
    public function batchCreate(int $uid, array $data)
    {
        Db::transaction(function () use ($data, $uid) {
            foreach ($data['type_id'] as $item) {
                $param = ['type' => $data['type'], 'type_id' => $item, 'uid' => $uid];
                if(!$this->dao->getWhereCount($param)) $this->dao->create($param);
            }
        });
    }

    /**
     * @param array $data
     * @return \app\common\dao\BaseDao|\think\Model
     * @author Qinii
     */
    public function create(array $params)
    {

        if($params['type'] == 10) {
            $id = $params['type_id'];
            app()->make(UserMerchantRepository::class)->getInfo($params['uid'], $params['type_id']);
            $make = app()->make(MerchantRepository::class);
        }else{
            $spu = $this->getSpu($params);
            $params['type_id'] = $spu->spu_id;
            $params['type'] = 1;
            $make = app()->make(ProductRepository::class);
            $id = $spu->product_id;
        }
        return Db::transaction(function()use($params,$make,$id){
            $make->incCareCount($id);
            $this->dao->create($params);
        });
    }

    /**
     * TODO 批量删除
     * @param array $ids
     * @param $uid
     * @param $type
     * @author Qinii
     * @day 2023/2/16
     */
    public function batchDestory(array $ids,$uid, $type = 1)
    {
        if ($type == 10) {
            app()->make(MerchantRepository::class)->decCareCount($ids);
        } else {
            $pids = app()->make(SpuRepository::class)->search(['spu_ids' => $ids])->column('S.product_id');
            app()->make(ProductRepository::class)->decCareCount($pids);
        }
        $this->dao->search(['uid' => $uid,'type' => $type])->where('type_id','in',$ids)->delete();
    }

    /**
     * @param $uid
     * @param array $merIds
     * @author xaboy
     * @day 2020/10/20
     */
    public function payer($uid, array $merIds)
    {
        $isset = $this->dao->intersectionPayer($uid, $merIds);
        $merIds = array_diff($merIds, $isset);
        if (!count($merIds)) return;
        $data = [];
        foreach ($merIds as $merId) {
            $data[] = [
                'type_id' => $merId,
                'type' => 12,
                'uid' => $uid
            ];
        }
        $this->dao->insertAll($data);
    }


    public function getSpu(array $data)
    {
        $make = app()->make(SpuRepository::class);
        $where['product_type'] = $data['type'];
        switch ($data['type']) {
            case 0:
            case 1:
                $where['product_id'] = $data['type_id'];
                break;
            default:
                $where['activity_id'] = $data['type_id'];
                break;
        }
        $ret = $make->getSearch($where)->find();
        if(!$ret) throw new ValidateException('SPU不存在');
        return $ret;
    }

    /**
     * TODO
     * @param string|null $keyword
     * @param int $uid
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 10/28/21
     */
    public function getUserProductToCommunity(?string $keyword, int $uid, int $page, int $limit)
    {
        $query = $this->dao->getUserProductToCommunity($keyword, $uid)->group('product_id');
        $count = $query->count();
        $list = $query->setOption('field',[])->field('uid,product_id,product_type,spu_id,image,store_name,price')
            ->page($page, $limit)->select();
        return compact('count', 'list');
    }
}
