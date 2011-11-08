<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_settings Model
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_settings extends CI_Model {



	var $settings;
	var $name_name;
	var $class_name;
	var $site_id;


	function Ep_settings()
	{
		parent::__construct();
	}



	function get_settings()
	{
		// Check that the settings table exists
		if($this->db->table_exists('ep_settings'))
		{
			$this->db->where('class', $this->class_name);
			$this->db->where('site_id', $this->site_id);
			return $this->db->get('ep_settings',1,0)->row('settings');
		}
		return '';
	}



	function prep_settings()
	{
		$vars = array('settings' => array('channels' => array()));
		
		// Set up the options for the channel and group drop downs
		$tpl_options = $this->_opts_for_templates();
		
		$groups = $this->_get_member_groups();
		$group_options = array();
		foreach($groups as $g)
		{
			$group_options[$g['group_id']] = $g['group_title'];
		}
		
		$this->load->library('api');
		$this->api->instantiate('channel_structure');
		$channels = $this->api_channel_structure->get_channels($this->settings['site_id']);

		// Get the status group ID for the group we created and the ID of the 'Open' status
		$this->load->model('ep_statuses');

		// Pass in the status colour options in case we need to create a new status group for a new site
		$this->ep_statuses->status_color_closed = $this->settings['status_color_closed'];
		$this->ep_statuses->status_color_draft = $this->settings['status_color_draft'];
		$this->ep_statuses->status_color_submitted = $this->settings['status_color_submitted'];
		$this->ep_statuses->status_color_open = $this->settings['status_color_open'];

		$vars['settings']['status_group_id'] = $this->ep_statuses->get_status_group_id($this->name_name);
		$vars['settings']['open_status_id'] = $this->ep_statuses->get_open_status_id($vars['settings']['status_group_id']);
		$vars['settings']['any_channels'] = false;

		// Do we have any Channels?
		if ($channels != FALSE)
		{
			// Set the flag so the view knows we have some channels to list
			$vars['settings']['any_channels'] = true;
			
			// Loop through the Channels
			foreach($channels->result_array() as $c)
			{
				// Is this channel in our settings array? 
				// If not, it may have been added since we last updated our settings so needs to be added
				if(!isset($this->settings['channels']['id_'.$c['channel_id']]))
				{
					$this->settings['channels']['id_'.$c['channel_id']]["uses_workflow"] = "no";
					$this->settings['channels']['id_'.$c['channel_id']]["template"] = "";
					$this->settings['channels']['id_'.$c['channel_id']]["notification_group"] = "";
				}


				$radio_name = "channels[id_{$c['channel_id']}][uses_workflow]";
				$id = "channel_{$c['channel_id']}";
				$uses_workflow_radio = array();

				
				$is_disabled = true;
			
				foreach(array('yes','no') as $v)
				{
					$is_checked = false;

					if (isset($this->settings['channels']) && 
					isset($this->settings['channels']['id_'.$c['channel_id']]) &&
					strtolower($this->settings['channels']['id_'.$c['channel_id']]['uses_workflow']) == strtolower($v))
					{
						$is_checked = true;
						if($v == 'yes') $is_disabled = false;
					}

					$radio_opts = array('name' => $radio_name, 'id' => $id.'_'.$v, 'value' => $v, 'checked' => $is_checked, 'class' => 'bwf_check_existing_channel_entries');
					$uses_workflow_radio[] = form_radio($radio_opts).'&nbsp;'.form_label(ucfirst($v), $id.'_'.$v).'&nbsp;&nbsp;&nbsp;&nbsp;';
				}
				
				// Append the extra param to the select elements
				$extra_params = ($is_disabled) ? 'disabled="disabled" class="bwf_hide"' : '';
				
				// Template processing
				$selected_template = (isset($this->settings['channels']['id_'.$c['channel_id']]['template']) && $this->settings['channels']['id_'.$c['channel_id']]['template']) ? 
				$this->settings['channels']['id_'.$c['channel_id']]['template'] : NULL;
				$template_view_data = form_dropdown("channels[id_{$c['channel_id']}][template]", $tpl_options, $selected_template, $extra_params." id=\"channel_{$c['channel_id']}_tdd\"");
				
				// Do we have any templates?
				$vars['settings']['any_templates'] = count($tpl_options);
				
				// Member group processing
				$selected_group = (isset($this->settings['channels']['id_'.$c['channel_id']]['notification_group']) && $this->settings['channels']['id_'.$c['channel_id']]['notification_group']) ? 
				$this->settings['channels']['id_'.$c['channel_id']]['notification_group'] : NULL;
				$groups_view_data = form_dropdown("channels[id_{$c['channel_id']}][notification_group]", $group_options, $selected_group, $extra_params." id=\"channel_{$c['channel_id']}_ndd\"");
				
				// Pass the vars to the settings view
				$vars['settings']['channels'][] = array(
					'name' => $c['channel_name'],
					'title' => $c['channel_title'],
					'uses_workflow' => implode("\n",$uses_workflow_radio),
					'template' => $template_view_data,
					'notification_group' => $groups_view_data 
				);

			}
		}

		// Loop through the Member groups
		$vars['settings']['groups'] = array();
		foreach($groups as $g)
		{
			$role_radio = array();
				$is_checked = false;
				foreach(array('Editor','Publisher') as $v)
				{
					$id = "bwf_role_{$g['group_id']}_$v";
					$radio_opts = array('name' => "groups[id_{$g['group_id']}][role]", 'id' => $id, 'value' => ucfirst($v));

					if (isset($this->settings['groups'])  && isset($this->settings['groups']['id_'.$g['group_id']]) &&
					$this->settings['groups']['id_'.$g['group_id']]['role'] == $v) {
						$radio_opts['checked'] = 'checked';
						$is_checked = true;
					} else {
						if ( !$is_checked && $g['group_id'] == 1 && $v == 'Publisher') {
						$radio_opts['checked'] = 'checked';
					} 
					elseif ($v == 'Editor') {
						$radio_opts['checked'] = 'checked';
					}
				}
				$role_radio[] = form_radio($radio_opts).'&nbsp;'.form_label($v, $id).'&nbsp;&nbsp;&nbsp;&nbsp;';
			}

			$vars['settings']['groups'][]=array(
				'name' => $g['group_title'],
				'bwf_role' => implode("\n",$role_radio), 
			);
		}

		// Advanced settings
		$vars['settings']['advanced'] = array();
		
		$log_events = (isset($this->settings['advanced']['log_events'])) ? $this->settings['advanced']['log_events'] : 'no';
		$log_radio = array();
		foreach(array('yes','no') as $v)
		{
			$log_radio_opts = array('name' => 'advanced[log_events]', 'id' => 'log_events_'.$v, 'value' => $v);
			if($v == 'yes' && $log_events == 'yes') $log_radio_opts['checked'] = 'checked';
			if($v == 'no' && $log_events == 'no') $log_radio_opts['checked'] = 'checked';
			$log_radio[] = form_radio($log_radio_opts).'&nbsp;'.form_label(ucfirst($v),'log_events_'.$v).'&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		
		$vars['settings']['advanced'][]=array(
			'name' => 'Log events',
			'radio' => implode("\n",$log_radio) 
		);

		// Ajax setting for existing entries check
		$vars['settings']['ajax_url'] = str_replace(AMP, '&', BASE) .'&C=addons_extensions&M=extension_settings&file=ep_better_workflow';
		
		return $vars;
	}



	function save_settings($data)
	{
		$status_group_id = $data['status_group_id'];
		$open_status_id = $data['open_status_id'];
		$bwf_channels = array();

		// Update the status group for all channels which have been selected as 'Use workflow'
		// The action will also update the status of any entry in that channel which has a non-workflow status
		foreach($data['channels'] as $key => $value)
		{
			$channel_id = substr($key, 3, strlen($key));

			// If this channel has been assigned to Workflow make sure it uses the Workflow status group 
			if($value['uses_workflow'] == 'yes')
			{
				// Do we need to do some status re-mappinhg for this channel
				if(isset($value['existing_statuses']))
				{
					foreach($value['existing_statuses'] as $value)
					{
						$old_status = $value['old_status'];
						$new_status = $value['new_status'];
						$this->_update_status_of_existing_entries($old_status, $new_status, $channel_id);
					}
					
					// Remove the old status data, its too late to do anything with this anyway
					unset($data['channels']['existing_statuses']);
				}

				$this->_update_channel_status_group($channel_id, $status_group_id);
				$bwf_channels[] = $channel_id;
			}
		}

		// Update the status access so that all member groups set to editors cannot access the open status
		// Make sure to unset status access for groups which are promoted from editors to publishers
		foreach($data['groups'] as $key => $value)
		{
			$group_id = substr($key, 3, strlen($key));
			$can_publish = false;
			foreach($value as $v)
			{
				if ($v == 'Publisher') $can_publish = true;
			}
			$this->_update_group_status_access($group_id, $open_status_id, $can_publish);
		}

		// Load the form data into an array and save to the extension settings
		$settings = array('channels' => $data['channels'] , 'bwf_channels' => $bwf_channels, 'groups' => $data['groups'], 'advanced' => $data['advanced']);
		
		// Update the ep_settings table for this site (MSM Compatibility)
		$this->_update_settings_table($settings);

		// Set the flash message
		$this->session->set_flashdata(
			'message_success',
			$this->lang->line('preferences_updated')
		);
	}



	// -----------------------------------------------------------------------------------------------------------
	// PRIVATE FUNCTIONS
	// -----------------------------------------------------------------------------------------------------------

	private function _update_channel_status_group($channel_id, $status_group_id)
	{
		// Update the Status Group and default Status of the Channel
		$this->db->where('channel_id', $channel_id);
		$this->db->update('channels', array('status_group' => $status_group_id, 'deft_status' => $this->settings['bwf_default_status']));
	}



	private function _update_group_status_access($group_id, $open_status_id, $can_publish)
	{
		// Check to see if we already have a record for this group and status
		$this->db->select('status_id');
		$this->db->where(array('status_id' => $open_status_id, 'member_group' => $group_id));
		$is_set = $this->db->get('status_no_access');
		
		// If we *don't* have a record
		if ($is_set->num_rows() == 0)
		{
			// If the user *can't* publish...
			if(!$can_publish) $this->db->insert('status_no_access', array('status_id' => $open_status_id, 'member_group' => $group_id));
		}
		else
		{
			// If the user *can* publish...
			if($can_publish) $this->db->delete('status_no_access', array('status_id' => $open_status_id, 'member_group' => $group_id));
		}
	}



	// Update the Status of all Entries in this Channel which are not using a Workflow Status
	private function _update_status_of_existing_entries($old_status, $new_status, $channel_id)
	{
		$this->db->where('channel_id', $channel_id);
		$this->db->where('status', $old_status);
		$this->db->update('channel_titles', array('status' => $new_status));
	}



	private function _opts_for_templates()
	{
		$opts = array();
		$hidden_indicator = ($this->config->item('hidden_template_indicator') != '') ? $this->config->item('hidden_template_indicator') : '.';

		$sql = "SELECT exp_templates.template_id, exp_templates.template_name, exp_template_groups.group_name 
		FROM exp_templates 
		LEFT JOIN exp_template_groups ON exp_templates.group_id = exp_template_groups.group_id 
		WHERE (exp_templates.site_id = ".$this->settings['site_id']." and exp_templates.template_type = 'webpage') 
		ORDER BY exp_template_groups.group_name, exp_templates.template_name ASC;";
		$results = $this->db->query($sql);

		foreach ($results->result_array() as $row)
		{
			// Don't include any templates which start with a the Hidden Template Indicator
			if( substr($row['group_name'], 0, 1) == $hidden_indicator || substr($row['template_name'], 0, 1) == $hidden_indicator )
			{
				// Do nothing, just easier than the reverse logic
			}
			else
			{
				$t = $row['group_name'] . '/' . $row['template_name'];
				$opts[$t] = $t;
			}
		}
		return $opts; 
	}



	private function _get_member_groups()
	{		
		// Do not return banned/guests/pending
		$sql = "SELECT * FROM exp_member_groups WHERE site_id = ".$this->settings['site_id']." AND can_access_cp = 'y' AND group_title NOT IN ('Banned','Guests','Pending');";
		$results = $this->db->query($sql);
		$groups = array();
		foreach ($results->result_array() as $row) {$groups[]=$row;}
		return $groups; 
	}



	private function _update_settings_table($settings)
	{
		// Check to see if we already have some settings for this site
		$site_settings = $this->db->get_where('ep_settings', array('site_id' => $this->site_id));

		// If we *don't* have any settings create a record
		if ($site_settings->num_rows() == 0)
		{
			$data = array(
				'class' => $this->class_name,
				'site_id' => $this->site_id,
				'settings' => serialize($settings)
			);
			$this->db->insert('ep_settings', $data);
		}
		
		// If we do, update them
		else
		{	
			$this->db->where('class', $this->class_name);
			$this->db->where('site_id', $this->site_id);
			$this->db->update('ep_settings', array('settings' => serialize($settings)));
		}
	}


}