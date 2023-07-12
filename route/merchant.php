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


use app\common\middleware\AllowOriginMiddleware;
use app\common\middleware\CheckSiteOpenMiddleware;
use app\common\middleware\InstallMiddleware;
use think\facade\Route;
use app\common\middleware\RequestLockMiddleware;

Route::group(config('admin.merchant_prefix'), function () {
    Route::miss(function () {
        $DB = DIRECTORY_SEPARATOR;
        return view(app()->getRootPath() . 'public' . $DB . 'mer.html');
    });
})->middleware(InstallMiddleware::class)
    ->middleware(CheckSiteOpenMiddleware::class);

Route::group(config('admin.api_merchant_prefix') . '/', function () {
    $path = $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR.'merchant';
    $files = scandir($path);
    foreach ($files as $file) {
        if($file != '.' && $file != '..'){
            include $path . DIRECTORY_SEPARATOR . $file;
        }
    }
    Route::miss(function () {
        return app('json')->fail('接口不存在');
    })->middleware(AllowOriginMiddleware::class);
})
    ->option([
        '_lock' => true
    ])
    ->middleware(InstallMiddleware::class)
    ->middleware(CheckSiteOpenMiddleware::class)
    ->middleware(RequestLockMiddleware::class);
