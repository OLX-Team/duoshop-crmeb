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
use app\common\model\wechat\WechatUser;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

/**
 * Class WechatUserDao
 * @package app\common\dao\wechat
 * @author xaboy
 * @day 2020-04-28
 */
class WechatUserDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return WechatUser::class;
    }

    /**
     * @param string $openId
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-28
     */
    public function openIdByWechatUser(string $openId)
    {
        return WechatUser::getDB()->where('openid', $openId)->find();
    }

    /**
     * @param string $unionId
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-28
     */
    public function unionIdByWechatUser(string $unionId)
    {
        return WechatUser::getDB()->where('unionid', $unionId)->find();
    }

    /**
     * @param string $openId
     * @return mixed
     * @author xaboy
     * @day 2020-04-28
     */
    public function openIdById(string $openId)
    {
        return WechatUser::getDB()->where('openid', $openId)->value('wechat_user_id');
    }

    /**
     * @param string $openId
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-28
     */
    public function routineIdByWechatUser(string $openId)
    {
        return WechatUser::getDB()->where('routine_openid', $openId)->find();
    }

    /**
     * @param string $unionId
     * @return mixed
     * @author xaboy
     * @day 2020-04-28
     */
    public function unionIdById(string $unionId)
    {
        return WechatUser::getDB()->where('unionid', $unionId)->value('wechat_user_id');
    }

    /**
     * @param $id
     * @return mixed
     * @author xaboy
     * @day 2020/5/30
     */
    public function idByOpenId(int $id)
    {
        return WechatUser::getDB()->where('wechat_user_id', $id)->value('openid');
    }

    /**
     * @param $id
     * @return mixed
     * @author xaboy
     * @day 2020/5/30
     */
    public function idByRoutineId(int $id)
    {
        return WechatUser::getDB()->where('wechat_user_id', $id)->value('routine_openid');
    }

    /**
     * @param string $openId
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-04-28
     */
    public function unsubscribe(string $openId)
    {
        return WechatUser::getDB()->where('openid', $openId)->update(['subscribe' => 0]);
    }

    /**
     * @param $id
     * @return bool
     * @author xaboy
     * @day 2020-05-11
     */
    public function isSubscribeWechatUser($id)
    {
        return WechatUser::getDB()->where('wechat_user_id', $id)->whereNotNull('openid')->where('subscribe', 1)->count() > 0;
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020-04-29
     */
    public function search(array $where)
    {
        $query = WechatUser::getDB()->whereNotNull('openid')->whereNotNull('routine_openid')->order('wechat_user_id desc');
        if (isset($where['nickname']) && $where['nickname']) $query->where('nickname', 'LIKE', "%$where[nickname]%");
        if (isset($where['add_time']) && $where['add_time']) getModelTime($query, $where['add_time']);
        if (isset($where['tagid_list']) && $where['tagid_list']) {
            $tagid_list = explode(',', $where['tagid_list']);
            foreach ($tagid_list as $v) {
                $query->where('tagid_list', 'LIKE', "%$v%");
            }
        }
        if (isset($where['groupid']) && $where['groupid']) $query->where('groupid', $where['groupid']);
        if (isset($where['sex']) && $where['sex']) $model = $query->where('sex', $where['sex']);
        if (isset($where['subscribe']) && $where['subscribe']) $query->where('subscribe', $where['subscribe']);


        return $query;
    }
}
