<?php if (! defined('BASEPATH')) die('No direct script access allowed');


/**
 * Assets Helper
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Assets_helper {

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
	}

	// -----------------------------------------------------------------------

	/**
	 * Theme URL
	 */
	private function _theme_url()
	{
		if (! isset($this->cache['theme_url']))
		{
			$theme_folder_url = $this->EE->config->item('theme_folder_url');
			if (substr($theme_folder_url, -1) != '/') $theme_folder_url .= '/';
			$this->cache['theme_url'] = $theme_folder_url.'third_party/assets/';
		}

		return $this->cache['theme_url'];
	}

	/**
	 * Include Theme CSS
	 */
	function include_css()
	{
		foreach (func_get_args() as $file)
		{
			$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().'styles/'.$file.'" />');
		}
	}

	/**
	 * Include Theme JS
	 */
	function include_js()
	{
		foreach (func_get_args() as $file)
		{
			$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().'scripts/'.$file.'"></script>');
		}
	}

	/**
	 * Include Sheet Resources
	 */
	function include_sheet_resources()
	{
		if (! isset($this->cache['included_sheet_resources']))
		{
			$this->EE->lang->loadfile('assets');

			$this->include_css('shared.css', 'field.css', 'filemanager.css');
			$this->include_js('filemanager.js', 'filemanager_folder.js', 'field.js', 'select.js', 'drag.js', 'thumbview.js', 'listview.js', 'properties.js', 'fileuploader.js', 'contextmenu.js');

			$this->insert_actions_js();
			$this->insert_lang_js('upload_a_file', 'showing', 'of', 'file', 'files', 'selected', 'cancel', 'save_changes', 'new_subfolder', 'rename', '_delete', 'view_file', 'edit_file', 'remove_file');

			$this->cache['included_sheet_resources'] = TRUE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Insert CSS
	 */
	function insert_css($css)
	{
		$this->EE->cp->add_to_head('<style type="text/css">'.$css.'</style>');
	}

	/**
	 * Insert JS
	 */
	function insert_js($js)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
	}

	// --------------------------------------------------------------------

	/**
	 * Site URL
	 */
	private function _site_url()
	{
		if (! isset($this->cache['site_url']))
		{
			// get the site URL, allowing for an override in config.php
			$this->cache['site_url'] = $this->EE->config->item('assets_site_url');
			if (! $this->cache['site_url']) $this->cache['site_url'] = $this->EE->functions->fetch_site_index(0, 0);
		}

		return $this->cache['site_url'];
	}

	/**
	 * Insert Actions JS
	 */
	function insert_actions_js()
	{
		// get the action IDs
		$this->EE->db->select('action_id, method')
		             ->where('class', 'Assets_mcp');

		if ($methods = func_get_args())
		{
			$this->EE->db->where_in('method', $methods);
		}

		$actions = $this->EE->db->get('actions');

		$json = array();

		foreach ($actions->result() as $act)
		{
			$json[$act->method] = $this->_site_url().QUERY_MARKER.'ACT='.$act->action_id;
		}

		$this->insert_js('Assets.actions = '.$this->EE->javascript->generate_json($json, TRUE));
	}

	/**
	 * Insert Language JS
	 */
	function insert_lang_js()
	{
		$json = array();

		foreach (func_get_args() as $line)
		{
			$json[$line] = lang($line);
		}

		$this->insert_js('Assets.lang = '.$this->EE->javascript->generate_json($json, TRUE));
	}

	// --------------------------------------------------------------------

	/**
	 * Get File Directory Preferences
	 */
	function get_filedir_prefs($filedir = 'all')
	{
		if (! isset($this->cache['filedir_prefs'][$filedir]))
		{
			// enforce access permissions for non-Super Admins, except on front-end pages
			if (REQ != 'PAGE')
			{
				$group = $this->EE->session->userdata('group_id');

				if ($group != 1)
				{
					$no_access = $this->EE->db->select('upload_id')
					                          ->where('member_group', $group)
					                          ->get('upload_no_access');

					if ($no_access->num_rows() > 0)
					{
						$denied = array();

						foreach ($no_access->result() as $result)
						{
							$denied[] = $result->upload_id;
						}

						$this->EE->db->where_not_in('id', $denied);
					}
				}
			}

			// limit to a single upload directory?
			if ($filedir && $filedir !== 'all')
			{
				$this->EE->db->where('id', $filedir);
			}
			else
			{
				// order by name
				$upload_prefs = $this->EE->db->order_by('name');
			}

			// limit to upload directories from the current site, except on front-end pages
			if (REQ != 'PAGE')
			{
				$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			}

			$this->cache['filedir_prefs'][$filedir] = $this->cache['filedir_prefs'][$filedir] = $this->EE->db->get('upload_prefs');

			if ($filedir == 'all')
			{
				// fill up the cache
				foreach ($this->cache['filedir_prefs'][$filedir]->result() as $filedir_prefs)
				{
					$this->cache['filedir_prefs'][$filedir_prefs->id] = $this->cache['filedir_prefs'][$filedir];
				}
			}
		}

		return $this->cache['filedir_prefs'][$filedir];
	}

	/**
	 * Get Filedir Server Path
	 */
	function get_server_path($filedir)
	{
		$server_path = $filedir->server_path;

		if (REQ != 'CP')
		{
			if (! preg_match('/^(\/|\\\|[a-zA-Z]+:)/', $server_path)) $server_path = SYSDIR.'/'.$server_path;
		}

		return $server_path;
	}

	/**
	 * Get Subfolders
	 */
	function get_subfolders($folder)
	{
		// make sure the parent folder has a trailing slash
		if (substr($folder, -1) != '/') $folder .= '/';

		$subfolders = array();

		if (is_dir($folder) && ($handle = opendir($folder)))
		{
			while (($file = readdir($handle)) !== FALSE)
			{
				// ignore relative dirs, hidden files, and the _thumb(s) folder
				if (substr($file, 0, 1) == '.' || $file == '_thumb' || $file == '_thumbs') continue;

				$path = $folder . $file;

				if (is_dir($path)) $subfolders[] = array($file, $path.'/');
			}

			closedir($handle);
		}

		return $subfolders;
	}

	/**
	 * Get All Folders
	 */
	function get_all_folders()
	{
		$folders = array();

		$filedirs = $this->get_filedir_prefs();

		foreach ($filedirs->result() as $filedir)
		{
			$tag_path = "{filedir_{$filedir->id}}";
			$folders[] = $tag_path;

			$this->get_all_subfolders($folders, $tag_path, $this->get_server_path($filedir));
		}

		return $folders;
	}

	/**
	 * Get All Subfolders
	 */
	function get_all_subfolders(&$folders, $tag_path, $real_path)
	{
		$subfolders = $this->get_subfolders($real_path);

		foreach ($subfolders as $folder)
		{
			$folder_tag_path = $tag_path.$folder[0].'/';
			$folder_real_path = $real_path.$folder[0].'/';

			// add this subfolder
			$folders[] = $folder_tag_path;

			// add any sub-subfolders
			$this->get_all_subfolders($folders, $folder_tag_path, $folder_real_path);
		}
	}

	/**
	 * Get Files
	 */
	function get_files_in_folder($folder)
	{
		$files = array();

		if (is_dir($folder) && ($handle = opendir($folder)))
		{
			while (($file = readdir($handle)) !== FALSE)
			{
				// ignore hidden files
				if (substr($file, 0, 1) == '.') continue;

				$path = $folder . $file;

				// ignore folders
				if (is_dir($path)) continue;

				$files[] = $file;
			}

			closedir($handle);
		}

		return $files;
	}

	// --------------------------------------------------------------------

	/**
	 * Prepare File for View
	 *
	 * Make sure we know everything about a file we need
	 * before spitting out its HTML in the view
	 */
	function prep_file_for_view(&$file)
	{
		if (is_string($file))
		{
			$file = array('file_path' => $file);
		}

		if (! isset($file['full_path']) && ! isset($file['folder']))
		{
			$this->parse_filedir_path($file['file_path'], $filedir, $subpath);

			$file['filedir_id']   = $filedir->id;
			$file['filedir_path'] = $this->get_server_path($filedir);
			$file['filedir_url']  = $filedir->url;
			$file['full_path']    = $this->get_server_path($filedir) . $subpath;
			$file['folder']       = $filedir->name . ($subpath ? '/'.$subpath : '');
			$file['subpath']      = $subpath;
		}

		if (! isset($file['file_name']))
		{
			$file['file_name'] = basename($file['full_path']);
		}

		if (! isset($file['file_size']))
		{
			$file['file_size'] = filesize($file['full_path']);
		}

		if (! isset($file['date']))
		{
			$file['date'] = filemtime($file['full_path']);
		}

		if (! isset($file['url']))
		{
			$file['url'] = $file['filedir_url'] . $file['subpath'] . $file['file_name'];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get Selected Files
	 *
	 * Take a list of asset_ids mixed with file paths, and return the files view
	 */
	function get_selected_files($selected_files)
	{
		$asset_ids_keys = array();

		// weed out the asset_ids
		foreach ($selected_files as $key => $file)
		{
			// is this an asset ID?
			if (ctype_digit($file))
			{
				$asset_ids_keys[$file] = $key;
			}
			else
			{
				$selected_files[$key] = array('file_path' => $file);
			}
		}

		if ($asset_ids_keys)
		{
			// get the filenames for the asset_ids
			$query = $this->EE->db->select('asset_id, file_path')
			                      ->where_in('asset_id', array_keys($asset_ids))
			                      ->get('assets');

			foreach ($query->result_array() as $asset)
			{
				$key = $assets_ids_keys[$asset['asset_id']];
				$selected_files[$key] = $asset;
			}
		}

		return $this->EE->load->view('listview/listview', array(), TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse File Directory Path
	 */
	function parse_filedir_path($path, &$filedir, &$subpath)
	{
		// is this actually a {filedir_x} path?
		if (preg_match('/^\{filedir_(\d+)\}?(.*)/', $path, $match))
		{
			// is this a valid file directory?
			if ($filedir = $this->get_filedir_prefs($match[1])->row())
			{
				$subpath = ltrim($match[2], '/');
			}
		}
	}

	/**
	 * Validate File Path
	 */
	function validate_file_path($file_path)
	{
		// is it actually set to something?
		if (! $file_path) return FALSE;

		$this->parse_filedir_path($file_path, $filedir, $subpath);
		if (! $filedir || ! $subpath) return FALSE;

		$full_path = $this->get_server_path($filedir) . $subpath;

		// does the file exist, and it actually a file?
		return (file_exists($full_path) && is_file($full_path)) ? $full_path : FALSE;
	}


	var $file_kinds = array(
		'access'      => array('adp','accdb','mdb'),
		'audio'       => array('wav','aif','aiff','aifc','m4a','wma','mp3','aac','oga'),
		'excel'       => array('xls'),
		'flash'       => array('fla','swf'),
		'html'        => array('html','htm'),
		'illustrator' => array('ai'),
		'image'       => array('jpg','jpeg','tiff','tif','png','gif','bmp','webp'),
		'pdf'         => array('pdf'),
		'photoshop'   => array('psd','psb'),
		'php'         => array('php'),
		'text'        => array('txt','text'),
		'video'       => array('mov','m4v','wmv','avi','flv','mp4','ogg','ogv','rm'),
		'word'        => array('doc','docx')
	);

	var $filesize_units = array('B', 'KB', 'MB', 'GB');

	/**
	 * Get File Kind
	 */
	function get_kind($file)
	{
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

		foreach ($this->file_kinds as $kind => &$extensions)
		{
			if (in_array($ext, $extensions))
			{
				return $kind;
			}
		}

		return 'file';
	}

	/**
	 * Format Date
	 */
	function format_date($date)
	{
		return date('M j, Y g:s A', $date);
	}

	/**
	 * Format File Size
	 */
	function format_filesize($filesize)
	{
		// get the formatted size
		foreach ($this->filesize_units as $i => $unit)
		{
			// round up to next unit at 0.95
			if (! isset($this->filesize_units[$i+1]) || $filesize < (pow(1000, $i+1) * 0.95))
			{
				return ($i ? round($filesize / pow(1000, $i)) : $filesize) . ' '.$unit;
			}
		}

	}

}
