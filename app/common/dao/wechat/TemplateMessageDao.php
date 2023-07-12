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


namespace app\common\dao\wechat;

use app\common\dao\BaseDao;
use app\common\model\wechat\TemplateMessage;

class TemplateMessageDao extends BaseDao
{
    protected function getModel(): string
    {
        return TemplateMessage::class;
    }


    public function search(array $where)
    {
        return ($this->getModel()::getDB())->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            $query->where('status', $where['status']);
        })->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('type', $where['type']);
        })->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->where(function($query)use($where) {
                $query->where('name', 'like', '%' . $where['keyword'] . '%');
                $query->whereOr('tempid', 'like', '%' . $where['keyword'] . '%');
            });
        })->order('create_time DESC');
    }

    public function getTempId($key, $type)
    {
        return TemplateMessage::getDB()->where(['type' => $type, 'tempkey' => $key])->value('tempid');
    }
}
