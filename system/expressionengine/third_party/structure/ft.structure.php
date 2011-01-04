<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Structure Fieldtype
 *
 * This file must be in your /system/third_party/structure directory of your ExpressionEngine installation
 *
 * @package             StructureFrame for EE2
 * @author              Jack McDade (jack@jackmcdade.com)
 * @copyright			Copyright (c) 2010 Travis Schmeisser
 * @link                http://buildwithstructure.com
 */

/**
 * Include Structure SQL Model
 */
require_once PATH_THIRD.'structure/sql.structure.php';

/**
 * Include Structure Core Mod
 */
require_once PATH_THIRD.'structure/mod.structure.php';


class Structure_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'StructureFrame',
		'version'	=> '2.2.4'
	);
	var $structure;
	var $sql;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Structure_ft()
	{
		parent::EE_Fieldtype();
		
		$this->structure = new Structure();
		$this->sql = new Sql_structure();
		
	}
	
	// --------------------------------------------------------------------
	
		
	/**
     * Normal Fieldtype Display
     */
	function display_field($data)
	{	
		return $this->_pages_select($data, $this->field_name, $this->field_id);
	}
	
	
	/**
     * Matrix Cell Display
     */
	function display_cell($data)
	{	
		return $this->_pages_select($data, $this->cell_name, $this->field_id);
	}
	
	
	/**
     * Low Variables Fieldtype Display
     */
    function display_var_field($data)
    {
        return $this->_pages_select($data, $this->field_name);
    }
    
	
	// --------------------------------------------------------------------
	
	/**
    * Structure Pages Select
    *
    * @return string select HTML
    * @access private
    */
	private function _pages_select($data, $name, $field_id = false)
	{
		$site_pages = $this->structure->get_site_pages();	
		$structure_data = $this->sql->get_data();
		
		$exclude_status_list[] = "closed";
		$closed_parents = array();

		foreach ($structure_data as $key => $entry_data)
		{
			if (in_array(strtolower($entry_data['status']), $exclude_status_list) || in_array($entry_data['parent_id'], $closed_parents))
			{
				$closed_parents[] = $entry_data['entry_id'];
				unset($structure_data[$key]);
			}
		}
		
		$structure_data = array_values($structure_data);

		$options = array();
		$options[''] = "-- None --";
		
		foreach ($structure_data as $page)
		{		
			$options[$page['entry_id']] = str_repeat('--', $page['depth']) . $page['title'];
		}
		
		return form_dropdown($name, $options, $data);
	}

	// --------------------------------------------------------------------
	
	function replace_tag($data, $params = '', $tagdata = '')
	{
		$this->structure = new Structure();
		$site_pages = $this->structure->get_site_pages();
		
		return $this->EE->functions->remove_double_slashes(trim($this->EE->functions->fetch_site_index(0, 0), '/') . $site_pages['uris'][$data]);
	}
}

// END Structure_ft class

/* End of file ft.structure.php */
/* Location: ./system/expressionengine/third_party/structure/ft.structure.php */