<?php
/*
Plugin Name: BSC Abstracts
Plugin URI: http://www.bscmanage.com/plugins/bsc-abstracts/
Description: BSC Abstracts submission and management
Version: 1.0.0
Requires at least: WordPress 2.9.1 / Formidable Pro
Tested up to: WordPress 2.9.1 / BuddyPress 1.2
License: GNU/GPL 2
Author: Val Catalasan
Author URI: http://www.bscmanage.com/staff-profiles/
*/
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

require( plugin_dir_path( __FILE__) . 'plugin.php' );

// initialize plugin
add_action( 'init', array( 'BSC_Abstract_Plugin', 'get_instance' ) );

