<?php

/*
|---------------------------------------------------------------
| PHP ERROR REPORTING LEVEL
|---------------------------------------------------------------
|
| By default CI runs with error reporting set to ALL.  For security
| reasons you are encouraged to change this when your site goes live.
| For more info visit:  http://www.php.net/error_reporting
|
*/

//changed for bridge to respect the error reporting shown for users or superadmin

if (isset($GLOBALS['PREFS']) AND
		(
			//set to show admin only
			( $GLOBALS['PREFS']->ini('debug') == 1 	AND 
			  ( isset($GLOBALS['SESS']) AND 
				isset($GLOBALS['SESS']->userdata) AND
			    $GLOBALS['SESS']->userdata('group_id') == 1) 
			) OR
			//set to show all
			$GLOBALS['PREFS']->ini('debug') == 2
		)
	
	)
{
    error_reporting(E_ALL);
}
else
{
	error_reporting(0);
}
/*
|---------------------------------------------------------------
| SYSTEM FOLDER NAME
|---------------------------------------------------------------
|
| This variable must contain the name of your "system" folder.
| Include the path if the folder is not in the same  directory
| as this file.
|
| NO TRAILING SLASH!
|
*/
	$system_folder = PATH . "bridge/codeigniter/system";

/*
|---------------------------------------------------------------
| APPLICATION FOLDER NAME
|---------------------------------------------------------------
|
| If you want this front controller to use a different "application"
| folder then the default one you can set its name here. The folder 
| can also be renamed or relocated anywhere on your server.
| For more info please see the user guide:
| http://codeigniter.com/user_guide/general/managing_apps.html
|
|
| NO TRAILING SLASH!
|
*/
	$application_folder = "application";

/*
|===============================================================
| END OF USER CONFIGURABLE SETTINGS
|===============================================================
*/


/*
|---------------------------------------------------------------
| SET THE SERVER PATH
|---------------------------------------------------------------
|
| Let's attempt to determine the full-server path to the "system"
| folder in order to reduce the possibility of path problems.
| Note: We only attempt this if the user hasn't specified a 
| full server path.
|
*/
if (strpos($system_folder, '/') === FALSE)
{
	if (function_exists('realpath') AND @realpath(dirname(__FILE__)) !== FALSE)
	{
		$system_folder = realpath(dirname(__FILE__)).'/'.$system_folder;
	}
}
else
{
	// Swap directory separators to Unix style for consistency
	$system_folder = str_replace("\\", "/", $system_folder); 
}

/*
|---------------------------------------------------------------
| DEFINE APPLICATION CONSTANTS
|---------------------------------------------------------------
|
| EXT		- The file extension.  Typically ".php"
| SELF		- The name of THIS file (typically "index.php")
| FCPATH	- The full server path to THIS file
| BASEPATH	- The full server path to the "system" folder
| APPPATH	- The full server path to the "application" folder
|
*/

// BRIDGE: Already set by EE 
// define('EXT', '.php');
// define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
// define('FCPATH', str_replace(SELF, '', __FILE__));

define('BASEPATH', $system_folder.'/');

if (is_dir($application_folder))
{
	define('APPPATH', $application_folder.'/');
}
else
{
	if ($application_folder == '')
	{
		$application_folder = 'application';
	}

	define('APPPATH', BASEPATH.$application_folder.'/');
}

/*
|---------------------------------------------------------------
| LOAD THE FRONT CONTROLLER
|---------------------------------------------------------------
|
| And away we go...
|
*/

/* -------- Begin original CodeIgniter.php ---------- */


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
 * System Front Controller
 *
 * Loads the base classes and executes the request.
 *
 * @package		CodeIgniter
 * @subpackage	codeigniter
 * @category	Front-controller
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/
 */

// CI Version
define('CI_VERSION',	'1.7.2');

/*
 * ------------------------------------------------------
 *  Load the global functions
 * ------------------------------------------------------
 */
require(BASEPATH.'codeigniter/Common'.EXT);

/*
 * ------------------------------------------------------
 *  Load the compatibility override functions
 * ------------------------------------------------------
 */
require(BASEPATH.'codeigniter/Compat'.EXT);

/*
 * ------------------------------------------------------
 *  Load the framework constants
 * ------------------------------------------------------
 */
require(APPPATH.'config/constants'.EXT);

/*
 * ------------------------------------------------------
 *  Define a custom error handler so we can log PHP errors
 * ------------------------------------------------------
 */
//had to remove this for bridge.

//set_error_handler('_exception_handler');

if ( ! is_php('5.3'))
{
	@set_magic_quotes_runtime(0); // Kill magic quotes
}

/*
 * ------------------------------------------------------
 *  Start the timer... tick tock tick tock...
 * ------------------------------------------------------
 */

$BM_CI =& load_class('Benchmark');
$BM_CI->mark('total_execution_time_start');
$BM_CI->mark('loading_time_base_classes_start');

/*
 * ------------------------------------------------------
 *  BRIDGE: Removed - Instantiate the hooks class
 * ------------------------------------------------------
 */

//$EXT_CI =& load_class('Hooks');

/*
 * ------------------------------------------------------
 *  BRIDGE: Removed - Is there a "pre_system" hook?
 * ------------------------------------------------------
 */
// $EXT_CI->_call_hook('pre_system');

/*
 * ------------------------------------------------------
 *  BRIDGE - Disable - Instantiate the base classes
 * ------------------------------------------------------
 */

$CFG_CI =& load_class('Config');
$URI_CI =& load_class('URI');
// $RTR =& load_class('Router');
$OUT_CI =& load_class('Output');

/*
 * ------------------------------------------------------
 *  BRIDGE: Manually Assign to Config
 * ------------------------------------------------------
 */
 
$assign_to_config 							= $GLOBALS['PREFS']->core_ini;
$assign_to_config['enable_query_strings'] 	= ( ! (REQ == 'PAGE') ); 
$assign_to_config['base_url'] 				= $GLOBALS['PREFS']->core_ini['site_url'];

if (isset($assign_to_config))
{	
	$CFG_CI->_assign_to_config($assign_to_config);
}

/*
 * ------------------------------------------------------
 *	BRIDGE: Remove - Is there a valid cache file?  If so, we're done...
 * ------------------------------------------------------
 */
/*
if ($EXT_CI->_call_hook('cache_override') === FALSE)
{
	if ($OUT->_display_cache($CFG, $URI) == TRUE)
	{
		exit;
	}
}
*/

/*
 * ------------------------------------------------------
 *  Load the remaining base classes
 * ------------------------------------------------------
 */

$IN_CI		=& load_class('Input');
$LANG_CI	=& load_class('Lang');

/*
 * ------------------------------------------------------
 *  Load the app controller and local controller
 * ------------------------------------------------------
 *
 *  Note: Due to the poor object handling in PHP 4 we'll
 *  conditionally load different versions of the base
 *  class.  Retaining PHP 4 compatibility requires a bit of a hack.
 *
 *  Note: The Loader class needs to be included first
 *
 */
if ( ! is_php('5.0.0'))
{
	load_class('Loader', FALSE);
	require(BASEPATH.'codeigniter/Base4'.EXT);
}
else
{
	require(BASEPATH.'codeigniter/Base5'.EXT);
}

// Load the base controller class
$CI = load_class('Controller');

/** --------------------------------------------
/**  Load Libraries - Pre-Database
/** --------------------------------------------*/

$CI->load->library('security');
$CI->load->library('functions');
$CI->load->library('blacklist');

if (REQ == 'CP')
{
	// $CI->load->library('cp');
}

/** --------------------------------------------
/**  A Missing Typography Helper Method from Current Version of CI
/**  - Since this file is only called in EE 1.x, this should work.
/**  - @todo - One day CI will have this and we should replace it?
/** --------------------------------------------*/

if ( ! function_exists('entity_decode'))
{
	function entity_decode($str, $charset='UTF-8')
	{
		return $GLOBALS['REGX']->_html_entity_decode($str, $charset);
	}
}

/** --------------------------------------------
/**  Load the Database Class
/**   -	We do this because in EE 1.x we do not want to explicitly set the connection character
/**		or collation as we do not know without a query and cannot assume it is latin-1 or utf-8
/**		And, since CI does not allow us to set an empty collation, we have to do some trickey.
/** --------------------------------------------*/

$CI->load->database('default');
$CI->db->database = $GLOBALS['db_config']['database'];
$CI->db->db_select();

/** --------------------------------------------
/**  Load Libraries - Post-Database
/** --------------------------------------------*/

$CI->load->library('extensions');

/** --------------------------------------------
/**  BRIDGE: STOP! STOP! STOP!
/** --------------------------------------------*/



return;


// Load the local application controller
// Note: The Router class automatically validates the controller path.  If this include fails it 
// means that the default controller in the Routes.php file is not resolving to something valid.
if ( ! file_exists(APPPATH.'controllers/'.$RTR->fetch_directory().$RTR->fetch_class().EXT))
{
	show_error('Unable to load your default controller.  Please make sure the controller specified in your Routes.php file is valid.');
}

include(APPPATH.'controllers/'.$RTR->fetch_directory().$RTR->fetch_class().EXT);

// Set a mark point for benchmarking
$BM_CI->mark('loading_time_base_classes_end');


/*
 * ------------------------------------------------------
 *  Security check
 * ------------------------------------------------------
 *
 *  None of the functions in the app controller or the
 *  loader class can be called via the URI, nor can
 *  controller functions that begin with an underscore
 */
$class  = $RTR->fetch_class();
$method = $RTR->fetch_method();

if ( ! class_exists($class)
	OR $method == 'controller'
	OR strncmp($method, '_', 1) == 0
	OR in_array(strtolower($method), array_map('strtolower', get_class_methods('Controller')))
	)
{
	show_404("{$class}/{$method}");
}

/*
 * ------------------------------------------------------
 *  BRIDGE: Remove - Is there a "pre_controller" hook?
 * ------------------------------------------------------
 */
// $EXT_CI->_call_hook('pre_controller');

/*
 * ------------------------------------------------------
 *  Instantiate the controller and call requested method
 * ------------------------------------------------------
 */

// Mark a start point so we can benchmark the controller
$BM_CI->mark('controller_execution_time_( '.$class.' / '.$method.' )_start');

$CI = new $class();

// HEREMES: Disable - Is this a scaffolding request?
if (FALSE && $RTR->scaffolding_request === TRUE)
{
	if ($EXT_CI->_call_hook('scaffolding_override') === FALSE)
	{
		$CI->_ci_scaffolding();
	}
}
else
{
	/*
	 * ------------------------------------------------------
	 *  BRIDGE: Remove - Is there a "post_controller_constructor" hook?
	 * ------------------------------------------------------
	 */
	//$EXT_CI->_call_hook('post_controller_constructor');
	
	// Is there a "remap" function?
	if (method_exists($CI, '_remap'))
	{
		$CI->_remap($method);
	}
	else
	{
		// is_callable() returns TRUE on some versions of PHP 5 for private and protected
		// methods, so we'll use this workaround for consistent behavior
		if ( ! in_array(strtolower($method), array_map('strtolower', get_class_methods($CI))))
		{
			show_404("{$class}/{$method}");
		}

		// Call the requested method.
		// Any URI segments present (besides the class/function) will be passed to the method for convenience
		call_user_func_array(array(&$CI, $method), array_slice($URI->rsegments, 2));
	}
}

// Mark a benchmark end point
$BM_CI->mark('controller_execution_time_( '.$class.' / '.$method.' )_end');

/*
 * ------------------------------------------------------
 *  BRIDGE: Remove - Is there a "post_controller" hook?
 * ------------------------------------------------------
 */
// $EXT_CI->_call_hook('post_controller');

/*
 * ------------------------------------------------------
 *  BRIDGE: Remove - Send the final rendered output to the browser
 * ------------------------------------------------------
 */

/*
if ($EXT_CI->_call_hook('display_override') === FALSE)
{
	$OUT->_display();
}
*/

/*
 * ------------------------------------------------------
 *  BRIDGE: Remove - Is there a "post_system" hook?
 * ------------------------------------------------------
 */
//$EXT_CI->_call_hook('post_system');

/*
 * ------------------------------------------------------
 *  Close the DB connection if one exists
 * ------------------------------------------------------
 */
if (class_exists('CI_DB') AND isset($CI->db))
{
	$CI->db->close();
}

?>