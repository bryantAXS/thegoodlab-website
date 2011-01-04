<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2009, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Config Class - Subclass
 *
 * This class contains functions that enable config files to be managed
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Solspace Dev Team
 * @filesource	/system/bridge/codeigniter/system/application/libraries/MY_URI.php
 */
 
class MY_URI extends CI_URI {

	/**
	* Constructor
	*
	* gets proper linkage to URI items for 1.6.x
	*
	* @access	public
	*/
	function MY_URI()
	{
		parent::CI_URI();
	
		log_message('debug', "URI Class Initialized");
		
		//query_string isn't in CI_URI

		if ( isset($GLOBALS['IN']) )
		{
			$this->query_string		 =& $GLOBALS['IN']->QSTR;
			$this->page_query_string =& $GLOBALS['IN']->PAGE_QSTR;
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Filter segments for malicious characters
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function _filter_uri($str)
	{
		//the following commented out items doesn't help ee 1.6.x because it is security for disallowing query strings
		//in a CI site that has query strings turned off. This is not blocked in EE 1.6.8
		
		/*if ($str != '' && $this->config->item('permitted_uri_chars') != '' && 
			 $this->config->item('enable_query_strings') == FALSE)
		{
			// preg_quote() in PHP 5.3 escapes -, so the str_replace() and addition of - to preg_quote() is to maintain backwards
			// compatibility as many are unaware of how characters in the permitted_uri_chars will be parsed as a regex pattern
			if ( ! preg_match("|^[".str_replace(array('\\-', '\-'), '-', 
				   preg_quote($this->config->item('permitted_uri_chars'), '-'))."]+$|i", $str))
			{
				show_error('The URI you submitted has disallowed characters.', 400);
			}
		}*/

		// Convert programatic characters to entities
		$bad	= array('$', 		'(', 		')',	 	'%28', 		'%29');
		$good	= array('&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;');

		return str_replace($bad, $good, $str);
	}
}

/* END MY_URI class */

/* End of file MY_URI.php */
/* Location: ./system/bridge/codeigniter/system/application/libraries/MY_URI.php */
