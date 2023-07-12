<?php

namespace crmeb\services\upload\storage;

use crmeb\basic\BaseUpload;
use crmeb\exceptions\UploadException;
use Guzzle\Http\EntityBody;
use Obs\ObsClient;
use Obs\ObsException;
use think\Exception;
use think\exception\ValidateException;

/**
 * 华为云OBS文件上传
 * Class OBS
 * @package crmeb\services\upload\storage
 */
class Ctoss extends BaseUpload
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
     * @var \Obs\ObsClient
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
     * 所属地域
     * @var mixed|null
     */
    protected $storageRegion;

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

    protected $cdn;

    protected $image_suffix;

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
        $this->uploadUrl = $this->checkUploadUrl($config['uploadUrl'] ?? '');
        $this->storageName = $config['storageName'] ?? null;
        $this->storageRegion = $config['storageRegion'] ?? null;
        $this->cdn = $config['cdn'] ?? null;
        $this->thumb_status = $config['thumb_status'];
        $this->thumb_rate = $config['thumb_rate'];
        $this->image_suffix = $config['image_suffix'];
    }

    /**
     * 初始化obs
     * @return ObsClient
     * @throws ObsException
     */
    protected function app()
    {
        if (!$this->accessKey || !$this->secretKey) {
            throw new UploadException('Please configure accessKey and secretKey');
        }
        $this->handle= new ObsClient([
            'key' => $this->accessKey,
            'secret' => $this->secretKey,
            'endpoint' => $this->storageRegion
        ]);
        return $this->handle;
    }

    /**
     * 上传文件
     * @param string $file
     * @return array|bool|mixed|\StdClass
     */
    public function move(string $file = 'file', $thubm = true)
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
        try {
            $uploadInfo = $this->app()->putObject(
                [
                    'Bucket' => $this->storageName,
                    'Key' => $key,
                    'SourceFile' => $fileHandle->getRealPath()
                ]);

            //   $this->storageName, $key, $fileHandle->getRealPath()); ObjectURL   HttpStatusCode
            if (!isset($uploadInfo['HttpStatusCode'])) {
                return $this->setError('Upload failure_obs');
            }
            $src = rtrim(($this->cdn ?: $this->uploadUrl)).'/'. $key;
            if ($thubm) $src = $this->thubm($src);
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
    public function stream(string $fileContent, string $key = null, $thubm = true)
    {
        try {
            if (!$key) {
                $key = $this->saveFileName();
            }
            $fileContent = (string)EntityBody::factory($fileContent);
            $uploadInfo = $this->app()->putObject(
                [
                    'Bucket' => $this->storageName,
                    'Key' => $key,
                    'Body' => $fileContent
                ]);
            if ((int)$uploadInfo['HttpStatusCode'] != 200) {
                return $this->setError('Upload failure obs str');
            }
            $src = $this->uploadUrl . '/'.$key;
            if ($thubm) $src = $this->thubm($src);

            $this->fileInfo->uploadInfo = $uploadInfo;
            $this->fileInfo->filePath = $src;
            $this->fileInfo->fileName = $key;
            return $this->fileInfo;
        } catch (UploadException $e) {
            return $this->setError($e->getMessage());
        }
    }

    public function thubm(string $key)
    {
        $suffix = explode('.',$key);
        if (!$suffix && !in_array($suffix[1],config('upload.iamge_fileExt'))) return $key;
        if ($this->thumb_status && $key) {
            $key = $key.'?x-image-process=image/resize'. urlencode(',').'p_'. $this->thumb_rate;
        }
        return $key;
    }


    /**
     * 获取上传配置信息
     * @return array
     */
    public function getSystem()
    {
        $token = $this->app()->uploadToken($this->storageName);
        $domain = $this->uploadUrl;
        $key = $this->saveFileName();
        return compact('token', 'domain', 'key');
    }

    /**
     * 删除资源
     * @param $key
     * @return mixed
     */
    public function delete(string $key)
    {
        try {
            return $this->app()->deleteObject([$this->storageName, $key]);
        } catch (ObsException $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 获取OBS上传密钥
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
            'expiration' => $this->gmtIso8601(time() + 300),
            'conditions' =>
                [
                    [0 => 'content-length-range', 1 => 0, 2 => 1048576000],
                    ['bucket' => $this->storageName],
                    [0 => 'starts-with', 1 => '$key', 2 => $dir],
                ]
        ]);
        $base64Policy = base64_encode($policy);
        $signature = base64_encode(hash_hmac('sha1', $base64Policy, $this->secretKey, true));
        return [
            'accessid' => $this->accessKey,
            'host' => $this->uploadUrl,
            'policy' => $base64Policy,
            'signature' => $signature,
            'expire' => time() + 30,
            'callback' => $base64CallbackBody,
            'cdn' => $this->cdn,
            'type' => 'CTOSS'
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
