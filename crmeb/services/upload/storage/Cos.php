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
use Qcloud\Cos\Client;
use think\Exception;
use think\exception\ValidateException;
use think\Image;

/**
 * 腾讯云COS文件上传
 * Class COS
 * @package crmeb\services\upload\storage
 */
class Cos extends BaseUpload
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
     * @var Client
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
     * 实例化cos
     * @return Client
     */
    protected function app()
    {
        if (!$this->accessKey || !$this->secretKey) {
            throw new UploadException('Please configure accessKey and secretKey');
        }
        $this->handle = new Client(['region' => $this->storageRegion, 'credentials' => [
            'secretId' => $this->accessKey, 'secretKey' => $this->secretKey
        ]]);
        return $this->handle;
    }

    /**
     * 上传文件
     * @param string|null $file
     * @param bool $isStream 是否为流上传
     * @param string|null $fileContent 流内容
     * @return array|bool|\StdClass
     */
    protected function upload(string $file = null, bool $isStream = false, string $fileContent = null,$thumb = true)
    {
        if (!$isStream) {
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
            $body = fopen($fileHandle->getRealPath(), 'rb');
        } else {
            $key = $file;
            $body = $fileContent;
        }
        $path = ($this->path ? trim($this->path , '/') . '/' : '');
        try {
            $this->fileInfo->uploadInfo = $this->app()->putObject([
                'Bucket' => $this->storageName,
                'Key' => $path . $key,
                'Body' => $body
            ]);
            $src = rtrim(($this->cdn ?: $this->uploadUrl), '/') . '/' . $path . $key;
            if ($thumb) $src = $this->thumb($src);
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
            $key = $key.'?imageMogr2/thumbnail/!' . $this->thumb_rate .'p';
        }
        return $key;
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
        return $this->upload($key, true, $fileContent,false);
    }

    /**
     * 文件上传
     * @param string $file
     * @return array|bool|mixed|\StdClass
     */
    public function move(string $file = 'file' ,$thumb = true)
    {
        return $this->upload($file,false,null,$thumb);
    }

    /**
     * TODO 删除资源
     * @param $key
     * @return mixed
     */
    public function delete(string $filePath)
    {
        try {
            return $this->app()->deleteObject(['Bucket' => $this->storageName, 'Key' => $filePath]);
        } catch (\Exception $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 获取腾讯云存储临时密钥
     * @return array|bool|mixed|null|string
     */
    public function getTempKeys()
    {
        // TODO: Implement getTempKeys() method.
        $config = array(
            'url' => 'https://sts.tencentcloudapi.com/',
            'domain' => 'sts.tencentcloudapi.com',
            'proxy' => '',
            'secretId' => $this->accessKey, // 固定密钥
            'secretKey' => $this->secretKey, // 固定密钥
            'bucket' => $this->storageName, // 换成你的 bucket
            'region' => $this->storageRegion, // 换成 bucket 所在园区
            'durationSeconds' => 1800, // 密钥有效期
            'allowPrefix' => '*', // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
            // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
            'allowActions' => array (
                // 简单上传
                'name/cos:PutObject',
                'name/cos:PostObject',
                // 分片上传
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload'
            )
        );
        $result = null;
        try{
            if(array_key_exists('policy', $config)){
                $policy = $config['policy'];
            }else{
                if(array_key_exists('bucket', $config)){
                    $ShortBucketName = substr($config['bucket'],0, strripos($config['bucket'], '-'));
                    $AppId = substr($config['bucket'], 1 + strripos($config['bucket'], '-'));
                }else{
                    throw new Exception("bucket== null");
                }
                if(array_key_exists('allowPrefix', $config)){
                    if(!(strpos($config['allowPrefix'], '/') === 0)){
                        $config['allowPrefix'] = '/' . $config['allowPrefix'];
                    }
                }else{
                    throw new Exception("allowPrefix == null");
                }
                $policy = array(
                    'version'=> '2.0',
                    'statement'=> array(
                        array(
                            'action'=> $config['allowActions'],
                            'effect'=> 'allow',
                            'principal'=> array('qcs'=> array('*')),
                            'resource'=> array(
                                'qcs::cos:' . $config['region'] . ':uid/' . $AppId . ':' . $config['bucket'] . $config['allowPrefix']
                            )
                        )
                    )
                );
            }
            $policyStr = str_replace('\\/', '/', json_encode($policy));
            $Action = 'GetFederationToken';
            $Nonce = rand(10000, 20000);
            $Timestamp = time();
            $Method = 'POST';
            if(array_key_exists('durationSeconds', $config)){
                if(!(is_integer($config['durationSeconds']))){
                    throw new exception("durationSeconds must be a int type");
                }
            }
            $params = array(
                'SecretId'=> $config['secretId'],
                'Timestamp'=> $Timestamp,
                'Nonce'=> $Nonce,
                'Action'=> $Action,
                'DurationSeconds'=> $config['durationSeconds'],
                'Version'=>'2018-08-13',
                'Name'=> 'cos',
                'Region'=> $config['region'],
                'Policy'=> urlencode($policyStr)
            );
            $params['Signature'] = $this->getSignature($params, $config['secretKey'], $Method, $config);
            $url = $config['url'];
            $ch = curl_init($url);
            if(array_key_exists('proxy', $config)){
                $config['proxy'] && curl_setopt($ch, CURLOPT_PROXY, $config['proxy']);
            }
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->json2str($params));
            $result = curl_exec($ch);
            if(curl_errno($ch)) $result = curl_error($ch);
            curl_close($ch);
            $result = json_decode($result, 1);
            if (isset($result['Response'])) {
                $result = $result['Response'];
                if(isset($result['Error'])){
                    throw new Exception("get cam failed");
                }
                $result['startTime'] = $result['ExpiredTime'] - $config['durationSeconds'];
            }
            $result = $this->backwardCompat($result);
            $result['url'] = $this->uploadUrl . '/';
            $result['type'] = 'COS';
            $result['cdn'] = $this->cdn;
            $result['bucket'] = $this->storageName;
            $result['region'] = $this->storageRegion;
            return $result;
        }catch(Exception $e){
            if($result == null){
                $result = "error: " . + $e->getMessage();
            }else{
                $result = json_encode($result);
            }
            throw new Exception($result);
        }
    }

    /**
     * 计算临时密钥用的签名
     * @param $opt
     * @param $key
     * @param $method
     * @param $config
     * @return string
     */
    public function getSignature($opt, $key, $method, $config) {
        $formatString = $method . $config['domain'] . '/?' . $this->json2str($opt, 1);
        $sign = hash_hmac('sha1', $formatString, $key);
        $sign = base64_encode($this->_hex2bin($sign));
        return $sign;
    }
    public function _hex2bin($data) {
        $len = strlen($data);
        return pack("H" . $len, $data);
    }
    // obj 转 query string
    public function json2str($obj, $notEncode = false) {
        ksort($obj);
        $arr = array();
        if(!is_array($obj)){
            return $this->setError($obj . " must be a array");
        }
        foreach ($obj as $key => $val) {
            array_push($arr, $key . '=' . ($notEncode ? $val : rawurlencode($val)));
        }
        return join('&', $arr);
    }
    // v2接口的key首字母小写，v3改成大写，此处做了向下兼容
    public function backwardCompat($result) {
        if(!is_array($result)){
            return $this->setError($result . " must be a array");
        }
        $compat = array();
        foreach ($result as $key => $value) {
            if(is_array($value)) {
                $compat[lcfirst($key)] = $this->backwardCompat($value);
            } elseif ($key == 'Token') {
                $compat['sessionToken'] = $value;
            } else {
                $compat[lcfirst($key)] = $value;
            }
        }
        return $compat;
    }
}
