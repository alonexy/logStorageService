<?php
namespace App\Command\Examples;

use App\Command\BaseCommand;
use Swoole\Coroutine as Co;
use Swoole\Timer;

class TcpServer extends BaseCommand
{

    public $commandName = 'example:tcp_server';
    public $commandDesc = '例子: 接收Client的log 打印到终端';


    public function handle()
    {
        $serv = new \Swoole\Server("127.0.0.1", 9880, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $serv->set(
            array(
                'worker_num' => 4,
                'open_length_check' => true,
                'package_length_type' => 'N',
                'package_length_offset' => 0, //第N个字节是包长度的值
                'package_body_offset' => 8, //第几个字节开始计算长度
                'package_max_length' => 1024 * 20, //协议最大长度
                'heartbeat_idle_time' => 60, // 表示一个连接如果*秒内未向服务器发送任何数据，此连接将被强制关闭
                'heartbeat_check_interval' => 5,  // 表示每*秒遍历一次
            ));
        $serv->on(
            'start', function ($server) {

        });
        $serv->on(
            "ManagerStart", function ($Manger) {

            var_dump("ManagerStart =>" . date("Y-m-d H:i:s", time()) . PHP_EOL);
        });
        $serv->on(
            "workerstart", function () {

        });
        $serv->on(
            'connect', function ($serv, $fd) {
            echo "Client: {$fd} Connect." . PHP_EOL;
        });

        $serv->on(
            'receive', function ($serv, $fd, $from_id, $data) {

            $metaData = unpack("N2", substr($data, 0, 8));
            $type     = $metaData[2];
            switch ($type) {
                case 1001:
                    //logdata
                    $data = substr($data, 8);
                    echo "{$fd}-{$from_id} -> Receive: {$data}" . PHP_EOL;
                    break;
                case 1002:
                    //heart
                    break;
            }

//            $serv->send($fd, "Server: " . $data);
        });

        $serv->on(
            'close', function ($serv, $fd) {
            echo "Client: {$fd} Close." . PHP_EOL;
        });
        $serv->start();

    }
}