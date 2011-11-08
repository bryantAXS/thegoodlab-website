<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_matrix
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_matrix {



	var $matrixObj;
	var $bundled_celltypes = array('text', 'date', 'file');



	function Ep_matrix()
	{		
		$this->EE =& get_instance();
	}



	function remove_matrices_from_data($data)
	{		
		// Load the third party model
		$this->EE->load->model('ep_third_party');
		
		// Get all the matrix fields
		$mtx_fields = $this->EE->ep_third_party->get_all_fields('matrix');
		
		if ($mtx_fields->num_rows() > 0)
		{
			foreach ($mtx_fields->result_array() as $row)
			{
				$this_entry_id = $data['entry_id'];
				$this_field_id = $row['field_id'];
				$this_field_name = 'field_id_'.$row['field_id'];
				$row_order = 0;
				$new_row_loop = 0;

				// If this field is defined in our data array - store it in the cache and update the data array before sending back
				if (isset($data[$this_field_name]))
				{					
					// Grab the value of the matrix field
					$mtx_data = $data[$this_field_name];
					
					// If this field is also defined in the revision_post array update $mtx_data
					if(isset($data['revision_post'][$this_field_name]))
					{
						$mtx_data = $data['revision_post'][$this_field_name];
					}
					
					
					// Delete all existing 'data' records for this field (as in, not 'delete' records)
					$this->EE->ep_third_party->delete_draft_data(array('entry_id' => $this_entry_id, 'field_id' => $this_field_id, 'type'=>'matrix'));
					
					
					// Now we have the Matrix data loop through its rows, if we have any
					if (isset($mtx_data['row_order']))
					{
						foreach ($mtx_data['row_order'] as $row_name)
						{
							if(isset($mtx_data[$row_name]))
							{
								$row_data = $mtx_data[$row_name];
							}

							// If the row_name has the words 'row_new' in it we need to create a temp ID
							if (strpos($row_name, 'row_new') !== false)
							{
								$row_id = 'row_new_'.$new_row_loop;
								
								$data = $this->update_matrix_data_in_array($data, $this_field_name, $row_name, $row_id);
								
								$new_row_loop++;
							}
							else
							{
								$row_id = substr($row_name, 7);
							}

							// Now that we have the raw data we need to check each col's *type* as they all have their own post processing
							// It's all pretty complex stuff but hopefully we can borrow a bunch of code from the *real* Matrix
							foreach($row_data as $col_name => $col_data)
							{
								$col_id = substr($col_name, 7);

								$col_data = $this->_do_type_specific_save($col_id, $col_name, $col_data, $row_name, $this_field_id);

								// If this col is a Playa we need to grab the playa data and save it in our third party table
								if(isset($this->EE->session->cache['playa']['selections'][$this_field_id][$col_id]))
								{
									// First delete any existing Playa records for this cell
									$this->EE->ep_third_party->delete_matrix_playa_data($this_entry_id, $this_field_id, $row_id, $col_id);
									
									// Reset the Playa order counter
									$playa_order = 0;
									
									// Get the Playa selection data from the cache
									$cell_rels = $this->EE->session->cache['playa']['selections'][$this_field_id][$col_id][$row_name];
									
									// Now update the third party table with our new Playa data
									foreach($cell_rels as $rel)
									{
										$this->EE->ep_third_party->update_draft_data($this_entry_id, $this_field_id, 'playa', $row_id, $playa_order, $col_id, $rel);
										$playa_order++;
									}
								}

								$this->EE->ep_third_party->update_draft_data($this_entry_id, $this_field_id, 'matrix', $row_id, $row_order, $col_id, $col_data);
							}
							$row_order++;
						}
					}
					
					// Remove all temp row IDs
					$data = $this->remove_temp_row_ids($data, $this_field_name);
					
					
					// If there are any deleted rows data remove this from the data array before we send it back
					$data = $this->save_matrix_delete_data($data, $this_entry_id, $this_field_name, $this_field_id);
										
				}
			}
		}
		
		return $data;
	}



	function update_matrix_data_in_array($data, $this_field_name, $old_value, $new_value)
	{
		// Do we have a defined sortorder for this field?
		if (isset($data['revision_post'][$this_field_name]['row_order']))
		{
			foreach($data['revision_post'][$this_field_name]['row_order'] as $key => $value)
			{
				if($value == $old_value)
				{
					$data['revision_post'][$this_field_name]['row_order'][$key] = $new_value."_temp";
					$data['revision_post'][$this_field_name][$new_value."_temp"] = $data['revision_post'][$this_field_name][$value];
					unset($data['revision_post'][$this_field_name][$value]);
				}
			}
		}
		return $data;
	}



	function remove_temp_row_ids($data, $this_field_name)
	{
		if (isset($data['revision_post'][$this_field_name]['row_order']))
		{
			// Create a temp array and looper
			$temp_array = array();
			$i = 0;
			
			foreach($data['revision_post'][$this_field_name]['row_order'] as $key => $value)
			{
				$row_id = $value;

				// If we created a temp row ID in the 'update_matrix_data_in_array' method, revert it to a matrix friendly format
				if (strpos($value, '_temp') !== false)
				{
					$row_id = substr($value, 0, strlen($value)-5);
				}
				
				$temp_array['row_order'][$i] = $row_id;
				$temp_array[$row_id] = $data['revision_post'][$this_field_name][$value];
				$i++;
			}
			if(isset($data['revision_post'][$this_field_name]['deleted_rows']))
			{
				$temp_array['deleted_rows'] = $data['revision_post'][$this_field_name]['deleted_rows'];
			}
			
			// Replace the record in the data array with our new, correctly ordered one
			$data['revision_post'][$this_field_name] = $temp_array;
			
		}
		else
		{
			$data['revision_post'][$this_field_name]['row_order'] = "0";	
		}
		return $data;
	}



	function save_matrix_delete_data($data, $this_entry_id, $this_field_name, $this_field_id)
	{
		// Do we have any deleted rows for this field?
		if (isset($data['revision_post'][$this_field_name]['deleted_rows']))
		{
			foreach($data['revision_post'][$this_field_name]['deleted_rows'] as $del_row)
			{
				$row_id = substr($del_row, 7);				
				$this->EE->ep_third_party->update_draft_data($this_entry_id, $this_field_id, 'matrix_delete', $row_id, '', '', '');
			}
			
			// Remove the actual deleted rows field from the data so the Publish page doesn't treat it like another row when it next loads
			unset($data['revision_post'][$this_field_name]['deleted_rows']);
		}
		return $data;
	}



	function get_draft_data_for_preview($matrixObj, $params, $sql)
	{
		$return_sql = $sql;

		$field_id = $matrixObj->field_id;
		$entry_id = $matrixObj->row['entry_id'];
					
		// Get the rows we need for this matrix field
		$matrix_query = "SELECT distinct row_id FROM exp_ep_entry_drafts_thirdparty where entry_id = ".$entry_id." and field_id = ".$field_id." and type = 'matrix' ORDER BY row_order ASC";
		
		$matrix_rows = $this->EE->db->query($matrix_query);
		
		// If we have some Matrix data build and return our custom query
		if ($matrix_rows->num_rows() > 0)
		{
			// Define the *master* select query and a looper
			$the_select = "";
			$looper = 0;	
	
				foreach($matrix_rows->result() as $matrix_row)
				{
					$this_row_id = $matrix_row->row_id;

					if($looper > 0) $the_select .= " UNION ALL ";

					$the_select .= "SELECT DISTINCT";
					$query = $this->EE->db->query("SELECT distinct col_id FROM exp_ep_entry_drafts_thirdparty WHERE entry_id = ".$entry_id." AND field_id = ".$field_id."");
					foreach ($query->result() as $row)
					{
						$the_select .= "(SELECT data FROM exp_ep_entry_drafts_thirdparty WHERE type = 'matrix' AND col_id = ".$row->col_id." AND field_id = ".$field_id." AND entry_id = ".$entry_id." AND row_id = '".$this_row_id."') as col_id_" .$row->col_id.",";
					}
					$the_select .= "'".$this_row_id."' AS row_id FROM exp_ep_entry_drafts_thirdparty WHERE field_id = ".$field_id." AND entry_id = ".$entry_id;
					$looper++;
				}

				$return_sql = $the_select;
		}
		// If we don't have any data return a query which will return nothing
		else
		{
			$return_sql = $matrix_query;
		}

		return $this->EE->db->query($return_sql);
	}



	private function _do_type_specific_save($col_id, $col_name, $col_data, $row_name, $field_id)
	{
		$query = $this->EE->ep_third_party->get_matrix_col_info($col_id);

		if ($query->num_rows() > 0)
		{
			$row = $query->row();
			$col_type = $row->col_type;
			$col_settings = $row->col_settings;
		}
		
		// Borrowing Matrix method, get the relevant cell type
		$celltype = $this->_get_celltype($col_type);

		// Give the celltype a chance to do what it wants with the data
		if (method_exists($celltype, 'save_cell'))
		{			
			// TODO: Figure out what happens with settings	
			// We've learnt that Playa needs the field_id
			if (is_Array($col_settings)) $celltype->settings = array_merge($celltype->settings, $col_settings);
			$celltype->settings['col_id']   = $col_id;
			$celltype->settings['col_name'] = $col_name;
			$celltype->settings['row_name'] = $row_name;
			$celltype->settings['field_id'] = $field_id;

			$col_data = $celltype->save_cell($col_data);
		}
		
		return $col_data;
		
	}



	// --------------------------------------------------------------------

	/**
	 * Get Celltype Class
	 */
	private function _get_celltype_class($name, $text_fallback = FALSE)
	{
		// $name should look like exp_fieldtypes.name values
		if (substr($name, -3) == '_ft') $name = substr($name, 0, -3);
		$name = strtolower($name);

		// is this a bundled celltype?
		// Need to try and get this data from Matrix...
		if (in_array($name,  $this->bundled_celltypes))
		{
			$class = 'Matrix_'.$name.'_ft';

			if (! class_exists($class))
			{
				// load it from matrix/celltypes/
				require_once PATH_THIRD.'matrix/celltypes/'.$name.EXT;
			}
		}
		else
		{			
			$class = ucfirst($name).'_ft';
			$this->EE->api_channel_fields->include_handler($name);
		}

		if (class_exists($class))
		{
			// method_exists() is supposed to accept the class name (string),
			// but running into at least one server where that's not the case...
			$ft = new $class();

			if (method_exists($ft, 'display_cell'))
			{
				if (! isset($this->cache['celltype_global_settings'][$name]))
				{					
					$this->EE->db->select('settings');
					$this->EE->db->where('name', $name);
					$query = $this->EE->db->get('fieldtypes');

					$settings = $query->row('settings');
					$this->cache['celltype_global_settings'][$name] = is_array($settings) ? $settings : unserialize(base64_decode($settings));
				}
				return $class;
			}
		}

		return $text_fallback ? $this->_get_celltype_class('text') : FALSE;
	}



	// --------------------------------------------------------------------

	/**
	 * Get Celltype
	 */
	private function _get_celltype($name, $text_fallback = FALSE)
	{
		$class = $this->_get_celltype_class($name, $text_fallback);

		if (! $class) return FALSE;

		$celltype = new $class();

		$global_settings = $this->cache['celltype_global_settings'][$name];
		$celltype->settings = $global_settings && is_array($global_settings) ? $global_settings : array();

		return $celltype;
	}



}