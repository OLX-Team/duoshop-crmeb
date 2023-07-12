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


namespace crmeb\services\easywechat;


use EasyWeChat\Core\AbstractAPI;
use EasyWeChat\Core\AccessToken;
use EasyWeChat\Core\Exceptions\HttpException;
use EasyWeChat\Core\Exceptions\InvalidConfigException;
use EasyWeChat\Core\Http;
use EasyWeChat\Encryption\EncryptionException;
use think\exception\InvalidArgumentException;

class BaseClient extends AbstractAPI
{
    protected $app;

    const KEY_LENGTH_BYTE = 32;
    const AUTH_TAG_LENGTH_BYTE = 16;

    protected $isService = true;

    public function __construct(AccessToken $accessToken, $app)
    {
        parent::__construct($accessToken);
        $this->app = $app;
    }

    public function setServiceStatus($val)
    {
        $this->isService = $val;
        return $this;
    }


    /**
     * @param $api
     * @param $params
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    protected function httpPostJson($api, $params)
    {
        try {
            return $this->parseJSON('json', [$api, $params]);
        } catch (HttpException $e) {
            $code = $e->getCode();
            throw new HttpException("接口异常[$code]" . ($e->getMessage()), $code);
        }
    }

    /**
     * @param $api
     * @param $params
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    protected function httpPost($api, $params)
    {
        try {
            return $this->parseJSON('post', [$api, $params]);
        } catch (HttpException $e) {
            $code = $e->getCode();
            throw new HttpException("接口异常[$code]" . ($e->getMessage()), $code);
        }
    }


    /**
     * @param $api
     * @param $params
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    protected function httpGet($api, $params)
    {
        try {
            return $this->parseJSON('get', [$api, $params]);
        } catch (HttpException $e) {
            $code = $e->getCode();
            throw new HttpException("接口异常[$code]" . ($e->getMessage()), $code);
        }
    }

    /**
     * request.
     *
     * @param string $endpoint
     * @param string $method
     * @param array $options
     * @param bool $returnResponse
     */
    public function request(string $endpoint, string $method = 'POST', array $options = [], $serial = true)
    {
        $sign_body = $options['sign_body'] ?? '';
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'curl',
            'Accept' => 'application/json',
            'Authorization' => $this->getAuthorization($endpoint, $method, $sign_body),
//            'Wechatpay-Serial' => $this->app['config']['payment']['serial_no']
        ];
        $options['headers'] = array_merge($headers, ($options['headers'] ?? []));

        if ($serial) $options['headers']['Wechatpay-Serial'] = $this->app->certficates->setServiceStatus($this->isService)->get()['serial_no'];

        Http::setDefaultOptions($options);
        return $this->_doRequestCurl($method, 'https://api.mch.weixin.qq.com' . $endpoint, $options);
    }


    private function _doRequestCurl($method, $location, $options = [])
    {
        $curl = curl_init();
        // POST数据设置
        if (strtolower($method) === 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $options['data'] ?? $options['sign_body'] ?? '');
        }
        // CURL头信息设置
        if (!empty($options['headers'])) {
            $headers = [];
            foreach ($options['headers'] as $k => $v) {
                $headers[] = "$k: $v";
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $location);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $content = curl_exec($curl);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);
        return json_decode(substr($content, $headerSize), true);
    }


    /**
     * get sensitive fields name.
     *
     * @return array
     */
    protected function getSensitiveFieldsName()
    {
        return [
            'contact_name',
            'contact_id_number',
            'mobile_phone',
            'contact_email',
            'id_card_name',
            'id_card_number',
            'id_card_address',
            'id_doc_name',
            'id_doc_number',
            'id_doc_address',
            'name',
            'id_number',
            'account_name',
            'account_number',
            'contact_id_card_number',
            'contact_email',
            'openid',
            'ubo_id_doc_name',
            'ubo_id_doc_number',
            'ubo_id_doc_address',
            'bank_address_code',
        ];
    }

    /**
     * To id card, mobile phone number and other fields sensitive information encryption.
     *
     * @param string $string
     *
     * @return string
     */
    protected function encryptSensitiveInformation(string $string)
    {
        $certificates = $this->app->certficates->setServiceStatus($this->isService)->get()['certificates'];
        if (null === $certificates) {
            throw new InvalidConfigException('config certificate connot be empty.');
        }
        $encrypted = '';
        if (openssl_public_encrypt($string, $encrypted, $certificates, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64编码
            $sign = base64_encode($encrypted);
        } else {
            throw new EncryptionException('Encryption of sensitive information failed');
        }
        return $sign;
    }

    /**
     * processing parameters contain fields that require sensitive information encryption.
     *
     * @param array $params
     *
     * @return array
     */
    protected function processParams(array $params)
    {

        $sensitive_fields = $this->getSensitiveFieldsName();
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $params[$k] = $this->processParams($v);
            } else {
                if (in_array($k, $sensitive_fields, true)) {
                    $params[$k] = $this->encryptSensitiveInformation($v);
                }
            }
        }

        return $params;
    }

    /**
     * @param string $url
     * @param string $method
     * @param string $body
     * @return string
     */
    protected function getAuthorization(string $url, string $method, string $body)
    {
        $nonce_str = uniqid();
        $timestamp = time();
        $message = $method . "\n" .
            $url . "\n" .
            $timestamp . "\n" .
            $nonce_str . "\n" .
            $body . "\n";
        openssl_sign($message, $raw_sign, $this->getPrivateKey(), 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);
        $schema = 'WECHATPAY2-SHA256-RSA2048 ';
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            ($this->isService ? $this->app['config']['service_payment']['merchant_id'] : $this->app['config']['payment']['merchant_id']),
            $nonce_str,
            $timestamp,
            ($this->isService ? $this->app['config']['service_payment']['serial_no'] : $this->app['config']['payment']['serial_no']),
            $sign);

        return $schema . $token;
    }

    /**
     * 获取商户私钥
     * @return bool|resource
     */
    protected function getPrivateKey()
    {
        $key_path = $this->isService ? $this->app['config']['service_payment']['key_path'] : $this->app['config']['payment']['key_path'];
        if (!file_exists($key_path)) {
            throw new \InvalidArgumentException(
                "SSL certificate not found: {$key_path}"
            );
        }
        return openssl_pkey_get_private(file_get_contents($key_path));
    }

    /**
     * decrypt ciphertext.
     *
     * @param array $encryptCertificate
     *
     * @return string
     */
    public function decrypt(array $encryptCertificate)
    {
        $ciphertext = base64_decode($encryptCertificate['ciphertext'], true);
        $associatedData = $encryptCertificate['associated_data'];
        $nonceStr = $encryptCertificate['nonce'];
        $aesKey = ($this->isService ? $this->app['config']['service_payment']['apiv3_key'] : $this->app['config']['payment']['apiv3_key']);

        try {
            // ext-sodium (default installed on >= PHP 7.2)
            if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') && \sodium_crypto_aead_aes256gcm_is_available()) {
                return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $aesKey);
            }
            // ext-libsodium (need install libsodium-php 1.x via pecl)
            if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') && \Sodium\crypto_aead_aes256gcm_is_available()) {
                return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $aesKey);
            }
            // openssl (PHP >= 7.1 support AEAD)
            if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
                $ctext = substr($ciphertext, 0, -self::AUTH_TAG_LENGTH_BYTE);
                $authTag = substr($ciphertext, -self::AUTH_TAG_LENGTH_BYTE);
                return \openssl_decrypt($ctext, 'aes-256-gcm', $aesKey, \OPENSSL_RAW_DATA, $nonceStr, $authTag, $associatedData);
            }
        } catch (\Exception $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode());
        } catch (\SodiumException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode());
        }
        throw new InvalidArgumentException('AEAD_AES_256_GCM 需要 PHP 7.1 以上或者安装 libsodium-php');
    }
}
