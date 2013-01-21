<?php
/**
 * Description of WPCron
 *
 * @author daithi
 */
class WPCron {

	/**
	 * constructor
	 */
	public function __construct() {
		
	}

	/**
	 * Prints the view html.
	 * 
	 * Loads the html then sets shortcodes ( @see WPCron::set_shortcodes() )
	 * then loads scripts (@see WPCron::load_scripts() ) and styles
	 * (@see WPCron::load_styles() ) then prints html
	 * @return void
	 */
	public function get_page() {

		$this->html = file_get_contents(WPCron_dir . "/public_html/WPCron.php");
		$this->set_shortcodes();

		$this->load_scripts();
		$this->load_styles();

		print $this->html;
	}

	/**
	 * Loads javascript files
	 * 
	 * @return void 
	 */
	private function load_scripts() {
		;
	}

	/**
	 * Loads css files
	 * 
	 * @return void 
	 */
	private function load_styles() {
		;
	}

	/**
	 * Sets values for the shortcodes in the view file.
	 * 
	 * Replaces the codes with values in @see FSNetworkRegister::$html . To add
	 * shortcodes to the view file use the syntax:
	 * <code> <!--[--identifying string--]--> </code>. In the construct of this
	 * class add the value to the array @see FSNetworkRegister::$shortcodes.
	 * eg: $this->shortcodes['identifying string'] = $this->method_returns_html()
	 * 
	 * @return void
	 */
	private function set_shortcodes() {
		foreach ($this->shortcodes as $code => $val)
			$this->html = str_replace("<!--[--{$code}--]-->", $val, $this->html);
	}

}

?>
