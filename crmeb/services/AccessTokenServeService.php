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

use app\common\repositories\system\config\ConfigValueRepository;
use think\exception\ValidateException;
use think\facade\Cache;

/**
 * Class AccessTokenServeService
 * @package crmeb\services
 */
class AccessTokenServeService extends HttpService
{
    /**
     * 配置
     * @var string
     */
    protected $account;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var Cache|null
     */
    protected $cache;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $cacheTokenPrefix = "_crmeb_plat";

    /**
     * @var string
     */
    protected $apiHost = 'http://sms.crmeb.net/api/';

    /**
     * 沙盒地址
     * @var string
     */
    protected $sandBoxApi = 'https://api_v2.crmeb.net/api/';

    /**
     * 沙盒模式
     * @var bool
     */
    protected $sandBox = false;

    /**
     * 登录接口
     */
    const USER_LOGIN = "user/login";


    /**
     * AccessTokenServeService constructor.
     * @param string $account
     * @param string $secret
     * @param Cache|null $cache
     */
    public function __construct(string $account, string $secret, $cache = null)
    {
        $this->account = $account;
        $this->secret = $secret;
    }

    /**
     * 获取配置
     * @return array
     */
    public function getConfig()
    {
        return [
            'account' => $this->account,
            'secret' => $this->secret
        ];
    }

    /**
     * 获取缓存token
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getToken()
    {
        $accessTokenKey = md5($this->account . '_' . $this->secret . $this->cacheTokenPrefix);
        $cacheToken = Cache::get($accessTokenKey);
        if (!$cacheToken) {
            $getToken = $this->getTokenFromServer();
            Cache::set($accessTokenKey, $getToken['access_token'], 300);
            $cacheToken = $getToken['access_token'];
        }
        $this->accessToken = $cacheToken;

        return $cacheToken;
    }

    /**
     * 从服务器获取token
     * @return mixed
     */
    public function getTokenFromServer()
    {
        $params = [
            'account' => $this->account,
            'secret' => $this->secret,
        ];
        if (!$this->account || !$this->secret) {
            throw new ValidateException('请先登录一号通平台!');
        }
        $response = $this->postRequest($this->get(self::USER_LOGIN), $params);
        $response = json_decode($response, true);
        if (!$response) {
            throw new ValidateException('获取token失败');
        }
        if ($response['status'] === 200) {
            return $response['data'];
        } else {
            throw new ValidateException($response['msg']);
        }
    }

    /**
     * 请求
     * @param string $url
     * @param array $data
     * @param string $method
     * @param bool $isHeader
     * @return array|mixed
     */
    public function httpRequest(string $url, array $data = [], string $method = 'POST', bool $isHeader = true,$header = [])
    {
        if ($isHeader) {
            $this->getToken();
            if (!$this->accessToken) {
                throw new ValidateException('配置已更改或token已失效');
            }
            $header =  array_merge(['Authorization:Bearer-' . $this->accessToken] , $header);
        }
        $res = $this->request($this->get($url), $method, $data, $header);
        if (!$res) {
            throw new ValidateException('平台错误：发生异常，请稍后重试');
        }
        $result = json_decode($res, true) ?: false;
        if (!isset($result['status']) || $result['status'] != 200) {
            if ($result['msg'] == '接口请求失败:用户或密码错误') {
               app()->make(CrmebServeServices::class)->logout();
            }
            throw new ValidateException(isset($result['msg']) ? '平台错误：' . $result['msg'] : '平台错误：发生异常，请稍后重试');
        }
        return $result['data'] ?? [];

    }

    /**
     * @param string $apiUrl
     * @return string
     */
    public function get(string $apiUrl = '')
    {
        $host = $this->sandBox ? $this->sandBoxApi : $this->apiHost;
        return $host . $apiUrl;
    }
}
