<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2009, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine 1.x Language Class for CodeIgniter
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		Solspace DevTeam
 * @filesource	/system/bridge/codeigniter/system/application/libraries/Lang.php
 */
 
class CI_Lang
{
	var $language	= array();
	var $is_loaded	= array();
		
	/**
	 * Constructor
	 */	  
	function CI_Lang()
	{
		if ( isset($GLOBALS['LANG'], $GLOBALS['LANG']->language) && is_array($GLOBALS['LANG']->language))
		{
			$this->language = $GLOBALS['LANG']->language;
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Add a language file's language variables to the main language array
	 *
	 * @access	public
	 * @param	string	$which - The name of the language file to load
	 * @param	string	$which - The module/package to load from, instead of default application language folder
	 * @return	null
	 */
	function loadfile($which = '', $package = '')
	{
		if ($which == '')
		{
			return;
		}
		
		if (in_array($which, $this->is_loaded, TRUE))
		{
			return;
		}
		
		/** --------------------------------------------
        /**  Instantiate our Super-Global and get Functions Library
        /** --------------------------------------------*/
	
		$EE =& get_instance();
		
		/** --------------------------------------------
        /**  Determine Language of User, Default is English
        /** --------------------------------------------*/
        
		$user_lang = 'english';
		
		if (isset($EE->session->userdata['language']) && $EE->session->userdata['language'] != '')
		{
			$user_lang = $EE->session->userdata['language'];
		}
		else
		{
			if ($EE->input->cookie('language'))
			{
				$user_lang = $EE->input->cookie('language');
			}
			elseif ($EE->config->item('deft_lang') != '')
			{
				$user_lang = $EE->config->item('deft_lang');
			}
		}
		
		/** --------------------------------------------
        /**  Determine File to Load - Tricky in EE 1.x
        /** --------------------------------------------*/

		$package = ($package == '') ? $EE->security->sanitize_filename(str_replace(array('lang.', EXT), '', $which)) : $EE->security->sanitize_filename($package);
		$which = $EE->security->sanitize_filename(str_replace(array('lang.', EXT), '', $which));
		$user_lang = $EE->security->sanitize_filename($user_lang);
		
		/** --------------------------------------------
        /**  Load File
        /** --------------------------------------------*/
        
		$paths = array(
						PATH_MOD.$package.'/language/'.$user_lang.'/lang.'.$which.EXT,
						PATH_MOD.$package.'/language/english/lang.'.$which.EXT,
						PATH_LANG.$user_lang.'/lang.'.$which.EXT,
						PATH_LANG.'english/lang.'.$which.EXT
					);
		
		$success = FALSE;
		
		foreach($paths as $path)
		{
			if (file_exists($path) && @include $path)
			{
				$success = TRUE;
				break;
			}
		}
		
		if ($success !== TRUE)
		{
			if ($EE->config->item('debug') >= 1)
			{
				show_error('Unable to load the following language file:<br /><br />lang.'.$which.EXT);
			}
			
			return;				
		}
		
		if (isset($L))
		{
			if ( isset($GLOBALS['LANG']))
			{
				$GLOBALS['LANG']->language = $this->language = array_merge($GLOBALS['LANG']->language, $this->language, $L);
			}
			else
			{
				$this->language = array_merge($this->language, $L);
			}
			
			unset($L);
			
			$this->is_loaded[] = $which;
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Fetch the translated language variable
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function line($which = '', $label = '')
	{
		$line = ($which != '' && isset($this->language[$which])) ? $this->language[$which] : $which;
		
		if ($label != '')
		{
			$line = '<label for="'.htmlspecialchars($label).'">'.$line."</label>";
		}
		
		return stripslashes($line);
	}
	
		// --------------------------------------------------------------------

	/**
	 * Load a language file
	 *
	 * @access	public
	 * @param	mixed	the name of the language file to be loaded. Can be an array
	 * @param	string	the language (english, etc.)
	 * @return	mixed
	 */
	function load($langfile = '', $idiom = '', $return = FALSE)
	{
		$langfile = str_replace(EXT, '', str_replace('_lang.', '', $langfile)).'_lang'.EXT;

		if (in_array($langfile, $this->is_loaded, TRUE))
		{
			return;
		}

		if ($idiom == '')
		{
			$CI =& get_instance();
			$deft_lang = $CI->config->item('language');
			$idiom = ($deft_lang == '') ? 'english' : $deft_lang;
		}

		// Determine where the language file is and load it
		if (file_exists(APPPATH.'language/'.$idiom.'/'.$langfile))
		{
			include(APPPATH.'language/'.$idiom.'/'.$langfile);
		}
		else
		{
			if (file_exists(BASEPATH.'language/'.$idiom.'/'.$langfile))
			{
				include(BASEPATH.'language/'.$idiom.'/'.$langfile);
			}
			else
			{
				show_error('Unable to load the requested language file: language/'.$idiom.'/'.$langfile);
			}
		}

		if ( ! isset($lang))
		{
			log_message('error', 'Language file contains no data: language/'.$idiom.'/'.$langfile);
			return;
		}

		if ($return == TRUE)
		{
			return $lang;
		}

		$this->is_loaded[] = $langfile;
		$this->language = array_merge($this->language, $lang);
		unset($lang);

		log_message('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile);
		return TRUE;
	}

}
/* END CLASS Lang */

/* End of file CI_Lang.php */
/* Location: ./system/bridge/codeigniter/system/application/libraries/Lang.php */