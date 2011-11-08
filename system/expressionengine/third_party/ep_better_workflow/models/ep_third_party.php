<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_third_party Model
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_third_party extends CI_Model {



	function Ep_third_party()
	{
		parent::__construct();
	}



	function get_all_fields($field_type) 
	{
		$this->db->select('field_id, field_related_to, field_related_id');
		return $this->db->get_where('channel_fields', array('field_type' => $field_type));
	}



	function update_draft_data($entry_id, $field_id, $field_type, $row_id, $row_order, $col_id, $col_data)
	{
		$this->db->insert('ep_entry_drafts_thirdparty', array('entry_id'=>$entry_id, 'field_id'=>$field_id, 'type'=>$field_type, 'row_id'=>$row_id, 'row_order'=>$row_order, 'col_id'=>$col_id, 'data'=>$col_data));
	}



	function delete_draft_data($where)
	{
		$this->db->delete('ep_entry_drafts_thirdparty', $where);
	}



	function delete_entry_data($field_type, $entry_id)
	{
		switch ($field_type)
		{
			case 'matrix':
				$this->_delete_matrix_data($entry_id);
			break;
		}
	}



	function delete_matrix_playa_data($this_entry_id, $this_field_id, $row_id, $col_id)
	{
		// Where conditions
		$where = array(
			'entry_id' => $this_entry_id,
			'field_id' => $this_field_id,
			'row_id' => $row_id,
			'col_id' => $col_id
		);
		
		$this->db->delete('ep_entry_drafts_thirdparty', $where);
	}



	function get_matrix_col_info($col_id)
	{
		$this->db->select('col_type, col_settings');
		$this->db->where('col_id', $col_id);
		return $this->db->get('matrix_cols');
	}



	private function _delete_matrix_data($entry_id)
	{
		$this->db->select('row_id');
		$rs = $this->db->get_where('ep_entry_drafts_thirdparty', array('type' => 'matrix_delete', 'entry_id' => $entry_id));
		
		if ($rs->num_rows() > 0)
		{
			foreach ($rs->result_array() as $row)
			{
				$this->db->delete('matrix_data', array('row_id' => $row['row_id']));
			}
		}
	}



}
