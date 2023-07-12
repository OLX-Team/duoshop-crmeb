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

use think\contract\Arrayable;
use think\response\Json;

class ApiResponseService
{
    protected $response;

    const DEFAULT_SUCCESS_MESSAGE = 'success';
    const DEFAULT_FAIL_MESSAGE = 'fail';

    const DEFAULT_SUCCESS_CODE = 200;
    const DEFAULT_FAIL_CODE = 400;

    public function __construct(Json $response)
    {
        $this->response = $response;
    }

    public function code(int $code)
    {
        $this->response->code($code);

        return $this;
    }

    /**
     * @param $data
     * @return array|string|null
     */
    private function parseData($data)
    {
        if ($data instanceof Arrayable)
            return $data->toArray();
        else
            return $data;
    }

    /**
     * @param int $status
     * @param string $message
     * @param array|Arrayable|null $data
     * @return Json
     */
    public function make(int $status, string $message, $data = null): Json
    {
        $content = compact('status', 'message');
        if (!is_null($data))
            $content['data'] = $this->parseData($data);
        $this->response->data($content);
        return $this->response;
    }

    /**
     * @param string|array|Arrayable $message
     * @param array|Arrayable|null $data
     * @return Json
     */
    public function success($message = self::DEFAULT_SUCCESS_MESSAGE, $data = null)
    {
        $message = $this->parseData($message);
        if (is_array($message)) {
            $data = $message;
            $message = self::DEFAULT_SUCCESS_MESSAGE;
        } else {
            $data = $this->parseData($data);
        }
        return $this->make(self::DEFAULT_SUCCESS_CODE, $message, $data);
    }

    /**
     * @param string|array|Arrayable $message
     * @param array|Arrayable|null $data
     * @return Json
     */
    public function fail($message = self::DEFAULT_FAIL_MESSAGE, $data = null)
    {
        $message = $this->parseData($message);
        if (is_array($message)) {
            $data = $message;
            $message = self::DEFAULT_FAIL_MESSAGE;
        } else {
            $data = $this->parseData($data);
        }
        return $this->make(self::DEFAULT_FAIL_CODE, $message, $data);
    }

    /**
     * @param $status
     * @param string|array|Arrayable $message
     * @param array|Arrayable $result
     * @return Json
     */
    public function status($status, $message, $result = [])
    {
        $message = $this->parseData($message);
        if (is_array($message)) {
            $result = $message;
            $message = self::DEFAULT_SUCCESS_MESSAGE;
        } else {
            $result = $this->parseData($result);
        }
        return $this->make(self::DEFAULT_SUCCESS_CODE, $message, compact('status', 'result'));
    }

    /**
     * @param string $type
     * @param $data
     * @return Json
     * @author xaboy
     * @day 2020/6/13
     */
    public function message(string $type, $data)
    {
        $this->response->data(compact('type', 'data'));
        return $this->response;
    }

}
