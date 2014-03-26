<?php
/**
 * @name    AbstractPlugin
 * @package drg\wordpress
 * @author  Torkel Lyng
 * @copyright 2014 Torkel Lyng
 */

namespace drg\wordpress;

use \drg\wordpress\Logger;


abstract class AbstractAdminPlugin {
    protected static $instance = null;
    protected static $class = null;
    protected static $plugin_class = null;
    protected $plugin_slug = null;

    protected $plugin_screen_hook_suffix = null;

    protected $log = null;

    protected function __construct() {
        if (!class_exists(static::$plugin_class)) {
            die();
        }

        $plugin_instance_class = static::$plugin_class;
        $plugin = $plugin_instance_class::get_instance();
        $this->plugin_slug = $plugin->get_plugin_slug();
        $this->log = Logger::getLogger($this->plugin_slug);

        // Load public-facing style sheet and javascripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add the options page and menu item
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

        // Do some reflection to automatically register hooks and filters
        $ref = new \ReflectionClass($this);
        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (!preg_match('/(?<type>[a-zA-Z0-9]+)_(?<name>\w+)/', $method, $matches))
                continue;
            switch ($matches['type']) {
                case "action":
                    add_action($matches['name'], array($this, $matches[0]));
                    break;
                case "filter":
                    add_filter($matches['name'], array($this, $matches[0]));
                    break;
            }
        }
        $this->log->debug(addslashes(static::$class)." initialized");
    }

    /**
     * We're having a protected constructor so this method is responsible
     * for instanciating the correct class and returning an initialized
     * instance. By default this method is called by the 'plugins_loaded'
     * action from Wordpress.
     *
     * @return AbstractAdminPlugin
     */
    public static function get_instance() {
        if (static::$instance == null) {
            static::$instance = new static::$class;
        }

        return static::$instance;
    }

    public function add_plugin_admin_menu() {
        $this->log->debug("add_plugin_admin_menu called");
        $this->plugin_screen_hook_suffix = add_options_page(
            __('Page Title', $this->plugin_slug),
            __('Menu Text', $this->plugin_slug),
            'manage_options',
            $this->plugin_slug,
            array($this, 'display_plugin_admin_page')
        );
    }

    public function display_plugin_admin_page() {
        ?>
        <div class="wrap">
            <?php screen_icon('plugins'); ?>
            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

            <!-- @TODO: Provide markup for your options page here. -->

        </div>
        <?php
    }

    public function enqueue_styles() {
    }

    public function enqueue_scripts() {
    }
}
