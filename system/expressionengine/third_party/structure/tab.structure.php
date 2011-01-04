<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Include Structure SQL Model
 */
require_once PATH_THIRD.'structure/sql.structure.php';

/**
 * Include Structure Core Mod
 */
require_once PATH_THIRD.'structure/mod.structure.php';

class Structure_tab
{
	
	function Structure_tab()
	{
		$this->EE =& get_instance();	
		$this->sql = new Sql_structure();
	    $this->structure = new Structure();
	}
	
	
	function default_tab()
	{
		$settings[] = array(
			'field_id'				=> '',
			'field_label'			=> '',
			'field_required' 		=> 'n',
			'field_data'			=> '',
			'field_list_items'		=> '',
			'field_fmt'				=> '',
			'field_instructions' 	=> '',
			'field_show_fmt'		=> 'n',
			'field_fmt_options'		=> array(),
			'field_pre_populate'	=> 'n',
			'field_text_direction'	=> 'ltr',
			'field_type' 			=> 'text',
			'field_maxl'			=> '1'
		);
			
		return $settings;
	}
		
	
	function publish_tabs($channel_id, $entry_id = '')
	{
		$this->EE->load->helper('form');
		$this->EE->lang->loadfile('structure');
	
		$settings = array();
		$selected = array();
		$selected_parent = array();
		$existing_files = array();
		
		// Grab piles of data
		$structure_settings = $this->sql->get_settings();
		$site_pages = $this->structure->get_site_pages(); 
		
		$site_id = $this->EE->config->item('site_id');
		$entry_id = $entry_id ? $this->EE->input->get_post('entry_id') : '';
		
		$channel_id = $this->EE->input->get_post('channel_id');
		$channel_type = $this->structure->get_channel_type($channel_id);
		
		$data  = $this->sql->get_data();	
		$cids  = isset($data['channel_ids']) ? $data['channel_ids'] : array();
		$lcids = isset($data['listing_cids']) ? $data['listing_cids'] : array();		

		$structure_channels = $this->structure->get_structure_channels();
		$current_channel_type = $structure_channels[$channel_id]['type'];
		
		// Hide the Structure tab if channel is not managed by Structure
		if (($current_channel_type != 'page' && $current_channel_type != 'listing')	|| (isset($permissions['admin']) && $permissions['admin'] != TRUE))
		{
			return array();
		}
		
		$uri = '';
		$slug = '';
		$template_id = '';
		$parent_id = '';
		
		$listing = 0;
		$listing_cid = 0;
		
		// Get previously set data
		if ($entry_id && isset($site_pages['uris'][$entry_id]))
		{
			// Get page uri slug without parents
			$uri = $site_pages['uris'][$entry_id];
			$template_id = $site_pages['templates'][$entry_id];
			
			if ($channel_type == 'static')
			{
				$parent_id  = isset($data[$entry_id]['parent_id']) ? $data[$entry_id]['parent_id'] : 0;
				$parent_uri = $parent_id ? $site_pages['uris'][$parent_id] : '';

				if (isset($data[$entry_id]['listing_cid']) AND $data[$entry_id]['listing_cid'] != 0)
				{
					$listing = 1;
					$listing_cid = $data[$entry_id]['channel_id'];
				}
			}		
		}
		else
		{
			@$template_id = $template_id ? $template_id : @$structure_settings['template_channel_' . $channel_id];
		}
		
		// overide defaults and previous data with data from the form if available
		$uri         = $this->EE->input->get_post('structure__uri') ? $this->EE->input->get_post('structure__uri') : $uri;
		$listing     = $this->EE->input->get_post('structure__listing') ? $this->EE->input->get_post('structure__listing') : $listing;
		$listing_cid = $this->EE->input->get_post('structure__listing_channel') ? $this->EE->input->get_post('structure__listing_channel') : $listing_cid;
		
		// if there are no / then we have a root slug already, else get the end
		$slug = trim($uri, '/');
		if (strpos($slug, '/'))
		{
		    $slug = substr(strrchr($slug, '/'), 1);
		}

		// If current entry is root slash then display it to avoid it being auto-updated on edits.
		if ($uri == "/")
		{
			$slug = $uri;
		}	
		
		/** -------------------------------------
		/**  Field: Parent ID
		/** -------------------------------------*/
		
		$parent_id = $this->EE->input->get_post('parent_id');

		if ($parent_id == '0' && array_key_exists($entry_id, $data))
		{
			$parent_id = $data[$entry_id]['parent_id'];
		}

		$selected_parent[] = $parent_id;
		
		$parent_ids = $this->get_parent_fields($entry_id, $data);

		if (array_key_exists($channel_id, $this->structure->get_structure_channels('page')))
		{
			$settings[] = array(
				'field_id'				=> 'parent_id',
				'field_label'			=> lang('tab_parent_entry'),
				'field_required' 		=> 'n',
				'field_data'			=> $selected_parent,
				'field_list_items'		=> $parent_ids,
				'field_fmt'				=> '',
				'field_instructions' 	=> '',
				'field_show_fmt'		=> 'n',
				'field_fmt_options'		=> array(),
				'field_pre_populate'	=> 'n',
				'field_text_direction'	=> 'ltr',
				'field_type' 			=> 'select'
			);
		}

		/** -------------------------------------
		/**  Field: Page URI/Slug
		/** -------------------------------------*/
		
		@$selected_uri = $site_pages['uris'][$entry_id];
		
		$settings[] = array(
			'field_id'				=> 'uri',
			'field_label'			=> lang('tab_page_url'),
			'field_required' 		=> 'n',
			'field_data'			=> $slug,
			'field_list_items'		=> $uri,
			'field_fmt'				=> '',
			'field_instructions' 	=> '',
			'field_show_fmt'		=> 'n',
			'field_fmt_options'		=> array(),
			'field_pre_populate'	=> 'n',
			'field_text_direction'	=> 'ltr',
			'field_type' 			=> 'text',
			'field_maxl'			=> 150
		);
			
		/** -------------------------------------
		/**  Field: Template
		/** -------------------------------------*/
	
		$structure_channels = $this->structure->get_structure_channels('', $channel_id);
		
		if (isset($entry_id) AND $entry_id != 0)
		{
			@$selected_template[] = $site_pages['templates'][$entry_id];
		}
		else
		{
			$selected_template[] = $structure_channels[$channel_id]['template_id'];
		}
		
		$templates = $this->get_template_fields($entry_id, $data, $channel_id);
		
		$settings[] = array(
			'field_id'				=> 'template_id',
			'field_label'			=> lang('template'),
			'field_required' 		=> 'n',
			'field_data'			=> $selected_template,
			'field_list_items'		=> $templates,
			'field_fmt'				=> 'text',
			'field_instructions' 	=> '',
			'field_show_fmt'		=> 'n',
			'field_fmt_options'		=> '',
			'field_pre_populate'	=> 'n',
			'field_text_direction'	=> 'ltr',
			'field_type' 			=> 'select'
		);
		
		/** -------------------------------------
		/**  Field: Listing Channel
		/** -------------------------------------*/
					
		$listing_channel = @$data[$entry_id]['listing_cid'] ? @$data[$entry_id]['listing_cid'] : 0;
		$listing_channels = $this->get_listing_channels($entry_id, $data, $channel_id);
				
		$result = $this->EE->db->query("SELECT listing_cid FROM exp_structure WHERE listing_cid != 0");
		
		$used_listing_ids = array();
		foreach ($result->result_array() as $row)
		{
			$used_listing_ids[$row['listing_cid']] = $row['listing_cid'];
		}
		
		unset($used_listing_ids[$listing_channel]);

		$type =  $this->structure->get_structure_channels('', $channel_id);
		$listing_channels = array_diff_key($listing_channels, $used_listing_ids);

		if ( ! array_key_exists($channel_id, $used_listing_ids) || $type[$channel_id]['type'] != 'listing')
		{
			$settings[] = array(
				'field_id'				=> 'listing_channel',
				'field_label'			=> lang('listing_channel'),
				'field_required' 		=> 'n',
				'field_data'			=> $listing_channel,
				'field_list_items'		=> $listing_channels,
				'field_fmt'				=> '',
				'field_instructions' 	=> lang('ui_add_listing'),
				'field_show_fmt'		=> 'n',
				'field_fmt_options'		=> array(),
				'field_pre_populate'	=> 'n',
				'field_text_direction'	=> 'ltr',
				'field_type' 			=> 'select'
				);
		}
		
		return $settings;
	
	}
	
	
	function publish_data_delete_db($params)
	{
		$this->structure->delete_data($params['entry_ids']);
	}
	

	function validate_publish($params)
	{	
		return FALSE;
	}
	
	function publish_data_db($params)
	{	
		$this->EE->load->helper('url');

		$site_pages = $this->structure->get_site_pages();
		$data = array();

		// get form fields
		$channel_id = $params['meta']['channel_id'];
		
		$data['channel_id'] 	= $channel_id;
		$data['entry_id']   	= $params['entry_id'];
		$data['uri']        	= trim($this->EE->input->get_post('structure__uri')) ? trim($this->EE->input->get_post('structure__uri')) : $params['meta']['url_title'];
		$data['template_id']	= $this->EE->input->get_post('structure__template_id');
		$data['listing_cid'] 	= $this->EE->input->get_post('structure__listing_channel') ?  $this->EE->input->get_post('structure__listing_channel') : 0;
		
		$type = $this->structure->get_structure_channels('', $data['channel_id']);
		
		// If the current channel is not assigned as any sort of Structure channel, then stop
		if( ! isset($type[$channel_id]['type']))
		{
			return;
		}
		// If it's a listing channel
		else if($type[$channel_id]['type'] == 'listing')
		{
			$result = $this->EE->db->query("SELECT entry_id FROM exp_structure WHERE listing_cid = {$channel_id}");
			$data['parent_id'] = $result->row('entry_id') ? $result->row('entry_id') : 0;
		}
		// Must be a static page...
		else
		{
			$data['parent_id'] = $this->EE->input->get_post('structure__parent_id') ?  $this->EE->input->get_post('structure__parent_id') : 0;	
		}
		
		$url_title = $params['meta']['url_title'];
		$entry_title = $params['meta']['title'];
		$structure_parent_uri = isset($site_pages['uris'][$data['parent_id']]) ? $site_pages['uris'][$data['parent_id']] : '/';
		$data['uri'] = $this->structure->create_uri($data['uri'], $url_title);
		
		$is_listing = array_key_exists($data['channel_id'], $this->structure->get_structure_channels('listing'));
		$is_page = array_key_exists($data['channel_id'], $this->structure->get_structure_channels('page'));
		
		if ($is_listing)
		{
			// make sure we don't have slashes in the URI
			$data['uri'] = trim($data['uri'], "/");	
			// send the parent_uri
			$data['parent_uri'] = $structure_parent_uri;
			
			$this->structure->set_listing_data($data);
		}
		
		if ($is_page)
		{
			// Create our URI & update entry data
			$data['uri'] = $this->structure->create_page_uri($structure_parent_uri, $data['uri']);
			$this->structure->set_data($data);
		}
		
	}
	
	
	/** -------------------------------------
	/**  Utility functions
	/** -------------------------------------*/
	
	function get_template_fields($entry_id, $data, $channel_id)
	{
		$site_id = $this->EE->config->item('site_id');

		$template_id = isset($structure_settings['template_channel_' . $channel_id]) ? $structure_settings['template_channel_' . $channel_id] : 0;
		$template_id = $this->EE->input->get_post('structure__template_id') ? $this->EE->input->get_post('structure__template_id') : $template_id;

		$templates = $this->sql->get_templates();
		$options = array();
		
		foreach ($templates as $template_row)
		{
			$template_id = $template_row['template_id'];
			$template_group = $template_row['group_name'] . "/" . $template_row['template_name'];
			$options[$template_id] = $template_group;
		}
		
		return $options;
	}
	
	
	function get_parent_fields()
	{
				
		// Build Parent Entries Select Box
		$parent_id = $this->EE->input->get_post('structure__parent_id') ? $this->EE->input->get_post('structure__parent_id') : 0;
		$parent_ids = array();
		$parent_ids[0] = "NONE";
		
		$data = $this->sql->get_data();
		
		foreach ($data as $eid => $entry)
		{
			// Add faux indent with "--" double dashes
			$option  = str_repeat("--", $entry['depth']);
			$option .= $entry['title'];
			
			$parent_ids[$eid] = $option;
		}
		
		return $parent_ids;
	}
	
	
	function get_listing_channels($entry_id, $data, $channel_id)
	{
		
		$site_id = $this->EE->config->item('site_id');
		$structure_data = $this->sql->get_data();
	

		$listings = $this->structure->get_structure_channels('listing');
		$count_listings = count($listings);
		
		// Build Listing Channels Select Box
		$listing_channel = $this->EE->input->get_post('structure__listing_channel') ? $this->EE->input->get_post('structure__listing_channel') : 0;
		$listing_channels = array();
		$listing_channels[0] = "==None Selected==";
		
		if ($count_listings > 0)
		{
			foreach ($listings as $channel_id => $row)
			{
				$listing_channels[$channel_id] = $row['channel_title'];
			}
		}
		
		return $listing_channels;
	}
}
/* END Class */

/* End of file tab.structure.php */
/* Location: ./system/expressionengine/third_party/structure/tab.structure.php */