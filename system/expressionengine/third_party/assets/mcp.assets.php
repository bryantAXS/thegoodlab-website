<?php if (! defined('BASEPATH')) die('No direct script access allowed');


// load dependencies
require_once PATH_THIRD.'assets/helper.php';


/**
 * Assets Control Panel
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>, Andris Sevcenko <andris@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Assets_mcp {

	var $max_files = 1000;

	/**
	 * @var EE
	 */
	private $EE;

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

		// -------------------------------------------
		//  CP-only stuff
		// -------------------------------------------

		if (REQ == 'CP')
		{
			// set the base URL
			$this->base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=assets';

			// Set the right nav for Super Admins
			if ($this->EE->session->userdata['group_id'] == 1)
			{
				$this->EE->cp->set_right_nav(array(
					'assets_file_manager' => BASE.AMP.$this->base.AMP.'method=index',
					'assets_settings'     => BASE.AMP.$this->base.AMP.'method=settings'
				));
			}
		}
		else
		{
			// disable the output profiler
			$this->EE->output->enable_profiler(FALSE);
		}
	}

	/**
	 * Set "Assets" Breadcrumb
	 */
	private function _set_page_title($line = 'assets_module_name')
	{
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line($line));

		if ($line != 'assets_module_name')
		{
			$this->EE->cp->set_breadcrumb(BASE.AMP.$this->base, $this->EE->lang->line('assets_module_name'));
		}
	}

	// -----------------------------------------------------------------------
	//  Pages
	// -----------------------------------------------------------------------

	/**
	 * Homepage
	 */
	function index()
	{
		$this->_set_page_title();

		$this->EE->cp->add_js_script(array('ui' => array('datepicker')));

		$this->helper->include_css('shared.css', 'filemanager.css');
		$this->helper->include_js('filemanager.js', 'filemanager_folder.js', 'select.js', 'drag.js', 'listview.js', 'thumbview.js', 'properties.js', 'fileuploader.js', 'contextmenu.js');

		$this->helper->insert_actions_js();
		$this->helper->insert_lang_js('upload_a_file', 'upload_status', 'showing', 'of', 'file', 'files', 'selected', 'cancel', 'save_changes', 'new_subfolder', 'rename', '_delete', 'view_file', 'edit_file', 'confirm_delete_folder', 'confirm_delete_file', 'confirm_delete_files', 'error_deleting_files');

		$this->helper->insert_js('new Assets.FileManager(jQuery(".assets-fm"));');

		$vars['base'] = $this->base;
		$vars['helper'] = $this->helper;

		$this->EE->load->library('table');

		return $this->EE->load->view('mcp/index', $vars, TRUE);
	}

	/**
	 * Settings
	 */
	function settings()
	{
		$this->_set_page_title(lang('assets_settings'));

		$vars['base'] = $this->base;

		// settings
		$query = $this->EE->db->select('settings')
							  ->where('name', 'assets')
							  ->get('fieldtypes');

		$settings = unserialize(base64_decode($query->row('settings')));

		$vars['license_key'] = isset($settings['license_key']) ? $settings['license_key'] : '';

		$this->EE->load->library('table');

		return $this->EE->load->view('mcp/settings', $vars, TRUE);
	}

	/**
	 * Save Settings
	 */
	function save_settings()
	{
		$settings = array(
			'license_key' => $this->EE->input->post('license_key')
		);

		$data['settings'] = base64_encode(serialize($settings));

		$this->EE->db->where('name', 'assets');
		$this->EE->db->update('fieldtypes', $data);

		// redirect to Index
		$this->EE->session->set_flashdata('message_success', lang('global_settings_saved'));
		$this->EE->functions->redirect(BASE.AMP.$this->base.AMP.'method=settings');
	}

	// -----------------------------------------------------------------------
	//  File Manager actions
	// -----------------------------------------------------------------------

	/**
	 * Get Subfolders
	 */
	function get_subfolders()
	{
		$folder = $this->EE->input->post('folder');

		$this->helper->parse_filedir_path($folder, $filedir, $subpath);

		if ($filedir)
		{
			$path = $filedir->server_path . $subpath;

			// load image manipulation data, so we know which folders to hide
			$this->EE->load->model('file_model');
			$file_dimensions = $this->EE->file_model->get_dimensions_by_dir_id($filedir->id);

			foreach ($file_dimensions->result() as $file_dimension)
			{
				$hidden_folders[] = '_' . $file_dimension->short_name;
			}


			$subfolders = $this->helper->get_subfolders($path);

			if ( ! empty($hidden_folders))
			{
				$subfolders = array_diff($subfolders, $hidden_folders);
			}

			foreach ($subfolders as $subfolder)
			{

				$vars['folders'][] = array(
					'name' => $subfolder,
					'path' => $folder.$subfolder.'/'
				);
			}
		}

		$vars['helper'] = $this->helper;
		$vars['id_prefix'] = $folder;
		$vars['depth'] = $this->EE->input->post('depth') + 1;

		exit($this->EE->load->view('filemanager/folders', $vars, TRUE));
	}

	/**
	 * Upload File
	 */
	function upload_file()
	{
		$this->EE->load->library('javascript');
		$this->EE->lang->loadfile('assets');

		// get the upload folder
		$folder = $this->EE->input->get('folder');
		$this->helper->parse_filedir_path($folder, $filedir, $subpath);
		$server_path = $filedir->server_path .  $subpath . '/';

		if (is_writable($server_path))
		{
			require_once PATH_THIRD.'assets/lib/fileuploader.php';

			$uploader = new qqFileUploader();

			if ($uploader->file)
			{
				if ($size = $uploader->file->getSize())
				{
					if (! $filedir->max_size || $size <= $filedir->max_size)
					{
						$path = $server_path . $uploader->file->getName();
						$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

						$is_image = in_array($ext, $this->helper->file_kinds['image']);

						if ($filedir->allowed_types != 'img' || $is_image)
						{
							// check if file is valid according to config/mimes.php
							$valid_mime = TRUE;

							global $mimes;

							if (! is_array($mimes))
							{
								require_once(APPPATH.'config/mimes.php');
							}

							if (is_array($mimes) && ! isset($mimes[$ext]))
							{
								$valid_mime = FALSE;
							}

							if ($valid_mime)
							{
								// make sure the filename is clean and unique
								$this->_prep_filename($path);


								if ($uploader->file->save($path))
								{
									// assemble the new {filedir_X} path
									$file_path = '{filedir_'.$filedir->id.'}'.substr($path, strlen($filedir->server_path));

									// EE can only know about this file only if it's at top level
									// otherwise we just create thumbnails for images
									if (empty($subpath))
									{
										$this->_store_file_data($path, $filedir->id);
									}
									else if ($is_image)
									{
										$this->_create_thumbnails($path, $filedir->id);
									}

									$r = array('success' => TRUE, 'path' => $file_path);
								}
								else
								{
									$r = array('error'=> lang('error_couldnt_save'));
								}

							}
							else
							{
								$r = array('error'=> lang('error_couldnt_save'));
							}
						}
						else
						{
							$r = array('error' => lang('error_filetype_not_allowed'));
						}

					}
					else
					{
						$error = $this->EE->functions->var_swap(lang('error_file_too_large'), array(
							'max_size' => $this->helper->format_filesize($filedir->max_size)
						));

						$r = array('error' => $error);
					}
				}
				else
				{
					$r = array('error' => lang('error_empty_file'));
				}
			}
			else
			{
				$r = array('error' => lang('no_files'));
			}
		}
		else
		{
			$r = array('error' => lang('error_filedir_not_writable'));
		}

		exit($this->EE->javascript->generate_json($r, TRUE));
	}

	/**
	 * Get Files View by Folders
	 *
	 * Called by the File Manager. Returns a view of all the files in the selected folders.
	 */
	function get_files_view_by_folders()
	{
		$this->EE->load->library('javascript');
		$this->EE->lang->loadfile('assets');

		$files = array();

		$keywords = array_filter(explode(' ', (string) $this->EE->input->post('keywords')));
		$folders  = $this->EE->input->post('folders');

		$selected_file_paths = $this->EE->input->post('selected_files');
		$selected_files = array();

		// -------------------------------------------
		//  Get all nested folders if there are keywords
		// -------------------------------------------

		if ($keywords)
		{
			if (! $folders && $keywords)
			{
				// search through *all* folders
				$folders = $this->helper->get_all_folders();
			}
			else
			{
				// search through all subfolders
				$folders_dup = array_merge($folders);

				foreach ($folders_dup as $folder)
				{
					$this->helper->parse_filedir_path($folder, $filedir, $subpath);
					$server_path = $filedir->server_path . $subpath;
					$this->helper->get_all_subfolders($folders, $folder, $server_path);
				}

				$folders = array_unique($folders);
			}
		}

		// -------------------------------------------
		//  Get the files
		// -------------------------------------------

		if ($folders)
		{
			$kinds = $this->EE->input->post('kinds');

			foreach ($folders as $folder)
			{
				$this->helper->parse_filedir_path($folder, $filedir, $subpath);

				// ignore if not a valid {filedir_X} path
				if (! $filedir) continue;

				$folder_path = $filedir->server_path . ($subpath ? $subpath.'/' : '');

				$folder_files = $this->helper->get_files_in_folder($folder_path);

				// ignore if no files
				if (! $folder_files) continue;

				foreach ($folder_files as $filename)
				{
					// ignore files that don't match the keywords
					foreach ($keywords as $keyword)
					{
						if (stripos($filename, $keyword) === FALSE) continue 2;
					}

					// make sure this file is one of the requested file kinds
					$server_path = $folder_path.$filename;
					$kind = $this->helper->get_kind($server_path);

					if ($kind && ($kinds == 'any' || in_array($kind, $kinds)))
					{
						$file_path = $folder.$filename;
						$file = $files[] = $this->helper->get_file($file_path);

						// selected?
						if ($file->selected = ($selected_file_paths && in_array($file_path, $selected_file_paths)))
						{
							$selected_files[] = $file;
						}
					}
				}
			}
		}

		// -------------------------------------------
		//  Sorting
		// -------------------------------------------

		$orderby = $this->EE->input->post('orderby');
		$sort    = $this->EE->input->post('sort');

		$this->helper->sort_files($files, $orderby, $sort);

		// -------------------------------------------
		//  Enforce the Max Files limit
		// -------------------------------------------

		// tell the JS how many files there are in total
		$total_files = isset($files) ? count($files) : 0;

		// are there more files than the Max Files limit?
		if ($this->max_files && $total_files > $this->max_files)
		{
			// cut out the extras
			$files = array_slice($files, 0, $this->max_files);

			// make sure any selected files are still going to be shown
			foreach ($selected_files as $file)
			{
				if (! in_array($file, $files))
				{
					$files[] = $file;
				}
			}

			$showing = count($files);

			if ($showing < $total_files)
			{
				// tell the JS how many we're actually showing
				$r['showing'] = $showing;
			}
		}

		$r['total'] = $total_files;

		// -------------------------------------------
		//  Load the view
		// -------------------------------------------

		$vars['helper'] = $this->helper;
		$vars['files']  = $files;

		// pass the disabled files
		$disabled_files = $this->EE->input->post('disabled_files');
		$vars['disabled_files'] = $disabled_files ? $disabled_files : array();

		if ($this->EE->input->post('view') == 'list')
		{
			// pass list view-specific vars
			$vars['show_cols'] = (count($folders) > 1) ? array('folder', 'date', 'size') : array('date', 'size');
			$vars['orderby']   = $orderby;
			$vars['sort']      = $sort;

			$r['html'] = $this->EE->load->view('listview/listview', $vars, TRUE);
		}
		else
		{
			// load the filemanager library and file helper for generating thumbs
			$this->EE->load->library('filemanager');
			$this->EE->load->helper('file');

			$r['html'] = $this->EE->load->view('thumbview/thumbview', $vars, TRUE);
		}

		// pass back the requestId so the JS knows the response matches the request
		$r['requestId'] = $this->EE->input->post('requestId');

		exit($this->EE->javascript->generate_json($r, TRUE));
	}

	// -----------------------------------------------------------------------
	//  Properties HUD
	// -----------------------------------------------------------------------

	/**
	 * Get File Properties HTML
	 */
	function get_props()
	{
		$this->EE->load->library('javascript');
		$this->EE->lang->loadfile('assets');

		$file_path = $this->EE->input->post('file_path');
		$file = $this->helper->get_file($file_path);

		if ($file->exists())
		{
			$vars['file'] = $file;
			$vars['helper'] = $this->helper;

			switch ($file->kind())
			{
				case 'image': $vars['author_lang'] = 'credit'; break;
				case 'video': $vars['author_lang'] = 'producer'; break;
				default: $vars['author_lang'] = 'author';
			}

			$r['html'] = $this->EE->load->view('properties', $vars, TRUE);
		}
		else
		{
			$r['html'] = lang('invalid_file');
		}

		$r['requestId'] = $this->EE->input->post('requestId');

		exit($this->EE->javascript->generate_json($r, TRUE));
	}

	/**
	 * Save Props
	 */
	function save_props()
	{
		$data = $this->EE->security->xss_clean($this->EE->input->post('data'));

		// convert the formatted dates to Unix timestamps
		foreach ($data as $key => $val)
		{
			if (strpos($key, 'date') !== FALSE && $val)
			{
				$data[$key] = $this->EE->localize->convert_human_date_to_gmt($val);
			}
		}

		// is this file already recorded in exp_assets?
		$query = $this->EE->db->select('asset_id')
							  ->where('file_path', $this->EE->security->xss_clean($data['file_path']))
							  ->get('assets');

		if ($query->num_rows())
		{
			$this->EE->db->where('asset_id', $query->row('asset_id'))
						 ->update('assets', $data);
		}
		else
		{
			$this->EE->db->insert('assets', $data);
		}
	}

	// -----------------------------------------------------------------------
	//  File/folder CRUD actions
	// -----------------------------------------------------------------------

	/**
	 * Move Folder
	 *
	 * Used when either renaming or moving a folder
	 */
	function move_folder()
	{
		$this->EE->load->library('javascript');
		$this->EE->lang->loadfile('assets');

		$old_folders = $this->EE->input->post('old_folder');
		$new_folders = $this->EE->input->post('new_folder');

		if (! is_array($old_folders)) $old_folders = array($old_folders);
		if (! is_array($new_folders)) $new_folders = array($new_folders);

		foreach ($old_folders as $i => $old_folder)
		{
			$new_folder = $new_folders[$i];

			$this->helper->parse_filedir_path($old_folder, $old_filedir, $old_subpath);
			$this->helper->parse_filedir_path($new_folder, $new_filedir, $new_subpath);

			if ($new_filedir)
			{
				$old_server_path = $old_filedir->server_path . rtrim($old_subpath, '/');
				$new_server_path = $new_filedir->server_path . rtrim($new_subpath, '/');

				// make sure the filename is clean and unique
				if ($this->_prep_filename($new_server_path, $old_server_path))
				{
					$dirname = dirname($new_subpath);
					$new_folder = '{filedir_'.$new_filedir->id.'}' . ($dirname != '.' ? $dirname.'/' : '') . basename($new_server_path) . '/';
				}

				// make sure we're actually changing the name
				if ($new_server_path != $old_server_path)
				{
					try
					{
						// rename the folder
						@rename($old_server_path, $new_server_path);

						try
						{
							// update file paths in exp_assets
							$this->EE->db->query('UPDATE exp_assets
												  SET file_path = REPLACE(file_path, "'.$old_folder.'", "'.$new_folder.'")
												  WHERE file_path LIKE "'.$old_folder.'%"');

							$r[] = array($old_folder, 'success', $new_folder);
						}
						catch (Exception $e)
						{
							// undo the file move
							rename($new_server_path, $old_server_path);

							$r[] = array($old_folder, 'error', lang('error_updating_table'));
						}
					}
					catch (Exception $e)
					{
						$r[] = array($old_folder, 'error', lang('error_moving_folder'));
					}
				}
				else
				{
					$r[] = array($old_folder, 'notice', lang('notice_same_folder_name'));
				}
			}
			else
			{
				$r[] = array($old_folder, 'error', lang('error_invalid_filedir_path'));
			}
		}

		exit($this->EE->javascript->generate_json($r, TRUE));
	}

	/**
	 * Create Folder
	 */
	function create_folder()
	{
		$this->EE->load->library('javascript');
		$this->EE->lang->loadfile('assets');

		$folder = rtrim($this->EE->input->post('folder'), '/');

		$this->helper->parse_filedir_path($folder, $filedir, $subpath);

		if ($filedir && $subpath)
		{
			$server_path = $filedir->server_path . $subpath;

			if (! file_exists($server_path) || ! is_dir($server_path))
			{
				if (@mkdir($server_path, DIR_WRITE_MODE, TRUE))
				{
					$r['success'] = TRUE;
				}
				else
				{
					$r['error'] = lang('error_creating_folder');
				}
			}
			else
			{
				$r['error'] = lang('error_folder_exists');
			}
		}
		else
		{
			$r['error'] = lang('error_invalid_folder_path');
		}

		exit($this->EE->javascript->generate_json($r, TRUE));
	}

	/**
	 * Delete Folder
	 *
	 * Recursively deletes a folder
	 */
	private function _delete_folder($folder)
	{
		$files = scandir($folder);

		foreach ($files as $file)
		{
			// ignore relative folders
			if ($file == '.' || $file == '..') continue;

			$server_path = $folder . '/' . $file;

			if (is_dir($server_path))
			{
				if (! $this->_delete_folder($server_path)) return FALSE;
			}
			else
			{
				if (! @unlink($server_path)) return FALSE;
			}
		}

		// now that there are no more files or folders in here, we can delete this folder
		return @rmdir($folder);
	}

	/**
	 * Delete Folder
	 */
	function delete_folder()
	{
		$this->EE->load->library('javascript');
		$this->EE->lang->loadfile('assets');

		$folder = $this->EE->input->post('folder');

		$this->helper->parse_filedir_path($folder, $filedir, $subpath);

		if ($filedir && $subpath)
		{
			$server_path = $filedir->server_path . $subpath;

			if ($this->helper->is_folder($server_path))
			{
				if ($this->_delete_folder($server_path))
				{
					$r['success'] = TRUE;

					// get the asset_ids we need to delete
					$assets = $this->EE->db->select('asset_id')
										   ->like('file_path', $folder.'/', 'after')
										   ->get('assets');

					if ($assets->num_rows())
					{
						foreach ($assets->result() as $asset)
						{
							$asset_ids[] = $asset->asset_id;
						}

						// delete the exp_assets records
						$this->EE->db->where_in('asset_id', $asset_ids)
									 ->delete('assets');

						// delete the exp_assets_entries records
						$this->EE->db->where_in('asset_id', $asset_ids)
									 ->delete('assets_entries');
					}
				}
				else
				{
					$r['error'] = lang('error_deleting_folder');
				}
			}
			else
			{
				$r['error'] = lang('error_folder_doesnt_exist');
			}
		}
		else
		{
			$r['error'] = lang('invalid_folder_path');
		}

		exit($this->EE->javascript->generate_json($r, TRUE));
	}

	// -----------------------------------------------------------------------

	/**
	 * View File
	 */
	function view_file()
	{
		$file = urldecode($this->EE->input->get('file'));

		// is this an asset_id
		if (ctype_digit($file))
		{
			$file = $this->EE->db->select('file_path')->where('asset_id', $file)->get('assets')->row('file_path');
		}

		$this->helper->parse_filedir_path($file, $filedir, $subpath);

		$url = $filedir->url . $subpath;

		$this->EE->functions->redirect($url);
	}

	/**
	 * Prep Filename
	 */
	private function _prep_filename(&$path, $original = FALSE)
	{
		// save a copy of the target path
		$_path = $path;

		$original = strtolower($original);

		$pathinfo = pathinfo($path);
		$folder = $pathinfo['dirname'].'/';

		if (isset($pathinfo['filename']))
		{
			$filename = $pathinfo['filename'];
		}
		else
		{
			if (($lastdot = strrpos($pathinfo['basename'], '.')) !== FALSE)
			{
				$filename = substr($pathinfo['basename'], 0, $lastdot);
			}
			else
			{
				$filename = $pathinfo['basename'];
			}
		}

		$ext = (isset($pathinfo['extension']) ? '.'.$pathinfo['extension'] : '');

		// -------------------------------------------
		//  Clean the filename
		// -------------------------------------------

		// swap whitespace with underscores
		$filename = preg_replace('/\s+/', '_', $filename);

		// sanitize it
		$filename = $this->EE->security->sanitize_filename($filename);

		$path = $folder.$filename.$ext;

		// -------------------------------------------
		//  Make sure it's unique
		// -------------------------------------------

		$i = 1;

		while ((! $original || strtolower($path) != $original) && file_exists($path))
		{
			$path = $folder.$filename.'_'.($i++).$ext;
		}

		// -------------------------------------------
		//  Return whether the filename has changed
		// -------------------------------------------

		return ($path != $_path);
	}

	/**
	 * Move File
	 *
	 * Used when either renaming or moving a file
	 */
	function move_file()
	{
		$this->EE->load->library('javascript');
		$this->EE->lang->loadfile('assets');

		$old_files = $this->EE->input->post('old_file');
		$new_files = $this->EE->input->post('new_file');

		if (! is_array($old_files)) $old_files = array($old_files);
		if (! is_array($new_files)) $new_files = array($new_files);

		foreach ($old_files as $i => $old_file)
		{
			$new_file = $new_files[$i];

			$this->helper->parse_filedir_path($old_file, $old_filedir, $old_subpath);
			$this->helper->parse_filedir_path($new_file, $new_filedir, $new_subpath);

			if ($old_filedir && $new_filedir)
			{
				$old_server_path = $old_filedir->server_path . $old_subpath;
				$new_server_path = $new_filedir->server_path . $new_subpath;

				// make sure the filename is clean and unique
				if ($this->_prep_filename($new_server_path, $old_server_path))
				{
					$dirname = dirname($new_subpath);
					$new_file = '{filedir_'.$new_filedir->id.'}' . ($dirname != '.' ? $dirname.'/' : '') . basename($new_server_path);
				}

				// make sure we're actually changing the name
				if ($new_server_path != $old_server_path)
				{
					try
					{
						// rename the file
						@rename($old_server_path, $new_server_path);

						try
						{
							// update file paths in exp_assets
							$this->EE->db->where('file_path', $old_file)
										 ->update('assets', array('file_path' => $new_file));

							if (version_compare(APP_VER, '2.1.5', '>='))
							{
								// was this file in the top level of the upload directory?
								if (dirname($old_subpath) == '.')
								{
									$old_filename = basename($old_server_path);

									$this->EE->db->where('upload_location_id', $old_filedir->id);
									$this->EE->db->where('file_name', $old_filename);

									// is it still in the top level of an upload directory?
									if (dirname($new_subpath) == '.')
									{
										$new_filename = basename($new_server_path);

										// update the exp_files record
										$this->EE->db->update('files', array(
							                'site_id'            => $new_filedir->site_id,
									        'upload_location_id' => $new_filedir->id,
											'rel_path'           => $new_server_path,
								    		'file_name'          => $new_filename
										 ));
									}
									else
									{
										// delete the exp_files record
										$this->EE->db->delete('files');
									}
								}
								// is it being moved to the top level of an upload directory?
								else if (dirname($new_subpath) == '.')
								{
									$this->_store_file_data($new_server_path, $new_filedir->id);
								}
							}

							$r[] = array($old_file, 'success', $new_file);

							// Delete old thumbnails and create new ones at location, if needed
							$ext = strtolower(pathinfo($old_file, PATHINFO_EXTENSION));
							$is_image = in_array($ext, $this->helper->file_kinds['image']);

							if ($is_image)
							{
								$this->_delete_thumbnails($old_server_path, $old_filedir->id);
								$this->_create_thumbnails($new_server_path, $new_filedir->id);
							}

						}
						catch (Exception $e)
						{
							// undo the file move
							rename($new_server_path, $old_server_path);

							$r[] = array($old_file, 'error', lang('error_updating_table'));
						}
					}
					catch (Exception $e)
					{
						$r[] = array($old_file, 'error', lang('error_moving_file'));
					}
				}
				else
				{
					$r[] = array($old_file, 'notice', lang('notice_same_file_name'));
				}
			}
			else
			{
				$r[] = array($old_file, 'error', lang('error_invalid_filedir_path'));
			}
		}

		exit($this->EE->javascript->generate_json($r, TRUE));
	}

	/**
	 * Delete File
	 */
	function delete_file()
	{
		$this->EE->load->library('javascript');
		$this->EE->lang->loadfile('assets');

		$files = $this->EE->input->post('file');

		if (! is_array($files))
		{
			$files = array($files);
		}

		foreach ($files as $file)
		{
			$file_result = array('file' => $file);

			$this->helper->parse_filedir_path($file, $filedir, $subpath);

			if ($filedir && $subpath)
			{
				$server_path = $filedir->server_path . $subpath;

				if (file_exists($server_path) && ! is_dir($server_path))
				{

					if (@unlink($server_path))
					{
						$file_result['success'] = TRUE;

						// get the asset_id we need to delete
						$asset = $this->EE->db->select('asset_id')
											  ->where('file_path', $file)
											  ->get('assets');

						if ($asset->num_rows())
						{
							$asset_id = $asset->row('asset_id');

							// delete the exp_assets records
							$this->EE->db->where('asset_id', $asset_id)
										 ->delete('assets');

							// delete the exp_assets_entries records
							$this->EE->db->where('asset_id', $asset_id)
										 ->delete('assets_entries');
						}

						// delete the exp_files record
						$this->EE->db->where('upload_location_id', $filedir->id)
							->where('rel_path', $server_path)
							->delete('files');


						$this->_delete_thumbnails($server_path, $filedir->id);


					}
					else
					{
						$file_result['error'] = lang('error_deleting_file');
					}
				}
				else
				{
					// fail silently (this is what we wanted to do anyway, right?)
					$file_result['success'] = TRUE;
				}
			}
			else
			{
				$file_result['error'] = lang('error_invalid_file_path');
			}

			$r[] = $file_result;
		}

		$r = array('result' => $r);

		exit($this->EE->javascript->generate_json($r, TRUE));
	}

	// -----------------------------------------------------------------------
	//  Field actions
	// -----------------------------------------------------------------------

	/**
	 * Get Ordered Files View
	 */
	function get_ordered_files_view()
	{
		$this->EE->load->library('javascript');
		$this->EE->lang->loadfile('assets');

		$files = $this->EE->input->post('files');
		$orderby = $this->EE->input->post('orderby');
		$sort = $this->EE->input->post('sort');

		// convert file paths to objects
		foreach ($files as $i => $file_path)
		{
			$files[$i] = $this->helper->get_file($file_path);
		}

		$this->helper->sort_files($files, $orderby, $sort);

		// -------------------------------------------
		//  Load the list view
		// -------------------------------------------

		$vars['helper'] = $this->helper;
		$vars['files'] = $files;
		$vars['orderby'] = $orderby;
		$vars['sort'] = $sort;
		$vars['field_name'] = $this->EE->input->post('field_name');

		if (($show_cols = $this->EE->input->post('show_cols')) !== FALSE)
		{
			$vars['show_cols'] = $show_cols;
		}

		// get the files HTML from the view!
		$r['html'] = $this->EE->load->view('listview/listview', $vars, TRUE);

		$r['requestId'] = $this->EE->input->post('requestId');

		exit($this->EE->javascript->generate_json($r, TRUE));
	}

	/**
	 * Build Sheet
	 */
	function build_sheet()
	{
		$this->EE->lang->loadfile('assets');

		$vars['helper'] = $this->helper;
		$vars['mode'] = 'sheet';
		$vars['site_id'] = $this->EE->input->post('site_id');
		$vars['filedirs'] = $this->EE->input->post('filedirs');
		$vars['multi'] = ($this->EE->input->post('multi') == 'y');

		exit ($this->EE->load->view('filemanager/filemanager', $vars, TRUE));
	}

	/**
	 * Get Selected Files
	 *
	 * Called from field.js when a new file(s) is selected
	 */
	function get_selected_files()
	{
		$this->EE->load->library('javascript');

		$file_paths = $this->EE->input->post('files');

		$files = array();

		foreach ($file_paths as $i => $file_path)
		{
			$file = $this->helper->get_file($file_path);

			if ($file->exists())
			{
				$files[] = $file;
			}
		}

		if ($files)
		{
			$vars['helper'] = $this->helper;
			$vars['field_name'] = $this->EE->input->post('field_name');
			$vars['files'] = $files;

			if ($this->EE->input->post('view') == 'thumbs')
			{
				// load the filemanager library and file helper for generating thumbs
				$this->EE->load->library('filemanager');
				$this->EE->load->helper('file');

				$r['html'] = $this->EE->load->view('thumbview/files', $vars, TRUE);
			}
			else
			{
				$vars['start_index'] = $this->EE->input->post('start_index');
				$vars['show_cols'] = $this->EE->input->post('show_cols');

				$r['html'] = $this->EE->load->view('listview/files', $vars, TRUE);
			}
		}
		else
		{
			$r['html'] = '';
		}

		// pass back the requestId so the JS knows the response matches the request
		$r['requestId'] = $this->EE->input->post('requestId');

		exit($this->EE->javascript->generate_json($r, TRUE));
	}


	/**
	 * Stores file data however filemanager pleases
	 * @param $file_path string absolute path to file
	 * @param $upload_folder_id
	 */
	private function _store_file_data($file_path, $upload_folder_id)
	{
		$this->EE->load->library('filemanager');

		$file_name = substr($file_path, strrpos($file_path, DIRECTORY_SEPARATOR) + 1);

		$preferences = array();
		$preferences['rel_path'] = $file_path;
		$preferences['file_name'] = $file_name;
		$preferences['file_size'] = filesize($file_path);
		$preferences['uploaded_by_member_id'] = $this->EE->session->userdata('member_id');

		$file_size = getimagesize($file_path);
		if ($file_size !== FALSE)
		{
			$preferences['file_hw_original'] = $file_size[1].' '.$file_size[0];
		}

		$this->EE->filemanager->save_file($file_path, $upload_folder_id, $preferences);
	}


	/**
	 * Creates thumbnails for uploaded image according to image manipulations specified
	 * @param string $image_path
	 * @param int $upload_folder_id
	 * @return bool
	 */
	private function _create_thumbnails ($image_path, $upload_folder_id)
	{
		$this->EE->load->library('filemanager');

		$file_name = substr($image_path, strrpos($image_path, DIRECTORY_SEPARATOR) + 1);
		$preferences = $this->EE->filemanager->fetch_upload_dir_prefs($upload_folder_id);
		$preferences['file_name'] = $file_name;

		// Trick Filemanager into creating the thumbnail where WE need it
		$preferences['server_path'] .=
			str_replace($file_name, '',
				str_replace($preferences['server_path'], '', $image_path));

		return $this->EE->filemanager->create_thumb($image_path, $preferences);
	}


	/**
	 * Delete all thumbnails and images created by manipulations for provided image
	 * @param string $image_path
	 * @param int $upload_folder_id
	 */
	private function _delete_thumbnails ($image_path, $upload_folder_id)
	{

		$this->EE->load->library('filemanager');

		$file_name = substr($image_path, strrpos($image_path, DIRECTORY_SEPARATOR) + 1);

		@unlink(str_replace($file_name, '', $image_path) . '_thumbs/' . $file_name);

		// Then, delete the dimensions
		$this->EE->load->model('file_model');
		$file_dimensions = $this->EE->file_model->get_dimensions_by_dir_id($upload_folder_id);

		foreach ($file_dimensions->result() as $file_dimension)
		{
			@unlink(str_replace($file_name, '', $image_path) . '_' . $file_dimension->short_name . '/' . $file_name);
		}

	}

}

/* End of file mcp.assets.php */
/* Location: ./system/expressionengine/third_party/assets/mcp.assets.php */
