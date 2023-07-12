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


namespace app\common\repositories\system\config;


use app\common\dao\system\config\SystemConfigDao;
use app\common\model\system\config\SystemConfigClassify;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\CacheRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\facade\Route;

/**
 * Class ConfigRepository
 * @package crmeb\repositories\system\config
 * @mixin SystemConfigDao
 */
class ConfigRepository extends BaseRepository
{
    const TYPES = ['input' => '文本框', 'number' => '数字框', 'textarea' => '多行文本框', 'radio' => '单选框', 'switches' => '开关', 'checkbox' => '多选框', 'select' => '下拉框', 'file' => '文件上传', 'image' => '图片上传', 'images' => '多图片上传', 'color' => '颜色选择框'];

    /**
     * ConfigRepository constructor.
     * @param SystemConfigDao $dao
     */
    public function __construct(SystemConfigDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param int $merId
     * @param SystemConfigClassify $configClassify
     * @param array $configs
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-23
     */
    public function formRule(int $merId, SystemConfigClassify $configClassify, array $configs, array $formData = [])
    {
        $components = $this->getRule($configs, $merId);

        $form = Elm::createForm(Route::buildUrl($merId ? 'merchantConfigSave' : 'configSave', ['key' => $configClassify->classify_key])->build(), $components);
        return $form->setTitle($configClassify->classify_name)->formData(array_filter($formData, function ($item) {
            return $item !== '' && !is_null($item);
        }));
    }

    public function getRule(array $configs, $merId)
    {
        $components = [];
        foreach ($configs as $config) {
            $component = $this->getComponent($config, $merId);
            $components[] = $component;
        }
        return $components;
    }

    public function getComponent($config, $merId)
    {
        switch ($config['config_type']) {
            case 'image':
                $component = Elm::frameImage($config['config_key'], $config['config_name'], '/' . config('admin.' . ($merId ? 'merchant' : 'admin') . '_prefix') . '/setting/uploadPicture?field=' . $config['config_key'] . '&type=1')->modal(['modal' => false])->width('896px')->height('480px')->props(['footer' => false]);
                break;
            case 'images':
                $component = Elm::frameImage($config['config_key'], $config['config_name'], '/' . config('admin.' . ($merId ? 'merchant' : 'admin') . '_prefix') . '/setting/uploadPicture?field=' . $config['config_key'] . '&type=2')->maxLength(5)->modal(['modal' => false])->width('896px')->height('480px')->props(['footer' => false]);
                break;
            case 'file':
                $component = Elm::uploadFile($config['config_key'], $config['config_name'], rtrim(systemConfig('site_url'), '/') . Route::buildUrl('configUpload', ['field' => 'file'])->build())->headers(['X-Token' => request()->token()]);
                break;
            case 'select':
                //notbreak
            case 'checkbox':
                //notbreak
            case 'radio':
                $options = array_map(function ($val) {
                    [$value, $label] = explode(':', $val, 2);
                    return compact('value', 'label');
                }, explode("\n", $config['config_rule']));
                $component = Elm::{$config['config_type']}($config['config_key'], $config['config_name'])->options($options);
                break;
            case 'switches':
                $component = Elm::{$config['config_type']}($config['config_key'], $config['config_name'])->activeText('开')->inactiveText('关');
                break;
            default:
                $component = Elm::{$config['config_type']}($config['config_key'], $config['config_name']);
                break;
        }

        if ($config['required']) $component->required();

        if ($config['config_props'] ?? '') {
            $props = @parse_ini_string($config['config_props'], false, INI_SCANNER_TYPED);
            if (is_array($props)) {
                $guidance_uri = $props['guidance_uri'] ?? '';
                $guidance_image = $props['guidance_image'] ?? '';
                if ($guidance_image) {
                    $config['guidance'] = [
                        'uri' => $guidance_uri,
                        'image' => $guidance_image,
                    ];
                }
                unset($props['guidance_image'], $props['guidance_uri']);
                $component->props($props);
                if (isset($props['required']) && $props['required']) {
                    $component->required();
                }
                if (isset($props['defaultValue'])) {
                    $component->value($props['defaultValue']);
                }
            }
        }
        if ($config['info']) {
            $component->appendRule('suffix', [
                'type' => 'guidancePop',
                'props' => [
                    'info' => $config['info'],
                    'url' => $config['guidance']['uri'] ?? '',
                    'image' => $config['guidance']['image'] ?? '',
                ]
            ]);
        }
        return $component;
    }

    /**
     * @param int $id
     * @param int $status
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-03-31
     */
    public function switchStatus(int $id, int $status)
    {
        return $this->dao->update($id, compact('status'));
    }

    /**
     * @param SystemConfigClassify $configClassify
     * @param int $merId
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-22
     */
    public function cidByFormRule(SystemConfigClassify $configClassify, int $merId)
    {
        $config = $this->dao->cidByConfig($configClassify->config_classify_id, $merId == 0 ? 0 : 1);
        $keys = $config->column('config_key');
        return $this->formRule($merId, $configClassify, $config->toArray(), app()->make(ConfigValueRepository::class)->more($keys, $merId));
    }

    /**
     * @param int|null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-03-31
     */
    public function form(?int $id = null, array $formData = []): Form
    {
        $form = Elm::createForm(is_null($id) ? Route::buildUrl('configSettingCreate')->build() : Route::buildUrl('configSettingUpdate', ['id' => $id])->build());
        $form->setRule([
            Elm::cascader('config_classify_id', '上级分类')->options(function () {
                $configClassifyRepository = app()->make(ConfigClassifyRepository::class);
                return array_merge([['value' => 0, 'label' => '请选择']], $configClassifyRepository->options());
            })->props(['props' => ['checkStrictly' => true, 'emitPath' => false]]),
            Elm::select('user_type', '后台类型', 0)->options([
                ['label' => '总后台配置', 'value' => 0],
                ['label' => '商户后台配置', 'value' => 1],
            ])->requiredNum(),
            Elm::input('config_name', '配置名称')->required(),
            Elm::input('config_key', '配置key')->required(),
            Elm::textarea('info', '说明'),
            Elm::select('config_type', '配置类型')->options(function () {
                $options = [];
                foreach (self::TYPES as $value => $label) {
                    $options[] = compact('value', 'label');
                }
                return $options;
            })->required(),
            Elm::textarea('config_rule', '选择项'),
            Elm::textarea('config_props', '配置'),
            Elm::number('sort', '排序', 0)->precision(0)->max(99999),
            Elm::switches('required', '必填', 0)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
            Elm::switches('status', '是否显示', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
        ]);

        return $form->setTitle(is_null($id) ? '添加配置' : '编辑配置')->formData($formData);
    }

    /**
     * @param int $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-03-31
     */
    public function updateForm(int $id)
    {
        return $this->form($id, $this->dao->get($id)->toArray());
    }

    /**
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-03-31
     */
    public function lst(array $where, int $page, int $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->withAttr('typeName', function ($value, $data) {
            return self::TYPES[$data['config_type']];
        })->hidden(['config_classify_id'])->append(['typeName'])->select();
        return compact('count', 'list');
    }

    public function tabForm($group, $merId)
    {
        $make = app()->make(ConfigClassifyRepository::class);
        $list = $make->children($group->config_classify_id, 'config_classify_id,classify_key,classify_name,info');
        $children = [];
        foreach ($list as $item) {
            $_children = $this->cidByFormRule($make->keyByData($item['classify_key']), $merId)->formRule();
            if ($item['info']) {
                array_unshift($_children, [
                    'type' => 'el-alert',
                    'props' => [
                        'type' => 'warning',
                        'closable' => false,
                        'title' => $item['info']
                    ]
                ], ['type' => 'div', 'style' => ['height' => '20px', 'width' => '100%']]);
            }
            $children[] = [
                'type' => 'el-tab-pane',
                'props' => [
                    'label' => $item['classify_name'],
                    'name' => $item['classify_key']
                ],
                'children' => $_children
            ];
        }
        if ($group['classify_key'] === 'distribution_tabs') {
            $action = Route::buildUrl('configOthersSettingUpdate')->build();
        } else {
            $action = Route::buildUrl($merId ? 'merchantConfigSave' : 'configSave', ['key' => $group['classify_key']])->build();
        }
        return Elm::createForm($action, [
            [
                'type' => 'el-tabs',
                'native' => true,
                'props' => [
                    'value' => $list[0]['classify_key'] ?? ''
                ],
                'children' => $children
            ]
        ])->setTitle($group['classify_name']);
    }

    public function uploadForm()
    {
        $config = $this->getWhere(['config_key' => 'upload_type']);
        $rule = $this->getComponent($config, 0)->value(systemConfig('upload_type'));
        $make = app()->make(ConfigClassifyRepository::class);
        $rule->control([
            [
                'value' => '1',
                'rule' => $this->cidByFormRule($make->keyByData('local'), 0)->formRule()
            ],
            [
                'value' => '2',
                'rule' => $this->cidByFormRule($make->keyByData('qiniuyun'), 0)->formRule()
            ],
            [
                'value' => '3',
                'rule' => $this->cidByFormRule($make->keyByData('aliyun_oss'), 0)->formRule()
            ],
            [
                'value' => '4',
                'rule' => $this->cidByFormRule($make->keyByData('tengxun'), 0)->formRule()
            ],
            [
                'value' => '5',
                'rule' => $this->cidByFormRule($make->keyByData('huawei_obs'), 0)->formRule()
            ],
            [
                'value' => '6',
                'rule' => $this->cidByFormRule($make->keyByData('ucloud'), 0)->formRule()
            ],
            [
                'value' => '7',
                'rule' => $this->cidByFormRule($make->keyByData('jdoss'), 0)->formRule()
            ],
            [
                'value' => '8',
                'rule' => $this->cidByFormRule($make->keyByData('ctoss'), 0)->formRule()
            ],
        ]);
        return Elm::createForm(Route::buildUrl('systemSaveUploadConfig')->build(), [$rule])->setTitle('上传配置');
    }

    public function saveUpload($data)
    {
        $configValueRepository = app()->make(ConfigValueRepository::class);
        $uploadType = $data['upload_type'] ?? '1';
        $key = '';
        switch ($uploadType) {
            case 1:
                $key = 'local';
                break;
            case 2:
                $key = 'qiniuyun';
                break;
            case 3:
                $key = 'aliyun_oss';
                break;
            case 4:
                $key = 'tengxun';
                break;
            case 5:
                $key = 'huawei_obs';
                break;
            case 6:
                $key = 'ucloud';
                break;
            case 7:
                $key = 'jdoss';
                break;
            case 8:
                $key = 'ctoss';
                break;

        }

        Db::transaction(function () use ($data, $key, $uploadType, $configValueRepository) {
            $configValueRepository->setFormData([
                'upload_type' => $uploadType
            ], 0);
            if ($key) {
                $make = app()->make(ConfigClassifyRepository::class);
                if (!($cid = $make->keyById($key))) return app('json')->fail('保存失败');
                $configValueRepository->save($cid, $data, 0);
            }
        });
    }

    public function wechatForm()
    {
        $formData['wechat_chekc_file'] = app()->make(CacheRepository::class)->getWhere(['key' => 'wechat_chekc_file']);
        if ($formData['wechat_chekc_file'] && !is_file($formData['wechat_chekc_file'])) $formData['wechat_chekc_file'] = '';

        $form = Elm::createForm(Route::buildUrl('configWechatUploadSet')->build());

        $form->setRule([
            Elm::uploadFile('wechat_chekc_file', '上传校验文件', rtrim(systemConfig('site_url'), '/') . Route::buildUrl('configUploadName', ['field' => 'file'])->build())->headers(['X-Token' => request()->token()]),
        ]);
        return $form->setTitle('上传校验文件')->formData($formData);
    }

    /**
     * 替换appid
     * @param string $appid
     * @param string $projectanme
     */
    public function updateConfigJson($appId = '', $projectName = '', $path = '')
    {
        $fileUrl = $path . "/download/project.config.json";
        $string = file_get_contents($fileUrl); //加载配置文件
        // 替换appid
        $appIdOld = '/"appid"(.*?),/';
        $appIdNew = '"appid"' . ': ' . '"' . $appId . '",';
        $string = preg_replace($appIdOld, $appIdNew, $string); // 正则查找然后替换
        // 替换小程序名称
        $projectNameOld = '/"projectname"(.*?),/';
        $projectNameNew = '"projectname"' . ': ' . '"' . $projectName . '",';
        $string = preg_replace($projectNameOld, $projectNameNew, $string); // 正则查找然后替换
        $newFileUrl = $path . "/download/project.config.json";
        @file_put_contents($newFileUrl, $string); // 写入配置文件
    }

    /**
     * 替换url
     * @param $url
     */
    public function updateUrl($url, $path)
    {
        $fileUrl = $path . "/download/common/vendor.js";

        $string = file_get_contents($fileUrl); //加载配置文件
        $string = str_replace('https://mer.crmeb.net', $url, $string); // 正则查找然后替换

        $ws = str_replace('https', 'wss', $url);
        $string = str_replace('wss://mer.crmeb.net', $ws, $string); // 正则查找然后替换

        $newFileUrl = $path . "/download/common/vendor.js";
        @file_put_contents($newFileUrl, $string); // 写入配置文件
    }

    /**
     * 关闭直播
     * @param int $iszhibo
     */
    public function updateAppJson($path)
    {
        $fileUrl = $path . "/download/app.json";
        $string = file_get_contents($fileUrl); //加载配置文件
        $pats = '/,
      "plugins": \{
        "live-player-plugin": \{
          "version": "(.*?)",
          "provider": "(.*?)"
        }
      }/';
        $string = preg_replace($pats, '', $string); // 正则查找然后替换
        $newFileUrl = $path . "/download/app.json";
        @file_put_contents($newFileUrl, $string); // 写入配置文件
    }

    /**
     * 去掉菜单
     * @param int $iszhibo
     */
    public function updateRouteJson($path)
    {
        $fileUrl = $path . "/download/app.json";
        $string = file_get_contents($fileUrl); //加载配置文件
        $pats = '/
      {
        "pagePath": "pages\/plant_grass\/index",
        "iconPath": "static\/images\/5-001.png",
        "selectedIconPath": "static\/images\/5-002.png",
        "text": "逛逛"
      },/';
        $string = preg_replace($pats, '', $string); // 正则查找然后替换
        $newFileUrl = $path . "/download/app.json";
        @file_put_contents($newFileUrl, $string); // 写入配置文件
    }

    /**
     * TODO 请求方式
     * @param $path
     * @param bool $plant
     * @author Qinii
     * @day 1/4/22
     */
    public function updatePlantJson(string $path, int $plant)
    {
        $fileUrl = $path . "/download/common/vendor.js";
        $string = file_get_contents($fileUrl); //加载配置文件
        $string = str_replace('"-openPlantGrass-"', $plant ? 'true' : 'false', $string); // 正则查找然后替换
        $newFileUrl = $path . "/download/common/vendor.js";
        @file_put_contents($newFileUrl, $string); // 写入配置文件
    }
}
