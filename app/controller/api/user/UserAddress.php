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


namespace app\controller\api\user;

use app\common\repositories\store\CityAreaRepository;
use think\App;
use crmeb\basic\BaseController;
use app\validate\api\UserAddressValidate as validate;
use app\common\repositories\user\UserAddressRepository as repository;
use think\exception\ValidateException;

class UserAddress extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;

    /**
     * UserAddress constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    public function lst()
    {
        return app('json')->success($this->repository->getList($this->request->uid()));
    }

    public function detail($id)
    {
        $uid = $this->request->uid();
        if (!$this->repository->existsWhere(['address_id' => $id, 'uid' => $uid])) {
            return app('json')->fail('地址不存在');
        }
        return app('json')->success($this->repository->get($id, $uid));
    }

    /**
     * @param validate $validate
     * @return mixed
     * @author Qinii
     */
    public function create(validate $validate)
    {
        $data = $this->checkParams($validate);
        if ($data['is_default']) {
            $this->repository->changeDefault($this->request->uid());
        } else {
            if (!$this->repository->defaultExists($this->request->uid())) $data['is_default'] = 1;
        }
        if ($data['address_id']) {
            if (!$this->repository->fieldExists($data['address_id'], $this->request->uid()))
                return app('json')->fail('信息不存在');
            $this->repository->update($data['address_id'], $data);
            return app('json')->success('编辑成功');
        };
        $data['uid'] = $this->request->uid();
        $address = $this->repository->create($data);
        return app('json')->success('添加成功', $address->toArray());
    }

    /**
     * @param $id
     * @param validate $validate
     * @return mixed
     * @author Qinii
     */
    public function update($id, validate $validate)
    {
        if (!$this->repository->fieldExists($id, $this->request->uid()))
            return app('json')->fail('信息不存在');
        $data = $this->checkParams($validate);
        if ($data['is_default']) $this->repository->changeDefault($this->request->uid());
        $this->repository->update($id, $data);
        return app('json')->success('编辑成功');
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function delete($id)
    {
        if (!$this->repository->fieldExists($id, $this->request->uid()))
            return app('json')->fail('信息不存在');
        if ($this->repository->checkDefault($id))
            return app('json')->fail('默认地址不能删除');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    public function editDefault($id)
    {
        if (!$this->repository->fieldExists($id, $this->request->uid()))
            return app('json')->fail('信息不存在');
        $this->repository->changeDefault($this->request->uid());
        $this->repository->update($id, ['is_default' => 1]);
        return app('json')->success('修改成功');
    }

    /**
     * @param validate $validate
     * @return array
     * @author Qinii
     */
    public function checkParams(validate $validate)
    {
        $data = $this->request->params(['address_id', 'real_name', 'phone', 'area', 'detail', 'post_code', 'is_default']);
        $validate->check($data);
        [$province, $city, $district, $street] = ((array)$data['area']) + [null, null, null, null];
        $last = $street ?? $district ?? $city ?? $province;
        if (!$last) {
            throw new ValidateException('请选择正确的收货地址');
        }
        $make = app()->make(CityAreaRepository::class);
        if (!$make->existsWhere(['id' => $last['id'], 'snum' => 0])) {
            throw new ValidateException('请手动选择所在地区');
        }
        if ($make->search([])->where('id', 'in', array_column($data['area'], 'id'))->count() !== count($data['area'])) {
            throw new ValidateException('请选择正确的收货地址');
        }

        $data['province'] = $province['name'];
        $data['province_id'] = $province['id'];
        $data['city'] = $city['name'];
        $data['city_id'] = $city['id'];
        $data['district'] = $district['name'];
        $data['district_id'] = $district['id'];
        if (isset($street)) {
            $data['street'] = $street['name'];
            $data['street_id'] = $street['id'];
        }
        unset($data['area']);
        return $data;
    }
}
