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


namespace app\common\dao\user;


use app\common\dao\BaseDao;
use app\common\model\user\UserRelation;
use app\common\model\user\UserRelation as model;

/**
 * Class UserVisitDao
 * @package app\common\dao\user
 * @author xaboy
 * @day 2020/5/27
 */
class UserRelationDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/5/27
     */
    protected function getModel(): string
    {
        return model::class;
    }

    /**
     * @param $field
     * @param $value
     * @param null $type
     * @param null $uid
     * @return mixed
     * @author Qinii
     */
    public function apiFieldExists($field, $value, $type = null, $uid = null)
    {
        return $this->getModel()::getDB()->when($uid, function ($query) use ($uid) {
            $query->where('uid', $uid);
        })->when(!is_null($type), function ($query) use ($type) {
            $query->where('type', $type);
        })->where($field, $value);
    }

    /**
     * @param $where
     * @return mixed
     * @author Qinii
     */
    public function search($where)
    {
        $query = ($this->getModel()::getDB())
            ->when((isset($where['type']) && $where['type'] !== ''), function ($query) use ($where) {
                if(in_array($where['type'],[1,2,3,4])){
                    $query->whereIn('type',[1,2,3,4]);
                }else{
                    $query->where('type',$where['type']);
                }
            })->when((isset($where['uid']) && $where['uid']), function ($query) use ($where) {
                $query->where('uid', $where['uid']);
            });

        return $query->order('create_time DESC');
    }


    /**
     * @param array $where
     * @author Qinii
     */
    public function destory(array $where)
    {
        ($this->getModel()::getDB())->where($where)->delete();
    }

    public function dayLikeStore($day, $merId = null)
    {
        return getModelTime(UserRelation::getDB()->where('type', 10)->when($merId, function ($query, $merId) {
            $query->where('type_id', $merId);
        }), $day)->count();
    }

    public function dateVisitStore($date, $merId = null)
    {
        return UserRelation::getDB()->where('type', 11)->when($merId, function ($query, $merId) {
            $query->where('type_id', $merId);
        })->when($date, function ($query, $date) {
            getModelTime($query, $date, 'create_time');
        })->count();
    }


    /**
     * @param $uid
     * @param array $ids
     * @return array
     * @author xaboy
     * @day 2020/10/20
     */
    public function intersectionPayer($uid, array $ids): array
    {
        return UserRelation::getDB()->where('uid', $uid)->whereIn('type', 12)->whereIn('type_id', $ids)->column('type_id');
    }

    public function getUserProductToCommunity(?string  $keyword, int $uid)
    {

        $query = UserRelation::hasWhere('spu', function ($query) use($keyword) {
            $query->when($keyword, function ($query) use($keyword) {
                $query->whereLike('store_name',"%{$keyword}%");
            });
            $query->where('status',1);
        });
        $query->where('uid',$uid);
        return $query;
    }
}
