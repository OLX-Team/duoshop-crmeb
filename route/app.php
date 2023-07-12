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

use app\common\middleware\CheckSiteOpenMiddleware;
use app\common\middleware\InstallMiddleware;
use think\facade\Route;

Route::any('api/wechat/serve', 'WechatNotice/serve');

Route::get('install','Install/begin');
Route::group('install',function(){
   route::get('environment','/environment') ;
   route::get('databases','/databases') ;
   route::post('databases/create','/create') ;
   route::post('databases/check','/databasesCheck') ;
   route::post('perform/:n','/perform') ;
   route::get('end','/end') ;
   route::get('loader','/swooleCompiler') ;
})->prefix('Install');

Route::group(config('admin.service_prefix'), function () {
    Route::miss(function () {
        $DB = DIRECTORY_SEPARATOR;
        return view(app()->getRootPath() . 'public' . $DB . 'kefu.html');
    });
})->middleware(InstallMiddleware::class)
    ->middleware(CheckSiteOpenMiddleware::class);
