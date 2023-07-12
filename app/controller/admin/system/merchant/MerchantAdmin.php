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


namespace app\controller\admin\system\merchant;


use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantAdminRepository;
use app\validate\admin\AdminValidate;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DbException;

/**
 * Class MerchantAdmin
 * @package app\controller\admin\system\merchant
 * @author xaboy
 * @day 2020-04-17
 */
class MerchantAdmin extends BaseController
{
    /**
     * @var MerchantAdminRepository
     */
    protected $repository;

    /**
     * MerchantAdmin constructor.
     * @param App $app
     * @param MerchantAdminRepository $repository
     */
    public function __construct(App $app, MerchantAdminRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @param int $id
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-17
     */
    public function passwordForm($id)
    {
        return app('json')->success(formToData($this->repository->passwordForm($id, 1)));
    }


    /**
     * @param int $id
     * @param AdminValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-17
     */
    public function password($id, AdminValidate $validate)
    {
        $data = $this->request->params(['pwd', 'againPassword']);
        $validate->isPassword()->check($data);

        if ($data['pwd'] !== $data['againPassword'])
            return app('json')->fail('两次密码输入不一致');
        $adminId = $this->repository->merchantIdByTopAdminId($id);
        if (!$adminId)
            return app('json')->fail('商户不存在');
        $data['pwd'] = $this->repository->passwordEncode($data['pwd']);
        unset($data['againPassword']);
        $this->repository->update($adminId, $data);

        return app('json')->success('修改密码成功');
    }
}
