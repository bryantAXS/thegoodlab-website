<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


// load dependencies
require_once PATH_THIRD.'assets/config.php';
require_once PATH_THIRD.'assets/helper.php';


/**
 * Assets Extension
 *
 * @package   Assets
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Assets_ext {

	var $name           = ASSETS_NAME;
	var $version        = ASSETS_VER;
	var $description    = ASSETS_DESC;
	var $docs_url       = ASSETS_DOCS;
	var $settings_exist = 'n';

	/**
	 * Constructor
	 */
	function __construct()
	{
		// -------------------------------------------
		//  Make a local reference to the EE super object
		// -------------------------------------------

		$this->EE =& get_instance();

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------

		if (! isset($this->EE->session->cache['assets']))
		{
			$this->EE->session->cache['assets'] = array();
		}

		$this->cache =& $this->EE->session->cache['assets'];

		// -------------------------------------------
		//  Get helper
		// -------------------------------------------

		$this->helper = get_assets_helper();
	}

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 */
	function activate_extension()
	{
		// -------------------------------------------
		//  Add the row to exp_extensions
		// -------------------------------------------

		$this->EE->db->insert('extensions', array(
			'class'    => 'Assets_ext',
			'method'   => 'channel_entries_query_result',
			'hook'     => 'channel_entries_query_result',
			'settings' => '',
			'priority' => 10,
			'version'  => ASSETS_VER,
			'enabled'  => 'y'
		));
	}

	/**
	 * Update Extension
	 */
	function update_extension($current = NULL)
	{
		// All updates are handled by the module,
		// so there's nothing to change here
		return FALSE;
	}

	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		// -------------------------------------------
		//  Remove the row from exp_extensions
		// -------------------------------------------

		$this->EE->db->where('class', 'Assets_ext')
		             ->delete('extensions');
	}

	// --------------------------------------------------------------------

	/**
	 * channel_entries_query_result
	 */
	function channel_entries_query_result($Channel, $query_result)
	{
		// -------------------------------------------
		//  Get the latest version of $query_result
		// -------------------------------------------

		if ($this->EE->extensions->last_call !== FALSE)
		{
			$query_result = $this->EE->extensions->last_call;
		}

		if ($query_result)
		{
			// -------------------------------------------
			//  Get all of the Assets fields that belong to entries' sites
			// -------------------------------------------

			$all_assets_fields = array();

			foreach ($this->EE->TMPL->site_ids as $site_id)
			{
				if (isset($Channel->pfields[$site_id]))
				{
					foreach ($Channel->pfields[$site_id] as $field_id => $field_type)
					{
						if ($field_type == 'assets')
						{
							// Now get the field name
							if (($field_name = array_search($field_id, $Channel->cfields[$site_id])) !== FALSE)
							{
								$all_assets_fields[$field_id] = $field_name;
							}
						}
					}
				}
			}

			if ($all_assets_fields)
			{
				// -------------------------------------------
				//  Figure out which of those fields are being used in this template
				// -------------------------------------------

				$tmpl_fields = array_merge(
					array_keys($this->EE->TMPL->var_single),
					array_keys($this->EE->TMPL->var_pair)
				);

				$tmpl_assets_fields = array();

				foreach ($tmpl_fields as $field)
				{
					// Get the actual field name, sans tag func name and parameters
					preg_match('/^[\w\d-]*/', $field, $m);
					$field_name = $m[0];

					if (($field_id = array_search($field_name, $all_assets_fields)) !== FALSE)
					{
						if (! in_array($field_id, $tmpl_assets_fields))
						{
							$tmpl_assets_fields[] = $field_id;
						}
					}
				}

				if ($tmpl_assets_fields)
				{
					// -------------------------------------------
					//  Get each of the entry IDs
					// -------------------------------------------

					$entry_ids = array();

					foreach ($query_result as $entry)
					{
						if (! empty($entry['entry_id']))
						{
							$entry_ids[] = $entry['entry_id'];
						}
					}

					// -------------------------------------------
					//  Get all of the exp_assets_entries rows that will be needed
					// -------------------------------------------

					// Set it first so that if there are simply no files selected,
					// the fieldtype still knows the extension was called
					$this->cache['assets_entries_rows'] = array();

					if ($entry_ids)
					{
						$query = $this->EE->db->query('SELECT DISTINCT a.asset_id, a.*, ae.* FROM exp_assets a
						                               INNER JOIN exp_assets_entries ae ON ae.asset_id = a.asset_id
						                               WHERE ae.entry_id IN ('.implode(',', $entry_ids).')
						                                 AND ae.field_id IN ('.implode(',', $tmpl_assets_fields).')
						                               ORDER BY ae.asset_order');

						foreach ($query->result_array() as $row)
						{
							$this->cache['assets_entries_rows'][$row['entry_id']][$row['field_id']][] = $row;
						}
					}
				}
			}
		}

		return $query_result;
	}

}
