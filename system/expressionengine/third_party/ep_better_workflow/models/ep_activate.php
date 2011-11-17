<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_install Model
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_activate extends CI_Model {


	var $class_name;
	var $version;


	function Ep_activate()
	{
		parent::__construct();
	}



	function regsiter_hooks()
	{
		$hooks = array(
			'on_sessions_start' => 'sessions_start',
			'on_entry_submission_start' => 'entry_submission_start',
			'on_entry_submission_ready' => 'entry_submission_ready',
			'on_entry_submission_end' => 'entry_submission_end',
			'on_publish_form_entry_data' => 'publish_form_entry_data',
			'on_publish_form_channel_preferences' => 'publish_form_channel_preferences',
			'on_channel_entries_row' => 'channel_entries_row',	
			'on_channel_entries_query_result' => 'channel_entries_query_result',
			'on_matrix_data_query' => 'matrix_data_query',
			'on_playa_data_query' => 'playa_data_query',
			'on_cp_js_end' => 'cp_js_end'
		);

		foreach ($hooks as $method => $hook){
			$data = array(
				'class'		=> $this->class_name,
				'method'	=> $method,
				'hook'		=> $hook,
				'settings'	=> '',
				'priority'	=> 10,
				'version'	=> $this->version,
				'enabled'	=> 'y'
				);

			$this->db->insert('extensions', $data);
		}
	}



	function create_tables()
	{
		$this->load->dbforge();
		$ep_entry_drafts_fields = array(
			'ep_entry_drafts_id' => array(
				'type' => 'int',
				'constraint' => '10',
				'unsigned' => TRUE,
				'auto_increment' => TRUE,),
			'entry_id' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
			'author_id' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
			'channel_id' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
				'site_id' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
			'status' => array(
				'type' => 'varchar',
				'constraint' => '255',
				'null' => FALSE,),
			'url_title' => array(
				'type' => 'varchar',
				'constraint' => '255',
				'null' => FALSE,),
			'draft_data' => array(
				'type' => 'text',),
			'expiration_date' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
			'edit_date' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
			'entry_date' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
		);
		$this->dbforge->add_field($ep_entry_drafts_fields);
		$this->dbforge->add_key('ep_entry_drafts_id', TRUE);
		$this->dbforge->create_table('ep_entry_drafts');


		$ep_entry_drafts_thirdparty_fields = array(
			'entry_id' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
			'field_id' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
			'type' => array(
				'type' => 'varchar',
				'constraint' => '255',
				'null' => FALSE,),
			'row_id' => array(
				'type' => 'varchar',
				'constraint' => '25',
				'null' => FALSE,),
			'row_order' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
			'col_id' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
			'data' => array(
				'type' => 'text',),
		);
		$this->dbforge->add_field($ep_entry_drafts_thirdparty_fields);
		$this->dbforge->create_table('ep_entry_drafts_thirdparty');


		$ep_entry_drafts_auth_fields = array(
			'ep_auth_id' => array(
				'type' => 'int',
				'constraint' => '10',
				'unsigned' => TRUE,
				'auto_increment' => TRUE,),
			'token' => array(
				'type' => 'varchar',
				'constraint' => '255',
				'null' => FALSE,),
		);
		$this->dbforge->add_field($ep_entry_drafts_auth_fields);
		$this->dbforge->add_key('ep_auth_id', TRUE);
		$this->dbforge->create_table('ep_entry_drafts_auth');


		$ep_settings_fields = array(
			'site_id' => array(
				'type' => 'int',
				'constraint' => '10',
				'null' => FALSE,),
			'class' => array(
				'type' => 'varchar',
				'constraint' => '255',
				'null' => FALSE,),
			'settings' => array(
				'type' => 'text'),
		);
		$this->dbforge->add_field($ep_settings_fields);
		$this->dbforge->add_key('site_id', TRUE);
		$this->dbforge->create_table('ep_settings', TRUE);
	}



	function remove_bwf()
	{
		$this->db->where('class', $this->class_name);
		$this->db->delete('extensions');
		
		$this->load->dbforge();
		$this->dbforge->drop_table('ep_entry_drafts');
		$this->dbforge->drop_table('ep_entry_drafts_thirdparty');
		$this->dbforge->drop_table('ep_entry_drafts_auth');
		
		// Delete the settings from ep_settings
		// But do not drop the table as it may be being used by other ep Add-Ons
		$this->db->where('class', $this->class_name);
		$this->db->delete('ep_settings');
	}


}