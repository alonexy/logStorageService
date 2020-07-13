<?php
namespace App\Command;

use App\Tool\TcpStreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Swoole\Coroutine as Co;
use Swoole\Coroutine;

class Client extends BaseCommand
{

    public $commandName = 'client:tcp';
    public $commandDesc = 'client Tcp test';

    public function handle()
    {
        Co\run(
            function () {

                $max = 50;

                for ($i = 0; $i < $max; $i++) {

                    Co::create(
                        function () {
                            $client = new Co\Client(SWOOLE_SOCK_TCP);
                            if (!$client->connect('127.0.0.1', 9880, 50)) {
                                echo "connect failed. Error: {$client->errCode}\n";
                            }
                            while (true) {
                                $data   = 'hello world111111rld1';
                                $type   = pack('N', 1001);
                                $length = pack('N', strlen($data));
                                //length+type+body
                                $packge = $length . $type . $data;
                                var_dump($packge);
                                $res = $client->send($packge);
                                var_dump($res);
                                var_dump($client->recv());
                                Co::sleep(3);
                            }
                        });

                }

//                $client->close();
            });

    }

}
