<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Wygwam Helper Class for EE2
*/
class Wygwam_Helper {

	public $entry_site_id = null;

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------

		if (! isset($this->EE->session->cache['wygwam']))
		{
			$this->EE->session->cache['wygwam'] = array();
		}
		$this->cache =& $this->EE->session->cache['wygwam'];
	}

	// --------------------------------------------------------------------

	/**
	 * Get Global Settings
	 */
	function get_global_settings()
	{
		if (! isset($this->cache['global_settings']))
		{
			$defaults = array(
				'license_key' => '',
				'file_browser' => 'ee'
			);

			$query = $this->EE->db->select('settings')
			                      ->where('name', 'wygwam')
			                      ->get('fieldtypes');

			$settings = unserialize(base64_decode($query->row('settings')));

			$this->cache['global_settings'] = array_merge($defaults, $settings);
		}

		return $this->cache['global_settings'];
	}

	// --------------------------------------------------------------------

	/**
	 * Toolbar button groupings, based on CKEditor's default "Full" toolbar
	 */
	function tb_groups()
	{
		return array(
			array('Source'),
			array('Save', 'NewPage', 'Preview'),
			array('Templates'),
			array('Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord'),
			array('Print', 'SpellChecker', 'Scayt'),
			array('Undo', 'Redo'),
			array('Find', 'Replace'),
			array('SelectAll', 'RemoveFormat'),
			array('Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'),
			array('Bold', 'Italic', 'Underline', 'Strike'),
			array('Subscript', 'Superscript'),
			array('NumberedList', 'BulletedList'),
			array('Outdent', 'Indent', 'Blockquote', 'CreateDiv'),
			array('JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'),
			array('Link', 'Unlink', 'Anchor'),
			array('Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'ReadMore', 'EmbedMedia'),
			array('Styles'),
			array('Format'),
			array('Font'),
			array('FontSize'),
			array('TextColor', 'BGColor'),
			array('Maximize', 'ShowBlocks'),
			array('About')
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Record of which toolbar items are selects
	 */
	function tb_selects()
	{
		return array('Styles', 'Format', 'Font', 'FontSize');
	}

	/**
	 * Record of which toolbar buttons' class names differ from their input value
	 */
	function tb_class_overrides()
	{
		return array(
			'SpellChecker'   => 'checkspell',
			'SelectAll'      => 'selectAll',
			'RemoveFormat'   => 'removeFormat'
		);
	}

	/**
	 * The real toolbar button names
	 */
	function tb_label_overrides()
	{
		return array(
			'NewPage'        => 'New Page',
			'PasteText'      => 'Paste As Plain Text',
			'PasteFromWord'  => 'Paste from Word',
			'SpellChecker'   => 'Check Spelling',
			'Scayt'          => 'Spell Check As You Type',
			'SelectAll'      => 'Select All',
			'RemoveFormat'   => 'Remove Format',
			'Radio'          => 'Radio Button',
			'TextField'      => 'Text Field',
			'Select'         => 'Selection Field',
			'ImageButton'    => 'Image Button',
			'HiddenField'    => 'Hidden Field',
			'Strike'         => 'Strike Through',
			'NumberedList'   => 'Insert/Remove Numbered List',
			'BulletedList'   => 'Insert/Remove Bulleted List',
			'Outdent'        => 'Decrease Indent',
			'Indent'         => 'Increase Indent',
			'CreateDiv'      => 'Create Div Container',
			'JustifyLeft'    => 'Left Justify',
			'JustifyRight'   => 'Right Justify',
			'JustifyCenter'  => 'Center Justify',
			'JustifyBlock'   => 'Block Justify',
			'HorizontalRule' => 'Insert Horizontal Line',
			'SpecialChar'    => 'Insert Special Character',
			'PageBreak'      => 'Insert Page Break for Printing',
			'Format'         => 'Format',
			'Font'           => 'Font',
			'FontSize'       => 'Size',
			'TextColor'      => 'Text Color',
			'BGColor'        => 'Background Color',
			'ShowBlocks'     => 'Show Blocks',
			'About'          => 'About CKEditor',
			'EmbedMedia'     => 'Embed Media',
			'ReadMore'       => 'Read More'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Mapping of EE languages to CKEditor language codes
	 */
	function lang_map()
	{
		return array(
			'arabic'              => 'ar',
			'arabic-utf8'         => 'ar',
			'arabic-windows-1256' => 'ar',
			'czech'               => 'cs',
			'cesky'               => 'cs',
			'danish'              => 'da',
			'german'              => 'de',
			'deutsch'             => 'de',
			'english'             => 'en',
			'spanish'             => 'es',
			'spanish_ee201pb'     => 'es',
			'finnish'             => 'fi',
			'french'              => 'fr',
			'hungarian'           => 'hu',
			'croatian'            => 'hr',
			'italian'             => 'it',
			'japanese'            => 'ja',
			'korean'              => 'ko',
			'dutch'               => 'nl',
			'norwegian'           => 'no',
			'polish'              => 'pl',
			'brazilian'           => 'pt',
			'portuguese'          => 'pt',
			'brasileiro'          => 'pt',
			'brasileiro_160'      => 'pt',
			'russian'             => 'ru',
			'russian_utf8'        => 'ru',
			'russian_win1251'     => 'ru',
			'slovak'              => 'sk',
			'swedish'             => 'sv',
			'swedish_ee20pb'      => 'sv',
			'turkish'             => 'tr',
			'ukrainian'           => 'uk',
			'chinese'             => 'zh',
			'chinese_traditional' => 'zh',
			'chinese_simplified'  => 'zh'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Default Config
	 */
	function default_config_settings()
	{
		$toolbars = $this->default_toolbars();

		return array(
			'toolbar' => $toolbars['Basic'],
			'height' => '200',
			'resize_enabled' => 'y',
			'contentsCss' => array(),
			'upload_dir' => ''
		);
	}

	/**
	 * Default Configs
	 */
	function default_toolbars()
	{
		return array(
			'Basic' => array('Bold','Italic','Underline','Strike','NumberedList','BulletedList','Link','Unlink','Anchor','About'),
			'Full'  => array('Source','Save','NewPage','Preview','Templates','Cut','Copy','Paste','PasteText','PasteFromWord','Print','SpellChecker','Scayt','Undo','Redo','Find','Replace','SelectAll','RemoveFormat','Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField','/',
			                 'Bold','Italic','Underline','Strike','Subscript','Superscript','NumberedList','BulletedList','Outdent','Indent','Blockquote','CreateDiv','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','Link','Unlink','Anchor','Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','ReadMore','EmbedMedia','/',
			                 'Styles','Format','Font','FontSize','TextColor','BGColor','Maximize','ShowBlocks','About')
		);
	}

	/**
	 * Default Global Settings
	 */
	function default_global_settings()
	{
		return array(
			'license_key' => ''
		);
	}

	/**
	 * Default Field Settings
	 */
	function default_settings()
	{
		return array(
			'upload_dir' => ''
		);
	}

	function base_config()
	{
		return array_merge(array(
			'skin'               => 'wygwam2',
			'toolbarCanCollapse' => 'n',
			'dialog_backgroundCoverOpacity' => 0,
			'entities_processNumerical' => 'y',
			'forcePasteAsPlainText' => 'y'
		), $this->default_config_settings());
	}

	// --------------------------------------------------------------------

	/**
	 * Wygwam Custom Toolbar
	 * 
	 * Converts flat array of buttons into multi-dimentional
	 * array of toolgroups and their buttons
	 */
	function custom_toolbar($buttons, $include_missing = FALSE)
	{
		$toolbar = array();

		//foreach ($buttons as $button)
		//{
		//	$toolbar[] = ($button == '/' ? '/' : array($button));
		//}
		//return $toolbar;

		// group buttons by toolgroup
		$tb_groups = $this->tb_groups();
		foreach($tb_groups as $group_index => &$group)
		{
			$group_selection_index = NULL;
			$missing = array();
			foreach($group as $button_index => &$button)
			{
				// selected?
				if (($button_selection_index = array_search($button, $buttons)) !== FALSE)
				{
					if ($group_selection_index === NULL) $group_selection_index = $button_selection_index;
					if ( ! isset($toolbar[$group_selection_index])) $toolbar[$group_selection_index] = array();
					$toolbar[$group_selection_index]['b'.$button_index] = $button;
				}
				else if ($include_missing)
				{
					$missing['b'.$button_index] = '!'.$button;
				}
			}
			if ($group_selection_index !== NULL)
			{
				if ($include_missing) $toolbar[$group_selection_index] = array_merge($missing, $toolbar[$group_selection_index]);
				ksort($toolbar[$group_selection_index]);
				$toolbar[$group_selection_index] = array_values($toolbar[$group_selection_index]);
			}
		}

		// add newlines
		foreach(array_keys($buttons, '/') as $key) $toolbar[$key] = '/';

		// sort by keys and remove them
		ksort($toolbar);
		$r = array();
		foreach($toolbar as $toolgroup) array_push($r, $toolgroup);
		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Config Booleans
	 */
	function config_booleans()
	{
		return array(
			'autoGrow_onStartup',
			'autoParagraph',
			'colorButton_enableMore',
			'disableNativeSpellChecker',
			'disableObjectResizing',
			'disableReadonlyStyling',
			'editingBlock',
			'entities',
			'entities_greek',
			'entities_latin',
			'entities_processNumerical',
			'fillEmptyBlocks',
			'forceEnterMode',
			'forcePasteAsPlainText',
			'forceSimpleAmpersand',
			'fullPage',
			'htmlEncodeOutput',
			'ignoreEmptyParagraph',
			'image_removeLinkByEmptyURL',
			'pasteFromWordNumberedHeadingToList',
			'pasteFromWordPromptCleanup',
			'pasteFromWordRemoveFontStyles',
			'pasteFromWordRemoveStyles',
			'readOnly',
			'resize_enabled',
			'startupFocus',
			'startupOutlineBlocks',
			'templates_replaceContent',
			'toolbarCanCollapse',
			'toolbarGroupCycling',
			'toolbarStartupExpanded'
		);
	}

	/**
	 * Config Lists
	 */
	function config_lists()
	{
		return array(
			'contentsCss',
			'templates_files'
		);
	}

	/**
	 * Config Literals
	 */
	function config_literals()
	{
		return array(
			'enterMode',
			'on',
			'stylesheetParser_skipSelectors',
			'stylesheetParser_validSelectors',
			'filebrowserBrowseFunc',
			'filebrowserLinkBrowseFunc',
			'filebrowserImageBrowseFunc',
			'filebrowserFlashBrowseFunc',
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Theme URL
	 */
	function theme_url()
	{
		if (! isset($this->cache['theme_url']))
		{
			$theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : $this->EE->config->slash_item('theme_folder_url').'third_party/';
			$this->cache['theme_url'] = $theme_folder_url.'wygwam/';
		}

		return $this->cache['theme_url'];
	}

	/**
	 * Include Theme CSS
	 */
	function include_theme_css($file)
	{
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->theme_url().$file.'" />');
	}

	/**
	 * Include Theme JS
	 */
	function include_theme_js($file)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->theme_url().$file.'"></script>');
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
	 * Get Upload Preferences
	 * @param  int $group_id Member group ID specified when returning allowed upload directories only for that member group
	 * @param  int $id       Specific ID of upload destination to return
	 * @return array         Result array of DB object, possibly merged with custom file upload settings (if on EE 2.4+)
	 */
	function get_upload_preferences($group_id = NULL, $id = NULL)
	{
		if (version_compare(APP_VER, '2.4', '>='))
		{
			$this->EE->load->model('file_upload_preferences_model');
			return $this->EE->file_upload_preferences_model->get_file_upload_preferences($group_id, $id);
		}

		if (version_compare(APP_VER, '2.1.5', '>='))
		{
			$this->EE->load->model('file_upload_preferences_model');
			$result = $this->EE->file_upload_preferences_model->get_upload_preferences($group_id, $id);
		}
		else
		{
			$this->EE->load->model('tools_model');
			$result = $this->EE->tools_model->get_upload_preferences($group_id, $id);
		}

		// If an $id was passed, just return that directory's preferences
		if ( ! empty($id))
		{
			return $result->row_array();
		}

		// Use upload destination ID as key for row for easy traversing
		$return_array = array();
		foreach ($result->result_array() as $row)
		{
			$return_array[$row['id']] = $row;
		}

		return $return_array;
	}

	// --------------------------------------------------------------------

	/**
	 * Is Pages Installed?
	 */
	private function _is_pages_mod_installed()
	{
		if (! isset($this->cache['pages_module_installed']))
		{
			$query = $this->EE->db->get_where('modules', 'module_name = "Pages"');
			$this->cache['pages_module_installed'] = $query->num_rows() ? TRUE : FALSE;
		}

		return $this->cache['pages_module_installed'];
	}

	// --------------------------------------------------------------------

	/**
	 * Get Site Pages
	 */
	private function _get_site_pages()
	{
		$site_id = $this->EE->config->item('site_id');

		// Is this entry from a different site?
		$different_site = ($this->entry_site_id && $site_id != $this->entry_site_id);
		$entry_site_id = $different_site ? $this->entry_site_id : $site_id;

		if (! isset($this->cache['site_pages'][$entry_site_id]))
		{
			// Temporarily swap the site config over to the entry's site
			if ($different_site)
			{
				$this->EE->config->site_prefs('', $entry_site_id);
			}

			$pages = $this->EE->config->item('site_pages');

			if (is_array($pages) && !empty($pages[$entry_site_id]['uris']))
			{
				// grab a copy of this site's pages
				$site_pages = array_merge($pages[$entry_site_id]);

				// sort by uris
				natcasesort($site_pages['uris']);

				$this->cache['site_pages'][$entry_site_id] = $site_pages;
			}
			else
			{
				$this->cache['site_pages'][$entry_site_id] = array();
			}

			// Return the config to the actual site
			if ($different_site)
			{
				$this->EE->config->site_prefs('', $site_id);
			}
		}

		return $this->cache['site_pages'][$entry_site_id];
	}

	/**
	 * Get Pages Data
	 */
	private function _get_pages_mod_data()
	{
		if (! isset($this->cache['page_data']))
		{
			$this->cache['page_data'] = array();

			if (($pages = $this->_get_site_pages()) && ($page_ids = array_filter(array_keys($pages['uris']))))
			{
				$query = $this->EE->db->query('SELECT entry_id, channel_id, title, url_title, status
											   FROM exp_channel_titles
											   WHERE entry_id IN ('.implode(',', $page_ids).')
											   ORDER BY entry_id DESC');

				// index entries by entry_id
				$entry_data = array();
				foreach ($query->result_array() as $entry)
				{
					$entry_data[$entry['entry_id']] = $entry;
				}

				foreach ($pages['uris'] as $entry_id => $uri)
				{
					if (! isset($entry_data[$entry_id])) continue;
					$entry = $entry_data[$entry_id];

					$url = $this->EE->functions->create_page_url($pages['url'], $uri);
					if (!$url || $url == '/') continue;

					$this->cache['page_data'][] = array(
						$entry_id,
						$entry['channel_id'],
						$entry['title'],
						'0',
						$url
					);
				}
			}

			// sort by entry title
			if(count($this->cache['page_data']) > 0)
			{
				$this->cache['page_data'] = $this->_subval_sort($this->cache['page_data'], 2);
			}
		}

		return $this->cache['page_data'];
	}

	/**
	 * Sorts a multidimensional array on an internal array's key.
	 */
	private function _subval_sort($initial_array, $sub_key)
	{
		$sorted_array = array();

		foreach ($initial_array as $key => $value)
		{
			$temp_array[$key] = strtolower($value[$sub_key]);
		}

		asort($temp_array);

		foreach ($temp_array as $key => $value)
		{
			$sorted_array[] = $initial_array[$key];
		}

		return $sorted_array;
	}

	// --------------------------------------------------------------------

	/**
	 * Gets all site page data from the pages module
	 */
	function get_all_page_data($install_check = TRUE)
	{
		$page_data = array();

		if ($install_check)
		{
			if ($this->_is_pages_mod_installed())
			{
				$page_data = $this->_get_pages_mod_data();
			}
		}
		else
		{
			$page_data = $this->_get_pages_mod_data();
		}

		return $page_data;
	}
}
