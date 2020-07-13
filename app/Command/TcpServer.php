<?php
namespace App\Command;

use Predis\Client;
use Swoole\Coroutine as Co;
use Swoole\Timer;

/**
 * Created by PhpStorm.
 * User: alonexy
 * Date: 20/7/2
 * Time: 17:12
 */
class TcpServer extends BaseCommand
{

    public $commandName = 'tcp:server';
    public $commandDesc = 'tcp log Server ';


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
            $data = substr($data, 8, -1);
            echo "{$fd}-{$from_id} -> Receive: {$data}" . PHP_EOL;
//            $serv->send($fd, "Server: " . $data);
        });

        $serv->on(
            'close', function ($serv, $fd) {
            echo "Client: {$fd} Close." . PHP_EOL;
        });
        $serv->start();

    }
}