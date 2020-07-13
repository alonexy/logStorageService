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

```





