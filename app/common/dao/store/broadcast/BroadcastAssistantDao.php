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
namespace app\common\dao\store\broadcast;

use app\common\dao\BaseDao;
use app\common\model\store\broadcast\BroadcastAssistant;
use think\exception\ValidateException;

class BroadcastAssistantDao extends BaseDao
{

    protected function getModel(): string
    {
        return BroadcastAssistant::class;
    }

    public function merExists(int $id, int $merId)
    {
        return $this->existsWhere([$this->getPk() => $id, 'is_del' => 0, 'mer_id' => $merId]);
    }

    public function intersection(?string $ids, int $merId)
    {
        if (!$ids)  return [0];
        return $this->getModel()::getDb()->whereIn('assistant_id',$ids)->where('mer_id', $merId)->column('assistant_id');
    }

    public function existsAll($ids, $merId)
    {
        foreach ($ids as $id) {
            $has = $this->getModel()::getDb()->where('assistant_id',$id)->where('mer_id',$merId)->count();
            if (!$has) throw new ValidateException('ID:'.$id.' 不存在');
        }

        return true;
    }
}
