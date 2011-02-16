<?php if ( ! defined('EXT')) exit('No direct script access allowed');
 
 /**
 * Solspace - Code pack
 *
 * Installs preconfigured ExpressionEngine data into a website.
 *
 * @package		Solspace:Code pack
 * @author		Solspace DevTeam
 * @copyright	Copyright (c) 2009-2010, Solspace, Inc.
 * @link		http://www.solspace.com/docs/addon/c/Code_Pack/
 * @version		1.1.1
 * @filesource 	./system/modules/code_pack/
 * 
 */
 
 /**
 * Code pack - User Side
 *
 * @package 	Solspace:Code pack
 * @author		Solspace DevTeam
 * @filesource 	./system/modules/code_pack/mod.code_pack.php
 */

if (APP_VER < 2.0)
{
	require_once PATH.'bridge/lib/addon_builder/module_builder.php';
}
else
{
	require_once PATH_THIRD . 'bridge/lib/addon_builder/module_builder.php';
}

class Code_pack extends Module_builder_bridge {

	var $return_data	= '';
	
	var $disabled		= FALSE;

    // --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */
	 
	function Code_pack()
	{		
		parent::Module_builder('code_pack');
        
        /** -------------------------------------
		/**  Module Installed and Up to Date?
		/** -------------------------------------*/
		
		if ($this->database_version() == FALSE OR $this->version_compare($this->database_version(), '<', CODE_PACK_VERSION))
		{
			$this->disabled = TRUE;
			
			trigger_error(ee()->lang->line('code_pack_module_disabled'), E_USER_NOTICE);
		}
	}
	/* END Code_pack() */
	
}
// END CLASS Code_pack