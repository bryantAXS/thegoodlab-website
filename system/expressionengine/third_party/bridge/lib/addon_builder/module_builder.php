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
 * Module Builder
 *
 * A class that helps with the building of ExpressionEngine Modules by allowing Bridge enabled modules
 * to be extensions of this class and thus gain all of the abilities of it and its parents.
 *
 * @package 	Bridge:Expansion
 * @subpackage	Add-On Builder
 * @category	Modules
 * @author		Solspace DevTeam
 * @link		http://solspace.com/docs/
 * @filesource 	./system/bridge/lib/addon_builder/module_builder.php
 */

if (APP_VER < 2.0)
{
	require_once PATH.'bridge/lib/addon_builder/addon_builder.php';
}
else
{
	require_once PATH_THIRD . 'bridge/lib/addon_builder/addon_builder.php';
}

class Module_builder_bridge extends Addon_builder_bridge {

	var $module_actions	= array();
	var $hooks			= array();
	
	var $base			= '';
    
    // --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */
    
	function Module_builder_bridge($name='')
	{
		global $LANG, $PREFS, $DB;
		
		parent::Addon_builder_bridge($name);

		/** --------------------------------------------
		/**  Default CP Variables
		/** --------------------------------------------*/

		if (REQ == 'CP')
		{
			//BASE is not set until AFTER sessions_end, and we don't want to clobber it.
			$base_const = defined('BASE') ? BASE :  SELF . '?S=0';
			
			//2.x adds an extra param for base
			if ( ! (APP_VER < 2.0) )
			{
				$base_const .= '&amp;D=cp';
			}
		
			// For 2.0, we have '&amp;D=cp' with BASE and we want pure characters, so we convert it
			$this->base	= (APP_VER < 2.0) ? $base_const.'&C=modules&M='.$this->lower_name : str_replace('&amp;', '&', $base_const).'&C=addons_modules&M=show_module_cp&module='.$this->lower_name;
			
			$this->cached_vars['page_crumb']	= '';
			$this->cached_vars['page_title']	= '';
			$this->cached_vars['base_uri']		= $this->base;
			
			$this->cached_vars['onload_events']  = '';
			
			$this->cached_vars['module_menu'] = array();
			$this->cached_vars['module_menu_highlight'] = '';
			
			/** --------------------------------------------
			/**  Default Crumbs for Module
			/** --------------------------------------------*/

			if (APP_VER < 2.0)
			{
				$this->add_crumb($this->EE->config->item('site_name'), $base_const);
				$this->add_crumb($this->EE->lang->line('modules'), $base_const.AMP.'C=modules');
			}
			
			$this->add_crumb($this->EE->lang->line($this->lower_name.'_module_name'), $this->cached_vars['base_uri']);
		}
	}
	/* END Module_builder_bridge() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */

    function default_module_install()
    {        
        $this->install_module_sql();
        $this->update_module_actions();
       	$this->update_extension_hooks();
        
        return TRUE;
    }
	/* END default_module_install() */
    
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */

    function default_module_uninstall()
    {
        global $DB;
        
        $query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = '".$this->EE->db->escape_str($this->class_name)."'");
        
        if (file_exists($this->addon_path.$this->lower_name.'.sql'))
        {
        	if (preg_match_all("/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`([^`]+)`/", file_get_contents($this->addon_path.$this->lower_name.'.sql'), $matches))
			{
				foreach($matches[1] as $table)
				{
					$sql[] = "DROP TABLE IF EXISTS `".$this->EE->db->escape_str($table)."`";
				}
			}
		}

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id')."'";
        $sql[] = "DELETE FROM exp_modules WHERE module_name = '".$this->EE->db->escape_str($this->class_name)."'";
        $sql[] = "DELETE FROM exp_actions WHERE class = '".$this->EE->db->escape_str($this->class_name)."'";
    
        foreach ($sql as $query)
        {
            $this->EE->db->query($query);
        }
        
        $this->remove_extension_hooks();

        return TRUE;
    }
    /* END default_module_uninstall() */
    
	// --------------------------------------------------------------------

	/**
	 * Module Update
	 *
	 * @access	public
	 * @return	bool
	 */

    function default_module_update()
    {
        global $DB;
        
        $this->update_module_actions();
    	$this->update_extension_hooks();
    	
    	unset($this->cache['database_version']);

        return TRUE;
    }
    /* END default_module_update() */

    
	// --------------------------------------------------------------------

	/**
	 * Install Module SQL
	 *
	 * @access	public
	 * @return	null
	 */
	
	public function install_module_sql()
	{
		// --------------------------------------------
        //  Our Install Queries
        // --------------------------------------------
        
        if (file_exists($this->addon_path . strtolower($this->lower_name) . '.sql'))
        {  
			$sql = preg_split(
				"/;;\s*(\n+|$)/", 
				file_get_contents($this->addon_path . strtolower($this->lower_name) . '.sql'), 
				-1, 
				PREG_SPLIT_NO_EMPTY
			);
			
			foreach($sql as $i => $query)
			{
				$sql[$i] = trim($query);
			}
		}
		
		// --------------------------------------------
        //  Module Install
        // --------------------------------------------
		
        foreach ($sql as $query)
        {
            $this->EE->db->query($query);
        }
	}
	//END install_module_sql()
	    
	// --------------------------------------------------------------------

	/**
	 * Module Actions
	 *
	 * Insures that we have all of the correct actions in the database for this module
	 *
	 * @access	public
	 * @return	array
	 */

	function update_module_actions()
    {
    	global $DB;
    	
    	$exists	= array();
    	
    	$query	= $this->EE->db->query("SELECT method FROM exp_actions 
    						   			WHERE class = '".$this->EE->db->escape_str($this->class_name)."'" );
    	
    	foreach ( $query->result_array() AS $row )
    	{
    		$exists[] = $row['method'];
    	}
    	
    	/** --------------------------------------------
        /**  Actions of Module Actions - Bug Fix - $this->actions is now an object
        /** --------------------------------------------*/
        
        $actions = (is_array($this->actions) && sizeof($this->actions) > 0) ? $this->actions : $this->module_actions;
    	
    	/** --------------------------------------------
        /**  Add Missing Actions
        /** --------------------------------------------*/
    	
    	foreach(array_diff($actions, $exists) as $method)
    	{
    		$this->EE->db->query($this->EE->db->insert_string('exp_actions', array(	'class'		=> $this->class_name,
    																				'method'	=> $method)));
    	}
    	
    	/** --------------------------------------------
        /**  Delete No Longer Existing Actions
        /** --------------------------------------------*/
    	
    	foreach(array_diff($exists, $actions) as $method)
    	{
    		$this->EE->db->query("DELETE FROM exp_actions 
    					WHERE class = '".$this->EE->db->escape_str($this->class_name)."' 
    					AND method = '".$this->EE->db->escape_str($method)."'");
    	}
    }
    /* END update_module_actions() */
    
	
	// --------------------------------------------------------------------

	/**
	 * Install/Update Our Extension for Module
	 *
	 * Tells ExpressionEngine what extension hooks we wish to use for this module.  If an extension
	 * is part of a module, then it is the module's class name with the '_extension' (1.x) or '_ext' 2.x
	 * suffix added on to it. 
	 *
	 * @access	public
	 * @return	null
	 */

	function update_extension_hooks()
    {	
    	if ( ! is_array($this->hooks) OR sizeof($this->hooks) == 0)
    	{
    		return TRUE;
    	}
    	
    	/** --------------------------------------------
        /**  First, Upgrade any EE 1.x Hooks to EE 2.x Format
        /** --------------------------------------------*/
        
        if (APP_VER >= 2.0)
        {
        	$this->EE->db->query("UPDATE exp_extensions SET class = '".$this->extension_name."' 
        						  WHERE class IN ('".$this->EE->db->escape_str($this->class_name.'_extension')."')");
        }
    	
    	/** --------------------------------------------
        /**  Determine Existing Methods
        /** --------------------------------------------*/
    	
    	$exists	= array();
    	
    	$query	= $this->EE->db->query( "SELECT method FROM exp_extensions 
    						   			WHERE class = '".$this->EE->db->escape_str($this->extension_name)."'");
    	
    	foreach ( $query->result_array() AS $row )
    	{
    		$exists[] = $row['method'];
    	}
    	
    	/** --------------------------------------------
        /**  Find Missing and Insert
        /** --------------------------------------------*/
        
        $current_methods = array();
    	
    	foreach($this->hooks as $data)
    	{
    		$current_methods[] = $data['method'];
    	
    		if ( ! in_array($data['method'], $exists))
    		{
				$this->EE->db->query($this->EE->db->insert_string('exp_extensions', $data));
    		}
    		else
    		{
    			unset($data['settings']);
    			
    			$this->EE->db->query( $this->EE->db->update_string( 'exp_extensions', 
												$data, 
												array(	'class' => $data['class'], 
														'method' => $data['method'])));
    		
    		}
    	}
    	
    	/** --------------------------------------------
        /**  Remove Old Hooks
        /** --------------------------------------------*/
    	
    	foreach(array_diff($exists, $current_methods) as $method)
    	{
    		$this->EE->db->query("DELETE FROM exp_extensions 
								WHERE class = '".$this->EE->db->escape_str($this->extension_name)."' 
								AND method = '".$this->EE->db->escape_str($method)."'");
    	}
    }
    /* END update_extension_hooks() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Remove Extension Hooks
	 *
	 * Removes all of the extension hooks that will be called for this module
	 *
	 * @access	public
	 * @return	null
	 */

	function remove_extension_hooks()
    {
    	$this->EE->db->query("DELETE FROM exp_extensions 
							  WHERE class = '".$this->EE->db->escape_str($this->extension_name)."'");
    				
    	/** --------------------------------------------
        /**  Remove from $this->EE->extensions->extensions array
        /** --------------------------------------------*/
        
        foreach($this->EE->extensions->extensions as $hook => $calls)
        {
        	foreach($calls as $priority => $class_data)
        	{
        		foreach($class_data as $class => $data)
        		{
					if ($class == $this->class_name OR $class == $this->extension_name)
					{
						unset($this->EE->extensions->extensions[$hook][$priority][$class]);
					}
				}
        	}
        }
    }
    /* END remove_extension_hooks() */
    	
	
	// --------------------------------------------------------------------
		
	/**
	 * Equalize Menu Text
	 *
	 * Goes through an array of Main Menu links and text so that we can equalize the width of the tabs.
	 * 
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	
	function equalize_menu($array = array())
	{
		$length = 1;
		
		foreach($array as $key => $data)
		{
			$length = (strlen(strip_tags($data['title'])) > $length) ? strlen(strip_tags($data['title'])) : $length;
		}
		
		foreach ($array as $key => $data)
		{
			$i = ceil(($length - strlen(strip_tags($data['title'])))/2);
						
			$array[$key]['title'] = str_repeat("&nbsp;", $i).$data['title'].str_repeat("&nbsp;", $i);
		}
		
		return $array;
	}
	/* END equalize_menu() */
	
	
	// --------------------------------------------------------------------

	/**
	 *	Module Specific No Results Parsing
	 *
	 *	Looks for (your_module)_no_results and uses that, otherwise it returns the default no_results conditional
	 *
	 *	@access		public
	 *	@return		string
	 */

    function no_results()
    {
		if ( preg_match( "/".LD."if ".preg_quote($this->lower_name)."_no_results".RD."(.*?)".LD.preg_quote(T_SLASH, '/')."if".RD."/s", $this->EE->TMPL->tagdata, $match ) )
		{
			return $match[1];
		}
		else
		{
			return $this->EE->TMPL->no_results();
		}
    }
    /* END no_results() */
    
}
/* END Module_builder Class */

?>