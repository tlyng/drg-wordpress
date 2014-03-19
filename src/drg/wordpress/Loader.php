<?php

namespace drg\wordpress;

class Loader {

    protected $plugin_class = '\\drg\\wordpress\\Plugin';
    protected $plugin_admin_class = '\\drg\\wordpress\\admin\\Plugin';

    function __construct() {
        register_activation_hook(__FILE__, array($this->plugin_class, 'activate'));
        register_deactivation_hook(__FILE__, array($this->plugin_class, 'deactivate'));

        add_action('plugins_loaded', array($this->plugin_class, 'get_instance'));

        // if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
        //     add_action('plugins_loaded', array($this->plugin_admin_class, 'get_instance'));
        // }
    }
}