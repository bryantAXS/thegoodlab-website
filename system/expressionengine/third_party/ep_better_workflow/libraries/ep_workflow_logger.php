<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_workflow_logger
 * 
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 * 	
 */

class Ep_workflow_logger {


	var $log_actions;

	/**
	* Instantiate the BetterWorkflow Logger class in the client
	*/
	function Ep_workflow_logger($log_actions)
	{
		$this->EE =& get_instance();
		$this->log_actions = $log_actions;
	}


	/** 
	 * Public method for logging BetterWorkflow activity 
	 */
	function add_to_log($action)
	{
		if($this->log_actions == 'yes')
		{
			$log_file = PATH_THIRD . "/ep_better_workflow/logs/log.txt";
			$the_handle = @fopen($log_file, 'a');
			if($the_handle)
			{
				$new_action = date("d/m/y : H:i:s", time())." : ".$action."\n";
				$fputs = fputs($the_handle, $new_action);
				fclose($the_handle);
			}
		}
	}


}