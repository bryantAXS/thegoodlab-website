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
 * ExpressionEngine Core Cp Class - Subclass of EE 1.x Display class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		Solspace DevTeam
 * @filesource	/system/bridge/codeigniter/application/libraries/Cp.php
 */
class CI_Cp extends Display {  
	
  
	/**
	 * Constructor
	 */	  
	function CI_Cp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		if ( isset($GLOBALS['DSP']) && is_object($GLOBALS['DSP']))
		{
			foreach(get_object_vars($GLOBALS['DSP']) as $var => $value)
			{
				$this->$var =& $GLOBALS['DSP']->$var;
			}
		}
	}
}
// END CLASS

/* End of file Cp.php */
/* Location: /system/bridge/codeigniter/application/libraries/Cp.php */