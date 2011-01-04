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
 * A class that helps with the building of ExpressionEngine Extensions by allowing Bridge enabled extensions' classes
 * to be extensions of this class and thus gain all of the abilities of it and its parents.
 *
 * @package 	Bridge:Expansion
 * @subpackage	Add-On Builder
 * @category	Extensions
 * @author		Solspace DevTeam
 * @link		http://solspace.com/docs/
 * @filesource 	./system/bridge/lib/addon_builder/extension_builder.php
 */
 
if (APP_VER < 2.0)
{
	require_once PATH.'bridge/lib/addon_builder/addon_builder.php';
}
else
{
	require_once PATH_THIRD . 'bridge/lib/addon_builder/addon_builder.php';
}

class Extension_builder_bridge extends Addon_builder_bridge {
	
	var $language   = array();
    var $cur_used   = array();
    
    // --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */
    
	function Extension_builder_bridge($name='')
	{
		parent::Addon_builder_bridge($name);
		
		/** --------------------------------------------
        /**  Set Required Extension Variables
        /** --------------------------------------------*/
        
        $this->fetch_language_file($this->lower_name);
        	
		$this->name			= $this->line($this->lower_name.'_label');
		$this->description	= $this->line($this->lower_name.'_description');
		
		if (defined(strtoupper($this->lower_name).'_VERSION') && defined(strtoupper($this->lower_name).'_DOCS_URL'))
		{
			$this->version		= constant(strtoupper($this->lower_name).'_VERSION');
			$this->docs_url		= constant(strtoupper($this->lower_name).'_DOCS_URL');
		}
		
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
			$this->base	= (APP_VER < 2.0) ? $base_const . '&C=admin&M=utilities&P=extension_settings&name=' . $this->lower_name :
			 	str_replace('&amp;', '&', $base_const) . '&C=addons_extensions&M=extension_settings&file=' . $this->lower_name;
			
			$this->cached_vars['page_crumb']	= '';
			$this->cached_vars['page_title']	= '';
			$this->cached_vars['base_uri']		= $this->base;
			
			$this->cached_vars['onload_events']	= '';
			
			$this->cached_vars['extension_menu'] = array();
			$this->cached_vars['extension_menu_highlight'] = '';
			
			/** --------------------------------------------
			/**  Default Crumbs for Module
			/** --------------------------------------------*/

			if (APP_VER < 2.0)
			{
				$this->add_crumb($this->EE->config->item('site_name'), $base_const);
				$this->add_crumb(ee()->lang->line('admin'), $base_const . AMP . 'C=admin');
				$this->add_crumb(ee()->lang->line('utilities'),  $base_const . AMP . 'C=admin' . AMP . 'area=utilities');
				$this->add_crumb(ee()->lang->line('extensions_manager'), 
					$base_const . AMP . 'C=admin' . AMP . 'M=utilities' . AMP . 'P=extensions_manager');
			}
			
			$this->add_crumb($this->EE->lang->line($this->lower_name.'_label'), $this->cached_vars['base_uri']);
		}
	}
	/* END Extension_builder_bridge() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 *
	 * @access	public
	 * @return	null
	 */
    
	function activate_extension()
    {
    	$this->update_extension_hooks(TRUE);
		
		return TRUE;
	}
	/* END activate_extension() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * @access	public
	 * @return	null
	 */
    
	function disable_extension()
    {
    	ee()->db->query("DELETE FROM exp_extensions 
						 WHERE class = '".ee()->db->escape_str($this->extension_name)."'");
    				
    	/** --------------------------------------------
        /**  Remove from ee()->extensions->extensions array
        /** --------------------------------------------*/
        
        foreach(ee()->extensions->extensions as $hook => $calls)
        {
        	foreach($calls as $priority => $class_data)
        	{
        		foreach($class_data as $class => $data)
        		{
					if ($class == $this->class_name OR $class == $this->extension_name)
					{
						unset(ee()->extensions->extensions[$hook][$priority][$class]);
					}
				}
        	}
        }
	}
	/* END disable_extension() */
	
	
	// --------------------------------------------------------------------

	/**
	 * Install/Update Our Extension Hooks for Extension
	 *
	 * Tells ExpressionEngine what extension hooks we wish to use for this extension.  If an extension
	 * is part of a module, then it is the module's class name with the '_extension' suffix added on 
	 * to it.  Stand-alone extensions are just the class name.
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
        	ee()->db->query("UPDATE exp_extensions SET class = '".ee()->db->escape_str($this->extension_name)."' 
        					 WHERE class IN ('".ee()->db->escape_str($this->class_name.'_extension')."')");
        }
    	
    	/** --------------------------------------------
        /**  Determine Existing Methods
        /** --------------------------------------------*/
    	
    	$exists	= array();
    	
    	$query	= ee()->db->query( "SELECT method FROM exp_extensions 
    						   		WHERE class = '".ee()->db->escape_str($this->extension_name)."'");
    	
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
    			$data['class'] = $this->extension_name;
				ee()->db->query(ee()->db->insert_string('exp_extensions', $data));
    		}
    		else
    		{
    			unset($data['settings']);
    			
    			ee()->db->query( ee()->db->update_string( 'exp_extensions', 
												$data, 
												array(	'class'		=> $this->extension_name, 
														'method'	=> $data['method'])));
    		
    		}
    	}
    	
    	/** --------------------------------------------
        /**  Remove Old Hooks
        /** --------------------------------------------*/
    	
    	foreach(array_diff($exists, $current_methods) as $method)
    	{
    		ee()->db->query("DELETE FROM exp_extensions 
							 WHERE class = '".ee()->db->escape_str($this->extension_name)."' 
							 AND method = '".ee()->db->escape_str($method)."'");
    	}
    }
    /* END update_extension_hooks() */
    
    // --------------------------------------------------------------------

	/**
	 *	Last Extension Call Variable
	 *
	 *	You know that annoying ee()->extensions->last_call class variable that some moron put into the Extensions
	 *	class for when multiple extensions call the same hook?  This will take the possible default
	 *	parameter and a default value and return whichever is valid.  Examples:
	 *
	 *	$argument = $this->last_call($argument);		// Default argument or Last Call?
	 *	$argument = $this->last_call(NULL, array());	// No default argument.  If no Last Call, empty array is default.
	 *
	 *	@access		public
	 *	@param		string|array|null	The default argument sent by the Extensions class, if any.
	 *	@param		string|array|null	If no default argument and no ee()->extensions->last_call, the default value to return.
	 *	@return		string|array
	 */
	 
	function get_last_call($argument, $default = NULL)
	{
		global $EXT;
	
		if (ee()->extensions->last_call !== FALSE)
		{
			return ee()->extensions->last_call;
		}
		elseif ($argument !== NULL)
		{
			return $argument;
		}
		else
		{
			return $default;
		}
	}
	/* END get_last_call() */
	
	
/*
=====================================================
 Language Class
-----------------------------------------------------
Two known extensions sessions_end and sessions_start are called prior to Language being instantiated,
so we wrote our own little method here that removes the ee()->session->userdata check and still loads the
language file for the extension, if required...
=====================================================
*/

	/** -------------------------------------
    /**  Fetch a language file
    /** -------------------------------------*/

    function fetch_language_file($which = '', $object = FALSE)
    {
        global $IN, $OUT, $LANG, $PREFS, $FNS;
        
        if ($which == '')
        {
            return;
        }
        
        if (is_object($object) && strtolower(get_class($object)) == 'session' && $object->userdata['language'] != '')
        {
            $user_lang = $object->userdata['language'];
        }
        else
        {
			if (ee()->input->cookie('language'))
			{
				$user_lang = ee()->input->cookie('language');
			}
			elseif (ee()->config->item('deft_lang') != '')
            {
                $user_lang = ee()->config->item('deft_lang');
            }
            else
            {
                $user_lang = 'english';
            }
        }
        
        // Sec.ur.ity code.  ::sigh::
        
        $user_lang = ee()->security->sanitize_filename($user_lang);
            
        if ( ! in_array($which, $this->cur_used))
        {
			$options = array($this->addon_path.'language/'.$user_lang.'/lang.'.$which.EXT,
							 $this->addon_path.'language/english/lang.'.$which.EXT);
        					 
        	$success = FALSE;
        	
        	foreach($options as $path)
        	{
        		if ( file_exists($path) && include $path)
        		{
        			$success = TRUE;
        			break;
        		}
        	}
        
        	if ($success === FALSE)
        	{
				return;
            }
            
            $this->cur_used[] = $which;
            
            if (isset($L))
            {
            	$this->language = array_merge($this->language, $L);
            	
            	if (is_object($LANG))
            	{
            		ee()->lang->language = array_merge(ee()->lang->language, $L);
            		ee()->lang->cur_used[] = $which;
            	}
            	
            	unset($L);
            }
        }
    }
    /* END */
    
    /** -------------------------------------
    /**  Fetch a specific line of text
    /** -------------------------------------*/

    function line($which = '', $label = '')
    {
    	global $PREFS;
    
        if ($which != '')
        {
        	if ( ! isset($this->language[$which]))
        	{
        		$line = $which;
        	}
        	else
			{
				$line = ( ! isset($this->language[$which])) ? FALSE : $this->language[$which];
							
				$word_sub = (ee()->config->item('weblog_nomenclature') != '' AND ee()->config->item('weblog_nomenclature') != "weblog") ? ee()->config->item('weblog_nomenclature') : '';
				
				if ($word_sub != '')
				{
					$line = preg_replace("/metaweblog/i", "Tr8Vc345s0lmsO", $line);
					$line = str_replace('"weblog"', 'Ghr77deCdje012', $line);
					$line = str_replace('weblog', strtolower($word_sub), $line);
					$line = str_replace('Weblog', ucfirst($word_sub),    $line);
					$line = str_replace("Tr8Vc345s0lmsO", 'Metaweblog', $line);
					$line = str_replace("Ghr77deCdje012", '"weblog"', $line);
				}
			}
            
            if ($label != '')
            {
                $line = '<label for="'.$label.'">'.$line."</label>";
            }
            
            return stripslashes($line);
        }
    }
    /* END */
    
    
}
/* END Extension_builder Class */

?>