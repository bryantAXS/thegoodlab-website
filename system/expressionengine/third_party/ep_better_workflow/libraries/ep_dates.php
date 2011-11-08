<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_dates
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_dates {



	function Ep_dates()
	{		
		$this->EE =& get_instance();
	}



	function dates_in_data_to_unix($data)
	{
		// Get all the fields of type *date*
		$date_fields = $this->_get_all_date_fields(array('date','eevent_helper'));
		
		if ($date_fields->num_rows() > 0)
		{
			foreach ($date_fields->result_array() as $row)
			{
				$this_field_id = 'field_id_'.$row['field_id'];
			
				// Is this field is defined in our data array...
				if (isset($data[$this_field_id]))
				{
					$d = $data[$this_field_id];
					$data[$this_field_id] = $this->_make_sure_its_unix($d);
					
					if(isset($data['revision_post'][$this_field_id]))
					{
						$dr = $data['revision_post'][$this_field_id];
						$data['revision_post'][$this_field_id] = $this->_make_sure_its_unix($dr);
					}
				}
			}
		}
		return $data;
	}



	private function _get_all_date_fields($field_types)
	{
		$this->EE->db->select('field_id, field_related_to, field_related_id');
		$this->EE->db->where_in('field_type', $field_types);
		return $this->EE->db->get('channel_fields');
	}



	private function _make_sure_its_unix($date_str)
	{
		if(strpos($date_str, "-") === false)
		{
			return $date_str;
		}
		else
		{
			if(strlen($date_str) == 10) $date_str = $date_str.' 04:00:00 AM';
			return $this->EE->localize->convert_human_date_to_gmt($date_str);
		}
	}
}