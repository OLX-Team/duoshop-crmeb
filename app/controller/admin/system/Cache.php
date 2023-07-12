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
namespace app\controller\admin\system;

use app\common\repositories\system\CacheRepository;
use crmeb\basic\BaseController;
use think\App;

class Cache extends BaseController
{
    /**
     * @var CacheRepository
     */
    protected $repository;

    /**
     * CacheRepository constructor.
     * @param App $app
     */
    public function __construct(App $app, CacheRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    public function getKeyLst()
    {
        $type = $this->request->param('type',0);
        $data = $this->repository->getAgreeList($type);
        return app('json')->success($data);
    }


    /**
     * @Author:Qinii
     * @Date: 2020/9/15
     * @return mixed
     */
    public function getAgree($key)
    {
        $allow = $this->repository->getAgreeKey();
        if (!in_array($key, $allow)) return app('json')->fail('数据不存在');
        $data = $this->repository->getResult($key);
        return app('json')->success($data);
    }


    /**
     * @Author:Qinii
     * @Date: 2020/9/15
     * @return mixed
     */
    public function saveAgree($key)
    {
        $allow = $this->repository->getAgreeKey();
        if (!in_array($key, $allow)) return app('json')->fail('KEY不存在');

        $value = $this->request->param('agree');
        $this->repository->save($key, $value);

        if ($key == CacheRepository::USER_PRIVACY)
            $this->repository->setUserAgreement($value);
        if ($key == CacheRepository::USER_AGREE)
            $this->repository->setUserRegister($value);

        return app('json')->success('保存成功');
    }

    /**
     * TODO 清除缓存
     * @return \think\response\Json
     * @author Qinii
     * @day 12/9/21
     */
    public function clearCache()
    {
        return app('json')->success('清除缓存成功');
    }
}
