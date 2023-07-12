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


namespace app\common\model\system;


use app\common\model\BaseModel;

/**
 * Class Cache
 * @package app\common\model\system
 * @author xaboy
 * @day 2020-04-24
 */
class Cache extends BaseModel
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'key';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'cache';
    }

    /**
     * @param $val
     * @return false|string
     * @author xaboy
     * @day 2020-04-24
     */
    public function setResultAttr($val)
    {
        return json_encode($val);
    }

    /**
     * @param string $val
     * @return mixed
     * @author xaboy
     * @day 2020-04-24
     */
    public function getResultAttr($val)
    {
        return json_decode($val, true);
    }

}
