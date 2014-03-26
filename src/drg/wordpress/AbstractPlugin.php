<?php
/**
 * @name    AbstractPlugin
 * @package drg\wordpress
 * @author  Torkel Lyng
 * @copyright 2014 Torkel Lyng
 */

namespace drg\wordpress;

/**
 * AbstractPlugin is a base class for quickly creating Wordpress plugins.
 * The basic principle behind this class (and this library overall) is
 * convention over configuration.
 *
 * Example usage:
 * <code>
 * <?php
 * namespace mynamespace;
 * use \drg\wordpress\AbstractPlugin;
 *
 * class Plugin extends AbstractPlugin {
 *     protected static $class = __CLASS__;
 *     protected static $plugin_slug = 'mynamespace-plugin';
 *
 *     protected function activate() {
 *         $this->log->info("I'm activated");
 *     }
 *
 *     protected function deactivate() {
 *         $this->log->info("I'm deactivated");
 *     }
 * }
 * ?>
 * </code>
 *
 * This class also does some magic reflection in it's constructor, if you have
 * public methods named "action_something..." or "filter_something.." they
 * will automatically be hooked up with Wordpress.
 *
 * Example:
 * <code>
 * <?php
 * namespace mynamespace;
 * use \drg\wordpress\AbstractPlugin;
 *
 * class Plugin extends AbstractPlugin {
 *     protected static $class = __CLASS__;
 *     protected static $plugin_slug = 'mynamespace-plugin';
 *
 *     public function action_wp_enqueue_scripts() {
 *         // This is the same as calling:
 *         // add_action('wp_enqueue_scripts', array($this, 'action_wp_enqueue_scripts'))
 *     }
 *
 *     public function filter_wp_somefilter() {
 *         // This is the same as calling:
 *         // add_filter('wp_somefilter', array($this, 'filter_wp_somefilter'));
 *     }
 * }
 * </code>
 */
abstract class AbstractPlugin {
    protected static $instance = null;
    protected static $class = null;
    protected static $plugin_slug = null;

    protected $log = null;

    abstract protected function activate();
    abstract protected function deactivate();

    /**
     * Initializes the plugin with custom logger and hooks up
     * some default actions and filters.
     *
     */
    protected function __construct() {
        $this->log = Logger::getLogger(static::$plugin_slug);
        // Load translations
        add_action('init', array($this, 'load_plugin_textdomain'));

        // Activate plugin when new blog is added
        add_action('wpmu_new_blog', array($this, 'activate_new_site'));

        // Load public-facing style sheet and javascripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

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
    }

    /**
     * We're having a protected constructor so this method is responsible
     * for instanciating the correct class and returning an initialized
     * instance. By default this method is called by the 'plugins_loaded'
     * action from Wordpress.
     *
     * @return AbstractPlugin
     */
    public static function get_instance() {
        if (static::$instance == null) {
            static::$instance = new static::$class;
        }
        return static::$instance;
    }

    /**
     * This method is called by the register_activation_hook.
     *
     * @param  bool $network_wide true if installed in a multisite environment
     */
    public static function base_activate($network_wide) {
        $instance = static::get_instance();
        if (function_exists('is_multisite') && is_multisite()) {
            if ($network_wide) {
                $blog_ids = $instance->get_blog_ids();

                foreach ($blog_ids as $blog_id) {
                    switch_to_blog($blog_id);
                    $instance->activate();
                }

                restore_current_blog();
            } else {
                $instance->activate();
            }
        } else {
            $instance->activate();
        }
    }

    /**
     * This method is called by the register_deactivation_hook.
     *
     * @param  bool $network_wide true if deactivated in a multisite environment
     */
    public static function base_deactivate($network_wide) {
        $instance = static::get_instance();
        if (function_exists('is_multisite') && is_multisite()) {
            if ( $network_wide ) {
                $blog_ids = $instance->get_blog_ids();

                foreach ($blog_ids as $blog_id) {
                    switch_to_blog($blog_id);
                    $instance->deactivate();
                }

                restore_current_blog();
            } else {
                $instance->deactivate();
            }

        } else {
            $instance->deactivate();
        }
    }

    /**
     * This method loads the default translation text. If you don't want to
     * follow my convention you have to override this method. By convention
     * the translation domain is set to the same as `static::$plugin_slug`
     * and it expects to find the translations in a directory called 'languages'
     * located in the same directory as the plugin class.
     *
     */
    public function load_plugin_textdomain() {
        $domain = static::$plugin_slug;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
        $ref = new \ReflectionClass($this);
        $filename = $ref->getFileName();
        $directory = dirname(plugin_basename($filename)) . '/languages/';

        if (WP_DEBUG) {
            $this->log->debug("Loading plugin textdomain from: {$directory}");
        }

        load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, false, $directory);
    }

    /**
     * Return the protected static::$plugin_slug value.
     *
     * @return string
     */
    public function get_plugin_slug() {
        return static::$plugin_slug;
    }

    /**
     * Called when new blog is created in a multisite environment.
     *
     * @param  int $blog_id
     */
    public function activate_new_site($blog_id) {
        if (did_action('wpmu_new_blog') !== 1) {
            return;
        }

        switch_to_blog($blog_id);
        $this->activate();
        restore_current_blog();
    }

    /**
     * Get hold of available blogs in a multisite environment.
     *
     * @return int[]
     */
    private function get_blog_ids() {
        global $wpdb;

        $sql = "SELECT blog_id FROM $wpdb->blogs
                WHERE archived = '0' AND spam = '0'
                AND deleted = '0'";

        return $wpdb->get_col($sql);
    }

    /**
     * Convenient method for enqueueing public facing stylesheets.
     *
     */
    public function enqueue_styles() {
        // Should be overridden by concrete plugin
    }

    /**
     * Convenient method for enqueueing public facing javascripts.
     *
     */
    public function enqueue_scripts() {
        // Should be overridden by concrete plugin
    }
}