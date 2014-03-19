<?php
namespace drg\wordpress;

class Plugin {

    const VERSION = '1.0.0';

    protected $plugin_slug = 'drg-baseplugin';
    protected static $instance = null;
    protected $log = null;

    private function __construct() {
        // Load translations
        add_action('init', array($this, 'load_plugin_textdomain'));

        // Activate plugin when new blog is added
        add_action('wpmu_new_blog', array($this, 'activate_new_site'));

        // Load public-facing style sheet and javascripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        $this->log = Logger::getLogger($this->plugin_slug);
        // Define custom functionality.
        // Refer to http://codex.wordpress.org/Plugin_API#Hooks:2C_Actions_and_Filters
        //add_action('@TODO', array($this, 'action_method_name'));
        //add_filter('@TODO', array($this, 'filter_method_name'));
    }

    public function get_plugin_slut() {
        return $this->plugin_slug;
    }

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public static function activate($network_wide) {
        if (function_exists('is_multisite') && is_multisite()) {
            if ($network_wide) {
                $blog_ids = self::get_blog_ids();

                foreach ($blog_ids as $blog_id) {
                    switch_to_blog($blog_id);
                    self::single_activate();
                }

                restore_current_blog();
            } else {
                self::single_activate();
            }
        } else {
            self::single_activate();
        }
    }

    public static function activate_new_site($blog_id) {
        if (did_action('wpmu_new_blog') !== 1) {
            return;
        }

        switch_to_blog($blog_id);
        self::single_activate();
        restore_current_blog();
    }

    private static function get_blog_ids() {
        global $wpdb;

        $sql = "SELECT blog_id FROM $wpdb->blogs
                WHERE archived = '0' AND spam = '0'
                AND deleted = '0'";

        return $wpdb->get_col($sql);
    }

    private static function single_activate() {
        // @TODO: Define activation functionality here
    }

    private static function single_deactivate() {
        // @TODO: Define deactivation functionality here
    }

    public function load_plugin_textdomain() {
        $domain = $this->plugin_slug;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, FALSE, basename(plugin_dir_path(dirname(__FILE__))) . '/languages/');
    }
}