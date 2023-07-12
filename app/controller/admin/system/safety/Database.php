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

namespace app\controller\admin\system\safety;

use crmeb\exceptions\UploadFailException;
use crmeb\services\MysqlBackupService;
use think\App;
use crmeb\basic\BaseController;
use think\facade\Db;
use think\facade\Env;

class Database extends BaseController
{

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $config = array(
            'level' => 5,//数据库备份卷大小
            'compress' => 1,//数据库备份文件是否启用压缩 0不压缩 1 压缩
            );
        $this->service = new MysqlBackupService($config);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/21
     * @return mixed
     */
    public function lst()
    {
        return app('json')->success( $this->service->dataList());
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/21
     * @return mixed
     */
    public function fileList()
    {
        $files = $this->service->fileList();
        $data = [];
        foreach ($files as $key => $t) {
            $data[] = [
                'filename' => $t['filename'],
                'part' => $t['part'],
                'size' => $t['size'] . 'B',
                'compress' => $t['compress'],
                'backtime' => $key,
                'time' => $t['time'],
            ];
        }
       // krsort($data);//根据时间降序

        return app('json')->success($data);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/21
     * @param $name
     * @return mixed
     */
    public function detail($name)
    {
        $database = Env::get("database.database");
        $result = Db::query("select COLUMN_NAME,COLUMN_TYPE,COLUMN_DEFAULT,IS_NULLABLE,EXTRA,COLUMN_COMMENT from information_schema.columns where table_name = '" . $name . "' and table_schema = '" . $database . "'");
        return app('json')->success($result);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/21
     * @param $name
     * @return mixed
     */
    public function backups($name)
    {
        $data = [];
        if(is_array($name)){
            foreach($name as $item){
                if(!$this->detail($item))
                    return app('json')->fail('不存在的表名');
                $res = $this->service->backup($item,0);
                if ($res == false && $res != 0) {
                    $data .= $item . '|';
                }
            }
        }
        if($data) return app('json')->fail('备份失败' . $data);
        return app('json')->success('备份成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/21
     * @param $name
     * @return mixed
     */
    public function optimize($name)
    {
        $this->service->optimize($name);
        return app('json')->success('优化成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/21
     * @param $name
     * @return mixed
     */
    public function repair($name)
    {
        foreach ($name as $item){
            $this->service->repair($item);
        }
        return app('json')->success('修复成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/25
     * @return \think\response\File
     */
    public function downloadFile()
    {
        try {
            $time = intval($this->request->param('feilname'));
            $file =$this->service->getFile('time', $time);
            $fileName = $file[0];
            return  download($fileName,$time);
        }catch (UploadFailException $e){
            return app('json')->fail('下载失败');
        }
    }

    public function deleteFile()
    {
        $feilname = intval($this->request->param('feilname'));
        $files = $this->service->delFile($feilname);
        return app('json')->success('删除成功');
    }

}
