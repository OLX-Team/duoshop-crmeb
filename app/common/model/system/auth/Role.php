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


namespace app\common\model\system\auth;

use app\common\model\BaseModel;

class Role extends BaseModel
{
    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'role_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'system_role';
    }

    public function ruleNames($isArray = false)
    {
        $menusName = Menu::getDB()->whereIn('menu_id', $this->rules)->column('menu_name');
        return $isArray ? $menusName : implode(',', $menusName);
    }


    /**
     * @param $value
     * @return array
     * @author xaboy
     * @day 2020-03-30
     */
    public function getRulesAttr($value)
    {
        return array_map('intval', explode(',', $value));
    }

    /**
     * @param $value
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public function setRulesAttr($value)
    {
        return implode(',', $value);
    }
}
