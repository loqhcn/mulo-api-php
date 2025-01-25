<?php

declare(strict_types=1);

namespace mulo\library\console;

use think\facade\Config;
use Symfony\Component\Process\PhpExecutableFinder;
use think\helper\Arr;

/**
 * Composer handles the composer process and its associated functions
 *
 * @package october\process
 * @author Alexey Bobkov, Samuel Georges
 */
class Composer extends ProcessBase
{
    /**
     * install runs the "composer install" command
     */
    public function install()
    {
        return $this->runComposerCommand('install');
    }

    /**
     * update runs the "composer update" command
     */
    public function update()
    {
        return $this->runComposerCommand('update');
    }

    /**
     * require runs the "composer require" command
     */
    public function require(...$packages)
    {
        return $this->runComposerCommand(...array_merge(['require'], $packages));
    }

    /**
     * remove runs the "composer remove" command
     */
    public function remove(...$packages)
    {
        return $this->runComposerCommand(...array_merge(['remove'], $packages));
    }

    /**
     * addRepository will add a repository to the composer config
     */
    public function addRepository($name, $type, $address)
    {
        return $this->runComposerCommand(
            'config',
            "repositories.{$name}",
            $type,
            $address
        );
    }

    /**
     * removeRepository will remove a repository to the composer config
     */
    public function removeRepository($name)
    {
        return $this->runComposerCommand(
            'config',
            '--unset',
            "repositories.{$name}"
        );
    }

    /**
     * isInstalled returns true if composer is installed
     */
    public function isInstalled()
    {
        $this->runComposerCommand('--version');
        return $this->lastExitCode() === 0;
    }


    /**
     * 获取 composer 版本信息
     */
    public function getVersion($output = null)
    {
        if (!$output) {
            // 运行查询版本
            $this->runComposerCommand('--version');

            $output = $this->getOutput();
        }

        $info = $output[0] ?? '';
        $infoArr = explode(' ', $info);

        $version = '';
        foreach ($infoArr as $in) {
            if (is_version_str($in)) {
                $version = $in;
                break;
            }
        }

        return $version;
    }

    /**
     * listPackages returns a list of installed packages
     */
    public function listPackages()
    {
        $installed = json_decode($this->runComposerCommand(
            'show',
            '--direct',
            '--format=json'
        ), true);

        $packages = [];

        foreach (Arr::get($installed, 'installed', []) as $package) {
            $package['version'] = ltrim(Arr::get($package, 'version'), 'v');
            $packages[] = $package;
        }

        return $packages;
    }

    /**
     * runComposerCommand is a helper for running a git command
     */
    protected function runComposerCommand(...$parts)
    {
        return $this->run($this->prepareComposerArguments($parts));
    }

    /**
     * prepareComposerArguments is a helper for preparing arguments
     */
    protected function prepareComposerArguments($parts)
    {
        // if ($composerBin = Config::get('system.composer_binary')) {
        //     return implode(' ', array_merge([$composerBin], $parts));
        // }

        $composerPath = $this->rootPath . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'composer';
        if (file_exists($composerPath)) {
            $phpBin = get_php();        // 获取 php 可执行文件绝对路径
            
            return (strpos(strtolower(PHP_OS), 'linux') !== false && request()->isCli() ? 'sudo -u www ' : '') . implode(' ', array_merge([
                '"' . $phpBin . '"',
                $composerPath
            ], $parts));
        } else {
            return implode(' ', array_merge(['composers'], $parts));
        }
    }
}
