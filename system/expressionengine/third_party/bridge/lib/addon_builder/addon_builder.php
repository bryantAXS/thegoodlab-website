<?php if ( ! defined('EXT')) exit('No direct script access allowed');
 
 /**
 * Bridge - Expansion
 *
 * @package		Bridge:Expansion
 * @author		Solspace DevTeam
 * @copyright	Copyright (c) 2008-2010, Solspace, Inc.
 * @link		http://solspace.com/docs/
 * @version		1.1.4
 * @filesource 	./system/bridge/
 * 
 */
 
 /**
 * Add-On Builder - Base Class
 *
 * A class that helps with the building of ExpressionEngine Add-Ons by allowing the automating of certain
 * tasks.  Also, connects with the documentDOM class to use View files like CI to build CP pages.
 *
 * @package 	Bridge:Expansion
 * @subpackage	Solspace:Add-On Builder
 * @category	None
 * @author		Solspace DevTeam
 * @link		http://solspace.com/docs/
 * @filesource 	./system/bridge/lib/addon_builder/addon_builder.php
 */
 
if (APP_VER < 2.0)
{
	require_once PATH.'bridge/lib/addon_builder/codeigniter.php'; 
	require_once PATH.'bridge/constants.php';
}
else
{
	require_once PATH_THIRD . 'bridge/constants.php';
}

class Addon_builder_bridge {
	
	var $bridge_version 	= '1.1.4';
	
	var $cache				= array(); // Internal cache
	
	var $ob_level			= 0;
	var $cached_vars		= array();
	var $switches			= array();
	
	// The general class name (ucfirst with underscores), used in database and class instantiation
	var $class_name			= '';
	
	// The lowercased class name, used for referencing module files and in URLs			
	var $lower_name			= '';
	
	// The name that we put into the Extensions DB table, different for 2.x and 1.x
	var $extension_name		= '';
	
	// Module disabled? Typically used when an update is in progress.
	var $disabled			= FALSE; 
	
	var $addon_path			= '';
	var $theme				= 'default';
	var $version			= '';
	
	var $crumbs				= array();
	
	var $document			= FALSE;
	var $data				= FALSE;
	var $actions			= FALSE;
	
	var $module_preferences	= array();
	var $remote_data		= '';	// For remote file retrieving and storage
	
	var $sc;
	
	//this will house items that might not always be set when called.
	var $constants;
	
	//holder for the json object if ever
	var $json;
    
    // --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */
    
	function Addon_builder_bridge($name='')
	{
		global $PREFS;
		
		/** --------------------------------------------
        /**  Super Object - It's a Bird!  It's a Plane!  It's a PHP Class!
        /** --------------------------------------------*/
        
        $this->EE =& get_instance();
        
        if ( APP_VER < 2.0)
        {
        	$this->EE->localize = $GLOBALS['LOC'];
        	$this->EE->stats	= ( ! isset($GLOBALS['STAT'])) ? FALSE : $GLOBALS['STAT'];
			
			//need a symbolic link to extension->last_call and end_script
			if ( isset($GLOBALS['EXT']) && is_object($GLOBALS['EXT']))
	        {
	        	$this->EE->extensions->last_call 	=& $GLOBALS['EXT']->last_call;
				$this->EE->extensions->end_script 	=& $GLOBALS['EXT']->end_script;
			}			
        }
        
		// Super short cuts!!!
		$this->sc = $this->generate_shortcuts();

        /** --------------------------------------------
        /**  Session Global
        /**		- Add-On Builder might be called for an Extension using the 'session_' hooks,
        /**		- so we need to check for that object first.
        /** --------------------------------------------*/
        
        if ( ! isset($this->EE->session) OR ! is_object($this->EE->session))
        {
        	if ( APP_VER < 2.0)
        	{
				//Have to check for ->userdata too because a REAL session instance has it always
				//Some other addon devs are creating $SESS->cache even when $SESS is null
				//That autocreates the session object before the real one clobbers it,
				//that in turn fools addons into thinking that $SESSION has already fired :/
				
				//assume its still not there
				$this->EE->session = FALSE;
				
				//if it is, lets grab it changed to pass by reference
        		if ( isset($GLOBALS['SESS']) AND isset($GLOBALS['SESS']->userdata)) 
				{
					$this->EE->session =& $GLOBALS['SESS'];
				}
        	}
        	elseif (file_exists(APPPATH.'libraries/Session.php'))
        	{
        		$this->EE->load->library('session');
        	}
        }
        
        /** --------------------------------------------
        /**  PAGE Request?  Check for $TMPL global
        /** --------------------------------------------*/
        
        if (isset($GLOBALS['TMPL']) && is_object($GLOBALS['TMPL']))
        {
        	$this->EE->TMPL =& $GLOBALS['TMPL'];
        }
        
        /** --------------------------------------------
        /**  CP Request?  Check for $DSP global
        /** --------------------------------------------*/
        
        if (APP_VER < 2.0 AND REQ == 'CP' && isset($GLOBALS['DSP']) && is_object($GLOBALS['DSP']))
        {
        	$this->EE->cp =& $GLOBALS['DSP'];
        }
		
		/** --------------------------------------------
		/**  Required CONSTANTs
		/** --------------------------------------------*/
		
		if ( ! defined('QUERY_MARKER'))
		{
			define('QUERY_MARKER', ($this->EE->config->item('force_query_string') == 'y') ? '' : '?');
		}
		
		if ( ! defined('SLASH'))
		{
			define('SLASH', '&#47;');  // Seems this constant is the same for both EE 1.x and EE 2.x
		}
		
		if ( ! defined('T_SLASH')) // Template Parsing Slash
		{
			define('T_SLASH', (APP_VER < '2.0') ? '&#47;' : "/");
		}
		
		if ( ! defined('NL'))
		{
			define('NL', "\n");
		}
		
		if ( ! defined('PATH_THIRD') AND defined('PATH_MOD'))
		{
			define('PATH_THIRD', PATH_MOD);	
		}
		
		if ( ! defined('PATH_CP_IMG') AND defined('PATH_CP_GBL_IMG'))
		{
			define('PATH_CP_IMG', PATH_CP_GBL_IMG);	
		}
		
		//just in case we need them early
		if ( ! defined('AMP')) 
		{
			define('AMP', '&amp;');
		}
		
        if ( ! defined('BR'))  
		{
			define('BR',  '<br />');
		}
                
		if ( ! defined('NBS')) 
		{	
			define('NBS', "&nbsp;");
		}
		
		// EE 1.x does not have this constant, 
		// but it adds it to every form automatically.
		// EE 2.x sets it all the time now.
		
		$constants = array(
			'XID_SECURE_HASH' 	=> (APP_VER < 2.0 OR ! defined('XID_SECURE_HASH')) ? 
											'' : XID_SECURE_HASH,											
		);
		
		$this->constants = (object) $constants;
		
		/** --------------------------------------------
        /**  Auto-Detect Name
        /** --------------------------------------------*/
        
        if ($name == '')
        {
        	$name = get_class($this);
        	
        	$ends = array(
				'_cp_base', 
				'_mcp', 
				'_CP', 
				'_ext', 
				'_extension', 
				'_extension_base', 
				'_updater_base', 
				'_updater', 
				'_upd', 
				'_actions'
			);
        	
        	foreach($ends as $remove)
        	{
        		if (substr($name, -strlen($remove)) == $remove)
        		{
        			$name = substr($name, 0, -strlen($remove));
        			break;
        		}
        	}
        }
		
		/** --------------------------------------------
        /**  Important Class Vars
        /** --------------------------------------------*/
        
        $this->EE->load->library('security');
        
		$this->lower_name		= strtolower($this->EE->security->sanitize_filename($name));
    	$this->class_name		= ucfirst($this->lower_name);
    	
    	$this->extension_name	= $this->class_name.((APP_VER < 2.0) ? '_extension' : '_ext'); 
		
		/** --------------------------------------------
        /**  Prepare Caching
        /** --------------------------------------------*/
    	
    	if ( ! isset($this->EE->session) OR ! is_object($this->EE->session))
    	{
    		if ( ! isset($GLOBALS['bridge']['cache']['addon_builder']['addon'][$this->lower_name]))
    		{
    			$GLOBALS['bridge']['cache']['addon_builder']['addon'][$this->lower_name] = array();
    		}
    		
    		$this->cache =& $GLOBALS['bridge']['cache']['addon_builder']['addon'][$this->lower_name];
    	}
    	else
    	{
    		if ( ! isset($this->EE->session->cache['bridge']['addon_builder']['addon'][$this->lower_name]))
 			{
 				if( isset($GLOBALS['bridge']['cache']['addon_builder']['addon'][$this->lower_name]))
				{
					$this->EE->session->cache['bridge']['addon_builder']['addon'][$this->lower_name] = $GLOBALS['bridge']['cache']['addon_builder']['addon'][$this->lower_name];
				}
 				else
 				{
 					$this->EE->session->cache['bridge']['addon_builder']['addon'][$this->lower_name] = array();
 				}
 			}
 		
 			$this->cache =& $this->EE->session->cache['bridge']['addon_builder']['addon'][$this->lower_name];
 		}
		
		/** --------------------------------------------
        /**  Add-On Path
        /** --------------------------------------------*/
        
        if (APP_VER < 2.0)
        {
        	// Because of Bridge Magic with eval() and parents, we might have to go one or two levels up
        	$parent_class		= get_parent_class($this);
        	$super_parent_class = get_parent_class($parent_class);
        
			if (($parent_class == 'Extension_builder_bridge' OR $super_parent_class == 'Extension_builder_bridge') && is_dir(PATH_EXT.$this->lower_name.'/'))
			{
				$this->extension_name	= $this->class_name;
				$this->addon_path		= PATH_EXT.$this->lower_name.'/';
			}
			else
			{
				$this->addon_path = PATH_MOD . $this->lower_name . '/';
			}
		}
		else
		{
			$this->addon_path = PATH_THIRD . $this->lower_name . '/';
		}
		
		/** --------------------------------------------
		/**  Language Override
		/** --------------------------------------------*/
		
		if ( is_object($this->EE->lang))
		{
			$this->EE->lang->loadfile($this->lower_name);
		}
		
		/** --------------------------------------------
        /**  Module Constants
        /** --------------------------------------------*/
        
        if ( defined(strtoupper($this->lower_name).'_VERSION') == FALSE && file_exists($this->addon_path.'constants.'.$this->lower_name.'.php'))
        {
        	require_once $this->addon_path.'constants.'.$this->lower_name.'.php';
        }
        
        if (defined(strtoupper($this->lower_name).'_VERSION') !== FALSE)
        {
        	$this->version = constant(strtoupper($this->lower_name).'_VERSION');
        }
		        
        /** --------------------------------------------
        /**  Data Object - Used Cached Version, if Available
        /** --------------------------------------------*/
        
        if ( isset($this->cache['objects']['data']) && is_object($this->cache['objects']['data']))
        {
        	$this->data =& $this->cache['objects']['data'];
        }
        else
        {			
			if ( file_exists($this->addon_path.'data.'.$this->lower_name.'.php'))
			{
				require_once $this->addon_path.'data.'.$this->lower_name.'.php';
				
				$name = $this->class_name.'_data';
				
				$this->data = new $name();
				
				$this->data->sc	= $this->sc;
			}
			else
			{
				require_once PATH_BRIDGE.'lib/addon_builder/data.addon_builder.php';
				
				$this->data = new Addon_builder_data_bridge();
			}
		}
        
        /** --------------------------------------------
		/**  documentDOM_bridge instantiated, might move this.
		/** --------------------------------------------*/
    	
    	if (REQ == 'CP')
    	{
    		require_once PATH_BRIDGE.'lib/documentDOM/documentDOM.php';
    	
    		$this->document = new documentDOM_bridge();
        }
        
        /** --------------------------------------------
        /**  Important Cached Vars - Used in Both Extensions and Modules
        /** --------------------------------------------*/
		
		$this->cached_vars['XID_SECURE_HASH'] 	= $this->constants->XID_SECURE_HASH;
		$this->cached_vars['page_crumb']	 	= '';
		$this->cached_vars['page_title']	 	= '';
		$this->cached_vars['text_direction'] 	= 'ltr';
		$this->cached_vars['onload_events']  	= '';
		$this->cached_vars['message']		 	= '';
		
		$this->cached_vars['caller'] 		 	=& $this;
		
		/** --------------------------------------------
		/**  Determine View Path for Add-On
		/** --------------------------------------------*/
		
		if ( isset($this->cache['view_path']))
		{
			$this->view_path = $this->cache['view_path'];
		}
		else
		{
			$possible_paths = array();
			
			$this->theme = $this->EE->security->sanitize_filename($this->theme);
			
			if (APP_VER < 2.0)
			{
				if (trim($this->theme, '/') != '')
				{
					$possible_paths[] = $this->addon_path.'views/1.x/'.trim($this->theme, '/').'/';
				}
				
				$possible_paths[] = $this->addon_path.'views/1.x/default/';
				$possible_paths[] = $this->addon_path.'views/1.x/';
			}
			else
			{
				if (trim($this->theme, '/') != '')
				{
					$possible_paths[] = $this->addon_path.'views/2.x/'.trim($this->theme, '/').'/';
				}
				
				$possible_paths[] = $this->addon_path.'views/2.x/default/';
				$possible_paths[] = $this->addon_path.'views/2.x/';
			}
			
			if (trim($this->theme, '/') != '')
			{
				$possible_paths[] = $this->addon_path.'views/'.trim($this->theme, '/').'/';
			}
			
			$possible_paths[] = $this->addon_path.'views/default/';
			$possible_paths[] = $this->addon_path.'views/';
			
			foreach(array_unique($possible_paths) as $path)
			{
				if ( is_dir($path))
				{
					$this->view_path = $path;
					break;
				}
			}
		}
		
		/** --------------------------------------------
        /**  Module Installed and Up to Date?
        /** --------------------------------------------*/
        
        if (REQ == 'PAGE' && constant(strtoupper($this->lower_name).'_VERSION') !== NULL && ($this->database_version() == FALSE OR $this->version_compare($this->database_version(), '<', constant(strtoupper($this->lower_name).'_VERSION'))))
		{
			$this->disabled = TRUE;
			
			if (empty($this->cache['disabled_message']) && !empty($this->EE->lang->language[$this->lower_name.'_module_disabled']))
			{
				trigger_error($this->EE->lang->line($this->lower_name.'_module_disabled'), E_USER_NOTICE);
				
				$this->cache['disabled_message'] = TRUE;
			}
		}
	}
	/* END Addon_builder_bridge() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Creates shortcuts for common changed items between versions.
	 *
	 * @access	public
	 * @return	object
	 */
	function generate_shortcuts()
	{
		$is2 = !(APP_VER < 2.0);
		
		return (object) array(
			'db'	=> (object) array(
				'channel_name'			=> $is2 ? 'channel_name'              	: 'blog_name',
				'channel_url'			=> $is2 ? 'channel_url'              	: 'blog_url',
				'channel_title'			=> $is2 ? 'channel_title'             	: 'blog_title',
				'channels'				=> $is2 ? 'exp_channels'              	: 'exp_weblogs',
				'data'					=> $is2 ? 'exp_channel_data'          	: 'exp_weblog_data',
				'channel_data'			=> $is2 ? 'exp_channel_data'          	: 'exp_weblog_data',
				'fields'				=> $is2 ? 'exp_channel_fields'        	: 'exp_weblog_fields',
				'channel_fields'		=> $is2 ? 'exp_channel_fields'        	: 'exp_weblog_fields',
				'id'					=> $is2 ? 'channel_id'                	: 'weblog_id',
				'channel_id'			=> $is2 ? 'channel_id'                	: 'weblog_id',
				'member_groups'			=> $is2 ? 'exp_channel_member_groups' 	: 'exp_weblog_member_groups',
				'channel_member_groups'	=> $is2 ? 'exp_channel_member_groups' 	: 'exp_weblog_member_groups',
				'titles'				=> $is2 ? 'exp_channel_titles'        	: 'exp_weblog_titles',
				'channel_titles'		=> $is2 ? 'exp_channel_titles'        	: 'exp_weblog_titles'
			),
			'channel'					=> $is2 ? 'channel'        				: 'weblog',
			'channels'					=> $is2 ? 'channels'        			: 'weblogs'
		);
	}
	/* END generate_shortcuts() */

	
	// --------------------------------------------------------------------

	/**
	 * Instantiates an Object and Returns It
	 *
	 * Tired of having the same code duplicate everywhere for calling Typography, Email, Et Cetera.
	 *
	 * @access	public
	 * @return	object|NULL
	 */
    
	function instantiate( $name , $variables = array())
    {
    	$lower_name = strtolower($name);
    	$class_name = ucfirst($lower_name);
    	
    	// I am embarrassed by this exception -Paul
    	if ($lower_name == 'email')
    	{
    		$class_name == 'EEmail';
    	}
    	
    	if ( ! class_exists($class_name))
		{
			// We only load classes from the CP or CORE directories
			
			if (file_exists(PATH_CP.'core.'.$lower_name.EXT))
			{
				$location = PATH_CP.'core.'.$lower_name.EXT;
			}
			elseif (file_exists(PATH_CORE.'cp.'.$lower_name.EXT))
			{
				$location = PATH_CORE.'cp.'.$lower_name.EXT;
			}
			elseif (file_exists(PATH_BRIDGE_THIRD.$name.EXT))
			{
				$class_name = $name;
				$location = PATH_BRIDGE_THIRD.$name.EXT;
			}
			else
			{
				// Perhaps return an error? Nah...
				return NULL;
			}
			
			require_once $location;
		}
		
		$NEW = new $class_name();
		
		foreach($variables AS $key => $value)
		{
			$NEW->$key = $value;
		}
		
		return $NEW;
    }
    /* END instantiate() */
    
    
	// --------------------------------------------------------------------

	/**
	 * Module's Action Object
	 *
	 * We do not cache this object because there is a fair chance that we will want a fresh object
	 * whenever it is called.  Could add an init() method of some sort, but I doubt anyone will 
	 * intelligently use it so why add confusion and pain? Eeesh, where is your optimism, Burdick?!
	 *
	 * @access	public
	 * @return	object
	 */
	 
	function actions()
	{
		if ( ! is_object($this->actions))
		{
			require_once $this->addon_path.'act.'.$this->lower_name.'.php';
			
			$name = $this->class_name.'_actions';
			
			$this->actions = new $name();
			$this->actions->data =& $this->data;
		}
		
		return $this->actions;
	}
	/* END actions() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Database Version
	 *
	 * Returns the version of the module in the database
	 *
	 * @access	public
	 * @return	string
	 */
    
	function database_version( )
    {
    	global $DB;
    	
    	if (isset($this->cache['database_version'])) return $this->cache['database_version'];
    	
    	/**	----------------------------------------
		/**	 Use Template object variable, if available
		/** ----------------------------------------*/
    	
    	if ( isset($GLOBALS['TMPL']) && is_object($GLOBALS['TMPL']) && sizeof($GLOBALS['TMPL']->module_data) > 0)
    	{
    		if ( ! isset($GLOBALS['TMPL']->module_data[$this->class_name]))
    		{
				$this->cache['database_version'] = FALSE;
			}
			else
			{
				$this->cache['database_version'] = $GLOBALS['TMPL']->module_data[$this->class_name]['version'];
			}
			
			return $this->cache['database_version'];
    	}
    	
    	/**	----------------------------------------
		/**	 Retrieve all Module Versions from the Database
		/**	  - By retrieving all of them at once, we can limit it to a max of one query per page load for all Bridge Add-Ons
		/** ----------------------------------------*/
    	
    	$query = $this->cacheless_query( "SELECT module_version, module_name FROM exp_modules
    									WHERE module_name = '".$this->EE->db->escape_str($this->class_name)."'");
    	
    	foreach($query->result_array() as $row)
    	{
    		if ( isset($this->EE->session) && is_object($this->EE->session))
			{
				$this->EE->session->cache['bridge']['addon_builder']['addon'][strtolower($row['module_name'])]['database_version'] = $row['module_version'];
			}
			elseif ($this->class_name == $row['module_name'])
			{
				$this->cache['database_version'] = $row['module_version'];
			}
    	}
			
		if ( ! isset($this->cache['database_version']))
		{
			return FALSE;
		}
		else
		{
			return $this->cache['database_version'];
		}
	}
	/* END database_version() */
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Find and return preference
	 *
	 * Any number of possible arguments, although typically I expect there will be only one or two
	 * 
	 * @access	public
	 * @param	string			Preference to retrieve
	 * @return	null|string		If preference does not exist, NULL is returned, else the value
	 */
	 
	function preference()
	{
		$s = func_num_args();
		
		if ($s == 0)
		{
			return NULL;
		}
		
		/** --------------------------------------------
        /**  Fetch Module Preferences
        /** --------------------------------------------*/
		
		if (sizeof($this->module_preferences) == 0 && $this->database_version() !== FALSE)
		{
			if ( ! method_exists($this->data, 'get_module_preferences'))
			{
				return NULL;
			}
		
			$this->module_preferences = $this->data->get_module_preferences();
		}
		
		/** --------------------------------------------
        /**  Find Our Value, If It Exists
        /** --------------------------------------------*/
        
        $value = (isset($this->module_preferences[func_get_arg(0)])) ? $this->module_preferences[func_get_arg(0)] : NULL;
		
		for($i = 1; $i < $s; ++$i)
		{
			if ( ! isset($value[func_get_arg($i)]))
			{
				return NULL;
			}
			
			$value = $value[func_get_arg($i)];
		}
		
		return $value;
	}
	/* END preference() */


	// --------------------------------------------------------------------
		
	/**
	 * Checks to see if extensions are enabled for this module
	 *
	 * 
	 * @access	public
	 * @param	bool	match exact number of hooks
	 * @return	bool	Whether the extensions are enabled if need be
	 */
	 
	function extensions_enabled( $check_all_enabled = FALSE )
	{
		$class 			= $this->class_name . '_updater_base';
		
		$update_file 	= $this->addon_path . '/upd.' . $this->lower_name . '.base.php';
		
		if ( ! class_exists($class))
		{
			if (is_file($update_file))
			{
				require_once $update_file;
			}
			else
			{
				//techincally, this is false, but we dont want to halt something else because the
				//file cannot be found that we need here. Needs to be a better solution
				return TRUE;
			}			 
		}
		
		$upd	= new $class();
		
		//is it there? is it array? is it empty? Such are life's unanswerable questions, until now.
		if ( ! isset($upd->hooks) OR ! is_array($upd->hooks) OR empty($upd->hooks) )
		{
			return TRUE; 
		}
		
		$query = ee()->db->query(
			"SELECT enabled 
			 FROM 	exp_extensions 
			 WHERE 	class = '" . ee()->db->escape_str($this->extension_name) . "'
			 AND	enabled = 'y'"
		);
		
		//we arent going to look for all of the hooks because some could be turned off manually for testing
		return (($check_all_enabled) ? ($query->num_rows() == count($upd->hooks) ) : ($query->num_rows() > 0) );		
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Homegrown Version of Version Compare
	 *
	 * Compared two versions in the form of 1.1.1.d12 <= 1.2.3.f0
	 * 
	 * @access	public
	 * @param	string	First Version
	 * @param	string	Operator for Comparison
	 * @param	string	Second Version
	 * @return	bool	Whether the comparison is TRUE or FALSE
	 */
	 
	function version_compare($v1, $operator, $v2)
	{
		// Allowed operators
		if ( ! in_array($operator, array('>', '<', '>=', '<=', '==', '!=')))
		{
			trigger_error("Invalid Operator in Add-On Library - Version Compare", E_USER_WARNING);
			return FALSE;
		}
	
		// Normalize and Fix Invalid Values
		foreach(array('v1', 'v2') as $var)
		{
			$x = array_slice(preg_split("/\./", trim($$var), -1, PREG_SPLIT_NO_EMPTY), 0, 4);
			
			for($i=0; $i < 4; $i++)
			{
				if ( ! isset($x[$i]))
				{
					$x[$i] = ($i == 3) ? 'f0' : '0';
				}
				elseif ($i < 3 && ctype_digit($x[$i]) == FALSE)
				{
					$x[$i] = '0';
				}
				elseif($i == 3 && ! preg_match("/^[abdf]{1}[0-9]+$/", $x[$i]))
				{
					$x[$i] = 'f0';
				}
				
				// Set up for PHP's version_compare
				if ($i == 3)
				{
					$letter 	 = substr($x[3], 0, 1);
					$sans_letter = substr($x[3], 1);
					
					if ($letter == 'd')
					{
						$letter = 'dev';
					}
					elseif($letter == 'f')
					{
						$letter = 'RC';
					}
					
					$x[3] = $letter.'.'.$sans_letter;
				}
			}
			
			$$var = implode('.', $x);
		}
		
		// echo $v1.' - '.$v2;
		
		return version_compare($v1, $v2, $operator);
	}
	/* END version_compare() */

	
	// --------------------------------------------------------------------
		
	/**
	 * ExpressionEngine CP View Request
	 *
	 * Just like a typical view request but we do a few EE CP related things too
	 * 
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function ee_cp_view($view)
	{
		/** --------------------------------------------
        /**  Build Crumbs!
        /** --------------------------------------------*/
        
        $this->build_crumbs();
        
        /** --------------------------------------------
        /**  EE 1.x Code for Calling Certain CP Hooks
        /** --------------------------------------------*/
        
		if (APP_VER < 2.0)
		{	
			// -------------------------------------------
			// 'show_full_control_panel_start' hook.
			//  - Full Control over CP
			//  - Modify any $DSP class variable (JS, headers, etc.)
			//  - Override any $DSP method and use their own
			//
				$edata = $this->EE->extensions->call('show_full_control_panel_start');
				if ($this->EE->extensions->end_script === TRUE) return;
			//
			// -------------------------------------------
		}
		
		/** --------------------------------------------
        /**  Load View Path, Call View File
        /** --------------------------------------------*/
        
        if (APP_VER < 2.0)
        {
        	// I tried to switch to the CI method in Bridge 2.0, but unfortunately it seems
        	// to break Add-Ons that used previous versions of Bridge. Disappointing.  Should 
        	// look into that a bit further when I have a bit of time.
        	
        	$output = $this->view($view, array(), TRUE);
        }
        else
        {
			$orig_view_path = $this->EE->load->_ci_view_path;
			$this->EE->load->_ci_view_path = $this->view_path;
			
			$output = $this->EE->load->view($view, $this->cached_vars, TRUE);
		
			$this->EE->load->_ci_view_path = $orig_view_path;
		}
		
		/** --------------------------------------------
        /**  EE 1.x Code for Calling Certain CP Hooks
        /** --------------------------------------------*/
		
		if (APP_VER < 2.0)
		{	
			// -------------------------------------------
			// 'show_full_control_panel_end' hook.
			//  - Rewrite CP's HTML
			//	- Find/Replace Stuff, etc.
			//
				if ($this->EE->extensions->active_hook('show_full_control_panel_end') === TRUE)
				{
					$output = $this->EE->extensions->call('show_full_control_panel_end', $output);
					if ($this->EE->extensions->end_script === TRUE) return;
				}
			//
			// -------------------------------------------
		}
		
		/** --------------------------------------------
        /**  EE 1.x, We Add Secure Form Hashes and Output Content to Browser
        /** --------------------------------------------*/
		
		if (APP_VER < 2.0)
		{
			if (stristr($output, '{XID_HASH}'))
			{
				$output = $this->EE->functions->add_form_security_hash($output);
			}

			$this->EE->output->_display($this->EE->cp->secure_hash($output));
			exit;
		}
		
		/** --------------------------------------------
        /**  In EE 2.x, we return the Output and Let EE Continue Building the CP
        /** --------------------------------------------*/
		
		return $output;
	}
	/* END ee_cp_view() */
	
	
		// --------------------------------------------------------------------
		
	/**
	 * Javascript/CSS File View Request
	 *
	 * Outputs a View file as if it were a Javascript file
	 * 
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function file_view($view, $modification_time = '')
	{
		/** --------------------------------------------
        /**  Auto-detect the Type
        /** --------------------------------------------*/
       
		if (preg_match("/\.([cjs]{2,3})$/i", $view, $match) && in_array($match[1], array('css', 'js')))
		{
			switch($match[1])
			{
				case 'css'	:
					$type = 'css';
				break;
				case 'js'	:
					$type = 'javascript';
				break;
			}
		}
		else
		{
			exit;
		}
	
		/** --------------------------------------------
        /**  Load View Path, Call View File
        /** --------------------------------------------*/
        
        if (APP_VER < 2.0)
        {
        	// I tried to switch to the CI method in Bridge 1.0, but unfortunately it seems
        	// to break Add-Ons that used previous versions of Hermes. Disappointing.  Should 
        	// look into that a bit further when I have a bit of time. -Paul
        	
        	$output = $this->view($view, array(), TRUE);
        }
        else
        {
			$orig_view_path = $this->EE->load->_ci_view_path;
			$this->EE->load->_ci_view_path = $this->view_path;
			
			$output = $this->EE->load->view($view, $this->cached_vars, TRUE);
		
			$this->EE->load->_ci_view_path = $orig_view_path;
		}
		
		/** --------------------------------------------
        /**  EE 1.x, We Add Secure Form Hashes and Output Content to Browser
        /** --------------------------------------------*/
        
        if ($type == 'javascript' && stristr($output, '{XID_SECURE_HASH}'))
        {
        	$output = str_replace('{XID_SECURE_HASH}', '{XID_HASH}', $output);
        }
        
        if ($type == 'javascript')
		{
			$output = $this->EE->functions->add_form_security_hash($output);
		}
		
		/**	----------------------------------------
        /**	Generate HTTP headers
        /**	----------------------------------------*/

        if (ee()->config->item('send_headers') == 'y')
        {
			$ext = pathinfo($view, PATHINFO_EXTENSION);
			$file = ($ext == '') ? $view.EXT : $view;
			$path = $this->view_path.$file;        
        
            $max_age			= 5184000;
			$modification_time	= ($modification_time != '') ? $modification_time : filemtime($path);
			$modified_since		= ee()->input->server('HTTP_IF_MODIFIED_SINCE');
			
			if ( ! ctype_digit($modification_time))
			{
				$modification_time	= filemtime($path);
			}

			// Remove anything after the semicolon

			if ($pos = strrpos($modified_since, ';') !== FALSE)
			{
				$modified_since = substr($modified_since, 0, $pos);
			}

			// Send a custom ETag to maintain a useful cache in
			// load-balanced environments

			header("ETag: ".md5($modification_time.$path));

			// If the file is in the client cache, we'll
			// send a 304 and be done with it.

			if ($modified_since && (strtotime($modified_since) == $modification_time))
			{
				ee()->output->set_status_header(304);
				exit;
			}

			ee()->output->set_status_header(200);
			@header("Cache-Control: max-age={$max_age}, must-revalidate");
			@header('Vary: Accept-Encoding');
			@header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modification_time).' GMT');
			@header('Expires: '.gmdate('D, d M Y H:i:s', time() + $max_age).' GMT');
			@header('Content-Length: '.strlen($output));
		}  

        /**	----------------------------------------
        /**	 Send JavaScript/CSS Header and Output
        /**	----------------------------------------*/

        @header("Content-type: text/".$type);
		
		exit($output);
	}
	/* END ee_cp_view() */
	 
	// --------------------------------------------------------------------

	/**
	 *	View File Loader
	 *
	 *	Takes a file from the filesystem and loads it so that we can parse PHP within it just
	 *
	 *
	 *	@access		public
	 *	@param		string		$view - The view file to be located
	 *	@param		array		$vars - Array of data variables to be parsed in the file system
	 *	@param		bool		$return - Return file as string or put into buffer
	 *	@param		string		$path - Override path for the file rather than using $this->view_path
	 *	@return		string
	 */
	 
	function view($view, $vars = array(), $return = FALSE, $path='')
	{
		global $LANG, $FNS, $PREFS, $DSP, $LOC, $SESS;
		
		/** --------------------------------------------
        /**  Determine File Name and Extension for Requested File
        /** --------------------------------------------*/
        
		if ($path == '')
		{
			$ext = pathinfo($view, PATHINFO_EXTENSION);
			$file = ($ext == '') ? $view.EXT : $view;
			$path = $this->view_path.$file;
		}
		else
		{
			$x = explode('/', $path);
			$file = end($x);
		}
		
		/** --------------------------------------------
        /**  Make Sure the File Actually Exists
        /** --------------------------------------------*/
		
		if ( ! file_exists($path))
		{
			trigger_error("Invalid View File Request of '".$path."'");
			return FALSE;
		}
		 
		// All variables sent to the function are cached, which allows us to use them
		// within embedded view files within this file.
		 
		if (is_array($vars))
		{
			$this->cached_vars = array_merge($this->cached_vars, $vars);
		}
		extract($this->cached_vars);
		
		//print_r($this->cached_vars);
		 
		/** --------------------------------------------
        /**  Buffer Output
        /**  - Increases Speed
        /**  - Allows Views to be Nested Within Views
        /** --------------------------------------------*/
        
		ob_start();

		/** --------------------------------------------
        /**  Load File and Rewrite Short Tags
        /** --------------------------------------------*/
		
		$rewrite_short_tags = TRUE; // Hard coded setting for now...
		
		if ((bool) @ini_get('short_open_tag') === FALSE AND $rewrite_short_tags == TRUE)
		{
			echo eval('?'.'>'.preg_replace("/;*\s*\?".">/", "; ?".">", str_replace('<'.'?=', '<?php echo ', file_get_contents($path))).'<'.'?php ');
		}
		else
		{
			include($path);
		}
		
		/** --------------------------------------------
        /**  Return Parsed File as String
        /** --------------------------------------------*/
		
		if ($return === TRUE)
		{		
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}
		 
		/** --------------------------------------------
        /**  Flush Buffer
        /** --------------------------------------------*/
        
		if (ob_get_level() > $this->ob_level + 1)
		{
			ob_end_flush();
		}
		else
		{
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}
	}
	/* END view() */
	
	// --------------------------------------------------------------------

	/**
	 * Fetch the CP Stylesheet
	 *
	 * Had to build this because it was not abstracted well enough for us to simply call EE methods
	 *
	 * @access	public
	 * @param	array		An array of find/replace values to perform in the stylesheet
	 * @return	string
	 */
	 
	function fetch_stylesheet()
	{
	 	global $DSP, $PREFS;
	 	
	 	// Change CSS on the click so it works like the hover until they unclick?  
        
		$tab_behaviors = array(
								'publish_tab_selector'		=> ($this->EE->config->item('publish_tab_behavior') == 'hover') ? 'hover' : 'active',
								'publish_tab_display'		=> ($this->EE->config->item('publish_tab_behavior') == 'none') ? '' : 'display:block; visibility: visible;',
								'publish_tab_ul_display'	=> ($this->EE->config->item('publish_tab_behavior') == 'none') ? '' : 'display:none;',
								'sites_tab_selector'		=> ($this->EE->config->item('sites_tab_behavior') == 'hover') ? 'hover' : 'active',
								'sites_tab_display'			=> ($this->EE->config->item('sites_tab_behavior') == 'none') ? '' : 'display:block; visibility: visible;',
								'sites_tab_ul_display'		=> ($this->EE->config->item('sites_tab_behavior') == 'none') ? '' : 'display:none;'
							);
		
		$stylesheet = $DSP->fetch_stylesheet();
	
		foreach ($tab_behaviors as $key => $val)
		{
			$stylesheet = str_replace(LD.$key.RD, $val, $stylesheet);
		}
	 	
	 	return $stylesheet;
	}
	 /* END fetch_stylesheet() */
	 
	 
	// --------------------------------------------------------------------
	
	/**
	 * Add Array of Breadcrumbs for a Page
	 *
	 * @access	public
	 * @param	array
	 * @return	null
	 */
	 
	function add_crumbs($array)
	{
		if ( is_array($array))
		{
			foreach($array as $value)
			{
				if ( is_array($value))
				{
					$this->add_crumb($value[0], $value[1]);
				}
				else
				{
					$this->add_crumb($value);
				}
			}
		}
	}
	/* END add_crumbs */
	
	// --------------------------------------------------------------------
	
	/**
	 * Add Single Crumb to List of Breadcrumbs
	 *
	 * @access	public
	 * @param	string		Text of breacrumb
	 * @param	string		Link, if any for breadcrumb
	 * @return	null
	 */
	
	function add_crumb($text, $link='')
	{
		$this->crumbs[] = ($link == '') ? array($text) : array($text, $link);
	}
	/* END add_crumb() */	
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Takes Our Crumbs and Builds them into the Breadcrumb List
	 *
	 * @access	public
	 * @return	null
	 */
	
	function build_crumbs()
	{
		global $DSP, $OUT;
		
		if ( is_string($this->crumbs))
		{
			$DSP->title	= $this->crumbs;
			$this->cached_vars['page_crumb'] = $this->crumbs;
			$this->cached_vars['page_title'] = $this->crumbs;
			return;
		}
		
		$DSP->crumb = '';
		$this->cached_vars['page_crumb'] = '';
		$this->cached_vars['page_title'] = '';
		
		$item = (count($this->crumbs) == 1) ? TRUE : FALSE;
		
		$this->EE->load->helper('url');
		
		foreach($this->crumbs as $key => $value)
		{
			if (is_array($value))
			{
				$name = $value[0];
				
				if (isset($value[1]))
				{
					$name = "<a href='{$value[1]}'>{$value[0]}</a>";	
				}
				
				$this->cached_vars['page_title'] = $value[0];
			}
			else
			{
				$name = $value;
				$this->cached_vars['page_title'] = $value;
			}
			
			if (APP_VER < 2.0)
			{			
				if ($item === FALSE)
				{
					$this->cached_vars['page_crumb'] .= $name;
					$item = TRUE;
				}
				else
				{
					$this->cached_vars['page_crumb'] .= $DSP->crumb_item($name); 
				}
			}
			else
			{
				if (is_array($value) && isset($value[1]))
				{
					$this->EE->cp->set_breadcrumb($value[1], $value[0]);
				}
			}
		}
		
		/** --------------------------------------------
        /**  2.0 Specific Code
        /** --------------------------------------------*/
        
		$this->cached_vars['cp_page_title'] = $this->cached_vars['page_title'];
		
		if (APP_VER >= 2.0)
		{
			$this->EE->cp->set_variable('cp_page_title', $this->cached_vars['cp_page_title'] );
		}
		
		/** --------------------------------------------
        /**  1.x Breadcrumb View Variable
        /** --------------------------------------------*/
		
		$DSP->crumb = $this->cached_vars['page_crumb'];
	}
	/* END build_crumbs() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Field Output Prep for arrays and strings
	 *		
	 *
	 * @access	public
	 * @param	string|array	The item that needs to be prepped for output
	 * @return	string|array
	 */
    
    function output($item)
    {
    	if (is_array($item))
    	{
    		$array = array();
    		
    		foreach($item as $key => $value)
    		{
    			$array[$this->output($key)] = $this->output($value);
    		}
    		
    		return $array;
    	}
    	elseif(is_string($item))
    	{
			return htmlspecialchars($item, ENT_QUOTES);
		}
		else
		{
			return $item;
		}
    }
    /* END output() */
    
	// --------------------------------------------------------------------

	/**
	 * Cycles Between Values
	 *
	 * Takes a list of arguments and cycles through them on each call
	 *
	 * @access	public
	 * @param	string|array	The items that need to be cycled through
	 * @return	string|array
	 */
    
    function cycle($items)
    {	
    	if ( ! is_array($items))
    	{
    		$items = func_get_args();
    	}
    	
    	$hash = md5(implode('|', $items));
    	
    	if ( ! isset($this->switches[$hash]) OR ! isset($items[$this->switches[$hash] + 1]))
    	{
    		$this->switches[$hash] = 0;
    	}
    	else
    	{
    		$this->switches[$hash]++;
    	}
    	
    	return $items[$this->switches[$hash]];
    }
    /* END cycle() */
    
    
	// --------------------------------------------------------------------

	/**
	 * Order Array
	 *
	 * Takes an array and reorders it based on the value of a key
	 *
	 * @access	public
	 * @param	array	$array		The array needing to be reordered
	 * @param	string	$key		The key being used to reorder
	 * @param	string	$order		The order for the values asc/desc
	 * @return	array
	 */
    
    function order_array($array, $key, $order = 'desc')
    {	
    	// http://us2.php.net/manual/en/function.array-multisort.php
    }
    /* END order_array() */
    
	// --------------------------------------------------------------------

	/**
	 * Column Exists in DB Table
	 *
	 * @access	public
	 * @param	string	$column		The column whose existence we are looking for
	 * @param	string	$table		In which table?
	 * @return	array
	 */
    
	function column_exists( $column, $table )
	{		
		if ( isset($this->cache['column_exists'][$table][$column]))
		{
			return $this->cache['column_exists'][$table][$column];
		}
		
		/**	----------------------------------------
		/**	Check for columns in tags table
		/** ----------------------------------------*/
		
		$query	= $this->EE->db->query( "DESCRIBE `".$this->EE->db->escape_str( $table )."` `".$this->EE->db->escape_str( $column )."`" );
		
		if ( $query->num_rows > 0 )
		{
			return $this->cache['column_exists'][$table][$column] = TRUE;
		}
		
		return $this->cache['column_exists'][$table][$column] = FALSE;
	}
	/* END column_exists() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Retrieve Remote File and Cache It
	 *
	 * @access	public
	 * @param	string		$url - URL to be retrieved
	 * @param	integer		$cache_length - How long to cache the result, if successful retrieval
	 * @return	bool		Success or failure.  Data result stored in $this->remote_data
	 */
	
	function retrieve_remote_file($url, $cache_length = 24, $path='', $file='')
	{
		global $FNS;
	
		$path		= ($path == '') ? PATH_CACHE.'addon_builder/' : rtrim($path, '/').'/';
		$file		= ($file == '') ? md5($url).'.txt' : $file;
		$file_path	= $path.$file;
	
		/** --------------------------------------------
        /**  Check for Cached File
        /** --------------------------------------------*/
	
		if ( ! file_exists($file_path) OR (time() - filemtime($file_path)) > (60 * 60 * round($cache_length))) 
		{
			@unlink($file_path);
		}
		elseif (($this->remote_data = file_get_contents($file_path)) === FALSE)
		{
			@unlink($file_path);
		}
		else
		{
			return TRUE;
		}
	
		/** --------------------------------------------
        /**  Validate and Create Cache Directory
        /** --------------------------------------------*/
	
		if ( ! is_dir($path))
		{
			$dirs = explode('/', trim($this->EE->functions->remove_double_slashes($path), '/'));
			
			$path = '/';
			
			foreach ($dirs as $dir)
			{       
				if ( ! @is_dir($path.$dir))
				{
					if ( ! @mkdir($path.$dir, 0777))
					{
						$this->errors[] = 'Unable to Create Directory: '.$path.$dir;
						return;
					}
					
					@chmod($path.$dir, 0777);            
				}
				
				$path .= $dir.'/';
			}
		}
		
		if ($this->is_really_writable($path) === FALSE)
		{
			$this->errors[] = 'Cache Directory is Not Writable: '.$path;
			return FALSE;
		}
		
		/** --------------------------------------------
        /**  Retrieve Our URL
        /** --------------------------------------------*/
        
        $this->remote_data = $this->fetch_url($url);
        
        if ($this->remote_data == '')
        {
        	$this->errors[] = 'Unable to Retrieve URL: '.$url;
        	return FALSE;
        }
        
        /** --------------------------------------------
        /**  Write Cache File
        /** --------------------------------------------*/
        
        if ( ! $this->write_file($file_path, $this->remote_data))
		{
			$this->errors[] = 'Unable to Write File to Cache';
			return FALSE;
		}
		
		return TRUE;
	}
	/* END retrieve_remote_file() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Fetch the Data for a URL
	 
	 * @access	public
	 * @param	string		$url - The URI that we are fetching
	 * @param	array		$post - The POST array we are sending
	 * @return	string
	 */
    
	function fetch_url($url, $post = array())
    {
    	$data = '';
    	
    	/** --------------------------------------------
        /**  file_get_contents()
        /** --------------------------------------------*/
    	
    	if ((bool) @ini_get('allow_url_fopen') !== FALSE AND empty($post))
		{
			if ($data = @file_get_contents($url))
			{
				return trim($data);
			}
		}
		
		/** --------------------------------------------
        /**  cURL
        /** --------------------------------------------*/

		if (function_exists('curl_init') === TRUE && ($ch = @curl_init()) !== FALSE)
		{
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			
			// prevent a PHP warning on certain servers
			if (! ini_get('safe_mode') && ! ini_get('open_basedir'))
			{
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			}
			
			//	Are we posting?
			if ( ! empty( $post ) )
			{
				$str	= '';
				
				foreach ( $post as $key => $val )
				{
					$str	.= urlencode( $key ) . "=" . urlencode( $val ) . "&";
				}
				
				$str	= substr( $str, 0, -1 );
			
				curl_setopt( $ch, CURLOPT_POST, TRUE );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $str );
			}
			
			curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			$data = curl_exec($ch);
			curl_close($ch);

			if ($data !== FALSE)
			{
				return trim($data);
			}
		}
		
		/** --------------------------------------------
        /**  fsockopen() - Last but only slightly least...
        /** --------------------------------------------*/
		
		$parts	= parse_url($url);
		$host	= $parts['host'];
		$path	= (!isset($parts['path'])) ? '/' : $parts['path'];
		$port	= ($parts['scheme'] == "https") ? '443' : '80';
		$ssl	= ($parts['scheme'] == "https") ? 'ssl://' : '';
		
		if (isset($parts['query']) && $parts['query'] != '')
		{
			$path .= '?'.$parts['query'];
		}
		
		$data = '';

		$fp = @fsockopen($ssl.$host, $port, $error_num, $error_str, 7); 

		if (is_resource($fp))
		{
			$getpost	= ( ! empty( $post ) ) ? 'POST ': 'GET ';
		
			fputs ($fp, $getpost.$path." HTTP/1.0\r\n" ); 
			fputs ($fp, "Host: ".$host . "\r\n" );
			
			if ( ! empty( $post ) )
			{
				$str	= '';
				
				foreach ( $post as $key => $val )
				{
					$str	.= urlencode( $key ) . "=" . urlencode( $val ) . "&";
				}
				
				$str	= substr( $str, 0, -1 );

				fputs ($fp, "Content-type: application/x-www-form-urlencoded\r\n");
				fputs ($fp, "Content-length: " . strlen( $str ) . "\r\n");
			}
			
			fputs ($fp, "User-Agent: Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1)\r\n");
			fputs ($fp, "Connection: close\r\n\r\n");
			
			if ( ! empty( $post ) )
			{
				fputs ($fp, $str . "\r\n\r\n");
			}
			
			/* ------------------------------
			/*  This error suppression has to do with a PHP bug involving
			/*  SSL connections: http://bugs.php.net/bug.php?id=23220
			/* ------------------------------*/
			
			$old_level = error_reporting(0);
			
			while ( ! feof($fp))
			{
				$data .= trim(fgets($fp, 128));
			}
			
			error_reporting($old_level);

			fclose($fp); 
		}
		
		return trim($data); 
	}
	/* END fetch_url() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Write File
	 *
	 * @access	public
	 * @param	$file	Full location of final file
	 * @param	$data	Data to put into file
	 * @return	bool
	 */
    
    function write_file($file, $data)
    {	
    	$temp_file = $file.'.tmp';
        
        if ( ! file_exists($temp_file))
        {
        	// Remove old cache file, prevents rename problem on Windows
        	// http://bugs.php.net/bug.php?id=44805
        	
			@unlink($file);
        	
			if (file_exists($file))
			{
				$this->errors[] = "Unable to Delete Old Cache File: ".$file;
				return FALSE;
			}
        
			if ( ! $fp = @fopen($temp_file, 'wb'))
			{
				$this->errors[] = "Unable to Write Temporary Cache File: ".$temp_file;
				return FALSE;
			}
			
			if ( ! flock($fp, LOCK_EX | LOCK_NB))
			{
				$this->errors[] = "Locking Error when Writing Cache File";
				return FALSE;
			}
			
			fwrite($fp, $data);
			flock($fp, LOCK_UN);
			fclose($fp);
			
			// Write, then rename...
			@rename($temp_file, $file);
			
			// Double check permissions
			@chmod($file, 0777); 
			
			// Just in case the rename did not work
			@unlink($temp_file);
		}
		
        return TRUE;
	}
	/* END write_file() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Check that File is Really Writable, Even on Windows
	 *
	 * is_writable() returns TRUE on Windows servers when you really cannot write to the file
	 * as the OS reports to PHP as FALSE only if the read-only attribute is marked.  Ugh!
	 *
	 * Oh, and there is some silly thing with 
	 *
	 * @access	public
	 * @param	string		$path	- Path to be written to.
	 * @param	bool		$remove	- If writing a file, remove it after testing?
	 * @return	bool
	 */
    
	
	function is_really_writable($file, $remove = FALSE)
	{
		// is_writable() returns TRUE on Windows servers
		// when you really can't write to the file
		// as the OS reports to PHP as FALSE only if the
		// read-only attribute is marked.  Ugh?
		
		if (substr($file, -1) == '/' OR is_dir($file))
		{
			return $this->is_really_writable(rtrim($file, '/').'/'.uniqid(mt_rand()), TRUE);
		}
		
		if (($fp = @fopen($file, 'ab')) === FALSE)
		{
			return FALSE;
		}
		else
		{
			if ($remove === TRUE)
			{
				@unlink($file);
			}
			
			fclose($fp);
			return TRUE;
		}
	}
	/* END is_really_writable() */
	
	
	// --------------------------------------------------------------------

	/**
	 *	Check Secure Forms
	 *
	 *	Checks to see if Secure Forms is enabled, and if so sees if the submitted hash is valid
	 *
	 *	@access		public
	 *	@return		bool
	 */
	
	function check_secure_forms()
    {
        global $DB, $IN, $PREFS;
        
		/**	----------------------------------------
		/**	 Secure forms?
		/**	----------------------------------------*/
      
        if ( $this->EE->config->item('secure_forms') == 'y' )
        {
        	if ( ! isset($_POST['XID']) && ! isset($_GET['XID']))
        	{
        		return FALSE;
        	}
        	
        	$hash = (isset($_POST['XID'])) ? $_POST['XID'] : $_GET['XID'];
        	
            $query	= $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes 
            					  WHERE hash = '".$this->EE->db->escape_str($hash)."' 
            					  AND ip_address = '".$this->EE->db->escape_str($this->EE->input->IP)."'
            					  AND date > UNIX_TIMESTAMP()-7200");
        
            if ($query->row('count') == 0)
            {
				return FALSE;
			}
                                
			$this->EE->db->query("DELETE FROM exp_security_hashes WHERE (hash='".$this->EE->db->escape_str($hash)."' AND ip_address = '".$this->EE->db->escape_str($this->EE->input->IP)."') OR date < UNIX_TIMESTAMP()-7200");
        }
        
		/**	----------------------------------------
		/**	 Return
		/**	----------------------------------------*/
		
		return TRUE;
    }
    /* END check_secure_forms() */
	
	
	// --------------------------------------------------------------------

	/**
	 * A Slightly More Flexible Magic Checkbox
	 *
	 * Toggles the checkbox based on clicking anywhere in the table row that contains the checkbox
	 * Also allows multiple master toggle checkboxes at the top and bottom of a table to de/select all checkboxes
	 *		- give them a name="toggle_all_checkboxes" attribute
	 *		- No longer need to add onclick="toggle(this);" attribute
	 * No longer do you have to give your <form> tag an id="target" attrbiute, you can specify your own ID:
	 *		- <script type="text/javascript">create_magic_checkboxes('delete_cached_uris_form');</script>
	 *		- Or, if you specify no ID, it will find every <table> in the document with a class of 
	 		'magic_checkbox_table' and create the magic checkboxes automatically
	 * Also, it fixes that annoying problem where it was very difficult to easily select text in a row.
	 *
	 *
	 * @access	public
	 * @return	string
	 */
    
    function js_magic_checkboxes()
	{
		return file_get_contents(PATH_BRIDGE.'javascript/magic_checkboxes.js');
	}
    /* END js_magic_checkboxes() */
    
	
	
	// --------------------------------------------------------------------

	/**
	 * Balance a URI
	 *
	 * @access	public
	 * @param	string	$uri
	 * @return	array
	 */
    
	function balance_uri( $uri )
	{
		$uri = '/'.trim($uri, '/').'/';
		
		if ($uri == '//' OR $uri == '')
		{
			$uri = '/';
		}
		
		return $uri;
	}
	/* END balance_uri() */
	
	// --------------------------------------------------------------------

	/**
	 * Fetch Themes for a path
	 *
	 * @access	public
	 * @param	string		$path - Absolute server path to theme directory
	 * @return	array
	 */    
    
	function fetch_themes($path)
	{
		$themes = array();
		
		if ($fp = @opendir($path))
		{ 
			while (false !== ($file = readdir($fp)))
			{			
				if (is_dir($path.$file) AND substr($file, 0, 1) != '.') 
				{				
					$themes[] = $file;
				}
			}		 
			
			closedir($fp); 
		}
	
		sort($themes);
		
		return $themes;
	}
	/* END fetch_themes() */
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Allowed Group
	 *
	 * Member access validation
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function allowed_group($which = '')
	{
		if ( is_object($this->EE->cp))
		{
			return $this->EE->cp->allowed_group($which);
		}
		else
		{
			return $this->EE->display->allowed_group($which);
		}
	}
	/* END allowed_group() */
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Global Error Message Routine
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function show_error($which = '')
	{
		if ( function_exists('show_error'))
		{
			show_error($which);
		}
		else
		{
			$this->EE->display->error_message($which);
		}
	}
	/* END error_message() */
	
	// --------------------------------------------------------------------

	/**
	 *	Check if Submitted String is a Yes value
	 *
	 *	If the value is 'y', 'yes', 'true', or 'on', then returns TRUE, otherwise FALSE
	 *
	 *	@access		public
	 *	@param		string
	 *	@return		bool
	 */

    function check_yes($which)
    {
		switch($which)
		{
			case 'y'	:
			case 'yes'	:
			case 'on'	:
			case 'true'	:
				return TRUE;
			break;
			default		:
				return FALSE;	
			break;
		}
    }
    /* END check_yes() */
    
	// --------------------------------------------------------------------

	/**
	 *	Check if Submitted String is a No value
	 *
	 *	If the value is 'n', 'no', 'false', or 'off', then returns TRUE, otherwise FALSE
	 *
	 *	@access		public
	 *	@param		string
	 *	@return		bool
	 */

    function check_no($which)
    {
		switch($which)
		{
			case 'n'	:
			case 'no'	:
			case 'off'	:
			case 'false'	:
				return TRUE;
			break;
			default		:
				return FALSE;	
			break;
		}
    }
    /* END check_yes() */


	// --------------------------------------------------------------------

	/**
	 *	json_encode
	 *
	 *	@access		public
	 *	@param		variable
	 *	@return		string
	 */

    public function json_encode($data)
    {
		if (function_exists('json_encode'))
		{
			return json_encode($data);
		}
		
		if ( ! class_exists('Services_JSON'))
		{
			require_once(PATH_BRIDGE . 'third_party/JSON.php');
		}
		
		if ( ! is_object($this->json))
		{
			$this->json = new Services_JSON();
		}
		
		return $this->json->encode($data);
    }
    /* END json_encode() */


	// --------------------------------------------------------------------

	/**
	 *	json_decode
	 *
	 *	@access		public
	 *	@param		object
	 *	@return		bool
	 */

    function json_decode($data)
    {
		if (function_exists('json_decode'))
		{
			return json_decode($data);
		}
		
		if ( ! class_exists('Services_JSON'))
		{
			require_once(PATH_BRIDGE . 'third_party/JSON.php');
		}
		
		if ( ! is_object($this->json))
		{
			$this->json = new Services_JSON();
		}
		
		return $this->json->decode($data);
    }
    /* END json_decode() */

	// --------------------------------------------------------------------

	/**
	 *	Pagination for all versions front-end and back
	 *
	 *	* = optional
	 *	$input_data = array(
	 *		'sql'					=> '', 
	 *		'total_results'			=> '', 
	 *		*'url_suffix' 			=> '',
	 *		'tagdata'				=> ee()->TMPL->tagdata,
	 *		'limit'					=> '',
	 *		*'offset'				=> ee()->TMPL->fetch_param('offset'),
	 *		*'query_string_segment'	=> 'P',
	 *		'uri_string'			=> ee()->uri->uri_string,
	 *		*'current_page'			=> 0
	 *		*'pagination_config'	=> array()
	 *	);
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		array
	 */

	function universal_pagination( $input_data )
	{	
	    // -------------------------------------
	    //	prep input data
		// -------------------------------------

		//set defaults for optional items
		$input_defaults	= array(
			'url_suffix' 			=> '',
			'query_string_segment' 	=> 'P',
			'offset'				=> 0,
			'pagination_page'		=> 0,
			'pagination_config'		=> array(),
			'sql'					=> '',
			'tagdata'				=> '',
			'uri_string'			=> '',
			'paginate_prefix'		=> ''
		);

		//using query strings?
		$use_query_strings 				= (REQ == 'CP' OR ee()->config->item('enable_query_strings'));

		//array2 overwrites any duplicate key from array1
		$input_data 					= array_merge($input_defaults, $input_data);

		//make sure there is are surrounding slashes.
		$input_data['uri_string']		= '/' . trim($input_data['uri_string'], '/') . '/';

		//shortcuts
		$config							= $input_data['pagination_config'];		 
		$p								= $input_data['query_string_segment'];
		$config['query_string_segment'] = $input_data['query_string_segment'];
		$config['page_query_string']	= $use_query_strings;
				
		//current page
		if ( ! $use_query_strings AND preg_match("/$p(\d+)/s", $input_data['uri_string'], $match) )
		{		
			if ( $input_data['pagination_page'] == 0 AND is_numeric($match[1]) )
			{
				$input_data['pagination_page'] 	= $match[1];
				
				//remove page from uri string, query_string, and uri_segments
				$input_data['uri_string'] 		= ee()->functions->remove_double_slashes(
					str_replace($p . $match[1] , '', $input_data['uri_string'] )
				);
			}
		}
		else if ( $use_query_strings === FALSE)
		{
			if ( ! is_numeric($input_data['pagination_page']) )
			{
				$input_data['pagination_page'] = 0;
			}
		}
		else if ( ! in_array(ee()->input->get_post($input_data['query_string_segment']), array(FALSE, '')))
		{
			$input_data['pagination_page'] = ee()->input->get_post($input_data['query_string_segment']);
		}

		//this prevents the CI pagination class from 
		//trying to find the number itself... 
		//why isn't that an option?
		$config['uri_segment'] = 1000;

	    // -------------------------------------
	    //	prep return data
		// -------------------------------------

		$return_data 	= array(		
			'paginate'				=> FALSE,	 
			'paginate_tagpair_data'	=> '',
			'current_page'			=> 0,		
			'total_pages'			=> 0,
			'page_count'			=> '',
			'pagination_links'		=> '',
			'base_url'				=> '',
			'page_next'				=> '',
			'page_previous'			=> '',
			'pagination_page'		=> $input_data['pagination_page'],
			'tagdata'				=> $input_data['tagdata'],
			'sql'					=> $input_data['sql'],
		);

	    // -------------------------------------
	    //	Begin pagination check
		// -------------------------------------

		if (REQ == 'CP' OR 
				(strpos( $return_data['tagdata'], LD . $input_data['paginate_prefix'] . 'paginate' ) !== FALSE OR 
				 strpos( $return_data['tagdata'], LD . 'paginate' ) !== FALSE) 
			)
		{
			$return_data['paginate'] = TRUE;

			//has tags?
			if ($input_data['paginate_prefix'] != '' AND preg_match( 
					"/" . LD . $input_data['paginate_prefix'] . "paginate" . RD . "(.+?)" . 
						  LD . preg_quote(T_SLASH, '/') . $input_data['paginate_prefix'] . "paginate" . RD . "/s", 
					$return_data['tagdata'], 
					$match
				))
			{			
				$return_data['paginate_tagpair_data']	= $match[1];
				$return_data['tagdata'] 				= str_replace( $match[0], '', $return_data['tagdata'] );
			}
		
			//prefix comes first
			else if (preg_match( 
					"/" . LD . "paginate" . RD . "(.+?)" . LD . preg_quote(T_SLASH, '/') . "paginate" . RD . "/s", 
					$return_data['tagdata'], 
					$match
				))
			{			
				$return_data['paginate_tagpair_data']	= $match[1];
				$return_data['tagdata'] 				= str_replace( $match[0], '', $return_data['tagdata'] );
			}

			// ----------------------------------------
			//  Calculate total number of pages
			// ----------------------------------------

			$return_data['current_page'] 	= floor($input_data['pagination_page'] / $input_data['limit']) + 1;	
			$return_data['total_pages']		= ceil($input_data['total_results'] / $input_data['limit']);
			$return_data['page_count'] 		= ee()->lang->line('page') 		. ' ' . 
										 	  $return_data['current_page'] 	. ' ' . 
										 	  ee()->lang->line('of') 		. ' ' . 
										 	  $return_data['total_pages'];

			// ----------------------------------------
			//  Do we need pagination?
			// ----------------------------------------

			if ( $input_data['total_results'] > $input_data['limit'] )
			{
				if ( ! isset( $config['base_url'] )  )
				{
					$config['base_url']			= ee()->functions->create_url(
						$input_data['uri_string'] . $input_data['url_suffix'], 
						FALSE, 
						0
					);
				}
	
				$config['total_rows'] 	= $input_data['total_results'];
				$config['per_page']		= $input_data['limit'];
				$config['cur_page']		= $input_data['pagination_page'];

				ee()->load->library('pagination');

				ee()->pagination->initialize($config);

				$return_data['pagination_links']		= ee()->pagination->create_links();

				$return_data['base_url']				= ee()->pagination->base_url;

				//fix links if not query strings
				if ( ! $use_query_strings )
				{					
					$return_data['pagination_links']		= preg_replace( 
						"/" . preg_quote($return_data['base_url'], '/') . 
										"([0-9]+)(?:" . preg_quote(T_SLASH, '/') . ")?/s",
						rtrim( $return_data['base_url'] . $p . "$1", '/') . '/',
						$return_data['pagination_links']
					);
				}

				// ----------------------------------------
				//  Prepare next_page and previous_page variables
				// ----------------------------------------

				//next page?
				if ( (($return_data['total_pages'] * $input_data['limit']) - $input_data['limit']) >
				 	 $return_data['pagination_page'])
				{						
					$return_data['page_next'] = $return_data['base_url'] . 
						($use_query_strings ? '' : $p) . 
						($input_data['pagination_page'] + $input_data['limit']) . '/';
				}

				//previous page?
				if (($return_data['pagination_page'] - $input_data['limit'] ) >= 0) 
				{						
					$return_data['page_previous'] = $return_data['base_url'] . 
						($use_query_strings ? '' : $p) . 
						($input_data['pagination_page'] - $input_data['limit']) . '/';
				}
			}
		}

		//??
		$return_data['current_page'] 	+= $input_data['offset'];

		$return_data['sql'] 			.= 	' LIMIT ' . $return_data['pagination_page'] . 
											', ' . $input_data['limit'];

		return $return_data;
	}
	//	End universal_pagination	


	// --------------------------------------------------------------------
	
	/**
	 * Create XID
	 *
	 * This creates a new XID hash in the DB for usage.
	 *
	 * @access	public
	 * @return	string
	 */
	 
	public function create_xid()
	{
		$sql 		= "INSERT INTO exp_security_hashes (date, ip_address, hash) VALUES";

		$hash 		= ee()->functions->random('encrypt');
		$sql 		.= "(UNIX_TIMESTAMP(), '". ee()->input->ip_address() . "', '" . $hash . "')";

		$this->cacheless_query($sql);
			
		return $hash;
	}
	// END Create XID


	// --------------------------------------------------------------------
	
	/**
	 * cacheless_query
	 *
	 * this sends a query to the db non-cached
	 *
	 * @access	public
	 * @param	string	sql to query
	 * @return	object	query object
	 */
	public function cacheless_query($sql)
	{
		$reset = FALSE;
		
		// Disable DB caching if it's currently set
		
		if (ee()->db->cache_on == TRUE)
		{
			$this->EE->db->cache_off();
			$reset = TRUE;
		}
		
		$query = ee()->db->query($sql);

		// Re-enable DB caching
		if ($reset == TRUE)
		{
			ee()->db->cache_on();			
		}
		
		return $query;
	}
	// END cacheless_query

}
/* END Addon_builder Class */