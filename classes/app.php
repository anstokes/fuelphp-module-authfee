<?php

namespace AuthFee;

use ErrorException;
use Fuel\Core\Config;

class App
{
    public static function configuration()
    {
        // Load app configuration
        $configuration = Config::load(strtolower(__NAMESPACE__) . '::app', 'app');

        // Load default configuration, so we can show an error
        if (! $configuration) {
            $default = Config::load(strtolower(__NAMESPACE__) . '::app.example.php', 'app');

            // TODO - show configuration instructions
            throw new ErrorException('AuthFEE module is incorrectly configured.');
        }

        return $configuration;
    }


    public static function parameter($parameter, $default = '')
    {
        $configuration = static::configuration();
        return (isset($configuration[$parameter]) ? $configuration[$parameter] : $default);
    }
}
