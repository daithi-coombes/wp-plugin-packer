<?php

/**
 * Description of WPDownload
 *
 * @author daithi
 */
class WPDownload extends WPDownload_Interface{

	public $logger;
	private $plugin_source;
	private $plugin_tmp_dir;

	function __construct() {

		global $wppp_logger;
		$this->logger = $wppp_logger;
		$this->plugin_source = dirname(dirname(__FILE__)) . "/downloads";
		$this->plugin_tmp_dir = WP_CONTENT_DIR . "/uploads/wp-download";
		
		parent::__construct();
	}

	/**
	 * Method checks requests and bootstraps.
	 * @return void die()'s when finished.
	 */
	public function stdin() {

		/**
		 * Bootstrap
		 */
		$dto = new WPDownload_DTO(); //will check for ipn action
		$plugin = new WPDownload_Plugin(array(
					'plugin_root' => $this->plugin_source,
					'name' => $dto->plugin,
					'tmp_dir' => $this->plugin_tmp_dir,
					'version' => $dto->version
				));
		//end bootstrap

		/**
		 * preferred code flow:
		 * 
		 * 
		 * if('downloading new zip'):
		 * 	- confirm paypal
		 * 	- $plugin = new WPDownload_Plugin()
		 *  - $plugin->create_tmp()
		 *  - $plugin->set_key()	//in later versions this will check db for key settings and params
		 *  - $zip = new WPDownload_Zipfile( $plugin->tmp_plugin() );	//tmp_plugin() will return new WPDownload_Plugin with the tmp_dir contents as source
		 *  - $zip->stream()
		 *  - die()
		 * 
		 * if('updating plugin):
		 *  - plugin->check_key()
		 */
		/**
		 * Action
		 */
		switch ($dto->requests['wp-download-action']) {

			//download a plugin
			case 'download-plugin':

				//create temporary plugin
				$plugin->create_tmp();

				//set new key
				$plugin->set_key();

				//build zip and stream
				$zip = new WPDownload_Zipfile($plugin);
				$zip->stream();
				break;

			//paypal ipn request
			case 'paypal-ipn':

				$ipn = new WPDownload_IPN($dto);
				$this->log($ipn);
				break;

			//no action, throw error.
			default:
				$this->error("Invalid action", $_REQUEST);
				break;
		}
		//grab zip
		//$zip = $this->pack_plugin($plugin);
		die();
	}

	/**
	 * Throw an error.
	 * json encodes error array and die()'s or prints to screen depending on
	 * the flog $die
	 * @param string $msg The error message
	 * @param mixed $data Default array() Can be a string or array of data
	 * @param boolean $die Default true. Whether to die() or just print error
	 * @return void
	 */
	private function error($msg, $data = array(), $die = true) {

		$error = json_encode(array('error' => $msg, 'data' => $data));
		if ($die)
			die($error);
		else
			print $error;
	}
}

/**
 * WP Download Data Transport Object
 */
class WPDownload_DTO extends WPDownload_Interface{

	public $plugin = "";
	public $requests = array();

	function __construct() {

		//parse request global
		$this->requests = $_REQUEST;
		unset($this->requests['action']);
		foreach ($this->requests as $param => $val)
			$this->$param = $val;
		
		//check if ipn
		if(WPDownload_IPN::is_ipn($_POST))
			$this->requests['wp-download-action'] = 'paypal-ipn';
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

class WPDownload_IPN extends WPDownload_Interface{

	public $response;
	
	function __construct(WPDownload_DTO $dto) {

		$this->log("WPDownload_IPN Constructed");
		
		//check valid ip request
// read the post from PayPal system and add 'cmd'
		$this->response = 'cmd=_notify-validate';
		if (function_exists('get_magic_quotes_gpc')) {
			$get_magic_quotes_exists = true;
		}
		foreach ($_POST as $key => $value) {
			if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1)
				$value = urlencode(stripslashes($value));
			else
				$value = urlencode($value);
			$this->response .= "&$key=$value";
		}
	}
	
	/**
	 * Check an array of $_POST values for ipn request
	 * @param array $post
	 * @return boolean
	 */
	static public function is_ipn( array $post ){
		
		//check for ipn_id
		if($post['txn_id'])
			return true;
		else
			return false;
	}

}

/**
 * WP Download Plugin object
 */
class WPDownload_Plugin extends WPDownload_Interface{

	public $key_replace;
	public $name;
	public $path;
	public $plugin_root;
	public $tmp_dir;
	public $version;

	function __construct(array $params) {

		//Set Fields
		foreach ($params as $key => $val)
			$this->$key = $val;
		//end Set Fields
		//Plugin Path
		if ($this->version)
			$this->path = $this->plugin_root . "/" . $this->name . "/" . $this->version;
		else
			$this->path = $this->plugin_root . "/" . $this->name;
		//end Plugin Path
		//These params will be taken from db in final release. These are all
		//set by wp-admin in the wp-download-plugin dashboard page
		$this->key_file = "index.php"; //nb in finished version this will be taken from db
		$this->key_replace = 'e3f8e543e968d7d0390a71bf3c4c2144';
		//end db params
	}

	/**
	 * Makes copy of plugin in tmp directory.
	 * Will create a unique folder in $this->tmp_dir and append name of new
	 * directory to $this->tmp_dir accordingly.
	 * @return void
	 */
	public function create_tmp() {

		//vars
		$tmp_dirname = time();

		//create tmp dir's
		if (!file_exists($this->tmp_dir))
			mkdir($this->tmp_dir);
		while (file_exists("{$this->tmp_dir}/{$tmp_dirname}"))
			$tmp_dirname++;
		mkdir("$this->tmp_dir/{$tmp_dirname}");
		$this->tmp_dir .= "/{$tmp_dirname}";

		//copy plugin files to tmp dir
		$this->copy_directory($this->path, $this->tmp_dir);
	}

	/**
	 * Sets the plugin key
	 * @param mixed $key Default false. If not set then 32 char key is 
	 * generated
	 * @param tmp_dir|path $source Default tmp_dir. Whether to update key
	 * in plugin path or plugin in tmp_dir.
	 */
	public function set_key($key = false, $source = 'tmp_dir') {

		//get key
		if (!$key)
			$key = $this->rand_md5(32);

		//get key file
		$key_file = $this->$source . "/" . $this->key_file;

		//add key to file
		$file = file_get_contents($key_file);
		$file = preg_replace("/$this->key_replace/", $key, $file);
		file_put_contents($key_file, $file);
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
	 * Generate a random string
	 * @param integer $length The required string length
	 * @return string Returns the random string
	 */
	private function rand_md5($length) {
		$max = ceil($length / 32);
		$random = '';
		for ($i = 0; $i < $max; $i++) {
			$random .= md5(microtime(true) . mt_rand(10000, 90000));
		}
		$key = substr($random, 0, $length);
		return $key;
	}

}

/**
 * Zip class for packing plugins
 */
class WPDownload_Zipfile extends ZipStream {

	/** WPDownload_Plugin The plugin object to build zip file from */
	private $plugin;

	/**
	 * Set params and construct parent
	 * @param WPDownload_Plugin $plugin The plugin to build zip file from
	 */
	function __construct(WPDownload_Plugin $plugin) {
		$this->plugin = $plugin;

		error_reporting(0);
		parent::__construct($plugin->name . ".zip");
	}

	/**
	 * Streams a zip file to stdout
	 * Builds the zipfile, prints headers, stream to stdout and die()
	 * @param tmp_dir|path $source Where to stream the zip file from
	 * @return void die()'s
	 */
	function stream($source = 'tmp_dir') {

		error_reporting(0);
		$source = str_replace('\\', '/', realpath($this->plugin->$source));

		//if root is directory
		if (is_dir($source) === true) {

			//loop through files
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($files as $file) {

				$file = str_replace('\\', '/', $file);
				$filename = str_replace($source . '/', '', $file);

				// Ignore "." and ".." folders
				if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
					continue;

				//add file
				$file = realpath($file);
				if (is_file($file) === true)
					$this->add_file($filename, file_get_contents($file));
			}
		}

		//if single file
		else if (is_file($source) === true)
			$this->add_file(basename($source), file_get_contents($source));

		//finish stream and die()
		$this->finish();
		die();
	}

}

class WPDownload_Interface{
	
	protected $logger;
			
	function __construct(){
		
		global $wppp_logger;
		$this->logger = $wppp_logger;
	}
	
	/**
	 * Write to the log file
	 * @global Logger $wpp_logger The log4php Logger class
	 * @param string $msg The message to log
	 * @param string $method Default info. The Logger::$method() to call.
	 * @return Logger Returns the logger 
	 */
	protected function log($msg, $method = 'info') {
		if (@$this->logger)
			$this->logger->$method($msg);
		return $this->logger;
	}
}