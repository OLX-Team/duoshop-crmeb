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
namespace app\common\model\store\broadcast;

use app\common\model\BaseModel;
use app\common\model\system\merchant\Merchant;

class BroadcastAssistant extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'assistant_id';
    }

    public static function tableName(): string
    {
        return 'broadcast_assistant';
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }

    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }

    public function searchUsernameAttr($query,$value)
    {
        $query->whereLike('username',"%{$value}%");
    }

    public function searchNicknameAttr($query,$value)
    {
        $query->whereLike('nickname',"%{$value}%");
    }

    public function searchAssistantIdsAttr($query,$value)
    {
        $query->whereIn('assistant_id', $value);
    }

    public function searchAssistantIdAttr($query,$value)
    {
        $query->where('assistant_id',$value);
    }
}
