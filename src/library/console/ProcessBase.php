<?php

declare(strict_types=1);

namespace mulo\library\console;

/**
 * ProcessBase is a base class for all other process classes
 *
 * @package october\process
 * @author Alexey Bobkov, Samuel Georges
 */
class ProcessBase
{
    /**
     * @var string output stores the resulting output as a string
     */
    protected $output;

    /**
     * @var int exitCode stores the previous error code
     */
    protected $exitCode;

    /**
     * @var string basePath stores the directory where the process is called
     */
    protected $rootPath;

    /**
     * @var Closure|null useCallback
     */
    protected $useCallback = null;

    /**
     * __construct
     */
    public function __construct($rootPath = null)
    {
        $this->rootPath = $rootPath ? : root_path();
    }

    /**
     * run executes the process with the current configuration
     */
    public function run($command)
    {
        if ($this->useCallback !== null) {
            return $this->runCallback($command, $this->useCallback);
        }

        return $this->runNow($command);
    }

    /**
     * runNow executes the process and captures completed output
     */
    public function runNow($command)
    {
        $this->output = '';

        $result = [];
        $code = null;
        exec($command . ' 2>&1', $result, $code);

        $this->output = array_values(array_filter($result));        // 过滤空行
        $this->exitCode = $code;

        return $this->output;
    }

    /**
     * runCallback executes the process with streamed output
     */
    public function runCallback($command, $callback)
    {
        $this->output = '';

        $handle = popen($command . ' 2>&1', 'r');

        while (!feof($handle)) {
            $this->output .= $callback(fread($handle, 4096));
        }

        $this->exitCode = pclose($handle);

        return $this->output;
    }

    /**
     * setCallback instructs commands to execute output as a callback
     */
    public function setCallback($callback)
    {
        $this->useCallback = $callback;
    }

    /**
     * lastExitCode returns the last known exit code
     */
    public function lastExitCode()
    {
        return $this->exitCode;
    }

    /**
     * 同步执行获取输出结果
     */
    public function getOutput()
    {
        return $this->output;
    }
}
