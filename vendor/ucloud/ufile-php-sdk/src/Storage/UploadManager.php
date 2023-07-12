<?php

namespace UCloud\Storage;

use UCloud;
use UCloud\Auth;
use UCloud\ActionType;
use UCloud\Http\Error;
use UCloud\Http\Client;
use UCloud\Http\Request;

class UploadManager {
    private $auth;

    public function __construct(Auth $auth = null) {
        if ($auth === null) {
            $auth = new Auth();
        }
        $this->auth = $auth;
    }

    public function PutFile($bucket, $key, $file) {
        $action_type = ActionType::PUTFILE;
        $err = \UCloud\CheckConfig($this->auth, ActionType::PUTFILE);
        if ($err != null) {
            return array(null, $err);
        }

        $f = @fopen($file, "r");
        if (!$f) return array(null, new Error(-1, -1, "open $file error"));

        $host = $bucket . $this->auth->ProxySuffix;
        $path = $key;
        $content  = @fread($f, filesize($file));
        list($mimetype, $err) = \UCloud\GetFileMimeType($file);
        if ($err) {
            fclose($f);
            return array("", $err);
        }
        $req = new Request('PUT', array('host'=>$host, 'path'=>$path), $content, $bucket, $key, $action_type);
        $req->Header['Expect'] = '';
        $req->Header['Content-Type'] = $mimetype;

        $client = new Client($this->auth, $mimetype);
        list($data, $err) = $client->Call($req);
        fclose($f);
        return array($data, $err);
    }

    //------------------------------表单上传------------------------------
    public function MultipartForm($bucket, $key, $file) {
        $action_type = ActionType::POSTFILE;
        $err = \UCloud\CheckConfig($this->auth, ActionType::POSTFILE);
        if ($err != null) {
            return array(null, $err);
        }

        $f = @fopen($file, "r");
        if (!$f) return array(null, new Error(-1, -1, "open $file error"));

        $host = $bucket . $this->auth->ProxySuffix;
        $path = "";
        $fsize = filesize($file);
        $content = "";
        if ($fsize != 0) {
            $content = @fread($f, filesize($file));
            if ($content == FALSE) {
                fclose($f);
                return array(null, new Error(0, -1, "read file error"));
            }
        }
        list($mimetype, $err) = \UCloud\GetFileMimeType($file);
        if ($err) {
            fclose($f);
            return array("", $err);
        }

        $req = new Request('POST', array('host'=>$host, 'path'=>$path), $content, $bucket, $key, $action_type);
        $req->Header['Expect'] = '';
        $token = $this->auth->SignRequest($req, $mimetype);

        $fields = array('Authorization'=>$token, 'FileName' => $key);
        $files  = array('files'=>array('file', $file, $content, $mimetype));

        $client = new Client($this->auth, Config::NO_AUTH_CHECK);
        list($data, $err) = $client->CallWithMultipartForm($client, $req, $fields, $files);
        fclose($f);
        return array($data, $err);
    }

    //------------------------------分片上传------------------------------
    public function MInit($bucket, $key) {
        $err = \UCloud\CheckConfig($this->auth, ActionType::MINIT);
        if ($err != null) {
            return array(null, $err);
        }

        $host = $bucket . $this->auth->ProxySuffix;
        $path = $key;
        $querys = array(
            "uploads" => ""
        );
        $req = new Request('POST', array('host'=>$host, 'path'=>$path, 'query'=>$querys), null, $bucket, $key);
        $req->Header['Content-Type'] = 'application/x-www-form-urlencoded';

        $client = new Client($this->auth);
        return $client->Call($req);
    }
    //@results: (tagList, err)
    function MUpload($bucket, $key, $file, $uploadId, $blkSize, $partNumber=0) {
        $err = \UCloud\CheckConfig($this->auth, ActionType::MUPLOAD);
        if ($err != null) {
            return array(null, $err);
        }

        $f = @fopen($file, "r");
        if (!$f) {
            return array(null, new Error(-1, -1, "open $file error"));
        }

        $etagList = array();
        list($mimetype, $err) = \UCloud\GetFileMimeType($file);
        if ($err) {
            fclose($f);
            return array("", $err);
        }
        $client = new Client($this->auth);
        for(;;) {
            $host = $bucket . $this->auth->ProxySuffix;
            $path = $key;
            if (@fseek($f, $blkSize*$partNumber, SEEK_SET) < 0) {
                fclose($f);
                return array(null, new Error(0, -1, "fseek error"));
            }
            $content = @fread($f, $blkSize);
            if ($content == FALSE) {
                if (feof($f)) break;
                fclose($f);
                return array(null, new Error(0, -1, "read file error"));
            }

            $querys = array(
                "uploadId" => $uploadId,
                "partNumber" => $partNumber
            );
            $req = new Request('PUT', array('host'=>$host, 'path'=>$path, 'query'=>$querys), $content, $bucket, $key);
            $req->Header['Content-Type'] = $mimetype;
            $req->Header['Expect'] = '';
            list($data, $err) = $client->Call($req);
            if ($err) {
                fclose($f);
                return array(null, $err);
            }
            $etag = @$data['ETag'];
            $part = @$data['PartNumber'];
            if ($part != $partNumber) {
                fclose($f);
                return array(null, new Error(0, -1, "unmatch partnumber"));
            }
            $etagList[] = $etag;
            $partNumber += 1;
        }
        fclose($f);
        return array($etagList, null);
    }

    function MFinish($bucket, $key, $uploadId, $etagList, $newKey = '') {
        $err = \UCloud\CheckConfig($this->auth, ActionType::MFINISH);
        if ($err != null) {
            return array(null, $err);
        }


        $host = $bucket . $this->auth->ProxySuffix;
        $path = $key;
        $querys = array(
            'uploadId' => $uploadId,
            'newKey' => $newKey,
        );

        $body = @implode(',', $etagList);
        $req = new Request('POST', array('host'=>$host, 'path'=>$path, 'query'=>$querys), $body, $bucket, $key);
        $req->Header['Content-Type'] = 'text/plain';

        $client = new Client($this->auth);
        return $client->Call($req);
    }

    function MCancel($bucket, $key, $uploadId) {
        $err = \UCloud\CheckConfig($this->auth, ActionType::MCANCEL);
        if ($err != null) {
            return array(null, $err);
        }


        $host = $bucket . $this->auth->ProxySuffix;
        $path = $key;
        $querys = array(
            'uploadId' => $uploadId
        );

        $req = new Request('DELETE', array('host'=>$host, 'path'=>$path, 'query'=>$querys), null, $bucket, $key);
        $req->Header['Content-Type'] = 'application/x-www-form-urlencoded';

        $client = new Client($this->auth);
        return $client->Call($req);
    }

    //------------------------------秒传------------------------------
    function UploadHit($bucket, $key, $file) {
        $err = \UCloud\CheckConfig($this->auth, ActionType::UPLOADHIT);
        if ($err != null) {
            return array(null, $err);
        }

        $f = @fopen($file, "r");
        if (!$f) return array(null, new Error(-1, -1, "open $file error"));

        $content = "";
        $fileSize = filesize($file);
        if ($fileSize != 0) {
            $content  = @fread($f, $fileSize);
            if ($content == FALSE) {
                fclose($f);
                return array(null, new Error(0, -1, "read file error"));
            }
        }
        list($fileHash, $err) = \UCloud\FileHash($file);
        if ($err) {
            fclose($f);
            return array(null, $err);
        }
        fclose($f);


        $host = $bucket . $this->auth->ProxySuffix;
        $path = "uploadhit";
        $querys = array(
            'Hash' => $fileHash,
            'FileName' => $key,
            'FileSize' => $fileSize
        );

        $req = new Request('POST', array('host'=>$host, 'path'=>$path, 'query'=>$querys), null, $bucket, $key);
        $req->Header['Content-Type'] = 'application/x-www-form-urlencoded';

        $client = new Client($this->auth);
        return $client->Call($req);
    }

    //------------------------------删除文件------------------------------
    function Delete($bucket, $key) {
        $err = \UCloud\CheckConfig($this->auth, ActionType::DELETE);
        if ($err != null) {
            return array(null, $err);
        }

        $host = $bucket . $this->auth->ProxySuffix;
        $path = "$key";

        $req = new Request('DELETE', array('host'=>$host, 'path'=>$path), null, $bucket, $key);
        $req->Header['Content-Type'] = 'application/x-www-form-urlencoded';

        $client = new Client($this->auth);
        return $client->Call($req);
    }

    //------------------------------生成公有文件Url------------------------------
    // @results: $url
    public function MakePublicUrl($bucket, $key) {
        return $bucket . $this->auth->ProxySuffix . "/" . rawurlencode($key);
    }
    //------------------------------生成私有文件Url------------------------------
    // @results: $url
    public function MakePrivateUrl($bucket, $key, $expires = 0) {
        $err = \UCloud\CheckConfig($this->auth, ActionType::GETFILE);
        if ($err != null) {
            return array(null, $err);
        }

        $public_url = $this->MakePublicUrl($bucket, $key);
        $req = new Request('GET', array('path'=>$public_url), null, $bucket, $key);
        if ($expires > 0) {
            $req->Header['Expires'] = $expires;
        }

        $temp = $this->Auth->SignRequest($req, null, Config::QUERY_STRING_CHECK);
        $signature = substr($temp, -28, 28);
        $url = $public_url . "?UCloudPublicKey=" . rawurlencode($this->auth->PublicKey) . "&Signature=" . rawurlencode($signature);
        if ('' != $expires) {
            $url .= "&Expires=" . rawurlencode($expires);
        }
        return $url;
    }
}
