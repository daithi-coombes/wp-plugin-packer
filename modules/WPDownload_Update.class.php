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
	public function stdin(){
		$this->log($_REQUEST);
	}
	
	/**
	 * Write to the log file
	 * @global Logger $wpp_logger The log4php Logger class
	 * @param string $msg The message to log
	 * @param string $method Default info. The Logger::$method() to call.
	 * @return Logger Returns the logger 
	 */
	protected function log($msg, $method='info'){
		$this->logger->configure(WPDOWNLOAD_DIR . '/Log4php.config.xml');
		$this->logger->$method($msg);
		return $this->logger;
	}
}

?>
