<?php if ( ! defined('APP_VER')) exit('No direct script access allowed');

/*
 GWcode SyntaxHighlighter
 http://gwcode.com/add-ons/gwcode-syntaxhighlighter
============================================================
 Author: Leon Dijk (Twitter: @GWcode)
 Copyright (c) 2011 Gryphon WebSolutions, Leon Dijk
 http://gwcode.com
============================================================
 This ExpressionEngine 2.x add-on is licensed under a
 Creative Commons Attribution-NoDerivs 3.0 Unported License.
 http://creativecommons.org/licenses/by-nd/3.0/
============================================================
 As always, please make a full backup first before using
 this or any other add-on.
============================================================
*/

class Gwcode_syntaxhighlighter_ext {

	var $name           = 'GWcode SyntaxHighlighter';
	var $version        = '1.0.0';
	var $description    = 'Adds a SyntaxHighlighter button to your Wygwam toolbar.';
	var $settings_exist = 'n';
	var $docs_url       = 'http://gwcode.com/add-ons/gwcode-syntaxhighlighter';

	/**
	 * Class Constructor
	 */
	function Gwcode_syntaxhighlighter_ext()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 */
	function activate_extension()
	{
		// add the row to exp_extensions
		$this->EE->db->insert('extensions', array(
			'class'    => 'Gwcode_syntaxhighlighter_ext',
			'hook'     => 'wygwam_config',
			'method'   => 'wygwam_config',
			'priority' => 10,
			'version'  => $this->version,
			'enabled'  => 'y'
		));
	}

	/**
	 * Update Extension
	 */
	function update_extension($current = '')
	{
		if($current == '' OR $current == $this->version) {
			return FALSE;
		}

		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('version' => $this->version));
	}

	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}

	// --------------------------------------------------------------------

	/**
	 * wygwam_config hook
	 */
	function wygwam_config($config, $settings)
	{
		// If another extension shares the same hook,
		// we need to get the latest and greatest config
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$config = $this->EE->extensions->last_call;
		}

		if(isset($config['toolbar'])) {
			array_splice($config['toolbar'],3,0,array(array('Code')));
		}

		// Return the (unmodified) config
		return $config;
	}
}