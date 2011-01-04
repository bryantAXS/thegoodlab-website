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
 * @author		Solspace DevTeam
 * @filesource	/system/bridge/codeigniter/system/application/libraries/MY_Config.php
 */
 
class MY_Config extends CI_Config {


	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */
	function MY_Config()
	{
		parent::CI_Config();
	}
  	
	// --------------------------------------------------------------------

	/**
	 * Assign to Config
	 *
	 * This function is called by the front controller (CodeIgniter.php)
	 * after the Config class is instantiated.  It permits config items
	 * to be assigned or overriden by variables contained in the index.php file
	 *
	 * @access	private
	 * @param	array
	 * @return	void
	 */	
	function _assign_to_config($items = array())
	{
		if (is_array($items))
		{
			foreach ($items as $key => $val)
			{
				$this->set_item($key, $val);
			}
		}	
	}


}

/* END MY_Config class */

/* End of file MY_Config.php */
/* Location: ./system/bridge/codeigniter/system/application/libraries/MY_Config.php */