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


namespace app\common\repositories\store;

use app\common\dao\store\StorePrinterDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Route;

class StorePrinterRepository extends BaseRepository
{
    public function __construct(StorePrinterDao $dao)
    {
        $this->dao = $dao;
    }


    public function form(?int $id)
    {
        if ($id) {
            $formData = $this->dao->get($id)->toArray();
            $form = Elm::createForm(Route::buildUrl('merchantStorePrinterUpdate',['id' => $id])->build());
        } else {
            $formData = [];
            $form = Elm::createForm(Route::buildUrl('merchantStorePrinterCreate')->build());
        }
        $form->setRule([
            Elm::input('printer_name','打印机名称')->required(),
            Elm::input('printer_appkey','应用ID')->required(),
            Elm::input('printer_appid','用户ID')->required(),
            Elm::input('printer_secret','应用密匙')->required(),
            Elm::input('printer_terminal','打印机终端号')->required()->appendRule('suffix', [
                'type' => 'div',
                'style' => ['color' => '#999999'],
                'domProps' => [
                    'innerHTML' =>'易联云打印机终端号打印机型号: 易联云打印机 K4无线版',
                ]
            ]),
            Elm::switches('status', '是否开启', 1)->inactiveValue(0)->activeValue(1)->inactiveText('关')->activeText('开')
        ]);
        return $form->setTitle($id ? '修改打印机' : '添加打印机')->formData($formData);

    }

    public function merList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list');
    }

    public function sysList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where)->with([
            'merchant' => function($query) {
                $query->field('mer_id,mer_name');
            },
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list');
    }

    public function checkPrinterConfig(int $merId)
    {
        if (!merchantConfig($merId, 'printing_status'))
            throw new ValidateException('打印功能未开启');
        $config = [
            'clientId' => merchantConfig($merId, 'printing_client_id'),
            'apiKey' => merchantConfig($merId, 'printing_api_key'),
            'partner' => merchantConfig($merId, 'develop_id'),
            'terminal' => merchantConfig($merId, 'terminal_number')
        ];
        if (!$config['clientId'] || !$config['apiKey'] || !$config['partner'] || !$config['terminal'])
            throw new ValidateException('打印机配置错误');
        return $config;
    }


    public function getPrinter(int $merId)
    {
        if (!merchantConfig($merId, 'printing_status'))
            throw new ValidateException('打印功能未开启');

        $res = $this->dao->getSearch(['mer_id' => $merId, 'status' => 1])->column('
        printer_appkey clientId,
        printer_terminal terminal,
        printer_appid partner,
        printer_secret apiKey
        ');

        if (!$res){
            $config = [
                'clientId' => merchantConfig($merId, 'printing_client_id'),
                'apiKey' => merchantConfig($merId, 'printing_api_key'),
                'partner' => merchantConfig($merId, 'develop_id'),
                'terminal' => merchantConfig($merId, 'terminal_number')
            ];

            if (!$config['clientId'] || !$config['apiKey'] || !$config['partner'] || !$config['terminal']) {
                $res[] = $config;
            }
        }

        if (!$res) throw new ValidateException('请添加打印机');
        return $res;
    }
}
