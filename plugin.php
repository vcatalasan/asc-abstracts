<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

require( plugin_dir_path( __FILE__) . 'includes/asc_abstract.php' );

class ASC_Abstract_Plugin extends ASC_Abstract {

    // plugin general initialization

    private static $instance = null;
    private static $plugin_name = __FILE__;

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    function __construct() {
        parent::__construct();

        // program basename and dir
        self::$settings += array( 'program' => array(
            'basename' => plugin_basename( __FILE__ ),
            'dir_path' => plugin_dir_path( __FILE__ ),
            'dir_url' => plugin_dir_url( __FILE__ )
            )
        );

        // register plugin
        register_activation_hook( self::$plugin_name, array( self::$instance, 'activate' ) ); // plugin activation actions
        register_deactivation_hook( self::$plugin_name, array( self::$instance, 'deactivate' ) );
    }

    /**
     * Activation hook for the plugin.
     */
    function activate() {

        //verify user is running WP 3.0 or newer
        if ( version_compare( get_bloginfo( 'version' ), '3.0', '<' ) ) {
            deactivate_plugins( self::$plugin_name ); // Deactivate our plugin
            wp_die( __( 'This plugin requires WordPress version 3.0 or higher.', 'asc-abstract' ) );
        }
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook for the plugin.
     */
    function deactivate() {
        flush_rewrite_rules();
    }

}
?>