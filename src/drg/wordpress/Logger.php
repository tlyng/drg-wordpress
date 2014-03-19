<?php
namespace drg\wordpress;

use \Monolog\Logger as MonoLogger;
use \Monolog\Handler\StreamHandler;

class Logger {
    private function __construct() {
    }

    public static function getLogger($name) {
        $logger = new MonoLogger($name);
        $logger->pushHandler(new StreamHandler(WP_PLUGIN_DIR . "/logs/{$name}-info.log", MonoLogger::INFO));
        $logger->pushHandler(new StreamHandler(WP_PLUGIN_DIR . "/logs/{$name}-warn.log", MonoLogger::WARNING));
        $logger->pushHandler(new StreamHandler(WP_PLUGIN_DIR . "/logs/{$name}-error.log", MonoLogger::ERROR));
        $logger->pushHandler(new StreamHandler(WP_PLUGIN_DIR . "/logs/{$name}-debug.log", MonoLogger::DEBUG));

        return $logger;
    }
}
