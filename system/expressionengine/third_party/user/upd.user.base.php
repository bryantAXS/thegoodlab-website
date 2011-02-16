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
 * User Module Class - Install/Uninstall/Update class
 *
 * @package 	Solspace:User module
 * @author		Solspace DevTeam
 * @filesource 	./system/modules/user/upd.user.php
 */
 
if ( ! defined('APP_VER')) define('APP_VER', '2.0'); // EE 2.0's Wizard doesn't like CONSTANTs

if (APP_VER < 2.0)
{
	require_once PATH.'bridge/lib/addon_builder/module_builder.php';
}
else
{
	require_once PATH_THIRD . 'bridge/lib/addon_builder/module_builder.php';
}

class User_updater_base extends Module_builder_bridge
{    
    public 	$module_actions		= array();
    public 	$hooks				= array();
    private $old_layout_data	= array(
		array(
			'user_authors' 	=> array(
				'solspace_user_browse_authors' 		=> array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				)
			)
		),
    	array(
			'user' 			=> array(
				'user__solspace_user_browse_authors' => array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				)
			)
		)
    );
    
	// --------------------------------------------------------------------

	/**
	 * Contructor
	 *
	 * @access	public
	 * @return	null
	 */
    
	public function __construct( )
    {
    	if ( isset($GLOBALS['CI']) && get_class($GLOBALS['CI']) == 'Wizard')
    	{
    		return;
    	}
    	
    	parent::Module_builder_bridge('user');
    	
		/** --------------------------------------------
        /**  Module Actions
        /** --------------------------------------------*/
        
        $this->module_actions = array(		
			'group_edit', 
			'edit_profile', 
			'reg', 
			'reassign_jump', 
			'retrieve_password',
        	'do_search', 
			'delete_account', 
			'activate_member', 
			'retrieve_username',
        	'create_key'
		);
        				 		
		/** --------------------------------------------
        /**  Extension Hooks
        /** --------------------------------------------*/
        
        $default = array(
			'class' 		=> $this->extension_name,
			'settings'		=> '', 								// NEVER!
			'priority'		=> 5,
			'version'		=> constant(strtoupper($this->class_name).'_VERSION'),
			'enabled'		=> 'y'
		);
        
        $this->hooks = array(
			array_merge($default,
				array(	
					'method'		=> 'loginreg',
					'hook'  		=> 'insert_comment_start'
				)
			),		
			array_merge($default,
				array(	
					'method'		=> 'loginreg',
					'hook'  		=> 'insert_rating_start'
				)
			),		
			array_merge($default,
				array(	
					'method'		=> 'loginreg',
					'hook'  		=> 'paypalpro_payment_start'
				)
			),
			array_merge($default,
				array(	
					'method'		=> 'loginreg',
					'hook'  		=> 'freeform_module_insert_begin'
				)
			),			
			array_merge($default,
				array(	
					'method'		=> 'cp_validate_members',
					'hook'  		=> 'cp_members_validate_members',
					'priority'		=> 1
				)
			),
			array_merge($default,
				array(
					'method'       => 'user_authors_tab',
					'hook'         => 'publish_form_new_tabs'
				)
			),
			array_merge($default,
				array(
					'method'       => 'user_authors_block',
					'hook'         => 'publish_form_new_tabs_block'
				)
			),
			array_merge($default,
				array(
					'method'       => 'parse',
					'hook'         => 'submit_new_entry_end'
				)
			),
			array_merge($default,
				array(
					'method'       => 'delete',
					'hook'         => 'delete_entries_start'
				)
			),
		);
		
		//this needs to be 2.x only
		if (APP_VER >= 2.0)
		{
			$this->hooks[] = array_merge($default,
				array(
					'method'       => 'ajax',
					'hook'         => 'sessions_end'
				)
			);
		}


    }
    /* END*/
	
	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */

    public function install()
    {
        // Already installed, let's not install again.
        if ($this->database_version() !== FALSE)
        {
        	return FALSE;
        }
        
        /** --------------------------------------------
        /**  Our Default Install
        /** --------------------------------------------*/
        
        if ($this->default_module_install() == FALSE)
        {
        	return FALSE;
        }
        
        /** --------------------------------------------
        /**  Add Profile Views Field to exp_members
        /** --------------------------------------------*/
        
        $sql[] = "ALTER TABLE exp_members ADD (profile_views int(10) unsigned default '0' NOT NULL)";
        
        /** --------------------------------------------
        /**  Default Preferences
        /** --------------------------------------------*/
        
        $forgot_username = <<<EOF
{screen_name},

Per your request, we have emailed you your username for {site_name} located at {site_url}.

Username: {username}
EOF;
        
        $prefs = array(	'email_is_username' 						=> 'n',
        				'screen_name_override'						=> '',
        				'category_groups'							=> '',
        				'welcome_email_subject'						=> ee()->lang->line('welcome_email_content'),
        				'welcome_email_content'						=> '',
        				'user_forgot_username_message'				=> $forgot_username,
        				'member_update_admin_notification_template'	=> '',
						'member_update_admin_notification_emails'	=> '',
						'key_expiration'							=> 7);
        
		foreach($prefs as $pref => $default)
		{
			$sql[]	= ee()->db->insert_string('exp_user_preferences', array('preference_name'	=> $pref,
																			'preference_value'	=> $default));
		}
		
		/** --------------------------------------------
        /**  Module Install
        /** --------------------------------------------*/
        
        $data = array(	'module_name'			=> $this->class_name,
						'module_version'		=> constant(strtoupper($this->class_name).'_VERSION'),
						'has_cp_backend'		=> 'y',
						'has_publish_fields'	=> 'y');
						
		if (APP_VER < 2.0)
		{
			unset($data['has_publish_fields']);
		}
        
        $sql[] = ee()->db->insert_string('exp_modules', $data);
		
        foreach ($sql as $query)
        {
            ee()->db->query($query);
        }
        
        return TRUE;
    }
	/* END install() */
    
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */

    public function uninstall()
    {
        // Cannot uninstall what does not exist, right?
        if ($this->database_version() === FALSE)
        {
        	return FALSE;
        }

		//--------------------------------------------  
		//	remove tabs
		//--------------------------------------------
        
		if (APP_VER >= 2.0) 
		{
			$this->remove_user_tabs();
		}

        //--------------------------------------------  
        //	Drop Profile Views Field from exp_members
        //--------------------------------------------
        
        ee()->db->query("ALTER TABLE `exp_members` DROP `profile_views`");
        
		//--------------------------------------------  
		//	Default Module Uninstall
		//--------------------------------------------
        
        if ($this->default_module_uninstall() == FALSE)
        {
        	return FALSE;
        }
        
        return TRUE;
    }
    /* END */


	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * For the sake of sanity, we only start upgrading from version 2.0 or above.  Cleans out
	 * all of the really old upgrade code, which was making Paul really really crazily confused.
	 *
	 * @access	public
	 * @return	bool
	 */
    
    public function update()
    {
    	/** --------------------------------------------
        /**  ExpressionEngine 2.x attempts to do automatic updates.  
        /**		- Mitchell questioned clients/customers and discovered that the majority preferred to update
        /**		themselves, especially on higher traffic sites. So, we forbid EE 2.x from doing updates
        /**		unless it comes through our update form.
        /** --------------------------------------------*/
        
    	if ( ! isset($_POST['run_update']) OR $_POST['run_update'] != 'y')
    	{
    		return FALSE;
    	}

		/** --------------------------------------------
        /**  User 2.0.2 Upgrade 
        /** --------------------------------------------*/
        
        if ($this->version_compare($this->database_version(), '<', '2.0.2'))
        {
        	ee()->db->query("ALTER TABLE `exp_user_keys` ADD INDEX (`author_id`)");
        	ee()->db->query("ALTER TABLE `exp_user_keys` ADD INDEX (`member_id`)");
        	ee()->db->query("ALTER TABLE `exp_user_keys` ADD INDEX (`group_id`)");
        	
        	ee()->db->query("ALTER TABLE `exp_user_params` ADD INDEX (`hash`)");
        }
        
        /** --------------------------------------------
    	/** Hermes Conversion
        /**  - Added: 3.0.0.d1
        /**  - Perform prior to Hermes default Update
        /** --------------------------------------------*/
        
        if ($this->version_compare($this->database_version(), '<', '3.0.0.d1'))
        {
        	ee()->db->query("UPDATE exp_extensions SET class = 'User_extension' WHERE class = 'User_ext'");
        }
        
        /** --------------------------------------------
        /**  Move Preferences Out of Config.php
        /** --------------------------------------------*/
        
        if ($this->version_compare($this->database_version(), '<', '3.0.0.d25'))
        {
        	$prefs = array(	'user_email_is_username'		=> 'email_is_username',
        					'user_screen_name_override'		=> 'screen_name_override',
        					'user_category_group'			=> 'category_groups',
        					'user_module_key_expiration'	=> 'key_expiration');
        					
        	foreach($prefs as $pref => $new_pref)
        	{
				if (ee()->config->item($pref) !== FALSE)
				{
					$query = ee()->db->query("SELECT preference_value FROM exp_user_preferences 
											  WHERE preference_name = '".ee()->db->escape_str($new_pref)."'
											  AND preference_value != ''
											  LIMIT 1");
					
					if ($query->num_rows() == 0)
					{
						ee()->db->query(ee()->db->insert_string('exp_user_preferences', 
															  array('preference_name'	=> $new_pref,
																	'preference_value'  => ee()->config->item($pref))));
					}
					
					if (APP_VER < 2.0)
					{
						if ( ! class_exists('Admin'))
						{
							require PATH_CP.'cp.admin'.EXT;
						}
		
						Admin::update_config_file(array(), FALSE, array($pref));
					}
					else
					{
						ee()->config->_update_config(array(), array($pref));
					}
				}
			}
        }
        
        /** --------------------------------------------
        /**  Welcome Email Subject - 3.0.2.d2
        /** --------------------------------------------*/
        
        if ($this->version_compare($this->database_version(), '<', '3.0.2.d2'))
        {
			ee()->db->query(ee()->db->insert_string('exp_user_preferences',
													array(	'preference_name'	=> 'welcome_email_subject',
															'preference_value'	=> ee()->lang->line('welcome_email_content'))));
        }
        
        /** --------------------------------------------
        /**  Key Expiration - 3.1.0.d2
        /** --------------------------------------------*/
        
        if ($this->version_compare($this->database_version(), '<', '3.1.0.d2'))
        {
			ee()->db->query(ee()->db->insert_string('exp_user_preferences',
													array(	'preference_name'	=> 'key_expiration',
															'preference_value'	=> 7)));
        }
 
		//remove old tab style from everything
		if (APP_VER >= 2.0 AND $this->version_compare($this->database_version(), '<', '3.2.4'))
		{
			ee()->load->library('layout');
			//remove original layout tabs
			$this->remove_user_tabs();
		
			//check and see if we need to install the newest tabs
			//we want a non-cached set
			$tab_channel_ids = $this->data->get_channel_ids(FALSE);
			
			//if we already have tabs named, we need to reinstall them
			if ($tab_channel_ids !== FALSE)
			{
				ee()->layout->add_layout_tabs($this->tabs(), '', array_keys($tab_channel_ids));
			}
		}
       
    	/** --------------------------------------------
        /**  Default Module Update
        /** --------------------------------------------*/
    
    	$this->default_module_update();
        
        /** --------------------------------------------
        /**  Version Number Update - LAST!
        /** --------------------------------------------*/
        
        $data = array(
			'module_version'		=> constant(strtoupper($this->class_name) . '_VERSION'),
			'has_publish_fields'	=> 'y'
		);
        			  
        if (APP_VER < 2.0)
		{
			unset($data['has_publish_fields']);
		}
    	
    	ee()->db->query(
			ee()->db->update_string(
				'exp_modules',
				$data, 
				array(
					'module_name'	=> $this->class_name
				)
			)
		);							
    									
    	return TRUE;
    }
    /* END update() */
    
    
	// --------------------------------------------------------------------

	/**
	 *	remove all tabs, old and new, from layouts
	 *
	 *	@access		public
	 *	@return		null
	 */	
	public function remove_user_tabs()
	{
		if (APP_VER < 2.0) return;
	
		ee()->load->library('layout');
		
		ee()->layout->delete_layout_tabs(array_merge_recursive($this->old_layout_data, $this->tabs()));
	}
	//END remove_user_tabs()


	// --------------------------------------------------------------------

	/**
	 *	tabs
	 *
	 *	returns tab for user. we replace the name choice with JS later
	 *
	 *
	 *	@access		public
	 *	@return		array
	 */

	public function tabs()
	{
		return array(
			'user' => array(
				'user__solspace_user_browse_authors' => array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				),
				'user__solspace_user_primary_author' => array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				)
			)
		);
	}
	/* END tabs() */
 
}
/* END Class */
?>