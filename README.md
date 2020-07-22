## logStorageService

- 分布式日志收集demo
- PipeStreamHandler 基于 Monolog 格式


###  php bin/console list
```
LogStorageService 1.0.1

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help                        Displays help for a command
  list                        Lists commands
 create
  create:command              快速创建命令
 example
  example:pipe_local_log_out  例子: 输出日志到管道
  example:pipe_local_server   例子: 本地的管道Server 转发到对应的TCP Serevr
  example:tcp_server          例子: 接收Client的log 打印到终端
```

- 文件解释
```
/tmp/phplog_pipe #日志管道
/tmp/phplog_active_time # 管道激活时间
/tmp/php_local_logs/*.log  #放到管道中的数据如果管道未打开就放到这里
/tmp/php_local_logs/_TcpSendErr # 发送到TcpServer失败的log数据
```

### 流程
```

  UserRequest ---> Nginx----> php-fpm(log) ----> pipe(FIFO) ----->TCPServer(Qrigin)---->....

```
- 如果pipe 未打开则存储到/tmp/php_local_logs/{ServiceName}.log
- 如果TcpServer 未打开则存储到/tmp/php_local_logs/_TcpSendErr.log


### TCPServer Setting

```
                'worker_num' => 4,
                'open_length_check' => true,
                'package_length_type' => 'N',
                'package_length_offset' => 0, //第N个字节是包长度的值
                'package_body_offset' => 8, //第几个字节开始计算长度
                'package_max_length' => 1024 * 20, //协议最大长度
                'heartbeat_idle_time' => 60, // 表示一个连接如果*秒内未向服务器发送任何数据，此连接将被强制关闭
                'heartbeat_check_interval' => 5,  // 表示每*秒遍历一次

```

### TODO
```
- TcpServer 发送失败数据重发
- 优化重连机制
- 优化解析数据失败处理
- 优化socket Server 断开后数据短时间内的发包导致的数据丢失问题
- Tcp Server 数据过滤器
- 通知Service
- 多存储模式
```



