<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


// load dependencies
require_once PATH_THIRD.'assets/config.php';
require_once PATH_THIRD.'assets/helper.php';


/**
 * Assets Fieldtype
 *
 * @package   Assets
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Assets_ft extends EE_Fieldtype {

	var $info = array(
		'name'    => ASSETS_NAME,
		'version' => ASSETS_VER
	);

	var $has_array_data = TRUE;

	var $row_id; // Set by Matrix
	var $var_id; // Set by Low Variables


	/**
	 * A list of tags that support image manipulation
	 * @var array
	 */
	private $_manipulatable_tags = array(
		'url',
		'server_path',
		'subfolder',
		'filename',
		'extension',
		'date_modified',
		'kind',
		'width',
		'height',
		'size'
	);


	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

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
	 * Display Global Settings
	 */
	function display_global_settings()
	{
		if ($this->EE->addons_model->module_installed('assets'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=assets'.AMP.'method=settings');
		}
		else
		{
			$this->EE->lang->loadfile('assets');
			$this->EE->session->set_flashdata('message_failure', lang('no_module'));
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules');
		}
	}

	// --------------------------------------------------------------------

	private function _prep_settings(&$settings)
	{
		$settings = array_merge(array(
			'filedirs' => 'all',
			'multi'    => 'y',
			'view'     => 'thumbs',
			'show_cols' => array('name', 'folder', 'date', 'size')
		), $settings);
	}

	/**
	 * Field Settings
	 */
	private function _field_settings($data, $is_cell = FALSE)
	{
		// prep the settings
		$this->_prep_settings($data);

		// -------------------------------------------
		//  Include Resources
		// -------------------------------------------

		if (! isset($this->cache['included_resources']))
		{
			$this->helper->include_css('settings.css');
			$this->helper->include_js('settings.js');

			// load the language file
			$this->EE->lang->loadfile('assets');

			$this->cache['included_resources'] = TRUE;
		}

		// get all the file upload directories
		$filedirs = $this->EE->db->select('id, name')->from('upload_prefs')
		                         ->where('site_id', $this->EE->config->item('site_id'))
		                         ->order_by('name')
		                         ->get();

		return array(
			// File Upload Directories
			array(
				lang('file_upload_directories', 'assets_filedirs') . (! $is_cell ? '<br/>'.lang('file_upload_directories_info') : ''),
				$this->EE->load->view('field/settings-filedirs', array('data' => $data['filedirs'], 'filedirs' => $filedirs), TRUE)
			),

			// Allow multiple selections?
			array(
				lang('allow_multiple_selections', 'assets_multi'),
				form_radio('assets[multi]', 'y', ($data['multi'] == 'y'), 'id="assets_multi_y"') . NL
					. lang('yes', 'assets_multi_y') . NBS.NBS.NBS.NBS.NBS . NL
					. form_radio('assets[multi]', 'n', ($data['multi'] != 'y'), 'id="assets_multi_n"') . NL
					. lang('no', 'assets_multi_n')
			),

			// View
			array(
				lang('view', 'assets_view'),
				form_radio('assets[view]', 'thumbs', ($data['view'] == 'thumbs'), 'id="assets_view_thumbs" onchange="jQuery(this).parent().parent().next().children().hide()"') . NL
					. lang('thumbnails', 'assets_view_thumbs') . NBS.NBS.NBS.NBS.NBS . NL
					. form_radio('assets[view]', 'list', ($data['view'] != 'thumbs'), 'id="assets_view_list" onchange="jQuery(this).parent().parent().next().children().show()"') . NL
					. lang('list', 'assets_view_list')
			),

			// Show Columns
			array(
				array(
					'data' => lang('show_columns', 'assets_show_cols'),
					'style' => ($data['view'] == 'thumbs' ? 'display: none' : '')
				),
				array(
					'data' => form_hidden('assets[show_cols][]', 'name')
					       .  '<label>'.form_checkbox(NULL, NULL, TRUE, 'disabled="disabled"').NBS.NBS.lang('name').'</label><br/>' // Name isn't optional
					       .  '<label>'.form_checkbox('assets[show_cols][]', 'folder', in_array('folder', $data['show_cols'])).NBS.NBS.lang('folder').'</label><br/>'
					       .  '<label>'.form_checkbox('assets[show_cols][]', 'date',   in_array('date',   $data['show_cols'])).NBS.NBS.lang('date').'</label><br/>'
					       .  '<label>'.form_checkbox('assets[show_cols][]', 'size',   in_array('size',   $data['show_cols'])).NBS.NBS.lang('size').'</label><br/>',
					'style' => ($data['view'] == 'thumbs' ? 'display: none' : '')
				)
			)
		);
	}

	/**
	 * Display Field Settings
	 */
	function display_settings($data)
	{
		$rows = $this->_field_settings($data);

		foreach ($rows as $row)
		{
			if (isset($row['data']))
			{
				$this->EE->table->add_row($row);
			}
			else
			{
				$this->EE->table->add_row($row[0], $row[1]);
			}
		}
	}

	/**
	 * Display Cell Settings
	 */
	function display_cell_settings($data)
	{
		$rows = $this->_field_settings($data, TRUE);

		$r = '<table class="matrix-col-settings" cellspacing="0" cellpadding="0" border="0">';

		$total_cell_settings = count($rows);

		foreach ($rows as $key => $row)
		{
			$tr_class = '';
			if ($key == 0) $tr_class .= ' matrix-first';
			if ($key == $total_cell_settings-1) $tr_class .= ' matrix-last';

			$r .= "<tr class=\"{$tr_class}\">";

			foreach ($row as $j => $cell)
			{
				if (! is_array($cell))
				{
					$cell = array('data' => $cell);
				}

				if ($j == 0)
				{
					$tag = 'th';
					$attr = 'class="matrix-first"';
				}
				else
				{
					$tag = 'td';
					$attr = 'class="matrix-last"';
				}

				if (isset($cell['style']))
				{
					$attr .= " style=\"{$cell['style']}\"";
				}

				$r .= "<{$tag} {$attr}>{$cell['data']}</{$tag}>";
			}

			$r .= '</tr>';
		}

		$r .= '</table>';

		return $r;
	}

	/**
	 * Display Variable Settings
	 */
	function display_var_settings($data)
	{
		if (! $this->var_id)
		{
			return array(
				array('', 'Assets requires Low Variables 1.3.7 or later.')
			);
		}

		$this->helper->add_package_path();

		$r = $this->_field_settings($data);

		// Low Variables doesn't support passing arrays for each cell,
		// so we'll have to manually hide the last row with JS
		$r[3][0] = $r[3][0]['data'];
		$r[3][1] = $r[3][1]['data'];

		if (! isset($data['view']) || $data['view'] == 'thumbs')
		{
			$this->helper->insert_js('jQuery("#assets > tbody > tr:last-child > td").hide();');
		}

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Save Field Settings
	 */
	function save_settings($data)
	{
		$settings = $this->EE->input->post('assets');

		// cross the T's
		$settings['field_fmt'] = 'none';
		$settings['field_show_fmt'] = 'n';
		$settings['field_type'] = 'assets';

		return $settings;
	}

	/**
	 * Save Cell Settings
	 */
	function save_cell_settings($settings)
	{
		$settings = $settings['assets'];

		return $settings;
	}

	/**
	 * Save Variable Settings
	 */
	function save_var_settings()
	{
		return $this->EE->input->post('assets');
	}

	// --------------------------------------------------------------------

	/**
	 * Migrate Field Data
	 */
	private function _migrate_field_data($field_data, $entry_data)
	{
		$file_paths = array_filter(preg_split('/[\r\n]/', $field_data));

		foreach ($file_paths as $asset_order => $file_path)
		{
			// ignore if not a valid file path
			$this->helper->parse_filedir_path($file_path, $filedir, $subpath);
			if (! $filedir || ! $subpath) continue;

			// do we already have a record of this asset?
			$asset = $this->EE->db->select('asset_id')
			                      ->where('file_path', $file_path)
			                      ->get('assets');

			if ($asset->num_rows())
			{
				// use the existing asset_id
				$asset_id = $asset->row('asset_id');
			}
			else
			{
				// add it
				$this->EE->db->insert('assets', array('file_path' => $file_path));

				// get the new asset_id
				$asset_id = $this->EE->db->insert_id();
			}

			// save the association in exp_assets_entries
			$this->EE->db->insert('assets_entries', array_merge($entry_data, array(
				'asset_id'    => $asset_id,
				'asset_order' => $asset_order
			)));
		}
	}

	/**
	 * Modify exp_channel_data Column Settings
	 */
	function settings_modify_column($data)
	{
		// is this a new Assets field?
		if ($data['ee_action'] == 'add')
		{
			$field_id = $data['field_id'];
			$field_name = 'field_id_'.$field_id;

			// is this an existing field?
			if ($this->EE->db->field_exists($field_name, 'channel_data'))
			{
				$entries = $this->EE->db->select("entry_id, {$field_name}")
				                        ->where("{$field_name} LIKE '{filedir_%'")
				                        ->where("{$field_name} != ", '')
				                        ->get('channel_data');

				foreach ($entries->result() as $entry)
				{
					$this->_migrate_field_data($entry->$field_name, array(
						'entry_id' => $entry->entry_id,
						'field_id' => $field_id
					));
				}
			}
		}
		else if ($data['ee_action'] == 'delete')
		{
			// delete any asset associations created by this field
			$this->EE->db->where('field_id', $data['field_id'])
			             ->delete('assets_entries');
		}

		// just return the default column settings
		return parent::settings_modify_column($data);
	}

	/**
	 * Modify exp_matrix_data Column Settings
	 */
	function settings_modify_matrix_column($data)
	{
		// is this a new Assets column?
		if ($data['matrix_action'] == 'add')
		{
			$field_id = $this->EE->input->post('field_id');
			$col_id = $data['col_id'];
			$col_name = 'col_id_'.$col_id;

			// is this an existing field?
			if ($field_id && $this->EE->db->field_exists($col_name, 'matrix_data'))
			{
				$rows = $this->EE->db->select("entry_id, row_id, {$col_name}")
				                     ->where("{$col_name} LIKE '{filedir_%'")
				                     ->where("{$col_name} != ", '')
				                     ->get('matrix_data');

				foreach ($rows->result() as $row)
				{
					$this->_migrate_field_data($row->$col_name, array(
						'entry_id' => $row->entry_id,
						'field_id' => $field_id,
						'col_id'   => $col_id,
						'row_id'   => $row->row_id,
					));
				}
			}
		}
		else if ($data['matrix_action'] == 'delete')
		{
			// delete any asset associations created by this column
			$this->EE->db->where('col_id', $data['col_id'])
			             ->delete('assets_entries');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field
	 */
	function display_field($data)
	{
		// include the resources
		$this->helper->include_sheet_resources();

		// prep the settings
		$this->_prep_settings($this->settings);

		// -------------------------------------------
		//  Field HTML
		// -------------------------------------------

		if ($is_cell = isset($this->cell_name))
		{
			$vars['field_name'] = $this->cell_name;
			$vars['field_id'] = str_replace(array('[',']'), array('_',''), $this->cell_name);
		}
		else if ($this->var_id)
		{
			$vars['field_name'] = $this->field_name;
			$vars['field_id'] = str_replace(array('[',']'), array('_',''), $this->field_name);
		}
		else
		{
			$vars['field_name'] = $vars['field_id'] = $this->field_name;
		}

		$entry_id = $this->EE->input->get('entry_id');

		$vars['files'] = array();

		// has this Assets field already been saved?
		if (($this->var_id || $entry_id) && (! $is_cell || $this->row_id))
		{
			$entry_id = $this->EE->security->xss_clean($entry_id);

			$sql = "SELECT DISTINCT a.asset_id, a.file_path
			        FROM exp_assets a
			        INNER JOIN exp_assets_entries ae ON ae.asset_id = a.asset_id
			        WHERE";

			if ($this->var_id)
			{
				$sql .= " ae.var_id = {$this->var_id}";
			}
			else
			{
				$sql .= " ae.entry_id = '{$entry_id}'
				          AND ae.field_id = '{$this->field_id}'";
			}

			if ($is_cell)
			{
				$sql .= " AND ae.col_id = '{$this->col_id}'
				          AND ae.row_id = '{$this->row_id}'";
			}

			$sql .= ' ORDER BY ae.asset_order';

			if ($this->settings['multi'] == 'n')
			{
				$sql .= ' LIMIT 1';
			}

			$query = $this->EE->db->query($sql);

			$data = array();

			foreach ($query->result() as $row)
			{
				$data[] = $row->file_path;
			}
		}

		if (is_array($data))
		{
			foreach ($data as $file_path)
			{
				$file = $this->helper->get_file($file_path);

				if ($file->exists())
				{
					$vars['files'][] = $file;
				}
			}
		}

		$vars['multi'] = ($this->settings['multi'] == 'y');

		$vars['helper'] = $this->helper;
		$vars['show_cols'] = $this->settings['show_cols'];

		if ($this->settings['view'] == 'thumbs')
		{
			// load the filemanager library and file helper for generating thumbs
			$this->EE->load->library('filemanager');
			$this->EE->load->helper('file');

			$vars['file_view'] = 'thumbview/thumbview';
		}
		else
		{
			$vars['file_view'] = 'listview/listview';
		}

		$r = $this->EE->load->view('field/field', $vars, TRUE);

		// Add a hidden input in case no files are selected
		$r .= '<input type="hidden" name="'.($is_cell ? $this->cell_name : $this->field_name).'[]" value="" />';

		// -------------------------------------------
		//  Pass field settings to JS
		// -------------------------------------------

		$settings_json = $this->EE->javascript->generate_json(array(
			'filedirs'  => $this->settings['filedirs'],
			'multi'     => $vars['multi'],
			'view'      => $this->settings['view'],
			'show_cols' => $this->settings['show_cols']
		), TRUE);

		if ($is_cell)
		{
			$this->helper->insert_js('Assets.Field.matrixConfs.col_id_'.$this->col_id.' = '.$settings_json.';');
		}
		else
		{
			$this->helper->insert_js('new Assets.Field(jQuery("#'.$vars['field_id'].'"), "'.$this->field_name.'", '.$settings_json.');');
		}

		return $r;
	}

	/**
	 * Display Cell
	 */
	function display_cell($data)
	{
		// include the resources
		$this->helper->include_sheet_resources();

		if (! isset($this->cache['included_matrix_resources']))
		{
			$this->helper->include_js('matrix.js');

			$this->cache['included_matrix_resources'] = TRUE;
		}

		return array(
			'data' => $this->display_field($data),
			'class' => 'assets'
		);
	}

	/**
	 * Display Variable Field
	 */
	function display_var_field($data)
	{
		if (! $this->var_id) return;

		$this->helper->add_package_path();

		return $this->display_field($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Validate
	 */
	function validate($data)
	{
		// is this a required field?
		if ($this->settings['field_required'] == 'y' && ! (is_array($data) && array_filter($data)))
		{
			return lang('required');
		}

		return TRUE;
	}

	/**
	 * Validate Cell
	 */
	function validate_cell($data)
	{
		// is this a required cell?
		if ($this->settings['col_required'] == 'y' && ! (is_array($data) && array_filter($data)))
		{
			return lang('col_required');
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Selections
	 *
	 * Takes the list of file selections coming from the publish field,
	 * creates asset records for any files that don't have one yet,
	 * and returns the list of asset_ids
	 *
	 * @param array $selections
	 * @return array
	 */
	private function _prep_selections($selections)
	{
		// filter out the empty input
		$selections = array_filter($selections);

		foreach ($selections as $key => $file)
		{
			// is this a file path?
			if (! ctype_digit($file))
			{
				$data = array('file_path' => $file);

				// do we already have a record of it?
				$query = $this->EE->db->select('asset_id')->where($data)->get('assets');

				if ($query->num_rows())
				{
					// just replace the file path with the asset id
					$selections[$key] = $query->row('asset_id');
				}
				else
				{
					// create a new asset record
					$this->EE->db->insert('assets', $data);

					$selections[$key] = $this->EE->db->insert_id();
				}
			}
		}

		return $selections;
	}

	/**
	 * Get Filenames
	 */
	private function _get_filenames($asset_ids)
	{
		$file_names = array();

		if ($asset_ids)
		{
			$query = $this->EE->db->select('file_path')
			                      ->where_in('asset_id', $asset_ids)
			                      ->get('assets');

			foreach ($query->result() as $asset)
			{
				$file_names[] = $asset->file_path;
			}
		}

		return implode("\n", $file_names);
	}

	/**
	 * Save
	 */
	function save($data)
	{
		// ignore if it doesn't look like Assets data
		if (! is_array($data)) return;

		$selections = $this->_prep_selections($data);

		// save the post data for later
		$this->cache['selections'][$this->field_id] = $selections;

		// return the filenames
		return $this->_get_filenames($selections);
	}

	/**
	 * Save Cell
	 */
	function save_cell($data)
	{
		// ignore if it doesn't look like Assets data
		if (! is_array($data)) return;

		$selections = $this->_prep_selections($data);

		// save the post data for later
		$id = ($this->var_id ? $this->var_id : $this->field_id);
		$this->cache['selections'][$id][$this->settings['col_id']][$this->settings['row_name']] = $selections;

		// return the filenames
		return $this->_get_filenames($selections);
	}

	/**
	 * Save Variable Field
	 */
	function save_var_field($data)
	{
		if (! $this->var_id) return;

		// ignore if it doesn't look like Assets data
		if (! is_array($data)) return;

		$selections = $this->_prep_selections($data);

		$data = array(
			'var_id' => $this->var_id
		);

		// save the changes
		$this->_save_selections($selections, $data);

		// return the filenames
		return $this->_get_filenames($selections);
	}

	// --------------------------------------------------------------------

	/**
	 * Save Selections
	 */
	private function _save_selections($selections, $data)
	{
		// delete previous selections
		$this->EE->db->where($data)
		             ->delete('assets_entries');

		if ($selections)
		{
			foreach ($selections as $asset_order => $asset_id)
			{
				$selection_data = array_merge($data, array(
					'asset_id'    => $asset_id,
					'asset_order' => $asset_order
				));

				$this->EE->db->insert('assets_entries', $selection_data);
			}
		}
	}

	/**
	 * Post Save
	 */
	function post_save($data)
	{
		// ignore if we didn't cache the selections
		if (! isset($this->cache['selections'][$this->field_id])) return;

		// get the selections from the cache
		$selections = $this->cache['selections'][$this->field_id];

		$data = array(
			'entry_id' => $this->settings['entry_id'],
			'field_id' => $this->field_id
		);

		// save the changes
		$this->_save_selections($selections, $data);
	}

	/**
	 * Post Save Cell
	 */
	function post_save_cell($data)
	{
		$id = ($this->var_id ? $this->var_id : $this->field_id);

		// ignore if we didn't cache the selections
		if (! isset($this->cache['selections'][$id][$this->settings['col_id']][$this->settings['row_name']])) return;

		// get the selections from the cache
		$selections = $this->cache['selections'][$id][$this->settings['col_id']][$this->settings['row_name']];

		$data = array(
			'col_id'   => $this->settings['col_id'],
			'row_id'   => $this->settings['row_id']
		);

		if ($this->var_id)
		{
			$data['var_id'] = $this->var_id;
		}
		else
		{
			$data['entry_id'] = $this->settings['entry_id'];
			$data['field_id'] = $this->field_id;
		}

		// save the changes
		$this->_save_selections($selections, $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Entries
	 */
	function delete($entry_ids)
	{
		$this->EE->db->where_in('entry_id', $entry_ids)
		             ->delete('assets_entries');
	}

	/**
	 * Delete Rows
	 */
	function delete_rows($row_ids)
	{
		$this->EE->db->where_in('row_id', $row_ids)
		             ->delete('assets_entries');
	}

	/**
	 * Delete Variable
	 */
	function delete_var($var_id)
	{
		$this->EE->db->where('var_id', $var_id)
		             ->delete('assets_entries');
	}

	// --------------------------------------------------------------------

	/**
	 * Pre Process
	 */
	function pre_process()
	{
		// -------------------------------------------
		//  Get the exp_assets_entries rows
		// -------------------------------------------

		// did the extension get called?
		if (isset($this->cache['assets_entries_rows']) && ! $this->row_id && ! $this->var_id)
		{
			if (isset($this->cache['assets_entries_rows'][$this->row['entry_id']][$this->field_id]))
			{
				$rows = $this->cache['assets_entries_rows'][$this->row['entry_id']][$this->field_id];
			}
			else
			{
				$rows = array();
			}
		}
		else
		{
			$sql = 'SELECT DISTINCT a.asset_id, a.* FROM exp_assets a
			        INNER JOIN exp_assets_entries ae ON ae.asset_id = a.asset_id';

			if ($this->var_id)
			{
				$sql .= ' WHERE ae.var_id = '.$this->var_id;
			}
			else
			{
				$sql .= ' WHERE ae.entry_id = "'.$this->row['entry_id'].'"
				            AND ae.field_id = "'.$this->field_id.'"';
			}

			if ($this->row_id)
			{
				$sql .= ' AND ae.col_id = "'.$this->col_id.'" AND ae.row_id = "'.$this->row_id.'"';
			}

			$sql .= ' ORDER BY ae.asset_order';

			$rows = $this->EE->db->query($sql)
			                     ->result_array();
		}

		// -------------------------------------------
		//  Get the files
		// -------------------------------------------

		$files = array();

		foreach ($rows as $row)
		{
			$file = $this->helper->get_file($row['file_path']);

			if ($file->exists())
			{
				$file->set_row($row);

				$files[] = $file;
			}
		}

		return $files;
	}

	// --------------------------------------------------------------------

	/**
	 * Apply Params
	 */
	private function _apply_params(&$data, $params)
	{
		// ignore if there are no selected files
		if (! $data) return;

		// -------------------------------------------
		//  Orderby and Sort
		// -------------------------------------------

		if (isset($params['orderby']))
		{
			$orderbys = explode('|', $params['orderby']);
			$sorts = isset($params['sort']) ? explode('|', $params['sort']) : array();

			foreach ($orderbys as $i => $orderby)
			{
				foreach ($data as $file)
				{
					$ms_arrays[$orderby][] = strtolower($file->$orderby());
				}

				$ms_params[] = $ms_arrays[$orderby];
				$ms_params[] = (isset($sorts[$i]) && $sorts[$i] == 'desc') ? SORT_DESC : SORT_ASC;
			}

			$ms_params[] =& $data;

			call_user_func_array('array_multisort', $ms_params);
		}

		else if (isset($params['sort']))
		{
			switch ($params['sort'])
			{
				case 'desc':
					$data = array_reverse($data);
					break;

				case 'random':
					shuffle($data);
					break;
			}
		}

		// -------------------------------------------
		//  Search filter params
		// -------------------------------------------

		$prop_params = array('server_path', 'subfolder', 'filename', 'extension', 'date_modified', 'kind', 'width', 'height', 'size');
		$meta_params = array_keys($data[0]->row());
		$search_params = array_merge($prop_params, $meta_params);

		foreach ($search_params as $param)
		{
			if (isset($params[$param]) && ($val = $params[$param]))
			{
				// exact match?
				if ($exact = (strncmp($val, '=', 1) == 0))
				{
					$val = substr($val, 1);
				}

				// negative match?
				if ($not = (strncmp($val, 'not ', 4) == 0))
				{
					$val = substr($val, 4);
				}

				// all required?
				$all_required = (strpos($val, '&&') !== FALSE);

				// get individual terms
				$conj = $all_required ? '&&' : '|';
				$terms = explode($conj, $val);

				foreach ($data as $i => $file)
				{
					$include_file = $all_required;

					foreach ($terms as $term)
					{
						// get the actual value
						$actual_val = in_array($param, $prop_params) ? $file->$param() : $file->row($param);

						// comparison match?
						if (preg_match('/^[<>]=?/', $term, $m))
						{
							$term = substr($term, strlen($m[0]));
							eval('$match = ($actual_val && ($actual_val '.$m[0].' $term));');
						}
						else
						{
							// looking for empty?
							if ($empty = ($term == 'IS_EMPTY'))
							{
								$term = '';
							}

							// exact match?
							if ($exact || $empty)
							{
								$match = (strcasecmp($actual_val, $term) == 0);
							}
							else
							{
								$match = (stripos($actual_val, $term) !== FALSE);
							}
						}

						// if all are required, exclude the file on the first non-match
						if ($all_required && ! $match)
						{
							$include_file = false;
							break;
						}

						// if one is required, include the file on the first match
						if (! $all_required && $match)
						{
							$include_file = true;
							break;
						}
					}

					// remove the file from the $data array if it should be excluded
					if ($not == $include_file)
					{
						array_splice($data, $i, 1);
					}
				}
			}
		}

		// -------------------------------------------
		//  Offset and Limit
		// -------------------------------------------

		if (isset($params['offset']) || isset($params['limit']))
		{
			$offset = isset($params['offset']) ? (int) $params['offset'] : 0;
			$limit  = isset($params['limit'])  ? (int) $params['limit']  : count($data);

			$data = array_splice($data, $offset, $limit);
		}
	}

	/**
	 * Filter by Kind
	 */
	private function _filter_by_kind($file)
	{
		return in_array($file->kind(), $this->_kinds);
	}

	// --------------------------------------------------------------------

	/**
	 * Replace Tag
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		// return the full URL if there's no tagdata
		if (! $tagdata) return $this->replace_url($data, $params);

		$var_prefix = (isset($params['var_prefix']) && $params['var_prefix']) ? rtrim($params['var_prefix'], ':') . ':' : '';

		// get the absolute number of files before we run the filters
		$vars[$var_prefix.'absolute_total_files'] = count($data);

		$this->_apply_params($data, $params);
		if (! $data) return;

		// get the filtered number of files
		$vars[$var_prefix.'total_files'] = count($data);

		// parse {total_files} and {absolute_total_files} now, since they'll never change
		$tagdata = $this->EE->TMPL->parse_variables_row($tagdata, $vars);

		// load a list of additional image sizes to fetch

		$manipulation_tags = array();
		$pattern = '/\{(' . join('|', $this->_manipulatable_tags) . '):([a-z\-_0-9]+)\}/';

		if (preg_match_all($pattern, $tagdata, $matches))
		{
			foreach ($matches[2] as $i => $manipulation_name)
			{
				$manipulation_tags[$manipulation_name][] = $matches[1][$i];
			}
		}

		$vars = array();
		foreach ($data as $file)
		{

			$file_vars = array(
				$var_prefix.'url'           => $file->url(),
				$var_prefix.'server_path'   => $file->server_path(),
				$var_prefix.'subfolder'     => $file->subfolder(),
				$var_prefix.'filename'      => $file->filename(),
				$var_prefix.'extension'     => $file->extension(),
				$var_prefix.'date_modified' => $file->date_modified(),
				$var_prefix.'kind'          => $file->kind(),
				$var_prefix.'width'         => $file->width(),
				$var_prefix.'height'        => $file->height(),
				$var_prefix.'size'          => $this->helper->format_filesize($file->size())
			);

			// add additional image sizes.
			foreach ($manipulation_tags as $manipulation_name => $mtags)
			{
				// create the filename for target file
				$size_filename = '_' . $manipulation_name . '/' . $file->filename();

				// cache to avoid overhead, if possible
				if ( ! isset($this->cache['file_objects'][$size_filename]))
				{
					if (file_exists($file->filedir_path() . $size_filename))
					{
						// Assets_file expects the path in it's own way, so we give it to it
						$resized_file = new Assets_file('{filedir_' . $file->filedir_id() . '}/' . $size_filename);
					}
					else
					{
						$resized_file = false;
					}

					$this->cache['file_objects'][$size_filename] = $resized_file;
				}
				else
				{
					$resized_file = $this->cache['file_objects'][$size_filename];
				}

				// add info for all tags that are using this manipulation
				foreach ($mtags as $tag)
				{
					if ($resized_file !== false && method_exists($resized_file, $tag))
					{
						switch ($tag)
						{
							case 'size':
							{
								$val = $this->helper->format_filesize($resized_file->size());
								break;
							}
							default:
							{
								$val = $resized_file->$tag();
							}
						}
					}
					else
					{
						$val = '';
					}

					$file_vars[$var_prefix.$tag.':'.$manipulation_name] = $val;
				}
			}

			// load in asset_id, title, date, etc.
			foreach ($file->row() as $key => $val)
			{
				$file_vars[$var_prefix.$key] = $val;
			}

			$vars[] = $file_vars;
		}

		$r = $this->EE->TMPL->parse_variables($tagdata, $vars);

		// -------------------------------------------
		//  Backspace param
		// -------------------------------------------

		if (isset($params['backspace']))
		{
			$chop = strlen($r) - $params['backspace'];
			$r = substr($r, 0, $chop);
		}

		return $r;
	}


	/**
	 * Catches {my_assets_field:manipulation} calls
	 * @param Assets_file $file_info
	 * @param array $params
	 * @param bool $tagdata
	 * @param $modifier
	 * @return bool|string
	 */
	function replace_tag_catchall($file_info, $params = array(), $tagdata = FALSE, $modifier = '')
	{
		if ($modifier && is_array($file_info))
		{
			$file_info_object = array_shift($file_info);

			// theoretically this should check out every time but let's make sure
			if ($file_info_object instanceof Assets_file)
			{
				$size_filename = '_' . $modifier . '/' . $file_info_object->filename();

				if (file_exists($file_info_object->filedir_path() . $size_filename))
				{
					// ah, the old bait-and-switch
					$file_info_object = new Assets_file('{filedir_' . $file_info_object->filedir_id() . '}/' . $size_filename);
					array_unshift($file_info, $file_info_object);
				}
				else
				{
					return false;
				}
			}
		}

		return $this->replace_tag($file_info, $params, $tagdata);

	}

	/**
	 * Display Variable Tag
	 */
	function display_var_tag($data)
	{
		if (! $this->var_id) return;

		$data = $this->pre_process($data);
		return $this->replace_tag($data, $this->EE->TMPL->tagparams, $this->EE->TMPL->tagdata);
	}

	// --------------------------------------------------------------------

	/**
	 * Replace Tag
	 */
	function replace_total_files($data, $params)
	{
		$this->_apply_params($data, $params);

		return (string) count($data);
	}

	/**
	 * Replace URL
	 */
	function replace_url($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->url();
	}

	/**
	 * Replace Server Path
	 */
	function replace_server_path($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->server_path();
	}

	/**
	 * Replace Subfolder
	 */
	function replace_subfolder($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->subfolder();
	}

	/**
	 * Replace Filename
	 */
	function replace_filename($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->filename();
	}

	/**
	 * Replace Extenison
	 */
	function replace_extension($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->extension();
	}

	/**
	 * Replace Date Modified
	 */
	function replace_date_modified($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->date_modified();
	}

	/**
	 * Replace Kind
	 */
	function replace_kind($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->kind();
	}

	/**
	 * Replace Width
	 */
	function replace_width($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->width();
	}

	/**
	 * Replace Height
	 */
	function replace_height($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->height();
	}

	/**
	 * Replace Size
	 */
	function replace_size($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $this->helper->format_filesize($data[0]->size());
	}

	/**
	 * Replace Asset Id
	 */
	function replace_asset_id($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->row('asset_id');
	}

	/**
	 * Replace Title
	 */
	function replace_title($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->row('title');
	}

	/**
	 * Replace Date
	 */
	function replace_date($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->row('date');
	}

	/**
	 * Replace Alt Text
	 */
	function replace_alt_text($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->row('alt_text');
	}

	/**
	 * Replace Caption
	 */
	function replace_caption($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->row('caption');
	}

	/**
	 * Replace Author
	 */
	function replace_author($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->row('author');
	}

	/**
	 * Replace Description
	 */
	function replace_desc($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->row('desc');
	}

	/**
	 * Replace Location
	 */
	function replace_location($data, $params)
	{
		$this->_apply_params($data, $params);
		if (! $data) return;

		return $data[0]->row('location');
	}

}
