<?php
namespace drg\wordpress;

use \Monolog\Logger as MonoLogger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\BrowserConsoleHandler;

class Logger {
    private function __construct() {
    }

    public static function getLogger($name) {
        $logger = new MonoLogger($name);
        $logger->pushHandler(new StreamHandler(WP_PLUGIN_DIR . "/logs/{$name}-info.log", MonoLogger::INFO));
        $logger->pushHandler(new StreamHandler(WP_PLUGIN_DIR . "/logs/{$name}-warn.log", MonoLogger::WARNING));
        $logger->pushHandler(new StreamHandler(WP_PLUGIN_DIR . "/logs/{$name}-error.log", MonoLogger::ERROR));
        $logger->pushHandler(new StreamHandler(WP_PLUGIN_DIR . "/logs/{$name}-debug.log", MonoLogger::DEBUG));

        // if (WP_DEBUG && (!defined('DOING_AJAX') || !DOING_AJAX)) {
        //     $logger->pushHandler(new BrowserConsoleHandler(), MonoLogger::DEBUG);
        // }
        return $logger;
    }
}
