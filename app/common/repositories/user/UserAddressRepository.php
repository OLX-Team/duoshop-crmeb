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

use app\common\repositories\BaseRepository;
use app\common\dao\user\UserAddressDao as dao;
use app\common\repositories\store\shipping\CityRepository;

/**
 * Class UserAddressRepository
 * @package app\common\repositories\user
 * @day 2020/6/3
 * @mixin dao
 */
class UserAddressRepository extends BaseRepository
{
    /**
     * @var dao
     */
    protected $dao;


    /**
     * UserAddressRepository constructor.
     * @param dao $dao
     */
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }


    /**
     * @param int $id
     * @param int $uid
     * @return bool
     * @author Qinii
     */
    public function fieldExists(int $id,int $uid)
    {
        return $this->dao->userFieldExists($this->dao->getPk(),$id,$uid);
    }

    /**
     * @param int $uid
     * @return bool
     * @author Qinii
     */
    public function defaultExists(int $uid)
    {
        return $this->dao->userFieldExists('is_default',1,$uid);
    }

    /**
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Qinii
     */
    public function checkDefault(int $id)
    {
        $res = $this->dao->getWhere([$this->dao->getPk() => $id]);
        return $res['is_default'];
    }

    /**
     * @param $province
     * @param $city
     * @return mixed
     * @author Qinii
     */
    public function getCityId($province,$city)
    {
        $make = app()->make(CityRepository::class);
        $provinceData = $make->getWhere(['name' => $province]);
        $cityData = $make->getWhere(['name' => $city,'parent_id' => $provinceData['city_id']]);
        if(!$cityData)$cityData = $make->getWhere([['name','like','直辖'.'%'],['parent_id' ,'=', $provinceData['city_id']]]);
        return $cityData['city_id'];
    }

    /**
     * @param $uid
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     */
    public function getList($uid)
    {
        $list = $this->dao->getAll($uid)->order('is_default desc')->select();
        return compact('list');
    }

    /**
     * @param $id
     * @param $uid
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Qinii
     */
    public function get($id,$uid)
    {
        return $this->dao->getWhere(['address_id' => $id,'uid' => $uid])->append(['area']);
    }
}
