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


namespace app\common\model\store;


use app\common\model\BaseModel;

/**
 * Class StoreAttrTemplate
 * @package app\common\model\store
 * @author xaboy
 * @day 2020-05-06
 */
class StoreAttrTemplate extends BaseModel
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'attr_template_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'store_attr_template';
    }

    /**
     * @param $val
     * @return mixed
     * @author xaboy
     * @day 2020-05-06
     */
    public function getTemplateValueAttr($val)
    {
        return json_decode($val, true);
    }

    /**
     * @param $val
     * @return false|string
     * @author xaboy
     * @day 2020-05-06
     */
    public function setTemplateValueAttr($val)
    {
        return json_encode($val);
    }
}
