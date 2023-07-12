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


namespace app\common\dao\system\attachment;

use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\system\attachment\AttachmentCategory;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Model;

/**
 * Class AttachmentCategoryDao
 * @package app\common\dao\system\attachment
 * @author xaboy
 * @day 2020-04-22
 */
class AttachmentCategoryDao extends BaseDao
{
    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return AttachmentCategory::class;
    }

    /**
     * @param int $mer_id
     * @return Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-15
     */
    public function getAll($mer_id = 0)
    {
        return AttachmentCategory::getDB()->where('mer_id', $mer_id)->order('sort DESC')->select();
    }

    /**
     * 通过 $attachmentCategoryEName 获取主键
     * @param string $attachmentCategoryEName 需要检测的数据
     * @return int
     * @author 张先生
     * @date 2020-03-30
     */
    public function getPkByAttachmentCategoryEName($attachmentCategoryEName)
    {
        return AttachmentCategory::getInstance()->where('attachment_category_enname', $attachmentCategoryEName)->value($this->getPk());
    }

    /**
     * 通过id 获取path
     * @param int $id 需要检测的数据
     * @return string
     * @author 张先生
     * @date 2020-03-30
     */
    public function getPathById($id)
    {
        return AttachmentCategory::getInstance()->where($this->getPk(), $id)->value('path');
    }

    /**
     * 通过id获取所有子集的id
     * @param int $id 需要检测的数据
     * @return array
     * @author 张先生
     * @date 2020-03-30
     */
    public function getIdListContainsPath($id)
    {
        return AttachmentCategory::getInstance()
            ->where($this->getPk(), $id)
            ->whereOrRaw("locate ('/{$id}/', path)")
            ->column($this->getPk());
    }

    /**
     * @param int $mer_id
     * @return array
     * @author xaboy
     * @day 2020-04-20
     */
    public function getAllOptions($mer_id = 0)
    {
        return AttachmentCategory::getDB()->where('mer_id', $mer_id)->order('sort DESC')->column('pid,attachment_category_name', 'attachment_category_id');
    }

    /**
     * @param int $merId
     * @param int $id
     * @param null $except
     * @return bool
     * @author xaboy
     * @day 2020-04-15
     */
    public function merExists(int $merId, int $id, $except = null)
    {
        return $this->merFieldExists($merId, $this->getPk(), $id, $except);
    }

    /**
     * @param int $merId
     * @param $field
     * @param $value
     * @param null $except
     * @return bool
     * @author xaboy
     * @day 2020-04-15
     */
    public function merFieldExists(int $merId, $field, $value, $except = null)
    {
        return ($this->getModel())::getDB()->when($except, function ($query, $except) use ($field) {
                $query->where($field, '<>', $except);
            })->where('mer_id', $merId)->where($field, $value)->count() > 0;
    }


    /**
     * @param int $id
     * @param int $merId
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-15
     */
    public function get($id, $merId = 0)
    {
        return ($this->getModel())::getDB()->where('mer_id', $merId)->find($id);
    }

    /**
     * @param int $id
     * @param int $merId
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-04-15
     */
    public function delete(int $id, $merId = 0)
    {
        return ($this->getModel())::getDB()->where($this->getPk(), $id)->where('mer_id', $merId)->delete();
    }

    /**
     * @param string $oldPath
     * @param string $path
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-30
     */
    public function updatePath(string $oldPath, string $path)
    {
        AttachmentCategory::getDB()->whereLike('path', $oldPath . '%')->field('attachment_category_id,path')->select()->each(function ($val) use ($oldPath, $path) {
            $newPath = str_replace($oldPath, $path, $val['path']);
            if (substr_count(trim($newPath, '/'), '/') > 1) throw new ValidateException('素材分类最多添加三级');
            AttachmentCategory::getDB()->where('attachment_category_id', $val['attachment_category_id'])->update(['path' => $newPath]);
        });
    }
}
