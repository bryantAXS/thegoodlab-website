<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * EP better workflow
 * ----------------------------------------------------------------------------------------------
 * Enables the assignment of 'editor' and 'publisher' roles to member groups
 * Enables the creation of both 'entries' and 'drafts'
 *
 * Editors:	Can create, modify and preview new entries. Then submit these for approval.
 * Publishers:	Can create, modify and preview new entries as well as publishing these live
 *
 * Editors:	Can create, modify and preview draft versions of live entries. Modification to these versions will not effect the live entry.
 * Publishers:	Can create, modify and preview draft versions of live entries as well as publishing these live.
 *
 *
 * Third Party Compatibility modules
 * ----------------------------------------------------------------------------------------------
 * Structure:	Previewing will utilise structure URLS if available (This is a known issue with previewing brand new entries)
 * Matrix:	Full support for Matrix data and preview (with the exception of embedded Playa col type)
 * Wigwam:	AJAX preview supports Wigwam field types
 * Playa:	Support for storage of draft data - preview support requires a 'modification' to playa code
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */
 

class Ep_better_workflow_ext {



	var $name			= 'Better Workflow';
	var $version			= '1.0';
	var $description		= 'Enables the assignment of editor and publisher roles to member groups. Enables the creation of drafts versions of live entries which can be modified independently.';
	var $settings_exist		= 'y';
	var $docs_url			= 'http://betterworkflow.electricputty.co.uk/';

	var $settings			= array();
	var $js_libs  = 		array(
					'better-workflow.js',
					'status-transition.js',
					'buttons-ui.js',
					'preview.js',
					);
					
	var $bwf_statuses		= array('closed','draft','submitted','open');



	/**
	* Instantiate the Ep_better_workflow_ext class
	*
	*
	*/
	function Ep_better_workflow_ext()
	{
		$this->EE =& get_instance();
		$this->_load_settings();
		
		#var_dump($this->settings['bwf_channels']);
		
		// -------------------------------------------
		// Set the package path to keep EE 2.1.5 happy - Thanks to @cwcrawley
		// -------------------------------------------
		$this->EE->load->add_package_path(PATH_THIRD.'ep_better_workflow/');

		// -------------------------------------------
		// Load the libraries
		// -------------------------------------------
		require_once PATH_THIRD . '/ep_better_workflow/libraries/ep_workflow_logger.php';
		require_once PATH_THIRD . '/ep_better_workflow/libraries/ep_status_transition.php';
		require_once PATH_THIRD . '/ep_better_workflow/libraries/ep_bwf_ajax.php';
		require_once PATH_THIRD . '/ep_better_workflow/libraries/ep_draft_utils.php';
		require_once PATH_THIRD . '/ep_better_workflow/libraries/ep_dates.php';
		require_once PATH_THIRD . '/ep_better_workflow/libraries/ep_relationships.php';
		require_once PATH_THIRD . '/ep_better_workflow/libraries/ep_matrix.php';
		require_once PATH_THIRD . '/ep_better_workflow/libraries/ep_playa.php';

		// -------------------------------------------
		// AJAX method to return Page URI
		// -------------------------------------------
		if (isset($_REQUEST['ajax_structure_get_entry_url']))
		{
			$this->_structure_get_entry_url();
		}

		// -------------------------------------------
		// AJAX method to set auth token for preview
		// This is necessary to allow cross-domain previewing in MSM
		// -------------------------------------------
		if (isset($_REQUEST['ajax_set_auth_token']))
		{
			$this->_set_auth_token();
		}

		// -------------------------------------------
		// AJAX method to delete auth token for preview
		// -------------------------------------------
		if (isset($_REQUEST['ajax_delete_auth_token']))
		{
			$this->_delete_auth_token();
		}

		// -------------------------------------------
		// AJAX method to entry IDs for edit view
		// -------------------------------------------
		if ($this->_is_cp_content_edit_ajax_call()) 
		{
			$this->_cp_content_edit_ajax_response();
		}

		// -------------------------------------------
		// Instantiate libraries
		// -------------------------------------------
		if (isset($this->EE->session))
		{
			$this->ep_draft_utils		= new Ep_draft_utils();
			$this->status_transition	= new Ep_status_transition($this->EE->session->userdata['group_id'],$this->settings);
			$this->action_logger		= new Ep_workflow_logger($this->settings['advanced']['log_events']);
			$this->ep_matrix		= new Ep_matrix();
			$this->ep_playa			= new Ep_playa();
			$this->ep_relationships		= new Ep_relationships();
			$this->ep_dates			= new Ep_dates();
		}

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------
		if (! isset($this->EE->session->cache['ep_better_workflow']))
		{
			$this->EE->session->cache['ep_better_workflow'] = array();
		}
		$this->cache =& $this->EE->session->cache['ep_better_workflow'];
	}



	/**
	* Standard settings_form method
	* Check to see if this is called via an AJAX request
	* If so it calls the process to chect the status of existing entries
	* If not is returns the standard settigs form
	*
	*/
	function settings_form($current)
	{
		// If this is NOT an AJAX request show the standard settings form 
		if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) OR strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
		{
			return $this->display_settings();
		}
		
		// If it IS an AJAX request process the required action
		if (isset($_REQUEST['ajax_check_existing_channel_entries']))
		{
			$out = array('response' => 'ok');
			$this->EE->load->model('ep_statuses');
			$statuses = $this->EE->ep_statuses->check_channel_entries($_REQUEST['channel_id'], $this->bwf_statuses);
			if ($statuses)
			{
				$s = array();
				foreach ($statuses->result() as $status)
				{
					$s[] = $status;
				}
				$out['response'] = 'fail';
				$out['statuses'] = $s;
			}
			header('ContentType: application/json');
			die(json_encode($out));
		}
	}



	/**
	* Non AJAX settings request - simply display the settings from
	*
	*
	*/
	function display_settings()
	{
		$this->EE->load->helper('form');
		$this->EE->load->library('table');
		
		$this->EE->load->model('ep_settings');
		$this->EE->ep_settings->settings = $this->settings;
		$this->EE->ep_settings->name_name = $this->name;
		$this->EE->ep_settings->site_id = $this->EE->config->item('site_id');
		$vars = $this->EE->ep_settings->prep_settings();
		
		// Load the settings view css / javascript
		$this->EE->cp->add_to_foot('<link media="screen, projection" rel="stylesheet" type="text/css" href="'.$this->_theme_url().'stylesheets/cp.css" />');
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().'javascript/cp.js"></script>');
		
		return $this->EE->load->view('index', $vars, TRUE);
	}



	function save_settings()
	{
		$this->EE->load->model('ep_settings');
		$this->EE->ep_settings->class_name = __CLASS__;
		$this->EE->ep_settings->site_id = $this->EE->config->item('site_id');
		$this->EE->ep_settings->save_settings($_POST);
	}



	// -----------------------------------------------------------------------------------------------------------
	// Many thanks to @croxton for rewriting the below to utilise the config->_global_vars 
	// and saving us about 200 queries
	// -----------------------------------------------------------------------------------------------------------
	function _load_settings()
	{
		// Set the site ID
		$site_id = $this->EE->config->item('site_id');
		
		if (! isset($this->EE->config->_global_vars['ep_better_workflow_settings_'.$site_id]))
		{
			// Load the settings model
			$this->EE->load->model('ep_settings');
			$this->EE->ep_settings->class_name = __CLASS__;
			$this->EE->ep_settings->site_id = $site_id;
			
			$this->EE->config->_global_vars['ep_better_workflow_settings_'.$site_id] = $this->EE->ep_settings->get_settings();
		}

		if ( ! empty($this->EE->config->_global_vars['ep_better_workflow_settings_'.$site_id])) 
		{
			$this->settings = unserialize($this->EE->config->_global_vars['ep_better_workflow_settings_'.$site_id]);
		}

		// Add the site_id so we're MSM compatible
		$this->settings['site_id'] = $site_id;
		
		// Add the status colours to the settings (Some day we may make these editable)
		$this->settings['status_color_closed'] = '990000';
		$this->settings['status_color_draft'] = 'B59A42';
		$this->settings['status_color_submitted'] = '3E6C89';
		$this->settings['status_color_open'] = '009933';
		
		//// Set the default status group
		$this->settings['bwf_default_status'] = 'draft';
		
		// If not defined, set logging to 'no'
		if(!isset($this->settings['advanced']['log_events'])) $this->settings['advanced']['log_events'] = 'no';
	}



	// -----------------------------------------------------------------------------------------------------------
	// HOOK IMPLEMENTATIONS
	// -----------------------------------------------------------------------------------------------------------

	/***
	* Implements session start
	*
	* This has to be here in order for the extension to be executed in all the different sections of the 
	* EE admin area.
	*/
	function on_sessions_start($session)
	{
		// As this hook seems to be called without instantiating the extension...
		if(!property_exists('Ep_better_workflow_ext', 'action_logger')) $this->action_logger = new Ep_workflow_logger($this->settings['advanced']['log_events']);
	
		// Logging
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_sessions_start()");
		
		// If would be good to check if we are previewing here, as we only need to do the next bit if we are
		$this->_switch_status_if_entry_closed($session);
		return $session;
	}



	/***
	* Implements a way around for previewing non-open entries 
	*
	*/
	function _switch_status_if_entry_closed(&$session)
	{
		// As this hook seems to be called without instantiating the extension...
		if(!property_exists('Ep_better_workflow_ext', 'action_logger')) $this->action_logger = new Ep_workflow_logger($this->settings['advanced']['log_events']);

		$userdata = $this->_get_userdata();
		$userdata = $userdata ? $userdata : $session->userdata;

		if(isset($this->settings['groups']))
		{		
			// Logging
			$this->action_logger->add_to_log("ext.ep_better_workflow: METHOD: _switch_status_if_entry_closed(): Passed test for issst:groups");
			$this->action_logger->add_to_log("User data group id: ".$userdata['group_id']);
		
			if(array_key_exists("id_{$userdata['group_id']}", $this->settings['groups']))
			{
				if (isset($_REQUEST['ep_bwf_draftpreview']) && isset($_REQUEST['ep_bwf_entry_id']))
				{
					$entry_id = (INT) $_REQUEST['ep_bwf_entry_id'];

					$query = $this->EE->db->get_where('channel_titles',array('entry_id' => $entry_id));
					$entry= array_shift($query->result());

					if ($query->num_rows() > 0 && $entry && $entry->status != 'open' )
					{
						// Logging
						$this->action_logger->add_to_log("ext.ep_better_workflow: METHOD: _switch_status_if_entry_closed(): Setting entry_status to open (was {$entry->status} before)");
						
						$session->bwf_preview_entry_status = $entry->status;
						$session->bwf_preview_entry_id = $entry_id;

						$this->EE->db->where('entry_id',$entry_id);
						$this->EE->db->update('channel_titles',array('status' => 'open'));
					}
				}
			}
		}
	}



	/**
	*
	* While the on_section hook is executed, the array $session->userdata is not 
	* popoulated yet. This function obtains some vital user information by getting 
	* the session id from the cookie and querring the database 
	*
	*/
	function _get_userdata()
	{
		// Make sure we get the correct session id - this was one *tricky* bug to diagnose
		$session_cookie_name = ($this->EE->config->item('cookie_prefix') != '') ? $this->EE->config->item('cookie_prefix').'_sessionid' : 'exp_sessionid';

		if (isset($_COOKIE[$session_cookie_name]))
		{
			$this->EE->db->join('members','sessions.member_id = members.member_id');
			$sess = array_shift($this->EE->db->get_where('sessions',array('session_id' => $_COOKIE[$session_cookie_name] ))->result());
			if ($sess) return (array) $sess;
		} 
	}



	/***
	*
	* Implements EE hook publish_form_channel_preferences
	* 
	* Inject the status transition javascript 
	* when creating a new entry. 
	* 
	*/
	function on_publish_form_channel_preferences($prefs)
	{
		// do nothing if channel does not use workflow           
		if (!isset($prefs['channel_id']) or !$this->_channel_has_workflow($prefs['channel_id'])) return $prefs ;

		if (!isset($_REQUEST['entry_id']) or (isset($_REQUEST['entry_id']) && empty($_REQUEST['entry_id'])))
		{
			$snippet = $this->status_transition->instantiate_js_class(array('channel_id' => $prefs['channel_id']));
			$this->_append_stylesheet();
			$this->_append_javascripts($snippet);
		}
		return $prefs;
	}



	/***
	*
	* Implements EE Hook publish_form_data
	* 
	* Replaces entry data with draft data and injects status transition javascript.
	*
	*/ 
	function on_publish_form_entry_data( $result=array() )
	{		
		#echo("Original entry data");
		#var_dump($result);
		
		if (isset($result['channel_id']))
		{
			if ($this->_channel_has_workflow($result['channel_id']))
			{
				// Build the JavaScript settings and inject into page
				$snippet = $this->status_transition->instantiate_js_class($result);
				$this->_append_stylesheet();
				$this->_append_javascripts($snippet);
		
				// Replace 'entry' data with 'draft' data when a draft exists
				$result = ( is_object($this->status_transition->draft_data) ) ? $this->ep_draft_utils->load_draft($this->status_transition->draft_data, $result) : $result;
				
				// Logging
				$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_publish_form_entry_data(): Load draft data into publish form for entry: ". $result['entry_id']);
			}
		}
		
		#echo("EP draft data");
		#var_dump($result);
		
		return $result;
	}



	/***
	*
	* Implements EE hook 'entry_submission_ready'
	*
	* Process entry status transitions.
	*/
	function on_entry_submission_start($channel_id=0, $autosave=FALSE)
	{
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_entry_submission_start()");
	
		if( ! $channel_id || $autosave === TRUE ) return;
	
		// If this channel is governed by workflow
		if ($this->_channel_has_workflow($channel_id))
		{

			// -----------------------------------------------------------------------------------------------------------
			// And we are creating or updating a draft
			// -----------------------------------------------------------------------------------------------------------
			if($this->_creating_or_updating_draft($this->EE->api_channel_entries->data))
			{
				#var_dump($this->EE->api_channel_entries->data);
				#die();
				
				// -----------------------------------------------------------------------------------------------------------
				// We need to store the *complete* revision post array, because EE2.2 strips out third-party data 
				// -----------------------------------------------------------------------------------------------------------
				if(isset($this->EE->api_channel_entries->data['revision_post']))
				{
					$this->cache['revision_post'] = $this->EE->api_channel_entries->data['revision_post'];
				}


				// -----------------------------------------------------------------------------------------------------------
				// Load the relationships model and update any relatonship fields found in the data array
				// -----------------------------------------------------------------------------------------------------------
				$this->EE->api_channel_entries->data = $this->ep_relationships->remove_relationships_from_data($this->EE->api_channel_entries->data);


				// -----------------------------------------------------------------------------------------------------------
				// Save the data we extracted from the data array, we'll want to re-instate this later
				// -----------------------------------------------------------------------------------------------------------
				$this->cache['rel_data'] = $this->ep_relationships->rel_data;
				$this->cache['rel_fields'] = $this->ep_relationships->rel_fields;


				// Logging
				$c = count($this->EE->api_channel_entries->data);
				$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_entry_submission_start(): There are now ".$c." items in the data array");
			}
		}
	}



	/***
	* Implements EE hook 'entry_submission_ready'
	*
	* Process entry status transitions.
	*/
	function on_entry_submission_ready($entry_meta, $entry_data)
	{		
		// Logging
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_entry_submission_ready(): entry_id: ". $entry_data['entry_id']);

		if ($this->_channel_has_workflow($entry_meta['channel_id']))
		{

			// -----------------------------------------------------------------------------------------------------------
			// And we are creating or updating a draft?
			// -----------------------------------------------------------------------------------------------------------
			if($this->_creating_or_updating_draft(array_merge($entry_meta, $entry_data)))
			{
		
				// -----------------------------------------------------------------------------------------------------------
				// Load the relationships model and re-instate the relationships we removed before
				// -----------------------------------------------------------------------------------------------------------
				if (isset($this->cache['rel_data']) && count($this->cache['rel_data'])>0)
				{
					$entry_data = $this->ep_relationships->reinstate_relationships_to_data($entry_data, $this->cache['rel_data']);

					// Logging
					$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_entry_submission_ready(): Relationships reinstated");
				}


				// -----------------------------------------------------------------------------------------------------------
				// If we have a cached version of the revision_post data, re-instate it here
				// -----------------------------------------------------------------------------------------------------------
				if(isset($this->cache['revision_post']))
				{
					$entry_data['revision_post'] = $this->cache['revision_post'];
				}


				// -----------------------------------------------------------------------------------------------------------
				// Check for dates amongst the custom fields - they may need to be converted to UNIX format
				// -----------------------------------------------------------------------------------------------------------
				$entry_data = $this->ep_dates->dates_in_data_to_unix($entry_data);


				// -----------------------------------------------------------------------------------------------------------
				// Extract all matrix/playa data - this could be extended for other thrid party field types...
				// -----------------------------------------------------------------------------------------------------------
				$entry_data = $this->ep_matrix->remove_matrices_from_data($entry_data);
				$entry_data = $this->ep_playa->remove_playas_from_data($entry_data);
			}


			// -----------------------------------------------------------------------------------------------------------
			// Is this an ajax request from the preview engine to create a new entry?
			// -----------------------------------------------------------------------------------------------------------
			if (isset($_REQUEST['bwf_ajax_new_entry']))
			{				
				$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_entry_submission_ready(): Create new entry via AJAX");
								
				// Check to see if any Playa data has been caches, and if so re-instate it
				$entry_data = $this->ep_playa->update_playa_data_from_cache($entry_data);
				
				$this->ajax_request = new Ep_bwf_ajax();
				$this->ajax_request->_create_new_entry($entry_meta, $entry_data, 'draft'); //dies 
			}

			$this->cache['new_status'] = $this->status_transition->process_button_input($entry_meta, $entry_data);	
		}
	}



	/***
	* Implements EE hook 'entry_submission_end'
	*
	* Over-rides status settings on submit
	*/
	function on_entry_submission_end($entry_id, $entry_meta, $entry_data)
	{
		// Logging
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_entry_submission_end(): Entry: ". $entry_id);

		$status = null;

		// -----------------------------------------------------------------------------------------------------------
		// If we are creating or updating an Entry...
		// -----------------------------------------------------------------------------------------------------------
		foreach($_POST as $k => $v) 
		{
			if (preg_match('/^epBwfEntry/',$k))
			{
				$status = array_pop(explode('|',$v));
				break;
			}
		}
		
		// Logging
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_entry_submission_end(): Status: ". $status);

		// -----------------------------------------------------------------------------------------------------------
		// If we are turning a draft into an entry
		// -----------------------------------------------------------------------------------------------------------
		if (isset($this->cache['new_status']))
		{
			$status = $this->cache['new_status'];
		}

		if ($status && $this->_channel_has_workflow($entry_meta['channel_id']))
		{
			$this->EE->db->where("entry_id = $entry_id"); 
			$this->EE->db->update('channel_titles',array('status' => $status));
		}
	}



	/** 
	 * Implements EE hook 'channel_entries_row'
	 *
	 * Used to preview entries.
	 *
	 */
	function on_channel_entries_row($channel, $row)
	{		
		// Logging
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_channel_entries_row(): Getting data for template rendering");
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_channel_entries_row(): Row Entry ID:".$row['entry_id']);
		
		// -----------------------------------------------------------------------------------------------------------
		// 1. Is this a valid preview request? If no, simply return current data without updating anything
		// -----------------------------------------------------------------------------------------------------------
		$token_id = (isset($_REQUEST['ep_bwf_token_id'])) ? $_REQUEST['ep_bwf_token_id'] : 0;
		$token = (isset($_REQUEST['ep_bwf_token'])) ? $_REQUEST['ep_bwf_token'] : '0';
		
		$this->EE->load->model('ep_auth');
		
		// Logging
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_channel_entries_row(): Check for valid auth token: Token ID: " . $token_id . " Token: ". $token);

		if (!$this->EE->ep_auth->is_valid_request($token_id, $token))
		{
			// Logging
			$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_channel_entries_row(): Failed token validity - returning live data");
			
			return $row;
		}
		
		// Logging
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_channel_entries_row(): Passed token validity - checking to see if this entry has a draft");
		
		#var_dump($row);
		#echo "<hr />";

		// -----------------------------------------------------------------------------------------------------------
		// 2. Does this entry have a draft? If yes, use method below
		// Thanks to @croxton for improved performance here
		// -----------------------------------------------------------------------------------------------------------
		if ( isset($_REQUEST['ep_bwf_draftpreview']))
		{
		
			// Logging
			$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_channel_entries_row(): This is a draft preview so we need to get the draft data");
		
			$this->EE->load->model('ep_entry_draft');
			$draft = $this->EE->ep_entry_draft->get_by_entry_id($row['entry_id']);
			if ($draft && isset($_REQUEST['ep_bwf_draftpreview']))
			{
				
				// Logging
				$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_channel_entries_row(): Draft data successfully found - return data updated");
				
				$row = $this->ep_draft_utils->load_draft($draft,$row);
				$this->cache['is_preview'] = true;
			}
		}
		
		#var_dump($row);
		#echo "<hr />";
		#die();

		// -----------------------------------------------------------------------------------------------------------
		// rollback the entry status to whatever it was before 
		// -----------------------------------------------------------------------------------------------------------
		$sess = $this->EE->session;
		if (isset($sess->bwf_preview_entry_id) && isset($sess->bwf_preview_entry_status))
		{
			$this->EE->db->where('entry_id',$sess->bwf_preview_entry_id);
			$ok = $this->EE->db->update('channel_titles', array('status' => $sess->bwf_preview_entry_status));
		}

		return $row;
	}



	/** 
	 * Implements Matrix hook 'matrix_data_query'
	 *
	 * Used to preview entries.
	 *
	 */
	function on_matrix_data_query($matrixObj, $params, $sql)
	{
		// Logging
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_matrix_data_query(): Call data hook within Matrix");
	
		// -----------------------------------------------------------------------------------------------------------
		// If we are previewing load the draft Matrix data
		// -----------------------------------------------------------------------------------------------------------
		if(isset($this->cache['is_preview']) && $this->cache['is_preview']) 
		{
			// Logging
			$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_matrix_data_query(): HOOK: This is a preview so get the draft Matrix data");
			
			// -----------------------------------------------------------------------------------------------------------
			// Grab all draft matrix data
			// -----------------------------------------------------------------------------------------------------------
			return $this->ep_matrix->get_draft_data_for_preview($matrixObj, $params, $sql);
		}
		else
		{
			// Logging
			$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_matrix_data_query(): HOOK: This is a normal request so just return the recordset");
			
			return $this->EE->db->query($sql);
		}
	}



	/** 
	 * Implements Playa hook 'playa_data_query'
	 *
	 * Used to preview entries.
	 *
	 */
	function on_playa_data_query($dir, $entry_id, $field_ids, $col_ids, $row_ids, $rels)
	{
		// Logging
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_playa_data_query(): Call data hook within Playa");
	
		// -----------------------------------------------------------------------------------------------------------
		// If we are previewing load the draft Playa data
		// -----------------------------------------------------------------------------------------------------------
		if(isset($this->cache['is_preview']) && $this->cache['is_preview']) 
		{
			// Logging
			$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_playa_data_query(): This is a preview so get the draft Playa data");
			
			// -----------------------------------------------------------------------------------------------------------
			// Grab all draft playa data
			// -----------------------------------------------------------------------------------------------------------
			return $this->ep_playa->get_draft_data_for_preview($dir, $entry_id, $field_ids, $col_ids, $row_ids);
		}
		else
		{
			// Logging
			$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_playa_data_query(): This is a normal request so just return the playa relationships unaltered");
			
			return $rels;
		}
	}



	/** 
	 * Implements hook 'channel_entries_query_result'
	 *
	 * Opportunity to modify the channel entries query result array before the parsing loop starts
	 *
	 */
	function on_channel_entries_query_result($channel, $res)
	{
		// Logging
		$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_channel_entries_query_result()");
		
		// -----------------------------------------------------------------------------------------------------------
		// Try and find out if this is a preview - Make sure we only call this function once
		// -----------------------------------------------------------------------------------------------------------
		if(!isset($this->cache['channel_entries_query_result_called'])) 
		{				
			// TODO: There must be a neater way of doing this bit...
			$data = $res[0];
			
			// -----------------------------------------------------------------------------------------------------------
			// Load the entry draft model and see if this entry has a draft, and if so is this a preview request
			// -----------------------------------------------------------------------------------------------------------
			$this->EE->load->model('ep_entry_draft');
			$draft = $this->EE->ep_entry_draft->get_by_entry_id($data['entry_id']);
			if ($draft && isset($_REQUEST['ep_bwf_draftpreview']))
			{
				// Logging
				$this->action_logger->add_to_log("ext.ep_better_workflow: HOOK: on_channel_entries_query_result(): This is a preview request for an entry with a draft");
			
				$this->cache['is_preview'] = true;
			}
		
			// Set the cache so we don't do this check again
			$this->cache['channel_entries_query_result_called'] = true;
		}
	
		return $res;
	}



	/**
	 * Helper test to check if a channel has workflow and if the user has access to the workflow functionality. 
	 *
	 * @return {boolean} $hasWorkFlow
	 */
	function _channel_has_workflow($channel_id)
	{
		if (! isset($this->cache['channel_has_workflow']))
		{
			$this->cache['channel_has_workflow'] = $this->status_transition->has_workflow($channel_id);
		}
		return $this->cache['channel_has_workflow'];
	}



	/**
	 * Helper test to check if we are working on a draft rather than an entry. 
	 *
	 * @return {boolean} $hasWorkFlow
	 */
	function _creating_or_updating_draft($entry_data)
	{
		foreach($entry_data as $k => $v)
		{
			if(
				preg_match('/^epBwfDraft_update_draft/',$k) || 
				preg_match('/^epBwfDraft_save_as_draft/',$k) || 
				preg_match('/^epBwfDraft_submit_for_approval/',$k) || 
				preg_match('/^epBwfDraft_revert_to_draft/',$k) ||
				preg_match('/^epBwfDraft_discard_draft/',$k)
				)
			{
				return true;
			}
		}
		return false;
	}



	/*
	 * Implements the cp_js_end hook and uses it to inject workflow 
	 * javascripts and stylesheet to the control panel entry list table.
	 *
	 */
	function on_cp_js_end($data)
	{
		return $this->EE->extensions->last_call .
		$this->_cp_content_js_snippet();
	}
	


	function _cp_content_js_snippet()
	{
		$scripts = array('better-workflow.js','entry-list-observer.js');
		$ajax_endpoint = str_replace('&C=javascript&M=load&file=ext_scripts', '&C=content_edit', $_SERVER['REQUEST_URI']);

		foreach($scripts as $script)
		{
			$script_tags[] = <<<EOD
			document.write('\x3Cscript type="text/javascript" src="{$this->_theme_url()}javascript/$script">\x3C/script>');
EOD;
		}

		$script_tags = implode("\n\r", $script_tags);
		return  <<<EOD
		if (/D=cp&C=content_edit/.test(document.location.href)) {
			$script_tags
			jQuery( function ($) {
				$('head').append('<link rel="stylesheet" type="text/css" href="{$this->_theme_url()}stylesheets/bwf.css"  />')
				var ajaxEndPoint = '{$ajax_endpoint}&ep_bwf_getEntryInfo';
				tableObserver = new Bwf.EntryListObserver(ajaxEndPoint);
				tableObserver.observeFilters();
			});
		}
EOD;

	}



	function _is_cp_content_edit()
	{
		return isset($_REQUEST['D']) && $_REQUEST['D'] == 'cp' && isset($_REQUEST['C']) && $_REQUEST['C'] == 'content_edit';
	}



	function _is_cp_content_edit_ajax_call(){
		return ($this->_is_cp_content_edit() && isset($_REQUEST['ep_bwf_getEntryInfo']));
	}



	/**
	 * Structure compatibility plugin
	 * Returns Page URI for a given entry
	 */
	function _structure_get_entry_url()
	{
		$out = array('structure_url' => null); 

		//1. Check if structure is enabled 
		$this->EE->db->where(array('module_name' => 'Structure'));
		if ( count($this->EE->db->get('modules')->result()) > 0 )
		{
			//2. Check if this channel is using structure
			$this->EE->db->where(array('channel_id' => @$_REQUEST['channel_id']));
			if (count($this->EE->db->get('structure_channels')) > 0 )
			{
				//3. if yes, get the structure url
				$site_pages = $this->EE->config->item('site_pages');
				$site_pages = $site_pages[$this->settings['site_id']];

				if (@isset($site_pages['uris'][$_REQUEST['entry_id']])) 
				{
					$out['structure_url'] = $site_pages['uris'][@$_REQUEST['entry_id']];
				}
			}
		}

		header('ContentType: application/json');
		die(json_encode($out));
	}



	/**
	 * Auth token 
	 * Sets and returns auth token value and ID 
	 * This enables cross domain previewing for MSM installations
	 */
	function _set_auth_token()
	{
		$out = array('token_id' => null, 'token' => null); 
		$this->EE->load->model('ep_auth');
		$token_data = explode("|", $this->EE->ep_auth->set_token());
		$out['token_id'] = $token_data[0];
		$out['token'] = $token_data[1];

		header('ContentType: application/json');
		die(json_encode($out));
	}


	/**
	 * Auth token 
	 * Deletes auth token from database
	 */
	function _delete_auth_token()
	{
		$token_id = $_POST['token_id'];
		$this->EE->load->model('ep_auth');
		$this->EE->ep_auth->delete_token($token_id);
		die('Auth token deleted');
	}



	function _cp_content_edit_ajax_response()
	{
		$entry_ids = $_POST['entryIds'];

		// To make sure we're dealing with integers re-cast each item in this array
		for($i=0;$i < count($entry_ids);$i++)
		{
			$entry_ids[$i] = (INT) $entry_ids[$i];
		}

		$this->EE->load->model('ep_entry_draft');
		$this->EE->ep_entry_draft->bwf_settings = $this->settings;
		$data = $this->EE->ep_entry_draft->get_in_entry_ids($entry_ids, array('entry_id','status'));

		//spit out the JSON output
		header('Content-type: application/json'); 
		die(json_encode($data));
	}



	function _append_stylesheet()
	{
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().'stylesheets/bwf.css?'.$this->version.'" />');
	}



	function _append_javascripts($snippet=null, $exclude=array())
	{
		$to_include = array_diff($this->js_libs, $exclude);

		foreach($to_include as $script)
		{
			$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$this->_theme_url().'javascript/'.$script.'?'.$this->version.'"></script>');
		}
		
		if ($snippet)
		{
			$this->EE->cp->add_to_head(implode("\n", array(
			'<script>' .
			'// <![CDATA[',
			$snippet .
			'// ]]>',
			'</script>'
			)));
		}
	}



	private function _clear_auth_tokens()
	{
		$this->EE->load->model('ep_auth');
		$this->EE->ep_auth->clear_tokens($this->settings['site_id']);
	}



	/**
	 * Activate Extension
	 * @return void
	 */
	function activate_extension()
	{
		// -----------------------------------------------------------------------------------------------------------
		// Load the activate model and then regsiter the hooks and create the tables
		// -----------------------------------------------------------------------------------------------------------
		$this->EE->load->model('ep_activate');
		$this->EE->ep_activate->class_name = __CLASS__;
		$this->EE->ep_activate->version = $this->version;
		$this->EE->ep_activate->regsiter_hooks();
		$this->EE->ep_activate->create_tables();

		// -----------------------------------------------------------------------------------------------------------
		// Load the status model and create the status group (For the current site)
		// -----------------------------------------------------------------------------------------------------------
		$this->EE->load->model('ep_statuses');
		$status_group_id = $this->EE->ep_statuses->create_status_group($this->name);
		
		$this->EE->ep_statuses->insert_status($status_group_id, 'closed', '1', $this->settings['status_color_closed']);
		$this->EE->ep_statuses->insert_status($status_group_id, 'draft', '2', $this->settings['status_color_draft']);
		$this->EE->ep_statuses->insert_status($status_group_id, 'submitted', '3', $this->settings['status_color_submitted']);
		$this->EE->ep_statuses->insert_status($status_group_id, 'open', '4', $this->settings['status_color_open']);
	}



	/**
	 * Disable Extension
	 * @return void
	 */
	function disable_extension()
	{
		// -----------------------------------------------------------------------------------------------------------
		// Load the activate model and then drop the tables and delete the extension record
		// -----------------------------------------------------------------------------------------------------------
		$this->EE->load->model('ep_activate');
		$this->EE->ep_activate->class_name = __CLASS__;
		$this->EE->ep_activate->remove_bwf();
		
		// -----------------------------------------------------------------------------------------------------------
		// Load the status model and remove the status group (Do we need to do this?)
		// -----------------------------------------------------------------------------------------------------------
		$this->EE->load->model('ep_statuses');
		$this->EE->ep_statuses->remove_status_group($this->name);
	}



	/**
	 * Update Extension
	 * @return 	mixed	void on update / false if none
	 */
	function update_extension($current = '')
	{
		return FALSE;
	}



	/** 
	 * Internal method for rendering PHP templates 
	 *
	 * @param string $tpl_path  The template's path (relative to ..third_party/nhw_brandcentre ) 
	 * @param mixed $tpl_vars An associative array of key and values. 
	 * @param boolean $include_globals=TRUE Expose php's $GLOBAL variable to the template.   
	 * @return void
	 */
	private function _apply_template($tpl_path,$tpl_vars,$include_globals=TRUE)
	{
		extract($tpl_vars);

		if ($include_globals) extract($GLOBALS,EXTR_SKIP);
		ob_start();
		require($tpl_path);

		$output=ob_get_contents();ob_end_clean();
		return $output;
	}



	/**
	 * Theme URL
	 */
	private function _theme_url()
	{
		if (! isset($this->cache['theme_url']))
		{
			$theme_folder_url = $this->EE->config->item('theme_folder_url');
			if (substr($theme_folder_url, -1) != '/') $theme_folder_url .= '/';
			$this->cache['theme_url'] = $theme_folder_url.'third_party/ep_better_workflow/';
		}

		return $this->cache['theme_url'];
	}


}
