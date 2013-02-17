<?php

/**
 * Description of WPDownload
 *
 * @author daithi
 */
class WPDownload {

	private $logger;
	private $plugin_source;

	function __construct() {

		global $wppp_logger;
		$this->logger = $wppp_logger;
		$this->plugin_source = dirname(dirname(__FILE__)) . "/downloads";
	}

	/**
	 * Method checks requests and bootstraps.
	 * @return void die()'s when finished.
	 */
	public function stdin() {

		/**
		 * Bootstrap
		 */
		$dto = new WPDownload_DTO();
		$plugin = new WPDownload_Plugin($dto);

		if (
				!@$_REQUEST['key'] ||
				!$dto->check_key()
		)
			die(json_encode(array('error' => 'invalid key', 'data' => $_REQUEST)));
		$this->log("DTO:");
		$this->log($dto);
		//end bootstrap
		
		$zip = $this->pack_plugin($plugin);
		die();
	}

	/**
	 * Builds zip file of plugin.
	 * @param WPDownload_Plugin $plugin The plugin object
	 */
	private function pack_plugin(WPDownload_Plugin $plugin) {

		$this->log("pack_plugin for {$plugin->name}::");

		//vars
		$action = @$_REQUEST['wp-download-action'];
		if ($plugin->version)
			$plugin_folder = $this->plugin_source . "/" . $plugin->name . "/" . $plugin->version;
		else
			$plugin_folder = $this->plugin_source . "/" . $plugin->name;
		$plugin_file = $plugin_folder . "/index.php";
		$plugin_tmp_dir = WP_CONTENT_DIR . "/uploads/wp-download/";
		$tmp_dirname = time();
		$replace_string = "e3f8e543e968d7d0390a71bf3c4c2144";
		$key = $this->rand_md5(32);

		//if not downloading exit here
		if ($action == 'download-plugin') {

			//create tmp dir's to work in
			if (!file_exists($plugin_tmp_dir))
				mkdir($plugin_tmp_dir);
			while (file_exists("{$plugin_tmp_dir}/{$tmp_dirname}"))
				$tmp_dirname++;
			mkdir("$plugin_tmp_dir/{$tmp_dirname}");

			//copy plugin files to tmp dir
			$this->copy_directory($plugin_folder, "{$plugin_tmp_dir}{$tmp_dirname}");

			//add key to file
			$file = file_get_contents($plugin_file);
			$file = preg_replace("/$replace_string/", "$key", $file);
			file_put_contents("{$plugin_tmp_dir}{$tmp_dirname}/index.php", $file);
			
			//build zip
			$tmp_zip = tempnam("tmp", "zip");
			print "|start|";
			$this->Zip("{$plugin_tmp_dir}{$tmp_dirname}", $tmp_zip);
			/**
			header('Content-type: application/zip');
			$length = filesize($tmp_zip);
			header('Content-length: ' . $length);
			header("Content-Disposition: attachment; filename=\"{$plugin->name}.{$plugin->version}.zip\"");
			 * 
			 */
			print $tmp_zip;
			print file_get_contents($tmp_zip);
			readfile($tmp_zip);
			//unlink($tmp_zip);
			print "|end|";
		}

		return false;
	}

	/**
	 * Copies plugin from source to destination
	 * @param string $source The path to the source files
	 * @param string $destination The path to the destination files
	 */
	private function copy_directory($source, $destination) {

		if (is_dir($source)) {
			@mkdir($destination);
			$directory = dir($source);
			while (FALSE !== ( $readdirectory = $directory->read() )) {
				if ($readdirectory == '.' || $readdirectory == '..') {
					continue;
				}
				$PathDir = $source . '/' . $readdirectory;
				if (is_dir($PathDir)) {
					$this->copy_directory($PathDir, $destination . '/' . $readdirectory);
					continue;
				}
				copy($PathDir, $destination . '/' . $readdirectory);
			}

			$directory->close();
		} else {

			//if file with key then create file
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
	 * Recursively build zip file.
	 * @param string $source Path to source folder
	 * @param string $destination Path to destination temporary folder
	 * @return boolean
	 */
	function Zip($source, $destination)
	{
		ar_print("Starting zip file");
		/**
		if (!extension_loaded('zip') || !file_exists($source)) {
			return false;
		}
		ar_print("extension loaded");

		$zip = new ZipArchive();
		if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
			return false;
		}
		 * 
		 */

		$zip = new ZipStream("test.zip");
		@$zip->add_file("index.php", "this is the data for the first file");
		@$zip->finish();
		die();
		die();
		$source = str_replace('\\', '/', realpath($source));

		if (is_dir($source) === true)
		{
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

			foreach ($files as $file)
			{
				$file = str_replace('\\', '/', $file);

				// Ignore "." and ".." folders
				if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
					continue;

				$file = realpath($file);

				if (is_dir($file) === true)
				{
					ar_print("adding dir {$file}...");
					$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
				}
				else if (is_file($file) === true)
				{
					ar_print("adding file {$file}...");
					$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
				}
			}
		}
		else if (is_file($source) === true)
		{
			$zip->addFromString(basename($source), file_get_contents($source));
		}
		
		ar_print($zip);
		return $zip->close();
	}

	private function rand_md5($length) {
		$max = ceil($length / 32);
		$random = '';
		for ($i = 0; $i < $max; $i++) {
			$random .= md5(microtime(true) . mt_rand(10000, 90000));
		}
		$key = substr($random, 0, $length);
		$this->log("Random key: {$key}");
		return $key;
	}

	/**
	 * Write to the log file
	 * @global Logger $wpp_logger The log4php Logger class
	 * @param string $msg The message to log
	 * @param string $method Default info. The Logger::$method() to call.
	 * @return Logger Returns the logger 
	 */
	protected function log($msg, $method = 'info') {
		return;
		$this->logger->$method($msg);
		return $this->logger;
	}

}

class WPDownload_DTO {

	public $plugin = "";
	public $requests = array();

	function __construct() {

		//parse request global
		$this->requests = $_REQUEST;
		unset($this->requests['action']);
		foreach ($this->requests as $param => $val)
			$this->$param = $val;
	}

	/**
	 * Checks key in $_REQUEST global against database
	 * @param string $key Default is to use key set in construct.
	 * @return boolean 
	 */
	public function check_key($key = null) {

		if (!$key)
			$key = $this->requests['key'];

		return true;
	}

}

class WPDownload_Plugin {

	public $name;
	public $version;

	function __construct(WPDownload_DTO $dto) {

		$this->name = $dto->requests['plugin'];
		if ($dto->requests['version'])
			$this->version = $dto->requests['version'];
		else
			$this->version = false;
	}

}