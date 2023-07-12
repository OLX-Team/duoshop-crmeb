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


namespace app\controller\admin\wechat;


use crmeb\basic\BaseController;
use app\common\repositories\wechat\WechatReplyRepository;
use app\validate\admin\WechatReplyValidate;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Filesystem;

/**
 * Class WechatReply
 * @package app\controller\admin\wechat
 * @author xaboy
 * @day 2020-04-24
 */
class WechatReply extends BaseController
{
    /**
     * @var WechatReplyRepository
     */
    protected $repository;

    /**
     * WechatReply constructor.
     * @param App $app
     * @param WechatReplyRepository $repository
     */
    public function __construct(App $app, WechatReplyRepository $repository)
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
     * @day 2020-04-27
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword']);

        return app('json')->success($this->repository->getLst($where, $page, $limit));
    }

    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-27
     */
    public function info($id)
    {
        $type = $this->request->param('type', 0);
        $reply = !$type ? $this->repository->get((int)$id) : $this->repository->keyByReply($id);
        if ($reply)
            return app('json')->success($reply->toArray());
        else
            return app('json')->fail('数据不存在');
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-24
     */
    public function changeStatus($id)
    {
        $status = $this->request->param('status');
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['status' => $status == 1 ? 1 : 0]);
        return app('json')->success('修改成功');
    }

    /**
     * @param WechatReplyValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-04-27
     */
    public function create(WechatReplyValidate $validate)
    {
        $data = $this->request->params(['key', 'type', 'data', 'status']);
        $validate->check($data);
        if ($this->repository->fieldExists('key', $data['key']))
            return app('json')->fail('关键字已存在');
        $this->repository->create($data);
        return app('json')->success('保存成功');
    }

    /**
     * @param $id
     * @param WechatReplyValidate $validate
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-27
     */
    public function update($id, WechatReplyValidate $validate)
    {
        $data = $this->request->params(['key', 'type', 'data', 'status']);
        $validate->check($data);
        if ($this->repository->fieldExists('key', $data['key'], $id))
            return app('json')->fail('关键字已存在');
        $this->repository->updateReply($id, $data);
        return app('json')->success('保存成功');
    }

    /**
     * @param string $key
     * @param WechatReplyValidate $validate
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-24
     */
    public function save($key, WechatReplyValidate $validate)
    {
        [$type, $data, $status] = $this->request->params(['type', 'data', 'status'], true);
        $validate->isUpdate()->check(compact('type', 'data', 'status'));
        if (!in_array($key, ['default', 'subscribe']))
            return app('json')->fail('修改失败');
        $this->repository->save($key, $type, (array)$data, $status == 1 ? 1 : 0, 1);
        return app('json')->success('保存成功');
    }

    /**
     * @param $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-24
     */
    public function delete($id)
    {
        $this->repository->delete($id);

        return app('json')->success('删除成功');
    }

    public function uploadImage()
    {
        $file = $this->request->file('file');
        if (!$file)
            return app('json')->fail('请上传图片');
        $file = is_array($file) ? $file[0] : $file;

        validate(["file|图片" => [
            'fileSize' => config('upload.filesize'),
            'fileExt' => 'jpg,jpeg,png,bmp,gif',
            'fileMime' => 'image/jpeg,image/png,image/gif',
        ]])->check(['file' => $file]);

        $path = Filesystem::putFile('wechat/img', $file, 'md5');
        return app('json')->success(['src' => '/uploads/' . $path]);
    }

    public function uploadVoice()
    {
        $file = $this->request->file('file');
        if (!$file)
            return app('json')->fail('请上传声音');
        $file = is_array($file) ? $file[0] : $file;
        validate(["file|声音" => [
            'fileSize' => config('upload.filesize'),
            'fileExt' => 'wav,aif,mp3',
            'fileMime' => 'audio/x-wav,audio/x-aiff,audio/x-mpeg,audio/mpeg,audio/wav,audio/aiff',
        ]])->check(['file' => $file]);

        $path = Filesystem::putFile('wechat/voice', $file, 'md5');
        return app('json')->success(['src' => '/uploads/' . $path]);
    }

}
