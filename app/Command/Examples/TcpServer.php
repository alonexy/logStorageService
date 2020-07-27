<?php

declare(strict_types=1);
/**
 * This file is part of log_store.
 *
 * @author     alonexy@qq.com
 */

namespace App\Command\Examples;

use App\Command\BaseCommand;
use App\Filter\CrisisFilter;
use Swoole\Coroutine as Co;

class TcpServer extends BaseCommand
{
    public $commandName = 'example:tcp_server {--d : 是否以守护进程模式启动}';

    public $commandDesc = '例子: 接收Client的log 打印到终端';

    public function handle()
    {
        Co::set(['hook_flags' => SWOOLE_HOOK_ALL]);
        $daemonize = $this->option('d');
        stream_filter_register('crisis_filter', CrisisFilter::class);
        $serv = new \Swoole\Server('127.0.0.1', 9880, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $serv->set(
            [
                'worker_num' => 4,
                'dispatch_mode' => 2,
                'open_length_check' => true,
                'package_length_type' => 'N',
                'package_length_offset' => 0, //第N个字节是包长度的值
                'package_body_offset' => 8, //第几个字节开始计算长度
                'package_max_length' => 1024 * 20, //协议最大长度
                'heartbeat_idle_time' => 60, // 表示一个连接如果*秒内未向服务器发送任何数据，此连接将被强制关闭
                'heartbeat_check_interval' => 10,  // 表示每*秒遍历一次
                'reload_async'=>true,
                'max_wait_time'=>10,
                'daemonize'=>$daemonize,
            ]
        );
        $serv->on('start', [$this, 'onStart']);
        $serv->on('ManagerStart', [$this, 'onManagerStart']);
        $serv->on('workerstart', [$this, 'onWorkerStart']);
        $serv->on('connect', [$this, 'onConnect']);
        $serv->on('receive', [$this, 'onReceive']);
        $serv->on('close', [$this, 'onClose']);
        $serv->start();
    }

    public function onStart($server)
    {
        dump("Master Start PID: {$server->master_pid} ");
    }

    public function onManagerStart($server)
    {
        dump("ManagerStart PID: {$server->manager_pid} ");
    }

    public function onWorkerStart($server, $workerId)
    {
        dump("StartWorkerId : {$workerId}");
        $chan = new Co\Channel(10);
        $server->workerChan = $chan;
        for ($i = 0; $i < 300; ++$i) {
            Co::create(function () use (&$chan) {
                for (;;) {
                    if ($chan->isEmpty()) {
                        Co::sleep(1);
                    }
                    $cData = $chan->pop();
                    if ($cData) {
                        $handle = fopen('./data.txt', 'a+');
                        stream_filter_append($handle, 'crisis_filter');
                        fwrite($handle, $cData);
                    }
                }
            });
        }
    }

    public function onConnect($server, $fd)
    {
        dump("Client: {$fd} Connect.");
    }

    public function onReceive($server, $fd, $from_id, $data)
    {
        $metaData = unpack('N2', substr($data, 0, 8));
        $type = $metaData[2];
        switch ($type) {
            case 1001:
                //logdata
                $data = substr($data, 8);
                echo "{$fd}-{$from_id} -> Receive: {$data}" . PHP_EOL;
                $server->workerChan->push($data);
                break;
            case 1002:
                //heart
                $data = substr($data, 8);
                echo "{$fd}-{$from_id} -> Receive Heart: {$data}" . PHP_EOL;
                break;
        }
    }

    public function onClose($server, $fd)
    {
        dump("Client: {$fd} Close.");
    }
}
