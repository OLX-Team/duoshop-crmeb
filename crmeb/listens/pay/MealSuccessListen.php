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


namespace crmeb\listens\pay;

use app\common\repositories\system\serve\ServeOrderRepository;
use crmeb\interfaces\ListenerInterface;

class MealSuccessListen implements ListenerInterface
{

    public function handle($data): void
    {
        app()->make(ServeOrderRepository::class)->paySuccess($data);
    }
}
