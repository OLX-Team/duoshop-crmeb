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


namespace app\controller\admin\system\config;


use app\common\repositories\system\CacheRepository;
use app\common\repositories\system\config\ConfigClassifyRepository;
use app\common\repositories\system\config\ConfigRepository;
use app\validate\admin\ConfigValidate;
use crmeb\basic\BaseController;
use crmeb\services\FileService;
use crmeb\services\UploadService;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Log;

/**
 * Class Config
 * @package app\controller\admin\system\config
 * @author xaboy
 * @day 2020-03-27
 */
class Config extends BaseController
{
    /**
     * @var ConfigRepository
     */
    protected $repository;

    /**
     * Config constructor.
     * @param App $app
     * @param ConfigRepository $repository
     */
    public function __construct(App $app, ConfigRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-03-31
     */
    public function lst()
    {
        $where = $this->request->params(['keyword', 'config_classify_id', 'user_type']);
        [$page, $limit] = $this->getPage();
        $lst = $this->repository->lst($where, $page, $limit);

        return app('json')->success($lst);
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-03-31
     */
    public function createTable()
    {
        $form = $this->repository->form();
        return app('json')->success(formToData($form));
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-03-31
     */
    public function updateTable($id)
    {
        if (!$this->repository->exists($id)) app('json')->fail('数据不存在');
        $form = $this->repository->updateForm($id);
        return app('json')->success(formToData($form));
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-03-31
     */
    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0);
        if (!$this->repository->exists($id))
            return app('json')->fail('分类不存在');
        $this->repository->switchStatus($id, $status == 1 ? 1 : 0);
        return app('json')->success('修改成功');
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-03-27
     */
    public function get($id)
    {
        $data = $this->repository->get($id);
        if (!$data)
            return app('json')->fail('配置不存在');
        else
            return app('json')->success($data->hidden(['mer_id', 'value']));
    }

    /**
     * @param ConfigValidate $validate
     * @param ConfigClassifyRepository $configClassifyRepository
     * @return mixed
     * @author xaboy
     * @day 2020-03-27
     */
    public function create(ConfigValidate $validate, ConfigClassifyRepository $configClassifyRepository)
    {
        $data = $this->request->params(['user_type', 'config_classify_id', 'config_name', 'config_props', 'config_key', 'config_type', 'config_rule', 'required', 'info', 'sort', 'status']);
        $validate->check($data);
        if (!$configClassifyRepository->exists($data['config_classify_id']))
            return app('json')->fail('配置分类不已存在');
        if ($this->repository->keyExists($data['config_key']))
            return app('json')->fail('配置key已存在');

        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * @param int $id
     * @param ConfigValidate $validate
     * @param ConfigClassifyRepository $configClassifyRepository
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-03-27
     */
    public function update($id, ConfigValidate $validate, ConfigClassifyRepository $configClassifyRepository)
    {
        $data = $this->request->params(['user_type', 'config_classify_id', 'config_name', 'config_props', 'config_key', 'config_type', 'config_rule', 'required', 'info', 'sort', 'status']);
        $validate->check($data);

        if (!$this->repository->exists($id))
            return app('json')->fail('分类不存在');
        if (!$configClassifyRepository->exists($data['config_classify_id']))
            return app('json')->fail('配置分类不已存在');
        if ($this->repository->keyExists($data['config_key'], $id))
            return app('json')->fail('配置key已存在');
        $this->repository->update($id, $data);
        return app('json')->success('修改成功');
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-03-27
     */
    public function delete($id)
    {
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    /**
     * @param string $key
     * @param ConfigClassifyRepository $configClassifyRepository
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-22
     */
    public function form($key, ConfigClassifyRepository $configClassifyRepository)
    {
        if (!$configClassifyRepository->keyExists($key) || !($configClassfiy = $configClassifyRepository->keyByData($key)))
            return app('json')->fail('配置分类不存在');
        if ($configClassifyRepository->existsWhere(['pid' => $configClassfiy->config_classify_id]))
            $form = $this->repository->tabForm($configClassfiy, $this->request->merId());
        else
            $form = $this->repository->cidByFormRule($configClassfiy, $this->request->merId());
        return app('json')->success(formToData($form));
    }

    /**
     * TODO 文件上传到本地
     * @param $field
     * @return \think\response\Json
     * @author Qinii
     * @day 2023/4/25
     */
    public function upload($field)
    {
        $file = $this->request->file($field);
        if (!$file) return app('json')->fail('请上传附件');

        //ico 图标处理
        if ($file->getOriginalExtension() == 'ico') {
            $file->move('public','favicon.ico');
            $res = tidy_url('public/favicon.ico');
            return app('json')->success(['src' => $res]);
        }

        $upload = UploadService::create(1);
        $data = $upload->to('attach')->validate()->move($field);
        if ($data === false) {
            return app('json')->fail($upload->getError());
        }
        return app('json')->success(['src' => path_to_url($upload->getFileInfo()->filePath)]);
    }

    public function uploadWechatForm()
    {
        return app('json')->success(formToData($this->repository->wechatForm()));
    }

    public function uploadAsName()
    {
        $file = $this->request->file('file');
        validate(["file|文件" => ['fileExt' => 'txt']])->check(['file' => $file]);
        if (!$file) return app('json')->fail('请上传附件');
        $res = \think\facade\Filesystem::putFileAs('', $file, $file->getOriginalName());
        if (!$res) return app('json')->fail('上传失败');

        return app('json')->success(['src' => $res]);
    }

    public function uploadWechatSet()
    {
        $name =  $this->request->param('wechat_chekc_file');
        if (!$name || !is_file(public_path() . 'uploads/' . $name)) return app('json')->fail('文件不存在');
        try {
            rename(public_path() . 'uploads/' . $name, public_path() . $name);
            app()->make(CacheRepository::class)->save('wechat_chekc_file', public_path() . $name, 0);
        } catch (\Exception $exception) {
            return app('json')->fail('修改失败');
        }

        return app('json')->success('提交成功');
    }

    /**
     * 下载小程序
     * @return mixed
     */
    public function downloadTemp()
    {
        $is_live = $this->request->param('is_live', 0);
        $is_menu = $this->request->param('is_menu', 0);
        if (systemConfig('routine_appId') == '') throw new ValidateException('请先配置小程序appId');
        $code = get_crmeb_version_code();
        $name = md5(time());
        $make = app()->make(CacheRepository::class);
        $routine_zip = $make->getResult('routine_zip');
        $path = root_path() . 'extend';

        if (!is_dir($path . '/download')) {
            @mkdir($path . '/download', 0777);
        }

        if (!is_dir(public_path() . 'static/download')) {
            @mkdir(public_path() . 'static/download', 0777);
        }
        if (!is_dir($path . '/mp-weixin/' . $code)) {
            return app('json')->fail('缺少小程序源文件');
        }

        try {
            @unlink(public_path() . 'static/download/' . $routine_zip . '.zip');
            //拷贝源文件
            /** @var FileService $fileService */
            $fileService = app(FileService::class);

            $fileService->copyDir($path . '/mp-weixin/' . $code, $path . '/download');
            //替换appid和名称

            $this->repository->updateConfigJson(systemConfig('routine_appId'), systemConfig('routine_name'), $path);
            //是否开启直播
            if ($is_live == 0 ) {
                $this->repository->updateAppJson($path);
            }

            $this->repository->updatePlantJson($path, $is_menu);
            //是否显示菜单
            if ($is_menu == 0) {
                $this->repository->updateRouteJson($path);
            }

            //替换url
            $this->repository->updateUrl(systemConfig('site_url'), $path);
            //压缩文件
            $fileService->addZip(
                $path . '/download',
                public_path() . 'static/download/' . $name . '.zip',
                $path . '/download'
            );
            $data['url'] = rtrim(systemConfig('site_url'), '/') . '/static/download/' . $name . '.zip';

            app()->make(CacheRepository::class)->save('routine_zip', $name);
            return app('json')->success($data);
        } catch (\Throwable $throwable) {
            Log::info($throwable->getMessage());
            return app('json')->fail('生成失败:' . $throwable->getMessage());
        }
    }

    public function getRoutineConfig()
    {
        $data['routine_name'] = systemConfig('routine_name');
        $data['routine_appId'] = systemConfig('routine_appId');
        $data['url'] = 'https://doc.crmeb.com/mer/mer2/4491';
        $data['site_url'] = rtrim(systemConfig('site_url'), '/') . '/pages/index/index';
        return app('json')->success($data);
    }
}
