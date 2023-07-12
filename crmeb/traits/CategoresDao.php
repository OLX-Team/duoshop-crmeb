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

namespace crmeb\traits;

trait CategoresDao
{
    public $path = 'path';
    public $pathTag = '/';
    public $level = 'level';
    public $maxLevel = 3;
    public $parentId = 'pid';
    public $status = 'is_show';

    /**
     * @return mixed
     * @author Qinii
     */
    public function getPath():string
    {
        return $this->path;
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function getPathTag():string
    {
        return $this->pathTag;
    }

    /**
     * @return mixed
     */
    public function getLevel():string
    {
        return $this->level;
    }

    /**
     * @return int
     * @author Qinii
     */
    public function getMaxLevel():int
    {
        return $this->maxLevel;
    }

    /**
     * @return int
     * @return string
     */
    public function getParentId(): string
    {
        return $this->parentId;
    }

    /**
     * @return int
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
    /**
     * 获取所有分类
     * @param int $mer_id
     * @return mixed
     * @author Qinii
     */
    public function getAll($mer_id = 0,$status = null, $type = 0)
    {
        return $this->getModel()::getDB()->where('mer_id', $mer_id)->when(($status !== null),function($query)use($status){
                $query->where($this->getStatus(),$status);
            })->order('sort DESC,'.$this->getPk().' DESC')->select();
    }

    /**
     * 通过id 获取path
     * @param int $id 需要检测的数据
     * @return string
     * @author Qinii
     * @date 2020-03-30
     */
    public function getPathById($id)
    {
        return ($this->getModel())::getDB()->where($this->getPk(), $id)->value($this->getPath());
    }

    /**
     * 根据id 获取 等级
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function getLevelById($id)
    {
        return ($this->getModel())::getDB()->where($this->getPk(), $id)->value(($this->getLevel()));
    }

    /**
     * 根据 字段名查询
     * @param int $merId
     * @param $field
     * @param $value
     * @param null $except
     * @return bool
     * @author Qinii
     */
    public function merFieldExists(int $merId, $field, $value, $except = null)
    {
        return ($this->getModel())::getDB()
                ->when($except, function ($query, $except) use ($field) {
                    $query->where($field, '<>', $except);
                })
                ->where('mer_id', $merId)
                ->where($field, $value)->count() > 0;
    }

    /**
     *  获取子集 等级 数组
     * @param $id
     * @return array
     * @author Qinii
     */
    public function getChildLevelById($id)
    {
        $level = ($this->getModel()::getDB())->where($this->getPath(),'like',$this->getPathById($id).$id.$this->getPathTag().'%')->column($this->getLevel());
        return (is_array($level) && !empty($level))  ? $level : [0];
    }

    /**
     * 查询修改目标是否为自己到子集
     * @param int $id
     * @param int $pid
     * @return mixed
     * @author Qinii
     */
    public function checkChangeToChild(int $id,int $pid)
    {
        return ($this->getModel()::getDB())
            ->where($this->getPath(),'like',$this->getPathById($id).$id.$this->getPathTag().'%')
            ->where($this->getPk(),$pid)
            ->count();
    }

    /**
     * 是否存在子集
     * @param int $id
     * @return mixed
     * @author Qinii
     */
    public function hasChild(int $id)
    {
        return ($this->getModel()::getDB())->where($this->getPath(),'like',$this->getPathById($id).$id.$this->getPathTag().'%')->count();
    }

    /**
     * 编辑
     * @param int $id
     * @param array $data
     * @author Qinii
     */
    public function updateParent(int $id,array $data)
    {
        ($this->getModel()::getDB())->transaction(function()use($id,$data){
            $change = $data['change'];
            unset($data['change']);
            ($this->getModel()::getDB())->where($this->getPk(),$id)->update($data);

            $this->updateChild($change['oldPath'],$change['newPath'],$change['changeLevel']);
       });
    }

    /**
     * 修改子类
     * @param string $oldPath
     * @param string $newPath
     * @param $changeLevel
     * @author Qinii
     */
    public function updateChild(string $oldPath,string $newPath, $changeLevel)
    {
        $query = ($this->getModel()::getDB())->where($this->getPath(),'like',$oldPath.'%')
            ->select();
        if ($query) {
            $query->each(function ($item) use ($oldPath, $newPath, $changeLevel) {
                $child = ($this->getModel()::getDB())->find($item[$this->getPk()]);
                $child->path = str_replace($oldPath, $newPath, $child->path);
                $child->level = $child->level + $changeLevel;
                $child->save();
            });
        }
    }

    /**
     * 修改状态
     * @param int $id
     * @param int $status
     * @return mixed
     * @author Qinii
     */
    public function switchStatus(int $id,int $status)
    {
       return  ($this->getModel()::getDB())->where($this->getPk(),$id)->update([
            $this->getStatus() => $status
        ]);
    }


    /**
     * 获取列表 -- 筛选用
     * @Author:Qinii
     * @Date: 2020/5/16
     * @param int|null $mer_id
     * @return mixed
     */
    public function getAllOptions($mer_id = null,$status = null,$level = null,$type = null)
    {
        $field = $this->getParentId().',cate_name';
        $query = ($this->getModel()::getDB());
        $query->when(($mer_id !== null),function($query)use($mer_id){
                $query->where('mer_id', $mer_id);
            })
            ->when($status,function($query)use($status){
                $query->where($this->getStatus(),$status);
            })
            ->when(($level != '' && $level != null),function($query)use($level){
                $query->where($this->getLevel(),'<',$level);
            });
        $query->when(!is_null($type),function ($query) use($type){
            $query->where('type',$type);
        });
        return $query->order('sort DESC,'.$this->getPk().' DESC')->column($field, $this->getPk());
    }

    public function switchStatusAndChild(int $id,$status)
    {
        $parent = $this->getModel()::find($id);
        ($this->getModel()::getDB())->where($this->getPk(),$id)->update([$this->getStatus() => $status]);
        return ($this->getModel()::getDB())->where('path','like',$parent->path.$id.'/%')->update([$this->getStatus() => $status]);
    }
}
