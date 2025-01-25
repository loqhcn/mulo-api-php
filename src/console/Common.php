<?php

declare(strict_types=1);

namespace mulo\console;

use think\console\Input;
use think\console\Output;
use think\console\Command as BaseCommand;


class Command extends BaseCommand
{
    protected $input = null;
    protected $output = null;
    protected $commonCb = null;

    /**
     * 执行帮助命令
     */
    protected function execute(Input $input, Output $output)
    {
        $this->input = $input;
        $this->output = $output;

        if ($this->commonCb && $this->commonCb instanceof \Closure) {
            ($this->commonCb)($input, $output);
        }

        $code = $input->getArgument('code');
        $code = $code ?: '1';

        $this->choose($code);
    }


    /**
     * 选择要执行的命令
     */
    public function choose($code)
    {
        $commands = $this->commands;
        $codes = array_column($commands, 'code');
        $names = array_column($commands, 'name');

        if (!in_array($code, $codes) && !in_array($code, $names)) {
            $this->output->writeln("已取消");
            return true;
        }

        $commands = array_column($commands, null, 'code');
        $name = isset($commands[$code]) ? $commands[$code]['name'] : $code;
        $name = \think\helper\Str::camel($name);
        if (method_exists($this, $name)) {
            $this->{$name}();
        } else {
            $this->cancel();
        }
    }


    /**
     * 取消操作
     */
    public function cancel()
    {
        $this->output->writeln("已取消");
        return true;
    }
}
