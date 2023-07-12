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
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use think\exception\ValidateException;


/**
 * TODO 七牛云上传
 * Class Qiniu
 */
class Qiniu extends BaseUpload
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
     * @var object
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
    public function initialize(array $config)
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
     * 实例化七牛云
     * @return object|Auth
     */
    protected function app()
    {
        if (!$this->accessKey || !$this->secretKey) {
            throw new UploadException('Please configure accessKey and secretKey');
        }
        $this->handle = new Auth($this->accessKey, $this->secretKey);
        return $this->handle;
    }

    /**
     * 上传文件
     * @param string $file
     * @return array|bool|mixed|\StdClass|string
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
        $key = $this->saveFileName($fileHandle->getRealPath(), $fileHandle->getOriginalExtension());
        $path = ($this->path ? trim($this->path, '/') . '/' : '');
        $token = $this->app()->uploadToken($this->storageName);
        try {
            $uploadMgr = new UploadManager();
            [$result, $error] = $uploadMgr->putFile($token, $path . $key, $fileHandle->getRealPath());
            if ($error !== null) {
                return $this->setError($error->message());
            }
            $src = rtrim(($this->cdn ?: $this->uploadUrl), '/') . '/' . $path . $key;
            if ($thumb) $src = $this->thumb($src);
            $this->fileInfo->uploadInfo = $result;
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
     * @return array|bool|mixed|\StdClass
     */
    public function stream(string $fileContent, string $key = null, $thumb = true)
    {
        $token = $this->app()->uploadToken($this->storageName);
        if (!$key) {
            $key = $this->saveFileName();
        }
        $path = ($this->path ? trim($this->path, '/') . '/' : '');
        try {
            $uploadMgr = new UploadManager();
            [$result, $error] = $uploadMgr->put($token, $path . $key, $fileContent);
            if ($error !== null) {
                return $this->setError($error->message());
            }

            $src = rtrim(($this->cdn ?: $this->uploadUrl), '/') . '/' . $path . $key;
            if ($thumb) $src = $this->thumb($src);

            $this->fileInfo->uploadInfo = $result;
            $this->fileInfo->filePath = $src;
            $this->fileInfo->fileName = $key;
            return $this->fileInfo;
        } catch (UploadException $e) {
            return $this->setError($e->getMessage());
        }
    }

    public function thumb(string $key = '')
    {
        $suffix = explode('.',$key);
        if (!$suffix && !in_array($suffix[1],config('upload.iamge_fileExt'))) return $key;
        if ($this->thumb_status && $key) {
            $key = $key . '?imageMogr2/thumbnail/!' . $this->thumb_rate . 'p';
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
     * TODO 删除资源
     * @param $key
     * @param $bucket
     * @return mixed
     */
    public function delete(string $key)
    {
        $bucketManager = new BucketManager($this->app(), new Config());
        return $bucketManager->delete($this->storageName, $key);
    }

    /**
     * 获取七牛云上传密钥
     * @return mixed|string
     */
    public function getTempKeys()
    {
        $token = $this->app()->uploadToken($this->storageName);
        $domain = $this->uploadUrl;
        $key = $this->saveFileName(NULL, 'mp4');
        $type = 'QINIU';
        $cdn = $this->cdn;
        return compact('token', 'domain', 'key', 'type', 'cdn');
    }
}
