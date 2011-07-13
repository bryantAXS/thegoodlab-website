<?php if (! defined('BASEPATH')) die('No direct script access allowed');

if (! defined('PATH_THIRD')) define('PATH_THIRD', EE_APPPATH.'third_party/');
require_once PATH_THIRD.'assets/config.php';


/**
 * Assets Update
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Assets_upd {

	var $version = ASSETS_VER;

	/**
	 * Constructor
	 */
	function __construct($switch = TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	/**
	 * Install
	 */
	function install()
	{
		$this->EE->load->dbforge();

		// add row to modules
		$this->EE->db->insert('modules', array(
			'module_name'        => ASSETS_NAME,
			'module_version'     => ASSETS_VER,
			'has_cp_backend'     => 'y',
			'has_publish_fields' => 'n'
		));

		// File Manager actions
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_subfolders'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'upload_file'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_files_view_by_folders'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_props'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'save_props'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_ordered_files_view'));

		// Folder/file CRUD actions
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'move_folder'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'create_folder'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'delete_folder'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'view_file'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'move_file'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'delete_file'));

		// Field actions
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'build_sheet'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_selected_files'));

		// create assets table

		$fields = array(
			'asset_id'   => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'file_path'  => array('type' => 'varchar', 'constraint' => 255),
			'title'      => array('type' => 'varchar', 'constraint' => 100),
			'date'       => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
			'alt_text'   => array('type' => 'tinytext'),
			'caption'    => array('type' => 'tinytext'),
			'author'     => array('type' => 'tinytext'),
			'desc'       => array('type' => 'text'),
			'location'   => array('type' => 'tinytext'),
			'keywords'   => array('type' => 'text')
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('asset_id', TRUE);
		$this->EE->dbforge->add_key('file_path');
		$this->EE->dbforge->create_table('assets');

		// create assets_entries table

		$fields = array(
			'asset_id'    => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
			'entry_id'    => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
			'field_id'    => array('type' => 'int', 'constraint' => 6, 'unsigned' => TRUE),
			'col_id'      => array('type' => 'int', 'constraint' => 6, 'unsigned' => TRUE),
			'row_id'      => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
			'asset_order' => array('type' => 'int', 'constraint' => 4, 'unsigned' => TRUE)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('asset_id');
		$this->EE->dbforge->add_key('entry_id');
		$this->EE->dbforge->add_key('field_id');
		$this->EE->dbforge->add_key('col_id');
		$this->EE->dbforge->add_key('row_id');
		$this->EE->dbforge->create_table('assets_entries');

		return TRUE;
	}

	/**
	 * Update
	 */
	function update($current = '')
	{
		if ($current == $this->version)
		{
			return FALSE;
		}

		if (version_compare($current, '0.2', '<'))
		{
			$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_subfolders'));
		}

		if (version_compare($current, '0.3', '<'))
		{
			$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'upload_file'));
		}

		if (version_compare($current, '0.4', '<'))
		{
			$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'move_folder'));
			$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'create_folder'));
			$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'delete_folder'));
			$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'move_file'));
			$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'delete_file'));
		}

		if (version_compare($current, '0.5', '<'))
		{
			$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'view_file'));
		}

		if (version_compare($current, '0.6', '<'))
		{
			// {filedir_x}/filename => {filedir_x}filename
			$this->EE->db->query('UPDATE exp_assets SET file_path = REPLACE(file_path, "}/", "}")');
		}

		if (version_compare($current, '0.7', '<'))
		{
			$this->EE->load->dbforge();

			// delete unused exp_assets columns
			$this->EE->dbforge->drop_column('assets', 'asset_kind');
			$this->EE->dbforge->drop_column('assets', 'file_dir');
			$this->EE->dbforge->drop_column('assets', 'file_name');
			$this->EE->dbforge->drop_column('assets', 'file_size');
			$this->EE->dbforge->drop_column('assets', 'sha1_hash');
			$this->EE->dbforge->drop_column('assets', 'img_width');
			$this->EE->dbforge->drop_column('assets', 'img_height');
			$this->EE->dbforge->drop_column('assets', 'date_added');
			$this->EE->dbforge->drop_column('assets', 'edit_date');

			// rename 'asset_date' to 'date', and move it after title
			$this->EE->db->query('ALTER TABLE exp_assets
			                      CHANGE COLUMN `asset_date` `date` INT(10) UNSIGNED NULL DEFAULT NULL  AFTER `title`');
		}

		if (version_compare($current, '0.8', '<'))
		{
			// build_file_manager => build_sheet
			$this->EE->db->where('method', 'build_file_manager')
			             ->update('actions', array('method' => 'build_sheet'));
		}

		if (version_compare($current, '1.0.1', '<'))
		{
			// tell EE about the fieldtype's global settings
			$this->EE->db->where('name', 'assets')
			             ->update('fieldtypes', array('has_global_settings' => 'y'));
		}

		return TRUE;
	}

	/**
	 * Uninstall
	 */
	function uninstall()
	{
		$this->EE->load->dbforge();

		// routine EE table cleanup

		$this->EE->db->select('module_id');
		$module_id = $this->EE->db->get_where('modules', array('module_name' => 'Assets'))->row('module_id');

		$this->EE->db->where('module_id', $module_id);
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Assets');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Assets');
		$this->EE->db->delete('actions');

		$this->EE->db->where('class', 'Assets_mcp');
		$this->EE->db->delete('actions');

		// drop Assets tables 
		$this->EE->dbforge->drop_table('assets');
		$this->EE->dbforge->drop_table('assets_entries');

		return TRUE;
	}

}
