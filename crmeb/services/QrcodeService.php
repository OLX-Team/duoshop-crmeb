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


use app\common\repositories\system\attachment\AttachmentRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\wechat\RoutineQrcodeRepository;
use Endroid\QrCode\QrCode;
use think\facade\Config;

class QrcodeService
{

    /**
     * 获取二维码
     * @param $url
     * @param $name
     * @return array|bool|string
     */
    public function getQRCodePath($url, $name)
    {
        if (!strlen(trim($url)) || !strlen(trim($name))) return false;
        try {
            $uploadType = systemConfig('upload_type');
            //TODO 没有选择默认使用本地上传
            if (!$uploadType) $uploadType = 1;
            $uploadType = (int)$uploadType;
            $siteUrl = systemConfig('site_url');
            if (!$siteUrl) return '请前往后台设置->系统设置->网站域名 填写您的域名格式为：http://域名';
            $info = [];
            $outfile = Config::get('qrcode.cache_dir');
            $code = new QrCode($url);
            if ($uploadType === 1) {
                if (!is_dir('./public/' . $outfile))
                    mkdir('./public/' . $outfile, 0777, true);
                $code->writeFile('./public/' . $outfile . '/' . $name);
                $info["code"] = 200;
                $info["name"] = $name;
                $info["dir"] = rtrim($siteUrl, '/') . '/' . $outfile . '/' . $name;
                $info["time"] = time();
                $info['size'] = 0;
                $info['type'] = 'image/png';
                $info["image_type"] = 1;
                $info['thumb_path'] = $info["dir"];
                return $info;
            } else {
                $upload = UploadService::create($uploadType);
                $res = $upload->to('/public/' . $outfile)->validate()->stream($code->writeString(), $name);
                if ($res === false) {
                    return $upload->getError();
                }
                $info = $upload->getUploadInfo();
                $info['image_type'] = $uploadType;
                return $info;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * 获取二维码完整路径，不存在则自动生成
     * @param string $name 路径名
     * @param string $link 需要生成二维码的跳转路径
     * @param bool $force 是否返回false
     * @return bool|mixed|string
     */
    public function getWechatQrcodePath(string $name, string $link, bool $force = false, $key = '')
    {
        try {
            $imageInfo = app()->make(AttachmentRepository::class)->getWhere(['attachment_name' => $name]);
            if (!$imageInfo) {
                $siteUrl = systemConfig('site_url');
                $codeUrl = tidy_url($link, null, $siteUrl);

                if ($key && systemConfig('open_wechat_share')) {
                    $qrcode = WechatService::create(false)->qrcodeService();
                    $codeUrl = $qrcode->forever('_scan_url_' . $key)->url;
                }

                $imageInfo = $this->getQRCodePath($codeUrl, $name);
                if (is_string($imageInfo) && $force)
                    return false;
                if (is_array($imageInfo)) {
                    $imageInfo['dir'] = tidy_url($imageInfo['dir'], null, $siteUrl);
                    app()->make(AttachmentRepository::class)->create($imageInfo['image_type'], -1, 0, [
                        'attachment_category_id' => 0,
                        'attachment_name' => $imageInfo['name'],
                        'attachment_src' => $imageInfo['dir']
                    ]);
                    $url = $imageInfo['dir'];
                } else {
                    $url = '';
                }
            } else $url = $imageInfo['attachment_src'];
            return $url;
        } catch (\Throwable $e) {
            if ($force)
                return false;
            else
                return '';
        }
    }

    /**
     * 获取小程序分享二维码
     * @param int $id
     * @param int $uid
     * @param int $type 1 = 拼团,2 = 秒杀
     * @param array $parame
     * @return bool|string
     */
    public function hotQrcodePath(int $id, int $uid, int $type, array $parame = [])
    {
        $page = '';
        $namePath = '';
        $data = 'id=' . $id . '&spid=' . $uid;
        switch ($type) {
            case 1:
                $page = 'pages/activity/goods_combination_details/index';
                $namePath = 'combination_' . $id . '_' . $uid . '.jpg';
                break;
            case 2:
                $page = 'pages/activity/goods_seckill_details/index';
                $namePath = 'seckill_' . $id . '_' . $uid . '.jpg';
                if (isset($parame['stop_time']) && $parame['stop_time']) {
                    $data .= '&time=' . $parame['stop_time'];
                    $namePath = $parame['stop_time'] . $namePath;
                }
                break;
        }
        if (!$page || !$namePath) {
            return false;
        }

        return $this->getRoutineQrcodePath($namePath, $page, $data);
    }

    /**
     * @param $namePath
     * @param $page
     * @param $data
     * @return bool|int|mixed|string
     * @author xaboy
     * @day 2020/6/18
     */
    public function getRoutineQrcodePath($namePath, $page, $data)
    {

        try {
            $imageInfo = app()->make(AttachmentRepository::class)->getWhere(['attachment_name' => $namePath]);
            if (!$imageInfo) {
                $res = app()->make(RoutineQrcodeRepository::class)->getPageCode($page, $data, 280);
                if (!$res) return false;
                $uploadType = (int)systemConfig('upload_type') ?: 1;
                $upload = UploadService::create($uploadType);
                $res = $upload->to('routine/product')->validate()->stream($res, $namePath);
                if ($res === false) {
                    return false;
                }
                $imageInfo = $upload->getUploadInfo();
                $imageInfo['image_type'] = $uploadType;
                $imageInfo['dir'] = tidy_url($imageInfo['dir'], 0);
                $remoteImage = remoteImage($imageInfo['dir']);
                if (!$remoteImage['status']) return false;
                app()->make(AttachmentRepository::class)->create($uploadType, -1, 0, [
                    'attachment_category_id' => 0,
                    'attachment_name' => $imageInfo['name'],
                    'attachment_src' => $imageInfo['dir']
                ]);
                $url = $imageInfo['dir'];
            } else $url = $imageInfo['attachment_src'];
            return $url;
        } catch (\Throwable $e) {
            return false;
        }
    }

}
