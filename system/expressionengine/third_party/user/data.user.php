<?php if ( ! defined('EXT') ) exit('No direct script access allowed');
 
 /**
 * Solspace - User
 *
 * @package		Solspace:User
 * @author		Solspace DevTeam
 * @copyright	Copyright (c) 2008-2011, Solspace, Inc.
 * @link		http://solspace.com/docs/addon/c/User/
 * @version		3.3.1
 * @filesource 	./system/modules/user/
 * 
 */
 
 /**
 * User Module Class - Data Models
 *
 * Data Models for the User Module
 *
 * @package 	Solspace:User module
 * @author		Solspace DevTeam
 * @filesource 	./system/modules/user/data.user.php
 */
 
if (APP_VER < 2.0)
{
	require_once PATH.'bridge/lib/addon_builder/data.addon_builder.php';
}
else
{
	require_once PATH_THIRD . 'bridge/lib/addon_builder/data.addon_builder.php';
}

class User_data extends Addon_builder_data_bridge {

	public $prefs_cache = array(
		'channel_ids' => array()
	);

	// --------------------------------------------------------------------
	
	/**
	 * Get the Preference for the Module for the Current Site
	 *
	 * @access	public
	 * @param	array	Array of Channel/Weblog IDs
	 * @return	array
	 */
    
	public function get_channel_data_by_channel_array( $channels = array() )
    {
 		/** --------------------------------------------
        /**  Prep Cache, Return if Set
        /** --------------------------------------------*/
 		
 		$cache_name = __FUNCTION__;
 		$cache_hash = $this->_imploder(func_get_args());
 		
 		if (isset($this->cached[$cache_name][$cache_hash][ee()->config->item('site_id')]))
 		{
 			return $this->cached[$cache_name][$cache_hash][ee()->config->item('site_id')];
 		}
 		
 		$this->cached[$cache_name][$cache_hash][ee()->config->item('site_id')] = array();
 		
 		/** --------------------------------------------
        /**  Perform the Actual Work
        /** --------------------------------------------*/
        
        $extra = '';
        
        if (APP_VER < 2.0)
        {
			if (is_array($channels) && sizeof($channels) > 0)
			{
				$extra = " AND w.weblog_id IN ('".implode("','", ee()->db->escape_str($channels))."')";
			}
			
			$query = ee()->db->query("SELECT w.blog_title, w.weblog_id, s.site_id, s.site_label
										   FROM exp_weblogs AS w, exp_sites AS s
										   WHERE s.site_id = w.site_id
										   {$extra}");
										   
			foreach($query->result_array() as $row)
			{
				$this->cached[$cache_name][$cache_hash][ee()->config->item('site_id')][$row['weblog_id']] = $this->translate_keys($row);	
			}
		}
		else
		{
			if (is_array($channels) && sizeof($channels) > 0)
			{
				$extra = " AND c.channel_id IN ('".implode("','", ee()->db->escape_str($channels))."')";
			}
			
			$query = ee()->db->query("SELECT c.channel_title, c.channel_id, s.site_id, s.site_label
										   FROM exp_channels AS c, exp_sites AS s
										   WHERE s.site_id = c.site_id
										   {$extra}");
										   
			foreach($query->result_array() as $row)
			{
				$this->cached[$cache_name][$cache_hash][ee()->config->item('site_id')][$row['channel_id']] = $row;	
			}
		}
       
 		/** --------------------------------------------
        /**  Return Data
        /** --------------------------------------------*/
 		
 		return $this->cached[$cache_name][$cache_hash][ee()->config->item('site_id')];	
    }
    /* END get_module_preferences() */

	// --------------------------------------------------------------------

	function get_channel_id_pref($id)
	{
		//cache?
		if (isset($this->prefs_cache['channel_ids'][$id]))
		{
			return $this->prefs_cache['channel_ids'][$id];
		}
		
		$query = ee()->db->query(
			"SELECT preference_value
			 FROM	exp_user_preferences
			 WHERE	preference_name = 'channel_ids'"
		);
		
		if ($query->num_rows() == 0)
		{
			return array();
		}
		
		$channel_data = unserialize($query->row('preference_value'));
		
		if ( isset($channel_data[$id]) )
		{
			$this->prefs_cache['channel_ids'][$id] = $channel_data[$id];
			return $this->prefs_cache['channel_ids'][$id];
		}
		else
		{
			return array();
		}
	}

	// --------------------------------------------------------------------

	function get_channel_ids($use_cache = TRUE)
	{
		//cache?
		if ($use_cache AND isset($this->prefs_cache['channel_ids']['full']))
		{
			return $this->prefs_cache['channel_ids']['full'];
		}
		
		$query = ee()->db->query(
			"SELECT preference_value
			 FROM	exp_user_preferences
			 WHERE	preference_name = 'channel_ids'"
		);
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		$this->prefs_cache['channel_ids']['full'] = unserialize($query->row('preference_value'));
		return $this->prefs_cache['channel_ids']['full'];
		
	}	
	
}
// END CLASS User_data