<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Exception\RuntimeException;


class BaseCommand extends Command
{

    public $commandName;
    public $commandDesc;
    protected $name;
    protected $input;
    protected $output;

    public function __construct()
    {
        if (!empty($this->commandName)) {
            $this->configureUsingFluentDefinition();
        }
        else {
            parent::__construct($this->name);
        }

        $this->setDescription($this->commandDesc);
    }
    /**
     * Configure the console command using a fluent definition.
     *
     * @return void
     */
    final protected function configureUsingFluentDefinition()
    {
        list($name, $arguments, $options) = self::parse($this->commandName);
        parent::__construct($name);

        foreach ($arguments as $argument) {
            $this->getDefinition()->addArgument($argument);
        }
        foreach ($options as $option) {
            $this->getDefinition()->addOption($option);
        }

    }

    /**
     * Get the value of a command argument.
     *
     * @param  string $key
     * @return string|array
     */
    public function argument($key = null)
    {
        if (is_null($key)) {
            return $this->input->getArguments();
        }

        return $this->input->getArgument($key);
    }

    /**
     * Get the value of a command option.
     *
     * @param  string $key
     * @return string|array
     */
    public function option($key = null)
    {
        if (is_null($key)) {
            return $this->input->getOptions();
        }

        return $this->input->getOption($key);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $methodExits = method_exists($this, 'handle');
        if (!$methodExits) {
            throw new RuntimeException(" Command handle Not Found.");
        }
        $this->input  = $input;
        $this->output = $output;
        return $this->handle()??0;
    }

    /**
     * Extract all of the parameters from the tokens.
     *
     * @param  array $tokens
     * @return array
     */
    protected static function parameters(array $tokens)
    {
        $arguments = [];

        $options = [];

        foreach ($tokens as $token) {
            if (!self::startsWith($token, '--')) {
                $arguments[] = static::parseArgument($token);
            }
            else {
                $options[] = static::parseOption(ltrim($token, '-'));
            }
        }

        return [$arguments, $options];
    }

    /**
     * Parse the given console command definition into an array.
     *
     * @param  string $expression
     * @return array
     */
    public static function parse($expression)
    {
        if (trim($expression) === '') {
            throw new \Exception('Console command definition is empty.');
        }

        preg_match('/[^\s]+/', $expression, $matches);

        if (isset($matches[0])) {
            $name = $matches[0];
        }
        else {
            throw new \Exception('Unable to determine command name from signature.');
        }

        preg_match_all('/\{\s*(.*?)\s*\}/', $expression, $matches);

        $tokens = isset($matches[1]) ? $matches[1] : [];
        if (count($tokens)) {
            return array_merge([$name], static::parameters($tokens));
        }

        return [$name, [], []];
    }

    /**
     * Parse an argument expression.
     *
     * @param  string $token
     * @return \Symfony\Component\Console\Input\InputArgument
     */
    protected static function parseArgument($token)
    {
        $description = null;

        if (self::contains($token, ' : ')) {
            list($token, $description) = explode(' : ', $token, 2);

            $token = trim($token);

            $description = trim($description);
        }

        switch (true) {
            case self::endsWith($token, '?*'):
                return new InputArgument(trim($token, '?*'), InputArgument::IS_ARRAY, $description);
            case self::endsWith($token, '*'):
                return new InputArgument(trim($token, '*'), InputArgument::IS_ARRAY | InputArgument::REQUIRED, $description);
            case self::endsWith($token, '?'):
                return new InputArgument(trim($token, '?'), InputArgument::OPTIONAL, $description);
            case preg_match('/(.+)\=(.+)/', $token, $matches):
                return new InputArgument($matches[1], InputArgument::OPTIONAL, $description, $matches[2]);
            default:
                return new InputArgument($token, InputArgument::REQUIRED, $description);
        }
    }

    /**
     * Parse an option expression.
     *
     * @param  string $token
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected static function parseOption($token)
    {
        $description = null;

        if (self::contains($token, ' : ')) {
            list($token, $description) = explode(' : ', $token);
            $token       = trim($token);
            $description = trim($description);
        }

        $shortcut = null;

        $matches = preg_split('/\s*\|\s*/', $token, 2);

        if (isset($matches[1])) {
            $shortcut = $matches[0];
            $token    = $matches[1];
        }

        switch (true) {
            case self::endsWith($token, '='):
                return new InputOption(trim($token, '='), $shortcut, InputOption::VALUE_OPTIONAL, $description);
            case self::endsWith($token, '=*'):
                return new InputOption(trim($token, '=*'), $shortcut, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $description);
            case preg_match('/(.+)\=(.+)/', $token, $matches):
                return new InputOption($matches[1], $shortcut, InputOption::VALUE_OPTIONAL, $description, $matches[2]);
            default:
                return new InputOption($token, $shortcut, InputOption::VALUE_NONE, $description);
        }
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ((string)$needle === static::substr($haystack, -static::length($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     *
     * @param  string $string
     * @param  int $start
     * @param  int|null $length
     * @return string
     */
    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * Return the length of the given string.
     *
     * @param  string $value
     * @return int
     */
    public static function length($value)
    {
        return mb_strlen($value);
    }
}
