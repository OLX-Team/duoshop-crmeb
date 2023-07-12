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
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => env('INSTALLED', false) ? env('cache.driver', 'redis') : 'file',

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        'redis' => [
            // 驱动方式
            'type'       => 'Redis',
            // 服务器地址
            'host'       => env('redis.redis_hostname', '127.0.0.1'),
            // 端口
            'port'       => env('redis.port', '6379'),
            // 密码
            'password'   => env('redis.redis_password', ''),
            // 数据库 0号数据库
            'select'     => (int)env('redis.select', 0),
        ],
        // 更多的缓存连接
    ],

    //缓存key名前缀
    'crmeb_key' => [
        'category'   => 'category_',
        'product'    => 'product_',
        'brand'      => 'brand_',
        'community'  => 'community_',
        'topic'      => 'topic_',
        'system'     => 'system_',
        'group_data' => 'group_data_',
    ],
];
