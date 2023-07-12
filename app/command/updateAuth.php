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

// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\command;

use Swoole\Coroutine\MySQL\Exception;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\event\RouteLoaded;
use think\facade\Route;
use app\common\repositories\system\auth\MenuRepository;

class updateAuth extends Command
{
    protected $k = [];
    protected $kv =[];

    protected function configure()
    {
        // 指令配置
        $this->setName('setAuth')
            ->addArgument('prompt',Argument::OPTIONAL, 'php think menu [s] / [e]')
            ->setDescription('使用方法： `php think menu` , 可选传参数 s 只显示成功信息');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/15
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     */
    protected function execute(Input $input, Output $output)
    {
        $prompt = $input->getArgument('prompt');
        $output->writeln('开始执行');
        $output->writeln('<----------------');
        $output->writeln('开始平台权限');
        $sys = $this->routeList('sys',$prompt);

        $output->writeln('开始商户权限');

        $mer = $this->routeList('mer',$prompt);
        $output->writeln('<----------------');
        $output->writeln('平台执行成功，合计: '. $sys .'条 , 商户执行成功，合计: '. $mer .'条');
    }


    /**
     * @Author:Qinii
     * @Date: 2020/5/15
     * @param string|null $dir
     * @return mixed
     */
    public function routeList($type, $prompt)
    {
        $this->k = [];
        $this->kv = [];
        $this->app->route->setTestMode(true);
        $this->app->route->clear();
        $path = $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR;

        if ($type == 'sys')
            include $path . 'admin.php';
//            include $path . 'admin/config.php';
        if ($type == 'mer')
            include $path . 'merchant.php';
        //触发路由载入完成事件
        $this->app->event->trigger(RouteLoaded::class);
        $routeList = $this->app->route->getRuleList();
        $resp = [];

        foreach ($routeList as $k => $item) {
            if ($item['option'] && isset($item['option']['_auth']) && $item['option']['_auth']) {
                if (!(strpos($item['name'], '/') !== false) && !(strpos($item['name'], '@') !== false)) {
                    if (isset($item['option']['_init'])) {
                        $route = (new $item['option']['_init'][0]())->create($item, $item['option']['_init'][1]);
                    } else {
                        $route = [$item];
                    }
                    if ($route) {
                        foreach ($route as $one) {
                            if (!isset($one['option']['_name'])) $one['option']['_name'] = $one['name'];
                            $this->menu($one['option']['_path'] ?? '', $one['option'], $resp);
                        }
                    }
                }
            }
        }
        return app()->make(MenuRepository::class)->commandMenu($type, $resp, $prompt);
    }


    /**
     * TODO
     * @param $_path
     * @param $data
     * @param array $resp
     * @return array
     * @author Qinii
     * @day 3/18/22
     */
    protected function menu($_path, $data, &$resp = [], $isAppend = 0)
    {
        $check = true;
            if ($_path && is_array($data)) {
                $v = [
                    'route'     => $data['_name'],
                    'menu_name' => $data['_alias'] ?? '权限',
                    'params'    => $data['_params'] ?? '',
                ];
                if (!isset($data['_repeat']) || (isset($data['_repeat']) && !$data['_repeat'])){
                    $check = $this->checkRepeat($v['route'], $v['menu_name']);
                    $this->k[] = $v['route'];
                    $this->kv[$v['route']] = $v['menu_name'];
                }

                if (!$check) {
                    throw new Exception( "路由名重复 < " . $v['route']. ' >' . '「'. $v['menu_name']. ' 」');
                }
                if ($isAppend) {
                    $_path = 'append_'.$_path;
                }

                $resp[$_path][$data['_name']] = $v;

                if (isset($data['_append']) && !empty($data['_append'])) {
                    foreach ($data['_append'] as $datum) {
                        $datum['_repeat'] = true;
                        $this->menu($datum['_path'] ?? $data['_path'], $datum, $resp, 1);
                    }
                }
            }
        return $resp;
    }

    protected function checkRepeat($key, $value)
    {
        if (in_array($key, $this->k)) {
            if ($value != $this->kv[$key]) {
                return false;
            }
        }
        return true;
    }


}
