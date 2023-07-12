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
use Guzzle\Http\EntityBody;
use OSS\Core\OssException;
use OSS\OssClient;
use think\exception\ValidateException;
use think\Image;


/**
 * 阿里云OSS上传
 * Class OSS
 */
class Oss extends BaseUpload
{
    /**
     * accessKey
     * @var mixed
     */
    protected $accessKey;

    /**
     * secretKey
     * @var mixed
     */
    protected $secretKey;

    /**
     * 句柄
     * @var \OSS\OssClient
     */
    protected $handle;

    /**
     * 空间域名 Domain
     * @var mixed
     */
    protected $uploadUrl;

    /**
     * 存储空间名称  公开空间
     * @var mixed
     */
    protected $storageName;

    /**
     * COS使用  所属地域
     * @var mixed|null
     */
    protected $storageRegion;

    /**
     * cdn 域名
     * @var
     */
    protected $cdn;

    /**
     * 缩略图配置
     * @var
     */
    protected $thumbConfig;

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

    /**
     * 初始化
     * @param array $config
     * @return mixed|void
     */
    protected function initialize(array $config)
    {
        parent::initialize($config);
        $this->accessKey = $config['accessKey'] ?? null;
        $this->secretKey = $config['secretKey'] ?? null;
        $this->uploadUrl = tidy_url($this->checkUploadUrl($config['uploadUrl'] ?? ''));
        $this->storageName = $config['storageName'] ?? null;
        $this->storageRegion = $config['storageRegion'] ?? null;
        $this->cdn = $config['cdn'] ?? null;
        $this->thumb_status = $config['thumb_status'];
        $this->thumb_rate = $config['thumb_rate'];
    }

    /**
     * 初始化oss
     * @return OssClient
     * @throws OssException
     */
    protected function app()
    {
        if (!$this->accessKey || !$this->secretKey) {
            throw new UploadException('Please configure accessKey and secretKey');
        }
        $this->handle = new OssClient($this->accessKey, $this->secretKey, $this->storageRegion);
        if (!$this->handle->doesBucketExist($this->storageName)) {
            $this->handle->createBucket($this->storageName, OssClient::OSS_ACL_TYPE_PUBLIC_READ_WRITE);
        }
        return $this->handle;
    }

    /**
     * 上传文件
     * @param string $file
     * @return array|bool|mixed|\StdClass
     */
    public function move(string $file = 'file',$thumb = true)
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
        $key = $this->saveFileName($fileHandle->getRealPath(), $fileHandle->getOriginalExtension());
        $path = ($this->path ? trim($this->path , '/') . '/' : '');
        try {
            $uploadInfo = $this->app()->uploadFile($this->storageName, $path . $key, $fileHandle->getRealPath());
            if (!isset($uploadInfo['info']['url'])) {
                return $this->setError('Upload failure');
            }
            $src =  rtrim(($this->cdn ?: $this->uploadUrl)) . '/'.$path.$key;
            if ($thumb) $src = $this->thumb($src);
            $this->fileInfo->uploadInfo = $uploadInfo;
            $this->fileInfo->filePath = $src;
            $this->fileInfo->fileName = $key;
            return $this->fileInfo;
        } catch (UploadException $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 文件流上传
     * @param string $fileContent
     * @param string|null $key
     * @return bool|mixed
     */
    public function stream(string $fileContent, string $key = null, $thumb = true)
    {
        try {
            if (!$key) {
                $key = $this->saveFileName();
            }
            $path = ($this->path ? trim($this->path , '/') . '/' : '');
            $fileContent = (string)EntityBody::factory($fileContent);
            $uploadInfo = $this->app()->putObject($this->storageName, $path . $key, $fileContent);
            if (!isset($uploadInfo['info']['url'])) {
                return $this->setError('Upload failure');
            }
            $src = $uploadInfo['info']['url'];
            if ($thumb) $src = $this->thumb($src);
            $this->fileInfo->uploadInfo = $uploadInfo;
            $this->fileInfo->filePath = $src;
            $this->fileInfo->fileName = $key;
            return $this->fileInfo;
        } catch (UploadException $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 缩略图
     * @param string $filePath
     * @param string $type
     * @return mixed|string[]
     */
    public function thumb(string $key = '')
    {
        $suffix = explode('.',$key);
        if (!$suffix && !in_array($suffix[1],config('upload.iamge_fileExt'))) return $key;
        if ($this->thumb_status && $key) {
            $param = ('x-oss-process=image/resize' . urlencode(',') . 'p_' . $this->thumb_rate);
            $key = $key . '?' . $param;
        }
        return $key;
    }


    /**
     * 删除资源
     * @param $key
     * @return mixed
     */
    public function delete(string $key)
    {
        try {
            return $this->app()->deleteObject($this->storageName, $key);
        } catch (OssException $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 获取OSS上传密钥
     * @return mixed|void
     */
    public function getTempKeys($callbackUrl = '', $dir = '')
    {
        // TODO: Implement getTempKeys() method.
        $base64CallbackBody = base64_encode(json_encode([
            'callbackUrl' => $callbackUrl,
            'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => "application/x-www-form-urlencoded"
        ]));

        $policy = json_encode([
            'expiration' => $this->gmtIso8601(time() + 30),
            'conditions' =>
                [
                    [0 => 'content-length-range', 1 => 0, 2 => 1048576000],
                    [0 => 'starts-with', 1 => '$key', 2 => $dir]
                ]
        ]);
        $base64Policy = base64_encode($policy);
        $signature = base64_encode(hash_hmac('sha1', $base64Policy, $this->secretKey, true));
        return [
            'accessid' => $this->accessKey,
            'host' => $this->uploadUrl,
            'policy' => $base64Policy,
            'signature' => $signature,
            'cdn' => $this->cdn,
            'expire' => time() + 30,
            'callback' => $base64CallbackBody,
            'type' => 'OSS'
        ];
    }
    /**
     * 获取ISO时间格式
     * @param $time
     * @return string
     */
    protected function gmtIso8601($time)
    {
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . "Z";
    }
}
