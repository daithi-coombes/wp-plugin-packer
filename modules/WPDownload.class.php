<?php

/**
 * Description of WPDownload
 *
 * @author daithi
 */
class WPDownload {

	private $logger;

	function __construct() {

		global $wppp_logger;
		$this->logger = $wppp_logger;
	}

	public function stdin() {
		$this->log("WPDownload::stdin()");
		$this->log($_REQUEST);
	}

	private function pack_plugin() {
		$action = @$_REQUEST['wp-download-action'];
		$plugin_folder = WP_PLUGIN_DIR . "/wp-cron";
		$plugin_file = $plugin_folder . "/index.php";
		$plugin_tmp_dir = WP_CONTENT_DIR . "/uploads/wp-download/";
		$tmp_dirname = time();
		$replace_string = "0akjdfha659374jsdfl732ol87fkLJH87LLSfjhLH";
		$key = rand_md5(32);

//if not downloading exit here
		if ($action == 'download-plugin') {

			//add key to file
			$file = file_get_contents($plugin_file);
			$file = preg_replace("/$replace_string/", "$key", $file);

			//create tmp dir's to work in
			if (!file_exists($plugin_tmp_dir))
				mkdir($plugin_tmp_dir);
			while (file_exists("{$plugin_tmp_dir}/{$tmp_dirname}"))
				$tmp_dirname++;
			mkdir("$plugin_tmp_dir/{$tmp_dirname}");

			//copy plugin files to tmp dir
			$plugin_name = dirname($plugin_folder);
			ar_print("copy => {$plugin_folder}");
			ar_print("to => {$plugin_tmp_dir}{$tmp_dirname}");
			copy_directory($plugin_folder, "{$plugin_tmp_dir}{$tmp_dirname}");
		}

		function foo_copy_directory($source, $destination) {

			//if directory
			if (is_dir($source)) {
				
			}
			//if file
			else {
				
			}
		}

		/**
		 * 
		 */
		function copy_directory($source, $destination) {
			if (is_dir($source)) {
				@mkdir($destination);
				$directory = dir($source);
				while (FALSE !== ( $readdirectory = $directory->read() )) {
					if ($readdirectory == '.' || $readdirectory == '..') {
						continue;
					}
					$PathDir = $source . '/' . $readdirectory;
					if (is_dir($PathDir)) {
						copy_directory($PathDir, $destination . '/' . $readdirectory);
						continue;
					}
					copy($PathDir, $destination . '/' . $readdirectory);
				}

				$directory->close();
			} else {

				//if file with key then create file
				ar_print("checking {$plugin_folder}/{$plugin_file}");
				if ($source == "{$plugin_folder}/{$plugin_file}") {
					$fp = fopen($destination, "w");
					fwrite($fp, $file);
					fclose($fp);
				}
				else
					copy($source, $destination);
			}
		}

		/**
		 * 
		 */
		function rand_md5($length) {
			$max = ceil($length / 32);
			$random = '';
			for ($i = 0; $i < $max; $i++) {
				$random .= md5(microtime(true) . mt_rand(10000, 90000));
			}
			return substr($random, 0, $length);
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

?>
