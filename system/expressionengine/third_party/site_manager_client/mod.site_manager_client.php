<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Installer
*/
class Site_manager_client
{

	var $EE;
	var $version	= 0.1;
	
	function __construct()
	{
		$this->EE =& get_instance();
	}
}