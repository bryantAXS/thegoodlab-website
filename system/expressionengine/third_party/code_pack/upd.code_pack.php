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
 * Code pack - Updater
 *
 * In charge of the install, uninstall, and updating of the module
 *
 * @package 	Solspace:Code pack
 * @author		Solspace DevTeam
 * @filesource 	./system/modules/code_pack/upd.code_pack.php
 */

require_once 'upd.code_pack.base.php';

if (APP_VER < 2.0)
{
	eval('class Code_pack_updater extends Code_pack_updater_base { }');
}
else
{
	eval('class Code_pack_upd extends Code_pack_updater_base { }');
}

?>