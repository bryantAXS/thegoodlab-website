<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


require_once PATH_THIRD.'assets/config.php';


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

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		/** ----------------------------------------
		/**  Prepare Cache
		/** ----------------------------------------*/

		if (! isset($this->EE->session->cache['assets']))
		{
			$this->EE->session->cache['assets'] = array();
		}

		$this->cache =& $this->EE->session->cache['assets'];

		// -------------------------------------------
		//  Get helper
		// -------------------------------------------

		if (! class_exists('Assets_helper'))
		{
			require_once PATH_THIRD.'assets/helper.php';
		}

		$this->helper = new Assets_helper;
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
	private function _field_settings($data, $cell)
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
				lang('file_upload_directories', 'assets_filedirs') . (! $cell ? '<br/>'.lang('file_upload_directories_info') : ''),
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
		$rows = $this->_field_settings($data, FALSE);

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
	function display_field()
	{
		// include the resources
		$this->helper->include_sheet_resources();

		// prep the settings
		$this->_prep_settings($this->settings);

		// -------------------------------------------
		//  Field HTML
		// -------------------------------------------

		if ($cell = isset($this->cell_name))
		{
			$vars['field_name'] = $this->cell_name;
			$vars['field_id'] = str_replace(array('[', ']'), array('_', ''), $this->cell_name);
		}
		else
		{
			$vars['field_name'] = $vars['field_id'] = $this->field_name;
		}

		$vars['files'] = array();

		// is this an existing entry, and if within a Matrix field, is this an existing row?
		if (($entry_id = $this->EE->input->get('entry_id')) && (! isset($this->cell_name) || isset($this->row_id)))
		{
			$entry_id = $this->EE->security->xss_clean($entry_id);

			$sql = "SELECT a.asset_id, a.file_path
			        FROM exp_assets a
			        INNER JOIN exp_assets_entries ae ON ae.asset_id = a.asset_id
			        WHERE ae.entry_id = '{$entry_id}'
			          AND ae.field_id = '{$this->field_id}'";

			if ($cell)
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

			foreach ($query->result_array() as $file)
			{
				// make sure the file still exists
				$this->helper->parse_filedir_path($file['file_path'], $filedir, $subpath);

				if ($filedir)
				{
					$full_path = $this->helper->get_server_path($filedir) . $subpath;

					if (file_exists($full_path))
					{
						$vars['files'][] = $file;
					}
				}
			}
		}

		$vars['multi'] = ($this->settings['multi'] == 'y');

		$vars['helper'] = $this->helper;
		$vars['show_cols'] = $this->settings['show_cols'];

		if ($this->settings['view'] == 'thumbs')
		{
			$this->EE->load->library('filemanager');
			$this->EE->load->helper('file');
			$vars['file_view'] = 'thumbview/thumbview';
		}
		else
		{
			$vars['file_view'] = 'listview/listview';
		}

		$r = $this->EE->load->view('field/field', $vars, TRUE);

		// -------------------------------------------
		//  Pass field settings to JS
		// -------------------------------------------

		$settings_json = $this->EE->javascript->generate_json(array(
			'filedirs'  => $this->settings['filedirs'],
			'multi'     => $vars['multi'],
			'view'      => $this->settings['view'],
			'show_cols' => $this->settings['show_cols']
		), TRUE);

		if ($cell)
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
			'data' => $this->display_field($data, TRUE),
			'class' => 'assets'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Selections
	 *
	 * Takes the list of file selections coming from the publish field,
	 * creates asset records for any files that don't have one yet,
	 * and returns the list of asset_ids
	 */
	private function _prep_selections(&$selections)
	{
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
	 * Save Field
	 */
	function save($data)
	{
		// ignore everything but the selections
		$selections = is_array($data) ? array_merge(array_filter($data)) : array();

		$this->_prep_selections($selections);

		// save the post data for later
		$this->cache['selections'][$this->settings['field_id']] = $selections;

		// return the filenames
		return $this->_get_filenames($selections);
	}

	/**
	 * Save Cell
	 */
	function save_cell($data)
	{
		// ignore everything but the selections
		$selections = is_array($data) ? array_merge(array_filter($data)) : array();

		$this->_prep_selections($selections);

		// save the post data for later
		$this->cache['selections'][$this->settings['field_id']][$this->settings['col_id']][$this->settings['row_name']] = $selections;

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
		// make sure this should have been called in the first place
		if (! isset($this->cache['selections'][$this->settings['field_id']])) return;

		// get the selections from the cache
		$selections = $this->cache['selections'][$this->settings['field_id']];

		$data = array(
			'entry_id' => $this->settings['entry_id'],
			'field_id' => $this->settings['field_id']
		);

		// save the changes
		$this->_save_selections($selections, $data);
	}

	/**
	 * Post Save Cell
	 */
	function post_save_cell($data)
	{
		// get the selections from the cache
		$selections = $this->cache['selections'][$this->settings['field_id']][$this->settings['col_id']][$this->settings['row_name']];

		$data = array(
			'entry_id' => $this->settings['entry_id'],
			'field_id' => $this->settings['field_id'],
			'col_id'   => $this->settings['col_id'],
			'row_id'   => $this->settings['row_id']
		);

		// save the changes
		$this->_save_selections($selections, $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Pre Process
	 */
	function pre_process()
	{
		$sql = 'SELECT a.* FROM exp_assets a
		        INNER JOIN exp_assets_entries ae ON ae.asset_id = a.asset_id
		        WHERE ae.entry_id = "'.$this->row['entry_id'].'"
		          AND ae.field_id = "'.$this->field_id.'"';

		if (isset($this->row_id))
		{
			$sql .= ' AND ae.col_id = "'.$this->col_id.'" AND ae.row_id = "'.$this->row_id.'"';
		}

		$sql .= ' ORDER BY ae.asset_order';

		$assets = $this->EE->db->query($sql);

		$files = array();

		foreach ($assets->result_array() as $asset)
		{
			$this->helper->parse_filedir_path($asset['file_path'], $filedir, $path);

			if ($path)
			{
				$asset['full_path'] = $this->helper->get_server_path($filedir) . $path;

				if (file_exists($asset['full_path']))
				{
					$asset['filedir'] = $filedir;
					$asset['path'] = $path;

					$asset['kind'] = $this->helper->get_kind($asset['full_path']);

					$files[] = $asset;
				}
			}
		}

		return $files;
	}

	/**
	 * Replace Tag
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		// return the full URL if there's no tagdata
		if (! $tagdata) return $this->replace_url($data, $params);

		$total_files = count($data);

		$vars = array();

		foreach ($data as $asset)
		{
			if ($asset['kind'] == 'image') $image_size = getimagesize($asset['full_path']);

			$vars[] = array(
				'asset_id'    => $asset['asset_id'],
				'url'         => $asset['filedir']->url . $asset['path'],
				'filename'    => basename($asset['file_path']),
				'extension'   => strtolower(pathinfo($asset['full_path'], PATHINFO_EXTENSION)),
				'kind'        => $asset['kind'],
				'date'        => $asset['date'],
				'width'       => ($asset['kind'] == 'image' ? $image_size[0] : ''),
				'height'      => ($asset['kind'] == 'image' ? $image_size[1] : ''),
				'size'        => $this->helper->format_filesize(filesize($asset['full_path'])),
				'title'       => $asset['title'],
				'alt_text'    => $asset['alt_text'],
				'caption'     => $asset['caption'],
				'author'      => $asset['author'],
				'desc'        => $asset['desc'],
				'location'    => $asset['location'],
				'total_files' => $total_files
			);
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
	 * Filter Data
	 */
	private function _filter_data(&$data, $params)
	{
		// -------------------------------------------
		//  Filter by Kind
		// -------------------------------------------

		if (isset($params['kind']))
		{
			if (strtolower(substr($params['kind'], 0, 4)) == 'not ')
			{
				$params['kind'] = substr($params['kind'], 4);
			}

			$this->_kinds = explode('|', $params['kind']);

			$data = array_filter($data, array(&$this, '_filter_by_kind'));

			unset($this->_kinds);
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
	private function _filter_by_kind($asset)
	{
		return in_array($asset['kind'], $this->_kinds);
	}

	/**
	 * Replace Asset Id
	 */
	function replace_asset_id($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $data[0]['asset_id'];
	}

	/**
	 * Replace URL
	 */
	function replace_url($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $data[0]['filedir']->url . $data[0]['path'];
	}

	/**
	 * Replace Filename
	 */
	function replace_filename($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return basename($data[0]['file_path']);
	}

	/**
	 * Replace Extenison
	 */
	function replace_($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return strtolower(pathinfo($data[0]['full_path'], PATHINFO_EXTENSION));
	}

	/**
	 * Replace Kind
	 */
	function replace_kind($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $data[0]['kind'];
	}

	/**
	 * Replace Date
	 */
	function replace_date($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $data[0]['date'];
	}

	/**
	 * Replace Width
	 */
	function replace_width($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		if ($asset['kind'] == 'image')
		{
			$image_size = getimagesize($data[0]['full_path']);
			return $image_size[0];
		}
	}

	/**
	 * Replace Height
	 */
	function replace_height($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		if ($asset['kind'] == 'image')
		{
			$image_size = getimagesize($data[0]['full_path']);
			return $image_size[1];
		}
	}

	/**
	 * Replace Size
	 */
	function replace_size($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $this->helper->format_filesize(filesize($data[0]['full_path']));
	}

	/**
	 * Replace Title
	 */
	function replace_title($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $data[0]['title'];
	}

	/**
	 * Replace Alt Text
	 */
	function replace_alt_text($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $data[0]['alt_text'];
	}

	/**
	 * Replace Caption
	 */
	function replace_caption($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $data[0]['caption'];
	}

	/**
	 * Replace Author
	 */
	function replace_author($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $data[0]['author'];
	}

	/**
	 * Replace Description
	 */
	function replace_desc($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $data[0]['desc'];
	}

	/**
	 * Replace Location
	 */
	function replace_location($data, $params)
	{
		$this->_filter_data($data, $params);
		if (! $data) return;

		return $data[0]['location'];
	}

	/**
	 * Replace Tag
	 */
	function replace_total_files($data, $params)
	{
		$this->_filter_data($data, $params);

		return (string) count($data);
	}

}
