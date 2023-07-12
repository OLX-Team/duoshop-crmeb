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


namespace app\common\dao\system;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\system\Cache;
use think\db\exception\DbException;

/**
 * Class CacheDao
 * @package app\common\dao\system
 * @author xaboy
 * @day 2020-04-24
 */
class CacheDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return Cache::class;
    }

    /**
     * @param $key
     * @return mixed
     * @author xaboy
     * @day 2020-04-24
     */
    public function getResult($key)
    {
        $val = Cache::getDB()->where('key', $key)->value('result');
        return $val ? json_decode($val, true) : null;
    }

    /**
     * @param string $key
     * @param $data
     * @throws DbException
     * @author xaboy
     * @day 2020-04-24
     */
    public function keyUpdate(string $key, $data)
    {
        if (isset($data['result']))
            $data['result'] = json_encode($data['result'], JSON_UNESCAPED_UNICODE);
        Cache::getDB()->where('key', $key)->update($data);
    }

    public function search(array $keys)
    {
        $cache = $this->getModel()::getDB()->whereIn('key',$keys)->column('result','key');
        $ret = [];

        foreach ($cache as $k => $v) {
            $ret[$k] = json_decode($v);
        }
        return $ret;
    }


}
