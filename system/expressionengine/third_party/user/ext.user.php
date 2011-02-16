<?php if ( ! defined('EXT') ) exit('No direct script access allowed');
 
 /**
 * Solspace - User
 *
 * @package		Solspace:User
 * @author		Solspace DevTeam
 * @copyright	Copyright (c) 2008-2011, Solspace, Inc.
 * @link		http://solspace.com/docs/addon/c/User/
 * @version		3.3.1
 * @filesource 	./system/modules/user/
 * 
 */
 
 /**
 * User Module Class - Extension Class
 *
 * @package 	Solspace:User module
 * @author		Solspace DevTeam
 * @filesource 	./system/modules/user/ext.user.php
 */
 
require_once 'ext.user.base.php';

if (APP_VER < 2.0)
{
	eval('class User_extension extends User_extension_base { }');
}
else
{
	eval('class User_ext extends User_extension_base { }');
}
?>