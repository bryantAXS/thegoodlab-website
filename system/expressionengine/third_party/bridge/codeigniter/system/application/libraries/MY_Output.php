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
 * CodeIgniter Output Class - Subclass
 *
 * This class contains functions that work with Outputting content to the browser
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Solspace DevTeam
 * @filesource	/system/bridge/codeigniter/system/application/libraries/MY_Output.php
 */
 
class MY_Output extends CI_Output {


	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */
	function MY_Output()
	{
		parent::CI_Output();
	}
  	

	
	// --------------------------------------------------------------------
	
	/**
	 * Display Output
	 *
	 * All "view" data is automatically put into this variable by the controller class:
	 *
	 * $this->final_output
	 *
	 * This function sends the finalized output data to the browser along
	 * with any server headers and profile data.  It also stops the
	 * benchmark timer so the page rendering speed and memory usage can be shown.
	 *
	 * @access	public
	 * @return	mixed
	 */		
	function _display($output = '')
	{	
		global $OUT;
		
		$OUT->display_final_output($output);
	}
	/* END _display() */
	
	// --------------------------------------------------------------------

	/**
	 * Display fatal error message
	 *
	 * @access	public
	 * @return	void
	 */
	function fatal_error($error_msg = '', $use_lang = TRUE)
	{
		global $OUT;
		
		$OUT->fatal_error($error_msg, $use_lang);
	}
	/* fatal_error() */
	
	// --------------------------------------------------------------------

	/**
	 * Show user error
	 *
	 * @access	public
	 * @param	string
	 * @param	mixed
	 * @param	string
	 * @return	void
	 */
	function show_user_error($type = 'submission', $errors, $heading = '')
	{
		$GLOBALS['OUT']->show_user_error($type, $errors, $heading);
	}
	/* END show_user_error() */
	
		// --------------------------------------------------------------------

	/**
	 * Show message
	 *
	 * This function and the next enable us to show error messages to
	 * users when needed. For example, when a form is submitted without
	 * the required info.
	 *
	 * This is not used in the control panel, only with publicly
	 * accessible pages.
	 *
	 * @access	public
	 * @param	mixed
	 * @param	bool
	 * @return	void
	 */
	function show_message($data, $xhtml = TRUE)
	{
		$GLOBALS['OUT']->show_message($data, $xhtml);
	}
	/* END show_message() */
	
}

/* END MY_Output class */

/* End of file MY_Output.php */
/* Location: ./system/bridge/codeigniter/system/application/libraries/MY_Output.php */