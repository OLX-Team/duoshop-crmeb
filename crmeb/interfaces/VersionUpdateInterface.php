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


namespace crmeb\interfaces;


use think\console\Input;
use think\console\Output;

interface VersionUpdateInterface
{
    public function __construct(Input $input, Output $output);

    public function autoUpdateStart();

    public function autoUpdateBefore();

    public function autoSqlBefore();

    public function autoSqlAfter();

    public function autoCopyBefore();

    public function autoCopyAfter();

    public function autoUpdateAfter();

    public function autoUpdateEnd();

    public function autoUpdateFail(\Throwable $e);
}
