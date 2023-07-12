<?php
namespace Joypack\Tencent\Map;

/**
 * 腾讯位置服务
 * 接口请求类
 */
class Request
{
    // 接口地址
    protected $url = 'https://apis.map.qq.com';
    
    protected $query = [];
    
    protected $field = [];
    
    public $logger;
    
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }
    
    public function uri($uri)
    {
        $this->url .= '/' . trim($uri, '/');
        return $this;
    }
    
    public function query($name, $value=null)
    {
        if(is_array($name)) {
            $this->query = array_merge($this->query, $name);
        } else {
            $this->query[$name] = $value;
        }
        return $this;
    }
    
    public function field($name, $value=null)
    {
        if(is_array($name)) {
            $this->field = array_merge($this->field, $name);
        } else {
            $this->field[$name] = $value;
        }
        return $this;
    }
    
    /**
     * method get
     * @param array $query
     * @return Response
     */
    public function get(array $query=[])
    {
        if($query) {
            $this->query($query);
        }
        
        $url = $this->mergeQuery($this->url, $this->query);
        
        return $this->create($url);
    }
    
    public function post(array $fields=[])
    {
        if($fields) {
            $this->field($fields);
        }
        
        $url = $this->mergeQuery($this->url, $this->query);
        
        return $this->create($url, 'POST', $this->field);
    }
    
    /**
     * <p>将参数合并到 URL</p>
     * @param string $url 请求的地址
     * @param array $query 请求参数
     * @param bool $recursive 是否递归合并
     * @return mixed
     */
    protected function mergeQuery($url, array $query, bool $recursive=false)
    {
        if(empty($url)) {
            return null;
        }
        
        // 没有设置参数时直接返回地址
        if(empty($query)) {
            return $url;
        }
        
        $parsed = parse_url($url);
        
        // 合并参数
        if(isset($parsed['query'])) {
            $url = substr($url, 0, strpos($url, '?'));
            
            $str_parsed = [];
            
            parse_str($parsed['query'], $str_parsed);
            
            if($recursive) {
                $query = array_merge_recursive($str_parsed, $query);
            } else {
                $query = array_merge($str_parsed, $query);
            }
        } else {
            $url = rtrim($url, '/?');
        }
        
        // 生成 query 字符串
        $url = "{$url}?" . http_build_query($query);
        
        return $url;
        
        // 处理锚点
        if(isset($parsed['fragment'])) {
            $url .= "#{$parsed['fragment']}";
        }
        
        return $url;
    }
    
    /**
     * <p>创建请求</p>
     * @param string $url 请求地址
     * @param string $method 请求方式
     * @param array $data POST 数据
     * @return Response
     */
    protected function create($url, $data=null)
    {
        //$referer = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['SERVER_NAME']}";
        
        //$header = [
            //"CLIENT-IP: {$_SERVER['REMOTE_ADDR']}",
            //"X-FORWARDED-FOR: {$_SERVER['REMOTE_ADDR']}",
            //"Content-Type: application/json; charset=utf-8",
            //"Accept: */*"
        //];
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        //curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        if(!is_null($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            if($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $original = curl_exec($ch);
        
        $error = null;
        if($errno = curl_errno($ch)) {
            $error = curl_error($ch);
        }
        
        curl_close($ch);
        
        $this->logger->info('请求地址', $url);
        
        if($data) {
            $this->logger->info('请求数据', $data);
        }
        
        $this->logger->info('响应数据', $original);
        
        return new Response($errno, $error, $original, $this->logger);
    }
}