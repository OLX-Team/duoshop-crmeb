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


namespace app\common\model\system\groupData;


use app\common\model\BaseModel;

/**
 * Class SystemGroup
 * @package app\common\model\system\groupData
 * @author xaboy
 * @day 2020-03-30
 */
class SystemGroup extends BaseModel
{
    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'group_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'system_group';
    }

    /**
     * @param $value
     * @return mixed
     * @author xaboy
     * @day 2020-03-30
     */
    public function getFieldsAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param $value
     * @return false|string
     * @author xaboy
     * @day 2020-03-30
     */
    public function setFieldsAttr($value)
    {
        return json_encode($value);
    }
}
