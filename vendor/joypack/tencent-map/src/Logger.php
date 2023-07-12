<?php
namespace Joypack\Tencent\Map;

/**
 * 腾讯位置服务
 * 日志管理
 */
class Logger
{
    protected $rootPath;
    
    protected $development = false;
    
    public function __construct($root_path, $development)
    {
        $this->development = $development;
        
        if($root_path) {
            if(is_dir($root_path)) {
                $this->rootPath = $root_path;
            } else {
                if(@mkdir($root_path, 0775, true)) {
                    $this->rootPath = $root_path;
                }
            }
        }
    }
    
    public function __toString()
    {
        return __CLASS__;
    }
    
    /**
     * 写入 debug 日志
     * @param string $message
     * @param string | array $data
     */
    public function debug($message, $data=null)
    {
        $this->save($message, $data, 'DEBUG');
    }
    
    /**
     * 写入 INFO 日志
     * @param string $message
     * @param string | array $data
     */
    public function info($message, $data=null)
    {
        $this->save($message, $data, 'INFO');
    }
    
    /**
     * 写入 ERROR 日志
     * @param string $message
     * @param string | array $data
     */
    public function error($message, $data=null)
    {
        $this->save($message, $data, 'ERROR');
    }
    
    /**
     * 打印变量
     * @param mixed $args 打印列表
     * 最后一个元素如果是 true 则 exit
     */
    public function print(...$args)
    {
        $args = func_get_args();
        
        $length = count($args);
        
        $exit = false;
        
        if(is_bool($last_argument = $args[$length-1])) {
            if($last_argument) {
                $exit = true;
                array_pop($args);
            }
        }
        
        echo '<pre>';
        while ($argument = array_shift($args)) {
            print_r($argument);
            echo '<br/><br/>';
        }
        echo '</pre>';
        
        if($exit) {
            exit;
        }
    }
    
    protected function save($message, $data, $level)
    {
        if(is_null($this->rootPath)) {
            return;
        }
        
        // 生产环境时只记录错误信息
        if(!$this->development) {
            if($level != 'ERROR') {
                return;
            }
        }
        
        $date = date('Y-m-d');
        
        $now = date('Y-m-d H:i:s');
        
        $filename = "{$this->rootPath}/{$date}.log";
        
        if(is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        @file_put_contents($filename, "[{$level}] {$now} {$message} {$data}\r\n", FILE_APPEND);
    }
}