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


namespace crmeb\services\upload\storage;

use crmeb\basic\BaseUpload;
use crmeb\exceptions\UploadException;
use crmeb\services\DownloadImageService;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\Filesystem;
use think\File;
use think\Image;

/**
 * 本地上传
 * Class Local
 * @package crmeb\services\upload\storage
 */
class Local extends BaseUpload
{

    /**
     * 默认存放路径
     * @var string
     */
    protected $defaultPath;
    protected $waterConfig;
    protected $thumbConofig;
    /**
     *  缩略图开关
     * @var mixed|null
     */
    protected $thumb_status;

    /**
     * 缩略图比例
     * @var mixed|null
     */
    protected $thumb_rate;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->defaultPath = Config::get('filesystem.disks.' . Config::get('filesystem.default') . '.url');
        $this->waterConfig = $config['water'] ?? [];
        $this->thumb_status = $config['thumb_status'];
        $this->thumb_rate = $config['thumb_rate'];
    }

    protected function app()
    {
        // TODO: Implement app() method.
    }

    public function getTempKeys()
    {
        return [
            'type' => 'local'
        ];
    }

    /**
     * 生成上传文件目录
     * @param $path
     * @param null $root
     * @return string
     */
    protected function uploadDir($path, $root = null)
    {
        if ($root === null) $root = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
        return str_replace('\\', '/', $root . 'uploads' . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * 检查上传目录不存在则生成
     * @param $dir
     * @return bool
     */
    protected function validDir($dir)
    {
        return is_dir($dir) == true || mkdir($dir, 0777, true) == true;
    }

    /**
     * 文件上传
     * @param string $file
     * @return array|bool|mixed|\StdClass
     */
    public function move(string $file = 'file', $thumb = true)
    {
        $fileHandle = app()->request->file($file);
        if (!$fileHandle) {
            return $this->setError('Upload file does not exist');
        }
        if ($this->validate) {
            try {
                validate([$file => $this->validate])->check([$file => $fileHandle]);
            } catch (ValidateException $e) {
                return $this->setError($e->getMessage());
            }
        }
        $fileName = Filesystem::putFile($this->path, $fileHandle);
        if (!$fileName) return $this->setError('Upload failure');
        $filePath = Filesystem::path($fileName);
        $this->fileInfo->uploadInfo = new File($filePath);
        $this->fileInfo->fileName = $this->fileInfo->uploadInfo->getFilename();
        $this->fileInfo->filePath = $this->defaultPath . '/' . str_replace('\\', '/', $fileName);
        if ($thumb) $this->thumb($this->fileInfo->filePath);
        return $this->fileInfo;
    }

    /**
     * 文件流上传
     * @param string $fileContent
     * @param string|null $key
     * @return array|bool|mixed|\StdClass
     */
    public function stream(string $fileContent, string $key = null)
    {
        if (!$key) {
            $key = $this->saveFileName();
        }
        $dir = $this->uploadDir($this->path);
        if (!$this->validDir($dir)) {
            return $this->setError('Failed to generate upload directory, please check the permission!');
        }
        $fileName = $dir . '/' . $key;
        file_put_contents($fileName, $fileContent);
        $this->fileInfo->uploadInfo = new File($fileName);
        $this->fileInfo->fileName = $key;
        $this->fileInfo->filePath = $this->defaultPath . '/' . $this->path . '/' . $key;
        return $this->fileInfo;
    }

    /**
     * 删除文件
     * @param string $filePath
     * @return bool|mixed
     */
    public function delete(string $filePath)
    {
        if (file_exists($filePath)) {
            try {
                unlink($filePath);
                return true;
            } catch (UploadException $e) {
                return $this->setError($e->getMessage());
            }
        }
        return false;
    }

    /**
     * 生成缩略图
     * @param string $filePath
     * @param string $type
     * @return array|mixed|string[]
     */
    public function thumb(string $filePath = '', $savePath = null)
    {

        if ($this->thumb_status && $filePath) {
            $filePath = $this->getFilePath($filePath, true);
            $savePath = $savePath ?: $filePath;
            //地址存在且不是远程地址
            if ($filePath && !$this->checkFilePathIsRemote($filePath)) {
                try {
                    $Image = Image::open(root_path() . 'public' . $filePath);
                    if (in_array($Image->type(),config('upload.iamge_fileExt'))){
                        $Image->thumb(
                            $Image->width() * ($this->thumb_rate ?: 0.8),
                            $Image->height() * ($this->thumb_rate ?: 0.8)
                        )->save(root_path() . 'public' . $savePath);
                    }
                } catch (\Throwable $e) {
                    if ($e->getMessage() == 'Illegal image file') {
                        return;
                    }
                    throw new ValidateException($e->getMessage());
                }
            }
            return $savePath;
        }
    }

    public function water(string $filePath = '')
    {
        if (!$this->waterConfig['image_watermark_status']) return $filePath;
        $filePath = $this->getFilePath($filePath, true);
        //地址存在且不是远程地址
        $waterPath = $filePath;
        if ($filePath && !$this->checkFilePathIsRemote($filePath)) {
            switch ($this->waterConfig['watermark_type']) {
                case 1:
                    if ($this->waterConfig['watermark_image'])
                        $waterPath = $this->image($filePath, $this->waterConfig, $waterPath);
                    break;
                case 2:
                    $waterPath = $this->text($filePath, $this->waterConfig, $waterPath);
                    break;
            }
        }
        return $waterPath;
    }

    /**
     * TODO
     * @param string $filePath
     * @return bool
     * @author Qinii
     * @day 12/14/21s
     */
    public function checkFilePathIsRemote(string $filePath)
    {
        return strpos($filePath, 'https:') !== false || strpos($filePath, 'http:') !== false || substr($filePath, 0, 2) === '//';
    }

    /**
     * 生成与配置相关的文件名称以及路径
     * @param string $filePath 原地址
     * @param string $toPath 保存目录
     * @param array $config 配置相关参数
     * @param string $root
     * @return string
     */
    public function createSaveFilePath(string $filePath, string $toPath, array $config = [], $root = '/')
    {
        [$path, $ext] = $this->getFileName($filePath);
        $fileName = md5(json_encode($config) . $filePath);
        $this->validDir($toPath);
        return $this->uploadDir($toPath, $root) . '/' . $fileName . '.' . $ext;
    }

    /**
     * 提取文件后缀以及之前部分
     * @param string $path
     * @return false|string[]
     */
    protected function getFileName(string $path)
    {
        $_empty = ['', ''];
        if (!$path) return $_empty;
        if (strpos($path, '?')) {
            $_tarr = explode('?', $path);
            $path = trim($_tarr[0]);
        }
        $arr = explode('.', $path);
        if (!is_array($arr) || count($arr) <= 1) return $_empty;
        $ext_name = trim($arr[count($arr) - 1]);
        $ext_name = !$ext_name ? 'jpg' : $ext_name;
        return [explode('.' . $ext_name, $path)[0], $ext_name];
    }

    /**
     * 获取图片地址
     * @param string $filePath
     * @param bool $is_parse_url
     * @return string
     */
    protected function getFilePath(string $filePath = '', bool $is_parse_url = false)
    {
        $path = $filePath ? $filePath : $this->path;
        if ($is_parse_url) {
            $data = parse_url($path);
            //远程地址处理
            if (isset($data['host']) && isset($data['path'])) {
                if (file_exists(app()->getRootPath() . 'public' . $data['path'])) {
                    $path = $data['path'];
                }
            }
        }
        return $path;
    }

    /**
     * 图片水印
     * @param string $filePath
     * @param array $waterConfig
     * @param string $waterPath
     * @return string
     */
    public function image(string $filePath, array $waterConfig = [], string $waterPath = '')
    {
        if (!$waterConfig) $waterConfig = $this->waterConfig;
        $watermark_image = $waterConfig['watermark_image'];

        //远程图片
        if ($watermark_image && $this->checkFilePathIsRemote($watermark_image)) {
            //看是否在本地
            $pathName = $this->getFilePath($watermark_image, true);

            if ($pathName == $watermark_image) { //不再本地  继续下载
                [$p, $e] = $this->getFileName($watermark_image);
                $name = 'water_image_' . md5($watermark_image) . '.' . $e;
                $watermark_image = '.' . $this->defaultPath . '/' . $this->path . '/' . $name;
                if (!file_exists($watermark_image)) {
                    try {
                        /** @var DownloadImageService $down */
                        $down = app()->make(DownloadImageService::class);
                        $data = $down->path($this->path)->downloadImage($waterConfig['watermark_image'], $name);
                        $watermark_image = $data['path'] ?? '';
                    } catch (\Throwable $e) {
                        throw new ValidateException('远程水印图片下载失败，原因：' . $e->getMessage());
                    }
                }
            } else {
                $watermark_image = '.' . $pathName;
            }
        }

        if (!$watermark_image) {
            throw new ValidateException('请先配置水印图片');
        }

        if (!$waterPath) {
            [$path, $ext] = $this->getFileName($filePath);
            $waterPath = $path . '_water_image.' . $ext;
        }
        try {
            $Image = Image::open(app()->getRootPath() . 'public' . $filePath);
            $Image->water(
                app()->getRootPath() . 'public' . $watermark_image,
                $waterConfig['watermark_position'] ?: 1,
                $waterConfig['watermark_opacity']
            )->save(root_path() . 'public' . $waterPath);
        } catch (\Throwable $e) {
            throw new ValidateException($e->getMessage());
        }
        return $waterPath;
    }

    /**
     * 文字水印
     * @param string $filePath
     * @param array $waterConfig
     * @param string $waterPath
     * @return string
     */
    public function text(string $filePath, array $waterConfig = [], string $waterPath = '')
    {
        if (!$waterConfig) {
            $waterConfig = $this->waterConfig;
        }
        if (!$waterConfig['watermark_text']) {
            throw new ValidateException('请先配置水印文字');
        }
        if (!$waterPath) {
            [$path, $ext] = $this->getFileName($filePath);
            $waterPath = $path . '_water_text.' . $ext;
        }
        try {
            if (!file_exists(root_path() . 'public' . $waterPath)) {
                $Image = Image::open(app()->getRootPath() . 'public' . $filePath);
                if (strlen($waterConfig['watermark_text_color']) < 7) {
                    $waterConfig['watermark_text_color'] = substr($waterConfig['watermark_text_color'], 1);
                    $waterConfig['watermark_text_color'] = '#' . $waterConfig['watermark_text_color'] . $waterConfig['watermark_text_color'];
                }
                if (strlen($waterConfig['watermark_text_color']) > 7) {
                    $waterConfig['watermark_text_color'] = substr($waterConfig['watermark_text_color'], 0, 7);
                }
                $Image->text($waterConfig['watermark_text'], public_path() . 'font/simsunb.ttf', $waterConfig['watermark_text_size'], $waterConfig['watermark_text_color'], $waterConfig['watermark_position'], [$waterConfig['watermark_x'], $waterConfig['watermark_y'], $waterConfig['watermark_text_angle']])->save(root_path() . 'public' . $waterPath);
            }
        } catch (\Throwable $e) {
            throw new ValidateException($e->getMessage() . $e->getLine());
        }
        return $waterPath;
    }
}
