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


namespace crmeb\services;

use crmeb\services\upload\Upload;

/**
 * Class UploadService
 * @package crmeb\services
 */
class UploadService
{

    /**
     * @param $type
     * @return Upload
     */
    public static function create($type = null)
    {
        $type = $type ?: (int)systemConfig('upload_type');
        switch ($type) {
            case 2://七牛
                $prefix = 'qiniu';
                break;
            case 3:// oss 阿里云
                break;
            case 4:// cos 腾讯云
                $prefix = 'tengxun';
                break;
            case 5:
                $prefix = 'obs';
                break;
            case 6:
                $prefix = 'uc';
                break;
            case 7:
                $prefix = 'jdoss';
                break;
            case 8:
                $prefix = 'ctoss';
                break;
            default: //本地
                $prefix = 'local';
                break;

        }
        //获取配置
        //accessKey
        $accessKey      = isset($prefix) ? $prefix.'_accessKey'      : 'accessKey';
        //secretKey
        $secretKey      = isset($prefix) ? $prefix.'_secretKey'      : 'secretKey';
        //空间域名 Domain
        $auploadUrl     = isset($prefix) ? $prefix.'_uploadUrl'      : 'uploadUrl';
        //存储空间名称
        $storage_name   = isset($prefix) ? $prefix.'_storage_name'   : 'storage_name';
        //所属地域
        $storage_region = isset($prefix) ? $prefix.'_storage_region' : 'storage_region';

        $cdn            = isset($prefix) ? $prefix.'_cdn'            : 'oss_cdn';
        $thumb_status   = isset($prefix) ? $prefix.'_thumb_status'   : 'thumb_status';
        $thumb_rate     = isset($prefix) ? $prefix.'_thumb_rate'     : 'thumb_rate';

        $data = systemConfig([$accessKey, $secretKey, $auploadUrl, $storage_name, $storage_region, $cdn,$thumb_status,$thumb_rate]);
        if ($data[$cdn]) {
            if (substr( $data[$cdn],0,4)  !== 'http') {
                $data[$cdn] = 'https'.$data[$cdn];
            }
        }
        $config = [
            'accessKey' => $data[$accessKey],
            'secretKey' => $data[$secretKey],
            'uploadUrl' => $data[$auploadUrl],
            'storageName' => $data[$storage_name],
            'storageRegion' => $data[$storage_region],
            'cdn'   =>   rtrim($data[$cdn],'/'),
            'thumb_status' => $data[$thumb_status],
            'thumb_rate' => $data[$thumb_rate],
            'image_suffix' => ['jpg', 'jpeg', 'png', 'gif','webp']
        ];

        return new Upload($type, $config);
    }
}
