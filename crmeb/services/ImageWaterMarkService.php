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


use think\exception\ValidateException;

class ImageWaterMarkService
{
    public function run($in_img, $water_text = '商城入驻专用其它无效', $font_size = 30, $water_w = 300, $water_h = 450, $angle = -45)
    {
        if (!is_file($in_img))
            throw new ValidateException('图片不存在');
        $font = public_path() . 'font/simsunb.ttf';
        $info = getimagesize($in_img);
        //通过编号获取图像类型
        $type = image_type_to_extension($info[2], false);
        //在内存中创建和图像类型一样的图像
        $fun = "imagecreatefrom" . $type;
        //图片复制到内存
        $image = $fun($in_img);
        //设置字体颜色和透明度
        $color = imagecolorallocatealpha($image, 190, 190, 190, 0.3);
        $x_length = $info[0];
        $y_length = $info[1];
        //铺满屏幕
        for ($x = 10; $x < $x_length; $x) {
            for ($y = 20; $y < $y_length; $y) {
                imagettftext($image, $font_size, $angle, $x, $y, $color, $font, $water_text);
                $y += $water_h;
            }
            $x += $water_w;
        }
        //浏览器输出 保存图片的时候 需要去掉
        //header("Content-type:".$info['mime']);
        $fun = "image" . $type;
//            $fun($image);
        //保存图片
        $fun($image, $in_img);
        //销毁图片
        imagedestroy($image);
    }
}
