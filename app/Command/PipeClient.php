<?php
namespace App\Command;

use App\Tool\Bytes;
use App\Tool\PipeStreamHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Swoole\Timer;

class PipeClient extends BaseCommand
{

    public $commandName = 'pipe:client';
    public $commandDesc = 'DESC';

    protected $levelMap = [
        Logger::DEBUG => 'debug',
        Logger::INFO => 'info',
        Logger::NOTICE => 'notice',
        Logger::WARNING => 'warning',
        Logger::ERROR => 'error',
        Logger::CRITICAL => 'critical',
        Logger::ALERT => 'alert',
        Logger::EMERGENCY => 'emergency',
    ];

    public function handle()
    {
        $this->logStore("TsetService","test",["user"=>1,time()],['xxx'=>111]);
    }
    function decrypt_pass($input, $key = 'df28a957671eab5436e6beeba2515b28')
    {
        $iv = '1172130435061718';
        $decrypted = openssl_decrypt(base64_decode($input), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }
    public function logStore($serviceName, $Message, array $Contexts,array $extra=[], $LogLevel = Logger::INFO, $Timeout = 50)
    {
        try {
            if (!isset($this->levelMap[$LogLevel])) {
                throw new \Exception("LogLevel Not Found.");
            }
            $pipe       = "/tmp/phplog_pipe";
            $pipeActive = "/tmp/phplog_active_time";
            if (!file_exists($pipeActive)) {
                throw new \Exception("PipeActiveTimeFile Not Exists.");
            }
            if (!file_exists($pipe)) {
                throw new \Exception("Pipe Not Exists.");
            }
            $ActiveTime = @file_get_contents($pipeActive);
//            echo " 最后激活时间 :" . date("Y-m-d H:i:s", $ActiveTime) . PHP_EOL;
            //大于5s
            if (bcsub(time(), $ActiveTime) > $Timeout) {
                throw new \Exception("pipe 激活时间超过{$Timeout}");
            }
            else {
                pcntl_signal(
                    SIGALRM, function ($sign) {
                    throw new \Exception("fopen : pcntl_signal[{$sign}] open  pipe Timeout 1.");
                }, false);
                pcntl_alarm(1);
                $handle = @fopen($pipe, "w");
                if ($handle === false) {
//                    pcntl_signal_dispatch();
                    throw new \Exception("fopen : open  pipe Failed 2.");
                }
                if ($handle) {
                    pcntl_alarm(0);
//                    stream_set_write_buffer($handle, 2048);
                    $logger     = new Logger("{$serviceName}", []);
                    $LogHandler = new PipeStreamHandler($handle, Logger::DEBUG);
                    $logger->pushHandler($LogHandler);
                    $logger->pushProcessor(function ($record) use($extra){
                        $record['extra'] = $extra;
                        return $record;
                    });
                    $func = $this->levelMap[$LogLevel];
                    $logger->$func($Message, $Contexts);
//                    var_dump("SUCC=>  ".$Message.PHP_EOL);
                    fclose($handle);
                }
            }
        }
        catch (\Exception $e) {
            //TODO 告警和存储本地文件...
//            var_dump("Exception : " . $e->getMessage());
            $localLogger     = new Logger("{$serviceName}", []);
            $localHandle     = fopen("/tmp/php_local_logs/{$serviceName}", "a+");
            $localLogHandler = new StreamHandler($localHandle, Logger::DEBUG);
            $localLogger->pushHandler($localLogHandler);
            $func = $this->levelMap[$LogLevel];
            $localLogger->$func($Message, $Contexts);
            fclose($localHandle);
        }

    }
}
