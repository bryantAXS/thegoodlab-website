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
 * ExpressionEngine Core Template Class - Subclass of EE 1.x Template class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		Solspace DevTeam
 * @filesource	/system/bridge/codeigniter/application/libraries/Template.php
 */
 
require_once PATH_CORE.'core.template'.EXT;
 
class CI_Template extends Template {
  
	/**
	 * Constructor
	 */	  
	function CI_Template()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		if ( isset($GLOBALS['TMPL']) && is_object($GLOBALS['TMPL']))
		{
			foreach(get_object_vars($GLOBALS['TMPL']) as $var => $value)
			{
				$this->$var =& $GLOBALS['TMPL']->$var;
			}
		}
	}
}
// END CLASS

/* End of file Template.php */
/* Location: /system/bridge/codeigniter/application/libraries/Template.php */