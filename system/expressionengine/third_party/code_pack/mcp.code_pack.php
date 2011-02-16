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
 * Code pack - Control Panel
 *
 * The Control Panel master class that handles all of the CP Requests and Displaying
 *
 * @package 	Solspace:Code pack
 * @author		Solspace DevTeam
 * @filesource 	./system/modules/code_pack/mcp.code_pack.php
 */
 
require_once 'mcp.code_pack.base.php';

if (APP_VER < 2.0)
{
	eval('class Code_pack_CP extends Code_pack_cp_base { }');
}
else
{
	eval('class Code_pack_mcp extends Code_pack_cp_base { }');
}
?>