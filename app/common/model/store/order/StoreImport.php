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


namespace app\common\model\store\order;

use app\common\model\BaseModel;

class StoreImport extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'import_id';
    }

    public static function tableName(): string
    {
        return 'store_import';
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }

    public function searchDateAttr($query,$value)
    {
        getModelTime($query,$value);
    }

    public function searchImportTypeAttr($query,$value)
    {
        $query->where('import_type',$value);
    }

    public function searchTypeAttr($query,$value)
    {
        $query->where('type',$value);
    }

}
