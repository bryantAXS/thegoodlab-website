<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Tagger AHelper File
 *
 * @package			DevDemon_Tagger
 * @version			2.1.2
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com
 */
class Tagger_helper
{

	public function __construct()
	{
		// Creat EE Instance
		$this->EE =& get_instance();

		$this->site_id = $this->EE->config->item('site_id');
		$this->EE->config->load('tagger_config');
	}

	// ********************************************************************************* //

	/**
	 * Url Safe Tag
	 *
	 * This method is responsible for encode/decode tags for URL's
	 *
	 * @param string $tag - The original Tag
	 * @param bool $encode[optional] - Are we encoding or decoding
	 * @return string - The processed Tag
	 */
	public function urlsafe_tag($tag, $encode=TRUE)
	{
		$conf = $this->grab_settings($this->site_id);

		if ($encode == true)
		{
			switch($conf['urlsafe_seperator'])
			{
				case 'plus':
					$tag = str_replace(' ', '+', $tag);
					break;
				case 'space':
					$tag = str_replace(' ', '%20', $tag);
					break;
				case 'dash':
					$tag = str_replace(' ', '-', $tag);
					break;
				case 'underscore':
					$tag = str_replace(' ', '_', $tag);
					break;
			}

			$tag = str_replace(' ', '%20', $tag);
			$tag = htmlentities($tag, ENT_QUOTES, 'UTF-8');
		}
		else
		{
			switch($conf['urlsafe_seperator'])
			{
				case 'plus':
					$tag = str_replace('+', ' ', $tag);
					break;
				case 'dash':
					$tag = str_replace('-', ' ', $tag);
					break;
				case 'underscore':
					$tag = str_replace('_', ' ', $tag);
					break;
			}

			$tag = str_replace('%20', ' ', $tag );
			$tag = html_entity_decode($tag);
		}

		return $tag;
	}

	// ********************************************************************************* //

	public function unitag($tag_id, $tag)
	{
		// Strip all weird chars from the tag
		//$tag = preg_replace("/[^a-zA-Z0-9]/", "", $tag);
		$tag = preg_replace("/[^A-Za-z0-9\s\s+\-]/", "", $tag);
		$tag = $this->urlsafe_tag($tag);
		$tag = $tag_id . '-' . $tag;

		return $tag;
	}

	// ********************************************************************************* //


	// -----------------------------------------
	// Support filedir tags in entries.
	// -----------------------------------------
	public function file_dir_parse($str)
	{
		$file_dirs = $this->EE->functions->fetch_file_paths();

		foreach($file_dirs AS $key => $row)
		{
			$str = str_ireplace("{filedir_$key}", $row, $str);
		}

		return $str;
	}

	// ********************************************************************************* //

	public function get_router_url($type='url')
	{
		// Do we have a cached version of our ACT_ID?
		if (isset($this->EE->session->cache['Tagger']['Router_Url']['ACT_ID']) == FALSE)
		{
			$this->EE->db->select('action_id');
			$this->EE->db->where('class', 'Tagger');
			$this->EE->db->where('method', 'tagger_router');
			$query = $this->EE->db->get('actions');
			$ACT_ID = $query->row('action_id');
		}
		else $ACT_ID = $this->EE->session->cache['Tagger']['Router_Url']['ACT_ID'];

		// RETURN: Full Action URL
		if ($type == 'url')
		{
			if (isset($this->EE->session->cache['Tagger']['Router_Url']['URL']) == TRUE) return $this->EE->session->cache['Tagger']['Router_Url']['URL'];
			$this->EE->session->cache['Tagger']['Router_Url']['URL'] = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=' . $ACT_ID;
			return $this->EE->session->cache['Tagger']['Router_Url']['URL'];
		}

		// RETURN: ACT_ID Only
		if ($type == 'act_id') return $ACT_ID;
	}

	// ********************************************************************************* //

	/**
	 * Grab File Module Settings
	 * @return array
	 */
	function grab_settings($site_id=FALSE)
	{

		$settings = array();

		if (isset($this->EE->session->cache['Tagger_Settings']) == TRUE)
		{
			$settings = $this->EE->session->cache['Tagger_Settings'];
		}
		else
		{
			$this->EE->db->select('settings');
			$this->EE->db->where('module_name', 'Tagger');
			$query = $this->EE->db->get('exp_modules');
			if ($query->num_rows() > 0) $settings = unserialize($query->row('settings'));
		}

		$this->EE->session->cache['Tagger_Settings'] = $settings;

		if ($site_id)
		{
			$settings = isset($settings['site:'.$site_id]) ? $settings['site:'.$site_id] : array();
		}

		$conf = $this->EE->config->item('tagger_defaults');
		$settings = array_merge($conf, $settings);

		return $settings;
	}

	// ********************************************************************************* //

	function define_theme_url()
	{
		$theme_url = $this->EE->config->item('theme_folder_url').'third_party/';

		// Are we working on SSL?
		if (isset($_SERVER['HTTP_REFERER']) == TRUE AND strpos($_SERVER['HTTP_REFERER'], 'https://') !== FALSE)
		{
			$theme_url = str_replace('http://', 'https://', $theme_url);
		}

		if (! defined('TAGGER_THEME_URL')) define('TAGGER_THEME_URL', $theme_url . 'tagger/');

		return TAGGER_THEME_URL;
	}

	// ********************************************************************************* //

	/**
	 * Generate new XID
	 *
	 * @return string the_xid
	 */
	public function xid_generator()
	{
		// Maybe it's already been made by EE
		if (defined('XID_SECURE_HASH') == TRUE) return XID_SECURE_HASH;

		// First is secure_forum enabled?
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			// Did we already cache it?
			if (isset($this->EE->session->cache['XID']) == TRUE && $this->EE->session->cache['XID'] != FALSE)
			{
				return $this->EE->session->cache['XID'];
			}

			// Is there one already made that i can use?
			$this->EE->db->select('hash');
			$this->EE->db->from('exp_security_hashes');
			$this->EE->db->where('ip_address', $this->EE->input->ip_address());
			$this->EE->db->where('`date` > UNIX_TIMESTAMP()-3600');
			$this->EE->db->limit(1);
			$query = $this->EE->db->get();

			if ($query->num_rows() > 0)
			{
				$row = $query->row();
				$this->EE->session->cache['XID'] = $row->hash;
				return $this->EE->session->cache['XID'];
			}

			// Lets make one then!
			$XID	= $this->EE->functions->random('encrypt');
			$this->EE->db->insert('exp_security_hashes', array('date' => $this->EE->localize->now, 'ip_address' => $this->EE->input->ip_address(), 'hash' => $XID));

			// Remove Old
			//$DB->query("DELETE FROM exp_security_hashes WHERE date < UNIX_TIMESTAMP()-7200"); // helps garbage collection for old hashes

			$this->EE->session->cache['XID'] = $XID;
			return $XID;
		}
	}

	// ********************************************************************************* //

	/**
	 * Format tags
	 *
	 * Cleans up the tag, by removing unwanted characters
	 *
	 * @param string $str[optional] - The unformatted tag
	 * @return string The formatted tag
	 */
	public function format_tag($str='')
	{
		global $DB, $PREFS, $REGX;

		$this->EE->load->helper('text');

		$not_allowed = array('$', '?', ')', '(', '!', '<', '>', '/', '\\');

		$str = str_replace($not_allowed, '', $str);

		//$str	= ( $this->convert_case === true ) ? $this->_strtolower( $str ): $str;

		if ($this->EE->config->item('auto_convert_high_ascii') == 'y')
		{
			$str	=  ascii_to_entities($str);
		}

		$str	= $this->EE->security->xss_clean($str);

		return trim($str);
	}

	// ********************************************************************************* //

	/**
	 * Insert Tag in DB
	 *
	 * @param string $tag - The tag
	 * @return int - The tag ID
	 */
	public function create_tag($tag)
	{
		global $DB, $LOC, $SESS;

		// Data array for insertion
		$data = array(	'tag_name'	=>	$tag,
						'site_id'	=>	$this->site_id,
						'author_id'	=>	$this->EE->session->userdata['member_id'],
						'entry_date'=>	$this->EE->localize->now,
						'edit_date'	=>	$this->EE->localize->now,
						'total_entries' => 0,
				);

		$this->EE->db->insert('exp_tagger', $data);

		return $this->EE->db->insert_id();

	}

	// ********************************************************************************* //

	public function generate_json($obj)
	{
		if (function_exists('json_encode') === FALSE)
		{
			if (class_exists('Services_JSON') === FALSE) include 'JSON.php';
			$JSON = new Services_JSON();
			return $JSON->encode($obj);
		}
		else
		{
			return json_encode($obj);
		}
	}

	// ********************************************************************************* //

	/**
	 * Is a Natural number  (0,1,2,3, etc.)
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function is_natural_number($str)
	{
   		return (bool)preg_match( '/^[0-9]+$/', $str);
	}

	// ********************************************************************************* //

	/**
	 * Get Entry_ID from tag paramaters
	 *
	 * Supports: entry_id="", url_title="", channel=""
	 *
	 * @return mixed - INT or BOOL
	 */
	public function get_entry_id_from_param($get_channel_id=FALSE)
	{
		$entry_id = FALSE;
		$channel_id = FALSE;

		$this->EE->load->helper('number');

		if ($this->EE->TMPL->fetch_param('entry_id') != FALSE && $this->is_natural_number($this->EE->TMPL->fetch_param('entry_id')) != FALSE)
		{
			$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		}
		elseif ($this->EE->TMPL->fetch_param('url_title') != FALSE)
		{
			$channel = FALSE;
			$channel_id = FALSE;

			if ($this->EE->TMPL->fetch_param('channel') != FALSE)
			{
				$channel = $this->EE->TMPL->fetch_param('channel');
			}

			if ($this->EE->TMPL->fetch_param('channel_id') != FALSE && $this->is_natural_number($this->EE->TMPL->fetch_param('channel_id')))
			{
				$channel_id = $this->EE->TMPL->fetch_param('channel_id');
			}

			$this->EE->db->select('exp_channel_titles.entry_id');
			$this->EE->db->select('exp_channel_titles.channel_id');
			$this->EE->db->from('exp_channel_titles');
			if ($channel) $this->EE->db->join('exp_channels', 'exp_channel_titles.channel_id = exp_channels.channel_id', 'left');
			$this->EE->db->where('exp_channel_titles.url_title', $this->EE->TMPL->fetch_param('url_title'));
			if ($channel) $this->EE->db->where('exp_channels.channel_name', $channel);
			if ($channel_id) $this->EE->db->where('exp_channel_titles.channel_id', $channel_id);
			$this->EE->db->limit(1);
			$query = $this->EE->db->get();

			if ($query->num_rows() > 0)
			{
				$channel_id = $query->row('channel_id');
				$entry_id = $query->row('entry_id');
				$query->free_result();
			}
			else
			{
				return FALSE;
			}
		}

		if ($get_channel_id != FALSE)
		{
			if ($this->EE->TMPL->fetch_param('channel') != FALSE)
			{
				$channel_id = $this->EE->TMPL->fetch_param('channel_id');
			}

			if ($channel_id == FALSE)
			{
				$this->EE->db->select('channel_id');
				$this->EE->db->where('entry_id', $entry_id);
				$this->EE->db->limit(1);
				$query = $this->EE->db->get('exp_channel_titles');
				$channel_id = $query->row('channel_id');

				$query->free_result();
			}

			$entry_id = array( 'entry_id'=>$entry_id, 'channel_id'=>$channel_id );
		}



		return $entry_id;
	}

	// ********************************************************************************* //

	/**
	 * Custom No_Result conditional
	 *
	 * Same as {if no_result} but with your own conditional.
	 *
	 * @param string $cond_name
	 * @param string $source
	 * @param string $return_source
	 * @return unknown
	 */
	public function custom_no_results_conditional($cond_name, $source, $return_source=FALSE)
	{
   		if (strpos($source, LD."if {$cond_name}".RD) !== FALSE)
		{
			if (preg_match('/'.LD."if {$cond_name}".RD.'(.*?)'.LD.'\/'.'if'.RD.'/s', $source, $cond))
			{
				return $cond[1];
			}

		}

		if ($return_source !== FALSE)
		{
			return $source;
		}

		return;
    }

	// ********************************************************************************* //

	/**
	 * Fetch data between var pairs
	 *
	 * @param string $open - Open var (with optional parameters)
	 * @param string $close - Closing var
	 * @param string $source - Source
	 * @return string
	 */
    function fetch_data_between_var_pairs($varname='', $source = '')
    {
    	if ( ! preg_match('/'.LD.($varname).RD.'(.*?)'.LD.'\/'.$varname.RD.'/s', $source, $match))
               return;

        return $match['1'];
    }

	// ********************************************************************************* //

	/**
	 * Fetch data between var pairs (including optional parameters)
	 *
	 * @param string $open - Open var (with optional parameters)
	 * @param string $close - Closing var
	 * @param string $source - Source
	 * @return string
	 */
    function fetch_data_between_var_pairs_params($open='', $close='', $source = '')
    {
    	if ( ! preg_match('/'.LD.preg_quote($open).'.*?'.RD.'(.*?)'.LD.'\/'.$close.RD.'/s', $source, $match))
               return;

        return $match['1'];
    }

	// ********************************************************************************* //

	/**
	 * Replace var_pair with final value
	 *
	 * @param string $open - Open var (with optional parameters)
	 * @param string $close - Closing var
	 * @param string $replacement - Replacement
	 * @param string $source - Source
	 * @return string
	 */
	function swap_var_pairs($varname = '', $replacement = '\\1', $source = '')
    {
    	return preg_replace("/".LD.$varname.RD."(.*?)".LD.'\/'.$varname.RD."/s", $replacement, $source);
    }

	// ********************************************************************************* //

	/**
	 * Replace var_pair with final value (including optional parameters)
	 *
	 * @param string $open - Open var (with optional parameters)
	 * @param string $close - Closing var
	 * @param string $replacement - Replacement
	 * @param string $source - Source
	 * @return string
	 */
	function swap_var_pairs_params($open = '', $close = '', $replacement = '\\1', $source = '')
    {
    	return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.'\/'.$close.RD."/s", $replacement, $source);
    }

	// ********************************************************************************* //

	public function mcp_meta_parser($type='js', $url, $name, $package='')
	{
		// -----------------------------------------
		// CSS
		// -----------------------------------------
		if ($type == 'css')
		{
			if ( isset($this->EE->session->cache['DevDemon']['CSS'][$name]) == FALSE )
			{
				$this->EE->cp->add_to_head('<link rel="stylesheet" href="' . $url . '" type="text/css" media="print, projection, screen" />');
				$this->EE->session->cache['DevDemon']['CSS'][$name] = TRUE;
			}
		}

		// -----------------------------------------
		// Javascript
		// -----------------------------------------
		if ($type == 'js')
		{
			if ( isset($this->EE->session->cache['DevDemon']['JS'][$name]) == FALSE )
			{
				$this->EE->cp->add_to_head('<script src="' . $url . '" type="text/javascript"></script>');
				$this->EE->session->cache['DevDemon']['JS'][$name] = TRUE;
			}
		}

		// -----------------------------------------
		// Global Inline Javascript
		// -----------------------------------------
		if ($type == 'gjs')
		{
			if ( isset($this->EE->session->cache['DevDemon']['GJS'][$name]) == FALSE )
			{
				$xid = $this->xid_generator();
				$AJAX_url = $this->get_router_url();

				$js = "	var Tagger = Tagger ? Tagger : new Object();
						Tagger.XID = '{$xid}';
						Tagger.AJAX_URL = '{$AJAX_url}&tagger_ajax=yes';
					";

				$this->EE->cp->add_to_head('<script type="text/javascript">' . $js . '</script>');
				$this->EE->session->cache['DevDemon']['GJS'][$name] = TRUE;
			}
		}
	}

} // END CLASS

/* End of file tagger_helper.php  */
/* Location: ./system/expressionengine/third_party/tagger/modules/libraries/tagger_helper.php */