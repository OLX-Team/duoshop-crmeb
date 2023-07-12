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

class UserReceipt extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'user_receipt_id';
    }

    public static function tableName(): string
    {
        return 'user_receipt';
    }



    /*
     ------------------------------------------------------------------------------------------------------------
     |
     | 搜索器
     ------------------------------------------------------------------------------------------------------------
     */
    public function searchUidAttr($query,$value)
    {
        $query->where('uid',$value);
    }

    public function searchIsDelAttr($query,$value)
    {
        $query->where('is_del',$value);
    }

    public function searchReceiptTypeAttr($query,$value)
    {
        $query->where('receipt_type',$value);
    }

    public function searchReceiptTitleTypeAttr($query,$value)
    {
        $query->where('receipt_title_type',$value);
    }

    public function searchIsDefaultAttr($query,$value)
    {
        $query->where('is_default',$value);
    }

    public function searchUserReceiptIdAttr($query,$value)
    {
        $query->where('user_receipt_id',$value);
    }
}
