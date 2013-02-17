<?php

/**
 * Plugin Name: WP Plugin Packer
 * Plugin URI: http://david-coombes.com
 * Description: Plugin for managing file zip downloads before, during and after sale.
 * Author: Daithi Coombes http://david-coombes.com
 * Version: 0.1
 * Author URI: http://david-coombes.com
 * 
 * @package wpdown_seller
 */
//debug?
error_reporting(E_ALL);
ini_set('display_errors', 'on');
//set_error_handler("wppp_error_handler");

//includes
require_once('debug.func.php');

/**
 * Globals
 */
//constants
global $wpdb;
global $wppp_tables;
$wppp_tables = (object) array(
	'client' => "{$wpdb->prefix}wppp_client"
);
define('WPDOWNLOAD_DIR', dirname(__FILE__));
//end Globals


/**
 * class autoloader
 */
spl_autoload_register("wpdownload_autoload");
/**
 * WP Download Autoloader
 * @param string $class The class name to try loading
 * @package WPDownloader
 */
function wpdownload_autoload($class){
	@include "modules/{$class}.class.php";
}
//end autoloader

/**
 * Construction
 */
//logging
if(!class_exists("Logger"))
	require_once( WPDOWNLOAD_DIR . "/includes/apache-log4php-2.3.0/Logger.php");
/** @var Logger The log4php logger global */
$wppp_logger = Logger::getLogger("wppp");
$wppp_logger->configure(WPDOWNLOAD_DIR . '/Log4php.config.xml');
$wppp_logger->info("Wordpresss Plugin Packager started");
//end logging

//plugin classes
$download = new WPDownload();
//end constructors

/**
 * Actions, hooks and filters 
 */
//add_action('plugins_loaded', array(&$updater, 'stdin'));	//@deprecated updater now needs to be in doc root
add_action('wp_ajax_nopriv_wp-plugin-packer_download', array(&$download, 'stdin'));
add_action('wp_ajax_wp-plugin-packer_download', array(&$download, 'stdin'));
register_activation_hook(__FILE__, "wp_plugin_packer_activate");
//end actions, hooks and filters

function wp_plugin_packer_activate(){
	
	require_once( ABSPATH . '/wp-admin/includes/upgrade.php');
	
	global $wpdb;
	$table = $wpdb->prefix . "wppp_client";
	$sql = "
		CREATE TABLE IF NOT EXISTS `{$table}` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`blog` varchar(250) NOT NULL,
		`key` varchar(250) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
		";
	dbDelta($sql);
}
function wppp_error_handler($errno, $errstr, $errfile, $errline){	
}