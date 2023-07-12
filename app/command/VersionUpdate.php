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


namespace app\command;


use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;
use think\facade\Db;

class VersionUpdate extends Command
{
    protected function configure()
    {
        $this->setName('version:update')
            ->setDescription('crmeb_merchant 版本更新命令')
            ->addOption('package', 'p', Option::VALUE_REQUIRED, '指定更新包的路径');
    }

    protected function execute(Input $input, Output $output)
    {
        $flag = $output->confirm($input, '更新之前请务必做好数据库和代码的备份,防止数据或代码在更新中被覆盖 !!!', false);
        if (!$flag) return;
        $flag = $output->confirm($input, '请确保swoole服务和队列服务已关闭,防止意外报错', false);
        if (!$flag) return;

        $version = get_crmeb_version_code();
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $packagePath = $input->getOption('package') ?: 'auto_update.zip';
        $updateFilePath = app()->getRootPath() . ltrim($packagePath, '/= ');
        $updatePath = dirname($updateFilePath);
        $unzipPath = $updatePath . '/_update_runtime_' . str_replace('.', '_', $version);
        if (!is_file($updateFilePath)) {
            $output->warning($updateFilePath . ' 更新包不存在');
            return;
        }
        $zip = new \ZipArchive();
        if ($zip->open($updateFilePath) === true) {
            $zip->extractTo($unzipPath);
            $zip->close();
        } else {
            $output->warning($updateFilePath . ' 更新包打开失败');
            return;
        }

        $unlink = function () use ($unzipPath) {
            @unlink($unzipPath . '/update.sql');
            @unlink($unzipPath . '/update.zip');
            @unlink($unzipPath . '/AutoUpdate.php');
            @unlink($unzipPath . '/.env');
            @unlink($unzipPath . '/.config');
            @rmdir($unzipPath);
        };

        if (!is_file($unzipPath . '/.env') && !is_file($unzipPath . '/.config')) {
            $output->warning('文件不完整');
            $unlink();
            return;
        }

        if (is_file($unzipPath . '/.env')) {
            $env = parse_ini_file($unzipPath . '/.env', true) ?: [];
        }

        if (is_file($unzipPath . '/.config')) {
            $env = parse_ini_file($unzipPath . '/.config', true) ?: [];
        }
        if (($env['NAME'] ?? '') !== 'CRMEB-MERCHANT' || ((($env['OLD_VERSION'] ?? '') && ($env['OLD_VERSION'] ?? '') !== $version))) {
            if (($env['TYPE'] ?? '') !== 'MODEL') {
                $output->warning('版本号对比失败,请检查当前版本号(.version/更新包)是否被修改');
                $unlink();
                return;
            }
        }

        $confirm = [];
        if (isset($env['confirm'])) {
            $confirm = is_array($env['confirm']) ? $env['confirm'] : [$env['confirm']];
        }
        foreach ($confirm as $item) {
            if (!$output->confirm($input, $item, false)) {
                $unlink();
                return;
            }
        }
        $installHost = systemConfig('site_url');
        if (substr($installHost, 0, 5) == 'https'){
            $_url = str_replace('//' ,'\\\/\\\/', $installHost);
        } else {
            $_url = str_replace('http://' ,'http:\\\/\\\/', $installHost);
        }

        if (is_file($unzipPath . '/update.sql')) {
            $str = preg_replace('/--.*/i', '', file_get_contents($unzipPath . '/update.sql'));
            $str = preg_replace('/\/\*.*\*\/(\;)?/i', '', $str);
            $sqlList = explode(";\n", $str);
        } else {
            $sqlList = [];
        }
        $autoUpdateData = null;
        if (is_file($unzipPath . '/AutoUpdate.php')) {
            try {
                require_once $unzipPath . '/AutoUpdate.php';
                $autoUpdateData = new \crmeb\update\AutoUpdate($input, $output);
            } catch (\Throwable $e) {}
        }

        if ($autoUpdateData) $autoUpdateData->autoUpdateStart();
        $output->info('开始更新');
        $pre = env('database.prefix');
        try {
            Db::transaction(function () use ($pre, $output, $unzipPath, $sqlList, $autoUpdateData,$installHost,$_url) {
                if ($autoUpdateData) $autoUpdateData->autoUpdateBefore();
                $count = count($sqlList);
                if ($count && $autoUpdateData) {
                    $autoUpdateData->autoSqlBefore();
                }
                foreach ($sqlList as $idx => $sql) {
                    $sql = trim($sql, " \xEF\xBB\xBF\r\n");
                    if (!$sql) continue;
                    if ($pre && $pre !== 'eb_') {
                        $sql = str_replace('eb_', $pre, $sql);
                    }
                    $sql = str_replace('https://mer1.crmeb.net', $installHost , $sql);
                    $sql = str_replace('https:\\\/\\\/mer1.crmeb.net', $_url , $sql);
                    Db::query($sql . ';');
                    if (!($idx % 50)) {
                        $output->info("导入中($idx/$count)");
                    }
                }
                if ($count) {
                    if ($autoUpdateData) $autoUpdateData->autoSqlAfter();
                    $output->info('数据库更新成功');
                }
                $zip = new \ZipArchive();
                if ($zip->open($unzipPath . '/update.zip') === true) {
                    if ($autoUpdateData) $autoUpdateData->autoCopyBefore();
                    $zip->extractTo(app()->getRootPath());
                    $zip->close();
                    if ($autoUpdateData) $autoUpdateData->autoCopyAfter();
                } else {
                    throw new Exception('更新文件覆盖失败');
                }
            });
            if ($autoUpdateData) $autoUpdateData->autoUpdateAfter();
        } catch (\Throwable $e) {
            $output->warning('更新失败:' . $e->getMessage());
            $unlink();
            if ($autoUpdateData) $autoUpdateData->autoUpdateFail($e);
            return;
        }

        $unlink();
        if ($autoUpdateData) $autoUpdateData->autoUpdateEnd();
        $output->info('版本更新成功, 请重启swoole服务和队列服务');

        update_crmeb_compiled();
    }

}
