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
 * @filesource	/system/bridge/codeigniter/system/application/libraries/MY_Input.php
 */
 
class MY_Input extends CI_Input {

	/**
	* Constructor
	*
	* Sets whether to globally enable the XSS processing and whether to allow the $_GET array
	*
	* @access	public
	*/
	function MY_Input()
	{
		log_message('debug', "Input Class Initialized");

		$CFG =& load_class('Config');
		$this->use_xss_clean	= ($CFG->item('global_xss_filtering') === TRUE) ? TRUE : FALSE;
		$this->allow_get_array	= ($CFG->item('enable_query_strings') === TRUE) ? TRUE : FALSE;
		// $this->_sanitize_globals();  EE 1.x already does this, and it causes havoc if we do not disable
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Return a COOKIE Value
	 *
	 * Overrides CI's cookie() method as EE has a cookie prefix to prevent collisions.
	 *
	 * @access	public
	 * @param	string
	 * @return	string|bool
	 */
	function cookie($index = '')
	{
		$EE =& get_instance();
		
		$prefix = ( ! ee()->config->item('cookie_prefix')) ? 'exp_' : trim(ee()->config->item('cookie_prefix'), '_').'_';
		
		return ( ! isset($_COOKIE[$prefix.$index]) ) ? FALSE : stripslashes($_COOKIE[$prefix.$index]);
	}
}

/* END MY_Input class */

/* End of file MY_Input.php */
/* Location: ./system/bridge/codeigniter/system/application/libraries/MY_Input.php */