<?php
namespace Joypack\Tencent\Map;

/**
 * 腾讯位置服务
 * 公共参数
 */
class Option
{
    const OUTPUT_JSON = 'json';
    const OUTPUT_JSONP = 'jsonp';
    
    protected $option = [];
    
    protected $secret;
    
    public function __construct($key=null, $secret=null)
    {
        $this->setKey($key);
        $this->setSecret($secret);
    }
    
    public function setSecret($value)
    {
        $this->secret = $value;
    }
    
    /**
     * 开发密钥
     * @param string $value
     */
    public function setKey($value)
    {
        $this->option['key'] = $value;
    }
    
    /**
     * 返回格式：支持JSON/JSONP，默认JSON
     * @param string $value
     */
    public function setOutput($value=self::OUTPUT_JSON)
    {
        $this->option['output'] = $value;
    }
    
    /**
     * JSONP方式回调函数
     * @param string $value
     */
    public function setCallback($value)
    {
        $this->option['callback'] = $value;
    }
    
    /**
     * 签名
     * @param string $uri
     */
    public function setSig($uri)
    {
        $this->option['sig'] = $this->buildSig($uri, $this->getAll());
    }
    
    /**
     * 获得所有参数
     * @return array
     */
    public function getAll()
    {
        return $this->option;
    }
    
    /**
     * 生成签名
     * @param string $uri
     * @return string
     */
    protected function buildSig($uri, $option)
    {
        ksort($option);
        
        $pieces = [];
        foreach ($option as $key => $val)
        {
            $pieces[] = "{$key}={$val}";
        }
        
        $str = sprintf('%s?%s', rtrim($uri, '/'), implode('&', $pieces));
        
        /*
         echo '<pre>';
         print_r("{$str}{$this->secret}");
         die;
         //*/
        
        return md5("{$str}{$this->secret}");
    }
}