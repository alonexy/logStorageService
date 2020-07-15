<?php
namespace App\Command;

use Co\Channel;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Swoole\Timer;
use Swoole\Coroutine as Co;

class PipeServer extends BaseCommand
{

    public $commandName = 'pipe:server';
    public $commandDesc = 'DESC';

    public function handle()
    {
        $pipe       = "/tmp/phplog_pipe";
        $pipeActive = "/tmp/phplog_active_time";
        $mode       = 0666;
        if (!file_exists($pipe)) {
            // create the pipe
            umask(0);
            posix_mkfifo($pipe, $mode);
        }
        $active_handle = @file_put_contents($pipeActive, time());
        if (!$active_handle) {
            throw new \Exception("激活时间文件无法读写 {$pipeActive} .");
        }
        #连接TCPServer
        Co\run(
            function () use ($pipe, $pipeActive) {
                Timer::tick(
                    1500, function () use ($pipeActive) {
                    //定时刷新激活时间
                    @file_put_contents($pipeActive, time());
                });

                $logServerTcpHost    = '127.0.0.1';
                $logServerTcpPort    = 9880;
                $logServerTcpTimeout = 10;
                $wg                  = new \Swoole\Coroutine\WaitGroup();

                $chan = new Channel(100);

                $tcpIsEnable = false;
                $retryTickId = 0;
                $retryTime   = 5000;

                $client = new Co\Client(SWOOLE_SOCK_TCP);
                if (!$client->connect($logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout)) {
                    echo "connect failed. Error: {$client->errCode}" . PHP_EOL;
                    if (empty($retryTickId)) {
                        $retryTickId = Timer::tick(
                            $retryTime, function () use (&$client, &$tcpIsEnable, $logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout) {
                            $client = new Co\Client(SWOOLE_SOCK_TCP);
                            if ($client->connect($logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout)) {
                                $tcpIsEnable = true;
                            }
                        });
                    }
                }
                else {
                    $tcpIsEnable = true;
                    //打开链接成功 开启心跳
                    Timer::tick(
                        7000, function () use (&$client, &$tcpIsEnable, &$retryTickId, $retryTime, $logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout) {
                        if ($tcpIsEnable) {
                            $heartData = "heart";
                            $type      = pack('N', 1002);
                            $length    = pack('N', strlen($heartData));
                            //length+type+body
                            $packge = $length . $type . $heartData;
                            $res    = $client->send($packge);
                            if (empty($res) && $client->errCode !== 0) {
                                $tcpIsEnable = false;
                                // 如果不存在定时重连
                                # fixme 优化重连机制
                                if (empty($retryTickId)) {
                                    $retryTickId = Timer::tick(
                                        $retryTime, function () use (&$client, &$tcpIsEnable, $logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout) {
                                        $client = new Co\Client(SWOOLE_SOCK_TCP);
                                        if ($client->connect($logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout)) {
                                            $tcpIsEnable = true;
                                        }
                                    });
                                }
                            }
                            else {
                                if ($retryTickId) {
                                    $clearRes = Timer::clear($retryTickId);
                                    if ($clearRes) {
                                        $retryTickId = 0;
                                    }
                                }
                            }
                        }
                    });
                }
                for ($i = 0; $i < 10; $i++) {
                    $wg->add();
                    Co::create(
                        function () use ($chan, &$wg, &$client, &$tcpIsEnable, &$retryTickId, $retryTime, $logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout) {
                            defer(
                                function () use (&$wg) {
                                    $wg->done();
                                });
                            //TODO Logic
                            while (true) {
                                $data = $chan->pop();
                                if (empty($data)) {
                                    Co::sleep(1);
                                }
                                if ($tcpIsEnable) {
                                    $type   = pack('N', 1001);
                                    $length = pack('N', strlen($data));
                                    //length+type+body
                                    $packge = $length . $type . $data;
                                    $res    = $client->send($packge);
                                    var_dump("发送 :{$res} , Code : {$client->errCode}");
                                    if (empty($res) && $client->errCode !== 0) {
                                        $tcpIsEnable = false;
                                        file_put_contents('/tmp/php_local_logs/_TcpSendErr', $data);
                                        // 如果不存在定时重连
                                        # fixme 优化重连机制
                                        if (empty($retryTickId)) {
                                            $retryTickId = Timer::tick(
                                                $retryTime, function () use (&$client, &$tcpIsEnable, $logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout) {
                                                $client = new Co\Client(SWOOLE_SOCK_TCP);
                                                if ($client->connect($logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout)) {
                                                    $tcpIsEnable = true;
                                                }
                                            });
                                        }

                                    }
                                    else {
                                        if ($retryTickId) {
                                            $clearRes = Timer::clear($retryTickId);
                                            if ($clearRes) {
                                                $retryTickId = 0;
                                            }
                                        }
                                    }
                                }
                                else {
                                    //本地存储
                                    file_put_contents('/tmp/php_local_logs/_TcpSendErr', $data);
                                }
                            }
                        });
                }
                $wg->add();
                Co::create(
                    function () use ($chan, &$wg, $pipe, &$tcpIsEnable) {
                        defer(
                            function () use (&$wg) {
                                $wg->done();
                            });
                        $handle  = fopen($pipe, "r");
                        $Buffers = "";
                        while ($handle) {
                            $resv = fread($handle, 1024);
                            if (!empty($resv)) {
//                                echo "============================================" . PHP_EOL;
//                                echo "RESVDATA :[  {$resv} ]" . PHP_EOL;
                                $resv = $Buffers . $resv;
                                list($exitBuffer, $buffer) = $this->parseLogData($resv, $chan);
                                if ($exitBuffer) {
                                    $Buffers = $buffer;
                                }
                                else {
                                    $Buffers = "";
                                }
//                var_dump("Buffers :[ {$Buffers} ]" . PHP_EOL);
//                                echo "============================================" . PHP_EOL;
                            }
                            else {
                                Co::sleep(1);
                            }
                        }
                    });

                $wg->wait();

            });


    }

    /**
     * log日志解析
     * @param $resv
     * @return array
     */
    public function parseLogData($resv, $chan)
    {
//        echo "parseLogData  ==>  {$resv}" . PHP_EOL;
        if (strlen($resv) > 8) {
            $metaData  = substr($resv, 0, 8); //type+length
            $resv      = substr($resv, 8);
            $metaArray = unpack('N2', $metaData);
            //type
            if ($metaArray[1] !== 888) {
                //TODO 本地存储解析失败的数据
                echo "LOG Parse 解析失败 :{$resv}" . PHP_EOL;

                return [false, ""];
            }
            else {
                //body length
                $length = $metaArray[2];
                if (strlen($resv) >= $length) {
                    //TODO 数据截取
                    $log  = substr($resv, 0, $length);
                    $resv = substr($resv, $length);
                    echo "LOG --> [ {$log} ] " . PHP_EOL;
                    $chan->push($log);
                    return $this->parseLogData($resv, $chan);
                }
            }
            return [true, $metaData . $resv];
        }
        else {
            return [true, $resv];
        }

    }

}
