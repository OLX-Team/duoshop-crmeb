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


namespace app\common\model\system\config;


use app\common\model\BaseModel;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * Class SystemConfigClassify
 * @package app\common\model\system\config
 * @author xaboy
 * @day 2020-03-30
 */
class SystemConfigClassify extends BaseModel
{
    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'config_classify_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'system_config_classify';
    }


    /**
     * @return HasOne
     * @author xaboy
     * @day 2020-03-30
     */
    public function parent()
    {
        return $this->hasOne(self::class, 'config_classify_id', 'pid');
    }

    /**
     * @return HasMany
     * @author xaboy
     * @day 2020-03-30
     */
    public function children()
    {
        return $this->hasMany(self::class, 'pid', 'config_classify_id');
    }

    /**
     * @return HasMany
     * @author xaboy
     * @day 2020-03-30
     */
    public function config()
    {
        return $this->hasMany(SystemConfig::class, 'classify_id', 'config_classify_id');
    }
}
