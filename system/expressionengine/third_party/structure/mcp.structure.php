<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Control Panel (MCP) File for Structure
 *
 * This file must be in your /system/third_party/structure directory of your ExpressionEngine installation
 *
 * @package             Structure for EE2
 * @author              Jack McDade (jack@jackmcdade.com)
 * @author              Travis Schmeisser (travis@rockthenroll.com)
 * @copyright			Copyright (c) 2010 Travis Schmeisser
 * @link                http://buildwithstructure.com
 */
 // Thanks also to Jeremy Messenger and many others for their code contributions.


/**
 * Include Structure SQL Model
 */
require_once PATH_THIRD.'structure/sql.structure.php';


/**
 * Include Structure Core Mod
 */
require_once PATH_THIRD.'structure/mod.structure.php';


class Structure_mcp
{

	var $debug = FALSE;
	var $version = '2.2.4';
	var $structure;
	var $sql;
	var $perms = array(
		'perm_admin_structure'  => 'Manage Structure Settings',
		'perm_view_add_page'    => 'View add page link',
		'perm_delete'   		=> 'Can delete',
		'perm_reorder'			=> 'Can reorder'
	);


	/**
	 * Constructor
	 * @param bool $switch
	 */
	function Structure_mcp($switch = TRUE)
	{
		$this->EE =& get_instance();
		$this->sql = new Sql_structure();
		$this->structure = new Structure();
	
		$settings = $this->sql->get_settings();
		$channel_data = $this->structure->get_structure_channels('page');
		
		$nav = array();
		// Check if we have admin permission		
		if ($this->structure->user_access('perm_admin_structure', $settings) && $settings['show_global_add_page'] == 'y' && $this->EE->input->get('method') === FALSE)
		{
			// If only one Structure "Page" Channel, don't use the modal window
			if (count($channel_data) == 1)
			{
				$nav['Add&nbsp;Page'] = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.key($channel_data);
			}
			else
			{
				$nav['Add Page'] = '#';
			}
		}
		$nav['Channel Settings'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure'.AMP.'method=channel_settings';
		$nav['Module Settings'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure'.AMP.'method=module_settings';
		if ($this->debug === TRUE)
		{
			$nav['Debug'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure'.AMP.'method=debug';
		}

		$this->EE->cp->set_right_nav($nav);
	}

	/**
	 * Main CP page
	 * @param string $message
	 */
	function index($message = FALSE)
	{
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('structure_module_name'));
		
		$settings = $this->sql->get_settings();
		
		// Load Libraries and Helpers
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('path');
		$this->EE->load->helper('form');
			
		// Check if we have admin permission
		$permissions = array();
		$permissions['admin'] = $this->structure->user_access('perm_admin_structure', $settings);
		$permissions['view_add_page'] = $this->structure->user_access('perm_view_add_page', $settings);
		$permissions['delete'] = $this->structure->user_access('perm_delete', $settings);
		$permissions['reorder'] = $this->structure->user_access('perm_reorder', $settings);
		
		$this->EE->cp->load_package_js('jquery_tools');
		$this->EE->cp->load_package_js('structure.new');
		$this->EE->cp->load_package_js('interface-1.2');
		
		// Enable/disable dragging and reordering
		if ((isset($permissions['reorder']) && $permissions['reorder']) || $permissions['admin'])
			$this->EE->cp->load_package_js('inestedsortable');
		
		$data['data'] 			= $this->sql->get_data();
		$data['valid_channels'] = $this->structure->get_structure_channels('page', '', 'alpha');
		$data['listing_cids'] 	= $this->structure->get_data_cids(TRUE);
		$data['settings'] 		= $settings;
		$data['asset_data'] 	= $this->structure->get_structure_channels('asset');
		$data['site_pages'] 	= $this->structure->get_site_pages();		
		$data['site_uris']  	= $data['site_pages']['uris'];
		$data['asset_path'] 	= PATH_THIRD.'structure/views/';
		$data['attributes'] 	= array('class' => 'form', 'id' => 'delete_form');
		$data['action_url'] 	= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure'.AMP.'method=delete';
		$data['permissions']	= $permissions;
		$data['theme_url']		= $this->EE->config->item('theme_folder_url') . 'third_party/structure';
		
		$valid_ids = '';
		$valid = array_diff($data['valid_channels'], $data['listing_cids']);

		foreach ($valid as $id)
			$valid_ids .= $id . ',';
			
		$valid_ids = (substr_replace($valid_ids ,'',-1));
		
		$this->EE->cp->add_to_head("<link rel='stylesheet' href='{$data['theme_url']}/css/structure-new.css'>");
		$this->EE->cp->add_to_head('
		<script type="text/javascript">
			var structure_settings = {
				"site_id": ' . $this->EE->config->item('site_id') . ',
				"xid": "' . XID_SECURE_HASH . '",
				"global_add_page": "' . $settings['show_global_add_page'] . '",
				"admin": ' . ($permissions['admin'] ? 'true' : 'false') .
			'};
		</script>');

		return $this->EE->load->view('index', $data, TRUE);
	}
	
	
	/**
	 * Channel settings page
	 * @param string $message
	 */
	function channel_settings($message = FALSE)
	{		
		// Load Libraries and Helpers
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');
		
		// Set Breadcrumb and Page Title
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure', $this->EE->lang->line('structure_module_name'));
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('cp_channel_settings_title'));
		
		$settings = $this->sql->get_settings();
		
		// Check if we have admin permission
		$permissions = array();
		$permissions['admin'] = $this->structure->user_access('perm_admin_structure', $settings);
		$permissions['view_add_page'] = $this->structure->user_access('perm_view_add_page', $settings);
		$permissions['delete'] = $this->structure->user_access('perm_limited_delete', $settings);
		
		// Vars to send into view
		$vars = array();
		$vars['data'] = $this->sql->get_data();
		$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure'.AMP.'method=channel_settings_submit';
		$vars['attributes'] = array('class' => 'form', 'id' => 'structure_settings');
		$vars['channel_data'] = $this->structure->get_structure_channels();
		$vars['templates'] = $this->sql->get_templates();
		$vars['permissions'] = $permissions;
		$vars['channel_check']	= FALSE;
					
		// Check for ANY channels
		$query = $this->EE->db->query("SELECT channel_id FROM exp_channels");
		if ($query->num_rows() > 0)
		{
			$vars['channel_check'] = TRUE;
		}
		
		return $this->EE->load->view('channel_settings', $vars, TRUE);
	}
	
	/**
	 * Reorder Structure Pages
	 *
	 * @return AJAX POST for reordering
	 **/
	function ajax_reorder()
	{
		// Grab the AJAX post
		if (isset($_POST['page-ui']) && is_array($_POST['page-ui']))
		{
			$sortable = $_POST['page-ui'];
		}
		else
		{
			die('no page data');
		}

		if (isset($_GET['site_id']) && is_numeric($_GET['site_id']) && $_GET['site_id'] > 0)
		{
			$site_id = $_GET['site_id'];
		}
		else
		{
			die('no site_id');
		}

		// Convert the array to php
		$data = $this->structure->nestedsortable_to_nestedset($sortable);
		
		$titles = array();
		$site_pages = $this->sql->get_site_pages();
		$structure_data = $this->sql->get_data();
		
		$uris = $site_pages['uris'];
		
		// Get Page Slugs
		foreach ($uris as $key => $uri)
		{
			$slug = trim($uri, '/');
			if (strpos($slug, '/'))
				$slug = substr(strrchr($slug, '/'), 1);

			if ($uri == "/")
				$slug = $uri;

			@$titles[$key] .= $slug;
		}
		
		// Build an array with all current channel_ids
		$results = $this->EE->db->query("SELECT * FROM exp_channel_data");
		$channel_data = array();
		if ($results->num_rows() > 0)
		{
			foreach($results->result_array() as $row)
			{
				$channel_data[$row['entry_id']] = $row['channel_id'];
			}
		}
		
		$row_insert = array();
		$page_uris = array();
		
		foreach($data as $key => $row)
		{
			$depth = count($row['crumb']);
			
			$row['site_id'] = $site_id;
			$row['entry_id'] = $entry_id = $row['crumb'][$depth - 1];
			$row['parent_id'] = $depth < 2 ? 0 : $row['crumb'][$depth - 2];
			$row['channel_id'] = $channel_data[$entry_id];
			$row['listing_cid'] = $structure_data[$entry_id]['listing_cid'];
			$row['dead'] = '';
			
			// build URI path for pages
			$uri_titles = array();
			foreach($data[$key]['crumb'] as $entry_id)
			{
				$uri_titles[] = $titles[$entry_id];
			}
			
			// Remove invalid row fields
			unset($row['depth']);
			unset($row['crumb']);			
			
			// Build pages URI
			$page_uris[$key] = trim(implode('/', $uri_titles), '/');
			// Account for "/" home page
			$page_uris[$key] = $page_uris[$key] == '' ? '/' : '/'.$page_uris[$key].'/';
			
			// be sanitary
			foreach($row as $field => $value)
			{
				$row[$field] = $this->EE->db->escape_str($value);
			}
			
			// build insert rows
			$row_insert[] = "('".implode("','", $row)."')";
		}
		
		// Multi-line insert of all Structure Data
		$sql = "REPLACE INTO exp_structure (".implode(', ', array_keys($row)).") VALUES ".implode(', ', $row_insert);
		$this->EE->db->query($sql);
				
		// Update Site Pages
		$site_pages['uris'] = $page_uris;	 
		
		// And save this moved page to the array
		$this->structure->set_site_pages($site_id, $site_pages);
		
		// Sorting pages blows away the listing data, so all URL's for listing items
		// are no longer in the site_pages array... lets fix that.
		foreach($site_pages['uris'] as $entry_id => $uri)
		{		
		   // Retrieve previous listing channel id
			$lcid_result = $this->EE->db->query("SELECT * FROM exp_structure WHERE entry_id = " . $entry_id);

			$listing_cid = $lcid_result->row('listing_cid');
			$lcid = $listing_cid ? $listing_cid : false;
			
			if($lcid)
			{
				// Retrieve all entries for channel
				$listing_entries = $this->EE->db->query("SELECT * FROM exp_structure_listings WHERE channel_id = " . $lcid);

				foreach ($listing_entries->result_array() as $l_entry)
				{
					$listing_data = array(
						'site_id' => $site_id,
						'channel_id' => $l_entry['channel_id'],
						'parent_id' => $l_entry['parent_id'],
						'entry_id' => $l_entry['entry_id'],
						'template_id' => $l_entry['template_id'],
						'parent_uri' => $site_pages['uris'][$entry_id],
						'uri' => $l_entry['uri']
					);
				
					// Update structure_listings table, and site_pages array with proper data
					$this->structure->set_listing_data($listing_data);
				}
			}
		}
		die('Reordered');
	}
	
	/**
	 * Module settings page
	 * @param string $message
	 */
	function module_settings($message = FALSE)
	{
		$site_id = $this->EE->config->item('site_id');
		
		$defaults = array(
			'show_picker' 			=> 'y',
			'show_view_page' 		=> 'y',
			'show_status' 			=> 'y',
			'show_page_type' 		=> 'y',
			'show_global_add_page' 	=> 'y',
			'redirect_on_login' 	=> 'n',
			'redirect_on_publish' 	=> 'n'
		);
		
		// Load Libraries and Helpers
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');
		
		// Set Breadcrumb and Page Title
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure', $this->EE->lang->line('structure_module_name'));
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('cp_module_settings_title'));
		
		$settings = $this->sql->get_settings();
		$groups = $this->sql->get_member_groups();
		
		// Check if we have admin permission
		$permissions = array();
		$permissions['admin'] = $this->structure->user_access('perm_admin_structure', $settings);
		$permissions['reorder'] = $this->structure->user_access('perm_reorder', $settings);
		$permissions['view_add_page'] = $this->structure->user_access('perm_view_add_page', $settings);
		$permissions['delete'] = $this->structure->user_access('perm_limited_delete', $settings);
		
		// Vars to send into view
		$vars = array();
		$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure'.AMP.'method=module_settings_submit';
		$vars['attributes'] = array('class' => 'form', 'id' => 'module_settings');
		$vars['groups'] = $groups;
		$vars['perms'] = $this->perms;
		$vars['settings'] = $settings;
		$vars['permissions'] = $permissions;
		$vars['extension_is_installed'] = $this->sql->extension_is_installed();
		
		// Check to make sure all settings have a value
		foreach ($defaults as $key => $default)
		{
			if ( ! isset($vars['settings'][$key]))
			{
				$vars['settings'][$key] = $default;
			}
		}
			
		return $this->EE->load->view('module_settings', $vars, TRUE);
	}
	
	// Process form data from the channel settings area
	function channel_settings_submit()
	{		
		if ($this->EE->input->get_post('submit'))
		{		
			$site_id = $this->EE->config->item('site_id');
			
			$working_data = ($_POST);
			unset($working_data['submit']);
			
			$form_data = array();
			foreach ($working_data as $key => $value)
			{		
				$form_data[] = array('site_id' => $site_id, 'channel_id' => $key, 'type' => $value[0], 'template_id' => $value[1]);
			}
			
			// Cleanse the DB
			$this->EE->db->query("DELETE FROM exp_structure_channels WHERE site_id = $site_id");
			
			// Insert the shiney new data
			foreach($form_data as $row)
			{
				$this->EE->db->query($this->EE->db->insert_string("exp_structure_channels", $row));	
			}
			
			// get current channel settings out of DB
			$sql = "SELECT * FROM exp_structure_channels WHERE site_id = $site_id";
			$channel_result = $this->EE->db->query($sql);

			$old_channels = $channel_result->result_array();
			
			// If channel is updated to be 'unmanaged', remove all nodes in that channel
			foreach($old_channels as $channel)
			{
				if($channel['type'] == 'unmanaged')
				{
					// Call delete from Structure by weblog function
					$this->structure->delete_data_by_channel($channel['channel_id']);
				}
			}
			
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure');
		}
		else
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure');
		}
	}
	
	// Process form data from the module settings area
	function module_settings_submit()
	{		
		$site_id = $this->EE->config->item('site_id');
		
		// get current settings out of DB
		$sql = "SELECT * FROM exp_structure_settings WHERE site_id = $site_id";
		$settings_result = $this->EE->db->query($sql);
		
		$old_settings = $settings_result->result_array();
				
		$current_settings = array();
				
		foreach ($old_settings as $csetting)
		{
			$current_settings[$csetting['var']] = $csetting['var_value'];
		}
				
		// clense current settings out of DB
		$sql = "DELETE FROM exp_structure_settings WHERE site_id = $site_id";
		$this->EE->db->query($sql);
				
		// insert settings into DB
		foreach ($_POST as $key => $value)
		{
			$value = strpos($key, 'perm_') === 0 ? 'y' : $value;
			if ($key !== 'submit')
			{
				// $key = $DB->escape_str($key);
				$this->EE->db->query($this->EE->db->insert_string(
					"exp_structure_settings", 
					array(
						'var'       => $key,
						'var_value' => $value, 
						'site_id'   => $site_id
					)
				));
			}
		}
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure');
	}
	
	
	function delete()
	{ 
	    $ids = $this->EE->input->get_post('toggle');
	
	    $this->structure->delete_data($ids);
	
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure');
	}
	
	
	/**
	 * Retrieve site path
	 */
	function get_site_path()
	{
		// extract path info
		$site_url_path = parse_url($this->EE->functions->fetch_site_index(), PHP_URL_PATH);

		$path_parts = pathinfo($site_url_path);
		$site_path = $path_parts['dirname'];

		$site_path = str_replace("\\", "/", $site_path);

		return $site_path;
	}
	
	
	/**
	 * Temporary debug page to fix some bugs
	 **/
	function debug()
	{
		$vars = array();
		
		// Set Breadcrumb and Page Title
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure', $this->EE->lang->line('structure_module_name'));
		$this->EE->cp->set_variable('cp_page_title', 'Debug');
		$duplicates = $this->sql->cleanup_check();
		
		$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure'.AMP.'method=debug_submit';
		$vars['attributes'] = array('class' => 'form', 'id' => 'debug');
		
		$vars['duplicate_entries'] = $duplicates['duplicate_entries'];
		$vars['duplicate_rights'] = $duplicates['duplicate_rights'];
		$vars['duplicate_lefts'] = $duplicates['duplicate_lefts'];
		
		return $this->EE->load->view('debug', $vars, TRUE);
	}
	
	// Process form data from the module settings area
	function debug_submit()
	{
		$this->sql->cleanup();
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure'.AMP.'method=debug');
	}
	
	

}
/* END Class */

/* End of file mcp.structure.php */
/* Location: ./system/expressionengine/third_party/structure/mcp.structure.php */ 