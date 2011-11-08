<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_statuses Model
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_statuses extends CI_Model {



	var $status_color_closed;
	var $status_color_draft;
	var $status_color_submitted;
	var $status_color_open;



	function Ep_statuses()
	{
		parent::__construct();
	}



	function create_status_group($group_name)
	{
		$data = array(
			'group_name'	=> $group_name,
			'site_id'	=> $this->config->item('site_id')
		);
		$this->db->insert('status_groups', $data);
		return $this->db->insert_id();
	}



	function insert_status($group_id, $status_name, $status_order, $status_colour)
	{
		$data = array(
			'site_id'	=> $this->config->item('site_id'),
			'group_id'	=> $group_id,
			'status'	=> $status_name,
			'status_order'	=> $status_order,
			'highlight'	=> $status_colour
		);
		$this->db->insert('statuses', $data);
		return true;
	}



	function remove_status_group($group_name)
	{
		$this->db->from('status_groups');
		$this->db->where('group_name', $group_name);
		$this->db->delete();
		return TRUE;
	}



	function get_status_group_id($group_name)
	{
		$this->db->where('group_name', $group_name);
		$this->db->where('site_id', $this->config->item('site_id'));
		$status_group = $this->db->get('status_groups');
		if ($status_group->num_rows() == 0)
		{
			// If we can't find the status group it may be because we have switched sites since installation
			return $this->_duplicate_status_group($group_name);
		}
		else
		{
			return $status_group->row('group_id');
		}
	}



	function get_open_status_id($group_id)
	{
		$this->db->where('group_id', $group_id);
		$this->db->where('status', 'open');
		$this->db->where('site_id', $this->config->item('site_id'));
		$statuses = $this->db->get('statuses');
		if ($statuses->num_rows() == 0)
		{
			return FALSE;
		}
		else
		{
			return $statuses->row('status_id');
		}
	}



	function check_channel_entries($channel_id, $bwf_statuses)
	{
		if($channel_id == '') return FALSE;
		
		$this->db->select('status');
		$this->db->where('channel_id', $channel_id);
		$this->db->where_not_in('status', $bwf_statuses);
		$this->db->group_by('status');
		$statuses = $this->db->get('channel_titles');
		if ($statuses->num_rows() == 0)
		{
			return FALSE;
		}
		else
		{
			return $statuses;
		}
	}



	private function _duplicate_status_group($group_name)
	{
		$status_group_id = $this->create_status_group($group_name);
		
		$this->insert_status($status_group_id, 'closed', '1', $this->status_color_closed);
		$this->insert_status($status_group_id, 'draft', '2', $this->status_color_draft);
		$this->insert_status($status_group_id, 'submitted', '3', $this->status_color_submitted);
		$this->insert_status($status_group_id, 'open', '4', $this->status_color_open);
		
		return $status_group_id;
	}


}