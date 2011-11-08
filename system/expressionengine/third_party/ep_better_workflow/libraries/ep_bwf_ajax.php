<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_ajax_create_entry
 * 
 * To avoid a never ending loop we can't use the channel entries API to create a new entry via an AJAX request on preview
 * so we use as much of the API as we can but dodge the hook calls
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_bwf_ajax {



	/**
	* Instantiate the ajax_create_entry class in the client
	*
	*
	*/
	function Ep_bwf_ajax()
	{
		$this->EE =& get_instance();
		
	}



	public function _create_new_entry($entry_meta, $entry_data, $status)
	{
		// Set the status
		$entry_data['revision_post']['status'] = $status;
		
		// Define the output array
		$out = array();
		
		// Remove the 'bwf_ajax_new_entry' value so we don't get stuck in a loop when we hit the 'on_entry_submission_ready' hook again
		unset($_POST['bwf_ajax_new_entry']);
		unset($_REQUEST['bwf_ajax_new_entry']);
		unset($entry_data['bwf_ajax_new_entry']);
		unset($entry_data['revision_post']['bwf_ajax_new_entry']);
		
		// Load the Channel entries API
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_entries');
		
		// Try and create a new entry
		$success = $this->EE->api_channel_entries->submit_new_entry($entry_data['channel_id'], $entry_data['revision_post']);
		
		// If we weren't successful output the error information
		if (!$success)
		{
			$errors = $this->EE->api_channel_entries->errors;
			if (!isset($_REQUEST['bwf_ajax_new_entry'])) show_error('An Error Occurred Updating the Entry: <pre>' . var_export( $errors, true ) . '</pre>');
		}

		// If we did manage to create a new entry grab in the new ID and send it back as JSON
		if ($success)
		{
			$r = array_shift($this->EE->db->query(
			"SELECT entry_id FROM exp_channel_titles ORDER BY entry_id DESC LIMIT 1"
			)->result());
			
			$out['new_entry_id'] = ($r && isset($r->entry_id)) ? $r->entry_id : null;
		}

		header('ContentType: application/json');
		die(json_encode($out));
	
	}




}