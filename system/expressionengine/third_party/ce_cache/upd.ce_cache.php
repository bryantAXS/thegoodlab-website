<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CE Cache - Module Update File
 *
 * @author		Aaron Waldon
 * @copyright	Copyright (c) 2011 Causing Effect
 * @license		http://www.causingeffect.com/software/expressionengine/ce-cache/license-agreement
 * @link		http://www.causingeffect.com
 */


class Ce_cache_upd {

	public $version = '1.7.5';

	private $EE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	public function install()
	{
		//install the module
		$mod_data = array(
			'module_name'			=> 'Ce_cache',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> 'y',
			'has_publish_fields'	=> 'n'
		);
		$this->EE->db->insert( 'modules', $mod_data );

		//setup the tables
		$this->setup_tables();

		//add actions
		$this->EE->db->insert( 'actions', array( 'class' => 'Ce_cache', 'method' => 'break_cache' ) );
		$this->EE->db->insert( 'actions', array( 'class' => 'Ce_cache_mcp', 'method' => 'ajax_get_level' ) );
		$this->EE->db->insert( 'actions', array( 'class' => 'Ce_cache_mcp', 'method' => 'ajax_delete' ) );

		return TRUE;
	}

	/**
	 * Uninstall
	 *
	 * @return 	boolean 	TRUE
	 */
	public function uninstall()
	{
		$this->EE->db->cache_off();

		//get the module id
		$mod_id = $this->EE->db->select( 'module_id' )->get_where( 'modules', array( 'module_name'	=> 'Ce_cache' ) )->row( 'module_id' );

		//remove the module by id from the module member groups
		$this->EE->db->where( 'module_id', $mod_id )->delete( 'module_member_groups' );

		//remove the module
		$this->EE->db->where( 'module_name', 'Ce_cache' )->delete( 'modules' );

		//remove the actions
		$this->EE->db->where( 'class', 'Ce_cache' );
		$this->EE->db->delete( 'actions' );
		$this->EE->db->where( 'class', 'Ce_cache_mcp' );
		$this->EE->db->delete( 'actions' );

		//remove the installed tables
		if ( $this->EE->db->table_exists( 'ce_cache_db_driver' ) )
		{
			$this->EE->load->dbforge();
			$this->EE->dbforge->drop_table( 'ce_cache_db_driver' );
		}

		if ( $this->EE->db->table_exists( 'ce_cache_breaking' ) )
		{
			$this->EE->load->dbforge();
			$this->EE->dbforge->drop_table( 'ce_cache_breaking' );
		}

		if ( $this->EE->db->table_exists( 'ce_cache_tagged_items' ) )
		{
			$this->EE->load->dbforge();
			$this->EE->dbforge->drop_table( 'ce_cache_tagged_items' );
		}

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @param string $current
	 * @return boolean TRUE
	 */
	public function update( $current = '' )
	{
		//If up-do-date or a new install, don't worry about it
		if ( $current == '' || $current == $this->version )
		{
			return FALSE;
		}

		//clear all caches and add the new tables
		if ( version_compare( $current, '1.5', '<' )  )
		{
			$this->clear_all_caches();

			//setup the tables
			$this->setup_tables();
		}

		//make sure the break cache action is added to the db
		if ( version_compare( $current, '1.5.2', '<' ) )
		{
			//remove the actions
			$this->EE->db->where( 'class', 'Ce_cache' );
			$this->EE->db->delete( 'actions' );

			//add actions
			$this->EE->db->insert( 'actions', array( 'class' => 'Ce_cache', 'method' => 'break_cache' ) );
		}

		//make sure the break cache action is added to the db
		if ( version_compare( $current, '1.7', '<' ) )
		{
			//add actions
			$this->EE->db->insert( 'actions', array( 'class' => 'Ce_cache_mcp', 'method' => 'ajax_get_level' ) );
			$this->EE->db->insert( 'actions', array( 'class' => 'Ce_cache_mcp', 'method' => 'ajax_delete' ) );
		}

		return TRUE;
	}

	/**
	 * Sets up the CE Cache tables.
	 *
	 * @return void
	 */
	private function setup_tables()
	{
		$this->EE->db->cache_off();

		//create the cache table for the db driver
		if ( ! $this->EE->db->table_exists( 'ce_cache_db_driver' ) )
		{
			$this->EE->load->dbforge();

			//specify the fields
			$fields = array(
				'id' => array(
					'type' => 'VARCHAR',
					'constraint' => '250',
					'auto_increment' => FALSE,
					'null' => FALSE
				),
				'ttl' => array(
					'type' => 'INT',
					'constraint' => '10',
					'null' => FALSE,
					'default' => '360'
				),
				'made' => array(
					'type' => 'INT',
					'constraint' => '10'
				),
				'content' => array(
					'type' => 'LONGTEXT'
				)
			);

			$this->EE->dbforge->add_field( $fields );
			$this->EE->dbforge->add_key( 'id', TRUE );
			$this->EE->dbforge->create_table( 'ce_cache_db_driver' );
		}

		//create the cache breaking table
		if ( ! $this->EE->db->table_exists( 'ce_cache_breaking' ) )
		{
			$this->EE->load->dbforge();

			//specify the fields
			$fields = array(
				'channel_id' => array(
					'type' => 'INT',
					'constraint' => '10',
					'null' => FALSE,
					'unsigned' => TRUE
				),
				'tags' => array(
					'type' => 'TEXT'
				),
				'items' => array(
					'type' => 'TEXT'
				),
				'refresh_time' => array(
					'type' => 'INT',
					'constraint' => '1',
					'unsigned' => TRUE
				),
				'refresh' => array(
					'type' => 'VARCHAR',
					'constraint' => '1',
					'default' => 'n'
				)
			);

			$this->EE->dbforge->add_field( $fields );
			$this->EE->dbforge->add_key( 'channel_id', TRUE );
			$this->EE->dbforge->create_table( 'ce_cache_breaking' );
		}

		//create the tagging table
		if ( ! $this->EE->db->table_exists( 'ce_cache_tagged_items' ) )
		{
			$this->EE->load->dbforge();

			//specify the fields
			$fields = array(
				'item_id' => array(
					'type' => 'VARCHAR',
					'constraint' => '250',
					'null' => FALSE
				),
				'tag' => array(
					'type' => 'VARCHAR',
					'constraint' => '100',
					'null' => FALSE
				),
			);

			$this->EE->dbforge->add_field( $fields );
			$this->EE->dbforge->create_table( 'ce_cache_tagged_items' );
		}
	}

	private function clear_all_caches()
	{
		//clear all caches
		if ( ! class_exists( 'Ce_cache_factory' ) ) //load the class if needed
		{
			include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
		}
		$classes = Ce_cache_factory::factory( Ce_cache_factory::$valid_drivers );
		foreach ( $classes as $class )
		{
			//attempt to clear the cache for this driver class
			$class->clear();
		}
	}
}
/* End of file upd.ce_cache.php */
/* Location: /system/expressionengine/third_party/ce_cache/upd.ce_cache.php */