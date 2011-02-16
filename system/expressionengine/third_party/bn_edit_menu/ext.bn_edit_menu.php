<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bn_edit_menu_ext
{
	public $settings = array();
	public $name = 'BN Edit Menu';
	public $version = '1.0.0';
	public $description = 'Displays your channels in the Content > Edit menu.';
	public $settings_exist = 'n';
	public $docs_url = 'http://barrettnewton.com';
	
	/**
	 * __construct
	 * 
	 * @access	public
	 * @param	mixed $settings = ''
	 * @return	void
	 */
	public function __construct($settings = '')
	{
		$this->EE = get_instance();
		
		$this->settings = $settings;
	}
	
	/**
	 * activate_extension
	 * 
	 * @access	public
	 * @return	void
	 */
	public function activate_extension()
	{
		$hook_defaults = array(
			'class' => __CLASS__,
			'settings' => '',
			'version' => $this->version,
			'enabled' => 'y',
			'priority' => 10
		);
		
		$hooks[] = array(
			'method' => 'cp_js_end',
			'hook' => 'cp_js_end'
		);
		
		foreach ($hooks as $hook)
		{
			$this->EE->db->insert('extensions', array_merge($hook_defaults, $hook));
		}
		
		return TRUE;
	}
	
	/**
	 * update_extension
	 * 
	 * @access	public
	 * @param	mixed $current = ''
	 * @return	void
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$this->EE->db->update('extensions', array('version' => $this->version), array('class' => __CLASS__));
		
		return TRUE;
	}
	
	/**
	 * disable_extension
	 * 
	 * @access	public
	 * @return	void
	 */
	public function disable_extension()
	{
		$this->EE->db->delete('extensions', array('class' => __CLASS__));
		
		return TRUE;
	}
	
	/**
	 * settings
	 * 
	 * @access	public
	 * @return	array
	 */
	public function settings()
	{
		$settings = array();
		
		return $settings;
	}
	
	/**
	 * cp_js_end
	 * 
	 * @access	public
	 * @return	string
	 */
	public function cp_js_end()
	{
		return $this->EE->extensions->last_call.'$(function(){$("#navigationTabs").children("li.parent").each(function(){if($(this).children("a:first").html() == "Content"){$(this).children("ul:first").children("li").eq(1).addClass("parent").append($(this).children("ul:first").children("li").eq(0).children("ul").clone(true)).find("a").each(function(){$(this).attr("href",$(this).attr("href").replace(/content_publish&(amp;)?M=entry_form/,"content_edit"));});}});});'."\r\n";
	}
}

/* End of file ext.bn_edit_menu.php */
/* Location: ./system/expressionengine/third_party/bn_edit_menu/ext.bn_edit_menu.php */