<?php

declare(strict_types=1);

namespace app\command;

use app\common\model\article\Article;
use app\common\model\article\ArticleCategory;
use app\common\model\community\Community;
use app\common\model\community\CommunityTopic;
use app\common\model\store\broadcast\BroadcastGoods;
use app\common\model\store\broadcast\BroadcastRoom;
use app\common\model\store\Guarantee;
use app\common\model\store\product\Product;
use app\common\model\store\product\ProductAssistUser;
use app\common\model\store\product\ProductAttrValue;
use app\common\model\store\product\ProductGroupUser;
use app\common\model\store\product\ProductReply;
use app\common\model\store\product\Spu;
use app\common\model\store\service\StoreService;
use app\common\model\store\StoreCategory;
use app\common\model\store\StoreSeckillTime;
use app\common\model\system\attachment\Attachment;
use app\common\model\system\financial\Financial;
use app\common\model\system\merchant\Merchant;
use app\common\model\system\merchant\MerchantIntention;
use app\common\model\user\MemberInterests;
use app\common\model\user\User;
use app\common\model\user\UserBrokerage;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

class resetImagePath extends Command
{
    protected $path = '';

    protected $change = '/';

    protected $type = [3];

    protected $field = [
        'image',
        'image_input',
        'cover_img',
        'share_img',
        'feeds_img',
        'pic',
        'mer_avatar',
        'mer_banner',
        'images',
        'qrcode_url',
        'avatar_img',
        'avatar',
        'attachment_src',
        'brokerage_icon',
        'pics',
        'slider_image',
    ];

    protected function configure()
    {
        // 指令配置
        $this->setName('reset:imagePath')
            ->addArgument('path', Argument::OPTIONAL, 'path:http:/crmeb.com')
            ->addOption('url', null, Option::VALUE_REQUIRED, 'change:http:/crmeb1.com', '/')
            ->setDescription('php think reset:imagePath http://old.com --url http://new.com');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->path = rtrim($input->getArgument('path'), '/');
        if ($input->hasOption('url')) {
            $this->change = rtrim($input->getOption('url'), '/');
            if (!$this->change) $this->change = '/';
        }

        $output->writeln('开始执行');
        foreach ($this->type as $type) {
            $models = $this->switchModel($type);

            foreach ($models as $model) {

                $this->getResult($model, $type);
            }
        }
        $output->info('执行完成');
    }

    protected function getResult($model, $type)
    {
        if (is_null($model)) return;

        try {
        $key = $model->getPk();
        if ($key){
           $model->chunk(100, function ($data) {
                foreach ($data as $item) {
                    $save = 0;
                    foreach ($this->field as $f) {
                        if (isset($item->$f) && !empty($item->$f)) {
                            $sr = $this->changeImage($item->$f);
                            $item->$f = $sr;
                            $save = 1;
                        }
                    }
                    if ($save) $item->save();
                }
            });
        }
            return;
        } catch (\Exception $exception) {
            Log::info('图片处理异常：' . $exception->getMessage());
        }
    }

    protected function changeImage($data)
    {
        if (!$data) return $data;
        echo PHP_EOL;
        echo '替换前：';
        print_r($data);
        if (is_array($data)) {
            $load = implode(',', $data);
            $load1 =  str_replace($this->path, $this->change, $load);
            $string = explode(',', $load1);
        } else {
            $string =  str_replace($this->path, $this->change, $data);
        }
        echo PHP_EOL;
        echo '替换后：';
        print_r($string);
        echo PHP_EOL;
        return $string;
    }

    protected function switchModel($type)
    {
        $model =  [];
         // 商品规格
        $model[] = (new ProductAttrValue());
        // 商品规格
        $model[] = (new ProductAssistUser());
        // 商品规格
        $model[] = (new ProductGroupUser());
        // 商品
        $model[] = (new Product());
        // 直播间
        $model[] = (new BroadcastRoom());
        // 直播间商品
        $model[] = (new BroadcastGoods());
        // 服务保障
        $model[] = (new Guarantee());
        // 分类
        $model[] = (new StoreCategory());
        // 商品评价
        $model[] = (new ProductReply());
        // spu
        $model[] = (new Spu());
        // 文章
        $model[] = (new Article());
        // 文章分类
        $model[] = (new ArticleCategory());

        // 社区
        $model[] = (new Community());
        // 社区话题
        $model[] = (new CommunityTopic());

        // 流水
        $model[] = (new Financial());
        // 会员
        $model[] = (new UserBrokerage());
        // 会员权益
        $model[] = (new MemberInterests());
        // 商户
        $model[] = (new Merchant());
        // 商户权益
        $model[] = (new MerchantIntention());

        // 客服
        $model[] = (new StoreService());
        // 秒杀配置
        $model[] = (new StoreSeckillTime());
        // 素材
        $model[] = (new Attachment());
        // 用户
        $model[] = (new User());

        return $model;
    }
}

