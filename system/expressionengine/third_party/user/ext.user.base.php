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
 * Main extension calss for all functionality`
 *
 * @package 	Solspace:User module
 * @author		Solspace DevTeam
 * @filesource 	./system/modules/user/ext.user.base.php
 */
 
if (APP_VER < 2.0)
{
	require_once PATH.'bridge/lib/addon_builder/extension_builder.php';
}
else
{
	require_once PATH_THIRD . 'bridge/lib/addon_builder/extension_builder.php';
}

class User_extension_base extends Extension_builder_bridge
{
	public $name				= "User";
	public $version				= "";
	public $description			= "";
	public $settings_exist		= "n";
	public $docs_url			= "http://solspace.com/docs/addon/c/User/";
	
	public $settings			= array();
		
	public $user_base			= '';
		
	// --------------------------------------------------------------------

	/**
	 *	Constructor
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		null
	 */
	 
    public function User_extension_base( $settings = '' )
    {
    	/** --------------------------------------------
        /**  Load Parent Constructor
        /** --------------------------------------------*/
        
        parent::Extension_builder_bridge();
        
        /** --------------------------------------------
        /**  Settings!
        /** --------------------------------------------*/
        
		$this->settings = $settings;
		
		/** --------------------------------------------
        /**  Set Required Extension Variables
        /** --------------------------------------------*/
        
        if ( is_object(ee()->lang))
        {
        	ee()->lang->loadfile('user');
        
        	$this->name			= ee()->lang->line('user_module_name');
        	$this->description	= ee()->lang->line('user_module_description');
        }
        
		if ( ! isset($this->base) )
		{
			//BASE is not set until AFTER sessions_end, and we don't want to clobber it.
			$base_const = defined('BASE') ? BASE :  SELF . '?S=0';
			
			//2.x adds an extra param for base
			if ( ! (APP_VER < 2.0) )
			{
				$base_const .= '&amp;D=cp';
			}
			
			$this->base	= (APP_VER < 2.0) ? $base_const.'&C=modules&M=' . $this->lower_name : str_replace('&amp;', '&', $base_const).'&C=addons_modules&M=show_module_cp&module=' . $this->lower_name;
		} 

        $this->docs_url		= USER_DOCS_URL;
        $this->version		= USER_VERSION;

		$this->user_base = $this->cached_vars['user_base'] = (APP_VER < 2.0) ? 
							$this->base.'&C=modules&M=user' : 
							str_replace('&amp;', '&', $this->base).'&C=addons_modules&M=show_module_cp&module=user';			
	}
	
	/**	End constructor */
	
	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 *
	 * A required method that we actually ignore because this extension is installed by its module
	 * and no other place.  If they want the extension enabled, they have to install the module.
	 *
	 * In EE 2.x, all Add-Ons are "packages", so they will be prompted to try and install the extension
	 * and module at the same time.  So, we only output a message for them in EE 1.x and in EE 2.x 
	 * we just ignore the request.
	 *
	 * @access	public
	 * @return	null
	 */
    
	public function activate_extension()
    {
    	if (APP_VER < 2.0)
    	{
    		return ee()->output->show_user_error('general', str_replace('%url%', 
    															BASE.AMP.'C=modules',
    															ee()->lang->line('enable_module_to_enable_extension')));
		}
	}
	/* END activate_extension() */
	
	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * A required method that we actually ignore because this extension is installed by its module
	 * and no other place.  If they want the extension disabled, they have to uninstall the module.
	 *
	 * In EE 2.x, all Add-Ons are "packages", so they will be prompted to try and install the extension
	 * and module at the same time.  So, we only output a message for them in EE 1.x and in EE 2.x 
	 * we just ignore the request.
	 *
	 * @access	public
	 * @return	null
	 */
    
	public function disable_extension()
    {
    	if (APP_VER < 2.0)
    	{
    		return ee()->output->show_user_error('general', str_replace('%url%', 
    															BASE.AMP.'C=modules',
    															ee()->lang->line('disable_module_to_disable_extension')));
		}					
	}
	/* END disable_extension() */
	
	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * A required method that we actually ignore because this extension is updated by its module
	 * and no other place.  We cannot redirect to the module upgrade script because we require a 
	 * confirmation dialog, whereas extensions were designed to update automatically as they will try
	 * to call the update script on both the User and CP side.
	 *
	 * @access	public
	 * @return	null
	 */
    
	function update_extension()
    {
    
	}
	/* END update_extension() */
	
    // --------------------------------------------------------------------

	/**
	 *	Login/Registration During Form Submission
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		array
	 */

    public function loginreg( $data = array() )
	{
		if ( is_array( ee()->extensions->last_call ) && count( ee()->extensions->last_call ) > 0 )
		{
			$data = ee()->extensions->last_call;
		}
		
		if ( ee()->input->post('user_login_type') === FALSE OR ee()->input->post('user_login_type') == '')
		{
			return $data;
		}
		
		ee()->extensions->end_script = FALSE;
		
		/** ----------------------------------------
		/**	Instantiate class
		/** ----------------------------------------*/
		
		if ( class_exists('User') === FALSE )
		{
			require $this->addon_path.'mod.user'.EXT;
		}
		
		$User = new User();
		
		if ( ee()->input->post('user_login_type') != 'register' )
		{
			$User->_remote_login();
		}
		else
		{
			$User->_remote_register();
		}
		
		return $data;
	}
	
	/**	End loginreg */
	
	
	// --------------------------------------------------------------------

	/**
	 *	Validate Members
	 *
	 *	@access		public
	 *	@return		null
	 */

    public function cp_validate_members()
	{
		if ( ! ee()->input->post('toggle') OR $_POST['action'] != 'activate')
        {
            return;
        }
        
        $member_ids = array();
        
        foreach ($_POST['toggle'] as $key => $val)
        {        
            if ( ! is_array($val))
            {
            	$member_ids[] = $val;
            }
		}

		if (sizeof($member_ids) == 0)
		{
			return;
		}
		
		/** ----------------------------------------
		/**	Instantiate class
		/** ----------------------------------------*/
		
		if ( class_exists('User') === FALSE )
		{
			require $this->addon_path.'mod.user'.EXT;
		}
		
		$User = new User();

		$User->cp_validate_members($member_ids);
	}
	
	/* END cp_validate_members() */
	
	// ----------------------------------------------------------------------------
	// user author functions
	// ----------------------------------------------------------------------------
	
	// --------------------------------------------------------------------

	/**
	 *	User Authors Tab
	 *
	 *	Adds the Tab to the EE 1.x Publish Page
	 *
	 *	@access		public
	 *	@param		array
	 *	@param		integer
	 *	@param		integer
	 *	@return		array
	 */
	 
	function user_authors_tab( $publish_tabs, $channel_id, $entry_id )
	{
		if ( is_array( ee()->extensions->last_call))
		{
			$publish_tabs = ee()->extensions->last_call;
		}
		
		/** ----------------------------------------
		/**	Check group permission
		/** ----------------------------------------*/
		
		if ( ee()->session->userdata['group_id'] != '1' )
		{
			$query   = ee()->db->query(
				"SELECT 	COUNT(*) AS count 
				 FROM 		exp_module_member_groups mg 
				 LEFT JOIN 	exp_modules m 
				 ON 		m.module_id = mg.module_id 
				 WHERE 		m.module_name = 'User' 
				 AND 		mg.group_id = '".ee()->db->escape_str( ee()->session->userdata('group_id') )."'" 
			);

			if ( $query->row('count') == 0 )
			{
				return $publish_tabs;
			}
		}
		
		/** --------------------------------------------
        /**  Do We Have a Setting for this Weblog/Channel
        /** --------------------------------------------*/
		
		$data = $this->data->get_channel_id_pref($channel_id);
		
		if ( $data == "" OR empty($data) OR $data === FALSE )
		{
			return $publish_tabs;
		}
		else
		{		
			$publish_tabs['user_authors'] = $this->data->get_channel_id_pref($channel_id);
			
			return $publish_tabs;
		}
	}
	/* END user_authors_tab() */
	
    
    // --------------------------------------------------------------------
    
	/**
	 *	User Authors Tab Block
	 *
	 *	Adds a Tab Block to the Publish Area of the CP for the User Authors Extension
	 *
	 *	@access		public
	 *	@param		integer
	 *	@return		string
	 */

	public function user_authors_block( $channel_id = '' )
    {	
    	ee()->lang->loadfile('user');

		/**	----------------------------------------
		/**	 Entry ID and Channel Id
		/**	----------------------------------------*/
		
		$this->cached_vars['entry_id']	 = '';
		$this->cached_vars['channel_id'] = $channel_id;
		
		if ( ee()->input->get_post('entry_id') !== FALSE AND ctype_digit( ee()->input->get_post('entry_id') ) )
		{
			$this->cached_vars['entry_id'] = ee()->input->get_post('entry_id');
		}
		
		if ( ee()->input->get('weblog_id') !== FALSE )
		{
			$this->cached_vars['channel_id'] = ee()->input->get('weblog_id');
		}
		elseif ( ee()->input->get('channel_id') !== FALSE )
		{
			$this->cached_vars['channel_id'] = ee()->input->get('channel_id');
		}
		
		/**	----------------------------------------
		/**	Query for authors
		/**	----------------------------------------*/
		
		if ($this->cached_vars['entry_id'] != '')
		{
			$query	= ee()->db->query(
				"SELECT DISTINCT	(ua.author_id), ua.principal, m.screen_name  
				 FROM 				exp_user_authors ua, exp_members m
				 WHERE 				ua.author_id != '0' 
			   	 AND 				ua.entry_id = '".ee()->db->escape_str($this->cached_vars['entry_id'])."' 
				 AND 				ua.author_id = m.member_id
				 ORDER BY 			m.screen_name" 
			);
		}
		
        /**	----------------------------------------
		/**	Create hash
		/**	----------------------------------------*/

		$this->cached_vars['hash'] = $this->create_xid();

		/**	----------------------------------------
		/**	Insert hash to DB
		/**	----------------------------------------*/
		
		if ($this->EE->config->item('secure_forms') != 'n')
		{
			$arr	= array(
				'date'			=> ee()->localize->now,
				'ip_address'	=> ee()->input->ip_address(),
				'hash'			=> $this->cached_vars['hash']
			);
			
			$sql	= ee()->db->insert_string( 'exp_security_hashes', $arr );
				
			ee()->db->query( $sql );
		}
		
		/** --------------------------------------------
        /**  Add CSS to Page
        /** --------------------------------------------*/
        
        ee()->cp->extra_header .= "\n<style type='text/css'>\n".$this->view('tab_block.css', array(), TRUE)."\n</style>";
        
        /** --------------------------------------------
        /**  Current Authors
        /** --------------------------------------------*/
        
        $this->cached_vars['principal_author'] = '0';
        $this->cached_vars['assigned_authors'] = array();
		
		if ( $this->cached_vars['entry_id'] != '' )
		{
			foreach ( $query->result_array() as $row )
			{
				// Deleted Member?
				if (empty($row['screen_name']))
				{
					ee()->db->query("DELETE FROM exp_user_authors 
									WHERE entry_id = '".ee()->db->escape_str($entry_id)."' 
									AND author_id = '".ee()->db->escape_str($row['author_id'])."'");
								
					continue;
				}
				
				if ( $row['principal'] == 'y' )
				{
					$this->cached_vars['principal_author'] = $row['author_id'];
				}
				
				$this->cached_vars['assigned_authors'][$row['author_id']] = $row['screen_name'];
			}
		}
		
		/** --------------------------------------------
        /**  Return Tab Block HTML
        /** --------------------------------------------*/
        
        $r = ( ee()->extensions->last_call !== FALSE ) ? ee()->extensions->last_call : '';
        
        return $r.$this->view('tab_block.html', array(), TRUE);
    }
    /* END user_authors_block() */
    
    
	// --------------------------------------------------------------------

	/**
	 *	User Authors Parsing Routine
	 *
	 *	@access		public
	 *	@param		integer
	 *	@return		null
	 */

	public function parse( $entry_id, $data, $ping_message )
	{
		/**	----------------------------------------
		/**	Do we have a hash?
		/**	----------------------------------------*/
		
		if ( ee()->input->post('user_authors_hash') !== FALSE AND ee()->input->post('user_authors_hash') != '' )
		{
			$hash	= ee()->input->post('user_authors_hash');
		}
		else
		{
			return;
		}
		
		/**	----------------------------------------
		/**	Do we have a principal author?
		/**	----------------------------------------*/
		
		$principal	= '';
		
		if ( ee()->input->post('user_authors_principal') !== FALSE AND ee()->input->post('user_authors_principal') != '' )
		{
			$principal	= ee()->input->post('user_authors_principal');
		}
		
		/**	----------------------------------------
		/**	Update the entries entry ids
		/**	----------------------------------------*/
		
		ee()->db->query( ee()->db->update_string('exp_user_authors',
												 array( 'entry_id'	=> $entry_id ),
												 array( 'hash' 		=> $hash,
												 		'entry_id'	=> '0' ) ) );
		
		/**	----------------------------------------
		/**	Clear the principal
		/**	----------------------------------------*/
		
		ee()->db->query( ee()->db->update_string('exp_user_authors',
												 array( 'principal'	=> 'n' ),
												 array( 'entry_id'	=> $entry_id ) ) );
		
		/**	----------------------------------------
		/**	Set principal
		/**	----------------------------------------*/
		
		if ( $principal != '' )
		{
			ee()->db->query( ee()->db->update_string('exp_user_authors',
													 array( 'principal'		=> 'y' ),
													 array( 'entry_id'		=> $entry_id,
													 		'author_id'		=> $principal ) ) );
		}
		
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		return;
	}
	
	/* END parse() */
	
	// --------------------------------------------------------------------

	/**
	 *	User Authors - Delete
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		string
	 */

	public function delete()
	{
		if ( empty($_POST['delete']) OR ! is_array($_POST['delete']) ) return;
		
		/**	----------------------------------------
		/**	 Delete Query
		/**	----------------------------------------*/
                
        $query = ee()->db->query("DELETE FROM exp_user_authors
        						  WHERE entry_id IN ('".implode("','", ee()->db->escape_str($_POST['delete']))."')");
		
		return;
	}
	/* END delete() */
	
	// --------------------------------------------------------------------

	/**
	 *	User Authors - Ajax
	 *
	 *	@access		public
	 *	@param		null
	 *	@return		null
	 */

	public function ajax( $incoming )
	{
		if (ee()->extensions->last_call !== FALSE)
		{
			$incoming = ee()->extensions->last_call;
		}
		
		//user stuff is sensitive, so CP only
		if ( REQ != 'CP' OR 
			 ee()->input->get('solspace_user_ajax') === FALSE)
		{
			return $incoming;
		}

		if ( class_exists('User_cp_base') === FALSE )
		{
			require $this->addon_path.'mcp.user.base'.EXT;
		}

		$ucpb = new User_cp_base();
		
		//will call an exit if a script is found
		$ucpb->ajax();
		
		//failsafe
		return $incoming;
	}
	/* END delete() */
    
}

/**	END User_extension_base CLASS */