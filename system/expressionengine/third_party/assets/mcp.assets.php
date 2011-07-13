<?php if (! defined('BASEPATH')) die('No direct script access allowed');


/**
 * Assets Control Panel
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Assets_mcp {

	var $max_files = 1000;

	/**
	 * Constructor
	 */
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
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

		if (! class_exists('Assets_helper'))
		{
			require_once PATH_THIRD.'assets/helper.php';
		}

		$this->helper = new Assets_helper;

		// -------------------------------------------
		//  CP-only stuff
		// -------------------------------------------

		if (REQ == 'CP')
		{
			// set the base URL
			$this->base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=assets';

			// Set the right nav
			$this->EE->cp->set_right_nav(array(
				'assets_file_manager' => BASE.AMP.$this->base.AMP.'method=index',
				'assets_settings'     => BASE.AMP.$this->base.AMP.'method=settings'
			));
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
		$this->helper->insert_lang_js('upload_a_file', 'showing', 'of', 'file', 'files', 'selected', 'cancel', 'save_changes', 'new_subfolder', 'rename', '_delete', 'view_file', 'edit_file', 'confirm_delete_folder', 'confirm_delete_file');

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
			$full_path = $this->helper->get_server_path($filedir) . $subpath;

			if ($subfolders = $this->helper->get_subfolders($full_path))
			{
				$vars['helper'] = $this->helper;
				$vars['id_prefix'] = $folder;
				$vars['depth'] = $this->EE->input->post('depth') + 1;
				$vars['folders'] = $subfolders;

				exit($this->EE->load->view('filemanager/folders', $vars, TRUE));
			}
		}
	}

	/**
	 * Upload File
	 */
	function upload_file()
	{
		require_once PATH_THIRD.'assets/lib/fileuploader.php';

		// list of valid extensions, ex. array("jpeg", "xml", "bmp")
		$allowedExtensions = array();

		// max file size in bytes
		$postSize = qqFileUploader::toBytes(ini_get('post_max_size'));
        $uploadSize = qqFileUploader::toBytes(ini_get('upload_max_filesize'));
		$sizeLimit = min($postSize, $uploadSize);

		$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);

		// get the upload folder
		$folder = $this->EE->input->get('folder');
		$this->helper->parse_filedir_path($folder, $filedir, $subpath);
		$full_path = $this->helper->get_server_path($filedir) .  $subpath . '/';

		$result = $uploader->handleUpload($full_path);

		// to pass data through iframe you will need to encode all html tags
		exit(htmlspecialchars(json_encode($result), ENT_NOQUOTES));
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

		$keywords = array_filter(explode(' ', (string) $this->EE->input->post('keywords')));
		$folders  = $this->EE->input->post('folders');
		$kinds    = $this->EE->input->post('kinds');

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
					$full_path = $this->helper->get_server_path($filedir) . $subpath;
					$this->helper->get_all_subfolders($folders, $folder, $full_path);
				}

				$folders = array_unique($folders);
			}
		}

		if ($folders)
		{
			$vars['files'] = array();

			foreach ($folders as $folder)
			{
				$this->helper->parse_filedir_path($folder, $filedir, $subpath);

				// ignore if not a valid {filedir_X} path
				if (! $filedir) continue;

				$folder_path = $this->helper->get_server_path($filedir) . ($subpath ? $subpath.'/' : '');

				// ignore if it's not a valid folder
				if (! file_exists($folder_path) || ! is_dir($folder_path)) continue;

				$files = $this->helper->get_files_in_folder($folder_path);

				// ignore if no files
				if (! $files) continue;

				foreach ($files as $file)
				{
					// ignore files that don't match the keywords
					foreach ($keywords as $keyword)
					{
						if (stripos($file, $keyword) === FALSE) continue 2;
					}

					// make sure this file is one of the requested file kinds
					$full_path = $folder_path . $file;
					$kind = $this->helper->get_kind($full_path);

					if ($kind && ($kinds == 'any' || in_array($kind, $kinds)))
					{
						$vars['files'][] = array(
							'filedir_path' => $this->helper->get_server_path($filedir),
							'filedir_url'  => $filedir->url,
							'file_path'    => $folder.$file,
							'file_name'    => $file,
							'full_path'    => $full_path,
							'folder'       => $filedir->name . ($subpath ? '/'.$subpath : ''),
							'subpath'      => $subpath
						);
					}
				}
			}

			// only show folders if more than one folder was selected
			if (count($folders) > 1)
			{
				$vars['show_cols'] = array('folder', 'date', 'size');
			}
		}

		// send the total files to the JS
		$r['total'] = isset($vars['files']) ? count($vars['files']) : 0;

		if ($this->max_files && $r['total'] > $this->max_files)
		{
			// tell the view to enforce the limit
			$vars['limit'] = $this->max_files;

			// tell the JS how many we're showing (vs the actual total)
			$r['showing'] = $this->max_files;
		}

		$vars['helper'] = $this->helper;

		// pass the disabled files
		$disabled_files = $this->EE->input->post('disabled_files');
		$vars['disabled_files'] = $disabled_files ? $disabled_files : array();

		// get the files HTML from the view!
		if ($this->EE->input->post('view') == 'list')
		{
			
			$vars['orderby'] = $this->EE->input->post('orderby');
			$vars['sort']    = $this->EE->input->post('sort');

			if (! in_array($vars['orderby'], array('name', 'folder', 'date', 'file_size'))) $vars['orderby'] = 'name';
			if (! in_array($vars['sort'], array('asc', 'desc'))) $vars['sort'] = 'asc';

			$r['html'] = $this->EE->load->view('listview/listview', $vars, TRUE);
		}
		else
		{
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

		// -------------------------------------------
		//  Get the existing asset record if it exists
		// -------------------------------------------

		$invalid_file = FALSE;

		$file_path = $r['file_path'] = $this->EE->input->post('file_path');
		$full_path = $this->helper->validate_file_path($file_path);

		// is this a valid file path?
		if ($full_path)
		{
			// is this file already recorded in exp_assets?
			$query = $this->EE->db->where('file_path', $file_path)
			                      ->get('assets');

			if ($query->num_rows())
			{
				$vars = $query->row_array();

				if ($vars['date'])
				{
					// pass the set date as the datepicker default
					$r['defaultDate'] = $this->EE->localize->set_localized_time($vars['date']) * 1000;

					// display the date in EE's human-readable format
					$vars['date'] = $this->EE->localize->set_human_time($vars['date']);
				}

				$r['asset_id'] = $query->row('asset_id');
			}
			else
			{
				$vars['date'] = '';
				$vars['title'] = '';
				$vars['alt_text'] = '';
				$vars['caption'] = '';
				$vars['author'] = '';
				$vars['desc'] = '';
				$vars['location'] = '';
				$vars['keywords'] = '';
			}

			$vars['file_name']  = basename($full_path);
			$vars['kind']       = $this->helper->get_kind($full_path);
			$vars['file_size']  = $this->helper->format_filesize(filesize($full_path));

			switch ($vars['kind'])
			{
				case 'image': $vars['author_lang'] = 'credit'; break;
				case 'video': $vars['author_lang'] = 'producer'; break;
				default: $vars['author_lang'] = 'author';
			}

			if ($vars['kind'] == 'image')
			{
				$image_size = getimagesize($full_path);
				$vars['image_size'] = $image_size[0].' &times; '.$image_size[1];
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

		// convert the formatted date to a Unix timestamp
		if ($data['date']) $data['date'] = $this->EE->localize->convert_human_date_to_gmt($data['date']);

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
				$old_full_path = $this->helper->get_server_path($old_filedir) . rtrim($old_subpath, '/');
				$new_full_path = $this->helper->get_server_path($new_filedir) . rtrim($new_subpath, '/');

				// does a folder/file already exist with the same name?
				$test_path = $new_full_path;

				for ($i = 1; file_exists($test_path) && $test_path != $old_full_path; $i++)
				{
					$test_path = $new_full_path.' '.$i;
				}

				// has the name changed?
				if ($new_full_path != $test_path)
				{
					$new_full_path = $test_path;
					$new_folder = rtrim($new_folder, '/').' '.($i-1).'/';
				}

				// make sure we're actually changing the name
				if ($new_full_path != $old_full_path)
				{
					try
					{
						// rename the folder
						@rename($old_full_path, $new_full_path);

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
							rename($new_full_path, $old_full_path);

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
			$full_path = $this->helper->get_server_path($filedir) . $subpath;

			if (! file_exists($full_path) || ! is_dir($full_path))
			{
				if (@mkdir($full_path, 0777, TRUE))
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

			$full_path = $folder . '/' . $file;

			if (is_dir($full_path))
			{
				if (! $this->_delete_folder($full_path)) return FALSE;
			}
			else
			{
				if (! @unlink($full_path)) return FALSE;
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
			$full_path = $this->helper->get_server_path($filedir) . $subpath;

			if (file_exists($full_path) && is_dir($full_path))
			{
				if ($this->_delete_folder($full_path))
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
		$file = $this->EE->input->get('file');

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

			if ($new_filedir)
			{
				$old_full_path = $this->helper->get_server_path($old_filedir) . $old_subpath;
				$new_full_path = $this->helper->get_server_path($new_filedir) . $new_subpath;

				// does a folder/file already exist with the same name?
				$test_path = $new_full_path;
				$path_parts = pathinfo($new_full_path);
				$new_filename = $path_parts['filename'].($path_parts['extension'] ? '.'.$path_parts['extension'] : '');

				for ($i = 1; file_exists($test_path) && $test_path != $old_full_path; $i++)
				{
					$new_filename = $path_parts['filename'].' '.$i.($path_parts['extension'] ? '.'.$path_parts['extension'] : '');
					$test_path = $path_parts['dirname'].'/'.$new_filename;
				}

				// has the name changed?
				if ($new_full_path != $test_path)
				{
					$new_full_path = $test_path;

					// update $new_file accordingly
					$new_file = dirname($new_file).'/'.$new_filename;
				}

				// make sure we're actually changing the name
				if ($new_full_path != $old_full_path)
				{
					try
					{
						// rename the file
						@rename($old_full_path, $new_full_path);

						try
						{
							// update file paths in exp_assets
							$this->EE->db->where('file_path', $old_file)
							             ->update('assets', array('file_path' => $new_file));

							if (version_compare(APP_VER, '2.1.5', '>='))
							{
								$old_filename = basename($old_full_path);

								// was this file in the top level of the upload directory?
								if ($old_subpath == $old_filename)
								{
									// is it still in the top level of an upload directory?
									if ($new_subpath == $new_filename)
									{
										// update the exp_files record
										$this->EE->db->where('upload_location_id', $old_filedir->id)
										             ->where('file_name', $old_filename)
										             ->update('files', array(
										                                      'site_id'            => $new_filedir->site_id,
										                                      'upload_location_id' => $new_filedir->id,
										                                      'rel_path'           => $new_full_path,
										                                      'file_name'          => $new_filename
										                                     ));
									}
									else
									{
										// delete the exp_files record
										$this->EE->db->where('upload_location_id', $old_filedir->id)
										             ->where('file_name', $old_filename)
										             ->delete('files');
									}
								}
							}

							$r[] = array($old_file, 'success', $new_file);
						}
						catch (Exception $e)
						{
							// undo the file move
							rename($new_full_path, $old_full_path);

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

		$file = $this->EE->input->post('file');

		$this->helper->parse_filedir_path($file, $filedir, $subpath);

		if ($filedir && $subpath)
		{
			$full_path = $this->helper->get_server_path($filedir) . $subpath;

			if (file_exists($full_path) && ! is_dir($full_path))
			{
				if (@unlink($full_path))
				{
					$r['success'] = TRUE;

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
				}
				else
				{
					$r['error'] = lang('error_deleting_file');
				}
			}
			else
			{
				$r['error'] = lang('error_file_doesnt_exist');
			}
		}
		else
		{
			$r['error'] = lang('error_invalid_file_path');
		}

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

		$vars['files'] = array();

		$files = $this->EE->input->post('files');

		$asset_ids = array();

		foreach ($files as $file)
		{
			if (ctype_digit($file))
			{
				$asset_ids[] = $file;
			}
			else
			{
				$vars['files'][] = $file;
			}
		}

		if ($asset_ids)
		{
			$query = $this->EE->db->select('file_path')
			                      ->where_in('asset_id', $asset_ids)
			                      ->get('assets');

			foreach ($query->result() as $asset)
			{
				$vars['files'][] = $asset->file_path;
			}
		}

		$vars['helper'] = $this->helper;
		$vars['orderby'] = $this->EE->input->post('orderby');
		$vars['sort'] = $this->EE->input->post('sort');
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

		$vars['helper'] = $this->helper;
		$vars['field_name'] = $this->EE->input->post('field_name');
		$vars['files'] = $this->EE->input->post('files');

		if ($this->EE->input->post('view') == 'thumbs')
		{
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

		// pass back the requestId so the JS knows the response matches the request
		$r['requestId'] = $this->EE->input->post('requestId');

		exit($this->EE->javascript->generate_json($r, TRUE));
	}

}
