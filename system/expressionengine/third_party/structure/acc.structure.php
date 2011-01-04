<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Accessory for Structure
 *
 * This file must be in your /system/third_party/structure directory of your ExpressionEngine installation
 *
 * @package             Structure for EE2
 * @author              Jack McDade (jack@jackmcdade.com)
 * @link                http://buildwithstructure.com
 */

/**
 * Include Structure Core Mod
 */
require_once PATH_THIRD.'structure/mod.structure.php';

class Structure_acc
{

	var $name = 'Structure';
	var $id	= 'structure-acc';
	var $version = '2.2.4';
	var $description = 'Access your Structure Assets anywhere';
	var $sections = array();
	
	var $structure;
	var $installed = FALSE;
	var $data = array();

	/**
	 * Constructor
	 */
	function Structure_acc()
	{
		$this->EE =& get_instance();

	    $results = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Structure'");

	    if ($results->num_rows > 0)
	        $this->installed = TRUE;
	
		if ($this->installed === FALSE)
			return;
		
		$this->structure = new Structure();
	}
	
	
	function set_sections()
	{
		if ($this->installed === FALSE)
		{
			$this->sections['Not Installed'] = "Structure is not installed.";
		}
		else
		{
			$this->sections['Assets'] = $this->get_assets(); 	
		}
	}
	
	
	/**
	 * Get Assets
	 *
	 * @access	public
	 * @return	string
	 */
	function get_assets()
	{		
		$data['theme_url'] = $this->EE->config->item('theme_folder_url') . 'third_party/structure';
		$data['asset_data']	= $this->structure->get_structure_channels('asset');
		
		return $this->EE->load->view('accessory', $data, TRUE);
	}
}