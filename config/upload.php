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

// +----------------------------------------------------------------------
// | 上传配置
// +----------------------------------------------------------------------

return [
    //默认上传模式
    'default' => 'local',
    //上传文件大小
    'filesize' => 52428800,
    //上传文件后缀类型
    'fileExt' => ['jpg', 'jpeg', 'png', 'gif','webp', 'pem', 'mp3', 'wma', 'wav', 'amr', 'mp4', 'key','xlsx','xls','ico'],
    //上传文件类型
    'fileMime' => ['image/jpeg', 'image/gif', 'image/png','image/webp', 'text/plain', 'audio/mpeg', 'image/vnd.microsoft.icon'],
    //驱动模式
    'stores' => [
        //本地上传配置
        'local' => [],
        //七牛云上传配置
        'qiniu' => [],
        //oss上传配置
        'oss' => [],
        //cos上传配置
        'cos' => [],
        //obs华为储存
        'obs' => [],
        //ucloud存储
        'us3' => [],
        //jd
        'jdoss' => [],
        //天翼云
        'ctoss' => [],
    ],
    'iamge_fileExt' => ['jpg', 'jpeg', 'png', 'gif','webp'],
    //上传文件类型
    'image_fileMime' => ['image/jpeg', 'image/gif', 'image/png','image/webp'],
];
