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
 * ExpressionEngine Core Validate Class - Subclass of EE 1.x Validate class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		Solspace DevTeam
 * @filesource	/system/bridge/codeigniter/application/libraries/Validate.php
 */
 
require_once PATH_CORE.'core.validate.php';
 
class CI_Validate extends Validate {  
	
  
	/**
	 * Constructor
	 */	  
	function CI_Validate($data = '')
	{
		parent::Validate($data);
	}

}
// END CLASS

/* End of file Validate.php */
/* Location: /system/bridge/codeigniter/application/libraries/Validate.php */