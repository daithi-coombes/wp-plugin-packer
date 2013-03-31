<?php
/*
  Plugin Name: Wordpress Cron
  Plugin URI: http://david-coombes.com
  Description: Automatically run cron jobs
  Author: Daithi Coombes
  Version: 0.2
  Author URI: http://david-coombes.com
 */

/**
 * The CCC (Control Command Center) hits admin-ajax.php This 
 * page then requests the homepage using phpCurl which thus triggers the 
 * wordpress crons and scheduled posts. To ensure that the scheduled posts are 
 * definitely sent the hit.php page should be called once every 10 mins after 
 * its due for one hour.
 * 
 * The reason the CCC doesn't directly hit the homepage is to save bandwidth.
 * Hitting the homepage will mean download the page to the CCC, where as hitting
 * the admin-ajax.php allows wpcron to run checks to make sure post went out.
 * 
 * Scheduled Post's
 * 		plugin hooks into wordpress posts. When a post is set for a future date
 * 		the timestamp, key, blog_url are sent to the CCC. This is a default
 * 		feature of the plugin and requires no installation.
 * 
 * Custom crons
 * 		For developers custom crons can be set up. A script url, key, blog_url
 * 		and timestamp are sent to the CCC
 */
error_reporting(E_ALL);
ini_set('display_errors', 'on');

//globals
$wp_cron_ccc_server = "http://david-coombes.com/ccc/setAlarm.php";
$version = "0.2";
define('WPCRON_CUSTOMER_KEY', "e3f8e543e968d7d0390a71bf3c4c2144");

//constants
define('WPCRON_DIR', dirname(__FILE__));
define('WPCRON_URL', 'http://wp_cron.loc/ccc');


//includes
require_once( WPCRON_DIR .'/application/includes/debug.func.php');

/**
 * Set up logger?
 */
if(!class_exists("Logger"))
	require_once( WPCRON_DIR . '/application/includes/apache-log4php-2.3.0/Logger.php');
Logger::configure( WPCRON_DIR . '/application/includes/Log4php.config.xml');
$logger = Logger::getLogger("wp-cron");
wpcron_log("Script started");

/**
 * actions
 */
add_action('save_post', 'wpcron_check_post', 10, 2);
add_action('wp_ajax_wpcron_alarm', 'wpcron_alarm');
add_action('wp_ajax_nopriv_wpcron_alarm', 'wpcron_alarm');
add_action('init', 'wpcron_activate_au');
function wpcron_activate_au()
{
	require_once(WPCRON_DIR . '/application/includes/wp_autoupdate.php');
	global $version;
	$update_server = 'http://wp-cron.loc/wp-admin/admin-ajax.php?' . http_build_query(array(
		'action' => 'wpdownload_update'
	));
	$plugin_slug = plugin_basename(__FILE__);
	new wp_auto_update ($version, $update_server, $plugin_slug);
}

/**
 * Ajax Callback. Alarm Request.
 * 
 * This callback gets called when an alarm is requested from the Central
 * Command Center. Will make request to block homepage to try activate
 * scheduled posts, will then check that the post status values in the db are
 * set correctly, will return json.
 * @return json Prints json result and dies() 
 */
function wpcron_alarm(){
	wpcron_log("Alarm requested:");
	
	/**
	 * Security 
	 */
	if($_REQUEST['key']!=$key)
		die( json_encode(array('error'=>'Invalid Key')));
	
	//get home page
	$res = wp_remote_get( get_bloginfo('wpurl') );
	wpcron_log("Made touch at ".time());
	wpcron_log($res);
	
	//check for error
	if($res['code']!='200')
		die( json_encode(array('error'=>'Can\'t ping homepage','data'=>$res)) );
	
	//check db for scheduled posts
	
	//return true
	die( json_encode(array('ok'=>true,'data'=>$res)));
}

/**
 * Hook for checking all posts, if post is set for the future then
 * wpcron_ccc_send() is called to send the curl request.
 *
 * @param integer $postID The post id
 * @param stdClass $post The post object
 * @return void 
 */
function wpcron_check_post($postID, $post) {

	wpcron_log("Checking post {$postID}..");
	
	/**
	 * make sure post is for future and not revision etc.
	 */
	if(
		(@$post->post_status!='future') ||
		(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
	) return;
	
	/**
	 * logging
	 * @deprecated
	 */
	$str = "";
	foreach($post as $key => $val)
		$str .= "\t{$key}\t\t\t=>\t{$val}\n";
	wpcron_log($str);
		
	/**
	 * if all is ok then send request.
	 */
	wpcron_ccc_send( $post->post_date_gmt );
}

/**
 * Log a string. Log file is at WP_CRON_DIR/log.txt
 *
 * @param string $str 
 */
function wpcron_log($str) {

	global $logger;
	$logger->info($str);
	return;
	
	$log = WPCRON_DIR . "/log.txt";
	$fp = fopen($log, "a+");
	fwrite($fp, time() . " - {$str}\n");
	fclose($fp);
}

/**
 * Send an alarm request to the ccc server.
 *
 * @global string $key
 * @param string $date The post_gmt date from the post object
 */
function wpcron_ccc_send( $date ) {

	if(empty($date)) return;
	
	wpcron_log("sending post for {$date}");
	
	/**
	 * vars
	 */
	global $wp_cron_key;
	global $wp_cron_ccc_server;
	$blog = admin_url('admin-ajax.php') . "?action=wpcron_alarm";
	$gmt_offset = get_option('gmt_offset');
	$now = time();
	$timezone_string = get_option('timezone_string');
	
	/**
	 * Send request 
	 */
	$http = new WP_Http();
	$params = array(
		'blog' => $blog,
		'logged' => $now,
		'gmt_offset' => $gmt_offset,
		'timezone_string' => $timezone_string,
		'alarm' => $date,
		'key' => $wp_cron_key
	);
	wpcron_log($params);
	/**
	 * send request
	 * @deprecated use wp_remote_post instead
	 */
	$ch = curl_init($wp_cron_ccc_server);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	$output = curl_exec($ch);
	$info = curl_getinfo($ch);
	wpcron_log("response => \n" . $output);
}