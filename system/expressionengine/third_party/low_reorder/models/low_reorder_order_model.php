<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Low Reorder Order Model class
 *
 * @package        low_reorder
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-reorder
 * @copyright      Copyright (c) 2009-2012, Low
 */
class Low_reorder_order_model extends Low_reorder_model {

	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access      public
	 * @return      void
	 */
	function __construct()
	{
		// Call parent constructor
		parent::__construct();

		// Initialize this model
		$this->initialize(
			'low_reorder_orders',
			array('set_id', 'cat_id'),
			array(
				'sort_order' => 'TEXT'
			)
		);
	}

	/**
	 * REPLACE INTO query
	 *
	 * @access      public
	 * @param       array
	 * @return      void
	 */
	public function replace($data = array())
	{
		$sql = $this->EE->db->insert_string(
			$this->table(),
			$data
		);

		$this->EE->db->query(str_replace('INSERT', 'REPLACE', $sql));
	}

	/**
	 * INSERT IGNORE query
	 *
	 * @access      public
	 * @param       array
	 * @return      void
	 */
	public function insert_ignore($data = array())
	{
		$sql = $this->EE->db->insert_string(
			$this->table(),
			$data
		);

		$this->EE->db->query(str_replace('INSERT', 'INSERT IGNORE', $sql));
	}

	// --------------------------------------------------------------------

} // End class

/* End of file Low_reorder_order_model.php */