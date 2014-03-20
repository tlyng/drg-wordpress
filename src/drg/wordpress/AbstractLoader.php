<?php
/**
 * @name    AbstractLoader
 * @package drg\wordpress
 * @author  Torkel Lyng
 * @copyright 2014 Torkel Lyng
 */

namespace drg\wordpress;

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
 *     protected static $plugin_class = '\\mynamespace\\Plugin';
 *     protected static $plugin_admin_class = '\\mynamespace\\admin\\Plugin';
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
    protected static $plugin_class = '\\drg\\wordpress\\Plugin';

    /**
     * Name of class to initialize at 'plugins_loaded' action when logged in
     * at Wordpress admin interface. We're expecting an instance of a class
     * that inherits from \drg\wordpress\AbstractPlugin
     *
     * @var string
     */
    protected static $plugin_admin_class = '\\drg\\wordpress\\admin\\Plugin';

    /**
     * Introspects the instance of the class to determine which activation
     * hooks to register. Will also register a hook for the plugins_loaded
     * action both for the public facing interface and administrative backend.
     */
    function __construct() {
        $ref = new \ReflectionClass($this);
        $filename = $ref->getFileName();

        register_activation_hook($filename, array(static::$plugin_class, 'base_activate'));
        register_deactivation_hook($filename, array(static::$plugin_class, 'base_deactivate'));

        add_action('plugins_loaded', array(static::$plugin_class, 'get_instance'));

        // if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
        //     add_action('plugins_loaded', array($this->plugin_admin_class, 'get_instance'));
        // }
    }
}