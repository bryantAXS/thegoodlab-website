<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_relationships
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 * 
 */

class Ep_relationships {



	var $rel_fields;
	var $rel_data;



	function Ep_relationships()
	{		
		$this->EE =& get_instance();
	}



	function remove_relationships_from_data($data)
	{
		// Load the relationship model
		$this->EE->load->model('ep_rels');

		// Get all the fields of type *relationship*
		$rel_fields = $this->EE->ep_rels->get_all_fields('rel');

		// Store any fields we remove in an array
		$this->rel_data = array();
		$this->rel_fields = array();
		$looper = 0;

		if ($rel_fields->num_rows() > 0)
		{
			foreach ($rel_fields->result_array() as $row)
			{
				$this_entry_id = $data['entry_id'];
				$this_field_id = 'field_id_'.$row['field_id'];
			
				// If this field is defined in our data array - store it in the cache and update the data array before sending back
				if (isset($data[$this_field_id]))
				{
					// Store the list of fields, we'll need this later
					$this->rel_fields[$looper]['id'] = $this_field_id;
					$this->rel_fields[$looper]['type'] = $row['field_related_to'];

					// Get whatever data is currently stored in the DB for this field
					$current_field_data = $this->EE->ep_rels->get_current_data($this_field_id, $this_entry_id);
					
					// Replace the value in the data array with the stored data, this way the *live* relationships won't change
					$this->rel_data[$this_field_id] = $data[$this_field_id];
					$data[$this_field_id] = (STRING)$current_field_data;
					
					if(isset($data['revision_post'][$this_field_id]))
					{
						$this->rel_data['revision_post'][$this_field_id] = $data['revision_post'][$this_field_id];
						$data['revision_post'][$this_field_id] = $current_field_data;
					}
					
					$looper++;
				}
			}
		}
		return $data;
	}



	function reinstate_relationships_to_data($entry_data, $rel_data)
	{
		foreach($rel_data as $key => $value)
		{
			if($key == 'revision_post' && is_array($value))
			{
				foreach($value as $key => $value)
				{
					$entry_data["revision_post"][$key] = $value;
				}
			}
			else
			{
				$entry_data[$key] = $value;
			}
		}
		
		return $entry_data;
	}





}