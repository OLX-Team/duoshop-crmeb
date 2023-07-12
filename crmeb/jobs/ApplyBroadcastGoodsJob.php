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


namespace crmeb\jobs;


use app\common\repositories\store\broadcast\BroadcastGoodsRepository;
use crmeb\interfaces\JobInterface;
use crmeb\services\YunxinSmsService;
use think\facade\Log;

class ApplyBroadcastGoodsJob implements JobInterface
{

    public function fire($job, $goodsId)
    {
        $broadcastRoomGoodsRepository = app()->make(BroadcastGoodsRepository::class);
        $goods = $broadcastRoomGoodsRepository->get($goodsId);
        if ($goods) {
            try {
                $res = $broadcastRoomGoodsRepository->wxCreate($goods);
            } catch (\Exception $e) {
                $goods->error_msg = $e->getMessage();
                $goods->status = -1;
            }
            if (isset($res)) {
                $goods->goods_id = $res->goodsId;
                $goods->audit_id = $res->auditId;
                $goods->status = 1;
            }
            $goods->save();
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
