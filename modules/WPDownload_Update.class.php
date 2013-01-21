<?php

/**
 * The update server for downloaded plugins
 *
 * @author daithi
 */
class WPDownload_Update {

	protected $logger;

	function __construct() {

		global $wppp_logger;
		$this->logger = $wppp_logger;
	}

	/**
	 * Parses all requests for the updater 
	 */
	public function stdin() {
		
		/**
		 * Bootstrap
		 */
		$dto = new WPDownload_DTO();
		if(!$dto->key)
			return false;
		//end bootstrap
		
		
		/**
		 * Update Actions 
		 */
		if (isset($_POST['action'])) {
			
			switch ($_POST['action']) {
				case 'version':
					echo '1.1';
					$this->log("Request for version");
					$this->log("Server Response: 1.1");
					break;
				case 'info':
					$obj = new stdClass();
					$obj->slug = 'wpcron/index.php';
					$obj->plugin_name = 'WP Cron';
					$obj->new_version = '1.1';
					$obj->requires = '3.0';
					$obj->tested = '3.3.1';
					$obj->downloaded = 12540;
					$obj->last_updated = '2012-01-12';
					$obj->sections = array(
						'description' => 'The new version of the Auto-Update plugin',
						'another_section' => 'This is another section',
						'changelog' => 'Some new features'
					);
					$obj->download_link = 'http://wordpress-schedule-post.com/wp-admin/admin-ajax.php?' . http_build_query(array(
						'action' => 'wp-plugin-packer_download',
						'key' => $dto->key,
						'slug' => 'wpcron/index.php',
						'verson' => '0.2'
					));
				case 'license':
					echo 'false';
					break;
			}
		} else {
			return;
			header('Cache-Control: public');
			header('Content-Description: File Transfer');
			header('Content-Type: application/zip');
			readfile('update.zip');
		}
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
