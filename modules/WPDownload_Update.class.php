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
		$dto = new WPDownload_Update_DTO();
		if(!$dto->key)
			return false;
		//end bootstrap
		
		
		/**
		 * Update Actions 
		 */
		if (isset($_POST['action'])) {
			$this->log($_POST);
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
					$obj->download_link = 'http://localhost/update.php';
					$this->log("Request for information:");
					$this->log($obj);
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

class WPDownload_Update_DTO{
	
	public $key=false;
	public $response=array();
	
	function __construct(){
		$this->response = $_REQUEST;
		
		//check key
		if($_REQUEST['key'])
			$this->check_key();
	}
	
	/**
	 * Checks $_REQUEST['key'] against db for match
	 * @return boolean 
	 */
	private function check_key(){
		$this->key = $_REQUEST['key'];
		return true;
	}
}
?>
