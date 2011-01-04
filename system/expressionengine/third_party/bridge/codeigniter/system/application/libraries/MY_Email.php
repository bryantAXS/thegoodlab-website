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
 * CodeIgniter Email Class - Subclass
 *
 * This class contains functions that enable config files to be managed
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Solspace DevTeam
 * @filesource	/system/bridge/codeigniter/system/application/libraries/MY_Email.php
 */
 
class MY_Email extends CI_Email {


	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */
	function MY_Email($init = TRUE)
	{
		parent::CI_Email();

		if ($init != TRUE) return;
			
		$this->first_init();	
	}
 
	// --------------------------------------------------------------------

	/**
	 * Set config values
	 *
	 * @access	private
	 * @return	void
	 */ 	
	function first_init()
	{
		
		$config = array();
		//just to help us with checking for yes
		$solspace_yes_array = array('y', 'true', 'yes', 'on');

		//use EE 1.6.x defaults
		$config['useragent'] 		= APP_NAME . ' ' . APP_VER;
		$config['protocol'] 		= "mail";			
		$config['mailpath'] 		= "/usr/sbin/sendmail";
		$config['smtp_host'] 		= "";				
		$config['smtp_user'] 		= "";				
		$config['smtp_pass'] 		= "";							
		$config['smtp_port'] 		= "25";				
		$config['smtp_timeout'] 	= 5;				
		$config['wordwrap'] 		= FALSE;			
		$config['wrapchars'] 		= "76";				
		$config['mailtype'] 		= "text";			
		$config['charset'] 			= "utf-8";			
		$config['validate'] 		= FALSE;	
		$config['priority'] 		= "3";
		$config['crlf'] 			= "\n";	
		$config['newline'] 			= "\n";	
		$config['bcc_batch_mode'] 	= FALSE;
		$config['bcc_batch_size'] 	= 250;

		//if we get get some settings from prefs, lets attempt to use them
		if (isset($GLOBALS['PREFS']))
		{
			//get ye flask... er settings	

			$config['protocol'] 		= ( ! in_array( $GLOBALS['PREFS']->ini('mail_protocol'), 
												array('mail', 'sendmail', 'smtp'))) ? 
											'mail' : $GLOBALS['PREFS']->ini('mail_protocol');	
			$config['smtp_host'] 	   	= $GLOBALS['PREFS']->ini('smtp_server');
			$config['smtp_user'] 	   	= $GLOBALS['PREFS']->ini('smtp_username');
			$config['smtp_pass'] 	   	= $GLOBALS['PREFS']->ini('smtp_password');	
			$config['wordwrap'] 		= in_array(strtolower($GLOBALS['PREFS']->ini('word_wrap')), 
											$solspace_yes_array );
			$config['mailtype'] 		= in_array(strtolower($GLOBALS['PREFS']->ini('mail_format')),
											array('text', 'html')) ? 
												$GLOBALS['PREFS']->ini('mail_format') : 
												'text';
			$config['charset'] 			= ($GLOBALS['PREFS']->ini('email_charset') == '') ? 
											'utf-8' : $GLOBALS['PREFS']->ini('email_charset');		
			$config['crlf'] 			= ($GLOBALS['PREFS']->ini('email_crlf') !== FALSE) ? 
											$GLOBALS['PREFS']->ini('email_crlf') : $config['crlf'];	
			$config['newline'] 			= ($GLOBALS['PREFS']->ini('email_newline') !== FALSE) ? 
											$GLOBALS['PREFS']->ini('email_newline') : $config['newline'];
			$config['bcc_batch_mode']  	= in_array(strtolower($GLOBALS['PREFS']->ini('email_batchmode')), 
											$solspace_yes_array );
			$config['bcc_batch_size']	= is_numeric($GLOBALS['PREFS']->ini('email_batch_size')) ? 									
											$GLOBALS['PREFS']->ini('email_batch_size') : 
											$config['bcc_batch_size']; 
		
		}
		
		//send to email
		$this->initialize($config);
	}
}

/* END MY_Email class */

/* End of file MY_Email.php */
/* Location: ./system/bridge/codeigniter/system/application/libraries/MY_Email.php */