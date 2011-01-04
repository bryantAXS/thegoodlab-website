<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2009, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Blacklist Class - Subclass of EE 1.x Blacklist class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		Solspace DevTeam
 * @filesource	/system/bridge/codeigniter/application/libraries/Blacklist.php
 */
class CI_Blacklist {  
	
	var $whitelisted = 'n';		// Is this request whitelisted
	var $blacklisted = 'n';		// Is this request blacklisted.
  
	/**
	 * Constructor
	 */	  
	function CI_Blacklist()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		if ( isset($GLOBALS['IN']) && is_object($GLOBALS['IN']))
		{
			$this->blacklisted = $GLOBALS['IN']->blacklisted;
			$this->whitelisted = $GLOBALS['IN']->whitelisted;
		}
	}

}
// END CLASS

/* End of file Blacklist.php */
/* Location: /system/bridge/codeigniter/application/libraries/Blacklist.php */