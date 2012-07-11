<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'wygwam/config.php';
require_once PATH_THIRD.'wygwam/helper.php';


/**
 * Wygwam Fieldtype Class
 *
 * @package   Wygwam
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Wygwam_ft extends EE_Fieldtype {

	var $info = array(
		'name'    => WYGWAM_NAME,
		'version' => WYGWAM_VER
	);

	var $has_array_data = TRUE;

	/**
	 * Fieldtype Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->helper = new Wygwam_Helper();

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
	 * Install
	 */
	function install()
	{
		if (! class_exists('FF2EE2')) require_once PATH_THIRD.'wygwam/lib/ff2ee2/ff2ee2.php';

		$converter = new FF2EE2('wygwam');
		return $converter->global_settings;
	}

	// --------------------------------------------------------------------

	/**
	 * Update
	 */
	function update($from)
	{
		// update the module version number
		$this->EE->db->where('module_name', WYGWAM_NAME)
		             ->update('modules', array('module_version' => WYGWAM_VER));

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Display Global Settings
	 */
	function display_global_settings()
	{
		if ($this->EE->addons_model->module_installed('wygwam'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wygwam');
		}
		else
		{
			$this->EE->lang->loadfile('wygwam');
			$this->EE->session->set_flashdata('message_failure', lang('wygwam_no_module'));
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Field Settings
	 */
	private function _field_settings($settings, $matrix = FALSE)
	{
		// load the language file
		$this->EE->lang->loadfile('wygwam');

		$r = array();

		// -------------------------------------------
		//  Editor Configuration
		// -------------------------------------------

		if ($this->EE->db->table_exists('wygwam_configs'))
		{
			$this->EE->db->select('config_id, config_name');
			$this->EE->db->order_by('config_name');
			$query = $this->EE->db->get('wygwam_configs');

			if ($query->num_rows())
			{
				$configs = array();
				foreach($query->result_array() as $config)
				{
					$configs[$config['config_id']] = $config['config_name'];
				}

				$config = isset($settings['config']) ? $settings['config'] : '';
				$config_setting = form_dropdown('wygwam[config]', $configs, $config, 'id="wygwam_config"');
			}
			else
			{
				$config_setting = lang('wygwam_no_configs');
			}
		}
		else
		{
			$config_setting = lang('wygwam_no_module');
		}

		$r[] = array(
			lang('wygwam_editor_config', 'wygwam_config'),
			$config_setting . NBS.NBS . ' <a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wygwam'.AMP.'method=configs">'.lang('wygwam_edit_configs').'</a>'
		);

		// -------------------------------------------
		//  Defer
		// -------------------------------------------

		$defer = isset($settings['defer']) ? $settings['defer'] : 'n';

		$r[] = array(
			lang('wygwam_defer', 'wygwam_defer') . ($matrix ? '' : '<br/>' . lang('wygwam_defer_desc')),
			form_dropdown('wygwam[defer]', array('n'=>lang('no'), 'y'=>lang('yes')), $defer, 'id="wygwam_defer"')
		);


		return $r;
	}

	/**
	 * Display Field Settings
	 */
	function display_settings($settings)
	{
		$settings = array_merge($this->helper->default_settings(), $settings);

		$rows = $this->_field_settings($settings);

		// -------------------------------------------
		//  Field Conversion
		// -------------------------------------------

		// was this previously a different fieldtype?
		if ($settings['field_id'] && $settings['field_type'] != 'wygwam')
		{
			array_unshift($rows, array(
				lang('wygwam_convert', 'wygwam_convert').'<br />'.$this->EE->lang->line('wygwam_convert_desc'),
				form_dropdown('wygwam[convert]',
					array(
						''        => '--',
						'auto'    => 'Auto &lt;br /&gt; or XHTML',
						'textile' => 'Textile'
					),
					(in_array($settings['field_fmt'], array('br', 'xhtml')) ? 'auto' : ''),
					'id="wygwam_convert"'
				)
			));
		}

		// add the rows
		foreach ($rows as $row)
		{
			$this->EE->table->add_row($row[0], $row[1]);
		}
	}

	/**
	 * Display Cell Settings
	 */
	function display_cell_settings($settings)
	{
		global $DSP;

		$settings = array_merge($this->helper->default_settings(), $settings);

		return $this->_field_settings($settings, TRUE);
	}

	/**
	 * Display Variable Settings
	 */
	function display_var_settings($settings)
	{
		$this->helper->insert_js('(function($){
		                            $("#wygwam").wrap($("<div />").attr("id", "ft_wygwam"));
		                          })(jQuery);');

		return $this->_field_settings($settings);
	}

	// --------------------------------------------------------------------

	/**
	 * Save Field Settings
	 */
	function save_settings($settings)
	{
		$settings = array_merge($this->EE->input->post('wygwam'));

		// cross the T's
		$settings['field_fmt'] = 'none';
		$settings['field_show_fmt'] = 'n';
		$settings['field_type'] = 'wygwam';

		// -------------------------------------------
		//  Field Conversion
		// -------------------------------------------

		if (isset($settings['convert']))
		{
			$field_id = $this->EE->input->post('field_id');
			if ($field_id && $settings['convert'])
			{
				$this->EE->db->select('entry_id, field_id_'.$field_id.' data, field_ft_'.$field_id.' format');
				$query = $this->EE->db->get_where('channel_data', 'field_id_'.$field_id.' != ""');

				if ($query->num_rows())
				{
					// prepare Typography
					$this->EE->load->library('typography');
					$this->EE->typography->initialize();

					// prepare Textile
					if ($settings['convert'] == 'textile')
					{
						if (! class_exists('Textile'))
						{
							require_once PATH_THIRD.'wygwam/lib/textile/textile.php';
						}

						$textile = new Textile();
					}

					foreach ($query->result_array() as $row)
					{
						$data = $row['data'];
						$this->_replace_file_tags($data);

						$convert = FALSE;

						// Auto <br /> and XHTML
						switch ($row['format'])
						{
							case 'br':    $convert = TRUE; $data = $this->EE->typography->nl2br_except_pre($data); break;
							case 'xhtml': $convert = TRUE; $data = $this->EE->typography->auto_typography($data); break;
						}

						// Textile
						if ($settings['convert'] == 'textile')
						{
							$convert = TRUE;
							$data = $textile->TextileThis($data);
						}

						// Save the new field data
						if ($convert)
						{
							$this->_replace_file_urls($data);

							$this->EE->db->query($this->EE->db->update_string('exp_channel_data',
								array(
									'field_id_'.$field_id => $data,
									'field_ft_'.$field_id => 'none'
								),
								'entry_id = '.$row['entry_id']
							));
						}
					}
				}
			}

			unset($settings['convert']);
		}

		return $settings;
	}

	/**
	 * Save Cell Settings
	 */
	function save_cell_settings($settings)
	{
		return $settings['wygwam'];
	}

	/**
	 * Save Variable Settings
	 */
	function save_var_settings()
	{
		return $this->EE->input->post('wygwam');
	}

	// --------------------------------------------------------------------

	/**
	 * Compare File URLs
	 */
	private function _cmp_file_urls($a, $b)
	{
		return -(strcmp(strlen($a), strlen($b)));
	}

	/**
	 * Fetch File Tags
	 */
	private function _fetch_file_tags($sort = FALSE)
	{
		if (! isset($this->cache['file_tags']))
		{
			$tags = array();
			$urls = array();

			if ($file_paths = $this->EE->functions->fetch_file_paths())
			{
				if ($sort)
				{
					uasort($file_paths, array(&$this, '_cmp_file_urls'));
				}

				foreach ($file_paths as $id => $url)
				{
					// ignore "/" URLs
					if ($url == '/') continue;

					$tags[] = LD.'filedir_'.$id.RD;
					$urls[] = $url;
				}
			}

			$this->cache['file_tags'] = array($tags, $urls);
		}

		return $this->cache['file_tags'];
	}

	/**
	 * Replace File Tags
	 */
	private function _replace_file_tags(&$data)
	{
		$tags = $this->_fetch_file_tags();
		$data = str_replace($tags[0], $tags[1], $data);
	}

	/**
	 * Replace File Paths
	 */
	private function _replace_file_urls(&$data)
	{
		$tags = $this->_fetch_file_tags();
		$data = str_replace($tags[1], $tags[0], $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Compare Page URLs
	 */
	private function _cmp_page_urls($a, $b)
	{
		return -(strcmp(strlen($a[4]), strlen($b[4])));
	}

	/**
	 * Fetch Page Tags
	 */
	private function _fetch_page_tags($sort = FALSE)
	{
		$tags = array();
		$urls = array();

		$page_data = $this->helper->get_all_page_data(FALSE);

		if ($sort)
		{
			usort($page_data, array(&$this, '_cmp_page_urls'));
		}

		foreach ($page_data as $page)
		{
			$tags[] = LD.'page_'.$page[0].RD;
			$urls[] = $page[4];
		}

		return array($tags, $urls);
	}

	/**
	 * Replace Page Tags
	 */
	private function _replace_page_tags(&$data)
	{
		if (strpos($data, LD.'page_') !== FALSE)
		{
			$tags = $this->_fetch_page_tags();
			$data = str_replace($tags[0], $tags[1], $data);
		}
	}

	/**
	 * Replace Page URLs
	 */
	private function _replace_page_urls(&$data)
	{
		$tags = $this->_fetch_page_tags(TRUE);
		$data = str_replace($tags[1], $tags[0], $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Config JSON
	 */
	private function _config_json()
	{
		// starting point
		$config = $this->helper->base_config();

		// -------------------------------------------
		//  Editor Config
		// -------------------------------------------

		if ($this->EE->db->table_exists('wygwam_configs')
			&& isset($this->settings['config']) && is_numeric($this->settings['config'])
			&& ($query = $this->EE->db->select('settings')->get_where('wygwam_configs', array('config_id' => $this->settings['config'])))
			&& $query->num_rows()
		)
		{
			// merge custom settings into config
			$custom_settings = unserialize(base64_decode($query->row('settings')));
			$config = array_merge($config, $custom_settings);
		}
		else
		{
			$this->settings['config'] = 'default';
		}

		// skip if already included
		if (in_array($this->settings['config'], $this->cache['included_configs']))
		{
			return;
		}

		// language
		if (! isset($config['language']) || ! $config['language'])
		{
			$lang_map = $this->helper->lang_map();
			$language = $this->EE->session->userdata('language');
			$config['language'] = isset($lang_map[$language]) ? $lang_map[$language] : 'en';
		}

		// toolbar
		if (is_array($config['toolbar']))
		{
			$config['toolbar'] = $this->helper->custom_toolbar($config['toolbar']);
		}

		// css
		if (! $config['contentsCss'])
		{
			unset($config['contentsCss']);
		}

		// extraPlugins
		$config['extraPlugins'] = (isset($config['extraPlugins']) && $config['extraPlugins'] ? $config['extraPlugins'].',' : '') . 'wygwam,embedmedia,readmore';

		// -------------------------------------------
		//  File Browser Config
		// -------------------------------------------

		$user_group = $this->EE->session->userdata('group_id');
		$upload_dir = isset($config['upload_dir']) ? $config['upload_dir'] : NULL;
		$upload_prefs = $this->helper->get_upload_preferences($user_group, $upload_dir);

		// before doing anything, make sure that the user has access to any upload directories
		// (taking into account the upload directory setting)
		if ($upload_prefs)
		{
			$file_browser = isset($this->settings['file_browser']) ? $this->settings['file_browser'] : 'ee';

			switch ($file_browser)
			{
				case 'ckfinder':

					// CKFinder can only pull files from a single upload directory, so make sure it's set
					if (! $upload_dir) break;

					if (! isset($_SESSION)) @session_start();
					if (! isset($_SESSION['wygwam_'.$config['upload_dir']])) $_SESSION['wygwam_'.$config['upload_dir']] = array();
					$sess =& $_SESSION['wygwam_'.$config['upload_dir']];

					// add the FCPATH if this is a relative path
					if (! preg_match('/^(\/|\\\|[a-zA-Z]+:)/', $upload_prefs['server_path']))
					{
						$upload_prefs['server_path'] = FCPATH . $upload_prefs['server_path'];
					}

					$sess['p'] = $upload_prefs['server_path'];
					$sess['u'] = $upload_prefs['url'];
					$sess['t'] = $upload_prefs['allowed_types'];
					$sess['s'] = $upload_prefs['max_size'];
					$sess['w'] = $upload_prefs['max_width'];
					$sess['h'] = $upload_prefs['max_height'];

					$config['filebrowserImageBrowseUrl'] = $this->helper->theme_url().'lib/ckfinder/ckfinder.html?Type=Images&id='.$config['upload_dir'];
					$config['filebrowserImageUploadUrl'] = $this->helper->theme_url().'lib/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images&id='.$config['upload_dir'];

					if ($upload_prefs['allowed_types'] == 'all')
					{
						$config['filebrowserBrowseUrl'] = $this->helper->theme_url().'lib/ckfinder/ckfinder.html?id='.$config['upload_dir'];
						$config['filebrowserUploadUrl'] = $this->helper->theme_url().'lib/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files&id='.$config['upload_dir'];
						$config['filebrowserFlashBrowseUrl'] = $this->helper->theme_url().'lib/ckfinder/ckfinder.html?Type=Flash&id='.$config['upload_dir'];
						$config['filebrowserFlashUploadUrl'] = $this->helper->theme_url().'lib/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash&id='.$config['upload_dir'];
					}

					break;

				case 'assets':

					// make sure Assets is actually installed
					// (otherwise, just use the EE File Manager)
					if (array_key_exists('assets', $this->EE->addons->get_installed()))
					{
						// include sheet resources
						if (! class_exists('Assets_helper'))
						{
							require PATH_THIRD.'assets/helper.php';
						}

						$assets_helper = new Assets_helper;
						$assets_helper->include_sheet_resources();

						// if no upload directory was set, just default to "all"
						if (! $upload_dir) $upload_dir = '"all"';

						$config['filebrowserBrowseFunc']      = 'function(params) { Wygwam.loadAssetsSheet(params, '.$upload_dir.', "any"); }';
						$config['filebrowserImageBrowseFunc'] = 'function(params) { Wygwam.loadAssetsSheet(params, '.$upload_dir.', "image"); }';
						$config['filebrowserFlashBrowseFunc'] = 'function(params) { Wygwam.loadAssetsSheet(params, '.$upload_dir.', "flash"); }';

						break;
					}

				default:

					// if no upload directory was set, just default to "all"
					if (! $upload_dir) $upload_dir = '"all"';

					$config['filebrowserBrowseFunc']      = 'function(params) { Wygwam.loadEEFileBrowser(params, '.$upload_dir.', "any"); }';
					$config['filebrowserImageBrowseFunc'] = 'function(params) { Wygwam.loadEEFileBrowser(params, '.$upload_dir.', "image"); }';

			}
		}

		// add any site page data to wygwam config
		if ($pages = $this->helper->get_all_page_data())
		{
			$this->EE->lang->loadfile('wygwam');
			$site_page_string = lang('wygwam_site_page');

			foreach ($pages as $page)
			{
				$config['link_types'][$site_page_string][] = array(
			            'label' => $page[2],
			            'url'   => $page[4]
				);
			}
		}

		// -------------------------------------------
		//  'wygwam_config' hook
		//   - Override any of the config settings
		// 
			if ($this->EE->extensions->active_hook('wygwam_config'))
			{
				$config = $this->EE->extensions->call('wygwam_config', $config, $this->settings);
			}
		// 
		// -------------------------------------------

		unset($config['upload_dir']);

		// -------------------------------------------
		//  JSONify Config and Return
		// -------------------------------------------

		$config_literals = $this->helper->config_literals();
		$config_booleans = $this->helper->config_booleans();

		$js = '';

		foreach ($config as $setting => $value)
		{
			if (! in_array($setting, $config_literals))
			{
				if (in_array($setting, $config_booleans))
				{
					$value = ($value == 'y' ? TRUE : FALSE);
				}

				$value = $this->EE->javascript->generate_json($value, TRUE);

				// Firefox gets an "Unterminated string literal" error if this line gets too long,
				// so let's put each new value on its own line
				if ($setting == 'link_types')
				{
					$value = str_replace('","', "\",\n\t\t\t\"", $value);
				}
			}

			$js .= ($js ? ','.NL : '')
			     . "\t\t".'"'.$setting.'": '.$value;
		}

		$this->helper->insert_js(NL."\t".'Wygwam.configs["'.$this->settings['config'].'"] = {'.NL.$js.NL."\t".'};'.NL);
		$this->cache['included_configs'][] = $this->settings['config'];
	}

	/**
	 * Field Includes
	 */
	private function _field_includes()
	{
		if (! isset($this->cache['included_configs']))
		{
			$this->helper->include_theme_js('lib/ckeditor/ckeditor.js');
			$this->helper->include_theme_js('scripts/wygwam.js');
			$this->helper->include_theme_css('styles/wygwam.css');

			$js = 'Wygwam.themeUrl = "'.$this->helper->theme_url().'";'
			    . 'Wygwam.ee2plus = '.(version_compare(APP_VER, '2.2', '>=') ? 'true' : 'false').';';

			$filedirs = $this->helper->get_upload_preferences(1);

			if ($filedirs)
			{
				foreach ($filedirs as $filedir)
				{
					$filedir_urls[$filedir['id']] = $filedir['url'];
				}

				$js .= 'Wygwam.filedirUrls = '.$this->EE->javascript->generate_json($filedir_urls, TRUE).';';
			}

			$this->helper->insert_js($js);

			$this->cache['included_configs'] = array();
		}
	}

	/**
	 * Display Field
	 */
	function display_field($data)
	{
		$this->_field_includes();
		$this->_config_json();

		$id = str_replace(array('[', ']'), array('_', ''), $this->field_name);
		$defer = (isset($this->settings['defer']) && $this->settings['defer'] == 'y') ? 'true' : 'false';

		$this->helper->insert_js('new Wygwam("'.$id.'", "'.$this->settings['config'].'", '.$defer.');');

		// pass the data through form_prep() if this is SafeCracker
		if (REQ == 'PAGE')
		{
			$data = form_prep($data, $this->field_name);
		}

		// convert file tags to URLs
		$this->_replace_file_tags($data);

		// convert site page tags to URLs
		$this->_replace_page_tags($data);

		return '<div class="wygwam"><textarea id="'.$id.'" name="'.$this->field_name.'" rows="10">'.$data.'</textarea></div>';
	}

	/**
	 * Display Cell
	 */
	function display_cell($data)
	{
		$this->_field_includes();
		$this->_config_json();

		if (! isset($this->cache['displayed_cols']))
		{
			$this->helper->include_theme_js('scripts/matrix2.js');
			$this->cache['displayed_cols'] = array();
		}

		if (! isset($this->cache['displayed_cols'][$this->col_id]))
		{
			$defer = (isset($this->settings['defer']) && $this->settings['defer'] == 'y') ? 'true' : 'false';

			$this->helper->insert_js('Wygwam.matrixColConfigs.col_id_'.$this->col_id.' = ["'.$this->settings['config'].'", '.$defer.'];');

			$this->cache['displayed_cols'][$this->col_id] = TRUE;
		}

		// convert file tags to URLs
		$this->_replace_file_tags($data);

		// convert site page tags to URLs
		$this->_replace_page_tags($data);

		return '<textarea name="'.$this->cell_name.'" rows="10">'.$data.'</textarea>';
	}

	/**
	 * Display Variable Field
	 */
	function display_var_field($data)
	{
		// Low Variables doesn't mix in the fieldtype's global settings,
		// so we'll do it manually here
		$this->settings = array_merge($this->settings, $this->helper->get_global_settings());

		// for now, it's way too complicated to get EE's file browser
		// loaded on non-Publish pages, so we'll fallback to CKFinder
		if ($this->settings['file_browser'] == 'ee')
		{
			$this->settings['file_browser'] = 'ckfinder';
		}

		return $this->display_field($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Validate
	 */
	function validate($data)
	{
		// is this a required field?
		if ($this->settings['field_required'] == 'y' && ! $data)
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
		if ($this->settings['col_required'] == 'y' && ! $data)
		{
			return lang('col_required');
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Save Field
	 */
	function save($data)
	{
		// Clear out if just whitespace
		if (! $data || preg_match('/^\s*(<\w+>\s*(&nbsp;)*\s*<\/\w+>|<br \/>)?\s*$/s', $data))
		{
			return '';
		}

		// Entitize curly braces within codeblocks
		$data = preg_replace_callback('/<code>(.*?)<\/code>/s',
			create_function('$matches',
				'return str_replace(array("{","}"), array("&#123;","&#125;"), $matches[0]);'
			),
			$data
		);

		// Remove Firebug 1.5.2+ div
		$data = preg_replace('/<div firebugversion=(.|\t|\n|\s)*<\\/div>/', '', $data);

		// Decode double quote entities (&quot;)
		//  - Eventually CKEditor will stop converting these in the first place
		//    http://dev.ckeditor.com/ticket/6645
		$data = str_replace('&quot;', '"', $data);

		// Convert file URLs to tags
		$this->_replace_file_urls($data);

		// Convert page URLs to tags
		$this->_replace_page_urls($data);

		// Preserve Read More comments
		//  - For whatever reason, SafeCracker is converting HTML comment brackets into entities
		$data = str_replace('&lt;!--read_more--&gt;', '<!--read_more-->', $data);

		return $data;
	}

	/**
	 * Save Cell
	 */
	function save_cell($data)
	{
		return $this->save($data);
	}

	/**
	 * Save Variable Field
	 */
	function save_var_field($data)
	{
		return $this->save($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Pre Process
	 */
	function pre_process($data)
	{
		$this->helper->entry_site_id = (isset($this->row['entry_site_id']) ? $this->row['entry_site_id'] : null);
		$this->_replace_page_tags($data);

		$this->EE->load->library('typography');

		$tmp_encode_email = $this->EE->typography->encode_email;
		$this->EE->typography->encode_email = FALSE;

		$tmp_convert_curly = $this->EE->typography->convert_curly;
		$this->EE->typography->convert_curly = FALSE;

		$data = $this->EE->typography->parse_type($data, array(
			'text_format'   => 'none',
			'html_format'   => 'all',
			'auto_links'    => (isset($this->row['channel_auto_link_urls']) ? $this->row['channel_auto_link_urls'] : 'n'),
			'allow_img_url' => (isset($this->row['channel_allow_img_urls']) ? $this->row['channel_allow_img_urls'] : 'y')
		));

		$this->EE->typography->encode_email = $tmp_encode_email;
		$this->EE->typography->convert_curly = $tmp_convert_curly;

		// use normal quotes
		$data = str_replace('&quot;', '"', $data);

		return $data;
	}

	/**
	 * Replace Tag
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		// return images only?
		if (isset($params['images_only']) && $params['images_only'] == 'yes')
		{
			return $this->_parse_images($data, $params, $tagdata);
		}

		// Text only?
		if (isset($params['text_only']) && $params['text_only'] == 'yes')
		{
			// Strip out the HTML tags
			$data = preg_replace('/<[^<]+?>/', '', $data);
		}
		else
		{
			// strip out the {read_more} tag
			$data = str_replace('<!--read_more-->', '', $data);
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Replace Has Excerpt Tag
	 */
	function replace_has_excerpt($data)
	{
		return (strpos($data, '<!--read_more-->') !== FALSE) ? 'y' : '';
	}

	/**
	 * Replace Excerpt Tag
	 */
	function replace_excerpt($data, $params)
	{
		if (($read_more_tag_pos = strpos($data, '<!--read_more-->')) !== FALSE)
		{
			$data = substr($data, 0, $read_more_tag_pos);
		}

		return $this->replace_tag($data, $params);
	}

	/**
	 * Replace Extended Tag
	 */
	function replace_extended($data, $params)
	{
		if (($read_more_tag_pos = strpos($data, '<!--read_more-->')) !== FALSE)
		{
			$data = substr($data, $read_more_tag_pos + 16);
		}
		else
		{
			$data = '';
		}

		return $this->replace_tag($data, $params);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse Images
	 */
	private function _parse_images($data, $params, $tagdata)
	{
		$images = array();

		if ($tagdata)
		{
			$p = !empty($params['var_prefix']) ? rtrim($params['var_prefix'], ':').':' : '';
		}

		// find all the image tags
		preg_match_all('/<img(.*)>/Ums', $data, $img_matches, PREG_SET_ORDER);

		foreach ($img_matches as $i => $img_match)
		{
			if ($tagdata)
			{
				$img = array();

				// find all the attributes
				preg_match_all('/\s([\w-]+)=([\'"])([^\2]*?)\2/', $img_match[1], $attr_matches, PREG_SET_ORDER);

				foreach ($attr_matches as $attr_match)
				{
					$img[$p.$attr_match[1]] = $attr_match[3];
				}

				// ignore image if it doesn't have a source
				if (empty($img[$p.'src'])) continue;

				// find all the styles
				if (! empty($img[$p.'style']))
				{
					$styles = array_filter(explode(';', trim($img[$p.'style'])));

					foreach ($styles as $style)
					{
						$style = explode(':', $style, 2);
						$img[$p.'style:'.trim($style[0])] = trim($style[1]);
					}
				}

				// use the width and height styles if they're set
				if (! empty($img[$p.'style:width']) && preg_match('/(\d+?\.?\d+)(px|%)/', $img[$p.'style:width'], $width_match))
				{
					$img[$p.'width'] = $width_match[1];
					if ($width_match[2] == '%') $img[$p.'width'] .= '%';
				}

				if (! empty($img[$p.'style:height']) && preg_match('/(\d+?\.?\d+)(px|%)/', $img[$p.'style:height'], $height_match))
				{
					$img[$p.'height'] = $height_match[1];
					if ($height_match[2] == '%') $img[$p.'height'] .= '%';
				}

				$images[] = $img;
			}
			else
			{
				$images[] = $img_match[0];
			}
		}

		// ignore if there were no valid images
		if (! $images) return;

		if ($tagdata)
		{
			// get the absolute number of files before we run the filters
			$constants[$p.'absolute_total_images'] = count($images);
		}

		// offset and limit params
		if (isset($params['offset']) || isset($params['limit']))
		{
			$offset = isset($params['offset']) ? (int) $params['offset'] : 0;
			$limit  = isset($params['limit'])  ? (int) $params['limit']  : count($images);

			$images = array_splice($images, $offset, $limit);
		}

		// ignore if there are no post-filter images
		if (! $images) return;

		if ($tagdata)
		{
			// get the filtered number of files
			$constants[$p.'total_images'] = count($images);

			// parse {total_images} and {absolute_total_images} first, since they'll never change
			$tagdata = $this->EE->TMPL->parse_variables_row($tagdata, $constants);

			// now parse all
			$r = $this->EE->TMPL->parse_variables($tagdata, $images);
		}
		else
		{
			$delimiter = isset($params['delimiter']) ? $params['delimiter'] : '<br />';
			$r = implode($delimiter, $images);
		}

		// backspace param
		if (!empty($params['backspace']))
		{
			$chop = strlen($r) - $params['backspace'];
			$r = substr($r, 0, $chop);
		}

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Display Variable Tag
	 */
	function display_var_tag($data)
	{
		return $this->replace_tag($this->pre_process($data));
	}
}

// END Wygwam_ft class
