<?php

/**
 * The update server for downloaded plugins
 *
 * @author daithi
 */
class WPDownload_Update {

	public $version;
	protected $logger;

	function __construct() {

		global $wppp_logger;
		$this->logger = $wppp_logger;
		$this->version = "0.2";
	}

	/**
	 * Parses all requests for the updater 
	 */
	public function stdin() {
		
		/**
		 * Bootstrap
		 */
		//check action
		$actions = array('version','info','license');
		if(!in_array(@$_POST['action'], $actions))
			return;
		
		//check key
		if(!$dto->key)
			die(json_encode(array('error'=>'invalid key', 'data'=>$_REQUEST)));
		//end bootstrap
		
		
		/**
		 * Update Actions 
		 */
		if (isset($_POST['action'])) {
			$this->log("Check Update actions for client {$dto->key}");
			switch ($_POST['action']) {
				case 'version':
					echo $this->version;
					$this->log("Request for version");
					$this->log("Server Response: {$this->version}");
					break;
				case 'info':
					$res = new stdClass();
					$res->slug = 'wpcron/index.php';
					$res->plugin_name = 'WP Cron';
					$res->new_version = $this->version;
					$res->requires = '3.0';
					$res->tested = '3.3.1';
					$res->downloaded = 12540;
					$res->last_updated = '2012-01-12';
					$res->sections = array(
						'description' => 'The new version of the Auto-Update plugin',
						'another_section' => 'This is another section',
						'changelog' => 'Some new features'
					);
					$res->download_link = 'http://wordpress-schedule-post.com/wp-admin/admin-ajax.php?' . http_build_query(array(
						'action' => 'wp-plugin-packer_download',
						'key' => $dto->key,
						'slug' => 'wpcron/index.php',
						'verson' => '0.2'
					));
					die();
				case 'license':
					echo 'false';
					break;
			}
		}
		
		//print response and die
		echo json_encode($res);
		die();
	}

	/**
	 * Write to the log file
	 * @global Logger $wpp_logger The log4php Logger class
	 * @param string $msg The message to log
	 * @param string $method Default info. The Logger::$method() to call.
	 * @return Logger Returns the logger 
	 */
	protected function log($msg, $method = 'info') {
		$this->logger->configure(WPDOWNLOAD_DIR . '/Log4php.config.xml');
		$this->logger->$method($msg);
		return $this->logger;
	}

}

class WPDownload_DTO{
	
	public $key=false;
	public $response=array();
	public $tables;
	
	function __construct(){
		
		global $wppp_tables;
		$this->tables = $wppp_tables;
		$this->response = $_REQUEST;
		
		$this->check_key(@$_REQUEST['key']);
	}
	
	/**
	 * Checks $_REQUEST['key'] against db for match
	 * @global wpdb
	 * @return boolean 
	 */
	public function check_key($key){
		
		global $wpdb;
		$this->key = $key;
		
		$res = $wpdb->get_results($wpdb->prepare("
			SELECT * FROM {$wpdb->prefix}{$this->tables->client}
			WHERE key='%s'", array($this->key)));
		return $res;
	}
}
?>
