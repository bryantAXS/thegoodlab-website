<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include base class
if ( ! class_exists('Low_reorder_base'))
{
	require_once(PATH_THIRD.'low_reorder/base.low_reorder.php');
}

/**
 * Low Reorder Extension class
 *
 * @package        low_reorder
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-reorder
 * @copyright      Copyright (c) 2009-2012, Low
 */
class Low_reorder_ext extends Low_reorder_base
{
	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * Do settings exist?
	 *
	 * @var        string	y|n
	 * @access     public
	 */
	public $settings_exist = 'n';

	/**
	 * Required?
	 *
	 * @var        array
	 * @access     public
	 */
	public $required_by = array('module');

	// --------------------------------------------------------------------

	/**
	 * Extension class name
	 *
	 * @var        string
	 * @access     private
	 */
	private $class_name;

	/**
	 * Extension hooks
	 *
	 * @var        array
	 * @access     private
	 */
	private $hooks = array(
		'entry_submission_end',
		'channel_entries_query_result'
	);

	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	/**
	 * Legacy Constructor
	 *
	 * @see        __construct()
	 */
	public function Low_reorder_ext($settings = array())
	{
		$this->__construct($settings);
	}

	// --------------------------------------------------------------------

	/**
	 * PHP5 Constructor
	 *
	 * @access     public
	 * @param      mixed
	 * @return     void
	 */
	public function __construct($settings = array())
	{
		// Call Base constructor
		parent::__construct();

		// Set current class name
		$this->class_name = ucfirst(get_class($this));

		// Assign current settings
		$this->settings = array_merge($this->default_settings, (array) $settings);
	}

	// --------------------------------------------------------------------

	/**
	 * Add/modify entry in sort orders
	 *
	 * @access      public
	 * @param       int
	 * @param       array
	 * @param       array
	 * @return      void
	 */
	public function entry_submission_end($entry_id, $meta, $data)
	{
		// Get sets for this channel
		$this->EE->db->like('channels', '|'.$meta['channel_id'].'|');
		$sets = $this->EE->low_reorder_set_model->get_all();

		// Define array for new orders
		$new_orders = array();

		foreach ($sets AS $set)
		{
			$cat_ids = ($set['cat_option'] == 'one' && isset($data['revision_post']['category']))
			         ? $data['revision_post']['category']
			         : array(0);

			// Create array of all the Orders that must be present
			foreach ($cat_ids AS $cat_id)
			{
				$new_orders[$set['set_id'].'-'.$cat_id] = array($entry_id);
			}

			// Get existing orders that might need this entry appended to it
			$this->EE->db->where('set_id', $set['set_id'])
			             ->where_in('cat_id', $cat_ids);
			$old_orders = $this->EE->low_reorder_order_model->get_all();

			// Loop through existing orders and update the order in $new_orders
			foreach ($old_orders AS $row)
			{
				$entry_ids = low_delinearize($row['sort_order']);

				// Append/Prepend if it's not there yet
				if ( ! in_array($entry_id, $entry_ids))
				{
					if ($set['new_entries'] == 'prepend')
					{
						$entry_ids = array_merge(array($entry_id), $entry_ids);
					}
					else
					{
						$entry_ids[] = $entry_id;
					}
				}

				$new_orders[$row['set_id'].'-'.$row['cat_id']] = $entry_ids;
			}

			// Loop through new orders and REPLACE INTO orders table
			foreach ($new_orders AS $key => $val)
			{
				list($set_id, $cat_id) = explode('-', $key);

				$this->EE->low_reorder_order_model->replace(array(
					'set_id' => $set_id,
					'cat_id' => $cat_id,
					'sort_order' => low_linearize($val)
				));
			}
		}
	}

	/**
	 * Add reverse count to channel entries
	 *
	 * @access      public
	 * @param       object
	 * @param       array
	 * @return      array
	 */
	public function channel_entries_query_result($obj, $query)
	{
		// -------------------------------------------
		// Get the latest version of $query
		// -------------------------------------------

		if ($this->EE->extensions->last_call !== FALSE)
		{
			$query = $this->EE->extensions->last_call;
		}

		// -------------------------------------------
		// Fire for low_reorder only
		// -------------------------------------------

		if ($this->EE->TMPL->fetch_param('low_reorder') == 'yes')
		{
			$total_results = count($query);
			$reverse_count = $this->EE->TMPL->fetch_param('reverse_count', 'reverse_count');

			foreach ($query AS &$row)
			{
				if ( ! isset($row[$reverse_count]))
				{
					$row[$reverse_count] = $total_results--;
				}
			}
		}

		// -------------------------------------------
		// Return (modified) query
		// -------------------------------------------

		return $query;
	}

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 *
	 * @access     public
	 * @return     void
	 */	
	public function activate_extension()
	{
		foreach ($this->hooks AS $hook)
		{
			$this->_add_hook($hook);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * @access     public
	 * @return     void
	 */
	public function disable_extension()
	{
		$this->EE->db->where('class', $this->class_name);
		$this->EE->db->delete('extensions');
	}

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * @access     public
	 * @return     void
	 */
	public function update_extension($current = '')
	{
		// -------------------------------------
		//  Same version? Bail out
		// -------------------------------------

		if ($current == '' OR (version_compare($current, $this->version) === 0) )
		{
			return FALSE;
		}

		// Add new hook in 2nd beta
		if (version_compare($current, '2.0b2', '<'))
		{
			$this->_add_hook('channel_entries_query_result');
		}

		// -------------------------------------
		//  Update version number and new settings
		// -------------------------------------

		$this->EE->db->where('class', $this->class_name);
		$this->EE->db->update('extensions', array(
			'version' => $this->version,
			'settings' => serialize($this->settings)
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Add extension hook
	 *
	 * @access     private
	 * @param      string
	 * @return     void
	 */
	private function _add_hook($name)
	{
		$this->EE->db->insert('extensions',
			array(
				'class'    => $this->class_name,
				'method'   => $name,
				'hook'     => $name,
				'settings' => serialize($this->settings),
				'priority' => 5,
				'version'  => $this->version,
				'enabled'  => 'y'
			)
		);
	}

	// --------------------------------------------------------------------

} // End Class low_reorder_ext

/* End of file ext.low_reorder.php */