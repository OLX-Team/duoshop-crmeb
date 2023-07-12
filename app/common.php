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

// 应用公共文件

use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use crmeb\services\UploadService;
use Swoole\Lock;
use think\db\BaseQuery;

if (!function_exists('go')) {
    function go(): bool
    {
        return \Swoole\Coroutine::create(...func_get_args());
    }
}

if (!function_exists('isDebug')) {
    function isDebug(): bool
    {
        return !!env('APP_DEBUG');
    }
}

if (!function_exists('formToData')) {
    function formToData($form): array
    {
        $rule = $form->formRule();
        $action = $form->getAction();
        $method = $form->getMethod();
        $title = $form->getTitle();
        $config = (object)$form->formConfig();
        $admin = config('admin.api_admin_prefix');
        $merchant = config('admin.api_merchant_prefix');
        $api = $action;
        if (strpos($api, '/' . $admin) === 0) {
            $api = substr($api, strlen($admin) + 1);
        } else if (strpos($api, '/' . $merchant) === 0) {
            $api = substr($api, strlen($merchant) + 1);
        }
        $api = str_replace('.html', '', $api);
        return compact('rule', 'action', 'method', 'title', 'config', 'api');
    }
}

if (!function_exists('getDistance')) {

    function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        //将角度转为狐度
        $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6371;
        return round($s, 1);
    }
}

/**
 * 无线级分类处理
 *
 * @param array $data 数据源
 * @param string $idName 主键
 * @param string $fieldName 父级字段
 * @param string $childrenKey 子级字段名
 * @return array
 * @author 张先生
 * @date 2020-03-27
 */
if (!function_exists('formatCategory')) {
    function formatCategory(array $data, string $idName = "id", string $fieldName = 'pid', $childrenKey = 'children')
    {
        $items = [];
        foreach ($data as $item) {
            $items[$item[$idName]] = $item;
        }
        $result = array();
        foreach ($items as $item) {
            if (isset($items[$item[$fieldName]])) {
                $items[$item[$fieldName]][$childrenKey][] = &$items[$item[$idName]];
            } else if ($item[$fieldName] == 0) {
                $result[] = &$items[$item[$idName]];
            }
        }
        return $result;
    }
}

if (!function_exists('formatTreeList')) {
    function formatTreeList(&$options, $name, $pidName = 'pid', $pid = 0, $level = 0, &$data = []): array
    {
        $_options = $options;
        foreach ($_options as $k => $option) {
            if ($option[$pidName] == $pid) {
                $data[] = ['value' => $k, 'label' => str_repeat('|---', $level + 1) . $option[$name]];
                unset($options[$k]);
                formatTreeList($options, $name, $pidName, $k, $level + 1, $data);
            }
        }
        return $data;
    }
}

if (!function_exists('formatTree')) {
    function formatTree(&$options, $name, $pidName = 'pid', $pid = 0, $level = 0, $data = []): array
    {
        $_options = $options;
        foreach ($_options as $k => $option) {
            if ($option[$pidName] == $pid) {
                $value = ['id' => $k, 'title' => $option[$name]];
                unset($options[$k]);
                $value['children'] = formatTree($options, $name, $pidName, $k, $level + 1);
                $data[] = $value;
            }
        }
        return $data;
    }
}

if (!function_exists('formatCascaderData')) {
    function formatCascaderData(&$options, $name, $baseLevel = 0, $pidName = 'pid', $pid = 0, $level = 0, $data = []): array
    {
        $_options = $options;
        foreach ($_options as $k => $option) {
            if ($option[$pidName] == $pid) {
                $value = ['value' => $k, 'label' => $option[$name]];
                unset($options[$k]);
                $value['children'] = formatCascaderData($options, $name, $baseLevel, $pidName, $k, $level + 1);
                if (!count($value['children'])) unset($value['children']);
                $data[] = $value;
            }
        }
        return $data;
    }
}


/**
 * @function toMap 数组重新组装
 * @param array $data 数据
 * @param string $field key
 * @param string $value value default null
 * @return array
 * @author 张先生
 * @date 2020-04-01
 */
if (!function_exists('toMap')) {
    function toMap(array $data, $field = 'id', $value = '')
    {
        $result = array();

        if (empty($data)) {
            return $result;
        }

        //开始处理数据
        foreach ($data as $item) {
            $val = $item;
            if (!empty($value)) {
                $val = $item[$value];
            }
            $result[$item[$field]] = $val;
        }

        return $result;
    }
}

/**
 * @function getUniqueListByArray 从数组中获取某个字段的值，重新拼装成新的一维数组
 * @param array $data 数据
 * @param string $field key
 * @return array
 * @author 张先生
 * @date 2020-04-01
 */
if (!function_exists('getUniqueListByArray')) {
    function getUniqueListByArray(array $data, $field = 'id')
    {
        return array_unique(array_values(array_column($data, $field)));
    }
}


if (!function_exists('isPhone')) {
    function isPhone($test)
    {
        return !preg_match("/^1[3456789]{1}\d{9}$/", $test);
    }
}

if (!function_exists('getMonth')) {
    /**
     * 获取本季度 time
     * @param int|string $time
     * @param $ceil
     * @return array
     */
    function getMonth($time = '', $ceil = 0)
    {
        if ($ceil != 0)
            $season = ceil(date('n') / 3) - $ceil;
        else
            $season = ceil(date('n') / 3);
        $firstday = date('Y-m-01', mktime(0, 0, 0, ($season - 1) * 3 + 1, 1, date('Y')));
        $lastday = date('Y-m-t', mktime(0, 0, 0, $season * 3, 1, date('Y')));
        return array($firstday, $lastday);
    }
}


if (!function_exists('getModelTime')) {
    /**
     * @param BaseQuery $model
     * @param string $section
     * @param string $prefix
     * @param string $field
     * @return mixed
     * @author xaboy
     * @day 2020-04-29
     */
    function getModelTime(BaseQuery $model, string $section, $prefix = 'create_time', $field = '-',$time = '')
    {
        if (!isset($section)) return $model;
        switch ($section) {
            case 'today':
                $model->whereBetween($prefix, [date('Y-m-d H:i:s', strtotime('today')), date('Y-m-d H:i:s', strtotime('tomorrow -1second'))]);
                break;
            case 'week':
                $model->whereBetween($prefix, [date('Y-m-d H:i:s', strtotime('this week 00:00:00')), date('Y-m-d H:i:s', strtotime('next week 00:00:00 -1second'))]);
                break;
            case 'month':
                $model->whereBetween($prefix, [date('Y-m-d H:i:s', strtotime('first Day of this month 00:00:00')), date('Y-m-d H:i:s', strtotime('first Day of next month 00:00:00 -1second'))]);
                break;
            case 'year':
                $model->whereBetween($prefix, [date('Y-m-d H:i:s', strtotime('this year 1/1')), date('Y-m-d H:i:s', strtotime('next year 1/1 -1second'))]);
                break;
            case 'yesterday':
                $model->whereBetween($prefix, [date('Y-m-d H:i:s', strtotime('yesterday')), date('Y-m-d H:i:s', strtotime('today -1second'))]);
                break;
            case 'quarter':
                list($startTime, $endTime) = getMonth();
                $model = $model->where($prefix, '>', $startTime);
                $model = $model->where($prefix, '<', $endTime);
                break;
            case 'lately7':
                $model = $model->where($prefix, 'between', [date('Y-m-d', strtotime("-7 day")), date('Y-m-d H:i:s')]);
                break;
            case 'lately30':
                $model = $model->where($prefix, 'between', [date('Y-m-d', strtotime("-30 day")), date('Y-m-d H:i:s')]);
                break;
            default:
                if (strstr($section, $field) !== false) {
                    list($startTime, $endTime) = explode($field, $section);
                    if (strlen($startTime) == 4) {
                        $model->whereBetweenTime($prefix, date('Y-m-d H:i:s', strtotime($section)), date('Y-m-d H:i:s', strtotime($section . ' +1day -1second')));
                    } else {
                        if ($startTime == $endTime) {
                            $model = $model->whereBetweenTime($prefix, date('Y-m-d 0:0:0', strtotime($startTime)), date('Y-m-d 23:59:59', strtotime($endTime)));
                        } else if(strpos($startTime, ':')) {
                            $model = $model->whereBetweenTime($prefix, $startTime, $endTime);
                        } else {
                            $model = $model->whereBetweenTime($prefix, date('Y-m-d H:i:s', strtotime($startTime)), date('Y-m-d H:i:s', strtotime($endTime . ' +1day -1second')));
                        }
                    }
                }
                break;
        }
        return $model;
    }
}

if (!function_exists('hasMany')) {
    function hasMany($collection, $field, $model, $searchKey, $insertKey, $where = [] ,$select = '*')
    {
        $ids = [];
        $link = [];

        if (!$collection) return [];
        $collection = $collection->toArray();
        foreach ($collection as $k => $item) {
            if (is_array($item[$field])) {
                $link[$k] = array_unique($item[$field]);
                $ids = array_merge($item[$field], $ids);
            } else {
                $link[$k] = array_unique(explode(',', $item[$field]));
            }
            $ids = array_merge($link[$k], $ids);
            if (isset($collection[$k][$insertKey])) unset($collection[$k][$insertKey]);
        }
        $ids = array_filter(array_unique($ids));
        if (!count($ids)) {
            return $collection;
        }
        $many = $model::whereIn($searchKey, array_unique($ids))->where($where)->field($select)->select();

        if (!$many) return $collection;
        $many = $many->toArray();
        foreach ($link as $k => $val) {
            foreach ($many as $item) {
                if (in_array($item[$searchKey], $val)) {

                    if (!isset($collection[$k][$insertKey])) $collection[$k][$insertKey] = [];

                    $collection[$k][$insertKey][] = $item;
                }
            }
        }

        return $collection;
    }
}

if (!function_exists('activeProductSku')) {
    //格式活动商品SKU
    function activeProductSku($activeData, $type = null)
    {
        $make = app()->make(\app\common\repositories\store\product\ProductRepository::class);
        $price = 0;
        $data = [];
        foreach($activeData as $key => $value) {
            $maxPrice = 0;
            $must_price = 0;
            $attrValue = [];
            if(is_null($value['product'])) continue;
            $productSku = $value['productSku'];
            $productAttr = $value['product']['attr'];
            $productAttrValue = $value['product']['attrValue'];
            unset($value['productSku'], $value['product']['attrValue'], $value['product']['attr']);
            foreach ($productAttrValue as $attr_value) {
                if (!empty($productSku)){
                    foreach ($productSku as $sk => $sv) {
                        if ( $sv['unique'] == $attr_value['unique']) {
                            if ($type == 'discounts') {
                                unset($attr_value['ot_price'], $attr_value['price']);
                                $attr_value['ot_price'] = $sv['price'];
                                $attr_value['price']    = $sv['active_price'];
                                $_price = bcsub($sv['price'], $sv['active_price'], 2);
                                if ($value['type']){
                                    $must_price = $must_price > $_price ? $must_price : $_price;
                                } else {
                                    $maxPrice = $maxPrice > $_price ? $maxPrice : $_price;
                                }
                            } else {
                                $attr_value['productSku'] = $sv;
                            }
                            $attrValue[] = $attr_value;
                        }
                    }
                }
            }
            $attr = $make->detailAttr($productAttr);
            if ($type == 'discounts') {
                $sku = $make->detailAttrValue($attrValue, null);
                $value['product']['sku'] = $sku;

            } else {
                $value['product']['attrValue'] = $attrValue;
            }
            $value['product']['attr'] = $attr;
            $price = bcadd($price, bcadd($must_price,$maxPrice,2), 2);
            if ($value['type'] == 1) {
                array_unshift($data,$value);
            }else {
                $data[] = $value;
            }
        }
        return compact('data', 'price');
    }
}


if (!function_exists('systemConfig')) {
    /**
     * 获取系统配置
     *
     * @param string|string[] $key
     * @return mixed
     * @author xaboy
     * @day 2020-05-08
     */
    function systemConfig($key)
    {
        return merchantConfig(0, $key);
    }
}

if (!function_exists('getDatesBetweenTwoDays')) {
    function getDatesBetweenTwoDays($startDate, $endDate)
    {
        $dates = [];
        if (strtotime($startDate) > strtotime($endDate)) {
            //如果开始日期大于结束日期，直接return 防止下面的循环出现死循环
            return $dates;
        } elseif ($startDate == $endDate) {
            //开始日期与结束日期是同一天时
            array_push($dates, date('m-d', strtotime($startDate)));
            return $dates;
        } else {
            array_push($dates, date('m-d', strtotime($startDate)));
            $currentDate = $startDate;
            do {
                $nextDate = date('Y-m-d', strtotime($currentDate . ' +1 days'));
                array_push($dates, date('m-d', strtotime($currentDate . ' +1 days')));
                $currentDate = $nextDate;
            } while ($endDate != $currentDate);
            return $dates;
        }
    }
}

if (!function_exists('getStartModelTime')) {
    function getStartModelTime(string $section)
    {
        switch ($section) {
            case 'today':
            case 'yesterday':
                return date('Y-m-d', strtotime($section));
            case 'week':
                return date('Y-m-d', strtotime('this week'));
            case 'month':
                return date('Y-m-d', strtotime('first Day of this month'));
            case 'year':
                return date('Y-m-d', strtotime('this year 1/1'));
            case 'quarter':
                list($startTime, $endTime) = getMonth();
                return $startTime;
            case 'lately7':
                return date('Y-m-d', strtotime("-7 day"));
            case 'lately30':
                return date('Y-m-d', strtotime("-30 day"));
            default:
                if (strstr($section, '-') !== false) {
                    list($startTime, $endTime) = explode('-', $section);
                    return date('Y-m-d H:i:s', strtotime($startTime));
                }
                return date('Y-m-d H:i:s');
        }
    }
}

if (!function_exists('merchantConfig')) {
    /**
     * 获取商户配置
     *
     * @param int $merId
     * @param string|string[] $key
     * @return mixed
     * @author xaboy
     * @day 2020-05-08
     */
    function merchantConfig(int $merId, $key)
    {
        $request = request();
        $make = app()->make(ConfigValueRepository::class);
        if (is_array($key)) {
            $_key = [];
            $cacheData = [];
            foreach ($key as $v) {
                if ($request->hasCache($merId, $v)) {
                    $cacheData[$v] = $request->getCache($merId, $v);
                } else {
                    $_key[] = $v;
                }
            }
            if (!count($_key)) return $cacheData;
            $data = $make->more($_key, $merId);
            $request->setCache($merId, $data);
            $data += $cacheData;
        } else {
            if ($request->hasCache($merId, $key)) {
                $data = $request->getCache($merId, $key);
            } else {
                $data = $make->get($key, $merId);
                $request->setCache($merId, $key, $data);
            }
        }
        return $data;
    }
}

if (!function_exists('systemGroupData')) {
    /**
     * 获取总后台组合数据配置
     *
     * @param string $key
     * @param int|null $page
     * @param int|null $limit
     * @return array
     * @author xaboy
     * @day 2020/5/27
     */
    function systemGroupData(string $key, ?int $page = null, ?int $limit = 10)
    {
        $make = app()->make(GroupDataRepository::class);
        return $make->groupData($key, 0, $page, $limit);
    }
}

if (!function_exists('merchantGroupData')) {
    /**
     * 获取商户后台组合数据配置
     *
     * @param int $merId
     * @param string $key
     * @param int|null $page
     * @param int|null $limit
     * @return array
     * @author xaboy
     * @day 2020/5/27
     */
    function merchantGroupData(int $merId, string $key, ?int $page = null, ?int $limit = 10)
    {
        $make = app()->make(GroupDataRepository::class);
        return $make->groupData($key, $merId, $page, $limit);
    }
}

if (!function_exists('filter_emoji')) {

    // 过滤掉emoji表情
    function filter_emoji($str)
    {
        $str = preg_replace_callback(    //执行一个正则表达式搜索并且使用一个回调进行替换
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str
        );
        return $str;
    }
}

if (!function_exists('setHttpType')) {

    /**
     * TODO 修改 https 和 http 移动到common
     * @param $url $url 域名
     * @param int $type 0 返回https 1 返回 http
     * @return string
     */
    function setHttpType($url, $type = 0)
    {
        $domainTop = substr($url, 0, 5);
        if ($type) {
            if ($domainTop == 'https') $url = 'http' . substr($url, 5, strlen($url));
        } else {
            if ($domainTop != 'https') $url = 'https:' . substr($url, 5, strlen($url));
        }
        return $url;
    }
}

if (!function_exists('remoteImage')) {

    /**
     * TODO 获取小程序二维码是否生成
     * @param $url
     * @return array
     */
    function remoteImage($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        $result = json_decode($result, true);
        if (is_array($result)) return ['status' => false, 'msg' => $result['errcode'] . '---' . $result['errmsg']];
        return ['status' => true];
    }
}

if (!function_exists('image_to_base64')) {
    /**
     * 获取图片转为base64
     * @param string $avatar
     * @return bool|string
     */
    function image_to_base64($avatar = '', $timeout = 9)
    {
        checkSuffix($avatar);
        try {
            $url = parse_url($avatar);
            $url = $url['host'];
            $header = [
                'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:45.0) Gecko/20100101 Firefox/45.0',
                'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
                'Accept-Encoding: gzip, deflate, br',
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Host:' . $url
            ];
            $dir = pathinfo($url);
            $host = $dir['dirname'];
            $refer = $host . '/';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_REFERER, $refer);
            curl_setopt($curl, CURLOPT_URL, $avatar);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            $data = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($code == 200) {
                return "data:image/jpeg;base64," . base64_encode($data);
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }
}


if (!function_exists('put_image')) {
    /**
     * 获取图片转为base64
     * @param string $avatar
     * @return bool|string
     */
    function put_image($url, $filename = '')
    {

        if ($url == '') {
            return false;
        }
        try {
            if ($filename == '') {

                $ext = pathinfo($url);
                if ($ext['extension'] != "jpg" && $ext['extension'] != "png" && $ext['extension'] != "jpeg") {
                    return false;
                }
                $filename = time() . "." . $ext['extension'];
            }

            //文件保存路径
            ob_start();
            readfile($url);
            $img = ob_get_contents();
            ob_end_clean();
            $path = 'public/uploads/qrcode';
            $fp2 = fopen($path . '/' . $filename, 'a');
            fwrite($fp2, $img);
            fclose($fp2);
            return $path . '/' . $filename;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('path_to_url')) {
    /**
     * 路径转url路径
     * @param $path
     * @return string
     */
    function path_to_url($path)
    {
        return trim(str_replace(DIRECTORY_SEPARATOR, '/', $path), '.');
    }
}

if (!function_exists('tidy_url')) {
    /**
     * 路径转url路径
     * @param $url
     * @param int $http
     * @param string $site
     * @return string
     */
    function tidy_url($url, $http = null, $site = null)
    {
        if (!$site) {
            $site = systemConfig('site_url');
        }
        $url = path_to_url($url);
        if (strpos($url, 'http') === false)
            $url = rtrim($site, '/') . '/' . ltrim($url, '/');

        if (is_null($http)) {
            $http = (parse_url($site)['scheme'] ?? '') == 'https' ? 0 : 1;
        }
        $url = set_http_type($url, $http);
        return $url;
    }
}


if (!function_exists('curl_file_exist')) {
    /**
     * CURL 检测远程文件是否在
     * @param $url
     * @return bool
     */
    function curl_file_exist($url)
    {
        $ch = curl_init();
        try {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            $contents = curl_exec($ch);
            if (preg_match("/404/", $contents)) return false;
            if (preg_match("/403/", $contents)) return false;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}


if (!function_exists('set_http_type')) {
    /**
     * 修改 https 和 http
     * @param $url $url 域名
     * @param int $type 0 返回https 1 返回 http
     * @return string
     */
    function set_http_type($url, $type = 0)
    {
        $domainTop = substr($url, 0, 5);
        if ($type) {
            if ($domainTop == 'https') $url = 'http' . substr($url, 5, strlen($url));
        } else {
            if ($domainTop != 'https') $url = 'https:' . substr($url, 5, strlen($url));
        }
        return $url;
    }
}

if (!function_exists('setSharePoster')) {
    /**
     * TODO 生成分享二维码图片
     * @param array $config
     * @param $path
     * @return array|bool|string
     * @throws Exception
     */
    function setSharePoster($config, $path)
    {
        $imageDefault = array(
            'left' => 0,
            'top' => 0,
            'right' => 0,
            'bottom' => 0,
            'width' => 100,
            'height' => 100,
            'opacity' => 100
        );
        $textDefault = array(
            'text' => '',
            'left' => 0,
            'top' => 0,
            'fontSize' => 32,       //字号
            'fontColor' => '255,255,255', //字体颜色
            'angle' => 0,
        );
        $background = $config['background']; //海报最底层得背景
        if (substr($background, 0, 1) === '/') {
            $background = substr($background, 1);
        }
        $backgroundInfo = getimagesize($background);
        $background = imagecreatefromstring(file_get_contents($background));
        $backgroundWidth = $backgroundInfo[0];  //背景宽度
        $backgroundHeight = $backgroundInfo[1];  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth, $backgroundHeight);
        $color = imagecolorallocate($imageRes, 0, 0, 0);
        imagefill($imageRes, 0, 0, $color);
        imagecopyresampled($imageRes, $background, 0, 0, 0, 0, imagesx($background), imagesy($background), imagesx($background), imagesy($background));
        if (!empty($config['image'])) {
            foreach ($config['image'] as $key => $val) {
                $val = array_merge($imageDefault, $val);
                $info = getimagesize($val['url']);
                $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
                if ($val['stream']) {
                    $info = getimagesizefromstring($val['url']);
                    $function = 'imagecreatefromstring';
                }
                $res = $function($val['url']);
                $resWidth = $info[0];
                $resHeight = $info[1];
                $canvas = imagecreatetruecolor($val['width'], $val['height']);
                imagefill($canvas, 0, 0, $color);
                imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'], $resWidth, $resHeight);
                $val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) - $val['width'] : $val['left'];
                $val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) - $val['height'] : $val['top'];
                imagecopymerge($imageRes, $canvas, $val['left'], $val['top'], $val['right'], $val['bottom'], $val['width'], $val['height'], $val['opacity']); //左，上，右，下，宽度，高度，透明度
            }
        }
        if (isset($config['text']) && !empty($config['text'])) {
            foreach ($config['text'] as $key => $val) {
                $val = array_merge($textDefault, $val);
                list($R, $G, $B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
                $val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) : $val['left'];
                $val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) : $val['top'];
                imagettftext($imageRes, $val['fontSize'], $val['angle'], $val['left'], $val['top'], $fontColor, $val['fontPath'], $val['text']);
            }
        }
        ob_start();
        imagejpeg($imageRes);
        imagedestroy($imageRes);
        $res = ob_get_contents();
        ob_end_clean();
        $key = substr(md5(rand(0, 9999)), 0, 5) . date('YmdHis') . rand(0, 999999) . '.jpg';
        $uploadType = (int)systemConfig('upload_type') ?: 1;
        $upload = UploadService::create($uploadType);
        $res = $upload->to($path)->validate()->stream($res, $key);
        if ($res === false) {
            return $upload->getError();
        } else {
            $info = $upload->getUploadInfo();
            $info['image_type'] = $uploadType;
            return $info;
        }
    }
}

if (!function_exists('getTimes')) {
    function getTimes()
    {
        $dates = [];
        for ($i = 0; $i <= 24; $i++) {
            for ($j = 0; $j < 60; $j++) {
                $dates[] = sprintf('%02.d', $i) . ':' . sprintf('%02.d', $j);
            }
        }
        return $dates;
    }
}

if (!function_exists('monday')) {
    /**
     * 获取周一
     *
     * @param null $time
     * @return false|string
     * @author xaboy
     * @day 2020/6/22
     */
    function monday($time = null)
    {
        return date('Y-m-d', strtotime('Sunday -6 day', $time ?: time()));
    }
}

if (!function_exists('orderLock')) {
    /**
     * @param string $name
     * @return Lock
     * @author xaboy
     * @day 2020/8/25
     */
    function makeLock($name = 'default'): Lock
    {
        return $GLOBALS['_swoole_order_lock'][$name];
    }
}

if (!function_exists('get_crmeb_version')) {
    /**
     * 获取CRMEB系统版本号
     * @param string $default
     * @return string
     */
    function get_crmeb_version($default = 'v1.0.0')
    {
        try {
            $version = parse_ini_file(app()->getRootPath() . '.version');
            return $version['version'] ?? $default;
        } catch (Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('get_crmeb_version_code')) {
    /**
     * 获取CRMEB系统版本号
     * @param string $default
     * @return string
     */
    function get_crmeb_version_code($default = '1.7.2')
    {
        try {
            $version = parse_ini_file(app()->getRootPath() . '.version');
            return $version['code'] ?? $default;
        } catch (Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('update_crmeb_compiled')) {
    /**
     * 获取CRMEB系统版本号
     * @param string $default
     * @return string
     */
    function update_crmeb_compiled($default = 'v1.0.0')
    {
        $compiled = [
            '7.1' => 'compiled71',
            '7.2' => 'compiled72',
            '7.3' => 'compiled73',
            '7.4' => 'compiled74',
        ];

        $phpv = @phpversion();
        $phpvs = substr($phpv, 0, 3);
        $key = $compiled[$phpvs] ?? '';
        if (!$key)
            return false;
        $root = app()->getRootPath();
        $compiledPath = $root . 'install/compiled';
        $file = $root . 'install/compiled/' . $key . '.zip';
        $toPath = $root . 'crmeb/basic';
        $toConfigPath = $root . 'config/crmeb.php';
        $demoImage = $root.'public/uploads'.'images.zip';
        try {
            if (is_file($file)) {
                $zip = new ZipArchive();
                if ($zip->open($file) === true) {
                    $zip->extractTo($compiledPath . '/');
                    $zip->close();
                }
                if (is_dir($compiledPath . '/basic')) {
                    if (is_dir($toPath) || mkdir($toPath, 0777) || is_dir($toPath)) {
                        foreach (glob($compiledPath . '/basic/*') as $item) {
                            @rename($item, $toPath . '/' . pathinfo($item, PATHINFO_BASENAME));
                        }
                    }
                    @rmdir($compiledPath . '/basic');
                }
                if (is_file($compiledPath . '/crmeb.php')) {
                    @rename($compiledPath . '/crmeb.php', $toConfigPath);
                }
            }
        } catch (\Exception $exception) {
            return false;
        }
        try{
            if (is_file($demoImage)) {
                $zip = new ZipArchive();
                if ($zip->open($demoImage) === true) {
                    $zip->extractTo($compiledPath . '/');
                    $zip->close();
                }
            }
        }catch (\Exception $exception) {

        }
        return true;
    }
}

if (!function_exists('attr_format')) {
    /**
     * 格式化属性
     * @param $arr
     * @return array
     */
    function attr_format($arr)
    {
        $data = [];
        $res = [];
        $count = count($arr);
        if ($count > 1) {
            for ($i = 0; $i < $count - 1; $i++) {
                if ($i == 0) $data = $arr[$i]['detail'];
                //替代变量1
                $rep1 = [];
                foreach ($data as $v) {
                    foreach ($arr[$i + 1]['detail'] as $g) {
                        //替代变量2
                        $rep2 = ($i != 0 ? '' : $arr[$i]['value'] . '_$_') . $v . '-$-' . $arr[$i + 1]['value'] . '_$_' . $g;
                        $tmp[] = $rep2;
                        if ($i == $count - 2) {
                            foreach (explode('-$-', $rep2) as $k => $h) {
                                //替代变量3
                                $rep3 = explode('_$_', $h);
                                //替代变量4
                                $rep4['detail'][$rep3[0]] = isset($rep3[1]) ? $rep3[1] : '';
                            }
                            if ($count == count($rep4['detail']))
                                $res[] = $rep4;
                        }
                    }
                }
                $data = isset($tmp) ? $tmp : [];
            }
        } else {
            $dataArr = [];
            foreach ($arr as $k => $v) {
                foreach ($v['detail'] as $kk => $vv) {
                    $dataArr[$kk] = $v['value'] . '_' . $vv;
                    $res[$kk]['detail'][$v['value']] = $vv;
                }
            }
            $data[] = implode('-', $dataArr);
        }
        return [$data, $res];
    }
}

if (!function_exists('filter_emoji')) {
    //过滤掉emoji表情
    function filter_emoji($str)
    {
        $str = preg_replace_callback('/./u', function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        }, $str);
        return $str;
    }
}

/*
 * TODO 腾讯地图转换百度地图 GCJ02 转 BD09
 * 中国正常GCJ02坐标---->百度地图BD09坐标
 * 腾讯地图/高德地图用的也是GCJ02坐标
 * @param double $lat 纬度
 * @param double $lng 经度
 */
if (!function_exists('gcj02ToBd09')) {
    function gcj02ToBd09($lng, $lat)
    {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng;
        $y = $lat;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);

        $lng = $z * cos($theta) + 0.0065;
        $lat = $z * sin($theta) + 0.006;
        return [$lng,$lat];
    }
}

if (!function_exists('lbs_address')) {
    function lbs_address($region, $address)
    {
        $locationOption = new \Joypack\Tencent\Map\Bundle\AddressOption(systemConfig('tx_map_key'));
        $locationOption->setAddress($address);
        $locationOption->setRegion($region);
        $location = new \Joypack\Tencent\Map\Bundle\Address($locationOption);
        $res = $location->request();
        if ($res->error) {
            throw new \think\exception\ValidateException($res->error);
        }
        if ($res->status) {
            throw new \think\exception\ValidateException($res->message);
        }
        if (!$res->result) {
            throw new \think\exception\ValidateException('获取失败');
        }
        return $res->result;
    }
}


if (!function_exists('aj_captcha_check_one')) {
    /**
     * 验证滑块1次验证
     * @param string $token
     * @param string $pointJson
     * @return bool
     */
    function aj_captcha_check_one(string $captchaType, string $token, string $pointJson)
    {
        aj_get_serevice($captchaType)->check($token, $pointJson);
        return true;
    }
}

if (!function_exists('aj_captcha_check_two')) {
    /**
     * 验证滑块2次验证
     * @param string $token
     * @param string $pointJson
     * @return bool
     */
    function aj_captcha_check_two(string $captchaType, string $captchaVerification )
    {
        aj_get_serevice($captchaType)->verificationByEncryptCode($captchaVerification);
        return true;
    }
}


if (!function_exists('aj_captcha_create')) {
    /**
     * 创建验证码
     * @return array
     */
    function aj_captcha_create(string $captchaType)
    {
        return aj_get_serevice($captchaType)->get();
    }
}

if (!function_exists('aj_get_serevice')) {

    function aj_get_serevice(string $captchaType)
    {
        $config = \think\facade\Config::get('ajcaptcha');
        switch ($captchaType) {
            case "clickWord":
                $service = new \Fastknife\Service\ClickWordCaptchaService($config);
                break;
            case "blockPuzzle":
//                $service = new \Fastknife\Service\BlockPuzzleCaptchaService($config);
                $service = new  \crmeb\services\BlockPuzzleCaptchaService($config);
                break;
            default:
                throw new \think\exception\ValidateException('captchaType参数不正确：'.$captchaType);
        }
        return $service;
    }
}

if (!function_exists('checkSuffix')) {
    function checkSuffix($data)
    {
        $suffix = \think\facade\Config::get('upload.fileExt');
        if (is_array($data)){
            foreach ($data as $datum) {
                if (strpos($datum,'phar://') !== false)
                    throw new \think\exception\ValidateException('操作失败');
                $result = pathinfo($datum);
                if (isset($result['extension']) && !in_array($result['extension'],$suffix)) {
                    throw new \think\exception\ValidateException('文件后缀不允许');
                }
            }
        } else {
            if (strpos($data,'phar://') !== false )
                throw new \think\exception\ValidateException('操作失败');
            $result = pathinfo($data);
            if (isset($result['extension']) && !in_array($result['extension'],$suffix)) {
                throw new \think\exception\ValidateException('文件后缀不允许');
            }
        }
        return ;
    }
}



