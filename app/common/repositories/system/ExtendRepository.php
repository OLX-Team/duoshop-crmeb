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


namespace app\common\repositories\system;


use app\common\dao\system\ExtendDao;
use app\common\repositories\BaseRepository;

/**
 * Class ExtendRepository
 * @package app\common\repositories\system
 * @author xaboy
 * @day 2020-04-24
 * @mixin ExtendDao
 */
class ExtendRepository extends BaseRepository
{

    const TYPE_SERVICE_USER_MARK = 'service_user_mark';

    /**
     * CacheRepository constructor.
     * @param ExtendDao $dao
     */
    public function __construct(ExtendDao $dao)
    {
        $this->dao = $dao;
    }

    public function updateInfo($extend_type, $link_id, $mer_id, $extend_value)
    {
        $data = compact('extend_type', 'link_id', 'mer_id');
        $extend = $this->getWhere($data);
        if ($extend) {
            $extend->extend_value = $extend_value;
            $extend->save();
        } else {
            $data['extend_value'] = $extend_value;
            $extend = $this->dao->create($data);
        }
        return $extend;
    }
}
