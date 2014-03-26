<?php
/**
 * @name    AbstractLoader
 * @package drg\wordpress
 * @author  Torkel Lyng
 * @copyright 2014 Torkel Lyng
 */

namespace drg\wordpress;

use drg\wordpress\Logger;

/**
 * AbstractLoader small baseclass responsible for bootstrapping Wordpress
 * plugins.
 *
 * Example usage:
 * <code>
 * <?php
 * namespace mynamespace;
 * use \drg\wordpress\AbstractLoader;
 *
 * class Loader extends AbstractLoader {
 *     protected static $plugins = '\\mynamespace\\Plugin';
 *     protected static $admin_plugins = '\\mynamespace\\admin\\Plugin';
 * }
 *
 * new Loader();
 * ?>
 * </code>
 */
abstract class AbstractLoader {

    /**
     * Name of class to initialize on Wordpress activation_hook. We're
     * expecting an instance of a class that inherits from
     * \drg\wordpress\AbstractPlugin.
     *
     * @var string
     */
    protected static $plugins = null;

    /**
     * Name of class to initialize at 'plugins_loaded' action when logged in
     * at Wordpress admin interface. We're expecting an instance of a class
     * that inherits from \drg\wordpress\AbstractPlugin
     *
     * @var string
     */
    protected static $admin_plugins = null;

    private $filename = null;
    private $namespace = null;
    private $log = null;

    /**
     * Introspects the instance of the class to determine which activation
     * hooks to register. Will also register a hook for the plugins_loaded
     * action both for the public facing interface and administrative backend.
     */
    function __construct() {
        $ref = new \ReflectionClass($this);
        $this->filename = $ref->getFileName();
        $this->namespace = $ref->getNamespaceName();
        $this->log = Logger::getLogger(addslashes($this->namespace));

        if (is_array(static::$plugins)) {
            foreach(static::$plugins as $plugin) {
                $this->register_plugin($plugin);
            }
        } else {
            $this->register_plugin(static::$plugins);
        }

        if (is_array(static::$admin_plugins)) {
            foreach(static::$admin_plugins as $plugin) {
                $this->register_admin_plugin($plugin);
            }
        } else {
            $this->register_admin_plugin(static::$admin_plugins);
        }
    }

    private function register_plugin($name) {
        if (WP_DEBUG) {
            $this->log->debug("Loading plugin: ".addslashes($name));
        }
        register_activation_hook($this->filename, array($name, 'base_activate'));
        register_deactivation_hook($this->filename, array($name, 'base_deactivate'));

        add_action('plugins_loaded', array($name, 'get_instance'));
    }

    private function register_admin_plugin($name) {
        if (WP_DEBUG) {
            $this->log->debug("Loading admin plugin: ".addslashes($name));
        }
        if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
            add_action('plugins_loaded', array($name, 'get_instance'));
        }
    }
}