<?php

declare(strict_types=1);
/**
 * This file is part of log_store.
 *
 * @author     alonexy@qq.com
 */

namespace App\Command\Examples;

use Alonexy\Pls\PlogStore;
use App\Command\BaseCommand;

class PipeClient extends BaseCommand
{
    public $commandName = 'example:pipe_local_log_out';

    public $commandDesc = '例子: 输出日志到本地管道';

    public function handle()
    {
        $Pls = new PlogStore();
        $Pls->setTimeout(3)->isEnabledInfo(true)->logStore('Example', 'testAll', ['user' => 1, time()], ['xxx' => 111]);
//        for ($i = 0; $i < 100; ++$i) {
//            $Pls->setTimeout(3)->isEnabledInfo(false)->logStore('Example', 'test=>' . $i, ['user' => 2, time()], ['xxx' => 222]);
//        }
    }
}
