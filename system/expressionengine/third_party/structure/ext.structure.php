<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Extension for Structure
 *
 * This file must be in your /system/third_party/structure directory of your ExpressionEngine installation
 *
 * @package             Structure for EE2
 * @author              Jack McDade (jack@jackmcdade.com)
 * @author              Travis Schmeisser (travis@rockthenroll.com)
 * @copyright			Copyright (c) 2010 Travis Schmeisser
 * @link                http://buildwithstructure.com
 */

/**
 * Include Structure SQL Model
 */
require_once PATH_THIRD.'structure/sql.structure.php';

/**
 * Include Structure Core Mod
 */
require_once PATH_THIRD.'structure/mod.structure.php';

class Structure_ext {
	
	var $name			= 'Structure';
	var $version 		= '2.2.4';
	var $description	= 'Enable some nice Structure-friendly control panel features';
	var $settings_exist	= 'n';
	var $docs_url		= 'http://buildwithstructure.com/documentation';
	var $settings 		= array();
	

	function structure_ext($settings = '')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
		
		$this->sql = new Sql_structure();
		
		if ($this->sql->module_is_installed() == TRUE)
		{
			$this->structure_settings = $this->sql->get_settings();	
		}
	}
	
	
	function entry_submission_redirect($entry_id, $meta, $data, $cp_call, $orig_loc)
	{

		if ($cp_call === TRUE && isset($this->structure_settings['redirect_on_publish']) && $this->structure_settings['redirect_on_publish'] == 'y')
		{
			return BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure';
		}
		else
		{
			return $orig_loc;
		} 
	}
	
	
	function cp_member_login()
	{
		if (isset($this->structure_settings['redirect_on_login']) && $this->structure_settings['redirect_on_login'] == 'y')
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure');
		}
	}
	
	
	/**
	 * Activate Extension
	 * @return void
	 */
	function activate_extension()
	{		
		$hooks = array(
			'entry_submission_redirect' 	=> 'entry_submission_redirect',
			'cp_member_login'				=> 'cp_member_login',
			);
			
		foreach ($hooks as $hook => $method)
		{
			$data = array(
				'class'		=> __CLASS__,
				'method'	=> $hook,
				'hook'		=> $method,
				'settings'	=> '',
				'priority'	=> 10,
				'version'	=> $this->version,
				'enabled'	=> 'y'
				);

			$this->EE->db->insert('extensions', $data);
		}
		
	}
		
		
	/**
	 * Disable Extension
	 * @return void
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}
	
	
	/**
	 * Update Extension
	 * @return 	mixed	void on update / false if none
	 */
	function update_extension($current = '')
	{
		return FALSE;
	}
	
}