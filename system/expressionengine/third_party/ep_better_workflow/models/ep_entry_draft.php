<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_entry_draft Model
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_entry_draft extends CI_Model {

	var $bwf_settings;

	function Ep_entry_draft()
	{
		parent::__construct();
	}



	function _drop_non_existing_columns(&$data)
	{
    	$table_fields=array_values($this->db->list_fields('ep_entry_drafts'));
		foreach($data as $k => $v ) if (! in_array($k,$table_fields)) unset($data[$k]);
		}



	function create($attr_values,$drop_non_existing_columns=TRUE)
	{
    	if ($drop_non_existing_columns) $this->_drop_non_existing_columns($attr_values);
    	$this->db->insert('ep_entry_drafts',$attr_values);    
	}



	function update($where,$attr_values,$drop_non_existing_columns=TRUE)
	{
    	if ($drop_non_existing_columns) $this->_drop_non_existing_columns($attr_values);
    	foreach($where as $k => $v) $this->db->where($k,$v);
    	$this->db->update('ep_entry_drafts',$attr_values);
    }



	function delete($where)
	{
		foreach($where as $k => $v) $this->db->where($k,$v);
		$this->db->delete('ep_entry_drafts');
	}



	function get_by_entry_id($entry_id)
	{
		//TODO: replace that $_REQUEST entry_id with a paramter
		$query=$this->db->get_where('ep_entry_drafts',array('entry_id' => $entry_id),1);
		$res=array();
		foreach ($query->result() as $row)
		{
			$res[]=$row;
		}
		return isset($res[0])? $res[0] : NULL; 
	}



	function get_in_entry_ids($entry_ids, $select_fields=array())
	{		
		// Array to hold the data we return
		$edit_view_data = array();
		
		// Get all the entry IDs which below to BWF managed Channels
		$bwf_entry_ids = array();
		
		// Array to hold all the BWF drafts
		$bwf_draft_data = array();

		if(count($this->bwf_settings['bwf_channels']) > 0)
		{
			$this->db->select('entry_id');
			$this->db->where_in('channel_id', $this->bwf_settings['bwf_channels']);
			$this->db->where_in('entry_id', $entry_ids);		
			$rs = $this->db->get('channel_titles');
			foreach ($rs->result_array() as $row)
			{
				$bwf_entry_ids[] = $row['entry_id'];
			}
		}	
		$edit_view_data['bwf_entry_ids'] = $bwf_entry_ids;
		
		// Get all the Entries which have a BWF draft and the status of that draft
		$fields = count($select_fields) > 0 ?  implode(', ',$select_fields) : '*';
		$entry_ids_str = implode(', ', $entry_ids);
		$res=$this->db->query("SELECT $fields FROM exp_ep_entry_drafts WHERE entry_id IN ($entry_ids_str)"); 

		foreach($res->result() as $row)
		{
			$bwf_draft_data[] = $row;
		}
		$edit_view_data['bwf_draft_data'] = $bwf_draft_data;
		
		return $edit_view_data;
	}



  function get_data_by_entry_id($entry_id) {
  	$data = "";
	$query = $this->db->query("SELECT draft_data FROM exp_ep_entry_drafts WHERE entry_id = '$entry_id'");
	foreach ($query->result_array() as $row){
		$data = $row['draft_data'];
	}
	return $data;
  }


}
