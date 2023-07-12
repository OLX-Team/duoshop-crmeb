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


namespace app\common\repositories\system\attachment;


//附件
use app\common\dao\BaseDao;
use app\common\dao\system\attachment\AttachmentDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Factory\Elm;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Route;
use think\Model;


/**
 * Class BaseRepository
 * @package common\repositories
 * @mixin AttachmentDao
 */
class AttachmentRepository extends BaseRepository
{
    /**
     * @var AttachmentCategoryRepository
     */
    private $attachmentCategoryRepository;

    /**
     * AttachmentRepository constructor.
     * @param AttachmentDao $dao
     * @param AttachmentCategoryRepository $attachmentCategoryRepository
     */
    public function __construct(AttachmentDao $dao, AttachmentCategoryRepository $attachmentCategoryRepository)
    {
        /**
         * @var AttachmentDao
         */
        $this->dao = $dao;
        $this->attachmentCategoryRepository = $attachmentCategoryRepository;
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
     * @day 2020-04-15
     */
    public function getList(array $where, int $page, int $limit)
    {
        $query = $this->search($where);
        $count = $query->count($this->dao->getPk());
        $list = $query->page($page, $limit)->hidden(['upload_type', 'user_type', 'user_id'])
            ->select();
        return compact('count', 'list');
    }

    /**
     * @param int $uploadType
     * @param int $userType
     * @param int $userId
     * @param array $data
     * @return BaseDao|Model
     * @author xaboy
     * @day 2020-04-15
     */
    public function create(int $uploadType, int $userType, int $userId, array $data)
    {
        $data['upload_type'] = $uploadType;
        $data['user_type'] = $userType;
        $data['user_id'] = $userId;
        return $this->dao->create($data);
    }

    /**
     * @param array $ids
     * @param int $categoryId
     * @param int $merId
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-04-16
     */
    public function batchChangeCategory(array $ids, int $categoryId, $merId = 0)
    {
        return $this->dao->batchChange($ids, ['attachment_category_id' => $categoryId], $merId);
    }

    public function form(int $id,int $merId)
    {
        if ($merId) {
            $action =  'merchantAttachmentUpdate';
        } else {
            $action =  'systemAttachmentUpdate';
        }
        $formData = $this->dao->get($id)->toArray();
        $form = Elm::createForm(Route::buildUrl($action, is_null($id) ? [] : ['id' => $id])->build());
        $form->setRule([
            Elm::input('attachment_name', '名称')->required(),
        ]);
        return $form->setTitle('编辑配置')->formData($formData);
    }
}
