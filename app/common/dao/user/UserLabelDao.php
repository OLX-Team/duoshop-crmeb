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
use app\common\model\BaseModel;
use app\common\model\user\UserLabel;
use think\db\BaseQuery;

/**
 * Class UserLabelDao
 * @package app\common\dao\user
 * @author xaboy
 * @day 2020-05-07
 */
class UserLabelDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return UserLabel::class;
    }


    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020-05-06
     */
    public function search(array $where = [])
    {
        return UserLabel::getDB()->when(!isset($where['all']) || $where['all'] === '', function ($query) use ($where) {
            $query->where('type', 0);
        })->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('mer_id', $where['mer_id']);
        }, function ($query) {
            $query->where('mer_id', 0);
        });
    }

    /**
     * @param int $merId
     * @return array
     * @author xaboy
     * @day 2020/10/20
     */
    public function allOptions($merId = 0)
    {
        return UserLabel::getDB()->where('mer_id', $merId)->where('type', 0)->column('label_name', 'label_id');
    }

    /**
     * @param array $ids
     * @param int $merId
     * @return array
     * @author xaboy
     * @day 2020/10/20
     */
    public function labels(array $ids, $merId = 0)
    {
        return UserLabel::getDB()->where('mer_id', $merId)->whereIn('label_id', $ids)->column('label_name');
    }

    /**
     * @param int $id
     * @param int $mer_id
     * @return bool
     * @author xaboy
     * @day 2020/10/20
     */
    public function exists(int $id, $mer_id = 0)
    {
        return $this->existsWhere(['label_id' => $id, 'type' => 0, 'mer_id' => $mer_id]);
    }

    public function existsName($name, $mer_id = 0, $type = 0, $except = null)
    {
        return UserLabel::where('label_name', $name)->where('mer_id', $mer_id)->where('type', $type)
                ->when($except, function ($query, $except) {
                    $query->where($this->getPk(), '<>', $except);
                })->count() > 0;
    }

    public function intersection(array $ids, $merId, $type)
    {
        return UserLabel::getDB()->whereIn('label_id', $ids)->where('mer_id', $merId)->when(!is_null($type), function ($query) use ($type) {
            $query->where('type', $type);
        })->column('label_id');
    }
}
