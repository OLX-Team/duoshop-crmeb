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

class StorePrinter extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'printer_id';
    }

    public static function tableName(): string
    {
        return 'store_printer';
    }


    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }

    public function searchKeywordAttr($query,$value)
    {
        $query->whereLike('printer_name|printer_terminal',"%{$value}%");
    }


}
