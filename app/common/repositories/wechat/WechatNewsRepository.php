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

namespace app\common\repositories\wechat;

use app\common\dao\wechat\WechatNewsDao;
use app\common\repositories\article\ArticleRepository;
use app\common\repositories\BaseRepository;
use think\facade\Db;
use app\common\dao\article\ArticleDao;

class WechatNewsRepository extends BaseRepository
{
    public function __construct(WechatNewsDao $dao)
    {
        $this->dao = $dao;
    }

    public function create(array $data,int $merId,int $adminId)
    {
        Db::transaction(function () use($adminId,$merId,$data){
            $wechatNews = $this->dao->create([
                'status' => $data['status'] ?? 1,
                'mer_id' => $merId
            ]);
            $make = app()->make(ArticleRepository::class);
            $wechat_news_id = $wechatNews->wechat_news_id;
            foreach ($data['data'] as $v) {
                $result = [
                    'admin_id' => $adminId,
                    'mer_id' => $merId,
                    'wechat_news_id' => $wechat_news_id,
                    'title' => $v['title'],
                    "author" => $v['author'],
                    "synopsis" => $v['synopsis'],
                    "image_input" => $v['image_input'],
                    'content' => $v['content'],
                ];
                $make->create($result);
            }
        });
    }

    public function update(int $id , array $data,int $merId,int $adminId)
    {
        Db::transaction(function () use($id,$adminId,$merId,$data){
            $this->dao->update($id,['status' => $data['status']]);
            $make = app()->make(ArticleRepository::class);
            $make->clearByNewId($id);
            foreach ($data['data'] as $v) {
                $result = [
                    'admin_id' => $adminId,
                    'mer_id' => $merId,
                    'wechat_news_id' => $id,
                    'title' => $v['title'],
                    "author" => $v['author'],
                    "synopsis" => $v['synopsis'],
                    "image_input" => $v['image_input'],
                    'content' => $v['content']['content'] ?? $v['content'],
                ];
                $make->create($result);
            }
        });

    }

    /**
     * @param int $id
     * @param int $merId
     * @author Qinii
     */
    public function delete(int $id,int $merId)
    {
        Db::transaction(function()use($id,$merId){
            $make = app()->make(ArticleRepository::class);
            $ids = $make->getKey($id,'wechat_news_id');
            foreach ($ids as $id) {$make->delete($id,$merId);}
            $this->dao->delete($id);
        });
    }

    /**
     * @param int $merId
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     */
    public function search(array $where, $page, $limit)
    {
        $query = $this->dao->getAll($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    public function git(int $id,int $merId)
    {
        return $this->dao->get($id,$merId);
    }

}
