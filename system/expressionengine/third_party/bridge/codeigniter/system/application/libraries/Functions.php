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
 * ExpressionEngine Core Functions Class - Subclass of EE 1.x Functions class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		Solspace DevTeam
 * @filesource	/system/bridge/codeigniter/application/libraries/Functions.php
 */
class CI_Functions extends Functions {  
	
  
	/**
	 * Constructor
	 */	  
	function CI_Functions()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		if ( isset($GLOBALS['FNS']) && is_object($GLOBALS['FNS']))
		{
			foreach(get_object_vars($GLOBALS['FNS']) as $var => $value)
			{
				$this->$var =& $GLOBALS['FNS']->$var;
			}
		}
	}
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Fetch allowed channels
	 *
	 * This function fetches the ID numbers of the
	 * channels assigned to the currently logged in user.
	 *
	 * @access	public
	 * @param	bool
	 * @return	array
	 */
	function fetch_assigned_channels($all_sites = FALSE)
	{
		return $this->fetch_assigned_weblogs($all_sites);
	}
	/* END fetch_assigned_channels() */
  	

}
// END CLASS

/* End of file Functions.php */
/* Location: /system/bridge/codeigniter/application/libraries/Functions.php */