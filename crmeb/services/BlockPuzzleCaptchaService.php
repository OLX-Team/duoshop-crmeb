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
namespace crmeb\services;

use Fastknife\Domain\Vo\PointVo;
use Fastknife\Exception\ParamException;
use Fastknife\Service\BlockPuzzleCaptchaService as baseBlockPuzzleCaptchaService;

class BlockPuzzleCaptchaService extends  baseBlockPuzzleCaptchaService
{
    /**
     * 验证
     * @param string $token
     * @param string $pointJson
     * @param null $callback
     */
    public function validate($token,  $pointJson, $callback = null)
    {
        //获取并设置 $this->originData
        $this->setOriginData($token);
        //数据处理类
        $blockData = $this->factory->makeBlockData();
        $pointJson = json_decode($pointJson);
        //解码出来的前端坐标
        $targetPoint = new PointVo($pointJson->x, $pointJson->y);
        //检查
        $blockData->check($this->originData['point'], $targetPoint);
        if (
            abs($pointJson->x - $targetPoint->x) <= $blockData->getFaultOffset() && $pointJson->y == $targetPoint->y
        ) {
            return;
        }
        if($callback instanceof \Closure){
            $callback();
        }
    }

    public function verificationByEncryptCode(string $encryptCode)
    {
        $result = explode('---',$encryptCode);
        if(empty($result)){
            throw new ParamException('参数错误！');
        }
        $this->validate($result[0], $result[1], function () use ($result,$encryptCode) {
            $cacheEntity = $this->factory->getCacheInstance();
            $cacheEntity->delete($result['token']);
            $cacheEntity->delete($encryptCode);
        });

    }
}
