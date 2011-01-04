<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Hidden Channels module by Crescendo (support@crescendo.net.nz)
 * 
 * Copyright (c) 2010 Crescendo Multimedia Ltd
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class Hidden_channels_acc
{
	var $name			= 'Hidden Channels';
	var $id				= 'hidden_channels';
	var $version		= '1.0';
	var $description	= 'Hide specific channels from the publish menu for all member groups. Configured via the Channel Management page.';
	var $sections		= array();
	var $config			= array(); // stores the hidden channel ids
	
	/**
	 * Constructor
	 */
	function Hidden_channels_acc()
	{
		$this->EE =& get_instance();
		$this->EE->lang->loadfile('hidden_channels');
		$this->config = $this->get_config();
	}
	
	/**
	 * Runs once when the accessory is installed
	 */
	function install()
	{
		// butcher exp_accessories and add settings column if it doesn't exist
		$this->EE->load->dbforge();
		if (!$this->EE->db->field_exists('settings', 'accessories'))
		{
			$this->EE->dbforge->add_column('accessories', array(
				'settings' => array('type' => 'text', 'null' => TRUE)
			));
		}
	}
	
	/**
	 * Set Sections is called every time a CP page is loaded
	 */
	function set_sections()
	{
		// language strings to use in javscript function
		$lang = array(
			'nav_content' => lang('nav_content'),
			'nav_publish' => lang('nav_publish'),
			'entry' => lang('entry')
		);
		
		// load our javascript package
		$this->EE->cp->load_package_js('hidden_channels');
		$this->EE->javascript->output('hiddenChannelsInit('.
				$this->EE->javascript->generate_json($lang, TRUE).','.
				$this->EE->javascript->generate_json($this->config, TRUE).');');
		
		// check if we are on the CP homepage
		if ($this->EE->input->get('C') == 'homepage')
		{
			$this->EE->javascript->output('hiddenChannelsHomepage('.
				$this->EE->javascript->generate_json($lang, TRUE).','.
				$this->EE->javascript->generate_json($this->config, TRUE).');');
		}
		
		// check if we are on the channel_management page
		if ($this->EE->input->get('C') == 'admin_content' AND
			$this->EE->input->get('M') == 'channel_management')
		{
			// language strings to use in javascript function
			$lang = $this->get_icons_array();
			$lang['hidden'] = lang('hidden');
			$lang['delete'] = lang('delete');
			$lang['hidden_channels_note'] = lang('hidden_channels_note');
			
			$this->EE->javascript->output('hiddenChannelsManagement('.
				$this->EE->javascript->generate_json($lang, TRUE).','.
				$this->EE->javascript->generate_json($this->config, TRUE).');');
		}
		
		$this->EE->javascript->compile();
	}
	
	/**
	 * Ajax function to toggle a channel between hidden and visible
	 * Returns a new hide/show icon
	 */
	function process_channel_toggle()
	{
		// check we have a valid channel_id
		$channel_id = (int)$this->EE->input->get('channel_id');
		if ($channel_id == 0)
		{
			echo lang('invalid_channel_id');
			exit;
		}
		
		$channel_icons = $this->get_icons_array();
		
		// is the channel already hidden?
		if (in_array($channel_id, $this->config))
		{
			// remove the hidden channel
			$this->set_config(array_diff($this->config, array($channel_id)));
			echo $channel_icons['ICON_CHANNEL_HIDE'];
		}
		else
		{
			// add the hidden channel
			$this->config[] = $channel_id;
			$this->set_config($this->config);
			echo $channel_icons['ICON_CHANNEL_SHOW'];
		}
		
		exit;
	}
	
	/**
	 * Fetch an array with hidden channel icons html
	 * Only need this on the channel management page
	 */
	function get_icons_array()
	{
		return array(
				'ICON_CHANNEL_LINK' => htmlspecialchars_decode(BASE).'&C=addons_accessories&M=process_request&accessory=hidden_channels&method=process_channel_toggle&channel_id=',
				'ICON_CHANNEL_HIDE' => '<img src="'.PATH_CP_GBL_IMG.'static_icon.png" alt="'.lang('hide_channel').'" title="'.lang('hide_channel').'" />',
				'ICON_CHANNEL_SHOW' => '<img src="'.PATH_CP_GBL_IMG.'static_icon_hidden.png" alt="'.lang('show_channel').'" title="'.lang('show_channel').'" />',
				'ICON_CHANNEL_PROGRESS' => '<img src="'.PATH_CP_GBL_IMG.'indicator.gif" alt="'.lang('processing').'" title="'.lang('processing').'" />'
		);
	}
	
	/**
	 * Get the accessory config from database
	 */
	function get_config()
	{
		$this->EE->db->where('class', 'Hidden_channels_acc');
		$query = $this->EE->db->get('accessories')->row_array();
		if (empty($query) OR empty($query['settings'])) return array();
		else return unserialize($query['settings']);
	}
	
	/**
	 * Store the accessory config in database
	 */
	function set_config($config)
	{
		$config = array_values($config);
		
		$this->EE->db->where('class', 'Hidden_channels_acc');
		$this->EE->db->update('accessories', array('settings' => serialize($config)));
		$this->config = $config;
	}
}

/* End of file acc.hidden_channels.php */