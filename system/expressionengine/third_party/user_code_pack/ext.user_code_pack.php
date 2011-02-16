<?php if ( ! defined('EXT')) exit('No direct script access allowed');

/**
 * User Code Pack Extension
 *
 * @package 	Solspace:User Code Pack
 * @author		Solspace DevTeam
 * @copyright	Copyright (c) 2010, Solspace, Inc.
 * @link		http://solspace.com/docs/
 * @version		1.0.0
 * @filesource 	./system/extensions/user_code_pack/
 * 
 */
 
 /**
 * User Code Pack - Extension File
 *
 * @package 	Solspace:User Code Pack
 * @author		Solspace DevTeam
 * @filesource 	./system/extensions/ext.user_code_pack.php
 */
 
require_once 'ext.user_code_pack.base.php';

if (APP_VER < 2.0)
{
	eval('class User_code_pack extends User_code_pack_extension_base { }');
}
else
{
	eval('class User_code_pack_ext extends User_code_pack_extension_base { }');
}
?>