<?php
/*
Plugin Name: ASC Abstracts
Plugin URI: http://www.bscmanage.com/plugins/asc-abstracts/
Description: ASC Abstracts submission and management
Version: 1.0.6
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
add_action( 'init', array( 'ASC_Abstracts_Plugin', 'get_instance' ) );

