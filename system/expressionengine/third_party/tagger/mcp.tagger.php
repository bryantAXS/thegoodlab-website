<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Tagger Module Control Panel Class
 *
 * @package			DevDemon_Tagger
 * @version			2.1.2
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com
 * @see				http://expressionengine.com/user_guide/development/module_tutorial.html#control_panel_file
 */
class Tagger_mcp
{
	/**
	 * Views Data
	 * @access private
	 */
	private $vData = array();

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		// Creat EE Instance
		$this->EE =& get_instance();

		// Load Models & Libraries & Helpers
		$this->EE->load->library('tagger_helper');
		$this->EE->load->model('tagger_model');

		// Some Globals
		$this->base = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=tagger';
		$this->base_short = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=tagger';
		$this->site_id = $this->EE->config->item('site_id');

		// Global Views Data
		$this->vData['base_url'] = $this->base;
		$this->vData['base_url_short'] = $this->base_short;
		$this->vData['method'] = $this->EE->input->get('method');

		$this->EE->tagger_helper->define_theme_url();

		$this->mcp_globals();

		// Add Right Top Menu
		$this->EE->cp->set_right_nav(array(
			'tagger:docs' 			=> $this->EE->cp->masked_url('http://www.devdemon.com/tagger/docs/'),
		));

		// Debug
		//$this->EE->db->save_queries = TRUE;
		//$this->EE->output->enable_profiler(TRUE);
	}

	// ********************************************************************************* //

	/**
	 * MCP PAGE: Index
	 *
	 * @access public
	 * @return string
	 */
	public function index()
	{
		// Page Title & BreadCumbs
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('tagger'));

		return $this->EE->load->view('mcp_index', $this->vData, TRUE);
	}

	// ********************************************************************************* //

	/**
	 * MCP PAGE: Tag Groups
	 * @access public
	 * @return string
	 */
	public function groups()
	{
		// Page Title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('tagger:groups'));


		$this->vData['groups'] = $this->EE->tagger_model->get_groups();
		$this->vData['total_groups'] = count($this->vData['groups']);


		return $this->EE->load->view('mcp_groups', $this->vData, TRUE);
	}

	// ********************************************************************************* //

	/**
	 * MCP PAGE: Import
	 * @access public
	 * @return string
	 */
	public function import()
	{
		// Page Title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('tagger:import'));

		// Grab all channels
		$channels = array();
		$query = $this->EE->db->query("SELECT channel_id, channel_title FROM exp_channels WHERE site_id = {$this->site_id}");
		foreach($query->result() as $row) $channels[$row->channel_id] = $row->channel_title;
		$this->vData['channels'] = $channels;

		return $this->EE->load->view('mcp_import', $this->vData, TRUE);
	}

	// ********************************************************************************* //

	/**
	 * MCP PAGE: Do Import
	 * @access public
	 * @return string
	 */
	public function do_import()
	{
		$channels = $this->EE->input->get_post('channels');

		if ($channels == FALSE or empty($channels) == TRUE)
		{
			$this->EE->functions->redirect($this->base . '&method=import');
		}

		// Grab All Tags
		$query = $this->EE->db->query("SELECT tag_id, tag_name, author_id, entry_date, clicks, channel_entries FROM exp_tag_tags WHERE site_id = {$this->site_id}");

		// Loop Over all tags
		foreach($query->result() as $tag)
		{
			// Does it already exist?
			$this->EE->db->select('tag_id');
			$this->EE->db->from('exp_tagger');
			$this->EE->db->where('tag_name', $tag->tag_name);
			$this->EE->db->where('site_id', $this->site_id);
			$this->EE->db->limit(1);
			$q2 = $this->EE->db->get();

			// Create the TAG!
			if ($q2->num_rows() == 0)
			{
				// Data array for insertion
				$data = array(	'tag_name'	=>	$tag->tag_name,
								'author_id'	=>	$tag->author_id,
								'entry_date'=>	$tag->entry_date,
								'hits' => $tag->clicks,
								'total_entries' => $tag->channel_entries,
						);

				$this->EE->db->insert('exp_tagger', $data);

				$tag_id = $this->EE->db->insert_id();
			}
			else
			{
				$tag_id = $q2->row('tag_id');
			}

			$q2->free_result();

			// Grab all relations!
			$q3 = $this->EE->db->query("SELECT entry_id, channel_id, author_id FROM exp_tag_entries WHERE tag_id = {$tag->tag_id} AND site_id = {$this->site_id}");
			foreach($q3->result() as $order => $row)
			{
				// In the Channel?
				if (in_array($row->channel_id, $channels) == FALSE) continue;

				// Does this relationship already exist?
				$q4 = $this->EE->db->query("SELECT rel_id FROM exp_tagger_links WHERE tag_id = {$tag_id} AND entry_id = {$row->entry_id}");
				if ($q4->num_rows() > 0) continue;

				// Data array for insertion
				$data = array(	'entry_id'	=>	$row->entry_id,
								'channel_id'=>	$row->channel_id,
								'tag_id'	=>	$tag_id,
								'site_id' 	=>	$this->site_id,
								'author_id' =>	$row->author_id,
								'type'		=> 1,
								'tag_order'	=>	$order,
						);

				$this->EE->db->insert('exp_tagger_links', $data);

				$q4->free_result();
			}

			$q3->free_result();
		}

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('tagger:deleted_group'));
		$this->EE->functions->redirect($this->base);
	}

	// ********************************************************************************* //

	public function add_group()
	{
		// Page Title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('tagger:create_group'));

		$this->vData['group_id'] = '';
		$this->vData['group_title'] = '';
		$this->vData['group_name'] = '';
		$this->vData['group_desc'] = '';

		// Are we editing?
		if ($this->EE->input->get('group_id') > 0)
		{
			// Grab the group
			$groups = $this->EE->tagger_model->get_groups($this->EE->input->get('group_id'));

			// Do we have any group?
			if (count($groups) == 1)
			{
				// Always grab the first result, just in case
				$group = reset($groups);

				$this->vData['group_id']	= $group->group_id;
				$this->vData['group_title']	= $group->group_title;
				$this->vData['group_name']	= $group->group_name;
				$this->vData['group_desc']	= $group->group_desc;
			}

		}

		return $this->EE->load->view('mcp_groups_add', $this->vData, TRUE);
	}

	// ********************************************************************************* //

	public function update_group()
	{
		//----------------------------------------
		// Create/Updating?
		//----------------------------------------
		if ($this->EE->input->get('delete') != 'yes')
		{
			$this->EE->db->set('group_title', $this->EE->input->post('group_title'));
			$this->EE->db->set('group_name', $this->EE->input->post('group_name'));
			$this->EE->db->set('group_desc', $this->EE->input->post('group_desc'));

			// Are we updating a group?
			if ($this->EE->input->post('group_id') >= 1)
			{
				$this->EE->db->where('group_id', $this->EE->input->post('group_id'));
				$this->EE->db->update('exp_tagger_groups');
			}
			else
			{
				$this->EE->db->insert('exp_tagger_groups');
			}

			$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('tagger:updated_group'));
		}
		//----------------------------------------
		// Delete
		//----------------------------------------
		else
		{
			$group_id = $this->EE->input->get('group_id');

			// Delete from exp_tagger_groups
			$this->EE->db->where('group_id', $group_id);
			$this->EE->db->delete('exp_tagger_groups');

			//Delete from exp_tagger_groups_entries
			$this->EE->db->where('group_id', $group_id);
			$this->EE->db->delete('exp_tagger_groups_entries');

			$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('tagger:deleted_group'));
		}




		$this->EE->functions->redirect($this->base . '&method=groups');
	}

	// ********************************************************************************* //

	public function settings()
	{
		// Page Title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('tagger:settings'));

		// -----------------------------------------
		// Defaults
		// -----------------------------------------
		$this->EE->config->load('tagger_config');
		$conf = $this->EE->config->item('tagger_defaults');

		// Grab Settings
		$query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Tagger'");
		if ($query->row('settings') != FALSE)
		{
			$settings = @unserialize($query->row('settings'));
			if ($settings != FALSE && isset($settings['site:'.$this->site_id]))
			{
				$conf = array_merge($conf, $settings['site:'.$this->site_id]);
			}
		}

		$this->vData['urlsafe_seperator'] = $conf['urlsafe_seperator'];
		$this->vData['lowercase_tags'] = $conf['lowercase_tags'];

		return $this->EE->load->view('mcp_settings', $this->vData, TRUE);
	}

	// ********************************************************************************* //

	public function update_settings()
	{
		// -----------------------------------------
		// Defaults
		// -----------------------------------------
		$this->EE->config->load('tagger_config');
		$conf = $this->EE->config->item('tagger_defaults');

		// Grab Settings
		$query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Tagger'");
		if ($query->row('settings') != FALSE)
		{
			$settings = @unserialize($query->row('settings'));
			if ($settings == FALSE)
			{
				$settings = array();
			}
		}

		$conf['urlsafe_seperator'] = $this->EE->input->post('urlsafe_seperator');
		$conf['lowercase_tags'] = $this->EE->input->post('lowercase_tags');
		$settings['site:'.$this->site_id] = $conf;

		// Put it Back
		$this->EE->db->set('settings', serialize($settings));
		$this->EE->db->where('module_name', 'Tagger');
		$this->EE->db->update('exp_modules');


		$this->EE->functions->redirect($this->base . '&method=index');
	}

	// ********************************************************************************* //

	private function mcp_globals()
	{
		$this->EE->cp->set_breadcrumb($this->base, $this->EE->lang->line('tagger_module_name'));

		$this->EE->tagger_helper->mcp_meta_parser('gjs', '', 'Tagger');
		$this->EE->tagger_helper->mcp_meta_parser('css', TAGGER_THEME_URL . 'tagger_mcp.css', 'tagger-mcp');
		$this->EE->tagger_helper->mcp_meta_parser('js', TAGGER_THEME_URL . 'jquery.multiselect.js', 'jquery.multiselect', 'jquery');
		$this->EE->tagger_helper->mcp_meta_parser('js', TAGGER_THEME_URL . 'jquery.dataTables.js', 'jquery.dataTables', 'jquery');
		$this->EE->tagger_helper->mcp_meta_parser('js', TAGGER_THEME_URL . 'tagger_mcp.js', 'tagger-mcp');
	}


} // END CLASS

/* End of file mcp.tagger.php */
/* Location: ./system/expressionengine/third_party/tagger/mcp.tagger.php */