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
 * Router Class - Subclass
 *
 * Parses URIs and determines routing - Subclass
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @author		ExpressionEngine Dev Team
 * @category	Libraries
 * @link		http://codeigniter.com/user_guide/general/routing.html
 */
class MY_Router extends CI_Router {

	/**
	 * Constructor
	 *
	 * Runs the route mapping function.
	 */
	function MY_Router()
	{
		$this->config =& load_class('Config');
		$this->uri =& load_class('URI');
		
		// No Routing in EE 1.x
		//$this->_set_routing();
		
		// Still need all of the URI stuff though
		// Fetch the complete URI string
		$this->uri->_fetch_uri_string();
		
		// Do we need to remove the URL suffix?
		$this->uri->_remove_url_suffix();
		
		// Compile the segments into an array
		$this->uri->_explode_segments();
		
		// Re-index the segment array so that it starts with 1 rather than 0
		$this->uri->_reindex_segments();
	}

}
// END MY_Router Class

/* End of file MY_Router.php */
/* Location: ./system/application/libraries/MY_Router.php */