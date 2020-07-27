<?php

declare(strict_types=1);
/**
 * This file is part of log_store.
 *
 * @author     alonexy@qq.com
 */

if (! function_exists('config_get')) {
    function config_get($k, $default = null)
    {
        return \App\Config::getInstance()->get($k, $default);
    }
}
