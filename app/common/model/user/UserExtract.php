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

namespace app\common\model\user;

use app\common\model\BaseModel;

class UserExtract extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'extract_id';
    }

    public static function tableName(): string
    {
        return 'user_extract';
    }

    public function user()
    {
        return $this->hasOne(User::class,'uid','uid');
    }

    public function searchUidAttr($query, $value)
    {
        $query->where('uid',$value);
    }

    public function searchExtractTypeAttr($query, $value)
    {
        $query->where('extract_type',$value);
    }


}
