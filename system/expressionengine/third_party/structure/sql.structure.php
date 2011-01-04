<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SQL Model for Structure
 *
 * This file must be in your /system/third_party/structure directory of your ExpressionEngine installation
 *
 * @package             Structure
 * @author              Jack McDade (jack@jackmcdade.com)
 * @copyright			Copyright (c) 2010 Travis Schmeisser
 * @link                http://buildwithstructure.com
 */

/**
 * 
 */
class Sql_structure
{
	
	var $site_id;
	var $data  = array();
	var $cids  = array();
	var $lcids = array();
	
	function Sql_structure()
	{
		$this->EE =& get_instance();		
		$this->site_id = $this->EE->config->item('site_id');
	}
	
	
	/**
	 * Get global and MSM specific settings
	 * from exp_structure_settings table
	 *
	 * @return array
	 **/
	function get_settings()
	{
		$site_id = '0,'.$this->site_id;
		$result = $this->EE->db->query("SELECT var_value, var FROM exp_structure_settings WHERE site_id IN ({$site_id})");

		$settings = array();
		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				$settings[$row['var']] = $row['var_value'];
			}
		}
		return $settings;
	}
	
	
	/**
	 * Get entry data on all Structure Channels
	 *
	 * @return array
	 */
	function get_data()
	{
		$data = array();
		
		$sql = "SELECT node.*, (COUNT(parent.entry_id) - 1) AS depth, expt.title, expt.status
				FROM exp_structure AS node
				INNER JOIN exp_structure AS parent
					ON node.lft BETWEEN parent.lft AND parent.rgt
				INNER JOIN exp_channel_titles AS expt
					ON node.entry_id = expt.entry_id
				WHERE parent.lft > 1
				AND node.site_id = {$this->site_id}
				AND parent.site_id = {$this->site_id}
				GROUP BY node.entry_id
				ORDER BY node.lft";
		$result = $this->EE->db->query($sql);

		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				$data[$row['entry_id']] = $row;
			}
		}

		return $data;
	}
	
	
	/**
	 * Get data from the exp_pages table
	 *
	 * @return array with site_id as key
	 */
	function get_site_pages()
	{
		$result = $this->EE->db->query("SELECT * FROM exp_sites WHERE site_id = $this->site_id");	
		$site_pages = unserialize(base64_decode($result->row('site_pages')));

		return $site_pages[$this->site_id];
	}
	
	
	/**
	 * Get Templates
	 *
	 * @return Single dimensional array of templates, ids and names
	 **/
	function get_templates()
	{
		$sql = "SELECT tg.group_name, t.template_id, t.template_name
				FROM   exp_template_groups tg, exp_templates t
				WHERE  tg.group_id = t.group_id 
				AND tg.site_id = {$this->site_id}
				ORDER BY tg.group_name, t.template_name";
		$templates = $this->EE->db->query($sql);
		
		return $templates->result_array();
	}
	
	
	/**
	 * User Access
	 *
	 * @param string $perm 
	 * @param array $settings 
	 * @return void
	 */
	function user_access($perm, $settings = array())
	{
		
		$user_group = $this->EE->session->userdata['group_id'];

		// super admins always have access
		if ($user_group == 1)
		{
			return TRUE;
		}

		$admin_perm = 'perm_admin_structure_' . $user_group;
		$this_perm	= $perm . '_' . $user_group;

		if ($settings !== array())
		{
			if ((isset($settings[$admin_perm]) || isset($settings[$this_perm])))
			{
				return true;
			}
			else
			{
				return FALSE;
			}
		}

		// settings were not passed we have to go to the DB for the check
		$result = $this->EE->db->query("SELECT var FROM exp_structure_settings WHERE var = '$admin_perm' OR var = '$this_perm'");
		if ($result->num_rows() > 0)
		{
			return TRUE;
		}

		return FALSE;
	}
	
	
	/**
	 * Module Is Installed
	 *
	 * @return bool TRUE if installed
	 * @return bool FALSE if not installed
	 */
	function module_is_installed()
	{
		$results = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Structure'");

	    if ($results->num_rows > 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	
	/**
	 * Extension Is Installed
	 *
	 * @return bool TRUE if installed
	 * @return bool FALSE if not installed
	 */
	function extension_is_installed()
	{
		$results = $this->EE->db->query("SELECT * FROM exp_extensions WHERE class = 'Structure_ext' AND enabled='y'");
	    if ($results->num_rows > 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Get Module ID
	 *
	 * @return numeral Module's ID
	 */
	function get_module_id()
	{
		$results = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Structure'");
		
		if ($results->num_rows > 0)
		{
			return $results->row('module_id');
		}
		else
		{
			return FALSE;
		}
	}
	
	
	/**
	 * Get Member Groups
	 *
	 * @return Array of member groups with access to Structure
	 */
	function get_member_groups()
	{
		$sql = "SELECT mg.group_id AS id, mg.group_title AS title 
				FROM exp_member_groups AS mg
				INNER JOIN exp_module_member_groups AS modmg
				ON (mg.group_id = modmg.group_id) 
				WHERE mg.can_access_cp = 'y' 
					AND mg.can_access_publish = 'y'
					AND mg.can_access_edit = 'y' 
					AND mg.group_id <> 1 
					AND modmg.module_id = {$this->get_module_id()}
					AND mg.site_id = {$this->site_id}
				ORDER BY mg.group_id";
				
		$groups = $this->EE->db->query($sql);
		
		if ($groups->num_rows > 0)
		{
			return $groups->result_array();
		}
		else
		{
			return FALSE;
		}
	}
	
	
	/**
	 * Get Entry Title
	 *
	 * @param string $entry_id 
	 * @return string Entry Title or NULL
	 */
	function get_entry_title($entry_id)
	{

		$result = $this->EE->db->query("SELECT title FROM exp_channel_titles WHERE entry_id = {$entry_id}");

		if ($result->num_rows > 0)
		{
			return $result->row('title');
		}
		else
		{
			return NULL;
		}
	}
	
	
	function cleanup_check()
	{
		$vals = array();
		
		// Remove extraneous entries in exp_structure
		$site_pages = $this->get_site_pages();
		$keys = array_keys($site_pages['uris']);
		$entry_ids = implode(",", $keys);
		
		$sql = "SELECT * FROM exp_structure
				WHERE site_id = $this->site_id
				AND entry_id NOT IN ($entry_ids)";
				
		$query = $this->EE->db->query($sql);
 		$vals['duplicate_entries'] = $query->num_rows();

		// Duplicate Right Values
		$sql = "SELECT entry_id, rgt, 
		 			COUNT(rgt) AS duplicates
				FROM exp_structure
				WHERE site_id = $this->site_id
				GROUP BY rgt
					HAVING ( COUNT(rgt) > 1 )";
					
		$query = $this->EE->db->query($sql);
		$vals['duplicate_rights'] = $query->num_rows();
		
		// Duplicate Left Values
		$sql = "SELECT entry_id, lft, 
		 			COUNT(rgt) AS duplicates
				FROM exp_structure
				WHERE site_id = $this->site_id
				GROUP BY lft
					HAVING ( COUNT(lft) > 1 )";
					
		$query = $this->EE->db->query($sql);
		$vals['duplicate_lefts'] = $query->num_rows();

		return $vals;
	}
	
	
	/**
	 * Clean up invalid Structure data
	 **/
	function cleanup()
	{
		$vars = array();
		
		// Remove extraneous entries in exp_structure
		$site_pages = $this->get_site_pages();
		$keys = array_keys($site_pages['uris']);
		$entry_ids = implode(",", $keys);
		
		$sql = "DELETE FROM exp_structure
				WHERE site_id = $this->site_id
				AND entry_id NOT IN ($entry_ids)";
				
		$query = $this->EE->db->query($sql);
			
		// Adjust the root node's right value
		$sql = "SELECT MAX(rgt) AS max_right FROM exp_structure where site_id != 0";
		$query = $this->EE->db->query($sql);
		$max_right = $query->row('max_right') + 1;
		
		$sql = "UPDATE exp_structure SET rgt = $max_right WHERE site_id = 0";
		$this->EE->db->query($sql);
		
		return $vars;
	}
	
	
}
