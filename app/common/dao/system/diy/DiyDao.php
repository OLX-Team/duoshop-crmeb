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


namespace app\common\dao\system\diy;

use app\common\dao\BaseDao;
use app\common\model\system\diy\Diy;
use think\facade\Db;

class DiyDao extends BaseDao
{

    protected function getModel(): string
    {
        return Diy::class;
    }

    public function setUsed($id, $merId)
    {
        $res  = $this->getModel()::getDb()->find($id);
        $this->getModel()::getDb()->where('mer_id', $merId)->where('is_default' ,0)->update(['status'=>0]);
        if (!$res['is_default']) {
            $this->getModel()::getDb()->where('mer_id', $merId)->where('id',$id)->update(['status'=> 1]);
        }
    }
    public function merExists(int $merId, int $id)
    {
        return ($this->getModel()::getDb()->where('mer_id', $merId)->where($this->getPk(), $id)->count() > 0 );
    }

}
