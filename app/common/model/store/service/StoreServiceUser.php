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


namespace app\common\model\store\service;


use app\common\model\BaseModel;
use app\common\model\system\Extend;
use app\common\model\system\merchant\Merchant;
use app\common\model\user\User;
use app\common\repositories\system\ExtendRepository;

class StoreServiceUser extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'service_user_id';
    }

    public static function tableName(): string
    {
        return 'store_service_user';
    }

    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }

    public function service()
    {
        return $this->hasOne(StoreService::class, 'service_id', 'service_id');
    }

    public function last()
    {
        return $this->hasOne(StoreServiceLog::class, 'service_log_id', 'last_log_id');
    }

    public function mark()
    {
        return $this->hasOne(Extend::class, 'link_id', 'uid')->where('extend_type', ExtendRepository::TYPE_SERVICE_USER_MARK);
    }

}
