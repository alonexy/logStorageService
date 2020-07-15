## logStorageService

- 分布式日志收集demo
- PipeStreamHandler 基于 Monolog 格式


###  操作命令
```
php bin/console tcp:server  #  打开tcpServer
php bin/console pipe:server #  打开 pipeServer 和TcpClient
php bin/console pipe:client #  输出日志
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

```



