<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_playa
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_playa {



	function Ep_playa()
	{		
		$this->EE =& get_instance();
	}



	function remove_playas_from_data($data)
	{
		// Load the third party model
		$this->EE->load->model('ep_third_party');
		
		// Get all the playa fields
		$playa_fields = $this->EE->ep_third_party->get_all_fields('playa');
		
		if ($playa_fields->num_rows() > 0)
		{
			foreach ($playa_fields->result_array() as $row)
			{
				$this_entry_id = $data['entry_id'];
				$this_field_id = $row['field_id'];
				$this_field_name = 'field_id_'.$row['field_id'];
				$row_order = 0;

				// If this field is defined in our data array
				if (isset($data[$this_field_name]))
				{
					// Grab the value of the matrix field
					$playa_data = $data[$this_field_name];
					
					// If this field is also defined in the revision_post array update $mtx_data
					if(isset($data['revision_post'][$this_field_name]))
					{
						$playa_data = $data['revision_post'][$this_field_name];
					}
					
					// Delete all existing 'data' records for this field (as in, not 'delete' records)
					$this->EE->ep_third_party->delete_draft_data(array('entry_id' => $this_entry_id, 'field_id' => $this_field_id, 'type'=>'playa'));
					
					// Do we have any new playa data
					if (is_array($playa_data))
					{
						// ignore everything but the selections
						$playa_data = array_merge(array_filter($playa_data['selections']));
					
						// Loop through the playa_data and insert the relationships
						foreach($playa_data as $rel)
						{
							$this->EE->ep_third_party->update_draft_data($this_entry_id, $this_field_id, 'playa', '', $row_order, '', $rel);
							$row_order++;
						}
					}
				}
			}
		}
	
		return $data;
	}



	function update_playa_data_from_cache($data)
	{
		// Load the third party model
		$this->EE->load->model('ep_third_party');
		
		// Get all the playa fields
		$playa_fields = $this->EE->ep_third_party->get_all_fields('playa');
		
		if ($playa_fields->num_rows() > 0)
		{
			foreach ($playa_fields->result_array() as $row)
			{
				$this_entry_id = $data['entry_id'];
				$this_field_id = $row['field_id'];
				$this_field_name = 'field_id_'.$row['field_id'];
				$row_order = 0;

				// If this field is defined in our data array
				if (isset($data[$this_field_name]))
				{					
					// If the data is an array, we're good
					if(isset( $this->EE->session->cache['playa']['selections'][$this_field_id] ))
					{
						unset($data[$this_field_name]);
						$data[$this_field_name]['selections'] = $this->EE->session->cache['playa']['selections'][$this_field_id];
						
						// If this field is also defined in the revision_post array, update this
						if(isset($data['revision_post'][$this_field_name]))
						{
							unset($data['revision_post'][$this_field_name]);
							$data['revision_post'][$this_field_name]['selections'] = $this->EE->session->cache['playa']['selections'][$this_field_id];
						}
					}
				}
			}
		}

		return $data;
	}



	function get_draft_data_for_preview($dir, $entry_id, $field_ids, $col_ids, $row_ids)
	{
		$this->EE->db->select('entry_id AS parent_entry_id');
		$this->EE->db->select('field_id AS parent_field_id');
		$this->EE->db->select('col_id AS parent_col_id');
		$this->EE->db->select('row_id AS parent_row_id');
		$this->EE->db->select('data AS child_entry_id');
		$this->EE->db->select('row_order AS rel_order');
		
		$this->EE->db->where('type', 'playa');

		if ($dir == 'children')
		{
			$this->EE->db->where('entry_id', $entry_id);

			// the rel_order matters
			$this->EE->db->order_by('rel_order');
		}
		else
		{
			$this->db_where('data', $entry_id);
		}
		

		// filter by field?
		if ($field_ids)
		{
			$this->db_where('field_id', $field_ids);
		}

		// filter by column?
		if ($col_ids)
		{
			$this->db_where('col_id', $col_ids);
		}

		// filter by row?
		if ($row_ids)
		{
			$this->db_where('row_id', $row_ids);
		}
	
		return $this->EE->db->get('ep_entry_drafts_thirdparty');
	}



	/**
	 * Helper function to deal with where clauses where the value may be an array or an integer
	 * Thanks, Brandon
	 */
	function db_where($col, $val)
	{
		if (! is_array($val))
		{
			$this->EE->db->where($col, $val);
		}
		elseif (count($val) == 1)
		{
			$this->EE->db->where($col, $val[0]);
		}
		else
		{
			$this->EE->db->where_in($col, $val);
		}
	}



}