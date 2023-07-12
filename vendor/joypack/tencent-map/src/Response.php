<?php
namespace Joypack\Tencent\Map;

/**
 * 腾讯位置服务
 * 接口响应类
 */
class Response
{
    public $error;
    
    public $logger;
    
    protected $original;
    
    protected $decode;
    
    public function __construct($errno, $error, $original, Logger $logger)
    {
        $this->logger = $logger;
        
        // 仅成功时
        if(0 === $errno) {
            $decode = json_decode($original, true);
            if(is_null($decode)) {
                // 错误
                $this->setErrorMessage(99);
                // 写入日志
                $logger->error('解析失败');
            } else {
                $this->original = $original;
                $this->decode = $decode;
            }
        } else {
            // 错误
            $this->setErrorMessage($errno);
            // 写入日志
            $logger->error($error);
        }
    }
    
    /**
     * 返回json
     * @return string
     */
    public function getOriginal()
    {
        return $this->original;
    }
    
    /**
     * 返回数组
     * @return array
     */
    public function toArray()
    {
        return $this->decode;
    }
    
    /**
     * 获得某属性时
     * @param string $prop_name
     * @return mixed
     */
    public function __get($property)
    {
        return $this->decode[ $property ] ?? null;
    }
    
    public function __toString()
    {
        return $this->original;
    }
    
    protected function setErrorMessage($errno)
    {
        $errors = [
            1=> 'UNSUPPORTED_PROTOCOL',
            2=> 'FAILED_INIT',
            3=> 'URL_MALFORMAT',
            4=> 'URL_MALFORMAT_USER',
            5=> 'COULDNT_RESOLVE_PROXY',
            6=> 'COULDNT_RESOLVE_HOST',
            7=> 'COULDNT_CONNECT',
            8=> 'FTP_WEIRD_SERVER_REPLY',
            9=> 'REMOTE_ACCESS_DENIED',
            11=> 'FTP_WEIRD_PASS_REPLY',
            13=> 'FTP_WEIRD_PASV_REPLY',
            14=>'FTP_WEIRD_227_FORMAT',
            15=> 'FTP_CANT_GET_HOST',
            17=> 'FTP_COULDNT_SET_TYPE',
            18=> 'PARTIAL_FILE',
            19=> 'FTP_COULDNT_RETR_FILE',
            21=> 'QUOTE_ERROR',
            22=> 'HTTP_RETURNED_ERROR',
            23=> 'WRITE_ERROR',
            25=> 'UPLOAD_FAILED',
            26=> 'READ_ERROR',
            27=> 'OUT_OF_MEMORY',
            28=> 'OPERATION_TIMEDOUT',
            30=> 'FTP_PORT_FAILED',
            31=> 'FTP_COULDNT_USE_REST',
            33=> 'RANGE_ERROR',
            34=> 'HTTP_POST_ERROR',
            35=> 'SSL_CONNECT_ERROR',
            36=> 'BAD_DOWNLOAD_RESUME',
            37=> 'FILE_COULDNT_READ_FILE',
            38=> 'LDAP_CANNOT_BIND',
            39=> 'LDAP_SEARCH_FAILED',
            41=> 'FUNCTION_NOT_FOUND',
            42=> 'ABORTED_BY_CALLBACK',
            43=> 'BAD_FUNCTION_ARGUMENT',
            45=> 'INTERFACE_FAILED',
            47=> 'TOO_MANY_REDIRECTS',
            48=> 'UNKNOWN_TELNET_OPTION',
            49=> 'TELNET_OPTION_SYNTAX',
            51=> 'PEER_FAILED_VERIFICATION',
            52=> 'GOT_NOTHING',
            53=> 'SSL_ENGINE_NOTFOUND',
            54=> 'SSL_ENGINE_SETFAILED',
            55=> 'SEND_ERROR',
            56=> 'RECV_ERROR',
            58=> 'SSL_CERTPROBLEM',
            59=> 'SSL_CIPHER',
            60=> 'SSL_CACERT',
            61=> 'BAD_CONTENT_ENCODING',
            62=> 'LDAP_INVALID_URL',
            63=> 'FILESIZE_EXCEEDED',
            64=> 'USE_SSL_FAILED',
            65=> 'SEND_FAIL_REWIND',
            66=> 'SSL_ENGINE_INITFAILED',
            67=> 'LOGIN_DENIED',
            68=> 'TFTP_NOTFOUND',
            69=> 'TFTP_PERM',
            70=> 'REMOTE_DISK_FULL',
            71=> 'TFTP_ILLEGAL',
            72=> 'TFTP_UNKNOWNID',
            73=> 'REMOTE_FILE_EXISTS',
            74=> 'TFTP_NOSUCHUSER',
            75=> 'CONV_FAILED',
            76=> 'CONV_REQD',
            77=> 'SSL_CACERT_BADFILE',
            78=> 'REMOTE_FILE_NOT_FOUND',
            79=> 'SSH',
            80=> 'SSL_SHUTDOWN_FAILED',
            81=> 'AGAIN',
            82=> 'SSL_CRL_BADFILE',
            83=> 'SSL_ISSUER_ERROR',
            84=> 'FTP_PRET_FAILED',
            84=> 'FTP_PRET_FAILED',
            85=> 'RTSP_CSEQ_ERROR',
            86=> 'RTSP_SESSION_ERROR',
            87=> 'FTP_BAD_FILE_LIST',
            88=> 'CHUNK_FAILED',
            99=> 'DECODE_ERROR',
        ];
        $this->error = $errors[ $errno ] ?? 'UNKNOWN_ERROR';
    }
}