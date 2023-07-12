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


namespace app\common\dao\wechat;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\wechat\WechatReply;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

/**
 * Class WechatReplyDao
 * @package app\common\dao\wechat
 * @author xaboy
 * @day 2020-04-24
 */
class WechatReplyDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return WechatReply::class;
    }

    /**
     * @param string $key
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-24
     */
    public function keyByReply(string $key)
    {
        return WechatReply::where('key', $key)->find();
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020-04-24
     */
    public function search(array $where)
    {
        $query = WechatReply::getDB()->where('hidden', 0);
        if (isset($where['keyword']) && $where['keyword'])
            $query->whereLike('key', "%{$where['keyword']}%");

        return $query;
    }

    /**
     * @param int $id
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-04-24
     */
    public function delete(int $id)
    {
        return ($this->getModel())::getDB()->where($this->getPk(), $id)->where('hidden', 0)->delete();
    }

    /**
     * @param $key
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-27
     */
    public function keyByValidData($key)
    {
        return WechatReply::getDB()->where(function ($query) use ($key) {
            $query->where('key', $key)->whereFieldRaw('CONCAT(\',\',`key`,\',\')', 'LIKE', '%,' . $key . ',%', 'OR');
        })->where('status', 1)->find();
    }
}
