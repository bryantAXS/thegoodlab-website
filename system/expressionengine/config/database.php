<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_group = 'expressionengine';
$active_record = TRUE;

$db['expressionengine']['hostname'] = "127.0.0.1";
$db['expressionengine']['username'] = "root";
$db['expressionengine']['password'] = "";
$db['expressionengine']['database'] = "starter_ee";
$db['expressionengine']['dbdriver'] = "mysql";
$db['expressionengine']['dbprefix'] = "exp_";
$db['expressionengine']['pconnect'] = FALSE;
$db['expressionengine']['swap_pre'] = "exp_";
$db['expressionengine']['db_debug'] = TRUE;
$db['expressionengine']['cache_on'] = FALSE;
$db['expressionengine']['autoinit'] = FALSE;
$db['expressionengine']['char_set'] = "utf8";
$db['expressionengine']['dbcollat'] = "utf8_general_ci";
$db['expressionengine']['cachedir'] = "/Users/bryanthughes/Sites/starter-ee/system/expressionengine/cache/db_cache/";

/* End of file database.php */
/* Location: ./system/expressionengine/config/database.php */

require($_SERVER['DOCUMENT_ROOT']."/bootstrap.php");