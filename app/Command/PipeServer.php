<?php
namespace App\Command;

use Co\Channel;
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
            function () use($pipe,$pipeActive){
                Timer::tick(
                    1500, function () use ($pipeActive) {
                    //定时刷新激活时间
                    @file_put_contents($pipeActive, time());
                });

                $logServerTcpHost    = '127.0.0.1';
                $logServerTcpPort    = 9880;
                $logServerTcpTimeout = 50;
                $wg                  = new \Swoole\Coroutine\WaitGroup();
                $wg->add();
                $wg->add();
                $chan = new Channel(100);
                Co::create(
                    function () use ($chan, &$wg, $logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout) {
                        defer(
                            function () use (&$wg) {
                                $wg->done();
                            });
                        $client = new Co\Client(SWOOLE_SOCK_TCP);

                        if (!$client->connect($logServerTcpHost, $logServerTcpPort, $logServerTcpTimeout)) {
                            echo "connect failed. Error: {$client->errCode}".PHP_EOL;
                        }
                        else {
                            //TODO Logic
                            while (true) {
                                $data = $chan->pop();
                                if (empty($data)) {
                                    Co::sleep(1);
                                }
                                $type   = pack('N', 1001);
                                $length = pack('N', strlen($data));
                                //length+type+body
                                $packge = $length . $type . $data;

                                $res = $client->send($packge);
                                var_dump($packge);
                                var_dump($res.PHP_EOL);
                            }
                        }
                    });

                Co::create(
                    function () use ($chan, &$wg,$pipe) {
                        defer(
                            function () use (&$wg) {
                                $wg->done();
                            });
                        $handle  = fopen($pipe, "r");
                        $Buffers = "";
                        while ($handle) {
                            $resv = fread($handle, 1024);
                            if (!empty($resv)) {
                                echo "============================================" . PHP_EOL;
                                echo "RESVDATA :[  {$resv} ]" . PHP_EOL;
                                $resv = $Buffers . $resv;
                                list($exitBuffer, $buffer) = $this->parseLogData($resv,$chan);
                                if ($exitBuffer) {
                                    $Buffers = $buffer;
                                }
                                else {
                                    $Buffers = "";
                                }
//                var_dump("Buffers :[ {$Buffers} ]" . PHP_EOL);
                                echo "============================================" . PHP_EOL;
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
    public function parseLogData($resv,$chan)
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
                    return $this->parseLogData($resv,$chan);
                }
            }
            return [true, $metaData . $resv];
        }
        else {
            return [true, $resv];
        }

    }

}
