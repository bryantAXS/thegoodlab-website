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
	}

	// -----------------------------------------------------------------------

	/**
	 * Theme URL
	 */
	private function _theme_url()
	{
		if (! isset($this->cache['theme_url']))
		{
			$theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : $this->EE->config->slash_item('theme_folder_url').'third_party/';
			$this->cache['theme_url'] = $theme_folder_url.'assets/';
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
			$this->insert_lang_js('upload_a_file', 'upload_status', 'showing', 'of', 'file', 'files', 'selected', 'cancel', 'save_changes', 'new_subfolder', 'rename', '_delete', 'view_file', 'edit_file', 'remove_file', 'remove_files');

			$this->insert_js('Assets.siteId = '.$this->EE->config->item('site_id'));

			$this->cache['included_sheet_resources'] = TRUE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Add Package Path
	 */
	function add_package_path()
	{
		$this->EE->load->add_package_path(PATH_THIRD.'assets/');

		// manually add the view path if this is less than EE 2.1.5
		if (version_compare(APP_VER, '2.1.5', '<'))
		{
			$this->EE->load->_ci_view_path = PATH_THIRD.'assets/views/';
		}
	}

	/**
	 * Site URL
	 */
	private function _site_url()
	{
		if (! isset($this->cache['site_url']))
		{
			if (! ($site_url = $this->EE->config->item('assets_site_url')))
			{
				$site_url = $this->EE->functions->fetch_site_index(0, 0);

				if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
				{
					$site_url = str_replace('http://', 'https://', $site_url);
				}
			}

			$this->cache['site_url'] = $site_url;
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
	 * Get File
	 */
	function get_file($file_path)
	{
		if (! isset($this->cache['files'][$file_path]))
		{
			if (! class_exists('Assets_file'))
			{
				require_once PATH_THIRD.'assets/models/file.php';
			}

			$this->cache['files'][$file_path] = new Assets_file($file_path);
		}

		return $this->cache['files'][$file_path];
	}

	// --------------------------------------------------------------------

	/**
	 * Get Denied Upload Directories
	 */
	function get_denied_filedirs()
	{
		if (! isset($this->cache['denied_filedirs']))
		{
			$denied = array();

			$group = $this->EE->session->userdata('group_id');

			if ($group != 1)
			{
				$no_access = $this->EE->db->select('upload_id')
				                          ->where('member_group', $group)
				                          ->get('upload_no_access');

				if ($no_access->num_rows() > 0)
				{
					foreach ($no_access->result() as $result)
					{
						$denied[] = $result->upload_id;
					}
				}
			}

			$this->cache['denied_filedirs'] = $denied;
		}

		return $this->cache['denied_filedirs'];
	}

	/**
	 * Get File Directory Preferences
	 */
	function get_filedir_prefs($filedirs = 'all', $site_id = NULL)
	{
		// -------------------------------------------
		//  Figure out what we already have cached
		// -------------------------------------------

		if ($filedirs == 'all')
		{
			$run_query = ! isset($this->cache['filedir_prefs']['all']);
		}
		else
		{
			if (($return_single = ! is_array($filedirs)))
			{
				$filedirs = array($filedirs);
			}

			// figure out which of these we don't already have cached
			foreach ($filedirs as $filedir)
			{
				if (! isset($this->cache['filedir_prefs'][$filedir]))
				{
					$not_cached[] = $filedir;
				}
			}

			$run_query = isset($not_cached);
		}

		// -------------------------------------------
		//  Query and cache the remaining filedirs
		// -------------------------------------------

		if ($run_query)
		{
			// enforce access permissions for non-Super Admins, except on front-end pages
			if (REQ != 'PAGE' && ($denied = $this->get_denied_filedirs()))
			{
				$this->EE->db->where_not_in('id', $denied);
			}

			if ($filedirs != 'all')
			{
				// limit to specific upload directories
				$this->EE->db->where_in('id', $filedirs);
			}
			else
			{
				// limit to upload directories from the current site, except on front-end pages
				if (REQ != 'PAGE')
				{
					if (! $site_id)
					{
						$site_id = $this->EE->config->item('site_id');
					}

					$this->EE->db->where('site_id', $site_id);
				}

				// order by name
				$upload_prefs = $this->EE->db->order_by('name');
			}

			// run the query
			$query = $this->EE->db->get('upload_prefs')->result();

			// grab the upload pref overrides once here
			$overrides = $this->EE->config->item('upload_preferences');

			// cache the results
			foreach ($query as $filedir)
			{
				// is this filedir's prefs overridden in config.php?
				if ($overrides && isset($overrides[$filedir->id]))
				{
					foreach ($overrides[$filedir->id] as $pref => $value)
					{
						$filedir->$pref = $value;
					}
				}

				if (REQ != 'CP')
				{
					// relative paths are always relative to the system directory,
					// but Assets' AJAX functions are loaded via the site URL
					// so attempt to turn relative paths into absolute paths
					if (! preg_match('/^(\/|\\\|[a-zA-Z]+:)/', $filedir->server_path))
					{
						// if the CP is masked, there's no way for us to determine the path to the CP's entry point
						// so people with relative upload directory paths _and_ masked CPs will have to point us in the right direction
						if ($cp_path = $this->EE->config->item('assets_cp_path'))
						{
							$filedir->server_path = rtrim($cp_path, '/').'/'.$filedir->server_path;
						}
						else
						{
							$filedir->server_path = SYSDIR.'/'.$filedir->server_path;
						}
					}
				}

				$this->cache['filedir_prefs'][$filedir->id] = $filedir;
			}

			if ($filedirs == 'all')
			{
				$this->cache['filedir_prefs']['all'] = $query;
			}
		}

		// -------------------------------------------
		//  Sort and return the upload prefs
		// -------------------------------------------

		if ($filedirs == 'all')
		{
			return $this->cache['filedir_prefs']['all'];
		}

		if ($return_single)
		{
			return isset($this->cache['filedir_prefs'][$filedirs[0]]) ? $this->cache['filedir_prefs'][$filedirs[0]] : FALSE;
		}

		$r = array();

		foreach ($filedirs as $filedir)
		{
			if (isset($this->cache['filedir_prefs'][$filedir]))
			{
				$r[] = $this->cache['filedir_prefs'][$filedir];
				$sort_names[] = strtolower($this->cache['filedir_prefs'][$filedir]->name);
			}
		}

		if ($r)
		{
			array_multisort($sort_names, SORT_ASC, SORT_STRING, $r);
		}

		return $r;
	}

	/**
	 * Is a Folder?
	 */
	function is_folder($path)
	{
		return (file_exists($path) && is_dir($path));
	}

	/**
	 * Get Subfolders
	 */
	function get_subfolders($folder)
	{
		$subfolders = array();

		// make sure the folder path has a trailing slash
		$folder = rtrim($folder, '/') . '/';

		if ($this->is_folder($folder) && ($files = scandir($folder)))
		{
			foreach ($files as $file)
			{
				// only include actual folders, except those that begin with "_"
				if (is_dir($folder.$file) && strncmp($file, '.', 1) != 0 && strncmp($file, '_', 1) != 0)
				{
					$subfolders[] = $file;
				}
			}
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

		foreach ($filedirs as $filedir)
		{
			$tag_path = "{filedir_{$filedir->id}}";
			$folders[] = $tag_path;

			$this->get_all_subfolders($folders, $tag_path, $filedir->server_path);
		}

		return $folders;
	}

	/**
	 * Get All Subfolders
	 */
	function get_all_subfolders(&$folders, $tag_path, $server_path)
	{
		$subfolders = $this->get_subfolders($server_path);

		foreach ($subfolders as $subfolder)
		{
			$folder_tag_path = $tag_path.$subfolder.'/';
			$folder_server_path = $server_path.$subfolder.'/';

			// add this subfolder
			$folders[] = $folder_tag_path;

			// add any sub-subfolders
			$this->get_all_subfolders($folders, $folder_tag_path, $folder_server_path);
		}
	}

	/**
	 * Get Files in Folder
	 */
	function get_files_in_folder($folder)
	{
		$r = array();

		if ($this->is_folder($folder) && ($files = scandir($folder)))
		{
			foreach ($files as $file)
			{
				// only include non-hidden files
				if (! is_dir($folder.$file) && strncmp($file, '.', 1) != 0)
				{
					$r[] = $file;
				}
			}
		}

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Sort Files
	 */
	function sort_files(&$files, $orderby, $sort)
	{
		// ignore if no files
		if (! $files) return;

		if (! in_array($orderby, array('name', 'folder', 'date', 'file_size'))) $orderby = 'name';
		if (! in_array($sort, array('asc', 'desc'))) $sort = 'asc';

		foreach ($files as &$file)
		{
			$sort_names[] = strtolower($file->filename());
			$sort_folders[] = $file->folder();
			if ($orderby == 'file_size') $sort_sizes[] = $file->size();
			else if ($orderby == 'date') $sort_dates[] = $file->date_modified();
		}

		$SORT = ($sort == 'asc') ? SORT_ASC : SORT_DESC;

		switch ($orderby)
		{
			case 'name':
				// sort by name, then folder
				array_multisort($sort_names, $SORT, SORT_STRING, $sort_folders, SORT_ASC, SORT_STRING, $files);
				break;

			case 'folder':
				// sort by folder, then name
				array_multisort($sort_folders, $SORT, SORT_STRING, $sort_names, SORT_ASC, SORT_STRING, $files);
				break;

			case 'date':
				// sort by date, then name, then folder
				array_multisort($sort_dates, $SORT, SORT_NUMERIC, $sort_names, SORT_ASC, SORT_STRING, $sort_folders, SORT_ASC, SORT_STRING, $files);
				break;

			case 'file_size':
				// sort by size, then name, then folder
				array_multisort($sort_sizes, $SORT, SORT_NUMERIC, $sort_names, SORT_ASC, SORT_STRING, $sort_folders, SORT_ASC, SORT_STRING, $files);
				break;
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
			if ($filedir = $this->get_filedir_prefs($match[1]))
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

		$full_path = $filedir->server_path . $subpath;

		// does the file exist, and it actually a file?
		return (file_exists($full_path) && is_file($full_path)) ? $full_path : FALSE;
	}


	var $file_kinds = array(
		'access'      => array('adp','accdb','mdb'),
		'audio'       => array('wav','aif','aiff','aifc','m4a','wma','mp3','aac','oga'),
		'excel'       => array('xls', 'xlsx'),
		'flash'       => array('fla','swf'),
		'html'        => array('html','htm'),
		'illustrator' => array('ai'),
		'image'       => array('jpg','jpeg','jpe','tiff','tif','png','gif','bmp','webp'),
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

// -----------------------------------------------------------------------
//  Keep a single instance of the Assets helper
// -----------------------------------------------------------------------

/**
 * Get Assets Helper
 */
function get_assets_helper()
{
	global $assets_helper;

	if (! $assets_helper)
	{
		$assets_helper = new Assets_helper;
	}

	return $assets_helper;
}
