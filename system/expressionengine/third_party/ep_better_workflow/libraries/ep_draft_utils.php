<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_draft_utils
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_draft_utils
{

	function Ep_draft_utils()
	{
		$this->EE =& get_instance();    
	}



	/**
	*  Replace entry with draft data.
	*  @param mixed $draft
	*  @param mixed $data
	*  @return mixed $data 
	*
	*/
	function load_draft($draft,$data)
	{
		//TODO: please review this, as it is very sketchy!

		$draft_data=unserialize($draft->draft_data); 
		// unset($draft->draft_data);

		$out = $data;
		foreach($draft_data as $k => $v)
		{
			if ($k != 'revision_post') $out[$k]=$v;
		}
		#$out['revision_post'] = $draft_data;
		$out['status'] = $draft->status;
		#$out['revision_post']['status'] = $draft->status;
		return $out;
	} 



	function publish_draft($entry_id,$meta,$data)
	{
		//TODO: implement access control here 

		$all_data=array_merge($meta,$data); 

		foreach(array('channel_data','channel_titles') as $table)
		{
			$new_data = array();
			$table_fields=$this->EE->db->list_fields($table);

			foreach($table_fields as $k)
			{
				$array = ($table == 'channel_data')? $data : $all_data;
				if (isset($array[$k])) $new_data[$k]=$array[$k];
			}

			$this->EE->db->where('entry_id',$entry_id);
			$this->EE->db->update($table,$new_data); 
		}

		//delete the draft 
		$this->EE->ep_entry_draft->delete(array('entry_id' => $entry_id));
		$this->EE->functions->redirect(BASE.AMP.'C=content_edit');  
	}



	function create_entry_draft($meta,$data)
	{
		// $this->EE->load->model('ep_entry_draft');
		$write_data= $this->ep_draft_utils->merge_entry_data($meta,$data);
		$this->EE->ep_entry_draft->create($write_data);
		$this->EE->functions->redirect(BASE.AMP.'C=content_edit'); 
		die();
	}



	function update_entry_draft($entry_id,$meta,$data)
	{
		$this->EE->load->model('ep_entry_draft'); 
		$write_data= $this->ep_draft_utils->merge_entry_data($meta,$data);
		$this->EE->ep_entry_draft->update(array('entry_id' => $entry_id),$write_data);
		$this->EE->functions->redirect(BASE.AMP.'C=content_edit'); 
		die();
	}


	function merge_entry_data($meta,$data)
	{
		$write_data=array();
		$fields=array_values($this->EE->db->list_fields('ep_entry_drafts'));

		foreach($meta as $k => $v)
		{
			if (in_array($k,$fields)) $write_data[$k]=$meta[$k];
		}

		return array_merge($write_data, array('draft_data'=> serialize($data), 'entry_id' => $data['entry_id'] ));
         }


}
