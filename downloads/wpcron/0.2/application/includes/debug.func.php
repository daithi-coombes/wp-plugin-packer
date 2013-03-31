<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Debug function. Prints an array wrapped inside &lt;pre> tags for easy viewing in 
 * html browser. Will print the array variable name as well, this is taken from 
 * debug dump using preg_match. 
 * 
 * @param array $ar the array to print 
 * @link http://www.david-coombes.com 
 * @copyright open 
 */
if(!function_exists("ar_print")){
	function ar_print($ar) {

	//vars  
	$name = "";
	$caller_info = array_shift(debug_backtrace());
	$lines = file($caller_info['file']);
	$line = $lines[$caller_info['line'] - 1];

	//search debug dump for var name  
	if (preg_match('/ar_print\\s*\\(\$(\\w+)/', $line, $matches))
		$name = $matches[1];

	//print to stdout  
	print "\n<pre>\n";
	print "{$name}\t";
	print_r($ar);
	print "\n</pre>\n";
}
}

/**
 * Debug function. Prints debug_print_backtrace() between two pre tags. 
 * 
 * @link http://www.david-coombes.com 
 * @copyright open 
 */
if(!function_exists("debug_print")){
	function debug_print() {

	print "<pre>\n";
	debug_print_backtrace();
	print "</pre>\n";
}
}
?>
