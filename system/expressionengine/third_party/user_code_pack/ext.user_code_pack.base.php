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
 
if (APP_VER < 2.0)
{
	require_once PATH.'bridge/lib/addon_builder/extension_builder.php';
}
else
{
	require_once PATH_THIRD . 'bridge/lib/addon_builder/extension_builder.php';
}
 
class User_code_pack_extension_base extends Extension_builder_bridge
{	
	var $name			= '';
	var $version		= '';
	var $description	= '';
	var $settings_exist	= 'n';
	var $docs_url		= '';
	
	var $default_settings	= array();
	var $settings			= array();
	var $hooks				= array();
	    
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 
	 * @access	public
	 * @return	null
	 */
    
	function User_code_pack_extension_base( $settings = array() )
    {    	
    	parent::Extension_builder_bridge('user_code_pack');
    	
    	/** --------------------------------------------
        /**  Settings
        /** --------------------------------------------*/
    	
    	$this->settings = $settings;
    	
        /** --------------------------------------------
        /**  Set Required Extension Variables
        /** --------------------------------------------*/
        
        if ( is_object(ee()->lang))
        {
        	ee()->lang->loadfile('user_code_pack');
        
        	$this->name			= ee()->lang->line('user_code_pack_name');
        	$this->description	= ee()->lang->line('user_code_pack_description');
        }
        
		$this->version		= USER_CODE_PACK_VERSION;
		$this->docs_url		= USER_CODE_PACK_DOCS_URL;
		
		/** --------------------------------------------
        /**  Extension Hooks
        /** --------------------------------------------*/
        
        $this->default_settings	= array();
        
        $default = array(	'class'        => $this->class_name,
							'settings'     => serialize($this->settings), 		
							'priority'     => 10,								// We're #1!
							'version'      => $this->version,
							'enabled'      => 'y'
							);
        
        $this->hooks = array(
			'code_pack_list'		=> array_merge(
				$default,
				array(
					'method'		=> 'code_pack_list',
					'hook'			=> 'code_pack_list'
				  )
			),
			'code_pack_install'		=> array_merge(
				$default,
				array(
					'method'		=> 'code_pack_install',
					'hook'			=> 'code_pack_module_install_begin_' . $this->lower_name
				  )
			)
		);
																				
		/** --------------------------------------------
        /**  Update to New Version - Required
        /** --------------------------------------------*/
        
        if (isset(ee()->extensions->version_numbers[$this->class_name])  && $this->version_compare($this->version, '>', ee()->extensions->version_numbers[$this->class_name]))
        {
        	$this->update_extension_hooks();
        }
	}
	
	/*	End constructor */
	
	// --------------------------------------------------------------------

	/**
	 * Code pack install
	 *
	 * This method runs during the installation of a code pack in the User module CP. It adds some members, weblog data and some user module data.
	 *
	 * @access	public
	 * @param	message
	 * @return	string
	 */
    
    function code_pack_install( &$ths, $variables )
    {
		ee()->extensions->end_script = FALSE;
		
		$cached_vars	= array();

		if ( is_array( ee()->extensions->last_call ) === TRUE )
		{
			$cached_vars	= ee()->extensions->last_call;
		}
		elseif ( ! empty( $ths->cached_vars ) )
		{
			$cached_vars	= $ths->cached_vars;
		}
    	
		/**	----------------------------------------
		/**	Do we have a prefix?
		/**	----------------------------------------*/
		
		if ( empty( $variables['prefix'] ) )
		{
			return $cached_vars;
		}
    	
		/**	----------------------------------------
		/**	Provide a link to the main template group and template for this code pack.
		/**	----------------------------------------*/
		
		$home_url	= ee()->functions->create_url( $variables['prefix'] . 'main/index' );
		
		$arr	= array(
			'label'			=> ee()->lang->line('home_page'),
			'description'	=> str_replace( '%link%', '<a href="' . $home_url . '" target="_blank">' . ee()->lang->line('home_page') . '</a>', ee()->lang->line('home_page_exp') )
		);

		$cached_vars['success'][]	= $arr;
    	
		/**	----------------------------------------
		/**	We're not actually going to create sample data right now
		/**	----------------------------------------*/
		
		return $cached_vars;
    	
		/**	----------------------------------------
		/**	Do we have a 'Member' group to assign our new members?
		/**	----------------------------------------*/
		
		$sql	= "SELECT group_id FROM exp_member_groups WHERE site_id = ".ee()->db->escape_str( ee()->config->item('site_id') )." AND group_title = 'Members' LIMIT 1";
		
		$query	= ee()->db->query( $sql );
		
		if ( $query->num_rows() == 0 )
		{
			$arr	= array(
				'label'			=> ee()->lang->line('missing_member_group'),
				'description'	=> ee()->lang->line('missing_member_group_exp'),
			);
	
			$cached_vars['errors'][]	= $arr;
		}
		
		$member_group_id	= $query->row('group_id');
    	
		/**	----------------------------------------
		/**	Check for member data conflicts
		/**	----------------------------------------*/
		
		$members[]	= array(
			'group_id'		=> $member_group_id,
			'username'		=> $variables['prefix'] . 'jake',
			'screen_name'	=> rtrim( $variables['prefix'], '_' ).' Jake Solspace',
			'email'			=> $variables['prefix'] . 'jakes@solspace.com',
        	'password'		=> ee()->functions->hash('jake2009'),
        	'unique_id'		=> ee()->functions->random('encrypt'),
			'join_date'		=> ee()->localize->now
		);
		
		$members[]	= array(
			'group_id'		=> $member_group_id,
			'username'		=> $variables['prefix'].'mike',
			'screen_name'	=> rtrim( $variables['prefix'], '_' ).' Mike Solspace',
			'email'			=> $variables['prefix'] . 'mikes@solspace.com',
        	'password'		=> ee()->functions->hash('mike2009'),
        	'unique_id'		=> ee()->functions->random('encrypt'),
			'join_date'		=> ee()->localize->now
		);
    	
		/**	----------------------------------------
		/**	Prepare SQL test arrays
		/**	----------------------------------------*/
		
		$emails		= array();
		$usernames	= array();
		
		foreach ( $members as $member )
		{
			$emails[]		= $member['email'];
			$usernames[]	= $member['username'];
		}
    	
		/**	----------------------------------------
		/**	Test DB for conflicts
		/**	----------------------------------------*/
		
		$sql	= "SELECT email, username
			FROM exp_members
			WHERE username IN ('" . implode( "','", $usernames ) . "')
			OR email IN ('" . implode( "','", $emails ) . "')";
		
		$query	= ee()->db->query( $sql );
		
		$conf_emails	= array();
		$conf_usernames	= array();
		
		if ( $query->num_rows() > 0 )
		{
			foreach ( $query->result_array() as $row )
			{
				$conf_emails[]		= $row['email'];
				$conf_usernames[]	= $row['username'];
			}
		}
		
		if ( count( $conf_emails ) > 0 )
		{
			$arr	= array(
				'label'			=> ee()->lang->line('conflicting_emails'),
				'description'	=> str_replace( '%conflicting_emails%', '<div class="defaultBold">'.implode( ', ', $conf_emails ).'</div>', ee()->lang->line('conflicting_emails_exp') )
			);
	
			$cached_vars['errors'][]	= $arr;
		}
		
		if ( count( $conf_usernames ) > 0 )
		{
			$arr	= array(
				'label'			=> ee()->lang->line('conflicting_usernames'),
				'description'	=> str_replace( '%conflicting_usernames%', '<div class="defaultBold">'.implode( ', ', $conf_usernames ).'</div>', ee()->lang->line('conflicting_usernames_exp') )
			);
	
			$cached_vars['errors'][]	= $arr;
		}
    	
		/**	----------------------------------------
		/**	Errors?
		/**	----------------------------------------*/
		
		if ( count( $cached_vars['errors'] ) > 0 )
		{
			return $cached_vars;
		}
    	
		/**	----------------------------------------
		/**	Insert member data
		/**	----------------------------------------*/
		
		$conf_members	= array();
		
		foreach ( $members as $member )
		{
			$sql	= ee()->db->insert_string( 'exp_members', $member );
			
			ee()->db->query( $sql );
			
			$member_id	= ee()->db->insert_id;
                                       
			ee()->db->query( ee()->db->insert_string('exp_member_data', array( 'member_id' => $member_id ) ) );
								
			ee()->db->query( ee()->db->insert_string('exp_member_homepage', array( 'member_id' => $member_id ) ) );
			
			$conf_members[]	= $member['screen_name'];
		}		
		
		$arr	= array(
			'label'			=> ee()->lang->line('members_created'),
			'description'	=> str_replace( '%members_created%', '<div class="defaultBold">'.implode( ', ', $conf_members ).'</div>', ee()->lang->line('members_created_exp') )
		);

		$cached_vars['success'][]	= $arr;
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $cached_vars;
    }
    
    /*	End user code pack install */
	
	// --------------------------------------------------------------------

	/**
	 * Code pack list
	 *
	 * This method populates the running list of code packs and makes itself known.
	 *
	 * @access	public
	 * @param	message
	 * @return	array
	 */
    
    function code_pack_list( $code_packs )
    {
    	if ( is_array( ee()->extensions->last_call ) === TRUE AND ! empty( ee()->extensions->last_call ) )
    	{
    		$code_packs	= ee()->extensions->last_call;
    	}
    	
    	$this_code_pack['user_code_pack']	= array(
    		'code_pack_name'			=> $this->lower_name,
    		'code_pack_label'			=> ee()->lang->line( 'user_code_pack_name' ),
    		'code_pack_description'		=> ee()->lang->line( 'user_code_pack_description' ),
    		'code_pack_theme_folder'	=> 'user',
    	);
    	
    	return $code_packs	= array_merge( $code_packs, $this_code_pack );
    }
    
    /*	End code pack list */
}

/* End class */