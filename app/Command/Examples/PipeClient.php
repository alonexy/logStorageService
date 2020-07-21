<?php
namespace App\Command\Examples;

use Alonexy\Pls\PlogStore;
use App\Command\BaseCommand;
use Swoole\Timer;

class PipeClient extends BaseCommand
{

    public $commandName = 'example:pipe_local_log_out';
    public $commandDesc = '例子: 输出日志到管道';

    public function handle()
    {
        $Pls = new PlogStore();
        $Pls->setTimeout(3)->isEnabledInfo(true)->logStore("Example", "testAll", ["user" => 1, time()], ['xxx' => 111]);
        $Pls->setTimeout(3)->isEnabledInfo(false)->logStore("Example", "test", ["user" => 2, time()], ['xxx' => 222]);
    }
}
