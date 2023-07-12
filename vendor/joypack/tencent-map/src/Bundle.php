<?php
namespace Joypack\Tencent\Map;

/**
 * 腾讯位置服务
 * 基础类
 */
class Bundle
{
    // 参数实例
    protected $option;
    
    // 请求实例
    protected $request;
    
    // 日志实例
    public $logger;
    
    public function __construct(Option $option, $log_root=null, $development=false)
    {
        $log_root = rtrim($log_root, '/\\');
        
        // 参数实例
        $this->option = $option;
        // 实例化日志
        $this->logger = new Logger("{$log_root}/joypack-tencent-map", $development);
        // 实例化请求
        $this->request = new Request($this->logger);
    }
}