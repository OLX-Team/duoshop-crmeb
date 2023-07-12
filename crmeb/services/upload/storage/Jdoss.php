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

use AsyncAws\Core\Credentials\Credentials;
use AsyncAws\Core\Exception\Exception;
use crmeb\basic\BaseUpload;
use crmeb\exceptions\UploadException;
use AsyncAws\S3\S3Client;
use Guzzle\Http\EntityBody;
use Symfony\Component\HttpClient\Response\AsyncResponse;
use think\exception\ValidateException;

/**
 * 阿里云OSS上传
 * Class OSS
 */
class Jdoss extends BaseUpload
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

    protected $cache = [];
    protected $cacheSize = 0;

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
        $this->handle = new S3Client([
            'endpoint' => $this->uploadUrl,
            'accessKeyId' => $this->accessKey,
            'accessKeySecret' => $this->secretKey,
            'region' => $this->storageRegion,
        ]);
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
            $uploadInfo = $this->app()->PutObject([
                'Bucket' => $this->storageName,
                'Key' => $path.$key,
                'Body' => fopen($fileHandle,'r'),
            ]);
            if ($uploadInfo->info()['response']->getStatusCode() == 200) {
                if($this->cdn) {
                    $src =rtrim($this->cdn).$path.$key;
                } else {
                    $src = $uploadInfo->info()['response']->getInfo()['url'];
                }
            }
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
            $uploadInfo = $this->app()->PutObject([
                'Bucket' => $this->storageName,
                'Key' => $path.$key,
                'Body' => $fileContent,
            ]);
            if ($uploadInfo->info()['response']->getStatusCode() == 200) {
                if($this->cdn) {
                    $src =rtrim($this->cdn).$path.$key;
                } else {
                    $src = $uploadInfo->info()['response']->getInfo()['url'];
                }
            }
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
            $param = ('x-oss-process=img/sp'. $this->thumb_rate);
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
            return $this->app()->deleteObject([
                'Bucket' => $this->storageName,
                'Key' => $key,
            ]);
        } catch (Exception $e) {
            return $this->setError($e->getMessage());
        }
    }

    public function buildSigningKey(array $credentialScope): string
    {
        $signingKey = 'AWS4' . $this->accessKey;
        foreach ($credentialScope as $scopePart) {
            $signingKey = hash_hmac('sha256', $scopePart, $signingKey, true);
        }

        return $signingKey;
    }

    public function buildStringToSign(\DateTimeImmutable $now, string $credentialString, string $canonicalRequest): string
    {
        return implode("\n", [
            'AWS4-HMAC-SHA256',
            $now->format('Ymd\THis\Z'),
            $credentialString,
            hash('sha256', $canonicalRequest),
        ]);
    }

    /**
     * 获取OSS上传密钥
     * @return mixed|void
     */
    public function getTempKeys($callbackUrl = '', $dir = '')
    {
        $ldt = gmdate('Ymd\THis\Z');
        $sdt = substr($ldt, 0, 8);
        $key = $this->getSigningKey(
            $sdt,
            $this->storageRegion,
            's3',
            $this->accessKey
        );
        $credential = [$sdt, $this->storageRegion, 's3', 'aws4_request'];

        $policy = json_encode([
            'expiration' => gmdate('Y-m-d\TH:i:s\Z', time()),
            'conditions' =>
                [
                    ['bucket' => $this->storageRegion],
                    ['success_action_status' => 200],
                    ['X-Amz-Credential' => $this->accessKey.'/'.implode('/', $credential)],
                    ['X-Amz-Algorithm' => 'AWS4-HMAC-SHA256'],
                    ['X-Amz-Date' => $ldt]
                ]
        ]);

        $jsonPolicy64 = base64_encode($policy);
        $signature = bin2hex(hash_hmac('sha256', $jsonPolicy64, $key, true));
        return [
            'expiration'    => $ldt,
            'storageName'   => $this->storageName,
            'storageRegion' => $this->storageRegion,
            'signature'     => $signature,
            'credential'    => $this->accessKey.'/'.implode('/', $credential),
            'policy'=> $jsonPolicy64,
            'type'  => 'JDOSS',
            'cdn'   => $this->cdn,
        ];
    }

    public function getSigningKey($shortDate, $region, $service, $secretKey)
    {
        $kSecret = 'AWS4' . $secretKey;
        $kDate = hash_hmac('sha256', $shortDate, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
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
        return $expiration . ".000Z";
    }
}
