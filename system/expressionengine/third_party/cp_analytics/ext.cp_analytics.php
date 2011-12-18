<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
    This file is part of CP Analytics add-on for ExpressionEngine.

    CP Analytics is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CP Analytics is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Read the terms of the GNU General Public License
    at <http://www.gnu.org/licenses/>.
    
    Copyright 2011 Derek Hogue
*/

class Cp_analytics_ext
{
	var $settings			= array();
	var $name				= 'CP Analytics Settings';
	var $version			= '1.2.2';
	var $description		= 'Google account settings for the CP Analytics accessory.';
	var $settings_exist		= 'y';
	var $docs_url			= 'http://github.com/amphibian/cp_analytics.ee_addon';
	var $slug				= 'cp_analytics';
	var $token				= '';
	var $debug				= FALSE;


	function Cp_analytics_ext($settings='')
	{
	    $this->settings = $settings;
	    $this->EE =& get_instance();
	}

	
	function settings_form($current)
	{	    		
		// Initialize our variable array
		$vars = array();
		
		// Get current site	
		$site = $this->EE->config->item('site_id');

		// Only grab settings for the current site
		$vars['current'] = (isset($current[$site])) ? $current[$site] : $current;
				
		// We need our file name for the settings form
		$vars['file'] = $this->slug;
		
		// AuthSub authentication destination
		
		// Oh edge cases, how you burn my balls.
		if(substr($this->EE->config->item('cp_url'), -4, 4) == '.php')
		{
			$base = $this->EE->config->item('cp_url');
		}
		else
		{
			$base = rtrim($this->EE->config->item('cp_url'), '/').'/index.php';
		}
		
		$next = $base.'?S='.$this->EE->input->get('S').'&D=cp&C=addons_extensions&M=extension_settings&file='.$this->slug.'&authsub=y';
		$scope = 'https://www.google.com/analytics/feeds/';
		$vars['authsub_url'] = 'https://www.google.com/accounts/AuthSubRequest?next='.urlencode($next).'&scope='.urlencode($scope).'&secure=0&session=1';		
		
		// Include our gapi class
		require_once(PATH_THIRD.'cp_analytics/libraries/gapi.class.php');				
		
		// This removes the current authentication
		if(isset($_GET['reset']))
		{
			if(isset($vars['current']['token']))
			{
				$ga = new gapi($vars['current']['token']);
				$ga->deauthorizeSessionToken();
			}
			$settings = $this->get_settings(TRUE);
			$settings[$site]['token'] = '';
			$settings[$site]['profile'] = '';

			$this->EE->db->where('class', ucfirst(get_class($this)));
			$this->EE->db->update('extensions', array('settings' => serialize($settings)));
			$this->EE->functions->redirect(
				BASE.AMP.'C=addons_extensions'.
				AMP.'M=extension_settings'.
				AMP.'file='.$this->slug
			);
			exit;	
		}
		
		// Are we authenticating right now?
		if(isset($_GET['authsub']) && isset($_GET['token']))
		{
			$ga = new gapi($_GET['token']);
			$token = $ga->getSessionToken();
			if(strpos($token, 'Error:') === FALSE)
			{
				$vars['current']['token'] = $token;
				$this->save_settings($token);
			}
			else
			{
				$vars['connection_error'] = $token;
			}
		}
		
		// If we're authenticated, try and authenticate and fetch our profile list
		if(isset($vars['current']['token']))
		{
			$ga = new gapi($vars['current']['token']);
			$ga->requestAccountData(1,1000);
			
			if($ga->getResults())
			{
				$vars['ga_profiles'] = array('' => '--');
				foreach($ga->getResults() as $result)
				{
				  $vars['ga_profiles'][$result->getProfileId()] = $result->getTitle();
				}
				asort($vars['ga_profiles']);
			}
		}
		
		// Compile settings
		$vars['radio_settings'] = array(
			'homepage_chart',
			'homepage_chart_labels',
			'cache_sparklines'
		);
		$vars['text_settings'] = array(
			'cache_path',
			'cache_url'
		);
		
		if($this->debug == TRUE) $vars['debug'] = TRUE;
		
		// We have our vars set, so load and return the view file
		return $this->EE->load->view('settings', $vars, TRUE);
	}
	
	
	function save_settings($token = null)
	{
		// Get all settings
		$settings = $this->get_settings(TRUE);

		// Get current site	
		$site = $this->EE->config->item('site_id');
				
		// print_r($settings); exit;
						
		// if we've passed a token
		if(isset($token))
		{
			require_once(PATH_THIRD.'cp_analytics/libraries/gapi.class.php');				
			$ga = new gapi($token);
			if($ga->getAuthToken() != FALSE)
			{
				$settings[$site]['token'] = $token;
			}
		}
		
		if($profile = $this->EE->input->post('profile'))
		{
			$settings[$site]['profile'] = $profile;			
			$settings[$site]['homepage_chart'] = $this->EE->input->post('homepage_chart');
			$settings[$site]['homepage_chart_labels'] = $this->EE->input->post('homepage_chart_labels');
			$settings[$site]['cache_sparklines'] = $this->EE->input->post('cache_sparklines');
			$settings[$site]['cache_path'] = $this->EE->input->post('cache_path');
			$settings[$site]['cache_url'] = $this->EE->input->post('cache_url');
			$settings[$site]['hourly_cache'] = '';
			$settings[$site]['daily_cache'] = '';
			// Clean up after legacy settings
			if(isset($settings[$site]['user'])) unset($settings[$site]['user']);
			if(isset($settings[$site]['password'])) unset($settings[$site]['password']);
			if(isset($settings[$site]['authenticated'])) unset($settings[$site]['authenticated']);
			ksort($settings[$site]);
		}
			
		$this->EE->db->where('class', ucfirst(get_class($this)));
		$this->EE->db->update('extensions', array('settings' => serialize($settings)));
		
		// If we passed the token, it means we're doing this silently
		if(isset($token))
		{
			return;
		}
		else
		{
			$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('preferences_updated'));
			
			$this->EE->functions->redirect(
					BASE.AMP.'C=addons_extensions'.
					AMP.'M=extension_settings'.
					AMP.'file='.$this->slug
				);
			exit;
		}
	}

	
	function get_settings($all_sites = FALSE)
	{
		$get_settings = $this->EE->db->query("SELECT settings 
			FROM exp_extensions 
			WHERE class = '".ucfirst(get_class($this))."' 
			LIMIT 1");
		
		$this->EE->load->helper('string');
		
		if ($get_settings->num_rows() > 0 && $get_settings->row('settings') != '')
        {
        	$settings = strip_slashes(unserialize($get_settings->row('settings')));
        	$settings = ($all_sites == TRUE) ? $settings : $settings[$this->EE->config->item('site_id')];
        }
        else
        {
        	$settings = array();
        }
        return $settings;
	}
	
		
	function activate_extension()
	{

	  $this->EE->db->query($this->EE->db->insert_string('exp_extensions',
	    	array(
		        'class'        => ucfirst(get_class($this)),
		        'method'       => '',
		        'hook'         => '',
		        'settings'     => '',
		        'priority'     => 10,
		        'version'      => $this->version,
		        'enabled'      => "y"
				)
			)
		);
	}


	function update_extension($current='')
	{
	    if ($current == '' OR $current == $this->version)
	    {
	        return FALSE;
	    }
	    
		$this->EE->db->query("UPDATE exp_extensions 
	     	SET version = '". $this->EE->db->escape_str($this->version)."' 
	     	WHERE class = '".ucfirst(get_class($this))."'");
	}

	
	function disable_extension()
	{	    
		$this->EE->db->query("DELETE FROM exp_extensions WHERE class = '".ucfirst(get_class($this))."'");
	}

}