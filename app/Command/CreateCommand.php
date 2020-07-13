<?php
namespace App\Command;
class CreateCommand extends BaseCommand
{

    public $commandName = 'create:command {class : 文件名称} {commandName=command:name : 命令名称}';
    public $commandDesc = '快速创建命令';

    public function handle()
    {
        $class          = $this->argument('class');
        $commandName    = $this->argument('commandName');
        $class          = ucwords(str_replace(['-', '_'], ' ', $class));
        $class          = str_replace(' ', '', $class);
        $commandName    = str_replace(' ', '', $commandName);
        $file           = __DIR__ . '/CommandTpl';
        $CommandContent = file_get_contents($file);
        $CommandContent = str_replace("CommandClass", $class, $CommandContent);
        $CommandContent = str_replace("NAME", $commandName, $CommandContent);

        file_put_contents(__DIR__ .'/'.$class.'.php',$CommandContent);

    }

}