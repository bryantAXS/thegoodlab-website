<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MODULE_DESCRIPTION
 *
 * @package		UCFIRST_MODULE_NAME
 * @subpackage	ThirdParty
 * @category	Modules
 * @author		MODULE_AUTHOR
 * @link		MODULE_LINK
 */
class UCFIRST_MODULE_NAME {

	var $return_data;

    /**
     * @var Devkit_code_completion
     */
    private $EE;

	function UCFIRST_MODULE_NAME()
	{		
		$this->EE =& get_instance(); // Make a local reference to the ExpressionEngine super object

        
	}
	
		
	/**
     * Helper function for getting a parameter
	 */		 
	function _get_param($key, $default_value = '')
	{
		$val = $this->EE->TMPL->fetch_param($key);
		
		if($val == '') {
			return $default_value;
		}
		return $val;
	}

	/**
	 * Helper funciton for template logging
	 */	
	function _error_log($msg)
	{		
		$this->EE->TMPL->log_item("MODULE_NAME ERROR: ".$msg);		
	}

    /**
     * For code completion
     *
     * @return Devkit_code_completion_helper
     */
    function EE() {if(!isset($this->EE)){$this->EE =& get_instance();}return $this->EE;}
}

/* End of file mod.MODULE_NAME.php */ 
/* Location: ./system/expressionengine/third_party/MODULE_NAME/mod.MODULE_NAME.php */
/* Generated by DevKit for EE - develop addons faster! */