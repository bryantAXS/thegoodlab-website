<?php if (! defined('BASEPATH')) die('No direct script access allowed');


// load dependencies
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
		// -------------------------------------------
		//  Make a local reference to the EE super object
		// -------------------------------------------

		$this->EE =& get_instance();
	}

	/**
	 * Install
	 */
	function install()
	{
		$this->EE->load->dbforge();

		// -------------------------------------------
		//  Add row to exp_modules
		// -------------------------------------------

		$this->EE->db->insert('modules', array(
			'module_name'        => ASSETS_NAME,
			'module_version'     => ASSETS_VER,
			'has_cp_backend'     => 'y',
			'has_publish_fields' => 'n'
		));

		// -------------------------------------------
		//  Add rows to exp_actions
		// -------------------------------------------

		// file manager actions
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_subfolders'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'upload_file'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_files_view_by_folders'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_props'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'save_props'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_ordered_files_view'));

		// folder/file CRUD actions
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'move_folder'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'create_folder'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'delete_folder'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'view_file'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'move_file'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'delete_file'));

		// field actions
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'build_sheet'));
		$this->EE->db->insert('actions', array('class' => 'Assets_mcp', 'method' => 'get_selected_files'));

		// -------------------------------------------
		//  Create the exp_assets table
		// -------------------------------------------

		$fields = array(
			'asset_id'   => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'file_path'  => array('type' => 'varchar', 'constraint' => 255),
			'title'      => array('type' => 'varchar', 'constraint' => 100),
			'date'       => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
			'alt_text'   => array('type' => 'tinytext'),
			'caption'    => array('type' => 'tinytext'),
			'author'     => array('type' => 'tinytext'),
			'`desc`'       => array('type' => 'text'),
			'location'   => array('type' => 'tinytext'),
			'keywords'   => array('type' => 'text')
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('asset_id', TRUE);
		$this->EE->dbforge->create_table('assets');

		$this->EE->db->query('ALTER TABLE exp_assets ADD UNIQUE unq_file_path (file_path)');

		// -------------------------------------------
		//  Create the exp_assets_entries table
		// -------------------------------------------

		$fields = array(
			'asset_id'    => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
			'entry_id'    => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
			'field_id'    => array('type' => 'int', 'constraint' => 6, 'unsigned' => TRUE),
			'col_id'      => array('type' => 'int', 'constraint' => 6, 'unsigned' => TRUE),
			'row_id'      => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
			'var_id'      => array('type' => 'int', 'constraint' => 6, 'unsigned' => TRUE),
			'asset_order' => array('type' => 'int', 'constraint' => 4, 'unsigned' => TRUE)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('asset_id');
		$this->EE->dbforge->add_key('entry_id');
		$this->EE->dbforge->add_key('field_id');
		$this->EE->dbforge->add_key('col_id');
		$this->EE->dbforge->add_key('row_id');
		$this->EE->dbforge->add_key('var_id');
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

		// -------------------------------------------
		//  Schema changes
		// -------------------------------------------

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

		if (version_compare($current, '1.1.5', '<'))
		{
			$this->EE->load->dbforge();

			// do we need to add the var_id column to exp_assets_entries?
			//  - the 1.1 update might have added this but then failed on another step, so the version wouldn't be updated
			$query = $this->EE->db->query('SHOW COLUMNS FROM `'.$this->EE->db->dbprefix.'assets_entries` LIKE "var_id"');
			if (! $query->num_rows())
			{
				$this->EE->db->query('ALTER TABLE exp_assets_entries ADD var_id INT(6) UNSIGNED AFTER row_id, ADD INDEX (var_id)');
			}
			else
			{
				// do we need to add its index?
				$query = $this->EE->db->query('SHOW INDEX FROM exp_assets_entries WHERE Key_name = "var_id"');
				if (! $query->num_rows())
				{
					$this->EE->db->query('ALTER TABLE exp_assets_entries ADD INDEX (var_id)');
				}
			}

			// do we need to add the unq_file_path index to exp_assets?
			//  - the 1.1 update used to attempt to add this, but it would fail if there was a duplicate file_path
			$query = $this->EE->db->query('SHOW INDEX FROM exp_assets WHERE Key_name = "unq_file_path"');
			if (! $query->num_rows())
			{
				// are there any duplicate file_path's?
				$query = $this->EE->db->query('
					SELECT a.asset_id, a.file_path FROM exp_assets a
					INNER JOIN (
						SELECT file_path FROM exp_assets
						GROUP BY file_path HAVING count(asset_id) > 1
					) dup ON a.file_path = dup.file_path');

				if ($query->num_rows())
				{
					$duplicates = array();
					foreach ($query->result() as $asset)
					{
						$duplicates[$asset->file_path][] = $asset->asset_id;
					}

					foreach ($duplicates as $file_path => $asset_ids)
					{
						$first_asset_id = array_shift($asset_ids);

						// point any entries that were using the duplicate IDs over to the first one
						$this->EE->db->where_in('asset_id', $asset_ids)
						             ->update('assets_entries', array('asset_id' => $first_asset_id));

						// delete the duplicates in exp_assets
						$this->EE->db->where_in('asset_id', $asset_ids)
						             ->delete('assets');
					}
				}

				// now that there are no more unique file_path's, add the unique index,
				// and drop the old file_path index, since that would be redundant
				$this->EE->db->query('ALTER TABLE exp_assets ADD UNIQUE unq_file_path (file_path), DROP INDEX file_path');
			}
		}

		// -------------------------------------------
		//  Update version number in exp_fieldtypes and exp_extensions
		// -------------------------------------------

		$this->EE->db->where('name', 'assets')
		             ->update('fieldtypes', array('version' => ASSETS_VER));

		$this->EE->db->where('class', 'Assets_ext')
		             ->update('extensions', array('version' => ASSETS_VER));

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
