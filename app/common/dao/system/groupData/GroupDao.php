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


namespace app\common\dao\system\groupData;


use app\common\dao\BaseDao;
use app\common\model\system\groupData\SystemGroup;
use think\Collection;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;


/**
 * Class GroupDao
 * @package app\common\dao\system\groupData
 * @author xaboy
 * @day 2020-03-27
 */
class GroupDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return SystemGroup::class;
    }


    /**
     * @return Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-01
     */
    public function all()
    {
        return SystemGroup::getDB()->select();
    }

    /**
     * @return int
     * @author xaboy
     * @day 2020-04-01
     */
    public function count()
    {
        return SystemGroup::getDB()->count();
    }

    /**
     * @param $page
     * @param $limit
     * @return BaseQuery
     * @author xaboy
     * @day 2020-04-01
     */
    public function page($page, $limit)
    {
        return SystemGroup::getDB()->page($page, $limit);
    }

    /**
     * @param $key
     * @param int|null $except
     * @return bool
     * @author xaboy
     * @day 2020-03-27
     */
    public function keyExists($key, ?int $except = null): bool
    {
        return parent::fieldExists('group_key', $key, $except);
    }

    /**
     * @param $id
     * @return mixed
     * @author xaboy
     * @day 2020-04-02
     */
    public function fields($id, $field = 'group_id')
    {
        return json_decode(SystemGroup::getDB()->where($field, $id)->value('fields'), true);
    }

    /**
     * @param string $key
     * @return mixed
     * @author xaboy
     * @day 2020/5/27
     */
    public function keyById(string $key)
    {
        return SystemGroup::getDB()->where('group_key', $key)->value('group_id');
    }
}
