## CLI-Command

## php bin/console list

- 和laravel 使用方式保持一致

```
Console Tool

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
  help      Displays help for a command
  list      Lists commands
 test
  test:one  Test DEsc

```
### php bin/console test:one -h
```
Usage:
  test:one [options] [--] [<user>]

Arguments:
  user                  用户ID [default: "1"]

Options:
      --param2          参数2
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Test DEsc
```

### 解释
```
public $commandName = 'test:one {user=1 : 用户ID} {--param2 : 参数2}';

command:xxx {参数=默认值 : 解释} {--选项 : 解释}

# 获取方式
 $user = $this->argument('user');
 $param2 = $this->option('param2');
```


