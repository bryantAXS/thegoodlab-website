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
 * User Module Class - User Side
 *
 * In charge of all Template and Action processing for User module
 *
 * @package 	Solspace:User module
 * @author		Solspace DevTeam
 * @filesource 	./system/modules/user/mod.user.php
 */

if (APP_VER < 2.0)
{
	require_once PATH.'bridge/lib/addon_builder/module_builder.php';
}
else
{
	require_once PATH_THIRD . 'bridge/lib/addon_builder/module_builder.php';
}

class User extends Module_builder_bridge
{
	public static $trigger			= 'user';
	public static $key_trigger		= 'key';

	protected 	$TYPE;
	protected 	$UP;
	protected 	$query;
	protected 	$email_obj			= FALSE;
	
	protected 	$dynamic			= TRUE;
	protected 	$member_only		= FALSE;
	protected 	$multipart			= FALSE;
	protected 	$search				= FALSE;
	protected 	$selected			= FALSE;
	
	private 	$entry_id			= 0;
	private 	$member_id			= 0;
	private 	$params_id			= 0;
	                            	
	public 		$refresh			= 1440;	//	Cache refresh in minutes
	       		                	
	public 		$cur_page			= 0;
	public 		$current_page		= 0;
	public 		$limit				= 100;
	public 		$total_pages		= 0;
	public 		$page_count			= '';
	public 		$pager				= '';
	public 		$paginate			= FALSE;
	public 		$paginate_data		= '';
	public 		$res_page			= '';
	public 		$completed_override	= FALSE;
	public		$screen_name_dummy	= '4f99fa19c1d3b11c9ad517b0c073e450';
	public		$lang_dir			= '';
	
	private 	$group_id			= '';
	private 	$str				= '';
	
	protected 	$assigned_cats		= array();
	protected 	$cat_params			= array();
	protected 	$cat_parents		= array();
	protected 	$categories			= array();
	public    	$form_data			= array();
	public	  	$insert_data		= array();
	protected 	$img				= array();
	protected 	$mfields			= array();
	protected 	$params				= array();
	protected 	$used_cat			= array();
	protected 	$catfields			= array();
	protected 	$cat_chunk			= array();
	          	                	
	protected 	$cat_formatting		= array( 
		'category_tagdata' 		=> '', 
		'category_formatting' 	=> '', 
		'category_header' 		=> '', 
		'category_indent' 		=> '', 
		'category_body' 		=> '', 
		'category_footer' 		=> '', 
		'category_selected' 	=> '', 
		'category_group_header' => '', 
		'category_group_footer' => '' 
	);
	
	protected 	$standard			= array( 
		'url', 
		'location', 
		'occupation', 
		'interests', 
		'language', 
		'last_activity', 
		'bday_d', 
		'bday_m', 
		'bday_y', 
		'daylight_savings', 
		'aol_im', 
		'yahoo_im', 
		'msn_im', 
		'icq', 
		'bio', 
		'profile_views', 
		'time_format', 
		'timezone', 
		'signature' 
	);
	
	protected 	$check_boxes		= array( 
		'accept_admin_email', 
		'accept_user_email', 
		'daylight_savings', 
		'notify_by_default', 
		'notify_of_pm', 
		'smart_notifications' 
	);
	
	protected 	$photo				= array( 
		'photo_filename', 
		'photo_width', 
		'photo_height' 
	);
	
	protected 	$avatar				= array( 
		'avatar_filename', 
		'avatar_width', 
		'avatar_height' 
	);
	
	protected 	$signature			= array( 
		'signature', 
		'sig_img_filename', 
		'sig_img_width', 
		'sig_img_height' 
	);
	
	protected 	$images				= array( 
		'avatar' 	=> 'avatar_filename', 
		'photo' 	=> 'photo_filename', 
		'sig' 		=> 'sig_img_filename' 
	);
	
	protected 	$preferences		= array();

	private 	$uploads			= array();

	// --------------------------------------------------------------------

	/**
	 *	Constructor
	 *
	 *	@access		public
	 *	@return		null
	 */

	public function __construct()
	{
		/** --------------------------------------------
        /**  Call Module Builder Parent
        /** --------------------------------------------*/
        
        parent::Module_builder_bridge('user');
		
		/** --------------------------------------------
        /**  Language Files of Translating Might!
        /** --------------------------------------------*/
        
		ee()->lang->loadfile('myaccount');
		ee()->lang->loadfile('member');
		ee()->lang->loadfile('user');  // Goes last as User overrides a few Member language variables
		
		/** --------------------------------------------
        /**  Welcome Email
        /** --------------------------------------------*/
        
     	if (ee()->config->item('req_mbr_activation') == 'manual' AND 
			ee()->db->table_exists('exp_user_welcome_email_list'))
        {
        	$query = ee()->db->query(
				"SELECT m.screen_name, m.email, m.username, m.member_id 
				 FROM 	exp_members AS m, exp_user_welcome_email_list AS el
				 WHERE 	m.member_id = el.member_id
				 AND 	el.email_sent = 'n'
				 AND 	el.group_id != m.group_id
				 LIMIT 	2"
			);
        						 
        	foreach($query->result_array() as $row)
			{
				$this->welcome_email($row);
			}
		}
		
		//--------------------------------------------  
		//	lang directory
		//--------------------------------------------
		
		$this->lang_dir =  (APP_VER < 2.0) ? PATH_LANG : APPPATH . 'language/';
		
		//--------------------------------------------  
		//	force https?
		//--------------------------------------------
		
		$this->_force_https();
	}

	/* END constructor */
	
	
    // --------------------------------------------------------------------

	/**
	 *	Statistics for a Specific User
	 *
	 *
	 *	@access		public
	 *	@return		string
	 */
 
	public function stats()
    {	
		/**	----------------------------------------
		/**	Member id only?
		/**	----------------------------------------*/
		
		if ( ! $this->_member_id() )
		{
    		return $this->no_results('user');
		}
    	
		/**	----------------------------------------
		/**	Set exclude
		/**	----------------------------------------*/
		
		$exclude	= array( 
			'channel_id', 			'weblog_id', 			'tmpl_group_id', 
			'upload_id', 			'password', 			'unique_id', 
			'authcode', 			'avatar_filename', 		'avatar_width', 
			'avatar_height',		'photo_width', 			'photo_height', 		
			'sig_img_width', 		'sig_img_height', 		'notepad',
			'in_authorlist', 		'accept_admin_email', 	'accept_user_email', 
			'notify_by_default', 	'notify_of_pm', 		'display_avatars', 
			'display_signatures', 	'smart_notifications', 	'localization_is_site_default', 
			'cp_theme', 			'profile_theme', 		'forum_theme', 
			'tracker',				'notepad_size', 		'quick_links', 			
			'quick_tabs', 			'pmember_id' 
		);
		
		if ( ee()->TMPL->fetch_param('exclude') )
		{
			$exclude	= array_merge( $exclude, explode( "|", ee()->TMPL->fetch_param('exclude') ) );
		}
    	
		/**	----------------------------------------
		/**	Set include
		/**	----------------------------------------*/
		
		$include		= array();
		
		if ( ee()->TMPL->fetch_param('include') )
		{
			$include	= array_merge( $include, explode( "|", ee()->TMPL->fetch_param('include') ) );
		}
    	
		/**	----------------------------------------
		/**	Set dates
		/**	----------------------------------------*/
		
		$dates	= array( 
			'join_date', 			'last_bulletin_date', 	'last_visit', 
			'last_activity', 		'last_entry_date', 		'last_rating_date', 
			'last_comment_date', 	'last_forum_post_date', 'last_email_date' 
		);
    	
		/**	----------------------------------------
		/**	Fetch stats
		/**	----------------------------------------*/
		
		$query	= ee()->db->query(
			"SELECT m.*, mg.group_title,
					m.screen_name AS user_screen_name, 
					( m.total_entries + m.total_comments ) AS total_combined_posts
			 FROM 	exp_members AS m, exp_member_groups AS mg
			 WHERE 	member_id = '".ee()->db->escape_str( $this->member_id )."'
		  	 AND 	m.group_id = mg.group_id
			 AND 	mg.site_id = '".ee()->db->escape_str(ee()->config->slash_item('site_id'))."'"
		);
		
		if ( $query->num_rows() == 0 )
		{
    		return $this->no_results('user');
		}
		
		$query_row = $query->row_array();
    	
		/**	----------------------------------------
		/**	Update profile views
		/**	----------------------------------------*/
		
		$this->_update_profile_views();
		
		/**	----------------------------------------
		/**	Add additional values to $query->row
		/**	----------------------------------------*/
		
		$query_row['photo_url']				= ee()->config->item('photo_url');
		$query_row['avatar_url']			= ee()->config->slash_item('avatar_url');
		$query_row['sig_img_url']			= ee()->config->slash_item('sig_img_url');
		                            		
		/** --------------------------------------------
        /**  Tweak Forum Variables to Match Meaning
        /** --------------------------------------------*/
    	
    	$query_row['total_forum_replies']	= $query->row('total_forum_posts');
		$query_row['total_forum_posts']		= $query->row('total_forum_topics') + $query->row('total_forum_posts');
    	
		/**	----------------------------------------
		/**	Handle categories
		/**	----------------------------------------*/
		
		$tagdata							= ee()->TMPL->tagdata;
		                    				
		$this->member_only					= ( ee()->TMPL->fetch_param('member_only') !== FALSE AND 
												ee()->TMPL->fetch_param('member_only') == 'no' ) ? FALSE: TRUE;
		                    				
		$tagdata							= $this->_categories( $tagdata, 'yes' );
    	
		/**	----------------------------------------
		/**	Conditional Prep
		/**	----------------------------------------*/
		
		$cond		= $query->row_array();
		
		$custom	= ee()->db->query( 
			"SELECT * 
			 FROM 	exp_member_data 
			 WHERE 	member_id = '".ee()->db->escape_str( $this->member_id )."'" 
		);
		
		$custom_row = $custom->row_array();
		
		foreach ( $this->_mfields() as $key => $val )
		{
			$cond[$key] = (isset($custom_row['m_field_id_'.$val['id']])) ? 
								$custom_row['m_field_id_'.$val['id']] : '';
		}
		
		/** --------------------------------------------
        /**  Typography
        /** --------------------------------------------*/
		
		ee()->load->library('typography');
		
		if (APP_VER >= 2.0)
		{
			ee()->typography->initialize();
		}
		
		ee()->typography->convert_curly = FALSE;

		if ($query->row('bio') != '')
		{
			$query_row['bio'] = ee()->typography->parse_type(
				$query->row('bio'), 
				array(
					'text_format'   => 'xhtml',
					'html_format'   => 'safe',
					'auto_links'    => 'y',
					'allow_img_url' => 'n'
				)
			);
		}

		//signature also needs love
		if ($query->row('signature') != '')
		{
			$query_row['signature'] = ee()->typography->parse_type(	
				$query->row('signature'), 
				array(
					'text_format'   => 'xhtml',
					'html_format'   => 'safe',
					'auto_links'    => 'y',
					'allow_img_url' => (ee()->config->item('sig_allow_img_hotlink') == 'y') ? 'y' : 'n'
				)
			);
		}
	
		/** --------------------------------------------
        /**  Conditionals
        /** --------------------------------------------*/
		
		$tagdata	= ee()->functions->prep_conditionals( $tagdata, $cond );
    	
		/**	----------------------------------------
		/**	Parse all
		/**	----------------------------------------*/
		
		if ( preg_match( "/".LD.'all'.RD."(.*?)".LD.preg_quote(T_SLASH, '/').'all'.RD."/s", $tagdata, $match ) )
		{			
			$str	= '';
			
			foreach ( $query->row_array() as $key => $val )
			{
				if ( count($include) > 0 )
				{
					if ( ! in_array( $key, $include ) )
					{
						continue;
					}
				}
				elseif ( in_array( $key, $exclude ) )
				{
					continue;
				}
				
				$all	= $match['1'];
				
				$all	= str_replace( LD.'label'.RD, $key, $all );
				
				if ( in_array( $key, $dates ) AND $val != 0 )
				{
					$all	= str_replace( LD.'value'.RD, ee()->localize->set_human_time($val), $all );
				}
				else
				{
					$all	= str_replace( LD.'value'.RD, $val, $all );
				}
				
				$str	.= $all;
			}
			
			$tagdata	= str_replace( $match[0], $str, $tagdata );
		}
		
		/**	----------------------------------------
		/**	Parse dates
		/**	----------------------------------------*/
                
		foreach ($dates as $val)
		{					
			if (preg_match_all("/".LD.$val."\s+format=([\"'])([^\\1]*?)\\1".RD."/s", $tagdata, $matches))
			{
				for($i=0, $s=sizeof($matches[2]); $i < $s; ++$i)
				{
					$str	= $matches[2][$i];
					
					$codes	= ee()->localize->fetch_date_params( $matches[2][$i] );
					
					foreach ( $codes as $code )
					{
						$str	= str_replace( 
							$code, 
							ee()->localize->convert_timestamp( 
								$code, 
								$query_row[$val], 
								TRUE 
							), 
							$str 
						);
					}
					
					$tagdata	= str_replace( $matches[0][$i], $str, $tagdata );
				}
			}
		}
    	
		/**	----------------------------------------
		/**	Parse remaining standards
		/**	----------------------------------------*/
		
		foreach ( $query_row as $key => $val )
		{
			if ( in_array( $key, $dates ) )
			{
				$tagdata	= str_replace( LD.$key.RD, $val, $tagdata );
			}
			else
			{
				$tagdata	= str_replace( LD.$key.RD, $val, $tagdata );
			}
		}
    	
		/**	----------------------------------------
		/**	Parse custom variables
		/**	----------------------------------------*/
		
		if ( $custom->num_rows() > 0 )
		{	
			foreach ( $this->_mfields() as $key => $val )
			{
				/**	----------------------------------------
				/**	Conditionals
				/**	----------------------------------------*/
				
				$cond[ $val['name'] ]	= $custom_row['m_field_id_'.$val['id']];
				$tagdata				= ee()->functions->prep_conditionals( $tagdata, $cond );
				
				/**	----------------------------------------
				/**	Parse select
				/**	----------------------------------------*/
				
				foreach ( ee()->TMPL->var_pair as $k => $v )
				{
					if ( $k == "select_".$key )
					{
						$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $k );
						
						$tagdata	= preg_replace( 
							"/".LD.preg_quote($k, '/').RD."(.*?)".
								LD.preg_quote(T_SLASH, '/').preg_quote($k, '/').RD."/s", 
							str_replace(
								'$', 
								'\$', 
								$this->_parse_select( 
									$key, 
									$custom_row, 
									$data 
								)
							),
							$tagdata 
						);
					}
				}
				
				/**	----------------------------------------
				/**	Parse singles
				/**	----------------------------------------*/
				
				$tagdata = ee()->TMPL->swap_var_single(
					$key, 
					ee()->typography->parse_type( 
						$custom_row['m_field_id_'.$val['id']], 
						array(
							'text_format'   => $this->mfields[$key]['format'],
							'html_format'   => 'safe',
							'auto_links'    => 'n',
							'allow_img_url' => 'n'
						)
					), 
					$tagdata
				);
			}
		}
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		return $tagdata;
    }
    
    /* END stats */
    
	
    // --------------------------------------------------------------------

	/**
	 *	Users Tag
	 *
	 *	Displays a list of Users, what else?
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function users()
    {
		/**	----------------------------------------
		/**	Assemble query
		/**	----------------------------------------*/
		
		if ( ee()->TMPL->fetch_param('disable') !== FALSE && stristr('member_data', ee()->TMPL->fetch_param('disable')))
		{
			$sql	 = "SELECT DISTINCT m.*, ( m.total_entries + m.total_comments ) AS total_combined_posts";
		}
		else
		{
			$sql	 = "SELECT DISTINCT m.*, md.*, ( m.total_entries + m.total_comments ) AS total_combined_posts";
		}
		
		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= ", mg.group_title, mg.group_description ";
		}
		
		if ( ee()->TMPL->fetch_param('disable') !== FALSE && stristr('member_data', ee()->TMPL->fetch_param('disable')))
		{
			$sql 	.= " FROM exp_members m ";
		}
		else
		{
			$sql 	.= " FROM exp_members m 
					     LEFT JOIN exp_member_data md ON md.member_id = m.member_id";
		}
				     
		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= " LEFT JOIN exp_member_groups AS mg ON mg.group_id = m.group_id";
		}
    	
		/**	----------------------------------------
		/**	Dynamic?
		/**	----------------------------------------*/
		
		$dynamic	= TRUE;
		
		if ( ee()->TMPL->fetch_param('dynamic') !== FALSE AND $this->check_no(ee()->TMPL->fetch_param('dynamic')))
		{
			$dynamic	= FALSE;
		}
    	
		/**	----------------------------------------
		/**	Fetch category
		/**	----------------------------------------*/
		
		if ( ee()->TMPL->fetch_param('category') !== FALSE AND ee()->TMPL->fetch_param('category') != '' )
		{
			$category	= str_replace( "C", "", ee()->TMPL->fetch_param('category') );
		}
		elseif ( preg_match("/\/C(\d+)/s", ee()->uri->uri_string, $match ) AND $dynamic === TRUE )
		{
			$category	= $match['1'];
		}
		
		/** --------------------------------------
		/**  Parse category indicator
		/** --------------------------------------*/
		
		// Text version of the category

		if (ee()->uri->uri_string != '' AND ee()->config->item("reserved_category_word") != '' AND in_array(ee()->config->item("reserved_category_word"), explode("/", ee()->uri->uri_string)) AND $dynamic)
		{
			if (preg_match("/(^|\/)".preg_quote(ee()->config->item("reserved_category_word"))."\/(.+?)($|\/)/i", ee()->uri->uri_string, $cmatch))
			{
				$cquery = ee()->db->query("SELECT cat_id 
											FROM exp_categories 
											WHERE site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."') AND 
											cat_url_title = '".ee()->db->escape_str($cmatch[2])."'");
										
				if ($cquery->num_rows() > 0)
				{
					$category = $cquery->row('cat_id');
				}
			}
		}
    	
		/**	----------------------------------------
		/**	Filter by category
		/**	----------------------------------------*/
		
		if ( isset( $category ) === TRUE )
		{
			$sql .= " LEFT JOIN exp_user_category_posts ucp ON ucp.member_id = m.member_id";
		}
    	
		/**	----------------------------------------
		/**	Continue sql
		/**	----------------------------------------*/
		
		$sql	.= " WHERE m.member_id != ''";
		
		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= " AND mg.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
		}
    	
		/**	----------------------------------------
		/**	Filter by category
		/**	----------------------------------------*/
		
		if ( isset( $category ) === TRUE )
		{
			$sql .= " AND ucp.cat_id = '".ee()->db->escape_str( $category )."'";
		}
		
		/**	----------------------------------------
		/**	Filter by member id
		/**	----------------------------------------*/
		
		if ( $member_ids = ee()->TMPL->fetch_param('member_id') )
		{
			$sql .= ee()->functions->sql_andor_string( $member_ids, 'm.member_id' );
		}
    	
		/**	----------------------------------------
		/**	Filter by group id
		/**	----------------------------------------*/
		
		if ( $group_id = ee()->TMPL->fetch_param('group_id') )
		{
			$sql .= ee()->functions->sql_andor_string( $group_id, 'm.group_id' );
		}
    	
		/**	----------------------------------------
		/**	Filter by alpha
		/**	----------------------------------------*/
		
		if ( ee()->TMPL->fetch_param('alpha') !== FALSE AND ee()->TMPL->fetch_param('alpha') != '' )
		{
			$alpha	= ee()->TMPL->fetch_param('alpha');
			
			$sql   .= " AND m.screen_name LIKE '".ee()->db->escape_like_str( $alpha )."%'";
		}
    	
		/**	----------------------------------------
		/**	Filter method
		/**	----------------------------------------*/
		
		$possible_filters = array('exact', 'any');
		
		$filter_method = (in_array(ee()->TMPL->fetch_param('filter_method'), $possible_filters)) ? ee()->TMPL->fetch_param('filter_method') : 'any';
    	
		/**	----------------------------------------
		/**	Filter by screen name
		/**	----------------------------------------*/
		
		if ( $letter = ee()->TMPL->fetch_param('screen_name') )
		{
			if ( $filter_method == 'exact' )
			{
				$sql .= " AND m.screen_name = '".ee()->db->escape_str($letter)."'";
			}
			elseif($filter_method == 'any')
			{
				$sql .= " AND m.screen_name LIKE '%".ee()->db->escape_like_str($letter)."%'";
			}
		}
    	
		/**	----------------------------------------
		/**	Filter by standard field
		/**	----------------------------------------*/
		
		if ( $standard_member_field = ee()->TMPL->fetch_param('standard_member_field') )
		{
			$standard_field	= explode( "|", $standard_member_field );
			
			if ( isset( $standard_field[0] ) AND isset( $standard_field[1] ) AND in_array( $standard_field[0], $this->standard))
			{
				if ( $standard_field[1] == 'IS_EMPTY' )
				{
					$sql .= " AND `m`.`".$standard_field[0]."` = ''";
				}
				elseif ( $standard_field[1] == 'IS_NOT_EMPTY' )
				{
					$sql .= " AND `m`.`".$standard_field[0]."` != ''";
				}
				elseif ( $filter_method == 'exact' )
				{
					$sql .= " AND `m`.`".$standard_field[0]."` = '".ee()->db->escape_str($standard_field[1])."'";
				}
				else
				{
					$sql .= " AND `m`.`".$standard_field[0]."` LIKE '%".ee()->db->escape_like_str($standard_field[1])."%'";
				}
			}
		}
    	
		/**	----------------------------------------
		/**	Filter by custom field
		/**	----------------------------------------*/
		
		if ( $custom_member_field = ee()->TMPL->fetch_param('custom_member_field') )
		{
			$this->_mfields();
			
			$custom_field	= explode( "|", $custom_member_field );
			
			if ( isset( $custom_field[0] ) AND isset( $custom_field[1] ) AND isset( $this->mfields[ $custom_field[0] ] ) )
			{
				if ($custom_field[1] == 'IS_EMPTY')
				{
					$sql .= " AND `md`.`m_field_id_".$this->mfields[ $custom_field[0] ]['id']."` = ''";
				}
				elseif($custom_field[1] == 'IS_NOT_EMPTY')
				{
					$sql .= " AND `md`.`m_field_id_".$this->mfields[ $custom_field[0] ]['id']."` != ''";
				}
				elseif ( $filter_method == 'exact' )
				{
					$sql .= " AND `md`.`m_field_id_".$this->mfields[ $custom_field[0] ]['id']."` = '".ee()->db->escape_str($custom_field[1])."'";
				}
				else
				{
					$sql .= " AND `md`.`m_field_id_".$this->mfields[ $custom_field[0] ]['id']."` LIKE '%".ee()->db->escape_like_str($custom_field[1])."%'";
				}
			}
		}
		
		/** --------------------------------------------
        /**  Magical Lookup Parameter Prefix
        /** --------------------------------------------*/
        
        $search_fields = array();
        
		if ( is_array(ee()->TMPL->tagparams))
		{
			foreach(ee()->TMPL->tagparams as $key => $value)
			{
				if (strncmp($key, 'search:', 7) == 0)
				{
					$this->_mfields();
					$search_fields[substr($key, strlen('search:'))] = $value;
				}
			}
			
			if (sizeof($search_fields) > 0)
			{	
				$sql .= $this->_search_fields($search_fields);
			}
			
		} // End is_array(ee()->TMPL->tagparams)
        
        /** ----------------------------------------
        /**	Order
        /** ----------------------------------------*/
        
        $sql	= $this->_order_sort( $sql );
        
        /** ----------------------------------------
        /**  Prep pagination
        /** ----------------------------------------*/
        
        $sql	= $this->_prep_pagination( $sql );
        
        /**	----------------------------------------
		/**	Empty
		/**	----------------------------------------*/
		
		if ( $sql == '' )
		{
			return $this->no_results('user');
		}
        
        /** ----------------------------------------
        /**	Run query
        /** ----------------------------------------*/
        
        $this->query	= ee()->db->query( $sql );
        
        if ($this->query->num_rows() == 0)
        {
        	return $this->no_results('user');
        }

		/** ----------------------------------------
		/**  Add Pagination
		/** ----------------------------------------*/

		if ($this->paginate == FALSE)
		{
			ee()->TMPL->tagdata = preg_replace("/".LD."if paginate".RD.".*?".LD."&#47;if".RD."/s", '', ee()->TMPL->tagdata);
		}
		else
		{
			ee()->TMPL->tagdata = preg_replace("/".LD."if paginate".RD."(.*?)".LD."&#47;if".RD."/s", "\\1", ee()->TMPL->tagdata);
			
			$this->paginate_data	= str_replace( LD."pagination_links".RD, $this->pager, $this->paginate_data);
			$this->paginate_data	= str_replace(LD.'current_page'.RD, $this->current_page, $this->paginate_data);
			$this->paginate_data	= str_replace(LD.'total_pages'.RD,	$this->total_pages, $this->paginate_data);
			$this->paginate_data	= str_replace(LD.'page_count'.RD,	$this->page_count, $this->paginate_data);
		}
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		$return	= $this->_users();
		
		if ( ee()->TMPL->fetch_param('paginate') == 'both' )
		{
			$return	= $this->paginate_data.$return.$this->paginate_data;
		}
		elseif ( ee()->TMPL->fetch_param('paginate') == 'top' )
		{
			$return	= $this->paginate_data.$return;
		}
		else
		{
			$return	= $return.$this->paginate_data;
		}
		
		return $return;
    }
    
    /* END users */
    
    
    // --------------------------------------------------------------------

	/**
	 *	Subprocessing Users
	 *
	 *	Method that is used both by users() and results() to output a list of users
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		string
	 */

	public function _users( $inject = array() )
    {    	
		/**	----------------------------------------
		/**	Set dates
		/**	----------------------------------------*/
		
		$dates	= array( 'join_date', 'last_bulletin_date', 'last_visit', 'last_activity', 
						 'last_entry_date', 'last_rating_date', 'last_comment_date', 
						 'last_forum_post_date', 'last_email_date' );
		
		/**	----------------------------------------
		/**	Set fixed vars
		/**	----------------------------------------*/
		
		$photo_url		= ee()->config->slash_item('photo_url');
		$avatar_url		= ee()->config->slash_item('avatar_url');
		$sig_img_url	= ee()->config->slash_item('sig_img_url');
		
		/**	----------------------------------------
		/**	Set classes
		/**	----------------------------------------*/
		
		ee()->load->library('typography');
		
		if (APP_VER >= 2.0)
		{
			ee()->typography->initialize();
		}
		
		ee()->typography->convert_curly = FALSE;
		
		/**	----------------------------------------
		/**	Inject
		/**	----------------------------------------*/
		
		//this doesn't appear to work
		foreach ( $this->query->result_array() as $id => $row )
		{
			if ( count( $inject ) > 0 AND isset( $inject[$row['member_id']] ) )
			{
				$this->query->result[$id]	= array_merge( $row, $inject[$row['member_id']] );
			}
		}
		
		/** ------------------------------------------
		/** Sort by principal if needed
		/** ------------------------------------------*/

		if ( ee()->TMPL->fetch_param('orderby') !== FALSE AND 
			 in_array(ee()->TMPL->fetch_param('orderby') ,array("primary", "principal")) )
		{
			usort($this->query->result, array(&$this, "principal_sort"));
			
			if ( ee()->TMPL->fetch_param('sort') !== FALSE AND ee()->TMPL->fetch_param('sort') == "desc" )
			{
				$this->query->result = array_reverse($this->query->result);
			}
		}
		
		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/
		
		$return			= '';
		$position		= 0;
		$total_results	= $this->query->num_rows();
		
		ee()->load->library('typography');
		
		if (APP_VER >= 2.0)
		{
			ee()->typography->initialize();
		}
		
		foreach ( $this->query->result_array() as $count => $row )
		{
			$position++;

			if ( count( $inject ) > 0 AND isset( $inject[$row['member_id']] ) )
			{
				$row = array_merge( $row, $inject[$row['member_id']] );
			}

			$row['count']			= $count+1;
            $row['total_results']	= $total_results;
			
			/**	----------------------------------------
			/**	Hardcode some row vars
			/**	----------------------------------------*/
			
			$row['photo_url']	= $photo_url;
			$row['avatar_url']	= $avatar_url;
			$row['sig_img_url']	= $sig_img_url;
			
			$row['total_combined_posts'] = $row['total_forum_topics'] + $row['total_forum_posts'] + 
											$row['total_entries'] + $row['total_comments'];
			
			/**	----------------------------------------
			/**	Conditionals
			/**	----------------------------------------*/
			
			$tagdata	= ee()->TMPL->tagdata;
			
			$cond		= $row;
			
			$tagdata	= ee()->functions->prep_conditionals( $tagdata, $cond );
			
			/**	----------------------------------------
			/**	Set member id for categories
			/**	----------------------------------------*/
			
			$this->member_id		= $row['member_id'];
			$this->assigned_cats	= array();
    	
			/**	----------------------------------------
			/**	Handle categories
			/**	----------------------------------------*/
			
			$tagdata	= $this->_categories( $tagdata, 'yes' );

			/**	----------------------------------------
			/**	Parse Switch
			/**	----------------------------------------*/
			
			if ( preg_match( "/".LD."(switch\s*=.+?)".RD."/is", $tagdata, $match ) > 0 )
			{
				$sparam = ee()->functions->assign_parameters($match['1']);
				
				$sw = '';
				
				if ( isset( $sparam['switch'] ) !== FALSE )
				{
					$sopt = explode("|", $sparam['switch']);

					$sw = $sopt[($position + count($sopt) -1) % count($sopt)];
				}
				
				$tagdata = ee()->TMPL->swap_var_single($match['1'], $sw, $tagdata);
			}
		
			/**	----------------------------------------
			/**	Parse dates
			/**	----------------------------------------*/
			
			foreach ($dates as $val)
			{					
				if (preg_match_all("/".LD.$val."\s+format=([\"'])([^\\1]*?)\\1".RD."/s", $tagdata, $matches))
				{
					for($i=0, $s=sizeof($matches[2]); $i < $s; ++$i)
					{
						$str	= $matches[2][$i];
						
						$codes	= ee()->localize->fetch_date_params( $matches[2][$i] );
						
						foreach ( $codes as $code )
						{
							$str	= str_replace( $code, ee()->localize->convert_timestamp( $code, $row[$val], TRUE ), $str );
						}
						
						$tagdata	= str_replace( $matches[0][$i], $str, $tagdata );
					}
				}
			}
			
			/** --------------------------------------------
			/**  Bio Needs Special Typography Parsing
			/** --------------------------------------------*/
			
			if (stristr($tagdata, LD.'bio'.RD))
			{
				$row['bio'] = ee()->typography->parse_type(	
					$row['bio'], 
					array(
						'text_format'   => 'xhtml',
						'html_format'   => 'safe',
						'auto_links'    => 'y',
						'allow_img_url' => 'n'
					)
				);
			}
			
			//signature also needs love
			if (stristr($tagdata, LD.'signature'.RD))
			{
				$row['signature'] = ee()->typography->parse_type(	
					$row['signature'], 
					array(
						'text_format'   => 'xhtml',
						'html_format'   => 'safe',
						'auto_links'    => 'y',
						'allow_img_url' => (ee()->config->item('sig_allow_img_hotlink') == 'y') ? 'y' : 'n'
					)
				);
			}
			
			/**	----------------------------------------
			/**	Parse remaining standards and injected vals
			/**	----------------------------------------*/
			
			foreach ( $row as $key => $val )
			{
				if ( in_array( $key, $dates ) )
				{
					$tagdata	= str_replace( LD.$key.RD, $val, $tagdata );
				}
				else
				{
					$tagdata	= str_replace( LD.$key.RD, $val, $tagdata );
				}
			}
    	
			/**	----------------------------------------
			/**	Parse custom variables
			/**	----------------------------------------*/
			
			foreach ( $this->_mfields() as $key => $val )
			{
				/**	----------------------------------------
				/**	Conditionals
				/**	----------------------------------------*/
				
				$cond[ $val['name'] ]	= $row['m_field_id_'.$val['id']];
				$tagdata				= ee()->functions->prep_conditionals( $tagdata, $cond );
				
				/**	----------------------------------------
				/**	Parse select
				/**	----------------------------------------*/
				
				foreach ( ee()->TMPL->var_pair as $k => $v )
				{
					if ( $k == "select_".$key )
					{
						$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $k );
						
						$tagdata	= preg_replace( 
							"/".LD.preg_quote($k, '/').RD."(.*?)".LD.preg_quote(T_SLASH, '/').preg_quote($k, '/').RD."/s", 
							str_replace('$', '\$', $this->_parse_select( $key, $row, $data )), 
							$tagdata 
						);
					}
				}
				
				/**	----------------------------------------
				/**	Parse abbreviated
				/**	----------------------------------------*/
				
				$tagdata = ee()->TMPL->swap_var_single(
					'abbr_'.$key, 
					ee()->typography->parse_type( 
						substr($row['m_field_id_'.$val['id']], 0, 1 ).'.', 
						array(
							'text_format'   => $this->mfields[$key]['format'],
							'html_format'   => 'safe',
							'auto_links'    => 'n',
							'allow_img_url' => 'n'
					  	)
					), 
					$tagdata
				);
				
				/**	----------------------------------------
				/**	Parse singles
				/**	----------------------------------------*/
				
				$tagdata = ee()->TMPL->swap_var_single(
					$key, 
					ee()->typography->parse_type( 
						$row['m_field_id_'.$val['id']], 
						array(
							'text_format'   => $this->mfields[$key]['format'],
							'html_format'   => 'safe',
							'auto_links'    => 'n',
							'allow_img_url' => 'n'
						)
					 ), 
					$tagdata
				);
			}
			
			$return	.= $tagdata;
		}
    	
		/**	----------------------------------------
		/**	Backspace
		/**	----------------------------------------*/
		
		$backspace = 0;
		
		if ( isset(ee()->TMPL) && is_object(ee()->TMPL) && ctype_digit( ee()->TMPL->fetch_param('backspace') ) )
		{
			$backspace = ee()->TMPL->fetch_param('backspace');
		}
		
		$return	= ( $backspace > 0 ) ? substr( $return, 0, - $backspace ): $return;
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		return $return;
    }
    
    /* END sub users */
	
	// --------------------------------------------------------------------

	/**
	 *	Sorts a Query Result by A Principal Value
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		string
	 *	@return		integer
	 */
	
	public function principal_sort($a, $b)
	{
		return strnatcmp($a['principal'], $b['principal']);
	}
	/* END principal sort */
	
	// --------------------------------------------------------------------

	/**
	 *	Authors
	 *
	 *	Returns the info of the most recently cached array depending on cache type.
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function authors()
    {		
		/**	----------------------------------------
		/**	Is this feature enabled?
		/**	----------------------------------------*/
		
		if ( ee()->db->table_exists( 'exp_user_authors' ) === FALSE )
		{
			return $this->no_results('user');
		}
		
		/**	----------------------------------------
		/**	Grab entry id
		/**	----------------------------------------*/
		
		if ( $this->_entry_id() === FALSE )
		{
			return $this->no_results('user');
		}
		
		/**	----------------------------------------
		/**	Grab authors
		/**	----------------------------------------*/
		
		$sql	= "SELECT ua.author_id, ua.principal
				   FROM exp_user_authors ua 
				   WHERE ua.entry_id = '".ee()->db->escape_str( $this->entry_id )."'";
		
		$query	= ee()->db->query( $sql );
		
		/**	----------------------------------------
		/**	Results?
		/**	----------------------------------------*/
		
		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}
		
		/**	----------------------------------------
		/**	Prepare injection array
		/**	----------------------------------------*/
		
		$inject	= array();
		$ids	= array();
		
		foreach ( $query->result_array() as $row )
		{
			$inject[$row['author_id']]['principal']	= $row['principal'];
			$inject[$row['author_id']]['primary']	= $row['principal'];
			$ids[]	= ee()->db->escape_str( $row['author_id'] );
		}

		//why do you hate me kelsey?
		if (is_object(ee()->TMPL) AND 
			isset(ee()->TMPL->tagparams['orderby']) AND 
			ee()->TMPL->tagparams['orderby'] === 'primary')
		{
			ee()->TMPL->tagparams['orderby'] = 'principal';
		}
		
		/**	----------------------------------------
		/**	Run full query
		/**	----------------------------------------*/
		
		$sql	 = "SELECT DISTINCT m.*, md.*";
		
		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= ", mg.group_title, mg.group_description ";
		}
		
		$sql 	.= " FROM exp_members m 
				     LEFT JOIN exp_member_data md ON md.member_id = m.member_id
				     LEFT JOIN exp_user_authors ua ON ua.author_id = m.member_id";
				     
		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= " LEFT JOIN exp_member_groups AS mg ON mg.group_id = m.group_id";
		}
    	
		
		$sql	.= " WHERE ua.author_id IN ('".implode( "','", $ids )."')
					 AND ua.entry_id = '".ee()->db->escape_str( $this->entry_id )."'";
		
		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= " AND mg.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
		}
        
        /** ----------------------------------------
        /**	Order
        /** ----------------------------------------*/

        $sql	= $this->_order_sort( $sql, array('principal' => 'ua'));
		
		$this->query	= ee()->db->query( $sql );
		
		/**	----------------------------------------
		/**	Results?
		/**	----------------------------------------*/
		
		if ( $this->query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		return $this->_users( $inject );
    }
    
    /* END authors */
	
	
	// --------------------------------------------------------------------

	/**
	 *	Entries
	 *
	 *	List of Entries for an Author
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function entries()
	{	
		/**	----------------------------------------
		/**	Is this feature enabled?
		/**	----------------------------------------*/
		
		if ( ee()->db->table_exists( 'exp_user_authors' ) === FALSE )
		{
			return $this->no_results('user');
		}
		
		/**	----------------------------------------
		/**	Grab member id
		/**	----------------------------------------*/
		
		if ( $this->_member_id() === FALSE )
		{
			return $this->no_results('user');
		}
		
		/**	----------------------------------------
		/**	Fetch entries
		/**	----------------------------------------*/
		
		$query	= ee()->db->query("SELECT DISTINCT entry_id FROM exp_user_authors 
									    WHERE author_id != 0
									    ".ee()->functions->sql_andor_string( $this->member_id, 'author_id'));
		
		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}
		
		/**	----------------------------------------
		/**	Prep
		/**	----------------------------------------*/
		
		$this->entry_id	= '';
		
		foreach ( $query->result_array() as $row )
		{
			$this->entry_id	.= $row['entry_id'].'|';
		}

		/**	----------------------------------------
		/**	Parse entries
		/**	----------------------------------------*/

		if ( ! $tagdata = $this->_entries( array('dynamic' => 'off') ) )
		{
			return $this->no_results('user');
		}
        
        return $tagdata;
	}
	
	/* END entries */
	
	
	// --------------------------------------------------------------------

	/**
	 *	List of Entires for an Author, Sub-Processing for entries() method
	 *
	 *	@access		private
	 *	@param		array
	 *	@return		string
	 */
		
	private function _entries ( $params = array() )
	{	
		
		/**	----------------------------------------
		/**	Execute?
		/**	----------------------------------------*/
		
		if ( $this->entry_id == '' ) return FALSE;
		
		/**	----------------------------------------
		/**	Invoke Channel/Weblog class
		/**	----------------------------------------*/      
        
        if (APP_VER < 2.0)
        {
			if ( ! class_exists('Weblog'))
	        {
	        	require PATH_MOD.'weblog/mod.weblog'.EXT;
	        }
	
        	$channel = new Weblog();
		}
		else
		{
			if (! class_exists('Channel'))
	        {
	        	require PATH_MOD.'channel/mod.channel.php';
	        }
	        			
			$channel = new Channel();
		}
		
		/**	----------------------------------------
		/**	Pass params
		/**	----------------------------------------*/
		
		ee()->TMPL->tagparams['entry_id']	= $this->entry_id;
        
        ee()->TMPL->tagparams['inclusive']	= '';
        
        if ( isset( $params['dynamic'] ) AND $params['dynamic'] == "off" )
        {
        	if (APP_VER < 2.0)
        	{
				ee()->TMPL->tagparams['dynamic'] = 'off';
			}
			else
			{
				ee()->TMPL->tagparams['dynamic'] = 'no';
			}
        }
		
		/**	----------------------------------------
		/**	Pre-process related data
		/**	----------------------------------------*/
		
		ee()->TMPL->tagdata	= ee()->TMPL->assign_relationship_data( ee()->TMPL->tagdata );
		
		ee()->TMPL->var_single	= array_merge( ee()->TMPL->var_single, ee()->TMPL->related_markers );
		
		/**	----------------------------------------
		/**	Execute needed methods
		/**	----------------------------------------*/
        
        if (APP_VER < 2.0)
        {
        	$channel->fetch_custom_weblog_fields();
        }
        else
        {
        	$channel->fetch_custom_channel_fields();
        }
        
        $channel->fetch_custom_member_fields();
        
		$channel->fetch_pagination_data();
		
		/**	----------------------------------------
		/**	Grab entry data
		/**	----------------------------------------*/
        
        $channel->create_pagination();
		
        $channel->build_sql_query();
        
        $channel->query = ee()->db->query($channel->sql);

		if (APP_VER < 2.0)
		{
			$channel->query->result	= $channel->query->result_array();
		}
        
        if ( ! isset( $channel->query ) OR 
			 $channel->query->num_rows() == 0 )
        {
            return ee()->TMPL->no_results();
        }   
        
        if (APP_VER < 2.0)
        {
        	if ( ! class_exists('Typography'))
			{
				require PATH_CORE.'core.typography'.EXT;
			}
					
			$channel->TYPE = new Typography;
			$channel->TYPE->convert_curly = FALSE;
        }
        else
        {
			ee()->load->library('typography');
			ee()->typography->initialize();
			ee()->typography->convert_curly = FALSE;
		}
        
        $channel->fetch_categories();
		
		/**	----------------------------------------
		/**	Parse and return entry data
		/**	----------------------------------------*/
		
		if (APP_VER < 2.0)
		{
        	$channel->parse_weblog_entries();
        }
        else
        {
        	$channel->parse_channel_entries();
        }
        
		$channel->add_pagination_data();
		
		/**	----------------------------------------
		/**	Count tag
		/**	----------------------------------------*/
		
		if (count(ee()->TMPL->related_data) > 0 AND count($channel->related_entries) > 0)
		{
			$channel->parse_related_entries();
		}
		
		if (count(ee()->TMPL->reverse_related_data) > 0 AND count($channel->reverse_related_entries) > 0)
		{
			$channel->parse_reverse_related_entries();
		}
		
		// ----------------------------------------
		//  Handle problem with pagination segments
		//	in the url
		// ----------------------------------------

		if ( preg_match("#(/P\d+)#", ee()->uri->uri_string, $match) )
		{
			$channel->return_data	= str_replace( $match['1'], "", $channel->return_data );
		}
		elseif ( preg_match("#(P\d+)#", ee()->uri->uri_string, $match) )
		{
			$channel->return_data	= str_replace( $match['1'], "", $channel->return_data );
		}
		
        $tagdata = $channel->return_data;
        
        return $tagdata;
	}
	
	/* END _entries() */
    
    // --------------------------------------------------------------------

	/**
	 *	Edit Profile Form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function edit()
    {
    	$this->form_data = array();
    	
		/**	----------------------------------------
		/**	Member id only?
		/**	----------------------------------------*/
		
		if ( ! $this->_member_id() )
		{
    		return $this->no_results('user');
		}
    	
		/**	----------------------------------------
		/**	If an admin is editing a member,
		/**	make sure they are allowed
		/**	----------------------------------------*/
		
		if ( ee()->session->userdata['member_id'] == $this->member_id OR ee()->session->userdata['group_id'] == 1 OR 
		   ( ee()->TMPL->fetch_param('group_id') !== FALSE && in_array( ee()->session->userdata['group_id'], preg_split( "/,|\|/", ee()->TMPL->fetch_param('group_id') ) ) ) )
		{
			// Silly Mitchell!  Empty conditionals are for kids!
		}
		else
		{
    		return $this->no_results('user');
		}
    	
		/**	----------------------------------------
		/**	Grab member data
		/**	----------------------------------------*/
		
		$sql	= "SELECT email, group_id, member_id, screen_name, username";
		
		$arr	= array_merge( $this->standard, $this->check_boxes, $this->photo, $this->avatar, $this->signature );
		
		foreach ( $arr as $a )
		{
			$sql	.= ",".$a;
		}
		
		$sql	.= " FROM exp_members WHERE member_id = '".ee()->db->escape_str( $this->member_id )."'";
		
		$query	= ee()->db->query( $sql );
		
		if ( $query->num_rows() == 0 )
		{
    		return $this->no_results('user');
		}
		
		$query_row = $query->row_array();
    	
		/**	----------------------------------------
		/**	Userdata
		/**	----------------------------------------*/
		
		$tagdata	= ee()->TMPL->tagdata;
    	
		/**	----------------------------------------
		/**	Sniff for checkboxes
		/**	----------------------------------------*/
		
		$checks			= '';
		$custom_checks	= '';
		
		if ( preg_match_all( "/name=['|\"]?(\w+)['|\"]?/", $tagdata, $match ) )
		{
			$this->_mfields();
			
			foreach ( $match['1'] as $m )
			{
				if ( in_array( $m, $this->check_boxes ) )
				{
					$checks	.= $m."|";
				}
				
				if ( isset( $this->mfields[ $m ] ) AND $this->mfields[ $m ]['type'] == 'select' )
				{
					$custom_checks	.= $m."|";
				}
			}
		}
    	
		/**	----------------------------------------
		/**	Sniff for fields of type 'file'
		/**	----------------------------------------*/
		
		if ( preg_match( "/type=['|\"]?file['|\"]?/", $tagdata, $match ) )
		{
			$this->multipart	= TRUE;
		}
		
		/**	----------------------------------------
		/**	Add additional values to $query->row
		/**	----------------------------------------*/
		
		$query_row['photo_url']		= ee()->config->slash_item('photo_url');
		$query_row['avatar_url']	= ee()->config->slash_item('avatar_url');
		$query_row['sig_img_url']	= ee()->config->slash_item('sig_img_url');
    	
		/**	----------------------------------------
		/**	Handle categories
		/**	----------------------------------------*/
		
		$tagdata	= $this->_categories( $tagdata );
    	
		/**	----------------------------------------
		/**	Conditional Prep
		/**	----------------------------------------*/
		
		$cond		= $query_row;
		
		$custom	= ee()->db->query( "SELECT * FROM exp_member_data WHERE member_id = '".ee()->db->escape_str( $this->member_id )."'" );
		
		$custom_row = $custom->row_array();
		
		foreach ( $this->_mfields() as $key => $val )
		{
			$cond[$key] = (isset($custom_row['m_field_id_'.$val['id']])) ? $custom_row['m_field_id_'.$val['id']] : '';
		}
		
		/** --------------------------------------------
        /**  Conditionals
        /** --------------------------------------------*/
		
		$tagdata	= ee()->functions->prep_conditionals( $tagdata, $cond );
    	
		/**	----------------------------------------
		/**	Parse var pairs
		/**	----------------------------------------*/
		
		foreach ( ee()->TMPL->var_pair as $key => $val )
		{
			/** --------------------------------------------
			/**  Member Groups Select List
			/** --------------------------------------------*/
			
			if ($key == 'select_member_groups')
			{
				if (ee()->TMPL->fetch_param('allowed_groups') !== FALSE)
				{
					$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $key );
				
					$tagdata	= preg_replace( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", 
												str_replace('$', '\$', $this->_parse_select_member_groups( $data, $query_row['group_id'])), 
												$tagdata );
				}
				else
				{
					$tagdata	= preg_replace( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", '', $tagdata);
				}
			}
			
			/**	----------------------------------------
			/**	Timezones
			/**	----------------------------------------*/
			
			if ( $key == 'timezones' )
			{
				preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", $tagdata, $match );
				$r	= '';
				
				foreach ( ee()->localize->zones() as $key => $val )
				{
					$out		= $match['1'];
					
					$checked	= ( isset( $query_row['timezone'] ) AND $query_row['timezone'] == $key ) ? 'checked="checked"': '';
					
					$selected	= ( isset( $query_row['timezone'] ) AND $query_row['timezone'] == $key ) ? 'selected="selected"': '';
					
					$out		= str_replace( LD."zone_name".RD, $key, $out );
					$out		= str_replace( LD."zone_label".RD, ee()->lang->line( $key ), $out );
					$out		= str_replace( LD."checked".RD, $checked, $out );
					$out		= str_replace( LD."selected".RD, $selected, $out );
					
					$r	.= $out;
				}
				
				$tagdata	= str_replace( $match[0], $r, $tagdata );
			}
			
			/**	----------------------------------------
			/**	Time format
			/**	----------------------------------------*/
			
			if ( $key == 'time_formats' )
			{
				preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", $tagdata, $match );
				$r	= '';
				
				foreach ( array( 'us', 'eu' ) as $key )
				{
					$out		= $match['1'];
					
					$checked	= ( isset($query_row['time_format'] ) AND $query_row['time_format'] == $key ) ? 'checked="checked"': '';
					
					$selected	= ( isset($query_row['time_format'] ) AND $query_row['time_format'] == $key ) ? 'selected="selected"': '';
					
					$out		= str_replace( LD."time_format_name".RD, $key, $out );
					$out		= str_replace( LD."time_format_label".RD, ee()->lang->line( $key ), $out );
					$out		= str_replace( LD."checked".RD, $checked, $out );
					$out		= str_replace( LD."selected".RD, $selected, $out );
					
					$r	.= $out;
				}
				
				$tagdata	= str_replace( $match[0], $r, $tagdata );
			}
			
			/**	----------------------------------------
			/**	Languages
			/**	----------------------------------------*/
			
			if ( $key == 'languages' )
			{
				$dirs = array();
					
				if (is_dir($this->lang_dir) AND $fp = @opendir($this->lang_dir))
				{
					while (FALSE !== ($file = readdir($fp)))
					{
						if (is_dir($this->lang_dir.$file) && substr($file, 0, 1) != ".")
						{
							$dirs[] = $file;
						}
					}
					closedir($fp);
				}
		
				sort($dirs);
		
				preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", $tagdata, $match );
				$r	= '';
				
				foreach ( $dirs as $key )
				{
					$out		= $match['1'];
					
					$checked	= ( isset($query_row['language'] ) AND $query_row['language'] == $key ) ? 'checked="checked"': '';
					
					$selected	= ( isset($query_row['language'] ) AND $query_row['language'] == $key ) ? 'selected="selected"': '';
					
					$out		= str_replace( LD."language_name".RD, $key, $out );
					$out		= str_replace( LD."language_label".RD, ucfirst( $key ), $out );
					$out		= str_replace( LD."checked".RD, $checked, $out );
					$out		= str_replace( LD."selected".RD, $selected, $out );
					
					$r	.= $out;
				}
				
				$tagdata	= str_replace( $match[0], $r, $tagdata );
			}
		}
    	
		/**	----------------------------------------
		/**	Parse primary variables
		/**	----------------------------------------*/
		
		foreach ( $query_row AS $key => $val )
		{
			$tagdata = ee()->TMPL->swap_var_single( $key, $val, $tagdata );
		}
    	
		/**	----------------------------------------
		/**	Parse custom variables
		/**	----------------------------------------*/
		
		if ( $custom->num_rows() > 0 )
		{
			foreach ( $this->_mfields() as $key => $val )
			{
				/**	----------------------------------------
				/**	Parse select
				/**	----------------------------------------*/
				
				foreach ( ee()->TMPL->var_pair as $k => $v )
				{
					if ( $k == "select_".$key )
					{
						$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $k );
						
						$tagdata	= preg_replace( "/".LD.$k.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$k.RD."/s", 
													str_replace('$', '\$', $this->_parse_select( $key, $custom_row, $data )), 
													$tagdata );
					}
				}
				
				/**	----------------------------------------
				/**	Parse singles
				/**	----------------------------------------*/
				
				$tagdata	= ee()->TMPL->swap_var_single( $key, $custom_row['m_field_id_'.$val['id']], $tagdata );
				
				/**	----------------------------------------
				/**	Parse Language
				/**	----------------------------------------*/
				
				$tagdata	= ee()->TMPL->swap_var_single( 'lang:'.$key.':label', $val['label'], $tagdata );
				$tagdata	= ee()->TMPL->swap_var_single( 'lang:'.$key.':description', $val['description'], $tagdata );
			}
		}
    	
		/**	----------------------------------------
		/**	Prep data
		/**	----------------------------------------*/
		
		$this->form_data['tagdata']					= $tagdata;
		
		$this->form_data['ACT']						= ee()->functions->fetch_action_id('User', 'edit_profile');
		
        $this->form_data['RET']						= (isset($_POST['RET'])) ? $_POST['RET'] : ee()->functions->fetch_current_uri();
		
		if ( ee()->TMPL->fetch_param('form_name') !== FALSE && ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
		}
		
		$this->form_data['id']						= ( ee()->TMPL->fetch_param('form_id') !== FALSE ) ? ee()->TMPL->fetch_param('form_id'): 'member_form';
		
		$this->form_data['return']					= ( ee()->TMPL->fetch_param('return') !== FALSE ) ? ee()->TMPL->fetch_param('return'): '';
		
		$this->params['member_id']				= $this->member_id;
		
		$this->params['checks']					= $checks;
		
		$this->params['custom_checks']			= $custom_checks;
		
		$this->params['username']				= $query_row['username'];
		
		$this->params['username_override']		= ( ee()->TMPL->fetch_param('username_override') ) ? ee()->TMPL->fetch_param('username_override'): '';
		
		$this->params['exclude_username']		= ( ee()->TMPL->fetch_param('exclude_username') ) ? ee()->TMPL->fetch_param('exclude_username'): '';
		
		$this->params['required']				= ( ee()->TMPL->fetch_param('required') ) ? ee()->TMPL->fetch_param('required'): '';
		
		$this->params['group_id']				= ( ee()->TMPL->fetch_param('group_id') ) ? ee()->TMPL->fetch_param('group_id'): '';
		
		$this->params['password_required']		= ( ee()->TMPL->fetch_param('password_required') ) ? ee()->TMPL->fetch_param('password_required'): '';
		
		$this->params['secure_action']			= ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';
		
		$this->params['screen_name_password_required'] = ( ee()->TMPL->fetch_param('screen_name_password_required') !== FALSE) ? ee()->TMPL->fetch_param('screen_name_password_required') : 'y';
		
		if (ee()->TMPL->fetch_param('allowed_groups') !== FALSE && ee()->TMPL->fetch_param('allowed_groups') != '')
		{
			$this->params['allowed_groups']		=  ee()->TMPL->fetch_param('allowed_groups');
    	}
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
        
		return $this->_form();
    }
    /* END edit */
    
    
    // --------------------------------------------------------------------

	/**
	 *	Edit Profile Processing Method
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function edit_profile()
    {
    	$this->insert_data = array();
        
		/**	----------------------------------------
		/**	Logged in?
		/**	----------------------------------------*/
        
        if ( ee()->session->userdata['member_id'] == 0 )
        {
        	return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
        }
        
		/**	----------------------------------------
		/**	Missing member_id?
		/**	----------------------------------------*/
		
		if ( ! $member_id = $this->_param('member_id') )
		{
        	return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
		}
        
		/**	----------------------------------------
		/**	We'll use the $admin variable to handle
		/**	occasions where one member is allowed
		/**	to edit another member's profile.
		/**	----------------------------------------*/
		
		if ( ee()->session->userdata['group_id'] == 1 OR ( $this->_param('group_id') AND in_array( ee()->session->userdata['group_id'], preg_split( "/,|\|/", $this->_param('group_id') ) ) ) )
		{
			$admin	= TRUE;
		}
		else
		{
			$admin	= FALSE;
		}
    	
		/**	----------------------------------------
		/**	If an admin is editing a member,
		/**	make sure they are allowed
		/**	----------------------------------------*/
		
		if ( ee()->session->userdata['member_id'] != $member_id && $admin === FALSE )
		{
        	return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
		}
		
		/** --------------------------------------------
        /**  Prepare for Update Email of Peace and Love
        /** --------------------------------------------*/
        
        $update_admin_email = FALSE;
        
        $wquery = ee()->db->query("SELECT preference_value, preference_name FROM exp_user_preferences 
									WHERE preference_name IN ('member_update_admin_notification_template', 'member_update_admin_notification_emails')");
			
		if ($wquery->num_rows() >= 2)
		{
			$update_admin_email = TRUE;
			
			foreach($wquery->result_array() as $row)
			{
				${$row['preference_name']} = stripslashes($row['preference_value']);
			}
			
			$oquery   = ee()->db->query("SELECT m.*, md.* 
									FROM exp_members AS m, exp_member_data AS md
									WHERE md.member_id = m.member_id
									AND m.member_id = '".ee()->db->escape_str($member_id)."'");
									
			$old_data = ($oquery->num_rows() == 0) ? array() : $oquery->row_array();
		}
		
		/**	----------------------------------------
        /**	Clean the post
        /**	----------------------------------------*/
        
		//passwords should not be xss cleaned because they get hashed
		$temp_pass = isset($_POST['password']) ? $_POST['password'] : '';

        $_POST	= ee()->security->xss_clean( $_POST );

		if ($temp_pass != '')
		{
			$_POST['password'] = $temp_pass;
		}
        
        /**	----------------------------------------
        /**	 Remove an Image?
        /**	----------------------------------------*/
        
        foreach(array('avatar', 'signature', 'photo') AS $type)
        {
        	if (isset($_POST['remove_'.$type]))
        	{	
        		$this->_remove_image($type, $member_id, $admin);
        		return;
        	}
        }
        
        /**	----------------------------------------
        /**	Check screen name override
        /**	----------------------------------------*/
        
        $this->_screen_name_override( $member_id );
        
        /** --------------------------------------------
        /**  Email as Username Preference
        /** --------------------------------------------*/
        
        $wquery = ee()->db->query("SELECT preference_value FROM exp_user_preferences WHERE preference_name = 'email_is_username'");
				
		$this->preferences['email_is_username'] = ($wquery->num_rows() == 0) ? 'n' : $wquery->row('preference_value');
        
        /**	----------------------------------------
        /**	Check email is username
        /**	----------------------------------------*/
        
        $this->_email_is_username( $member_id );
        
        /**	----------------------------------------
		/**	Is username banned?
		/**	----------------------------------------*/
	
		if ( ee()->input->post('username') AND ee()->session->ban_check('username', ee()->input->post('username')) )
		{
			return $this->_output_error('general', array(ee()->lang->line('prohibited_username')));
		}
		
		if ($this->_param('exclude_username') != '' && in_array(ee()->input->post('username'), explode('|', $this->_param('exclude_username'))))
		{
			return $this->_output_error('general', array(ee()->lang->line('prohibited_username')));
		}

		/**	----------------------------------------
		/**	If the screen name field is absent,
		/**	we'll stick to the current screen name
		/**	----------------------------------------*/
		
		if (ee()->input->get_post('screen_name') === FALSE && $member_id == ee()->session->userdata['member_id'])
		{
			$_POST['screen_name']	= ee()->session->userdata['screen_name'];
		}
		
		/**	----------------------------------------
		/**	If the screen name field is empty, we'll assign it from the username field.
		/**	----------------------------------------*/
		
		elseif ( trim(ee()->input->get_post('screen_name')) == '' )
		{
			$_POST['screen_name']	= ee()->input->post('username');
		}

		/**	----------------------------------------
		/**	Prepare validation array.
		/**	----------------------------------------*/
		
		$query = ee()->db->query("SELECT * FROM exp_members WHERE member_id = '".ee()->db->escape_str( $member_id )."'");
		
		if ( $query->num_rows() == 0 )
		{
        	return $this->_output_error('general', array(ee()->lang->line('cant_find_member')));
		}
		
		$validate	= array(
							'member_id'			=> $member_id,
							'val_type'			=> 'update', // new or update
							'fetch_lang' 		=> FALSE, 
							'require_cpw' 		=> FALSE,
							'enable_log'		=> FALSE,
							'cur_username'		=> $query->row('username'),
							'screen_name'		=> stripslashes(ee()->input->post('screen_name')),
							'cur_screen_name'	=> $query->row('screen_name'),
							'cur_email'			=> $query->row('email')
							);
							
		if ( ee()->input->post('username') )
		{
			$validate['username']	= ee()->input->post('username');
		}
							
		if ( ee()->input->get_post('email') )
		{
			$validate['email']	= ee()->input->get_post('email');
		}
							
		if ( ee()->input->get_post('password') )
		{
			$validate['password']	= ee()->input->get_post('password');
		}
							
		if ( ee()->input->get_post('password_confirm') )
		{
			$validate['password_confirm']	= ee()->input->get_post('password_confirm');
		}
							
		if ( ee()->input->get_post('current_password') )
		{
			$validate['cur_password']	= ee()->input->get_post('current_password');
		}
		
		$old_username		= $query->row('username');
		
		/** --------------------------------------------
        /**  Brute Force Password Attack - Denied!
        /** --------------------------------------------*/

		if (ee()->session->check_password_lockout() === TRUE)
		{		
			$line = str_replace("%x", ee()->config->item('password_lockout_interval'), ee()->lang->line('password_lockout_in_effect'));		
			return $this->_output_error('submission', $line);		
		}
		        
		/**	----------------------------------------
		/**	Are we changing a password?
		/**	----------------------------------------*/
		
		$changing_password	= FALSE;
      
        if (ee()->input->get_post('password') !== FALSE && ee()->input->get_post('password') != '' && 
        	ee()->input->get_post('current_password') != ee()->input->get_post('password') && 
        	$member_id == ee()->session->userdata['member_id'])
		{	
			$changing_password	= TRUE;
		}
		
		/**	----------------------------------------
		/**	 Password Required for Form Submission?!
		/**	----------------------------------------*/
		
		if ($admin === FALSE && ($this->check_yes($this->_param('password_required')) OR $this->_param('password_required') == 'yes'))
		{
			if (ee()->session->check_password_lockout() === TRUE)
			{		
				$line = str_replace("%x", ee()->config->item('password_lockout_interval'), ee()->lang->line('password_lockout_in_effect'));		
				return $this->_output_error('submission', $line);		
			}
			
			if (ee()->input->get_post('current_password') === FALSE OR
				ee()->input->get_post('current_password') == '' OR 
				ee()->functions->hash(stripslashes($_POST['current_password'])) != $query->row('password'))
			{
				return $this->_output_error( 'general', array( ee()->lang->line( 'invalid_password' ) ) );
			}
		}
		
		/** --------------------------------------------
        /**  Password Check for when Username, Screen Name, Email, and Password are changed
        /** --------------------------------------------*/ 
        
        if ($admin === FALSE)
        {
        	$check = array('username', 'email');
        	
			//allows the override of screen_name password protection
			if (! $this->check_no($this->_param('screen_name_password_required')))
			{
				$check[] = 'screen_name';
			}

        	if (ee()->input->get_post('password') != '')
        	{
        		$check[] = 'password';
        	}
        
        	foreach($check as $val)
			{
				if (ee()->input->get_post($val) !== FALSE AND ee()->input->get_post($val) != $query->row($val))
				{
					if (ee()->input->get_post('current_password') === FALSE OR ee()->input->get_post('current_password') == '')
					{
						return $this->_output_error( 'general', array( ee()->lang->line( 'invalid_password' ) ) );
					}
					
					if (ee()->functions->hash(stripslashes($_POST['current_password'])) != $query->row('password'))
					{
						ee()->session->save_password_lockout();
						
						if ($check == 'email')
						{
							return $this->_output_error( 'general', array( ee()->lang->line( 'current_password_required_email' ) ) );
						}
						else
						{
							return $this->_output_error( 'general', array( ee()->lang->line( 'invalid_password' ) ) );
						}
					}
				}
			}
		}

		/**	----------------------------------------
        /**	If we are changing the language
        /**	preference, use caution
		/**	----------------------------------------*/
		
		if ( ee()->input->post('language') !== FALSE AND ee()->input->post('language') != '' )
		{
			$language    = ee()->security->sanitize_filename( ee()->input->get_post('language') );
			
			if ( ! is_dir( $this->lang_dir.$language ) )
			{
				return $this->_output_error('general', array(ee()->lang->line('incorrect_language')));
			}
		}
		
		/**	----------------------------------------
        /**   Required Fields
        /**	----------------------------------------*/
        
		if ( $this->_param('required') !== FALSE)
		{
        	$this->_mfields();
        		
			$missing	= array();
			
			$required	= preg_split( "/,|\|/", $this->_param('required') );
        	
			foreach ( $required as $req )
			{
				if ( $req == 'all_required')
				{
					foreach ( $this->mfields as $key => $val )
					{
						if ( ! ee()->input->get_post($key) AND $val['required'] == 'y' )
						{
							$missing[]	= $this->mfields[$key]['label'];
						}
					}
				}
				elseif ( ! ee()->input->get_post($req) )
				{
					if (isset( $this->mfields[$req] ) )
					{
						$missing[]	= $this->mfields[$req]['label'];
					}
					elseif (in_array($req, $this->standard))
					{
						if (in_array($req, array('bday_d', 'bday_m', 'bday_y')))
						{
							$missing[]	= ee()->lang->line('mbr_birthday');	
						}
						elseif ($req == 'daylight_savings')
						{
							$missing[] = ee()->lang->line('daylight_savings_time');
						}
						elseif(in_array($req, array('aol_im', 'yahoo_im', 'msn_im', 'icq', 'signature' )))
						{
							$missing[]	= ee()->lang->line($req);	
						}
						else
						{
							$missing[]	= ee()->lang->line('mbr_'.$req);	
						}
					}
				}
        	}
			
			/**	----------------------------------------
			/**	Anything missing?
			/**	----------------------------------------*/
			
			if ( count( $missing ) > 0 )
			{
				$missing	= implode( "</li><li>", $missing );
				
				$str		= str_replace( "%fields%", $missing, ee()->lang->line('missing_fields') );
				
				return $this->_output_error('general', $str);
			}        	
        }
        
        /** --------------------------------------------
        /**  Required Custom Member Fields?
        /** --------------------------------------------*/
        
        $no_fields		 = '';
        
        foreach ( $this->_mfields() as $key => $val )
        {
        	if ( $val['required'] == 'y' AND ! ee()->input->get_post($key) )
			{
				$no_fields	.=	"<li>".$val['label']."</li>";
			}
		}
		
		if ( $no_fields != '' )
		{
			return $this->_output_error('general', str_replace( "%s", $no_fields, ee()->lang->line('user_field_required') ));
		}

        /**	----------------------------------------
        /**	Validate submitted data
        /**	----------------------------------------*/

		ee()->load->library('validate', $validate, 'validate');

        if ( ee()->input->post('screen_name') )
        {
			ee()->validate->validate_screen_name();
        }

        /**	----------------------------------------
        /**	Username
        /**	----------------------------------------*/
        
        if ( isset( $_POST['username'] ) )
        {
        	if ( ee()->input->post('username') != '' )
        	{
				if ( ee()->input->post('username') != $query->row('username') )
				{
					if ( ee()->config->item('allow_username_change') == 'y' )
					{
						ee()->validate->validate_username();
						
						if ($this->preferences['email_is_username'] != 'n' && ($key = array_search(ee()->lang->line('username_password_too_long'), ee()->validate->errors)) !== FALSE)
						{
							if (strlen(ee()->validate->username) <= 50)
							{
								unset(ee()->validate->errors[$key]);
							}
							else
							{
								ee()->validate->errors[$key] = str_replace('32', '50', ee()->validate->errors[$key]);	
							}
						}
					}
					else
					{
						return $this->_output_error( 'general', array( ee()->lang->line( 'username_change_not_allowed' ) ) );
					}
				}
        	}
        	else
        	{
        		ee()->validate->errors[]	= ee()->lang->line( 'missing_username' );
        	}
        }

        /**	----------------------------------------
        /**	Password
        /**	----------------------------------------*/
                       
        if ( ee()->input->get_post('password') AND ee()->input->get_post('password') != '')
        {
			ee()->validate->validate_password();
        }

        /**	----------------------------------------
        /**	Email
        /**	----------------------------------------*/
                       
        if ( ee()->input->get_post('email') )
        {
			ee()->validate->validate_email();
        }
                        
        /**	----------------------------------------
        /**	Display errors if there are any
        /**	----------------------------------------*/
        
		if (count(ee()->validate->errors) > 0)
		{
			return $this->_output_error('submission', ee()->validate->errors);
		}
		
		/** --------------------------------------------
        /**  Test Image Uploads
        /** --------------------------------------------*/
        
        $this->_upload_images( 0, TRUE );
        
        /**	----------------------------------------
        /**	Check Form Hash
        /**	----------------------------------------*/
        
        if ( ee()->config->item('secure_forms') == 'y' )
        {
            $secure = ee()->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes 
            								WHERE hash='".ee()->db->escape_str($_POST['XID'])."' 
            								AND ip_address = '".ee()->input->ip_address()."' 
            								AND date > UNIX_TIMESTAMP()-7200");
        
            if ($secure->row('count') == 0)
            {
				return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
            }
                                
			ee()->db->query("DELETE FROM exp_security_hashes 
								  WHERE (hash='".ee()->db->escape_str($_POST['XID'])."' 
								  AND ip_address = '".ee()->input->ip_address()."') 
								  OR date < UNIX_TIMESTAMP()-7200");
        }
                    
        /** ---------------------------------
        /**	Fetch categories
        /** ---------------------------------*/
                        
        if ( isset( $_POST['category'] ) AND is_array( $_POST['category'] ) )
        {
			foreach ( $_POST['category'] as $cat_id )
			{
				$this->cat_parents[] = $cat_id;
			}
			
			if ( ee()->config->item('auto_assign_cat_parents') == 'y' )
			{
				$this->_fetch_category_parents( $_POST['category'] );            
			}
        }
        
		unset( $_POST['category'] );
		
		ee()->db->query( "DELETE FROM exp_user_category_posts WHERE member_id = '".ee()->db->escape_str( $member_id )."'" );
		
		foreach ( $this->cat_parents as $cat_id )
		{
			ee()->db->query( ee()->db->insert_string( 'exp_user_category_posts', array( 'member_id' => $member_id, 'cat_id' => $cat_id ) ) );
		}
         
        /**	----------------------------------------
        /**	Update "last post" forum info if needed
        /**	----------------------------------------*/
         
        if ($query->row('screen_name') != $_POST['screen_name'] AND ee()->config->item('forum_is_installed') == "y" )
        {
        	ee()->db->query("UPDATE exp_forums SET forum_last_post_author = '".ee()->db->escape_str($_POST['screen_name'])."' WHERE forum_last_post_author_id = '".$member_id."'");
        }
                
        /**	----------------------------------------
        /**	Assign the query data
        /**	----------------------------------------*/
        
        if ( ee()->input->post('screen_name') )
        {
			$this->insert_data['screen_name'] = ee()->input->post('screen_name');
        }

        if ( ee()->input->post('username') AND ee()->config->item('allow_username_change') == 'y')
        {
            $this->insert_data['username'] = ee()->input->get_post('username');
        }
        
        /**	----------------------------------------
        /**	Was a password submitted?
        /**	----------------------------------------*/

        if ( ee()->input->get_post('password') AND ee()->input->get_post('password') != '' )
        {
            $this->insert_data['password'] = ee()->functions->hash(stripslashes($_POST['password']));
        }
        
        /**	----------------------------------------
        /**	Was an email submitted?
        /**	----------------------------------------*/

        if ( ee()->input->get_post('email') )
        {
            $this->insert_data['email'] = ee()->input->get_post('email');
        }
        
        /**	----------------------------------------
        /**	Assemble standard fields
        /**	----------------------------------------*/
        
        foreach ( $this->standard as $field )
        {
        	if ( isset( $_POST[ $field ] ) )
        	{
        		$this->insert_data[$field]	= $_POST[ $field ];
        	}
        }
        
        /**	----------------------------------------
        /**	Assemble checkbox fields
        /**	----------------------------------------*/
        
        if ( $this->_param('checks') != '' )
        {
        	foreach ( explode( "|", $this->_param('checks') )  as $c )
        	{
        		if ( in_array( $c, $this->check_boxes ) )
        		{
        			if ( ee()->input->post($c) !== FALSE )
        			{
        				if ( stristr( ee()->input->post($c), 'n' ) )
        				{
							$this->insert_data[$c]	= 'n';
        				}
        				else
        				{
							$this->insert_data[$c]	= 'y';
        				}
        			}
        			else
        			{
        				$this->insert_data[$c]	= 'n';
        			}
        		}
        	}
        }
        
        /**	----------------------------------------
        /**	If a super admin is editing, we will
        /**	allow changes to member group.
        /**	Otherwise we will unset group id
        /**	right before updating just to be
        /**	damn sure we don't get hacked.
        /**	----------------------------------------*/
        
        if ( ee()->input->post('group_id') AND ee()->input->post('group_id') != $query->row('group_id') AND ee()->session->userdata('group_id') == '1' )
        {
        	if ( ee()->session->userdata('member_id') == $member_id )
        	{
				return $this->_output_error('general', array(ee()->lang->line('super_admin_group_id')));
        	}
        	
        	$this->insert_data['group_id']	= ee()->db->escape_str( ee()->input->post('group_id') );
        }
        
        elseif(ee()->input->post('group_id') !== FALSE && ctype_digit(ee()->input->post('group_id')) && $this->_param('allowed_groups') !== FALSE)
        {
        	$sql = "SELECT DISTINCT group_id FROM exp_member_groups
    				WHERE group_id NOT IN (1,2,3,4) 
    				AND group_id = '".ee()->db->escape_str(ee()->input->post('group_id'))."'
    				".ee()->functions->sql_andor_string( $this->_param('allowed_groups'), 'group_id');
    			
    		$gquery = ee()->db->query($sql);
    	
    		if ($query->num_rows() > 0)
    		{
    			$this->insert_data['group_id'] = $gquery->row('group_id');
    		}
    		else
    		{
    			unset( $this->insert_data['group_id'] );
    		}
        }
        
        //	HACK! This allows those with Admin permissions to change a member's group id.
        //  		Change this at your own risk...
        /*
        elseif ( ee()->input->post('group_id') !== FALSE AND $this->_param('group_id') !== FALSE AND ee()->input->post('group_id') != $query->row('group_id') AND ee()->session->userdata('group_id') != '1' AND in_array( ee()->session->userdata('group_id'), preg_split( "/,|\|/", $this->_param('group_id'), -1, PREG_SPLIT_NO_EMPTY ) ) !== FALSE AND ctype_digit( ee()->input->post('group_id') ) )
        {
        	$this->insert_data['group_id']	= ee()->input->post('group_id');
        }
        */        
        
        else
        {
        	unset( $this->insert_data['group_id'] );
        }
        
        /**	----------------------------------------
        /**	Last activity
        /**	----------------------------------------*/
        
        if ($member_id == ee()->session->userdata['member_id'])
        {
        	$this->insert_data['last_activity'] = ee()->localize->now;
        }
        
        /**	----------------------------------------
        /**	Run standard insert
        /**	----------------------------------------*/
        
        if ( count( $this->insert_data ) > 0 )
        {
			ee()->db->query(ee()->db->update_string('exp_members', $this->insert_data, "member_id = '".$member_id."'"));
        }
        
        /**	----------------------------------------
        /**	Assemble custom fields
        /**	----------------------------------------*/
        
        $cfields	= array();
        
        foreach ( $this->_mfields() as $key => $val )
        {
			/**	----------------------------------------
			/**	Handle empty checkbox fields
			/**	----------------------------------------*/
			
			if ( $this->_param( 'custom_checks' ) )
			{
				$arr	= explode( "|", $this->_param( 'custom_checks' ) );
				
				foreach ( $arr as $check )
				{
					$cfields['m_field_id_'.$val['id']]	= "";
				}
			}
			
			/**	----------------------------------------
			/**	Handle fields
			/**	----------------------------------------*/
			
        	if ( isset( $_POST[ $key ] ) )
        	{
				/**	----------------------------------------
				/**	Handle arrays
				/**	----------------------------------------*/
				
				if ( is_array( $_POST[ $key ] ) )
				{
					$cfields['m_field_id_'.$val['id']]	= implode( "\n", $_POST[ $key ] );
				}
				else
				{
					$cfields['m_field_id_'.$val['id']]	= $_POST[ $key ];
				}
        	}
        	else
        	{
        		unset( $cfields['m_field_id_'.$val['id']] );
        	}
        }
        
        /**	----------------------------------------
        /**	Run custom fields insert
        /**	----------------------------------------*/
        
        if ( count( $cfields ) > 0 )
        {
			ee()->db->query(ee()->db->update_string('exp_member_data', $cfields, "member_id = '".$member_id."'")); 
        }
        
        /**	----------------------------------------
        /**	Handle image uploads
        /**	----------------------------------------*/
        
		$this->_upload_images( $member_id );
        
        /**	----------------------------------------
        /**	Update comments if screen name has
        /**	changed.
        /**	----------------------------------------*/        

		if ($query->row('screen_name') != $_POST['screen_name'])
		{                          
			ee()->db->query(ee()->db->update_string('exp_comments', array('name' => $_POST['screen_name']), "author_id = '".$member_id."'"));   
        
			// We need to update the gallery comments 
			// But!  Only if the table exists
						
			if (ee()->db->table_exists('gallery_comments'))
			{
				ee()->db->query(ee()->db->update_string('exp_gallery_comments', array('name' => $_POST['screen_name']), "author_id = '".$member_id."'"));   
			}
        }
        
        /** --------------------------------------------
        /**  Send Update Email of Peace and Love
        /** --------------------------------------------*/
        
        if ($update_admin_email === TRUE && trim($member_update_admin_notification_emails) != '')
        {
        	$this->_member_update_email($old_data, array_merge($query->row_array(), $this->insert_data, $cfields), $member_update_admin_notification_emails, $member_update_admin_notification_template);
        }
        
		/* -------------------------------------------
		/* 'user_edit_end' hook.
		/*  - Do something when a user edits their profile
		/*  - Added $cfields for User 2.1
		*/
			$edata = ee()->extensions->call('user_edit_end', $query->row('member_id'), $this->insert_data, $cfields);
			if (ee()->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
        
		/**	----------------------------------------
        /**	 Override Return
		/**	----------------------------------------*/
		
		if ( $this->_param('override_return') !== FALSE AND $this->_param('override_return') != '' )
		{	
			ee()->functions->redirect( $this->_param('override_return') );
			exit;
		}
		
		/**	----------------------------------------
		/**	Set return
		/**	----------------------------------------*/
        
        if ( ee()->input->get_post('return') !== FALSE AND ee()->input->get_post('return') != '' )
        {
        	$return	= ee()->input->get_post('return');
        }
        elseif ( ee()->input->get_post('RET') !== FALSE AND ee()->input->get_post('RET') != '' )
        {
        	$return	= ee()->input->get_post('RET');
        }
        else
        {
        	$return = ee()->config->item('site_url');
        }
		
		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) > 0 )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		elseif ( stristr( $return, "http://" ) === FALSE && stristr( $return, "https://" ) === FALSE )
		{
			$return	= ee()->functions->create_url( $return );
		}
		
		/**	----------------------------------------
		/**	Prep for username change on return
		/**	To keep it loose, but keep it tight.
		/**	----------------------------------------*/
		
		if ( ee()->input->post('username') !== FALSE AND $old_username != ee()->input->post('username') )
		{
			if ( stristr( $return, "/user/".$old_username ) )
			{
				$return	= str_replace( "/user/".$old_username, "/user/".ee()->input->get_post('username'), $return );
			}
		}
		else
		{
			$return	= str_replace( LD.'username'.RD, $old_username, $return );
		}
		
		/**	----------------------------------------
		/**	Password stuff
		/**	----------------------------------------*/
		
		if ( $changing_password )
		{
			return ee()->output->show_message(array('title'		=> ee()->lang->line('success'), 
											'heading'	=> ee()->lang->line('success'), 
											'link'		=> array( $return, ee()->lang->line('return') ), 
											'content'	=> ee()->lang->line('password_changed')));
		}
		
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		$return	= $this->_chars_decode( $return );
        
		ee()->functions->redirect( $return );
		exit;
	}
	
	/* END edit profile */
	

	// --------------------------------------------------------------------

	/**
	 *	Category Parsing for a Tag
	 *
	 *	@access		private
	 *	@param		string
	 *	@param		string
	 *	@return		string
	 */

	private function _categories( $tagdata, $only_show_selected = '' )
	{
		/**	----------------------------------------
		/**	Parent id
		/**	----------------------------------------*/
		
		$parent_id	= '';
		
		if ( isset(ee()->TMPL)  AND ee()->TMPL->fetch_param('parent_id') !== FALSE AND ctype_digit( ee()->TMPL->fetch_param('parent_id') ) )
		{
			$parent_id	= ee()->TMPL->fetch_param('parent_id');
		}
		
		/**	----------------------------------------
		/**	 Parse the {category} User Pair
		/**	----------------------------------------*/
		
		if (sizeof($this->cat_chunk) == 0 && 
			preg_match_all( "/".LD."categories(.*?)".RD."(.*?)".LD.preg_quote(T_SLASH, '/')."categories".RD."/s", $tagdata, $matches ) )
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$this->cat_chunk[$j] = array('category_tagdata'	=> $matches['2'][$j], 
											 'params'			=> ee()->functions->assign_parameters($matches['1'][$j]), 
											 'category_block'	=> $matches['0'][$j]);
											
				foreach ( array( 'category_header', 'category_footer', 'category_indent', 'category_body', 'category_selected', 'category_group_header', 'category_group_footer' ) as $val )
				{
					if ( preg_match( "/".LD.$val.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$val.RD."/s", $this->cat_chunk[$j]['category_tagdata'], $match ) )
					{
						$this->cat_chunk[$j][$val]	= $match['1'];
					}
					else
					{
						$this->cat_chunk[$j][$val] = '';
					}
				}
			}
			
			$query = ee()->db->query("SELECT field_id, field_name FROM exp_category_fields
								 WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."')");
			
			foreach ($query->result_array() as $row)
			{
				$this->catfields[$row['field_name']] = $row['field_id'];
			}
		}
		
		/** --------------------------------------------
        /**  Process Categories
        /** --------------------------------------------*/
		
		if (sizeof($this->cat_chunk) > 0)
		{
			foreach($this->cat_chunk as $cat_chunk)
			{
				// Reset
				$this->categories	= array();
				$this->used_cat		= array();
				$this->cat_params = array();
				
				foreach($cat_chunk as $var => $value)
				{
					if ( $var == 'params' AND is_array( $value ) === TRUE )
					{
						$this->cat_params	= $value;
					}
					
					$this->cat_formatting[$var] = $value;
				}
				
				if ( ! isset($this->cat_params['group_id']))
				{
					$this->cat_params['group_id'] = '';
				}
				
				/**	----------------------------------------
				/**	Prepare the tree
				/**	----------------------------------------*/
				
				$query = ee()->db->query("SELECT preference_value FROM exp_user_preferences WHERE preference_name = 'category_groups' LIMIT 1");
    	
				$category_groups = ($query->num_rows() == 0) ? '' : $query->row('preference_value');
				
				if ( ! empty($category_groups) )
				{
					foreach ( explode( "|", $category_groups ) as $group_id )
					{
						if ( $this->cat_params['group_id'] != '')
						{
							if (substr($this->cat_params['group_id'], 0, 4) != 'not ' && ! in_array( $group_id, explode( "|", $this->cat_params['group_id'] ) ))
							{
								continue;
							}
							elseif (substr($this->cat_params['group_id'], 0, 4) == 'not ' && in_array( $group_id, explode( "|", substr($this->cat_params['group_id'], 4))))
							{
								continue;
							}
						}
						
						$this->categories[]	= $this->_category_group_vars( $group_id, $this->cat_formatting['category_group_header'] );
						
						$this->category_tree( 'text', $group_id, $parent_id, $only_show_selected );
						
						$this->categories[]	= $this->_category_group_vars( $group_id, $this->cat_formatting['category_group_footer'] );
					}
				}
				
				$r	= implode( "", $this->categories );
				
				if ( isset( $this->cat_params['backspace'] ) === TRUE AND ctype_digit( $this->cat_params['backspace'] ) AND $this->cat_params['backspace'] > 0 )
				{
					$r	= substr( $r, 0, -($this->cat_params['backspace']) );
				}
				
				$tagdata	= str_replace( $this->cat_formatting['category_block'], $r, $tagdata );
			}
		}
		
		return $tagdata;
	}
	
	/* END _categories() */
	

    // --------------------------------------------------------------------

	/**
	 *	Fetch Parents for an Array of Categories
	 *
	 *	@access		private
	 *	@param		array
	 *	@return		null	Puts the categories into the global category array
	 */
    
	private function _fetch_category_parents(array $cat_array = array())
	{
		if ( ! is_array($cat_array) OR count($cat_array) == 0)
		{
			return;
		}

		$sql = "SELECT parent_id FROM exp_categories WHERE cat_id != ''";
		
		if ( ee()->config->item('site_id') !== FALSE )
		{
			$sql	.= " AND site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
		}
		
		$sql	.= " AND cat_id IN ('".implode("','", ee()->db->escape_str($cat_array))."')";
		
		$query = ee()->db->query($sql);
				
		if ($query->num_rows() == 0)
		{
			return;
		}
		
		$temp = array();

		foreach ($query->result_array() as $row)
		{
			if ($row['parent_id'] != 0)
			{
				$this->cat_parents[] = $row['parent_id'];
				
				$temp[] = $row['parent_id'];
			}
		}
	
		$this->_fetch_category_parents($temp);
	}
	
	/* END fetch parent category id */
	
    
	// --------------------------------------------------------------------

	/**
	 *	Process Category Tree
	 *
	 *	@access		public
	 *	@param		string
	 *	@param 		integer
	 *	@param		integer
	 *	@param		string
	 *	@return		null
	 */

	public function category_tree( $type = 'text', $group_id = '', $parent_id = '', $only_show_selected = '' )
    {   
		/**	----------------------------------------
		/**  Fetch member's categories
		/**	----------------------------------------*/
		
		if ( $this->member_id != 0 AND count( $this->assigned_cats ) == 0 )
		{
			$catq	= ee()->db->query( "SELECT cat_id FROM exp_user_category_posts WHERE member_id = '".ee()->db->escape_str( $this->member_id )."'" );
			
			foreach ( $catq->result_array() as $row )
			{
				$this->assigned_cats[]	= $row['cat_id'];
			}
		}
        
		/**	----------------------------------------
		/**  Fetch categories
		/**	----------------------------------------*/
		
		$sql = "SELECT c.cat_name	 AS category_name, 
					   c.cat_id		 AS category_id, 
					   c.parent_id	 AS parent_id,
					   c.cat_image	 AS category_image,
					   c.cat_description AS category_description,
					   c.cat_url_title	 AS category_url_title,
					   p.cat_name	 AS parent_name,
					   cg.group_name AS category_group_name, cg.group_id AS category_group_id,
					   fd.*
				FROM exp_categories c 
				LEFT JOIN exp_categories p ON p.cat_id = c.parent_id 
				LEFT JOIN exp_category_groups cg ON cg.group_id = c.group_id 
				LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
				WHERE c.cat_id != 0
				AND fd.site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')";
		
		if ( $group_id != '' AND ctype_digit( $group_id ) )
		{
			$sql	.= " AND c.group_id = '".ee()->db->escape_str( $group_id )."'";
		}
		
		if ( $parent_id != '' AND ctype_digit( $parent_id ) )
		{
			$sql	.= " AND c.parent_id = '".ee()->db->escape_str( $parent_id )."'";
		}
		
		if ( $only_show_selected == 'yes' )
		{
			$sql	.= " AND c.cat_id IN ('".implode( "','", $this->assigned_cats )."')";
		}
        
		/**	----------------------------------------
		/**  Establish sort order
		/**	----------------------------------------*/
		
		if ( sizeof( $this->cat_params ) > 0 AND isset( $this->cat_params['orderby'] ) === TRUE AND $this->cat_params['orderby'] == 'category_order' )
		{
			$sql .= " ORDER BY c.parent_id, c.cat_order";
		}
		else
		{
			$sql .= " ORDER BY c.parent_id, c.cat_name";
		}
							 
		if ( ! isset ($this->cached[md5($sql)]))
		{
			$query = ee()->db->query($sql);
			$this->cached[md5($sql)] = $query;
		}
		else
		{
			$query = $this->cached[md5($sql)];
		}
              
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }
        
		/**	----------------------------------------
		/**  Assign cats to array
		/**	----------------------------------------*/
                    
        foreach($query->result_array() as $row)
        {        
            $cat_array[$row['category_id']]  = $row;
        }
        
        $this->categories[]	= $this->cat_formatting['category_header'];
        
		/**	----------------------------------------
		/**	Loop for each category
		/**	----------------------------------------
		/*	Listen, we try to construct a family
		/*	when we can, but if we're not
		/*	auto-assigning parent cats, forget it.
		/*	Just show a flat list.
		/**	----------------------------------------*/
		         
        foreach($cat_array as $key => $val) 
        {
        	if ( in_array( $key, $this->used_cat ) === TRUE ) continue;
        	
        	$selected	= ( in_array( $key, $this->assigned_cats ) === TRUE ) ? $this->cat_formatting['category_selected']: '';
        	
        	$checked	= ( in_array( $key, $this->assigned_cats ) === TRUE ) ? $this->cat_formatting['category_selected']: '';
        	
        	$parent		= ( $this->search === TRUE ) ? $val['parent_name']: '';
        	
        	$cat_body	= $this->cat_formatting['category_body'];
        	
        	$cat_body	= str_replace( LD."selected".RD, $selected, $cat_body );
        	
        	$cat_body	= str_replace( LD."checked".RD, $checked, $cat_body );
        	
        	$data					= $val;
        	$data['depth']			= 0;
        	$data['indent']			= '';
        	$data['category_id']	= $key;
        	
        	foreach($this->catfields as $name => $id)
        	{
        		if (isset($val['field_id_'.$id]))
        		{
        			$data[$name] = $val['field_id_'.$id];
        		}
        	}
        	
        	$cat_body = ee()->functions->prep_conditionals($cat_body, $data);
        	
        	foreach($data as $var_name => $var_value)
        	{
        		$cat_body = str_replace(LD.$var_name.RD, $var_value, $cat_body);
        	}
        	
			$this->categories[] = $cat_body;
			
			$this->used_cat[]	= $key;

			$this->category_subtree($key, $cat_array, $group_id, $depth=0, $type, $parent_id);
        }
        
        $this->categories[]	= $this->cat_formatting['category_footer'];
    }
    
    /* END category tree */
    
    // --------------------------------------------------------------------

	/**
	 *	Process the Subcategories for Our Category Tree
	 *
	 *	@access		public
	 *	@param		integer
	 *	@param		array
	 *	@param		integer
	 *	@param		integer
	 *	@param		string
	 *	@param		integer
	 *	@return		string
	 */
    
	public function category_subtree( $cat_id, $cat_array, $group_id, $depth = 0, $type, $parent_id = '' )
    {
        $depth	= ($depth == 0) ? 1: $depth + 1;
        
		$indent	= 15;
        
        $this->categories[]	= $this->cat_formatting['category_header'];
        
        $checked	= ( $this->selected === TRUE ) ? 'checked="checked"': '';
        
        $arr		= array();

        foreach ($cat_array as $key => $val) 
        {
        	if ( in_array( $key, $this->used_cat ) === TRUE ) continue;
        	
        	$selected	= ( in_array( $key, $this->assigned_cats ) === TRUE ) ? $this->cat_formatting['category_selected']: '';
        	
        	$checked	= ( in_array( $key, $this->assigned_cats ) === TRUE ) ? $this->cat_formatting['category_selected']: '';
        	
            if ($cat_id == $val['parent_id']) 
            {
				$cat_body	= $this->cat_formatting['category_body'];
				
				$cat_body	= str_replace( LD."selected".RD, $selected, $cat_body );
        	
				$cat_body	= str_replace( LD."checked".RD, $checked, $cat_body );
				
				$data					= $val;
				$data['depth']			= $depth;
				$data['indent']			= str_repeat( $this->cat_formatting['category_indent'], $depth);
				$data['categoriy_id']	= $key;
        		foreach($this->catfields as $name => $id)
				{
					if (isset($val['field_id_'.$id]))
					{
						$data[$name] = $val['field_id_'.$id];
					}
				}
				
				$cat_body = ee()->functions->prep_conditionals($cat_body, $data);
				
				foreach($data as $var_name => $var_value)
				{
					$cat_body = str_replace(LD.$var_name.RD, $var_value, $cat_body);
				}
				
				$this->categories[] = $cat_body;
				
				$this->used_cat[]	= $key;
				
				$this->category_subtree($key, $cat_array, $group_id, $depth, $type, $parent_id);
            }
        }
        
        $this->categories[]	= $this->cat_formatting['category_footer'];
    }
    
    /* END category subtree */
	
	
	// --------------------------------------------------------------------

	/**
	 *	Return Category Group Variables
	 *
	 *	@access		public
	 *	@param		integer
	 *	@param		string
	 *	@return		string
	 */

	public function _category_group_vars( $group_id = 0, $data = '' )
    {
        
        if ( $group_id == 0 OR $data == '' ) return FALSE;
        
        if ( isset( $this->cat_group_vars[$group_id] ) === TRUE )
        {
        	$data   = ee()->functions->prep_conditionals( $data, array('category_group_id' => $group_id, 'category_group_name' => $this->cat_group_vars[$group_id]) );
        	
        	return str_replace( array( LD.'category_group_id'.RD, LD.'category_group_name'.RD ), array( $group_id, $this->cat_group_vars[$group_id] ), $data );
        }
        
        $sql	= "SELECT group_id, group_name FROM exp_category_groups WHERE group_id != 0 ";
        
        if ( $this->cat_params['group_id'] != '' )
        {
        	$sql	.= ee()->functions->sql_andor_string( $this->cat_params['group_id'], 'group_id' );
        }
        
        $query	= ee()->db->query( $sql );
        
        if ( $query->num_rows() > 0 )
        {
        	foreach ( $query->result_array() as $row )
        	{
        		$this->cat_group_vars[ $row['group_id'] ]	= $row['group_name'];
        	}
        }
        
        if ( isset( $this->cat_group_vars[$group_id] ) )
        {
        	$data   = ee()->functions->prep_conditionals( $data, array('category_group_id' => $group_id, 'category_group_name' => $this->cat_group_vars[$group_id]) );
        	
        	$data	= str_replace( array( LD.'category_group_id'.RD, LD.'category_group_name'.RD ), 
        						   array( $group_id, $this->cat_group_vars[$group_id] ), 
        						   $data );
        }
        
        return $data;
	}
	
	/* END category group vars */
	
	// --------------------------------------------------------------------

	/**
	 *	Groups
	 *
	 *	Allows Authorized Members to Edit Other Members in Batches
	 *
	 *	@access		public
	 *	@return		string
	 */
	 
	public function groups()
    {	
		/**	----------------------------------------
		/**	Validate the admin
		/**	----------------------------------------
		/*	We can authorize member groups to use
		/*	this form. Let's check to see if this
		/*	person can.
		/**	----------------------------------------*/
		
		if ( ee()->session->userdata['group_id'] == 1 OR 
			( ee()->TMPL->fetch_param('authorized_group') !== FALSE AND 
			  ctype_digit( preg_split( "/,|\|/", ee()->TMPL->fetch_param('authorized_group'), -1, PREG_SPLIT_NO_EMPTY ) ) &&
			  in_array( ee()->session->userdata['group_id'], preg_split( "/,|\|/", ee()->TMPL->fetch_param('authorized_group'), -1, PREG_SPLIT_NO_EMPTY ) ) !== FALSE
			)
		   )
		{
		}
		else
		{
    		return $this->no_results('user');
		}
    	
		/**	----------------------------------------
		/**	Editable groups
		/**	----------------------------------------
		/*	We have a safeguard that requires that
		/*	the editable groups be supplied by the
		/*	creator of the template using this
		/*	function. So we need to validate that.
		/**	----------------------------------------*/
		
		$all			= FALSE;
		$editable_group	= array();
		
		if ( ee()->TMPL->fetch_param('editable_group') !== FALSE AND ee()->TMPL->fetch_param('editable_group') != '' )
		{
			if ( ee()->TMPL->fetch_param('editable_group') == 'all' )
			{
				$all	= TRUE;
			}
			else
			{
				$editable_group		= preg_split( "/,|\|/", ee()->TMPL->fetch_param('editable_group'), -1, PREG_SPLIT_NO_EMPTY );
			}
			
			if ( $all === FALSE AND ( count( $editable_group ) == 0 OR ctype_digit( $editable_group ) === FALSE ) )
			{
				return $this->no_results('user');
			}
		}
		else
		{
    		return $this->no_results('user');
		}
    	
		/**	----------------------------------------
		/**	Grab member data
		/**	----------------------------------------*/
		
		$sql	= "SELECT md.*, m.email, m.group_id, m.member_id, m.screen_name, m.username";
		
		$arr	= array_merge( $this->standard, $this->check_boxes, $this->photo, $this->avatar, $this->signature );
		
		foreach ( $arr as $a )
		{
			$sql	.= ", m.".$a;
		}
		
		$sql	.= " FROM exp_members m LEFT JOIN exp_member_data md ON m.member_id = md.member_id WHERE m.group_id != '1'";
		
		if ( $all === FALSE )
		{
			$sql	.= " AND m.group_id IN (";
			
			foreach ( $editable_group as $group )
			{
				$sql	.= "'".$group."',";
			}
			
			$sql	= rtrim( $sql, "," );
			
			$sql	.= ")";
		}
		
		$query	= ee()->db->query( $sql );
		
		if ( $query->num_rows() == 0 )
		{
    		return $this->no_results('user');
		}
    	
		/**	----------------------------------------
		/**	Userdata
		/**	----------------------------------------*/
		
		$tagdata	= ee()->TMPL->tagdata;
    	
		/**	----------------------------------------
		/**	Sniff for checkboxes
		/**	----------------------------------------*/
		
		$checks			= '';
		$custom_checks	= '';
		
		if ( preg_match_all( "/name=['|\"]?(\w+)['|\"]?/", $tagdata, $match ) )
		{
			$this->_mfields();
			
			foreach ( $match['1'] as $m )
			{
				if ( in_array( $m, $this->check_boxes ) )
				{
					$checks	.= $m."|";
				}
				
				if ( isset( $this->mfields[ $m ] ) AND $this->mfields[ $m ]['type'] == 'select' )
				{
					$custom_checks	.= $m."|";
				}
			}
		}
    	
		/**	----------------------------------------
		/**	Sniff for fields of type 'file'
		/**	----------------------------------------*/
		
		if ( preg_match( "/type=['|\"]?file['|\"]?/", $tagdata, $match ) )
		{
			$this->multipart	= TRUE;
		}
		
		/**	----------------------------------------
		/**	Prep additional values
		/**	----------------------------------------*/
		
		$photo_url		= ee()->config->slash_item('photo_url');
		$avatar_url		= ee()->config->slash_item('avatar_url');
		$sig_img_url	= ee()->config->slash_item('sig_img_url');
		
		/**	----------------------------------------
		/**	Are we in 'all' mode?
		/**	----------------------------------------
		/*	This function can loop through all members or it can loop through members
		/*	per group. If we are doing all, then that's it, no group level looping.
		/**	----------------------------------------*/
		
		if ( $all === TRUE AND preg_match( "/".LD."group".RD."(.*?)".LD.preg_quote(T_SLASH, '/')."group".RD."/s", $tagdata, $match ) > 0 )
		{			
			$output	= '';
			
			foreach ( $query->result_array() as $row )
			{
				$tdata	= $match['1'];
				
				/**	----------------------------------------
				/**	Additionals
				/**	----------------------------------------*/
				
				$row['photo_url']	= $photo_url;
				$row['avatar_url']	= $avatar_url;
				$row['sig_img_url']	= $sig_img_url;
				
				/**	----------------------------------------
				/**	Conditionals
				/**	----------------------------------------*/
				
				$cond	= $row;
				
				$tdata	= ee()->functions->prep_conditionals( $tdata, $cond );
				
				/**	----------------------------------------
				/**	Parse var pairs
				/**	----------------------------------------*/
				
				foreach ( ee()->TMPL->var_pair as $key => $val )
				{
					/**	----------------------------------------
					/**	Timezones
					/**	----------------------------------------*/
					
					if ( $key == 'timezones' )
					{
						preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", $tdata, $m );
						$r	= '';
						
						foreach ( ee()->localize->zones() as $key => $val )
						{
							$out		= $m['1'];
							
							$checked	= ( isset( $row['timezone'] ) AND $row['timezone'] == $key ) ? 'checked="checked"': '';
							
							$selected	= ( isset( $row['timezone'] ) AND $row['timezone'] == $key ) ? 'selected="selected"': '';
							
							$out		= str_replace( LD."zone_name".RD, $key, $out );
							$out		= str_replace( LD."zone_label".RD, ee()->lang->line( $key ), $out );
							$out		= str_replace( LD."checked".RD, $checked, $out );
							$out		= str_replace( LD."selected".RD, $selected, $out );
							
							$r	.= $out;
						}
						
						$tdata	= str_replace( $m['0'], $r, $tdata );
					}
					
					/**	----------------------------------------
					/**	Time format
					/**	----------------------------------------*/
					
					if ( $key == 'time_formats' )
					{
						preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", $tdata, $m );
						$r	= '';
						
						foreach ( array( 'us', 'eu' ) as $key )
						{
							$out		= $m['1'];
							
							$checked	= ( isset( $row['time_format'] ) AND $row['time_format'] == $key ) ? 'checked="checked"': '';
							
							$selected	= ( isset( $row['time_format'] ) AND $row['time_format'] == $key ) ? 'selected="selected"': '';
							
							$out		= str_replace( LD."time_format_name".RD, $key, $out );
							$out		= str_replace( LD."time_format_label".RD, ee()->lang->line( $key ), $out );
							$out		= str_replace( LD."checked".RD, $checked, $out );
							$out		= str_replace( LD."selected".RD, $selected, $out );
							
							$r	.= $out;
						}
						
						$tdata	= str_replace( $m['0'], $r, $tdata );
					}
					
					/**	----------------------------------------
					/**	Languages
					/**	----------------------------------------*/
					
					if ( $key == 'languages' )
					{
						$dirs = array();
				
						if ($fp = @opendir($this->lang_dir))
						{
							while (FALSE !== ($file = readdir($fp)))
							{
								if (is_dir($this->lang_dir.$file) && substr($file, 0, 1) != ".")
								{
									$dirs[] = $file;
								}
							}
							closedir($fp);
						}
				
						sort($dirs);
				
						preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", $tdata, $m );
						$r	= '';
						
						foreach ( $dirs as $key )
						{
							$out		= $m['1'];
							
							$checked	= ( isset( $row['language'] ) AND $row['language'] == $key ) ? 'checked="checked"': '';
							
							$selected	= ( isset( $row['language'] ) AND $row['language'] == $key ) ? 'selected="selected"': '';
							
							$out		= str_replace( LD."language_name".RD, $key, $out );
							$out		= str_replace( LD."language_label".RD, ucfirst( $key ), $out );
							$out		= str_replace( LD."checked".RD, $checked, $out );
							$out		= str_replace( LD."selected".RD, $selected, $out );
							
							$r	.= $out;
						}
						
						$tdata	= str_replace( $m['0'], $r, $tdata );
					}
				}
				
				/**	----------------------------------------
				/**	Parse primary variables
				/**	----------------------------------------*/
				
				foreach ( $row as $key => $val )
				{
					$tdata	= ee()->TMPL->swap_var_single( $key, $val, $tdata );
				}
				
				/**	----------------------------------------
				/**	Parse custom variables
				/**	----------------------------------------*/
				
				foreach ( $this->_mfields() as $key => $val )
				{
					/**	----------------------------------------
					/**	Parse select
					/**	----------------------------------------*/
					
					foreach ( ee()->TMPL->var_pair as $k => $v )
					{
						if ( $k == "select_".$key )
						{
							$data		= ee()->TMPL->fetch_data_between_var_pairs( $tdata, $k );
							
							$tdata	= preg_replace( "/".LD.preg_quote($k,'/').RD."(.*?)".LD.preg_quote(T_SLASH, '/').preg_quote($k, '/').RD."/s", 
													str_replace('$', '\$', $this->_parse_select( $key, $row, $data )), 
													$tdata );
						}
					}
					
					/**	----------------------------------------
					/**	Parse singles
					/**	----------------------------------------*/
					
					$tdata	= ee()->TMPL->swap_var_single( $key, $row['m_field_id_'.$val['id']], $tdata );
				}
				
				$output	.= $tdata;
			}
			
			$tagdata	= str_replace( $match[0], $output, $tagdata );
		}
		
		/**	----------------------------------------
		/**	We're in group mode
		/**	----------------------------------------*/
		
    	else
    	{
			/**	----------------------------------------
			/**	Let's create an array of members by
			/**	group
			/**	----------------------------------------*/
			
			$members	= array();
			
			foreach ( $query->result_array() as $row )
			{
				$members[$row['group_id']][$row['member_id']]	= $row;
			}
			
			/**	----------------------------------------
			/**	Let's loop for each group and parse
			/**	----------------------------------------*/
			
			foreach ( $members as $group => $member )
			{
				if ( preg_match( "/".LD."group_".$group.RD."(.*?)".LD.preg_quote(T_SLASH, '/')."group_".$group.RD."/s", $tagdata, $match ) > 0 )
				{					
					$output	= '';
					
					foreach ( $member as $row )
					{
						$tdata	= $match['1'];
						
						/**	----------------------------------------
						/**	Additionals
						/**	----------------------------------------*/
						
						$row['photo_url']	= $photo_url;
						$row['avatar_url']	= $avatar_url;
						$row['sig_img_url']	= $sig_img_url;
						
						/**	----------------------------------------
						/**	Conditionals
						/**	----------------------------------------*/
						
						$cond		= $row;
						
						$tdata	= ee()->functions->prep_conditionals( $tdata, $cond );
						
						/**	----------------------------------------
						/**	Parse var pairs
						/**	----------------------------------------*/
						
						foreach ( ee()->TMPL->var_pair as $key => $val )
						{
							/**	----------------------------------------
							/**	Timezones
							/**	----------------------------------------*/
							
							if ( $key == 'timezones' )
							{
								preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", $tdata, $m );
								$r	= '';
								
								foreach ( ee()->localize->zones() as $key => $val )
								{
									$out		= $m['1'];
									
									$checked	= ( isset( $row['timezone'] ) AND $row['timezone'] == $key ) ? 'checked="checked"': '';
									
									$selected	= ( isset( $row['timezone'] ) AND $row['timezone'] == $key ) ? 'selected="selected"': '';
									
									$out		= str_replace( LD."zone_name".RD, $key, $out );
									$out		= str_replace( LD."zone_label".RD, ee()->lang->line( $key ), $out );
									$out		= str_replace( LD."checked".RD, $checked, $out );
									$out		= str_replace( LD."selected".RD, $selected, $out );
									
									$r	.= $out;
								}
								
								$tdata	= str_replace( $m['0'], $r, $tdata );
							}
							
							/**	----------------------------------------
							/**	Time format
							/**	----------------------------------------*/
							
							if ( $key == 'time_formats' )
							{
								preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", $tdata, $m );
								$r	= '';
								
								foreach ( array( 'us', 'eu' ) as $key )
								{
									$out		= $m['1'];
									
									$checked	= ( isset( $row['time_format'] ) AND $row['time_format'] == $key ) ? 'checked="checked"': '';
									
									$selected	= ( isset( $row['time_format'] ) AND $row['time_format'] == $key ) ? 'selected="selected"': '';
									
									$out		= str_replace( LD."time_format_name".RD, $key, $out );
									$out		= str_replace( LD."time_format_label".RD, ee()->lang->line( $key ), $out );
									$out		= str_replace( LD."checked".RD, $checked, $out );
									$out		= str_replace( LD."selected".RD, $selected, $out );
									
									$r	.= $out;
								}
								
								$tdata	= str_replace( $m['0'], $r, $tdata );
							}
							
							/**	----------------------------------------
							/**	Languages
							/**	----------------------------------------*/
							
							if ( $key == 'languages' )
							{
								$dirs = array();
						
								if ($fp = @opendir($this->lang_dir))
								{
									while (FALSE !== ($file = readdir($fp)))
									{
										if (is_dir($this->lang_dir.$file) && substr($file, 0, 1) != ".")
										{
											$dirs[] = $file;
										}
									}
									closedir($fp);
								}
						
								sort($dirs);
						
								preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", $tdata, $m );
								$r	= '';
								
								foreach ( $dirs as $key )
								{
									$out		= $m['1'];
									
									$checked	= ( isset( $row['language'] ) AND $row['language'] == $key ) ? 'checked="checked"': '';
									
									$selected	= ( isset( $row['language'] ) AND $row['language'] == $key ) ? 'selected="selected"': '';
									
									$out		= str_replace( LD."language_name".RD, $key, $out );
									$out		= str_replace( LD."language_label".RD, ucfirst( $key ), $out );
									$out		= str_replace( LD."checked".RD, $checked, $out );
									$out		= str_replace( LD."selected".RD, $selected, $out );
									
									$r	.= $out;
								}
								
								$tdata	= str_replace( $m['0'], $r, $tdata );
							}
						}
						
						/**	----------------------------------------
						/**	Parse primary variables
						/**	----------------------------------------*/
						
						foreach ( $row as $key => $val )
						{							
							$tdata	= ee()->TMPL->swap_var_single( $key, $val, $tdata );
						}
						
						/**	----------------------------------------
						/**	Parse custom variables
						/**	----------------------------------------*/
						
						foreach ( $this->_mfields() as $key => $val )
						{
							/**	----------------------------------------
							/**	Parse select
							/**	----------------------------------------*/
							
							foreach ( ee()->TMPL->var_pair as $k => $v )
							{
								if ( $k == "select_".$key )
								{
									$data		= ee()->TMPL->fetch_data_between_var_pairs( $tdata, $k );
									
									$tdata	= preg_replace( "/".LD.preg_quote($k, '/').RD."(.*?)".LD.preg_quote(T_SLASH, '/').preg_quote($k, '/').RD."/s", 
															str_replace('$', '\$', $this->_parse_select( $key, $row, $data )), 
															$tdata );
								}
							}
							
							/**	----------------------------------------
							/**	Parse singles
							/**	----------------------------------------*/
							
							$tdata	= ee()->TMPL->swap_var_single( $key, $row['m_field_id_'.$val['id']], $tdata );
						}
						
						$output	.= $tdata;
					}
			
					$tagdata	= str_replace( $match[0], $output, $tagdata );
				}
			}
    	}
    	
    	
		/**	----------------------------------------
		/**	Prep data
		/**	----------------------------------------*/
		
		$this->form_data['tagdata']					= $tagdata;
		
		$this->form_data['ACT']						= ee()->functions->fetch_action_id('User', 'group_edit');
		
        $this->form_data['RET']						= (isset($_POST['RET'])) ? $_POST['RET'] : ee()->functions->fetch_current_uri();
		
		if ( ee()->TMPL->fetch_param('form_name') !== FALSE && ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
		}
		
		$this->form_data['id']						= ( ee()->TMPL->fetch_param('form_id') !== FALSE ) ? ee()->TMPL->fetch_param('form_id'): 'member_form';
		
		$this->form_data['return']					= ( ee()->TMPL->fetch_param('return') !== FALSE ) ? ee()->TMPL->fetch_param('return'): '';
		
		$this->params['checks']					= $checks;
		
		$this->params['custom_checks']			= $custom_checks;
		
		$this->params['required']				= ( ee()->TMPL->fetch_param('required') !== FALSE ) ? ee()->TMPL->fetch_param('required'): '';
		
		$this->params['authorized_group']		= ( ee()->TMPL->fetch_param('authorized_group') !== FALSE ) ? ee()->TMPL->fetch_param('authorized_group'): '';
		
		$this->params['editable_group']			= ( ee()->TMPL->fetch_param('editable_group') !== FALSE ) ? ee()->TMPL->fetch_param('editable_group'): '';
		
		$this->params['secure_action']			= ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
        
		return $this->_form();
    }
    
    /* END groups */
    
    // --------------------------------------------------------------------

	/**
	 *	Group Edit
	 *
	 *	Edit members in a batch
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function group_edit()
    {	
		/**	----------------------------------------
		/**	Logged in?
		/**	----------------------------------------*/
		
		if ( ee()->session->userdata('member_id') == 0 )
		{
        	return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
		}
        
        /** ----------------------------------------
        /**  Is the user banned?
        /** ----------------------------------------*/
        
        if (ee()->session->userdata['is_banned'] == TRUE)
        {            
            return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
        }
                
        /** ----------------------------------------
        /**  Is the IP address and User Agent required?
        /** ----------------------------------------*/
                
        if (ee()->config->item('require_ip_for_posting') == 'y')
        {
        	if (ee()->input->ip_address() == '0.0.0.0' OR ee()->session->userdata['user_agent'] == "")
        	{            
            	return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
        	}        	
        } 
        
        /** ----------------------------------------
		/**  Is the nation of the user banned?
		/** ----------------------------------------*/
		
		ee()->session->nation_ban_check();
        
        /** ----------------------------------------
        /**  Blacklist/Whitelist Check
        /** ----------------------------------------*/
        
        if (ee()->blacklist->blacklisted == 'y' AND ee()->blacklist->whitelisted == 'n')
        {
        	return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
        }  
    	
		/**	----------------------------------------
		/**	Validate the admin
		/**	----------------------------------------
		/*	We can authorize member groups to use
		/*	this function. Let's check to see if
		/*	this person can.
		/**	----------------------------------------*/
		
		if ( ee()->session->userdata['group_id'] == 1 OR 
			 ( $this->_param('authorized_group') !== FALSE &&
			   ctype_digit( preg_split( "/,|\|/", $this->_param('authorized_group'), -1, PREG_SPLIT_NO_EMPTY ) ) &&
			   in_array( ee()->session->userdata['group_id'], preg_split( "/,|\|/", $this->_param('authorized_group'), -1, PREG_SPLIT_NO_EMPTY ) ) !== FALSE ) )
		{
		}
		else
		{
        	return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
		}
    	
		/**	----------------------------------------
		/**	Editable groups
		/**	----------------------------------------
		/*	We have a safeguard that requires that
		/*	the editable groups be supplied by the
		/*	creator of the template using this
		/*	function. So we need to validate that.
		/**	----------------------------------------*/
		
		$all			= FALSE;
		$editable_group	= array();
		
		if ( $this->_param('editable_group') !== FALSE AND $this->_param('editable_group') != '' )
		{
			if ( $this->_param('editable_group') == 'all' )
			{
				$all	= TRUE;
			}
			else
			{
				$editable_group		= preg_split( "/,|\|/", $this->_param('editable_group'), -1, PREG_SPLIT_NO_EMPTY );
			}
			
			if ( $all === FALSE AND ( count( $editable_group ) == 0 OR ctype_digit( $editable_group ) === FALSE ) )
			{
				return $this->_output_error('general', array(ee()->lang->line('incorrect_editable_groups')));
			}
		}
		else
		{
        	return $this->_output_error('general', array(ee()->lang->line('incorrect_editable_groups')));
		}
    	
		/**	----------------------------------------
		/**	Assemble array from post
		/**	----------------------------------------
		/*	We're doing a group thing, so we only
		/*	care about info coming through a POST
		/*	array
		/**	----------------------------------------*/
		
		if ( isset( $_POST ) === FALSE )
		{
        	return $this->_output_error('general', array(ee()->lang->line('no_data')));
		}
		
		$members	= array();
		
		$this->_mfields();
		
		foreach ( $_POST as $key => $val )
		{
			/**	----------------------------------------
			/**	If we're not dealing with an array we
			/**	skip
			/**	----------------------------------------*/
			
			if ( is_array( $val ) === TRUE )
			{
				/**	----------------------------------------
				/**	Let's only allow things we care about
				/**	into the process
				/**	----------------------------------------*/
				
				//	Standard fields
				
				if ( in_array( $key, $this->standard ) === TRUE OR in_array( $key, array('group_id') ) === TRUE OR isset( $this->mfields[$key] ) === TRUE )
				{
					/**	----------------------------------------
					/**	Load members array
					/**	----------------------------------------
					/*	We're going to check later, but right
					/*	now we assume that for each of our
					/*	arrays, the key is the member id and
					/*	the value is some value we're going to]
					/*	set.
					/**	----------------------------------------*/
					
					foreach ( $val as $k => $v )
					{
						if ( ctype_digit( $k ) )
						{
							$members[$k][$key]	= ee()->security->xss_clean( $v );
						}
					}
				}
			}
		}
		
        /**	----------------------------------------
        /**	Any members?
        /**	----------------------------------------*/
        
        if ( count( $members ) == 0 )
        {
        	return $this->_output_error('general', array(ee()->lang->line('member_list_error')));
        }
		
        /**	----------------------------------------
        /**	Check the members against the DB
        /**	----------------------------------------*/
        
        $sql	= "SELECT member_id, group_id FROM exp_members WHERE group_id != '1'";
		
		if ( $all === FALSE )
		{
			$sql	.= " AND group_id IN (";
			
			foreach ( $editable_group as $group )
			{
				$sql	.= "'".ee()->db->escape_str( $group )."',";
			}
			
			$sql	= rtrim( $sql, "," );
			
			$sql	.= ")";
		}
		
		$sql	.= " AND member_id IN (";
		
		foreach ( array_keys( $members ) as $member )
		{
			$sql	.= "'".ee()->db->escape_str( $member )."',";
		}
		
		$sql	= rtrim( $sql, "," );
		
		$sql	.= ")";
        
        $query	= ee()->db->query( $sql );
        
        /**	----------------------------------------
        /**	Validate
        /**	----------------------------------------*/
        
        if ( $query->num_rows() == 0 OR $query->num_rows() != count( $members ) )
        {
        	return $this->_output_error('general', array(ee()->lang->line('member_list_error')));
        }
        
        /**	----------------------------------------
        /**	Check Form Hash
        /**	----------------------------------------*/
        
        if ( ee()->config->item('secure_forms') == 'y' )
        {
            $secure = ee()->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes 
            								WHERE hash='".ee()->db->escape_str($_POST['XID'])."' 
            								AND ip_address = '".ee()->input->ip_address()."' 
            								AND date > UNIX_TIMESTAMP()-7200");
        
            if ($secure->row('count') == 0)
            {
				return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
            }
                                
			ee()->db->query("DELETE FROM exp_security_hashes 
								  WHERE (hash='".ee()->db->escape_str($_POST['XID'])."' 
								  AND ip_address = '".ee()->input->ip_address()."') 
								  OR date < UNIX_TIMESTAMP()-7200");
        }
        
        /**	----------------------------------------
        /**	Loop and update
        /**	----------------------------------------*/
        
        foreach ( $query->result_array() as $row )
        {
        	$data	= array();
        
			/**	----------------------------------------
			/**	Modify group id?
			/**	----------------------------------------*/
			
			if ( isset( $members[$row['member_id']]['group_id'] ) === TRUE AND $members[$row['member_id']]['group_id'] != $row['group_id'] AND in_array( $members[$row['member_id']]['group_id'], $editable_group ) === TRUE )
			{
				$data['group_id']	= $members[$row['member_id']]['group_id'];
			}
        
			/**	----------------------------------------
			/**	Modify standard field?
			/**	----------------------------------------*/
			
			foreach ( $members[$row['member_id']] as $key => $val )
			{
				if ( in_array( $key, $this->standard ) )
				{
					$data[$key]	= $val;
				}
			}
        
			/**	----------------------------------------
			/**	Update DB
			/**	----------------------------------------*/
			
			ee()->db->query( ee()->db->update_string( 'exp_members', $data, array( 'member_id' => $row['member_id'] ) ) );
        }
        
        /**	----------------------------------------
        /**	 Override Return
		/**	----------------------------------------*/
		
		if ( $this->_param('override_return') !== FALSE AND $this->_param('override_return') != '' )
		{	
			ee()->functions->redirect( $this->_param('override_return') );
			exit;
		}
		
		/**	----------------------------------------
		/**	Set return
		/**	----------------------------------------*/
        
        if ( ee()->input->get_post('return') !== FALSE AND ee()->input->get_post('return') != '' )
        {
        	$return	= ee()->input->get_post('return');
        }
        elseif ( ee()->input->get_post('RET') !== FALSE AND ee()->input->get_post('RET') != '' )
        {
        	$return	= ee()->input->get_post('RET');
        }
        else
        {
        	$return = ee()->config->item('site_url');
        }
		
		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		elseif ( stristr( $return, 'http://' ) === FALSE )
		{
			$return	= ee()->functions->create_url( $return );
		}
		
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		$return	= $this->_chars_decode( $return );
        
		ee()->functions->redirect( $return );
	}
	
	/* END group edit */
    
    // --------------------------------------------------------------------

	/**
	 *	The Register Form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function register()
    {
		/**	----------------------------------------
		/**	Allow registration?
		/**	----------------------------------------*/
		
		if ( ee()->config->item('allow_member_registration') != 'y' )
		{
			return $this->_output_error('general', array(ee()->lang->line('registration_not_enabled')));
		}
        
        /**	----------------------------------------
        /**	Is the current user logged in?
        /**	----------------------------------------*/
        
		if ( ee()->session->userdata('member_id') != 0 && ee()->TMPL->fetch_param('admin_register') !== 'yes')
		{ 
			if (ee()->TMPL->fetch_param('admin_register') !== 'yes' OR (ee()->session->userdata['group_id'] != 1 && ee()->session->userdata['can_admin_members'] !== 'y'))
			{
				// In case the registration form is on a page with other content, we don't want to
				// seize control and output an error.
				return $this->no_results('user');
				//return $this->_output_error('general', array(ee()->lang->line('mbr_you_are_registered')));
			}
		}
		
		/**	----------------------------------------
		/**	Userdata
		/**	----------------------------------------*/
		
		$tagdata			= ee()->TMPL->tagdata;
    	
		/**	----------------------------------------
		/**	Grab key from url
		/**	----------------------------------------*/
		
		if ( preg_match( "#/".self::$key_trigger."/(\w+)/?#", ee()->uri->uri_string, $match ) )
		{
			$tagdata		= ee()->TMPL->swap_var_single( 'key', $match['1'], $tagdata );
		}
		else
		{
			$tagdata		= ee()->TMPL->swap_var_single( 'key', '', $tagdata );
		}
    	
		/**	----------------------------------------
		/**	Handle categories
		/**	----------------------------------------*/
		
		$tagdata	= $this->_categories( $tagdata );
    
        /**	----------------------------------------
        /**	 Parse conditional pairs
        /**	----------------------------------------*/
        
        $cond['captcha'] = FALSE;
        
        if (ee()->config->item('use_membership_captcha') == 'y')
        {
        	$cond['captcha'] =  (ee()->config->item('captcha_require_members') == 'y'  || 
								(ee()->config->item('captcha_require_members') == 'n' AND ee()->session->userdata('member_id') == 0)) ? 'TRUE' : 'FALSE';  
        }
        
		$tagdata			= ee()->functions->prep_conditionals( $tagdata, $cond );
		
		/**	----------------------------------------
		/**	Parse var pairs
		/**	----------------------------------------*/
		
		foreach ( ee()->TMPL->var_pair as $key => $val )
		{
			/** --------------------------------------------
			/**  Member Groups Select List
			/** --------------------------------------------*/
			
			if ($key == 'select_member_groups')
			{
				if (ee()->TMPL->fetch_param('allowed_groups') !== FALSE)
				{
					$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $key );
				
					$tagdata	= preg_replace( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", 
												str_replace('$', '\$', $this->_parse_select_member_groups( $data )), 
												$tagdata );
				}
				else
				{
					$tagdata	= preg_replace( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", '', $tagdata);
				}
			}
			
			/** --------------------------------------------
			/**  Mailing Lists Select List
			/** --------------------------------------------*/
			
			if ($key == 'select_mailing_lists')
			{
				$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $key );
				
				$tagdata	= preg_replace( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", 
											str_replace('$', '\$', $this->_parse_select_mailing_lists( $data, array() )), 
											$tagdata );
			}
			
			/**	----------------------------------------
			/**	Languages
			/**	----------------------------------------*/
			
			if ( $key == 'languages' )
			{
				$dirs = array();
		
				if ($fp = @opendir($this->lang_dir))
				{
					while (FALSE !== ($file = readdir($fp)))
					{
						if (is_dir($this->lang_dir.$file) && substr($file, 0, 1) != ".")
						{
							$dirs[] = $file;
						}
					}
					closedir($fp);
				}
		
				sort($dirs);
		
				preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote(T_SLASH, '/').$key.RD."/s", $tagdata, $match );
				$r	= '';
				
				foreach ( $dirs as $key )
				{
					$out		= $match['1'];
					
					$checked	= ( isset($query_row['language'] ) AND $query_row['language'] == $key ) ? 'checked="checked"': '';
					
					$selected	= ( isset($query_row['language'] ) AND $query_row['language'] == $key ) ? 'selected="selected"': '';
					
					$out		= str_replace( LD."language_name".RD, $key, $out );
					$out		= str_replace( LD."language_label".RD, ucfirst( $key ), $out );
					$out		= str_replace( LD."checked".RD, $checked, $out );
					$out		= str_replace( LD."selected".RD, $selected, $out );
					
					$r	.= $out;
				}
				
				$tagdata	= str_replace( $match[0], $r, $tagdata );
			}
		}
		
		/**	----------------------------------------
		/**	Parse selects
		/**	----------------------------------------*/
		
		foreach ( $this->_mfields() as $key => $val )
		{
			/**	----------------------------------------
			/**	Parse select
			/**	----------------------------------------*/
			
			foreach ( ee()->TMPL->var_pair as $k => $v )
			{
				if ( $k == "select_".$key )
				{
					$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $k );
					
					$tagdata	= preg_replace( "/".LD.preg_quote($k, '/').RD."(.*?)".LD.preg_quote(T_SLASH, '/').preg_quote($k, '/').RD."/s", 
												str_replace('$', '\$', $this->_parse_select( $key, array(), $data )), 
												$tagdata );
				}
			}
		}
    	
		/**	----------------------------------------
		/**	Sniff for fields of type 'file'
		/**	----------------------------------------*/
		
		if ( preg_match( "/type=['|\"]?file['|\"]?/", $tagdata, $match ) )
		{
			$this->multipart	= TRUE;
		}
        
		/**	----------------------------------------
        /**	Do we just want the parsing and no
        /**	form?
		/**	----------------------------------------*/
		
		if ( ee()->TMPL->fetch_param( 'no_form' ) == "yes" )
		{
			return $tagdata;
		}
    	
		/**	----------------------------------------
		/**	Prep data
		/**	----------------------------------------*/
		
		$this->form_data['tagdata']					= $tagdata;
		
		$this->form_data['ACT']						= ee()->functions->fetch_action_id('User', 'reg');
		
		if (isset($_POST['RET']))
		{
			 $this->form_data['RET'] = $_POST['RET'];
		}
		elseif(ee()->TMPL->fetch_param('return') !== FALSE)
		{
			$this->form_data['RET'] = ee()->TMPL->fetch_param('return');
		}
		else
		{
			$this->form_data['RET'] = ee()->functions->fetch_current_uri();
		}
		
		if ( ee()->TMPL->fetch_param('form_name') !== FALSE && ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
		}
		
		$this->form_data['id']						= ( ee()->TMPL->fetch_param('form_id') !== FALSE ) ? ee()->TMPL->fetch_param('form_id'): 'member_form';
		
		$this->params['group_id']				= ( ee()->TMPL->fetch_param('group_id') !== FALSE ) ? ee()->TMPL->fetch_param('group_id'): '';
		
		$this->params['notify']					= ( ee()->TMPL->fetch_param('notify') !== FALSE ) ? ee()->TMPL->fetch_param('notify'): '';
		
		$this->params['screen_name_override']	= ( ee()->TMPL->fetch_param('screen_name') !== FALSE ) ? ee()->TMPL->fetch_param('screen_name'): '';
		
		$this->params['exclude_username']		= ( ee()->TMPL->fetch_param('exclude_username') ) ? ee()->TMPL->fetch_param('exclude_username'): '';
		
		$this->params['require_key']			= ( ee()->TMPL->fetch_param('require_key') ) ? ee()->TMPL->fetch_param('require_key'): '';
		
		$this->params['key_email_match']		= ( ee()->TMPL->fetch_param('key_email_match') ) ? ee()->TMPL->fetch_param('key_email_match'): '';
		
		$this->params['key']					= ( ee()->TMPL->fetch_param('key') != '' ) ? ee()->TMPL->fetch_param('key'): '';
		
		$this->params['secure_action']			= ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';
		
		$this->params['admin_register']			= ( ee()->TMPL->fetch_param('admin_register') !== FALSE) ? ee()->TMPL->fetch_param('admin_register'): 'no';
		
		$this->params['required']				= ( ee()->TMPL->fetch_param('required') ) ? ee()->TMPL->fetch_param('required'): '';
		
		if (ee()->TMPL->fetch_param('allowed_groups') !== FALSE && ee()->TMPL->fetch_param('allowed_groups') != '')
		{
			$this->params['allowed_groups']		=  ee()->TMPL->fetch_param('allowed_groups');
    	}
    	
		$this->params['required']				= ( ee()->TMPL->fetch_param('required') ) ? ee()->TMPL->fetch_param('required'): '';
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
        
		return $this->_form();
	}
	
	/* END register */
	
    
    // --------------------------------------------------------------------

	/**
	 *	Registration Form Processing
	 *
	 *	@access		public
	 *	@param		bool
	 *	@return		string
	 */

	public function reg( $remote = FALSE )
    {
    	ee()->load->helper('url'); // For prep_url();
        
        $key_id	= '';
        
        /**	----------------------------------------
        /**	Do we allow new member registrations?
        /**	----------------------------------------*/        
        
		if (ee()->config->item('allow_member_registration') == 'n')
		{
            return $this->_output_error('general', array(ee()->lang->line('registration_not_enabled')));
        }
        
        /** --------------------------------------------
        /**  Allowed to Register
        /** --------------------------------------------*/
        
        if ( ee()->session->userdata('member_id') != 0)
		{ 
			if ($this->_param('admin_register') !== 'yes' OR (ee()->session->userdata['group_id'] != 1 && ee()->session->userdata['can_admin_members'] !== 'y'))
			{
				return $this->_output_error('general', array(ee()->lang->line('mbr_you_are_registered')));
			}
		}

        /**	----------------------------------------
        /**	Is user banned?
        /**	----------------------------------------*/
        
        if (ee()->session->userdata['is_banned'] == TRUE)
		{
            return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
		}	
		
		/**	----------------------------------------
        /**	Blacklist/Whitelist Check
        /**	----------------------------------------*/
        
        if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n')
        {
        	return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
        }
        
        /**	----------------------------------------
        /**	Clean the post
        /**	----------------------------------------*/

        //need to protect passwords from this because they get hashed anyway
		$temp_pass 	= isset($_POST['password']) 		? $_POST['password'] 		 : FALSE;
		$temp_pass2 = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : FALSE;

        $_POST	= ee()->security->xss_clean( $_POST );
        
		//make sure the password is actually set
		if ( ! in_array($temp_pass, array(FALSE, ''), TRUE))
		{
			$_POST['password'] = $temp_pass;
		}

		//make sure the password is actually set
		if ( ! in_array($temp_pass2, array(FALSE, ''), TRUE))
		{
			$_POST['password_confirm'] = $temp_pass2;
		}

        /** --------------------------------------------
        /**  Email as Username Preference
        /** --------------------------------------------*/
        
        $wquery = ee()->db->query("SELECT preference_value FROM exp_user_preferences WHERE preference_name = 'email_is_username'");
				
		$this->preferences['email_is_username'] = ($wquery->num_rows() == 0) ? 'n' : $wquery->row('preference_value');
        
        /**	----------------------------------------
        /**	Check email is username
        /**	----------------------------------------*/
        
        $this->_email_is_username( '0', 'new' );
        
        /**	----------------------------------------
        /**	Empty email?
        /**	----------------------------------------*/
        
        if ( ! ee()->input->get_post('email') )
        {
        	return $this->_output_error('general', array(ee()->lang->line('email_required')));
        }
        
        /* -------------------------------------------
		/* 'user_register_start' hook.
		/*  - Take control of member registration routine
		*/
		if (ee()->extensions->active_hook('user_register_start') === TRUE)
		{
			$edata = ee()->extensions->universal_call('user_register_start', $this);
			if (ee()->extensions->end_script === TRUE) return;
		}
		/*
		/* -------------------------------------------*/

        /**	----------------------------------------
        /**	Set the default globals
        /**	----------------------------------------*/
        
        $default = array_merge( array('username', 'password', 'password_confirm', 'email', 'screen_name' ), $this->standard );
                
        foreach ($default as $val)
        {
        	if ( ! isset($_POST[$val])) $_POST[$val] = '';
        }
        
        /**	----------------------------------------
        /**	Check screen name override
        /**	----------------------------------------*/
        
        $this->_screen_name_override();
        
        /**	----------------------------------------
        /**	Handle alternate username / screen name
        /**	----------------------------------------*/
        
        if ( ee()->input->post('username') == '' && $this->preferences['email_is_username'] == 'y' )
        {
        	$_POST['username']	= ee()->input->get_post('email');
        }
        
        if ( ! ee()->input->get_post('screen_name') OR ee()->input->get_post('screen_name') == '' )
        {
        	$_POST['screen_name']	= $_POST['username'];
        }
        
        /**	----------------------------------------
        /**	Check prohibited usernames
        /**	----------------------------------------*/
	
		if (ee()->session->ban_check('username', $_POST['username']))
		{
			return $this->_output_error('general', array(ee()->lang->line('prohibited_username')));
		}
		
		if ($this->_param('exclude_username') != '' && in_array($_POST['username'], explode('|', $this->_param('exclude_username'))))
		{
			return $this->_output_error('general', array(ee()->lang->line('prohibited_username')));
		}
		
		/**	----------------------------------------
        /**   Required Fields
        /**	----------------------------------------*/
        
		if ( $this->_param('required') !== FALSE)
		{
        	$this->_mfields();
        		
			$missing	= array();
			
			$required	= preg_split( "/,|\|/", $this->_param('required') );
        	
			foreach ( $required as $req )
			{
				if ( $req == 'all_required')
				{
					foreach ( $this->mfields as $key => $val )
					{
						if ( ! ee()->input->get_post($key) AND $val['required'] == 'y' )
						{
							$missing[]	= $this->mfields[$key]['label'];
						}
					}
				}
				elseif ( ! ee()->input->get_post($req) )
				{
					if (isset( $this->mfields[$req] ) )
					{
						$missing[]	= $this->mfields[$req]['label'];
					}
					elseif (in_array($req, $this->standard))
					{
						if (in_array($req, array('bday_d', 'bday_m', 'bday_y')))
						{
							$missing[]	= ee()->lang->line('mbr_birthday');	
						}
						elseif ($req == 'daylight_savings')
						{
							$missing[] = ee()->lang->line('daylight_savings_time');
						}
						elseif(in_array($req, array('aol_im', 'yahoo_im', 'msn_im', 'icq', 'signature' )))
						{
							$missing[]	= ee()->lang->line($req);	
						}
						else
						{
							$missing[]	= ee()->lang->line('mbr_'.$req);	
						}
					}
				}
        	}
			
			/**	----------------------------------------
			/**	Anything missing?
			/**	----------------------------------------*/
			
			if ( count( $missing ) > 0 )
			{
				$missing	= implode( "</li><li>", $missing );
				
				$str		= str_replace( "%fields%", $missing, ee()->lang->line('missing_fields') );
				
				return $this->_output_error('general', $str);
			}        	
        }
        
        /**	----------------------------------------
        /**	Instantiate validation class
        /**	----------------------------------------*/

		ee()->load->library('validate', array( 
													'member_id'			=> '',
													'val_type'			=> 'new', // new or update
													'fetch_lang' 		=> TRUE, 
													'require_cpw' 		=> FALSE,
													'enable_log'		=> FALSE,
													'username'			=> $_POST['username'],
													'cur_username'		=> '',
													'screen_name'		=> stripslashes($_POST['screen_name']),
													'cur_screen_name'	=> '',
													'password'			=> $_POST['password'],
													'password_confirm'	=> $_POST['password_confirm'],
													'cur_password'		=> '',
													'email'				=> $_POST['email'],
													'cur_email'			=> ''
												 ),
												 'validate'
										);
					
		ee()->validate->validate_username();
		ee()->validate->validate_screen_name();
		ee()->validate->validate_password();
		ee()->validate->validate_email();
		
		if ($this->preferences['email_is_username'] != 'n' && ($key = array_search(ee()->lang->line('username_password_too_long'), ee()->validate->errors)) !== FALSE)
		{
			if (strlen(ee()->validate->username) <= 50)
			{
				unset(ee()->validate->errors[$key]);
			}
			else
			{
				ee()->validate->errors[$key] = str_replace('32', '50', ee()->validate->errors[$key]);	
			}
		}

        /**	----------------------------------------
        /**	Do we have any custom fields?
        /**	----------------------------------------*/
        
        $cust_errors = array();
        $cust_fields = array();
        $fields		 = '';
        
        if ( count( $this->_mfields() ) > 0 )
        {
        	foreach ( $this->mfields as $key => $val )
        	{
        		if ( $val['required'] == 'y' AND ! ee()->input->get_post($key) )
        		{
					$fields	.=	"<li>".$val['label']."</li>";
        		}
        		
				if ( isset( $_POST[ $key ] ) )
				{        
					/**	----------------------------------------
					/**	Handle arrays
					/**	----------------------------------------*/
					
					if ( is_array( $_POST[ $key ] ) )
					{
						$cust_fields['m_field_id_'.$val['id']] =  implode( "\n", $_POST[ $key ] );
					}
					else
					{
						$cust_fields['m_field_id_'.$val['id']] = $_POST[ $key ];
					}
				}
        	}
        	
        	if ( $fields != '' )
        	{
				$cust_errors[] = str_replace( "%s", $fields, ee()->lang->line('user_field_required') );
        	}
        }
        
        /**	----------------------------------------
        /**	Assemble custom fields
        /**	----------------------------------------*/
        
        $cfields	= array();
        
        foreach ( $this->_mfields() as $key => $val )
        {
        	if ( isset( $_POST[ $key ] ) )
        	{        
				/**	----------------------------------------
				/**	Handle arrays
				/**	----------------------------------------*/
				
				if ( is_array( $_POST[ $key ] ) )
				{
					$cfields['m_field_id_'.$val['id']]	= implode( "\n", $_POST[ $key ] );
				}
				else
				{
					$cfields['m_field_id_'.$val['id']]	= $_POST[ $key ];
				}
        	}
        }
        
		
		if (ee()->config->item('use_membership_captcha') == 'y')
		{
			if (ee()->config->item('captcha_require_members') == 'y'  ||  (ee()->config->item('captcha_require_members') == 'n' AND ee()->session->userdata('member_id') == 0))
			{
				if ( ! isset($_POST['captcha']) || $_POST['captcha'] == '')
				{
					$cust_errors[] = ee()->lang->line('captcha_required');
				}
			}
		}		
        
        if (ee()->config->item('require_terms_of_service') == 'y')
        {
			if ( ! isset($_POST['accept_terms']))
			{
				$cust_errors[] = ee()->lang->line('mbr_terms_of_service_required');
			}
        }
                
		$errors = array_merge(ee()->validate->errors, $cust_errors);
		
		
		/** --------------------------------------------
        /**	 'user_register_error_checking' Extension Hook
        /**		- Error checking
        /**		- Added User 2.0.9
       /** --------------------------------------------*/
       
		if (ee()->extensions->active_hook('user_register_error_checking') === TRUE)
		{
			$errors = ee()->extensions->universal_call('user_register_error_checking', $this, $errors);
			if (ee()->extensions->end_script === TRUE) return;
		}
	
        /**	----------------------------------------
        /**	 Output Errors
        /**	----------------------------------------*/

         if (count($errors) > 0)
         {
			return $this->_output_error('submission', $errors);
         }
         
        /**	----------------------------------------
        /**	Do we require a key?
        /**	----------------------------------------*/
		
		if ( $this->_param('require_key') == 'yes' OR 
			 $this->_param('key_email_match') == 'yes' )
		{
			/**	----------------------------------------
			/**	No key?
			/**	----------------------------------------*/
			
			if ( ! ee()->input->post('key') )
			{
				return $this->_output_error('submission', array(ee()->lang->line('key_required')));
			}
			
			/**	----------------------------------------
			/**	Key and email match required?
			/**	----------------------------------------*/
			
			if ( $this->_param('key_email_match') == 'yes' AND ! ee()->input->get_post('email') )
			{
				return $this->_output_error('submission', array(ee()->lang->line('key_email_match_required')));
			}
			
			/**	----------------------------------------
			/**	Query
			/**	----------------------------------------*/
			
			$sql	= "SELECT key_id FROM exp_user_keys 
					   WHERE member_id = '0' 
					   AND hash = '".ee()->db->escape_str( ee()->input->get_post('key') )."'";
			
			if ( $this->_param('key_email_match') == 'yes' )
			{
				$sql	.= " AND email = '".ee()->db->escape_str( ee()->input->get_post('email') )."'";
			}
			
			$query	= ee()->db->query( $sql );
		
            if ( $query->num_rows() == 0 )
            {
            	$query = ee()->db->query("SELECT preference_value FROM exp_user_preferences WHERE preference_name = 'key_expiration' LIMIT 1");
            
            	$exp = ( $query->num_rows() > 0 ) ? $query->row('preference_value') : $exp;
            	
				return $this->_output_error('submission', array( str_replace( "%s", $exp, ee()->lang->line('key_incorrect'))));
			}
			
			$key_id	= $query->row('key_id');
		}
        
        /**	----------------------------------------
        /**	Set member group
        /**	----------------------------------------*/
                        
        if (ee()->config->item('req_mbr_activation') == 'manual' || ee()->config->item('req_mbr_activation') == 'email')
        {
        	$this->insert_data['group_id'] = 4;  // Pending
        }
        else
        {
        	if (ee()->config->item('default_member_group') == '')
        	{
				$this->insert_data['group_id'] = 4;  // Pending
        	}
        	else
        	{
				$this->insert_data['group_id'] = ee()->config->item('default_member_group');
        	}
        }
        
        /**	----------------------------------------
        /**	Override member group if hard coded
        /**	----------------------------------------*/
        
        if ( $this->_param('group_id') AND is_numeric( $this->_param('group_id') ) AND $this->_param('group_id') != '1' )
        {
        	// Email and Manual Activation will use the exp_user_activation_group table to change group.
        	
        	if (ee()->config->item('req_mbr_activation') != 'email' && ee()->config->item('req_mbr_activation') != 'manual')
        	{
				$this->insert_data['group_id']	= $this->_param('group_id');
			}
        }
        
        /**	----------------------------------------
        /**	Override member group if invitation
        /**	code provided and valid.
        /**	----------------------------------------*/
        
        if ( $key_id != '' AND $key_id != '1' )
        {
        	$key	= ee()->db->query( "SELECT k.group_id FROM exp_user_keys AS k 
        						   JOIN exp_member_groups AS g ON g.group_id = k.group_id 
        						   WHERE k.key_id = '".ee()->db->escape_str($key_id)."' 
        						   AND k.group_id NOT IN (0, 1)" );
        	
        	if ( $key->num_rows() > 0 )
        	{
        		if (ee()->config->item('req_mbr_activation') == 'email' OR ee()->config->item('req_mbr_activation') == 'manual')
    			{
    				$this->params['group_id'] = $key->row('group_id');
    			}
    			else
    			{
    				$this->insert_data['group_id'] = $key->row('group_id');
    			}
        	}
        }
        
        /** --------------------------------------------
        /**  Submitted Group ID, Restricted by allowed_groups=""
        /** --------------------------------------------*/
        
        if(ee()->input->post('group_id') !== FALSE && ctype_digit(ee()->input->post('group_id')) && $this->_param('allowed_groups') )
        {
        	$sql = "SELECT DISTINCT group_id FROM exp_member_groups
    				WHERE group_id NOT IN (1,2,3,4) 
    				AND group_id = '".ee()->db->escape_str(ee()->input->post('group_id'))."'
    				".ee()->functions->sql_andor_string( $this->_param('allowed_groups'), 'group_id');
    			
    		$mquery = ee()->db->query($sql);
    	
    		if ($mquery->num_rows() > 0)
    		{
    			if (ee()->config->item('req_mbr_activation') == 'email' OR ee()->config->item('req_mbr_activation') == 'manual')
    			{
    				$this->params['group_id'] = $mquery->row('group_id');
    			}
    			else
    			{
    				$this->insert_data['group_id'] = $mquery->row('group_id');
    			}
    		}
        }
        
        /**	----------------------------------------
        /**	Double check that member group is real
        /**	----------------------------------------*/
        
        $query	= ee()->db->query("SELECT COUNT(*) AS count FROM exp_member_groups
        					  WHERE group_id != '1' AND group_id = '".ee()->db->escape_str($this->insert_data['group_id'])."'" );
        
        if ( $query->row('count') == 0 )
        {
			return $this->_output_error('submission', array(ee()->lang->line('invalid_member_group')));
        }
        
        /** --------------------------------------------
        /**  Test Image Uploads
        /** --------------------------------------------*/
        
        $this->_upload_images( 0, TRUE );
        
		/**	----------------------------------------
        /**	Do we require captcha?
        /**	----------------------------------------*/
		
		if (ee()->config->item('use_membership_captcha') == 'y')
		{	
			if (ee()->config->item('captcha_require_members') == 'y'  ||  (ee()->config->item('captcha_require_members') == 'n' AND ee()->session->userdata('member_id') == 0))
			{
				$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_captcha 
											   WHERE word='".ee()->db->escape_str($_POST['captcha'])."' 
											   AND ip_address = '".ee()->input->ip_address()."' 
											   AND date > UNIX_TIMESTAMP()-7200");
			
				if ($query->row('count') == 0)
				{
					return $this->_output_error('submission', array(ee()->lang->line('captcha_incorrect')));
				}
			
				ee()->db->query("DELETE FROM exp_captcha 
									  WHERE (word='".ee()->db->escape_str($_POST['captcha'])."' 
									  AND ip_address = '".ee()->input->ip_address()."') 
									  OR date < UNIX_TIMESTAMP()-7200");
			}
		}
        		
        /**	----------------------------------------
        /**	Secure Mode Forms?
        /**	----------------------------------------*/
		
        if (ee()->config->item('secure_forms') == 'y')
        {
            $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes 
            					 WHERE hash='".ee()->db->escape_str($_POST['XID'])."' 
            					 AND ip_address = '".ee()->input->ip_address()."'
            					 AND date > UNIX_TIMESTAMP()-7200");
        
            if ($query->row('count') == 0)
            {
				return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
			}
		
			/**	----------------------------------------
			/**	Delete secure hash?
			/**	----------------------------------------
			/*	The reg() function is also assisting the remote_registration routine. That
			/*	routine receives form submissions from comment and rating forms. If we delete
			/*	the secure hash now, those forms will fail when they do their security check.
			/*	So we don't delete in the case of remote reg.
			/**	----------------------------------------*/
			
			if ( $remote === FALSE )
			{	
				ee()->db->query("DELETE FROM exp_security_hashes 
							WHERE (hash='".ee()->db->escape_str($_POST['XID'])."' 
							AND ip_address = '".ee()->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
			}
		}
                  
        /**	----------------------------------------
        /**	Assign the base query data
        /**	----------------------------------------*/
                 
        $this->insert_data['username']    = $_POST['username'];
        $this->insert_data['password']    = ee()->functions->hash(stripslashes($_POST['password']));
        $this->insert_data['ip_address']  = ee()->input->ip_address();
        $this->insert_data['unique_id']   = ee()->functions->random('encrypt');
        $this->insert_data['join_date']   = ee()->localize->now;
        $this->insert_data['email']       = $_POST['email'];
        $this->insert_data['screen_name'] = $_POST['screen_name'];
        
        /**	----------------------------------------
        /**	Optional Fields
        /**	----------------------------------------*/
        
        $optional	= array('language'			=> 'deft_lang', 
        					'timezone'			=> 'server_timezone', 
        					'time_format'		=> 'time_format');
        
        foreach($optional as $key => $value)
        {
        	if (isset($_POST[$value]))
        	{
        		$this->insert_data[$key] = $_POST[$value];
        	}
        }
        
        foreach($this->standard as $key)
        {
        	if (isset($_POST[$key]))
        	{
        		$this->insert_data[$key] = $_POST[$key];
        	}
        }
        
        $this->insert_data['url']				= prep_url($_POST['url']);
        
        $this->insert_data['daylight_savings']	= (ee()->input->post('daylight_savings') == 'y') ? 'y' : 'n';
        
        // We generate an authorization code if the member needs to self-activate
        
		if (ee()->config->item('req_mbr_activation') == 'email')
		{
			$this->insert_data['authcode'] = ee()->functions->random('alpha', 10);
		}
		
		// Default timezone
		if ( ! isset($this->insert_data['timezone']))
		{
			$this->insert_data['timezone'] = 'UTC';
		}
		        
        /**	----------------------------------------
        /**	Insert basic member data
        /**	----------------------------------------*/

        ee()->db->query(ee()->db->insert_string('exp_members', $this->insert_data)); 
        
        $member_id = ee()->db->insert_id();

		//running a second time to get the member_id correct
		$this->_screen_name_override($member_id);
         
        /**	----------------------------------------
        /**	Insert custom fields
        /**	----------------------------------------*/

		$cust_fields['member_id'] = $member_id;
											   
		ee()->db->query(ee()->db->insert_string('exp_member_data', $cust_fields));
		
		/**	----------------------------------------
        /**	Member Group Override on Activation
        /**	----------------------------------------*/
        
        if ( $this->_param('group_id') AND is_numeric( $this->_param('group_id') ) AND $this->_param('group_id') != '1' )
        {
        	if (ee()->config->item('req_mbr_activation') == 'email' OR ee()->config->item('req_mbr_activation') == 'manual')
        	{
				ee()->db->query(ee()->db->insert_string('exp_user_activation_group', array('member_id' => $member_id, 
																				 'group_id' => $this->_param('group_id'))));
			}
        }
                    
        /** ---------------------------------
        /**	Fetch categories
        /** ---------------------------------*/
                        
        if ( isset( $_POST['category']))
        {
        	if (is_array( $_POST['category'] ))
        	{
				foreach ( $_POST['category'] as $cat_id )
				{
					$this->cat_parents[] = $cat_id;
				}
			}
			elseif (is_numeric($_POST['category']))
			{
				$this->cat_parents = $_POST['category'];
			}
		}
			
		if (sizeof($this->cat_parents) > 0)
		{
			if ( ee()->config->item('auto_assign_cat_parents') == 'y' )
			{
				$this->_fetch_category_parents( $this->cat_parents );            
			}
        }
        
		unset( $_POST['category'] );
		
		ee()->db->query( "DELETE FROM exp_user_category_posts WHERE member_id = '".$member_id."'" );
		
		foreach ( $this->cat_parents as $cat_id )
		{
			ee()->db->query( ee()->db->insert_string( 'exp_user_category_posts',
											array(	'member_id' => $member_id, 
													'cat_id' => $cat_id ) ) );
		}
        
        /**	----------------------------------------
        /**	Handle image uploads
        /**	----------------------------------------*/
        
		$this->_upload_images( $member_id );
         
        /**	----------------------------------------
        /**	Update key table
        /**	----------------------------------------*/
        
        if ( $key_id != '' )
        {			
			ee()->db->query( ee()->db->update_string( 'exp_user_keys', array( 'member_id' => $member_id ), array( 'key_id' => $key_id ) ) );
		}

        /**	----------------------------------------
        /**	Create a record in the member
        /**	homepage table
        /**	----------------------------------------*/

		// This is only necessary if the user gains CP access, but we'll add the record anyway.            
                           
        ee()->db->query(ee()->db->insert_string('exp_member_homepage', array('member_id' => $member_id)));
        
        /** --------------------------------------------
        /**  Set Language Variable
        /** --------------------------------------------*/
        
        if ( isset($_POST['language']) && preg_match("/^[a-z]+$/", $_POST['language']))
        {
        	ee()->session->userdata['language'] = $_POST['language'];
        }
        
        /**	----------------------------------------
        /**	Mailinglist Subscribe
        /**	----------------------------------------*/
        
        $mailinglist_subscribe = FALSE;
        
        if (isset($_POST['mailinglist_subscribe']) && (is_array($_POST['mailinglist_subscribe']) OR is_numeric($_POST['mailinglist_subscribe'])))
		{
			// Kill duplicate emails from authorizatin queue.
			ee()->db->query("DELETE FROM exp_mailing_list_queue WHERE email = '".ee()->db->escape_str($_POST['email'])."'");
			
			$lists = (is_array($_POST['mailinglist_subscribe'])) ? $_POST['mailinglist_subscribe'] : array($_POST['mailinglist_subscribe']);

			foreach($lists as $list_id)
			{
				// Validate Mailing List ID
				$query = ee()->db->query("SELECT list_title
									 FROM exp_mailing_lists 
									 WHERE list_id = '".ee()->db->escape_str($list_id)."'");
				
				// Email Not Already in Mailing List
				$results = ee()->db->query("SELECT count(*) AS count 
									   FROM exp_mailing_list 
									   WHERE email = '".ee()->db->escape_str($_POST['email'])."' 
									   AND list_id = '".ee()->db->escape_str($list_id)."'");
				
				/**	----------------------------------------
				/**	INSERT Email
				/**	----------------------------------------*/
				
				if ($query->num_rows() > 0 && $results->row('count') == 0)
				{	
					$code = ee()->functions->random('alpha', 10);
					
					// The User module still does member activation through the Member module, 
					// which does not allow one to activate MORE THAN ONE Mailing List subscription
					// per registration.  So, what we do is if member activation is not automatic
					// AND there is more than one mailing list being subscribed to, then we require 
					// activation of mailing list subscription on an individual basis through the
					// Mailing List module. -Paul
					
					if (ee()->config->item('req_mbr_activation') == 'email' && sizeof($lists) == 1)
					{
						$mailinglist_subscribe = TRUE;
						
						// Activated When Membership Activated
						ee()->db->query("INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date) 
									VALUES ('".ee()->db->escape_str($_POST['email'])."', '".ee()->db->escape_str($list_id)."', '".$code."', '".time()."')");			
					}
					elseif (ee()->config->item('req_mbr_activation') == 'manual' OR ee()->config->item('req_mbr_activation') == 'email')
					{
						// Mailing List Subscribe Email
						ee()->db->query("INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date) 
									VALUES ('".ee()->db->escape_str($_POST['email'])."', '".ee()->db->escape_str($list_id)."', '".$code."', '".time()."')");			
						
						ee()->lang->loadfile('mailinglist');
						
						$qs = (ee()->config->item('force_query_string') == 'y') ? '' : '?';        
						$action_id  = ee()->functions->fetch_action_id('Mailinglist', 'authorize_email');
						
						if (APP_VER < 2.0)
						{
							$action_id = ee()->functions->insert_action_ids($action_id);
						}
				
						$swap = array(
										'activation_url'	=> ee()->functions->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$code,
										'site_name'			=> stripslashes(ee()->config->item('site_name')),
										'site_url'			=> ee()->config->item('site_url'),
										'mailing_list'		=> $query->row('list_title')
									 );
						
						$template = ee()->functions->fetch_email_template('mailinglist_activation_instructions');
						$email_tit = ee()->functions->var_swap($template['title'], $swap);
						$email_msg = ee()->functions->var_swap($template['data'], $swap);
						
						/**	----------------------------------------
						/**	Send email
						/**	----------------------------------------*/
				
						ee()->load->library('email');
						
						ee()->email->initialize();
						ee()->email->wordwrap = true;
						ee()->email->mailtype = 'plain';
						ee()->email->priority = '3';
						
						ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));	
						ee()->email->to(ee()->input->post('email')); 
						ee()->email->subject($email_tit);	
						ee()->email->message($email_msg);	
						ee()->email->Send();
					}	
					else
					{
						// Automatically Accepted
						ee()->db->query("INSERT INTO exp_mailing_list (user_id, list_id, authcode, email) 
									VALUES ('', '".ee()->db->escape_str($list_id)."', '".$code."', '".ee()->db->escape_str($_POST['email'])."')");			
					}
				}
			}
		}
		// End Mailing Lists inserts... 
		
        /**	----------------------------------------
        /**	Send admin notifications
        /**	----------------------------------------*/
        
        $notify	= ( $this->_param('notify') ) ? $this->_param('notify'): '';
	
		if ( ( ee()->config->item('new_member_notification') == 'y' AND ee()->config->item('mbr_notification_emails') != '' ) OR $notify != '' )
		{
			$name = ($this->insert_data['screen_name'] != '') ? $this->insert_data['screen_name'] : $this->insert_data['username'];
            
			$swap = array(
							'name'					=> $name,
							'site_name'				=> stripslashes(ee()->config->item('site_name')),
							'control_panel_url'		=> ee()->config->item('cp_url'),
							'username'				=> $this->insert_data['username'],
							'email'					=> $this->insert_data['email']
						 );
			
			$template = ee()->functions->fetch_email_template('admin_notify_reg');
			$email_tit = $this->_var_swap($template['title'], $swap);
			$email_msg = $this->_var_swap($template['data'], $swap);
                                    
			$notify_address = ( $notify != '' ) ? $notify: ee()->config->item('mbr_notification_emails');
			
			ee()->load->helper('string');
			
			$notify_address	= reduce_multiples( $notify_address, ',', TRUE);
                        
            /**	----------------------------------------
            /**	Send email
            /**	----------------------------------------*/
            
            ee()->load->library('email');
			ee()->load->helper('text');
                 
            ee()->email->initialize();
            ee()->email->wordwrap = true;
            ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));	
            ee()->email->to($notify_address); 
            ee()->email->subject($email_tit);	
            ee()->email->message(entities_to_ascii($email_msg));		
            ee()->email->Send();
		}
		
		/**	----------------------------------------
		/*	'user_register_end' hook.
		/*	- Additional processing when a member is created through the User Side
		/**	----------------------------------------*/
		
		$edata = ee()->extensions->call('user_register_end', $this, $member_id);
		if (ee()->extensions->end_script === TRUE) return;

		/**	----------------------------------------*/
	
        /**	----------------------------------------
        /**	Send user notifications
        /**	----------------------------------------*/

		if ( ee()->config->item('req_mbr_activation') == 'email' )
		{
			$qs = (ee()->config->item('force_query_string') == 'y') ? '' : '?';
			
			$action_id  = ee()->functions->fetch_action_id('User', 'activate_member');
			
			if (APP_VER < 2.0)
			{
				$action_id = ee()->functions->insert_action_ids($action_id);
			}
		
			$name = ($this->insert_data['screen_name'] != '') ? $this->insert_data['screen_name'] : $this->insert_data['username'];
		
			$forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f' : '';
			
			$add = ($mailinglist_subscribe !== TRUE) ? '' : '&mailinglist='.$list_id; 
				
			$swap = array(
							'name'				=> $name,
							'activation_url'	=> ee()->functions->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$this->insert_data['authcode'].$forum_id.$add,
							'site_name'			=> stripslashes(ee()->config->item('site_name')),
							'site_url'			=> ee()->config->item('site_url'),
							'username'			=> $this->insert_data['username'],
							'email'				=> $this->insert_data['email']
						 );
			
			$template = ee()->functions->fetch_email_template('mbr_activation_instructions');
			$email_tit = $this->_var_swap($template['title'], $swap);
			$email_msg = $this->_var_swap($template['data'], $swap);
                                                
            /**	----------------------------------------
            /**	Send email
            /**	----------------------------------------*/
            
            ee()->load->library('email');
			ee()->load->helper('text');
                 
            ee()->email->initialize();
            ee()->email->wordwrap = true;
            ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));	
            ee()->email->to($this->insert_data['email']); 
            ee()->email->subject($email_tit);	
            ee()->email->message(entities_to_ascii($email_msg));		
            ee()->email->Send();
            
            $message = ee()->lang->line('mbr_membership_instructions_email');		
        }
        elseif (ee()->config->item('req_mbr_activation') == 'manual')
        {
			$message = ee()->lang->line('mbr_admin_will_activate');
        }	
		elseif($this->_param('admin_register') != 'yes')
		{
			// Kill old sessions
			ee()->session->delete_old_sessions();
			
			ee()->session->gc_probability = 100;
		
			/**	----------------------------------------
			/**	Log user in
			/**	----------------------------------------*/
				
			$expire = 60*60*24*182;
					
			ee()->functions->set_cookie(ee()->session->c_anon);
			ee()->functions->set_cookie(ee()->session->c_expire , time()+$expire, $expire);
			ee()->functions->set_cookie(ee()->session->c_uniqueid , $this->insert_data['unique_id'], $expire);       
			ee()->functions->set_cookie(ee()->session->c_password , $this->insert_data['password'],  $expire);   

			/**	----------------------------------------
			/**	Create a new session
			/**	----------------------------------------*/
			
			if (ee()->config->item('user_session_type') == 'cs' OR 
				ee()->config->item('user_session_type') == 's')
			{  
				ee()->session->sdata['session_id']		= ee()->functions->random();  
				ee()->session->sdata['member_id']		= $member_id;  
				ee()->session->sdata['last_activity']	= ee()->localize->now;  
				
				ee()->session->create_new_session($member_id);
				
				// ee()->functions->set_cookie(ee()->session->c_session , ee()->session->sdata['session_id'], ee()->session->session_length);   
				
				// ee()->db->query(ee()->db->insert_string('exp_sessions', ee()->session->sdata));          
			}
			
			/**	----------------------------------------
			/**	Update existing session variables
			/**	----------------------------------------*/
			
			ee()->session->userdata['username']		= $this->insert_data['username'];
			ee()->session->userdata['email']		= $this->insert_data['email'];
			ee()->session->userdata['screen_name']	= $this->insert_data['screen_name'];
			ee()->session->userdata['url']			= $this->insert_data['url'];
			ee()->session->userdata['location']		= $this->insert_data['location'];
			ee()->session->userdata['member_id']	= $member_id;
			ee()->session->userdata['group_id']		= $this->insert_data['group_id'];
		
			/**	----------------------------------------
			/**	Update stats
			/**	----------------------------------------*/
	 
			$cutoff		= ee()->localize->now - (15 * 60);
	
			ee()->db->query("DELETE FROM exp_online_users 
							 WHERE (ip_address = '".ee()->db->escape_str(ee()->input->ip_address())."' AND member_id = '0') 
							 OR date < $cutoff");				
				
			$data = array(
				'member_id'		=> $member_id,
				'name'			=> (ee()->session->userdata['screen_name'] == '') ? 
									ee()->session->userdata['username'] : 
									ee()->session->userdata['screen_name'],
				'ip_address'	=> ee()->input->ip_address(),
				'date'			=> ee()->localize->now,
				'anon'			=> 'y'
			);
		   
			ee()->db->query(
				ee()->db->update_string(
					'exp_online_users', 
					$data, 
					array(
						"ip_address" => ee()->input->ip_address(), 
						"member_id" => $member_id
					)
				)
			);
			
			$message = ee()->lang->line('mbr_your_are_logged_in');
		}
		
		/** --------------------------------------------
        /**  Welcome Email!
        /** --------------------------------------------*/
        
     	if (ee()->config->item('req_mbr_activation') == 'manual')
        {
			// Put in a Table and Send Later!
			
			ee()->db->query(
				ee()->db->insert_string(
					'exp_user_welcome_email_list',
					array(	
						'member_id' => $member_id, 
						'group_id' => $this->insert_data['group_id']
					)
				)
			);
        }
        elseif ( ee()->config->item('req_mbr_activation') != 'email')
		{
			$this->insert_data['member_id'] = $member_id;
			$this->welcome_email($this->insert_data);
        }
		
		/**	----------------------------------------
        /**	 Override Return
		/**	----------------------------------------*/
		
		if ( $this->_param('override_return') !== FALSE AND 
			 $this->_param('override_return') != '' )
		{	
			ee()->functions->redirect( $this->_param('override_return') );
			exit;
		}
		
		/**	----------------------------------------
		/**	Set return
		/**	----------------------------------------*/
        
        if ( ee()->input->get_post('return') !== FALSE AND ee()->input->get_post('return') != '' )
        {
        	$return	= ee()->input->get_post('return');
        }
        elseif ( ee()->input->get_post('RET') !== FALSE AND ee()->input->get_post('RET') != '' )
        {
        	$return	= ee()->input->get_post('RET');
        }
        else
        {
        	$return = ee()->config->item('site_url');
        }
		
		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		$return	= $this->_chars_decode( $return );
		
		if ( $remote === FALSE)
		{
			ee()->functions->redirect( $return );
		}        
	}
	
	/* END reg */
	
	
	// --------------------------------------------------------------------

	/**
	 *	Automatic Welcome Message for New Users!
	 *
	 *	@access		public
	 *	@param		array
	 *	@param		string
	 *	@return		bool
	 */

	public function welcome_email($row)
	{
		$wquery = ee()->db->query("SELECT preference_name, preference_value FROM exp_user_preferences 
        					  	   WHERE preference_name IN ('welcome_email_subject','welcome_email_content')");
			
		if ($wquery->num_rows() == 0)
		{
			return FALSE;
		}
		
		$subject = ee()->lang->line('welcome_email_content');
		$message = '';
		
		foreach($wquery->result_array() as $wrow)
		{
			if ($wrow['preference_name'] == 'welcome_email_subject')
			{
				$subject = stripslashes($wrow['preference_value']);
			}
			else
			{
				$message = stripslashes($wrow['preference_value']);
			}
		}
		
		if ($message == '')
		{
			return FALSE;
		}
		
		/**	----------------------------------------
		/**	Send email
		/**	----------------------------------------*/
		
		ee()->load->library('email');
		
		$swap = array(LD.'site_name'.RD		=> ee()->config->item('site_name'),
					  LD.'site_url'.RD		=> ee()->config->item('site_url'),
					  LD.'screen_name'.RD	=> $row['screen_name'],
					  LD.'email'.RD			=> $row['email'],
					  LD.'username'.RD		=> $row['username'],
					  LD.'member_id'.RD		=> $row['member_id']);
		
		$message = str_replace(array_keys($swap), array_values($swap), $message);
		
		ee()->load->helper('text');
		
		ee()->email->initialize();
		ee()->email->wordwrap = true;
		ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));	
		ee()->email->to($row['email']); 
		ee()->email->subject($subject);	
		ee()->email->message(entities_to_ascii($message));		
		ee()->email->Send();
		
		ee()->db->query("DELETE FROM exp_user_welcome_email_list WHERE member_id = '".ee()->db->escape_str($row['member_id'])."'");
		
		return TRUE;
	}
	/* END welcome_message() */
	
	
	// --------------------------------------------------------------------

	/**
	 *	Member's Profile has Been Updated Email Routine
	 *
	 *	@access		private
	 *	@param		array
	 *	@param		array
	 *	@param		string
	 *	@param		string
	 *	@return		bool
	 */

	private function _member_update_email($old_data, $new_data, $emails, $message)
	{	
		if (trim($message) == '' OR trim(str_replace(',', '', $emails)) == '') return FALSE;
		
		$swap = array(LD.'site_name'.RD		=> ee()->config->item('site_name'),
					  LD.'site_url'.RD		=> ee()->config->item('site_url'));
		
		$message = str_replace(array_keys($swap), array_values($swap), $message);
		
		/** --------------------------------------------
        /**  Fields that are Disallowed
        /** --------------------------------------------*/
		
		unset($old_data['password'], $new_data['password']);
		unset($old_data['last_activity'], $new_data['last_activity']);
		
		/** --------------------------------------------
        /**  Changed Values?
        /** --------------------------------------------*/
      	
      	$this->_mfields();
      	
        if (preg_match_all("/".LD."changed(.*?)".RD."(.*?)".LD.'\/changed'.RD."/s", $message, $matches))
        {
			$changed = array();
			
			foreach($old_data as $key => $value)
			{
				if (isset($new_data[$key]) && stripslashes($new_data[$key]) != $value)
				{
					$changed[$key] = $new_data[$key];
				}
			}
			
			/** --------------------------------------------
			/**  Convert Dates to User Friendly Version
			/** --------------------------------------------*/
			
			$dates = array('last_activity');
			
			foreach($dates as $date)
			{
				if (isset($new_data[$date]))
				{
					$new_data[$date] = ee()->localize->set_human_time($new_data[$date]);
				
					if (isset($changed[$date]))
					{
						$changed[$date] = $new_data[$date];
					}
				}
			}
			
			/** --------------------------------------------
			/**  Replace!
			/** --------------------------------------------*/
			
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$result  = '';
				
				foreach($changed as $key => $value)
				{
					$content = $matches['2'][$j];
					
					if (stristr($key, 'm_field_id_') !== FALSE)
					{
						foreach($this->mfields AS $info)
						{
							if (str_replace('m_field_id_', '', $key) == $info['id'])
							{
								$new_data[$info['name']] = $value;
								
								$name = $info['label'];
							}
						}
					}
					else
					{
						if (ee()->lang->line($key) != FALSE && ee()->lang->line($key) != '')
						{
							$name = ee()->lang->line($key);
						}
						else
						{
							// This will eventually have to be replaced with an array...
							$name = ucwords(str_replace('_', ' ', $key));
						}
					}
					
					$content = str_replace(LD.'name'.RD, $name, $content);
					$content = str_replace(LD.'value'.RD, $value, $content);
				
					$result .= $content;
				}
				
				$message = str_replace($matches[0][$j], $result, $message);
			}
		}
		
		/** --------------------------------------------
        /**  New Data Replace
        /** --------------------------------------------*/
        
        if (stristr($message, '{') !== FALSE)
        {
        	foreach($new_data as $key => $value)
			{
				if (stristr($key, 'm_field_id_') !== FALSE)
				{
					foreach($this->mfields as $name => $info)
					{
						if (str_replace('m_field_id_', '', $key) == $info['id'])
						{
							$key = $info['label'];
						}
					}
				}
				
				$message = str_replace(LD.$key.RD, $value, $message);
			}
        }
        
		/** --------------------------------------------
        /**  Date of Email and Change
        /** --------------------------------------------*/
        
        $message = str_replace(LD.'update_date'.RD, ee()->localize->set_human_time(ee()->localize->now), $message);
		
		/**	----------------------------------------
		/**	Parse dates
		/**	----------------------------------------*/
                
		foreach (array('update_date' => ee()->localize->now) as $key => $val)
		{
			if (preg_match("/".LD.$key."\s+format=[\"'](.*?)[\"']".RD."/s", $message, $match))
			{
				$str	= $match['1'];
				
				$codes	= ee()->localize->fetch_date_params( $match['1'] );
				
				foreach ( $codes as $code )
				{
					$str	= str_replace( $code, ee()->localize->convert_timestamp( $code, $val, TRUE ), $str );
				}
				
				$message	= str_replace( $match[0], $str, $message );
			}
		}
        
		/**	----------------------------------------
		/**	Send email
		/**	----------------------------------------*/
		
		ee()->load->library('email');
		
		ee()->load->helper('text');
		ee()->load->helper('string');
		
		ee()->email->initialize();
		ee()->email->wordwrap = true;
		ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));	
		ee()->email->to(reduce_multiples($emails, ',', TRUE)); 
		ee()->email->subject(ee()->lang->line('member_update'));	
		ee()->email->message(entities_to_ascii($message));		
		ee()->email->Send();
		
		return TRUE;
	}
	/* END welcome_message() */
	
    // --------------------------------------------------------------------

	/**
	 *	The Validate Members functionality for the CP Hook
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		string
	 */
    
	public function cp_validate_members($member_ids = array())
    {
    	if (sizeof($member_ids) == 0)
    	{
    		return;
    	}
    	
    	/** --------------------------------------------
        /**  Retrieve Member Data
        /** --------------------------------------------*/
    	
    	$query = ee()->db->query("SELECT member_id, group_id, email, screen_name, username 
    						 FROM exp_members 
    						 WHERE member_id IN ('".implode("','", ee()->db->escape_str($member_ids))."')");        
        
        if ($query->num_rows() == 0)
        {
			return;
        }
        
        /** --------------------------------------------
        /**  Find Activation Groups
        /** --------------------------------------------*/
        
        if (ee()->db->table_exists('exp_user_activation_group'))
        {
        	$aquery = ee()->db->query("SELECT group_id, member_id FROM exp_user_activation_group 
        						  WHERE member_id IN ('".implode("','", ee()->db->escape_str($member_ids))."')
        						  AND group_id != 0");
        	
        	foreach($aquery->result_array() as $row)
        	{
        		ee()->db->query("UPDATE exp_members 
        					SET group_id = '".ee()->db->escape_str($row['group_id'])."' 
        					WHERE member_id = '".ee()->db->escape_str($row['member_id'])."'");  
        	}
        	
        	ee()->db->query("DELETE FROM exp_user_activation_group
        				WHERE member_id IN ('".implode("','", ee()->db->escape_str($member_ids))."')");
        }
		
		ee()->stats->update_member_stats();
		
		return TRUE;
    }
    /* END cp_validate_members() */
    
    // --------------------------------------------------------------------

	/**
	 *	Member Self Activation Processing
	 *
	 *	ACTION Method
	 *
	 *	@access		public
	 *	@return		string
	 */
	 
	public function activate_member()
	{
        /** ----------------------------------------
        /**  Fetch the site name and URL
        /** ----------------------------------------*/
        
		if (ee()->input->get_post('r') == 'f')
		{
			if (ee()->input->get_post('board_id') !== FALSE && is_numeric(ee()->input->get_post('board_id')))
			{
				$query	= ee()->db->query("SELECT board_forum_url, board_id, board_label FROM exp_forum_boards WHERE board_id = '".ee()->db->escape_str(ee()->input->get_post('board_id'))."'");
			}
			else
			{
				$query	= ee()->db->query("SELECT board_forum_url, board_id, board_label FROM exp_forum_boards WHERE board_id = '1'");
			}
				
			$site_name	= $query->row('board_label');
			$return		= $query->row('board_forum_url');
		}
		else
		{
			$return 	= ee()->functions->fetch_site_index();
			$site_name 	= (ee()->config->item('site_name') == '') ? ee()->lang->line('back') : stripslashes(ee()->config->item('site_name'));		
		}
        
        /** ----------------------------------------
        /**  No ID?  Tisk tisk...
        /** ----------------------------------------*/
                
        $id  = ee()->input->get_post('id');        
                
        if ($id == FALSE)
        {
                        
			$data = array(	'title' 	=> ee()->lang->line('mbr_activation'),
							'heading'	=> ee()->lang->line('error'),
							'content'	=> ee()->lang->line('invalid_url'),
							'link'		=> array($return, $site_name)
						 );
        
			ee()->output->show_message($data);
        }
        
        
        /** ----------------------------------------
        /**  Set the member group
        /** ----------------------------------------*/
        
        $group_id = ee()->config->item('default_member_group');
        
        // Is there even an account for this particular user?
        $query = ee()->db->query("SELECT member_id, group_id, email, screen_name, username FROM exp_members
        						  WHERE authcode = '".ee()->db->escape_str($id)."'");        
        
        if ($query->num_rows() == 0)
        {
			$data = array(	'title' 	=> ee()->lang->line('mbr_activation'),
							'heading'	=> ee()->lang->line('error'),
							'content'	=> ee()->lang->line('mbr_problem_activating'),
							'link'		=> array($return, $site_name)
						 );
        
			ee()->output->show_message($data);        
        }
        
		$member_id = $query->row('member_id');
		
        if (ee()->input->get_post('mailinglist') !== FALSE && is_numeric(ee()->input->get_post('mailinglist')))
        {
        	$expire = time() - (60*60*48);
        
			ee()->db->query("DELETE FROM exp_mailing_list_queue WHERE date < '$expire' ");
        
        	$results = ee()->db->query("SELECT authcode
        						   FROM exp_mailing_list_queue
        						   WHERE email = '".ee()->db->escape_str($query->row('email'))."'
        						   AND list_id = '".ee()->db->escape_str(ee()->input->get_post('mailinglist'))."'");
        						 
        	ee()->db->query("INSERT INTO exp_mailing_list (user_id, list_id, authcode, email) 
        				VALUES ('', '".ee()->db->escape_str(ee()->input->get_post('mailinglist'))."', '".ee()->db->escape_str($results->row('authcode'))."', '".ee()->db->escape_str($query->row('email'))."')");	
        				
			ee()->db->query("DELETE FROM exp_mailing_list_queue WHERE authcode = '".ee()->db->escape_str($results->row('authcode'))."'");
        }
        
        /** --------------------------------------------
        /**  User Specific for Email Activation!
        /** --------------------------------------------*/
        
        if (ee()->db->table_exists('exp_user_activation_group'))
        {
        	$aquery = ee()->db->query("SELECT group_id FROM exp_user_activation_group WHERE member_id = '".ee()->db->escape_str($member_id)."'");
        	
        	if ($aquery->num_rows() > 0 && $aquery->row('group_id') != 0)
        	{
        		$group_id = $aquery->row('group_id');
        	}
        }
        
        // If the member group hasn't been switched we'll do it.
        
		if ($query->row('group_id') != $group_id)
		{
			ee()->db->query("UPDATE exp_members SET group_id = '".ee()->db->escape_str($group_id)."' WHERE authcode = '".ee()->db->escape_str($id)."'");        
		}
        
        ee()->db->query("UPDATE exp_members SET authcode = '' WHERE authcode = '$id'");
        
        /** --------------------------------------------
        /**  Welcome Email of Doom and Despair
        /** --------------------------------------------*/
        
        $this->welcome_email($query->row_array());
        
		// -------------------------------------------
        // 'member_register_validate_members' hook.
        //  - Additional processing when member(s) are self validated
        //  - Added 1.5.2, 2006-12-28
        //  - $member_id added 1.6.1
        //
        //  - We leave this in here for the User module, just in case other extensions exist!
		//
        	$edata = ee()->extensions->call('member_register_validate_members', $member_id);
        	if (ee()->extensions->end_script === TRUE) return;
        //
        // -------------------------------------------
        
       // Upate Stats
       
		ee()->stats->update_member_stats();

        /** ----------------------------------------
        /**  Show success message
        /** ----------------------------------------*/
                
		$data = array(	'title' 	=> ee()->lang->line('mbr_activation'),
						'heading'	=> ee()->lang->line('thank_you'),
						'content'	=> ee()->lang->line('mbr_activation_success')."\n\n".ee()->lang->line('mbr_may_now_log_in'),
						'link'		=> array($return, $site_name)
					 );
										
		ee()->output->show_message($data);
	}
	/* END activate_member() */
	
    
    // --------------------------------------------------------------------

	/**
	 *	Remote Login
	 *
	 *	Allows One to Login Someone During a Form Submission
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function _remote_login()
    {	
        /** ----------------------------------------
        /**  Is user already logged in?
        /** ----------------------------------------*/
        
        if ( ee()->session->userdata['member_id'] != 0 )
        {
        	return;
        }
        
        /** ----------------------------------------
        /**  Is user banned?
        /** ----------------------------------------*/
        
        if (ee()->session->userdata['is_banned'] == TRUE)
		{
            return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
		}
				
        ee()->lang->loadfile('login');        
        
        /** ----------------------------------------
        /**  Error trapping
        /** ----------------------------------------*/
                
        $errors = array();

        /** ----------------------------------------
        /**  No username/password?  Bounce them...
        /** ----------------------------------------*/
    
        if ( ! ee()->input->get('multi') && ( ! ee()->input->post('username') || ! ee()->input->post('password')))
        {
			return $this->_output_error('submission', array(ee()->lang->line('mbr_form_empty')));
        }
        
        /** ----------------------------------------
        /**  Is IP and User Agent required for login?
        /** ----------------------------------------*/
    
        if (ee()->config->item('require_ip_for_login') == 'y')
        {
			if (ee()->session->userdata['ip_address'] == '' || ee()->session->userdata['user_agent'] == '')
			{
				return $this->_output_error('general', array(ee()->lang->line('unauthorized_request')));        
           	}
        }
                
        /** ----------------------------------------
        /**  Check password lockout status
        /** ----------------------------------------*/
		
		if (ee()->session->check_password_lockout() === TRUE)
		{
			$line = ee()->lang->line('password_lockout_in_effect');
		
			$line = str_replace("%x", ee()->config->item('password_lockout_interval'), $line);
		
			return $this->_output_error('general', array($line));        
		}
				        
        /** ----------------------------------------
        /**  Fetch member data
        /** ----------------------------------------*/

		if ( ee()->input->get('multi') === FALSE )
		{
			$sql = "SELECT exp_members.username, exp_members.screen_name, exp_members.email, exp_members.url, exp_members.location, exp_members.password, exp_members.unique_id, exp_members.member_id, exp_members.group_id
					FROM   exp_members, exp_member_groups
					WHERE  username = '".ee()->db->escape_str(ee()->input->post('username'))."'
					AND    exp_members.group_id = exp_member_groups.group_id";
					
			if ( ee()->config->item('site_id') !== FALSE )
			{
				$sql	.= " AND exp_member_groups.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
			}
                
        	$query = ee()->db->query($sql);
        	
        }
        else
        {
			if (ee()->config->item('allow_multi_logins') == 'n' || ! ee()->config->item('multi_login_sites') || ee()->config->item('multi_login_sites') == '')
			{
				return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
			}
        	
			// Current site in list.  Original login site.
			if (ee()->input->get('cur') === false || ee()->input->get('orig') === false)
			{
				return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
			}
			
			// Kill old sessions first
		
			ee()->session->gc_probability = 100;
			
			ee()->session->delete_old_sessions();
		
			// Set cookie expiration to one year if the "remember me" button is clicked
	
			$expire = ( ! isset($_POST['auto_login'])) ? '0' : 60*60*24*365;

			// Check Session ID
			
			$sql	= "SELECT exp_members.member_id, exp_members.password, exp_members.unique_id
							FROM   	exp_sessions, exp_members 
							WHERE  	exp_sessions.session_id  = '".ee()->db->escape_str(ee()->input->get('multi'))."'
							AND		exp_sessions.member_id = exp_members.member_id
							AND    	exp_sessions.last_activity > $expire";
					
			if ( ee()->config->item('site_id') !== FALSE )
			{
				$sql	.= " AND exp_member_groups.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
			}
			
			$query	= ee()->db->query( $sql );
			 
			if ($query->num_rows() == 0) 
				return;
			
			// Set Various Cookies
			
			ee()->functions->set_cookie(ee()->session->c_anon);
			ee()->functions->set_cookie(ee()->session->c_expire , time()+$expire, $expire);
			ee()->functions->set_cookie(ee()->session->c_uniqueid , $query->row('unique_id'), $expire);       
			ee()->functions->set_cookie(ee()->session->c_password , $query->row('password'),  $expire); 
				
			if (ee()->config->item('user_session_type') == 'cs' || ee()->config->item('user_session_type') == 's')
			{                    
				ee()->functions->set_cookie(ee()->session->c_session , ee()->input->get('multi'), ee()->session->session_length);     
			}
			
			// -------------------------------------------
			// 'member_member_login_multi' hook.
			//  - Additional processing when a member is logging into multiple sites
			//
				$edata = ee()->extensions->call('member_member_login_multi', $query->row);
				if (ee()->extensions->end_script === TRUE) return;
			//
			// -------------------------------------------
				
			// Check if there are any more sites to log into
			
			$sites	= explode('|',ee()->config->item('multi_login_sites'));
			$next	= (ee()->input->get('cur') + 1 != ee()->input->get('orig')) ? ee()->input->get('cur') + 1 : ee()->input->get('cur') + 2;
			
			if ( ! isset($sites[$next]))
			{
				// We're done.
				$data = array(	'title' 	=> ee()->lang->line('mbr_login'),
								'heading'	=> ee()->lang->line('thank_you'),
								'content'	=> ee()->lang->line('mbr_you_are_logged_in'),
								'redirect'	=> $sites[ee()->input->get('orig')],
								'link'		=> array($sites[ee()->input->get('orig')], ee()->lang->line('back'))
								 );
			
				ee()->output->show_message($data);
			}
			else
			{
				// Next Site
				
				$next_url = $sites[$next].'?ACT='.ee()->functions->fetch_action_id('Member', 'member_login').
							'&multi='.ee()->input->get('multi').'&cur='.$next.'&orig='.ee()->input->get_post('orig');
							
				return ee()->functions->redirect($next_url);
			}        	
		}
       
        /** ----------------------------------------
        /**  Invalid Username
        /** ----------------------------------------*/

        if ($query->num_rows() == 0)
        {
        	ee()->session->save_password_lockout();
        	
			return $this->_output_error('submission', array(ee()->lang->line('no_username')));        
        }
                
        /** ----------------------------------------
        /**  Is the member account pending?
        /** ----------------------------------------*/

        if ($query->row('group_id') == 4)
        { 
			return $this->_output_error('general', array(ee()->lang->line('mbr_account_not_active')));        
        }
                
        /** ----------------------------------------
        /**  Check password
        /** ----------------------------------------*/

        $password = ee()->functions->hash(stripslashes(ee()->input->post('password')));
        
        if ($query->row('password') != $password)
        {
            // To enable backward compatibility with pMachine we'll test to see 
            // if the password was encrypted with MD5.  If so, we will encrypt the
            // password using SHA1 and update the member's info.
            
            $orig_enc_type = ee()->config->item('encryption_type');
            $PREFS->core_ini['encryption_type'] = (ee()->config->item('encryption_type') == 'md5') ? 'sha1' : 'md5';
			$password = ee()->functions->hash(stripslashes(ee()->input->post('password')));

            if ($query->row('password') == $password)
            {
            	$PREFS->core_ini['encryption_type'] = $orig_enc_type;
				$password = ee()->functions->hash(stripslashes(ee()->input->post('password')));

                $sql = "UPDATE exp_members 
                        SET    password = '".$password."' 
                        WHERE  member_id = '".$query->row('member_id')."' ";
                        
                ee()->db->query($sql);
            }
            else
            {
				/** ----------------------------------------
				/**  Invalid password
				/** ----------------------------------------*/
					
        		ee()->session->save_password_lockout();
	
				$errors[] = ee()->lang->line('no_password');        
            }
        }
        
        /** --------------------------------------------------
        /**  Do we allow multiple logins on the same account?
        /** --------------------------------------------------*/
        
        if (ee()->config->item('allow_multi_logins') == 'n')
        {
            // Kill old sessions first
        
            ee()->session->gc_probability = 100;
            
            ee()->session->delete_old_sessions();
        
            $expire = time() - ee()->session->session_length;
            
            // See if there is a current session
            
            $sql = "SELECT ip_address, user_agent FROM exp_sessions 
					WHERE  member_id  = '".$query->row('member_id')."'
					AND    last_activity > $expire";
					
			if ( ee()->config->item('site_id') !== FALSE )
			{
				$sql	.= " AND site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
			}
			
			$result	= ee()->db->query( $sql );
                                
            // If a session exists, trigger the error message
                               
            if ($result->num_rows() == 1)
            {
                if (ee()->session->userdata['ip_address'] != $result->row('ip_address') || 
                    ee()->session->userdata['user_agent'] != $result->row('user_agent') )
                {
					$errors[] = ee()->lang->line('multi_login_warning');        
                }               
            } 
        }  
        
		/** ----------------------------------------
		/**  Are there errors to display?
		/** ----------------------------------------*/
        
        if (count($errors) > 0)
        {
			return $this->_output_error('submission', $errors);
        }        
        
        /** ----------------------------------------
        /**  Set cookies
        /** ----------------------------------------*/
        
        // Set cookie expiration to one year if the "remember me" button is clicked

        $expire = ( ! isset($_POST['auto_login'])) ? '0' : 60*60*24*365;

		ee()->functions->set_cookie(ee()->session->c_expire , time()+$expire, $expire);
        ee()->functions->set_cookie(ee()->session->c_uniqueid , $query->row('unique_id'), $expire);       
        ee()->functions->set_cookie(ee()->session->c_password , $password,  $expire);  
                
        // Does the user want to remain anonymous?
        
        if ( ! isset($_POST['anon'])) 
        {
            ee()->functions->set_cookie(ee()->session->c_anon , 1,  $expire);
            
            $anon = 'y';            
        }
        else
        { 
            ee()->functions->set_cookie(ee()->session->c_anon);
                   
            $anon = '';
        }

        /** ----------------------------------------
        /**  Create a new session
        /** ----------------------------------------*/
        
        ee()->session->create_new_session($query->row('member_id'));

        /** ----------------------------------------
        /**  Populate session
        /** ----------------------------------------*/
        
        ee()->session->userdata['username']		= $query->row('username');
        ee()->session->userdata['screen_name']	= $query->row('screen_name');
        ee()->session->userdata['email']		= $query->row('email');
        ee()->session->userdata['url']			= $query->row('url');
        ee()->session->userdata['location']		= $query->row('location');
    
        /** ----------------------------------------
        /**  Update stats
        /** ----------------------------------------*/
 
		$cutoff		= ee()->localize->now - (15 * 60);
		
		$sql		= "DELETE FROM exp_online_users 
						WHERE (ip_address = 'ee()->input->ip_address()' AND member_id = '0') OR date < $cutoff";
					
		if ( ee()->config->item('site_id') !== FALSE )
		{
			$sql	.= " AND site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
		}

        ee()->db->query( $sql );
                
		$data = array(
						'member_id'		=> ee()->session->userdata('member_id'),
						'name'			=> (ee()->session->userdata['screen_name'] == '') ? ee()->session->userdata['username'] : ee()->session->userdata['screen_name'],
						'ip_address'	=> ee()->input->ip_address(),
						'date'			=> ee()->localize->now,
						'anon'			=> $anon
					);
					
		if ( ee()->config->item('site_id') !== FALSE )
		{
			$data['site_id']	= ee()->config->item('site_id');
		}
       
		ee()->db->query(ee()->db->update_string('exp_online_users', $data, array("ip_address" => ee()->input->ip_address(), "member_id" => $data['member_id'])));
               
        /** ----------------------------------------
        /**  Delete old password lockouts
        /** ----------------------------------------*/
        
		ee()->session->delete_password_lockout();
    }
    
    /* END remote login */
    
    // --------------------------------------------------------------------

	/**
	 *	Remote Register
	 *
	 *	Allows Person to Be Registered During a Form Submission
	 *
	 *	@access		public
	 *	@return		null
	 */

	public function _remote_register()
    {
		
        /** ----------------------------------------
        /**	Is user already logged in?
        /** ----------------------------------------*/
        
        if ( ee()->session->userdata['member_id'] != 0 )
        {
        	return;
        }
        
        /** ----------------------------------------
        /**	Is user banned?
        /** ----------------------------------------*/
        
        if (ee()->session->userdata['is_banned'] == TRUE)
		{
            return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
		}
        
		/**	----------------------------------------
		/**	Is immediate registration enabled?
		/**	----------------------------------------
		/*	There can be many many permutations on
		/*	this because of the different
		/*	registration types and approval processes
		/*	and such.
		/*	If immediate registration is not enabled,
		/*	we're going to not allow this process.
		/**	----------------------------------------*/
                        
        if ( ee()->config->item('req_mbr_activation') == 'manual' OR ee()->config->item('req_mbr_activation') == 'email' )
        {
            return $this->_output_error('general', array(ee()->lang->line('wrong_reg_mode')));
        }
        
        /** ----------------------------------------
        /**	Invoke the reg function and pray
        /** ----------------------------------------*/
        
        $this->reg( TRUE );
    }
    
    /* END remote register */
	
	
    // --------------------------------------------------------------------

	/**
	 *	Search Form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function search()
    {    
        /** ----------------------------------------
        /**  Fetch ID number
        /** ----------------------------------------
        /*	We want to repopulate our search field with the pervious searched data in case
        /*	the search form and results occupy the same page.
        /** ----------------------------------------*/
        
        $search_id	= '';
        
        foreach ( ee()->uri->segments as $seg )
        {
        	if ( strlen($seg) >= 32 )
        	{
        		$search_id	= $seg;
        	}
        }
        
        if ( strlen( $search_id ) > 32 )
        {
			$search_id = substr( $search_id, 0, 32 );
			$this->cur_page  = substr( $search_id, 32 );
        }
        
        /** ----------------------------------------
        /**	Check DB
        /** ----------------------------------------*/
        
        $fields		= array();
        $cfields	= array();
        $keywords	= '';
        
        if ( $search_id != '' )
        {
			$query	= ee()->db->query( "SELECT `keywords`, `categories`, `fields`, `cfields` FROM exp_user_search WHERE search_id = '".ee()->db->escape_str( $search_id )."'" );
        
			if ( $query->num_rows() > 0 )
			{
				$this->assigned_cats	= unserialize( $query->row('categories') );
				$fields					= unserialize( $query->row('fields') );
				$cfields				= unserialize( $query->row('cfields') );
				$keywords				= $query->row('keywords');
			}
        }		
    	
		/**	----------------------------------------
		/**	Userdata
		/**	----------------------------------------*/
		
		$tagdata	= ee()->TMPL->tagdata;
    	
		/**	----------------------------------------
		/**	Handle categories
		/**	----------------------------------------*/
		
		$user_parent_category	= '';
		$user_category			= '';
		
		if ( count( $this->assigned_cats ) > 0 )
		{
			$catq	= ee()->db->query( "SELECT cat_id, parent_id FROM exp_categories WHERE cat_id IN ('".implode( "','", $this->assigned_cats )."')" );
			
			foreach ( $catq->result_array() as $row )
			{
				if ( $row['parent_id'] == 0 )
				{
					$user_parent_category	= $row['cat_id'];
				}
				else
				{
					$user_category			= $row['cat_id'];
				}
			}
		}
		
		$tagdata	= str_replace( LD.'user_parent_category'.RD, $user_parent_category, $tagdata );
		$tagdata	= str_replace( LD.'user_category'.RD, $user_category, $tagdata );
    	
		/**	----------------------------------------
		/**	Sniff for checkboxes
		/**	----------------------------------------*/
		
		$checks			= '';
		$custom_checks	= '';
		
		if ( preg_match_all( "/name=['|\"]?(\w+)['|\"]?/", $tagdata, $match ) )
		{
			$this->_mfields();
			
			foreach ( $match['1'] as $m )
			{
				if ( in_array( $m, $this->check_boxes ) )
				{
					$checks	.= $m."|";
				}
				
				if ( isset( $this->mfields[ $m ] ) AND $this->mfields[ $m ]['type'] == 'select' )
				{
					$custom_checks	.= $m."|";
				}
			}
		}
    	
		/**	----------------------------------------
		/**	Handle categories
		/**	----------------------------------------*/
		
		$tagdata	= $this->_categories( $tagdata );
    	
		/**	----------------------------------------
		/**	Handle standard fields
		/**	----------------------------------------*/
        
        $standard	= array_merge( array('username', 'email', 'screen_name' ), $this->standard );
        
        foreach ( $standard as $key )
        {
        	if ( isset( $fields[$key] ) === TRUE )
        	{
        		$tagdata	= str_replace( LD.$key.RD, $fields[$key], $tagdata );
        	}
        	else
        	{
        		$tagdata	= str_replace( LD.$key.RD, "", $tagdata );
        	}
        }
    	
		/**	----------------------------------------
		/**	Handle custom fields
		/**	----------------------------------------*/
        
        foreach ( $this->_mfields() as $key => $val )
        {
        	if ( isset( $cfields['m_field_id_'.$val['id']] ) === TRUE )
        	{
        		$tagdata	= str_replace( LD.$key.RD, $cfields['m_field_id_'.$val['id']], $tagdata );
        	}
        	else
        	{
        		$tagdata	= str_replace( LD.$key.RD, "", $tagdata );
        	}
        }
    	
		/**	----------------------------------------
		/**	Keywords
		/**	----------------------------------------*/
		
		$tagdata	= str_replace( LD."keywords".RD, $keywords, $tagdata );
    	
		/**	----------------------------------------
		/**	Prep data
		/**	----------------------------------------*/
		
		$this->form_data['tagdata']					= $tagdata;
		
		$this->form_data['ACT']						= ee()->functions->fetch_action_id('User', 'do_search');
		
        $this->form_data['RET']						= (isset($_POST['RET'])) ? $_POST['RET'] : ee()->functions->fetch_current_uri();
		
		$this->form_data['id']						= ( ee()->TMPL->fetch_param('form_id') ) ? ee()->TMPL->fetch_param('form_id'): '';
		
		$this->form_data['class']					= ( ee()->TMPL->fetch_param('form_class') ) ? ee()->TMPL->fetch_param('form_class'): '';
		
		$this->form_data['skip_field']				= ( ee()->TMPL->fetch_param('skip_field') ) ? ee()->TMPL->fetch_param('skip_field'): '';
		
		$this->form_data['group_id']					= ( ee()->TMPL->fetch_param('group_id') ) ? ee()->TMPL->fetch_param('group_id'): '';
		
		$this->form_data['return']					= ( ee()->TMPL->fetch_param('return') ) ? ee()->TMPL->fetch_param('return') : '';
		
		$this->form_data['inclusive_categories']		= ( ee()->TMPL->fetch_param('inclusive_categories') ) ? ee()->TMPL->fetch_param('inclusive_categories'): '';
		
		$this->params['checks']					= $checks;
		
		$this->params['custom_checks']			= $custom_checks;
		
		$this->params['search_id']				= $search_id;
		
		if ( ee()->TMPL->fetch_param('form_name') !== FALSE AND ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']					= ee()->TMPL->fetch_param('form_name');
		}
		
		$this->params['secure_action']			= ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
        
		return $this->_form();
	}
	
	/* END search */
	
	
    // --------------------------------------------------------------------

	/**
	 *	Perform Search Processing
	 *
	 *	@access		public
	 *	@return		redirect
	 */

	public function do_search()
    {
        /**	----------------------------------------
        /**	Is user banned?
        /**	----------------------------------------*/
        
        if (ee()->session->userdata['is_banned'] == TRUE)
		{
            return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
		}	
		
		/**	----------------------------------------
        /**	Blacklist/Whitelist Check
        /**	----------------------------------------*/
        
        if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n')
        {
        	return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
        }
		
        /** ----------------------------------------
        /**  Update last activity
        /** ----------------------------------------*/
        
        $this->_update_last_activity();
        
        /** ----------------------------------------
        /**  Do we have a search results page?
        /** ----------------------------------------*/
        
        if ( ee()->input->post('return') === FALSE OR ee()->input->post('return') == '' )
        {
        	if ( ee()->input->post('RET') !== FALSE AND ee()->input->post('RET') != '' )
        	{
				$return	= ee()->input->post('RET');
        	}
        	else
        	{
				return $this->_output_error('general', array(ee()->lang->line('search_path_error')));
			}
        }
        else
        {
        	$return	= ee()->input->post('return');
        }
		
        /** ----------------------------------------
        /**  Is the current user allowed to search?
        /** ----------------------------------------*/

        if (ee()->session->userdata['can_search'] == 'n' AND ee()->session->userdata['group_id'] != 1)
        {            
            return $this->_output_error('general', array(ee()->lang->line('search_not_allowed')));
        }
		
        /** ----------------------------------------
        /**  Flood control
        /** ----------------------------------------*/
        
        if (ee()->session->userdata['search_flood_control'] > 0 AND ee()->session->userdata['group_id'] != 1)
		{
			$cutoff = time() - ee()->session->userdata['search_flood_control'];

			$sql = "SELECT search_id FROM exp_user_search WHERE search_id != '' AND search_date > '{$cutoff}' AND ";
			
			if ( ee()->config->item('site_id') !== FALSE )
			{
				$sql .= "site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND ";
			}
			
			if (ee()->session->userdata['member_id'] != 0)
			{
				$sql .= "(member_id='".ee()->db->escape_str(ee()->session->userdata('member_id'))."' OR ip_address='".ee()->db->escape_str(ee()->input->ip_address())."')";
			}
			else
			{
				$sql .= "ip_address='".ee()->db->escape_str(ee()->input->ip_address())."'";
			}
			
			$query = ee()->db->query($sql);
					
			$text = str_replace("%x", ee()->session->userdata['search_flood_control'], ee()->lang->line('search_time_not_expired'));
				
			if ($query->num_rows() > 0)
			{
            	return $this->_output_error('general', array($text));
			}
		}
        
        /** ----------------------------------------
        /**	Prep group ids if needed
        /** ----------------------------------------*/
        
        $group_id	= '';
        
        if ( ee()->input->post('group_id') !== FALSE AND ee()->input->post('group_id') != '' )
        {
        	$group_id	= ee()->input->post('group_id');
        }
        
        /** ----------------------------------------
        /**	Prep member ids if needed
        /** ----------------------------------------*/
        
        $member_ids	= array();
        
        if ( ee()->input->post('search_within_results') !== FALSE AND ee()->input->post('search_within_results') == 'yes' AND $this->_param('search_id') !== FALSE AND $this->_param('search_id') != '' )
        {        	
        	$memq	= ee()->db->query("SELECT member_ids FROM exp_user_search WHERE search_id = '".ee()->db->escape_str( $this->_param('search_id') )."'");
        	
        	if ( $memq->row('member_ids') !== FALSE )
        	{
        		$member_ids	= unserialize( $memq->row('member_ids') );
        	}
        }
        
        /** ----------------------------------------
        /**	Prep categories
        /** ----------------------------------------*/
        
        $categories	= array();
        
        if ( isset( $_POST['category'] ) !== FALSE )
        {
        	if ( is_array( $_POST['category'] ) === TRUE )
        	{
        		$_POST['category']	= ee()->security->xss_clean( $_POST['category'] );
        		
        		foreach ( $_POST['category'] as $cat )
        		{
        			if (ctype_digit($cat)) 
					{
						$categories[] = $cat;
					}
        		}
        	}
        	elseif ( ctype_digit( $_POST['category'] ) )
        	{
        		$categories[]	= ee()->security->xss_clean( $_POST['category'] );
        	}
        }
        
        /** ----------------------------------------
        /**	Turn keywords into an array
        /** ----------------------------------------*/
        
        $exclude	= array();
        $terms		= array();
        
        if ( ( $keywords = ee()->input->post('keywords') ) !== FALSE )
        {
        	$keywords = stripslashes($keywords);
        	
			if ( preg_match_all( "/\-*\"(.*?)\"/", $keywords, $matches ) )
			{
				for( $m=0; $m < sizeof( $matches['1'] ); $m++ )
				{
					$terms[]	= trim( str_replace( '"', '', $matches['0'][$m] ) );
					$keywords	= str_replace( $matches['0'][$m], '', $keywords );
				}    
			}
			
			if ( trim( $keywords ) != '' )
			{
				$terms = array_merge( $terms, preg_split( "/\s+/", trim( $keywords ) ) );
			}
			
			$keywords	= array();
			
			foreach ( $terms as $val )
			{				
				if ( substr( $val, 0, 1 ) == "-" )
				{
					$exclude[]	= substr( $val, 1 );
				}
				else
				{
					$keywords[]	= $val;
				}
			}
		}
		else
		{
			$keywords	= array();
		}
        
        /**	----------------------------------------
        /**	Set skip fields
        /**	----------------------------------------*/
        
        $skip	= array();
        
        if ( ee()->input->post('skip_field') !== FALSE AND ee()->input->post('skip_field') != '' )
        {
        	$skip	= explode( "|", ee()->input->post('skip_field') );
        }
        
        /**	----------------------------------------
        /**	Assemble standard fields
        /**	----------------------------------------*/
        
        $this->standard	= array_merge( $this->standard, array( 'username', 'screen_name', 'email' ) );
        
        foreach ( $this->standard as $field )
        {
        	if ( isset( $_POST[ $field ]) AND ! in_array( $field, $skip ) AND trim($_POST[ $field ]) !== '')
        	{
        		$this->insert_data[$field]	= trim($_POST[ $field ]);
        	}
        }
        
        /**	----------------------------------------
        /**	Assemble checkbox fields
        /**	----------------------------------------*/
        
        if ( $this->_param('checks') != '' )
        {
        	foreach ( explode( "|", $this->_param('checks') )  as $c )
        	{
        		if ( in_array( $c, $this->check_boxes ) )
        		{
        			if ( ee()->input->post($c) !== FALSE )
        			{
        				if ( stristr( ee()->input->post($c), 'n' ) )
        				{
							$this->insert_data[$c]	= 'n';
        				}
        				else
        				{
							$this->insert_data[$c]	= 'y';
        				}
        			}
        			else
        			{
        				$this->insert_data[$c]	= 'n';
        			}
        		}
        	}
        }
        
        $this->insert_data	= ee()->security->xss_clean( $this->insert_data );
        
        /**	----------------------------------------
        /**	Assemble custom fields
        /**	----------------------------------------*/
        
        $cfields	= array();
        
        foreach ( $this->_mfields() as $key => $val )
        {
			/**	----------------------------------------
			/**	Field named 'keywords'? Skip it.
			/**	----------------------------------------*/
			
			if ( $key == 'keywords' ) continue;
			
			/**	----------------------------------------
			/**	Handle empty checkbox fields
			/**	----------------------------------------*/
			
			if ( $this->_param( 'custom_checks' ) !== FALSE )
			{
				$arr	= explode( "|", $this->_param( 'custom_checks' ) );
				
				foreach ( $arr as $check )
				{
					// No idea what this is doing.  -Paul
					// $cfields['m_field_id_'.$val['id']]	= "";
				}
			}
			
			/**	----------------------------------------
			/**	Handle fields
			/**	----------------------------------------*/
			
        	if ( isset( $_POST[ $key ] ) && ! in_array( $key, $skip ) )
        	{
				/**	----------------------------------------
				/**	Handle arrays
				/**	----------------------------------------*/
				
				if ( is_array( $_POST[ $key ] ) )
				{
					$cfields['m_field_id_'.$val['id']]	= implode( "\n", $_POST[ $key ] );
				}
				else
				{
					$cfields['m_field_id_'.$val['id']]	= $_POST[ $key ];
				}
        	}
        }
        
        $cfields	= ee()->security->xss_clean( $cfields );
        
        /**	----------------------------------------
        /**	Start query
        /**	----------------------------------------*/
        
        $globalandor	= " AND";
        
        $sql			= "SELECT DISTINCT(m.member_id) FROM exp_members m";
        
        /**	----------------------------------------
        /**	Join for custom fields?
        /**	----------------------------------------*/
        
        if ( count( $cfields ) > 0 OR count( $keywords ) > 0 )
        {
        	$sql	.= " LEFT JOIN exp_member_data md ON md.member_id = m.member_id";
        }
        
        /**	----------------------------------------
        /**	Join for categories
        /**	----------------------------------------*/
        
        if ( count( $categories ) > 0 )
        {
        	$sql	.= " LEFT JOIN exp_user_category_posts ucp ON ucp.member_id = m.member_id";
        }
        
        /**	----------------------------------------
        /**	Where
        /**	----------------------------------------*/
        
        $sql	.= " WHERE m.member_id != 0";
        
        /**	----------------------------------------
        /**	Categories
        /**	----------------------------------------*/
        
        if ( count( $categories ) > 0 )
        {
        	$sql	.= " AND (";
        	
        	foreach ( $categories as $cat )
        	{
        		$sql	.= " ucp.cat_id = '".ee()->db->escape_str( $cat )."' OR";
        	}
			
			$sql	= substr( $sql, 0, -2 );
        	
        	$sql	.= ")";
        }
        
        /**	----------------------------------------
        /**	Group ids
        /**	----------------------------------------*/
        
        if ( $group_id != '' )
        {
        	$sql	.= " ".ee()->functions->sql_andor_string( $group_id, 'm.group_id' );
        }
        
        /**	----------------------------------------
        /**	Standard fields
        /**	----------------------------------------*/
        
        if ( count( $this->insert_data ) > 0 )
        {
			$compare	= 'like';
        	
			//allready doing this above
        	//$this->insert_data	= ee()->security->xss_clean( $this->insert_data );
        	
        	$andor	= " AND";
        	
			$sql	.= $globalandor." (";
			
			foreach ( $this->insert_data as $key => $val )
			{				
				if ( $compare == 'like' )
				{
					$sql	.= " m.".$key." LIKE '%".ee()->db->escape_str( $val )."%'".$andor;
				}
				else
				{
					$sql	.= " m.".$key." = '".ee()->db->escape_str( $val )."'".$andor;
				}
			}
			
			$sql	= substr( $sql, 0, -( strlen( $andor ) ) );
			
			$sql	.= ")";
        }

        
        /**	----------------------------------------
        /**	Custom fields
        /**	----------------------------------------*/
        
        if ( count( $cfields ) > 0 )
        {
        	$compare	= 'like';
        	
        	$cfields	= ee()->security->xss_clean( $cfields );
        	
        	$andor		= " AND";
        	
			$sql		.= $globalandor." (";
			
			foreach ( $cfields as $key => $val )
			{
				if ( $compare == 'like' )
				{
					$sql	.= " md.".$key." LIKE '%".ee()->db->escape_str( $val )."%'".$andor;
				}
				else
				{
					$sql	.= " md.".$key." = '".ee()->db->escape_str( $val )."'".$andor;
				}
			}
			
			$sql		= substr( $sql, 0, -( strlen( $andor ) ) );
			
			$sql		.= ")";
        }
        
        /**	----------------------------------------
        /**	Keywords
        /**	----------------------------------------
        /*	This is where all the action is. It's
        /*	convoluted, but we'll try to make it more
        /*	simple than EE's regular search query.
        /**	----------------------------------------*/
        
        if ( count( $keywords ) > 0 )
        {
			/**	----------------------------------------
			/**	Clean
			/**	----------------------------------------*/
			
			$keywords	= ee()->security->xss_clean( $keywords );
        	
        	$andor		= " OR";
			
			/**	----------------------------------------
			/**	Start the wrapper
			/**	----------------------------------------*/
			
			$sql		.= $globalandor." (";
			
			/**	----------------------------------------
			/**	Check standard fields
			/**	----------------------------------------*/
			
			foreach ( $keywords as $keyword )
			{
				if (trim($keyword) == '') continue;
				
				foreach ( $this->standard as $val )
				{
					if (in_array($val, $skip)) continue;
					
					$sql	.= " m.".$val." LIKE '%".ee()->db->escape_str( $keyword )."%'".$andor."\n";
				}
				
				foreach ( $this->_mfields() as $key => $val )
				{
					if (in_array($key, $skip)) continue;
					
					$sql	.= " md.m_field_id_".$val['id']." LIKE '%".ee()->db->escape_str( $keyword )."%'".$andor."\n";
				}
			}
			
			/**	----------------------------------------
			/* END the wrapper
			/**	----------------------------------------*/
			
			$sql		= substr( $sql, 0, -( strlen( $andor ) ) );
			
			$sql		.= ")";
        }
        
        /**	----------------------------------------
        /**	Exclude
        /**	----------------------------------------*/
        
        if ( count( $exclude ) > 0 )
        {
			/**	----------------------------------------
			/**	Clean
			/**	----------------------------------------*/
			
			$exclude	= ee()->security->xss_clean( $exclude );
        	
        	$andor		= " OR";
			
			/**	----------------------------------------
			/**	Start the wrapper
			/**	----------------------------------------*/
			
			$sql		.= " AND (";
			
			/**	----------------------------------------
			/**	Check standard fields
			/**	----------------------------------------*/
			
			foreach ( $exclude as $ex )
			{
				if (trim($ex) == '') continue;
				
				foreach ( $this->standard as $val )
				{
					if (in_array($val, $skip)) continue;
					
					$sql	.= " m.".$val." NOT LIKE '%".ee()->db->escape_str( $ex )."%'".$andor."\n";
				}
				
				foreach ( $this->mfields as $key => $val )
				{
					if (in_array($key, $skip)) continue;
					
					$sql	.= " md.m_field_id_".$val['id']." NOT LIKE '%".ee()->db->escape_str( $keyword )."%'".$andor."\n";
				}
			}
			
			/**	----------------------------------------
			/* END the wrapper
			/**	----------------------------------------*/
			
			$sql		= substr( $sql, 0, -( strlen( $andor ) ) );
			
			$sql		.= ")";
		}

		/**	----------------------------------------
		/**	Member ids if we're within results
		/**	----------------------------------------*/
		
		if ( count( $member_ids ) > 0 )
		{
			$sql	.= " AND m.member_id IN ('".implode( "','", $member_ids )."')";
		}
			
		/**	----------------------------------------
		/**	Inclusive categories?
		/**	----------------------------------------*/
		
		$fail	= FALSE;
		
		if ( count( $categories ) > 1 AND ee()->input->post('inclusive_categories') !== FALSE AND ee()->input->post('inclusive_categories') == 'yes' )
		{
			$chosen	= array();
			
			$catq	= ee()->db->query( "SELECT member_id, cat_id FROM exp_user_category_posts WHERE cat_id IN ('".implode( "','", $categories )."')" );
			
			$mem_array	= array();
			
			foreach ( $catq->result_array() as $row )
			{
				$mem_array[ $row['cat_id'] ][]	= $row['member_id'];
			}
        		
			if ( count( $mem_array) < 2 OR count( array_diff( $categories, array_keys( $mem_array ) ) ) > 0)
			{
				$fail	= TRUE;
			}
			else
			{
				$chosen = call_user_func_array('array_intersect', $mem_array);
			}			
			
			if ( count( $chosen ) == 0)
			{
				$fail	= TRUE;
			}
			
			if ( count( $chosen ) > 0 )
			{
				$sql	.= " AND ucp.member_id IN ('".implode( "','", $chosen )."')";
			}
		}
		
		/**	----------------------------------------
		/**	Run?
		/**	----------------------------------------*/
		
		if ( $fail === FALSE )
		{			
			/**	----------------------------------------
			/**	Run the poor query
			/**	----------------------------------------*/
			
			$this->query	= ee()->db->query( $sql );
			
			$ids	= array();
			
			foreach ( $this->query->result_array() as $row )
			{
				$ids[]	= $row['member_id'];
			}
			
			/**	----------------------------------------
			/**	Prep final query
			/**	----------------------------------------*/
			
			$sql	= "SELECT m.*, md.*, ( m.total_entries + m.total_comments ) AS total_combined_posts, mg.group_title, mg.group_description
					   FROM exp_members m 
					   LEFT JOIN exp_member_data md ON md.member_id = m.member_id 
					   LEFT JOIN exp_member_groups AS mg ON mg.group_id = m.group_id
					   WHERE m.member_id IN ('".implode( "','", $ids )."')
					   AND mg.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
			
			// This fixes a bug that occurs when a different table prefix is used
			
			$sql	= str_replace('exp_', 'MDBMPREFIX', $sql);
		}
		else
		{
			$sql	= "";
			$ids	= array();
		}
		
		/**	----------------------------------------
		/**	Prep insert
		/**	----------------------------------------*/
		
		$hash = ee()->functions->random('md5');
		
		$data = array(
				'search_id'		=> $hash,
				'search_date'	=> time(),
				'member_id'		=> ee()->session->userdata('member_id'),
				'keywords'		=> ee()->security->xss_clean( ee()->input->post('keywords') ),
				'ip_address'	=> ee()->input->ip_address(),
				'total_results'	=> ( $sql != '' ) ? $this->query->num_rows() : 0,
				'query'			=> $sql,
				'categories'	=> serialize( $categories ),
				'member_ids'	=> serialize( $ids ),
				'fields'		=> serialize( $this->insert_data ),
				'cfields'		=> serialize( $cfields )
				);
			
		if ( ee()->config->item('site_id') !== FALSE )
		{
			$data['site_id']	= ee()->config->item('site_id');
		}

		ee()->db->query( ee()->db->insert_string('exp_user_search', $data) );
		
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		$return	= $this->_chars_decode( $return );
		
		return ee()->functions->redirect( rtrim($return, '/')."/".$hash );
	}
	
	/* END do_search() */
	
	
    // --------------------------------------------------------------------

	/**
	 *	Output Results of Search
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function results()
    {
    	$cache_expire	= 24;
                
        /** ----------------------------------------
        /**  Clear old search results
        /** ----------------------------------------*/

		$expire = time() - ($cache_expire * 3600);
		
		ee()->db->query("DELETE FROM exp_user_search WHERE search_date < '".ee()->db->escape_str($expire)."'");
        
        /** ----------------------------------------
        /**  Fetch ID number and page number
        /** ----------------------------------------
        /*	We cleverly disguise the page number in the ID hash string.
        /** ----------------------------------------*/
        
        $search_id	= '';
        
        foreach ( ee()->uri->segments as $seg )
        {
        	if ( strlen($seg) >= 32 )
        	{
        		$search_id	= $seg;
        	}
        }
        
        if ( $search_id == '' )
        {
        	ee()->TMPL->template = str_replace(LD.'keywords'.RD, '', ee()->TMPL->template);
        	ee()->TMPL->template = str_replace(LD.'total_results'.RD, '', ee()->TMPL->template);
        	return $this->no_results('user');
        }
        elseif ( strlen( $search_id ) == 32 )
        {
        }
        else
        {
			$this->cur_page	= substr( $search_id, 32 );
			$search_id		= substr( $search_id, 0, 32 );
        }
        
        $this->res_page	= str_replace( $search_id.$this->cur_page, "", ee()->uri->uri_string );
        $this->res_page	= str_replace( $search_id, "", $this->res_page );
        
        /** ----------------------------------------
        /**	Check DB
        /** ----------------------------------------*/
        
        $this->query = ee()->db->query("SELECT `search_id`, `keywords`, `total_results`, `categories`, `fields`, `cfields`, `query` 
        									 FROM exp_user_search
        									 WHERE search_id = '".ee()->db->escape_str( $search_id )."'" );
        
        if ( $this->query->num_rows() == 0 )
        {
        	return $this->no_results('user');
        }
        
        /** ----------------------------------------
        /**	Parse some vars
        /** ----------------------------------------*/
        
		ee()->TMPL->template = str_replace(LD.'keywords'.RD, $this->query->row('keywords'), ee()->TMPL->template);
        
		ee()->TMPL->template = str_replace(LD.'total_results'.RD, $this->query->row('total_results'), ee()->TMPL->template);
        
        if ( $this->query->row('total_results') == 0 )
        {		
        	return $this->no_results('user');
        }
        
        /** ----------------------------------------
        /**	Start SQL
        /** ----------------------------------------*/
        
        $sql	= str_replace( 'MDBMPREFIX', 'exp_', $this->query->row('query') );
        
        /** ----------------------------------------
        /**	Order
        /** ----------------------------------------*/
        
        $sql	= $this->_order_sort( $sql );
        
        /** ----------------------------------------
        /**  Prep pagination
        /** ----------------------------------------*/
        
        $sql	= $this->_prep_pagination( $sql, $this->query->row('search_id'), FALSE );
        
        /** ----------------------------------------
        /**	Run query
        /** ----------------------------------------*/
        
        $this->query	= ee()->db->query( $sql );

		/** ----------------------------------------
		/**  Add Pagination
		/** ----------------------------------------*/

		if ($this->paginate == FALSE)
		{
			ee()->TMPL->tagdata = preg_replace("/".LD."if paginate".RD.".*?".LD."&#47;if".RD."/s", '', ee()->TMPL->tagdata);
		}
		else
		{
			ee()->TMPL->tagdata = preg_replace("/".LD."if paginate".RD."(.*?)".LD."&#47;if".RD."/s", "\\1", ee()->TMPL->tagdata);
			
			$this->paginate_data	= str_replace( LD."pagination_links".RD, $this->pager, $this->paginate_data);
			$this->paginate_data	= str_replace(LD.'current_page'.RD, $this->current_page, $this->paginate_data);
			$this->paginate_data	= str_replace(LD.'total_pages'.RD,	$this->total_pages, $this->paginate_data);
			$this->paginate_data	= str_replace(LD.'page_count'.RD,	$this->page_count, $this->paginate_data);
		}
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		$return	= $this->_users();
		
		if ( ee()->TMPL->fetch_param('paginate') == 'both' )
		{
			$return	= $this->paginate_data.$return.$this->paginate_data;
		}
		elseif ( ee()->TMPL->fetch_param('paginate') == 'top' )
		{
			$return	= $this->paginate_data.$return;
		}
		else
		{
			$return	= $return.$this->paginate_data;
		}
		
		return $return;
    }
	
	/* END search results */
	
	// --------------------------------------------------------------------

	/**
	 *	Forgot Username Form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function forgot_username()
    {	
		if ( ee()->TMPL->fetch_param('form_name') !== FALSE && ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
		}
		
		$this->form_data['id']	= ( ee()->TMPL->fetch_param('form_id') ) ? ee()->TMPL->fetch_param('form_id'): 'forgot_username_form';
		
		$this->form_data['ACT']	= ee()->functions->fetch_action_id('User', 'retrieve_username');
		
		$this->form_data['RET']	= ( ee()->TMPL->fetch_param('return') != '' ) ? ee()->TMPL->fetch_param('return'): '';
		
		$this->params['secure_action'] = ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';
									  
		return $this->_form();
    }
    
    /* END forgot username form */
    
    
	// --------------------------------------------------------------------

	/**
	 *	Forgot Username Form Processing
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function retrieve_username()
    {
        /** ----------------------------------------
        /**  Is user banned?
        /** ----------------------------------------*/
        
        if (ee()->session->userdata['is_banned'] == TRUE)
		{            
            return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
		}
		
        /** ----------------------------------------
        /**  Error trapping
        /** ----------------------------------------*/
        
        if ( ! $address = ee()->input->post('email'))
        {
			return $this->_output_error('submission', array(ee()->lang->line('invalid_email_address')));
        }
        
		ee()->load->helper('email');
        
        if ( ! valid_email($address))
        {
			return $this->_output_error('submission', array(ee()->lang->line('invalid_email_address')));
        }
        
		$address = strip_tags($address);
        
        // Fetch user data
        
        $sql = "SELECT member_id, username, screen_name, email, language FROM exp_members 
        		WHERE email ='".ee()->db->escape_str($address)."'";
        
        $query = ee()->db->query($sql);
        
        if ($query->num_rows() == 0)
        {
			return $this->_output_error('submission', array(ee()->lang->line('no_email_found')));
        }
        
        $member_id		= $query->row('member_id');
        $username		= $query->row('username');
        $screen_name	= $query->row('screen_name');
        $address		= $query->row('email'); // Just incase there were tags in it...
        
        ee()->session->userdata['language'] = $query->row('language');
        
        /** --------------------------------------------
        /**  Where are we returning them to while they wait for the email?
        /** --------------------------------------------*/
                
		if (ee()->input->get_post('FROM') == 'forum')
		{
			if (ee()->input->get_post('board_id') !== FALSE && is_numeric(ee()->input->get_post('board_id')))
			{
				$query	= ee()->db->query("SELECT board_forum_url, board_id, board_label 
												FROM exp_forum_boards 
												WHERE board_id = '".ee()->db->escape_str(ee()->input->get_post('board_id'))."'");
			}
			else
			{
				$query	= ee()->db->query("SELECT board_forum_url, board_id, board_label 
												FROM exp_forum_boards WHERE board_id = '1'");
			}
			
			$return		= $query->row('board_forum_url');
			$site_name	= $query->row('board_label');
			$board_id	= $query->row('board_id');
		}
		else
		{
			$site_name	= stripslashes(ee()->config->item('site_name'));
			$return 	= ee()->config->item('site_url');
		}
                		
		$forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f&board_id='.$board_id : '';
        		
		$swap = array(
						'username'		=> $username,
						'screen_name'	=> $screen_name,
						'site_name'		=> $site_name,
						'site_url'		=> $return,
						'email'			=> $address,
						'member_id'		=> $member_id
					 );
					 
		$wquery = ee()->db->query("SELECT preference_value FROM exp_user_preferences 
        					  	   WHERE preference_name IN ('user_forgot_username_message')");
			
		if ($wquery->num_rows() == 0)
		{
			return $this->_output_error('submission', array(ee()->lang->line('error_sending_email')));
		}
                 
        // Instantiate the email class
             
        ee()->load->library('email');
		ee()->email->initialize();
        ee()->email->wordwrap = true;
        ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));	
        ee()->email->to($address); 
        ee()->email->subject(ee()->lang->line('forgotten_username_subject'));	
        ee()->email->message($this->_var_swap(stripslashes($wquery->row('preference_value')), $swap));	
        
        if ( ! ee()->email->Send())
        {
			return $this->_output_error('submission', array(ee()->lang->line('error_sending_email')));
        }
        
        /**	----------------------------------------
        /**	 Override Return
		/**	----------------------------------------*/
		
		if ( $this->_param('override_return') !== FALSE AND $this->_param('override_return') != '' )
		{	
			ee()->functions->redirect( $this->_param('override_return') );
			exit;
		}
        
        /**	----------------------------------------
		/**	 Set return
		/**	----------------------------------------*/
        
        if ( ee()->input->get_post('return') !== FALSE AND ee()->input->get_post('return') != '' )
        {
        	$return	= ee()->input->get_post('return');
        }
        elseif ( ee()->input->get_post('RET') !== FALSE AND ee()->input->get_post('RET') != '' )
        {
        	$return	= ee()->input->get_post('RET');
        }
        else
        {
        	$return = ee()->config->item('site_url');
        }
		
		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		$return	= $this->_chars_decode( $return );

        /** ----------------------------------------
        /**  Build success message
        /** ----------------------------------------*/
		                
        $data = array(	'title' 	=> ee()->lang->line('forgotten_username_subject'),
        				'heading'	=> ee()->lang->line('thank_you'),
        				'content'	=> ee()->lang->line('forgotten_username_sent'),
        				'link'		=> array($return, $site_name)
        			 );
			
		ee()->output->show_message($data);
    }
    /* END retrieve username */
    
    
	// --------------------------------------------------------------------

	/**
	 *	Forgot Password Form -> And Alias
	 *
	 *	@access		public
	 *	@return		string
	 */
	 
	public function forgot_password()
    {
		return $this->forgot();
	}

	public function forgot()
    {
		if ( ee()->TMPL->fetch_param('form_name') !== FALSE && ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
		}
		
		$this->form_data['id']			= ( ee()->TMPL->fetch_param('form_id') ) ? ee()->TMPL->fetch_param('form_id'): 'forgot_password_form';
		
		$this->form_data['ACT']			= ee()->functions->fetch_action_id('User', 'retrieve_password');
		
		$this->form_data['RET']			= ( ee()->TMPL->fetch_param('return') != '' ) ? ee()->TMPL->fetch_param('return'): '';
		
		$this->params['secure_action']	= ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';
		
		//$this->form_data['return']		= ( ee()->TMPL->fetch_param('return') !== FALSE ) ? ee()->TMPL->fetch_param('return'): '';
									  
		return $this->_form();
    }
    
    /* END forgot_password() */
    

	// --------------------------------------------------------------------

	/**
	 *	Forgot Password Processing
	 *
	 *	Let's come together everybody and just use EE!
	 *
	 *	@access		public
	 *	@return		null
	 */

	public function retrieve_password()
    {
    	if ( ! class_exists('Member'))
    	{
    		require PATH_MOD.'member/mod.member.php';
    	}
    	
    	$M = new Member();
    	
    	foreach(get_object_vars($this) as $key => $value)
		{
			$M->{$key} = $value;
		}
		
		/** --------------------------------------------
        /**  Set Language for Location, If Email Found.  Do NOT Return Error!
        /** --------------------------------------------*/
        
        if ( isset($_POST['email']))
        {
			$query = ee()->db->query("SELECT language FROM exp_members WHERE email ='".ee()->db->escape_str($_POST['email'])."'");
			
			if ($query->num_rows() > 0)
			{
				ee()->session->userdata['language'] = $query->row('language');
			}
		}
    	
    	$M->retrieve_password();
	}
	/* END retrieve_password() */
	
    
    // --------------------------------------------------------------------

	/**
	 *	Key Form
	 *
	 *	Allows the sending of a registration key to people, allowing them to Register on the site.
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function key()
    {
		/**	----------------------------------------
		/**	Allow registration?
		/**	----------------------------------------*/
		
		if ( ee()->config->item('allow_member_registration') != 'y' )
		{
			return $this->_output_error('general', array(ee()->lang->line('registration_not_enabled')));
		}
        
        /**	----------------------------------------
        /**	Is the current user logged in?
        /**	----------------------------------------*/
        
		if ( ee()->session->userdata('member_id') == 0 )
		{ 
			return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
        }
    	
		/**	----------------------------------------
		/**	Userdata
		/**	----------------------------------------*/
		
		$tagdata			= ee()->TMPL->tagdata;
    	
		/**	----------------------------------------
		/**	Member groups
		/**	----------------------------------------*/
		
		if ( preg_match( "/" . LD . 'member_groups' . RD . "(.*?)" . 
							   LD . preg_quote(T_SLASH, '/').'member_groups'.RD."/s", $tagdata, $match ) )
		{
			$query	= ee()->db->query( 
				"SELECT DISTINCT group_id, group_title 
				 FROM 			 exp_member_groups 
				 WHERE 			 group_id 
				 NOT IN 		 (1,2,3,4)" 
			);
			
			$groups	= '';
			
			if ( $query->num_rows() > 0 )
			{
				foreach ( $query->result_array() as $row )
				{
					$out	= $match['1'];
					$out	= str_replace( LD.'group_id'.RD, $row['group_id'], $out );
					$out	= str_replace( LD.'group_title'.RD, $row['group_title'], $out );
					$groups	.= $out;
				}
				
				$tagdata	= str_replace( $match[0], $groups, $tagdata );
			}
			else
			{
				$tagdata	= str_replace( $match[0], '', $tagdata );
			}
		}
    	
		/**	----------------------------------------
		/**	Prep data
		/**	----------------------------------------*/
		
		$this->form_data['tagdata']			= $tagdata;
		
		$this->form_data['ACT']				= ee()->functions->fetch_action_id('User', 'create_key');
		
        $this->form_data['RET']				= (isset($_POST['RET'])) ? 
													$_POST['RET'] : 
													ee()->functions->fetch_current_uri();
		
		if ( ee()->TMPL->fetch_param('form_name') !== FALSE AND
			 ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']		= ee()->TMPL->fetch_param('form_name');
		}
		
		$this->form_data['id']				= ( ee()->TMPL->fetch_param('form_id') ) ? 
													ee()->TMPL->fetch_param('form_id') : 
													'member_form';
		
		$this->params['template']			= ( ee()->TMPL->fetch_param('template') ) ? ee()->TMPL->fetch_param('template'): '';
		
		$this->params['html']				= $this->check_yes(ee()->TMPL->fetch_param('html')) ? 'yes': 'no';
		
		$this->params['word_wrap']			= ( ! $this->check_yes(ee()->TMPL->fetch_param('word_wrap'))) ? 'no': 'yes';
		
		$this->params['parse']				= ( in_array(ee()->TMPL->fetch_param('parse'), array('br', 'none', 'xhtml'))) ?
		 										ee()->TMPL->fetch_param('parse') : 'none';
		
		$this->params['return']				= ( ee()->TMPL->fetch_param('return') ) ? 
													ee()->TMPL->fetch_param('return') : '';
		
		$this->params['secure_action']		= ( ! $this->check_yes(ee()->TMPL->fetch_param('secure_action'))) ? 'no': 'yes';
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
        
		return $this->_form();
	}
	
	/* END key */
	
    
	// --------------------------------------------------------------------

	/**
	 *	Create Key
	 *
	 *	Processing the Key Form and sends out the Key
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function create_key()
    {   
        $email	= '';
        $hashes	= array();
        
		//	----------------------------------------
		//	Allow registration?
		//	----------------------------------------
		
		if ( ee()->config->item('allow_member_registration') != 'y' )
		{
			return $this->_output_error('general', array(ee()->lang->line('registration_not_enabled')));
		}
        
		//	----------------------------------------
        //	Is the current user logged in?
		//	----------------------------------------
        
		if ( ee()->session->userdata('member_id') == 0 )
		{ 
			return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
        }
		
        // ----------------------------------------
        //  Update last activity
        // ----------------------------------------
        
        $this->_update_last_activity();
        
		//	----------------------------------------
        //	Clear old hashes
		//	----------------------------------------
		
		$this->_clear_old_hashes();
        
		//	----------------------------------------
        //	Set base vars
		//	----------------------------------------
		
		$this->insert_data['author_id']		= ee()->session->userdata['member_id'];
		$this->insert_data['date']			= ee()->localize->now;
		$this->insert_data['group_id']		= ( ee()->input->get_post('group_id') ) ? 
													ee()->input->get_post('group_id'): '';
		
		// Invalid Member Group...
		if (in_array($this->insert_data['group_id'], array(1,2,3,4)))
		{
			return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
		}
		
		$vars						= array();
		                    		
		$vars['from_email']			= ( ee()->input->get_post('sender_email') !== FALSE ) ? 
											ee()->input->get_post('sender_email') :
											(( ee()->input->get_post('from') !== FALSE ) ? 
												ee()->input->get_post('from') : 
												ee()->config->item('webmaster_email')
											);
		$vars['from_name']			= ( ee()->input->get_post('sender_name') !== FALSE ) ? 
											ee()->input->get_post('sender_name') :
											(( ee()->input->get_post('name') !== FALSE ) ? 
												ee()->input->get_post('name') : 
												ee()->config->item('webmaster_name')
											);
		$vars['subject']			= ( ee()->input->get_post('subject') !== FALSE ) ? 
											ee()->input->get_post('subject') : 
											ee()->lang->line('you_are_invited_to_join') . ' ' . 
												ee()->config->item('site_name');
		$vars['message']			= ( ee()->input->get_post('message') !== FALSE ) ? 
											ee()->input->get_post('message') : '';
		                    		
		$vars['from_name']			= stripslashes( $vars['from_name'] );
		$vars['subject']			= stripslashes( $vars['subject'] );
		
		$vars['selected_group_id'] 	= $this->insert_data['group_id'];
		
		/** --------------------------------------------
        /**  Parse Template?
        /** --------------------------------------------*/
		
		if ( $tmp	= $this->_param('template') )
		{					
			$template = explode( "/", $tmp );
			
			$query = ee()->db->query(
				"SELECT t.template_type, t.template_data 
				 FROM 	exp_templates AS t 
				 JOIN 	exp_template_groups AS tg 
				 ON 	tg.group_id = t.group_id 
				 WHERE 	tg.group_name = '" . ee()->db->escape_str($template['0']) . "' 
				 AND 	t.template_name = '" . ee()->db->escape_str($template['1']) . "' 
				 LIMIT 	1" 
			);
			
			if ( $query->num_rows() == 0 )
			{
				return $this->_output_error('general', array(ee()->lang->line('template_not_found')));
			}
			
			// ----------------------------------------
			//  Instantiate template class
			// ----------------------------------------
			
			ee()->load->library('template');
			ee()->load->helper('text');
		
			/**	----------------------------------------
			/**	Set some values
			/**	----------------------------------------*/
			
			ee()->template->encode_email	= FALSE;
			
			ee()->template->global_vars	= array_merge( ee()->template->global_vars, $vars );
						
			if (APP_VER >= 2.0)
			{
				ee()->config->_global_vars 	= array_merge( ee()->config->_global_vars, $vars );
			}
			
			ee()->template->run_template_engine( $template['0'], $template['1'] );
		
			/**	----------------------------------------
			/**	Parse typography
			/**	----------------------------------------*/
		
			ee()->load->library('typography');
			
			if (APP_VER >= 2.0)
			{
				ee()->typography->initialize();
			}
			
			ee()->typography->smileys			= FALSE;
			ee()->typography->highlight_code	= TRUE;
			ee()->typography->convert_curly		= FALSE;
			
			$formatting['html_format']			= 'all';
			$formatting['auto_links']			= 'n';
			$formatting['allow_img_url']		= 'y';
			$formatting['text_format']			= 'none';
			                                	
			if ( in_array($this->_param('parse'), array('br', 'none', 'xhtml')))
			{
				$formatting['text_format'] = $this->_param('parse');
			}
			
			$body = ee()->typography->parse_type(
				stripslashes( 
					ee()->security->xss_clean( 
						ee()->template->final_template 
					) 
				), 
				$formatting
			);
		}
        
		/**	----------------------------------------
        /**	Are we sending email?
		/**	----------------------------------------*/
		
		$to = ee()->input->get_post('to') ? 
				ee()->input->get_post('to') :  
				(ee()->input->get_post('recipient_email') ? 
					ee()->input->get_post('recipient_email') : 
					FALSE
				);
		
		if ( $to )
		{
			$email	= explode( ",", $to );
			
			$email	= array_unique( $email );
			
			foreach ( $email as $e )
			{
				/**	----------------------------------------
				/**	Insert
				/**	----------------------------------------*/
				
				$this->insert_data['email']	= trim( $e );
				$this->insert_data['hash']	= ee()->functions->random( 'alpha' );
				$hashes[]					= $this->insert_data['hash'];
				
				/**	----------------------------------------
				/**	Prep vars
				/**	----------------------------------------*/
				
				$key		= $this->insert_data['hash'];
				
				ee()->db->query( ee()->db->insert_string( 'exp_user_keys', $this->insert_data ) );
				
				/**	----------------------------------------
				/**	Are we sending invitations?
				/**	----------------------------------------*/
				
				if ( $tmp	= $this->_param('template') )
				{				
					$message = str_replace(LD.'key'.RD, $key, $body);
					$message = str_replace(LD.'to_email'.RD, trim( $e ), $message);
				
					/**	----------------------------------------
					/**	Send email
					/**	----------------------------------------*/
					
					ee()->load->library('email');
					
					ee()->load->helper('text');
					
					ee()->email->initialize();
					ee()->email->wordwrap	= ( ee()->input->get_post('word_wrap') == 'yes' ) ? true: false;
					ee()->email->mailtype	= ( $this->_param('html') == 'yes' ) ? 'html': 'text';
					ee()->email->from( $vars['from_email'], $vars['from_name'] );	
					ee()->email->to( trim( $e ) ); 
					ee()->email->subject( $vars['subject'] );	
					ee()->email->message( entities_to_ascii( $message ) );		
					ee()->email->Send();
				}
			}
		}
		else
		{
			/**	----------------------------------------
			/**	Insert
			/**	----------------------------------------*/
			
			$this->insert_data['hash']	= ee()->functions->random( 'alpha' );
			$hashes[]					= $this->insert_data['hash'];
			
			ee()->db->query( ee()->db->insert_string( 'exp_user_keys', $this->insert_data ) );
		}
        
		/**	----------------------------------------
        /**	Return
		/**	----------------------------------------*/
		
		if ( $this->_param('return') !== FALSE AND $this->_param('return') != '' )
		{
			$return	= $this->_chars_decode( $this->_param('return') );
			
			ee()->functions->redirect( ee()->functions->create_url( str_replace( "%%key%%", implode( ",", $hashes ), $return ) ) );
		}
		else
		{
			$return	= ( ee()->input->get_post('return') ) ? ee()->input->get_post('return'): ee()->input->get_post('RET');
			
			$return	= $this->_chars_decode( $return );
			
			$data	= array(
							'title'		=> ee()->lang->line('key_created'),
							'heading'	=> ee()->lang->line('key_created'),
							'link'		=> array(
											$return,
											ee()->lang->line('back')
											),
							'content'	=> ee()->lang->line('key_success')
							);
		
			return ee()->output->show_message( $data, TRUE );
		}
	}
	
	/* END create key */
    
    // --------------------------------------------------------------------

	/**
	 *	Rank
	 *
	 *	Returns a ranked list of users based on their site participation. ::rolls eyes::
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function rank()
    {
    	$users	= array();
    	
		/**	----------------------------------------
		/**	Begin blog entries query
		/**	----------------------------------------*/
		
		if (APP_VER < 2.0)
		{
			$sql = "SELECT author_id AS member_id, entry_date AS date FROM exp_weblog_titles t WHERE author_id != '0'";
		}
		else
		{
			$sql = "SELECT author_id AS member_id, entry_date AS date FROM exp_channel_titles t WHERE author_id != '0'";
		}
		
		/**	----------------------------------------
		/**	Filter by member
		/**	----------------------------------------*/
		
		if ( ee()->TMPL->fetch_param('member_id') == 'CURRENT_USER' AND ee()->session->userdata['member_id'] != '0' )
		{
			$sql	.= " AND author_id = '".ee()->session->userdata['member_id']."'";
		}

		/**	----------------------------------------
		/**	Exclude members
		/**	----------------------------------------*/
		
		if ( ee()->TMPL->fetch_param('exclude') )
		{
			$exclude	= explode( ",", ee()->TMPL->fetch_param('exclude') );
			
			if ( count( $exclude ) > 0 )
			{
				$sql	.= " AND author_id NOT IN (".implode( ",", $exclude ).")";
			}
			
		}

		/**	----------------------------------------
		/**	Add status declaration
		/**	----------------------------------------*/
						
		if ($status = ee()->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);
			
			$sstr = ee()->functions->sql_andor_string($status, 't.status');
			
			if ( strpos($sstr, "'closed'") === FALSE)
			{
				$sstr .= " AND t.status != 'closed' ";
			}
			
			$sql .= $sstr;
		}
		else
		{
			$sql .= "AND t.status = 'open' ";
		}
	
		/**	----------------------------------------
		/**	Days limit
		/**	----------------------------------------*/
		
		if ( $days = ee()->TMPL->fetch_param('days') )
		{
			$time	= ee()->localize->now - ( $days * 60 * 60 * 24);
			$sql	.= " AND t.entry_date > $time";
		}
		
		/**	----------------------------------------
		/**	Group
		/**	----------------------------------------*/
		
		// $sql	.= " GROUP BY member_id";
		
		/**	----------------------------------------
		/**	Order
		/**	----------------------------------------*/
		
		$sql	.= " ORDER BY member_id DESC, date DESC";
		
		/**	----------------------------------------
		/**	Execute
		/**	----------------------------------------*/
		
		$query	= ee()->db->query( $sql );
	
		/**	----------------------------------------
		/**	Assemble
		/**	----------------------------------------*/
		
		if ( $query->num_rows() > 0 )
		{
			$i		= ( ee()->TMPL->fetch_param('entries_per_day') ) ? ee()->TMPL->fetch_param('entries_per_day'): 3;
			
			$iarr	= range( 0, $i );
			
			foreach ( $query->result_array() as $row )
			{
				/**	----------------------------------------
				/**	Add value
				/**	----------------------------------------*/
				
				if ( $i = next($iarr) )
				{
					$users[ $row['member_id'] ]['entry'.date( "ymd", $row['date'] ).$i]	= 1;
				}
				else
				{
					reset($iarr);
				}
			}
		}
	
		/**	----------------------------------------
		/**	Begin comments query
		/**	----------------------------------------*/
		
		if (APP_VER < 2.0)
		{
			$sql = "SELECT c.author_id AS member_id, c.comment_date AS date FROM exp_comments c 
					LEFT JOIN exp_weblog_titles t ON t.entry_id = c.entry_id 
					WHERE c.author_id != '0'";
		}
		else
		{
			$sql = "SELECT c.author_id AS member_id, c.comment_date AS date FROM exp_comments c 
					LEFT JOIN exp_channel_titles t ON t.entry_id = c.entry_id 
					WHERE c.author_id != '0'";
		}
	
		/**	----------------------------------------
		/**	Filter by member
		/**	----------------------------------------*/
		
		if ( ee()->TMPL->fetch_param('member_id') == 'CURRENT_USER' AND ee()->session->userdata['member_id'] != '0' )
		{
			$sql	.= " AND c.author_id = '".ee()->session->userdata['member_id']."'";
		}
	
		/**	----------------------------------------
		/**	Exclude members
		/**	----------------------------------------*/
		
		if ( ee()->TMPL->fetch_param('exclude') )
		{
			$sql	.= " AND c.author_id NOT IN (".ee()->TMPL->fetch_param('exclude').")";
		}

		/**	----------------------------------------
		/**	Add status declaration
		/**	----------------------------------------*/
						
		if ($status = ee()->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);
			
			$sstr = ee()->functions->sql_andor_string($status, 't.status');
			
			if ( strpos($sstr, "'closed'") === FALSE)
			{
				$sstr .= " AND t.status != 'closed' ";
			}
			
			$sql .= $sstr;
		}
		else
		{
			$sql .= "AND t.status = 'open' ";
		}
		
		/**	----------------------------------------
		/**	Days limit
		/**	----------------------------------------*/
		
		if ( $days = ee()->TMPL->fetch_param('days') )
		{
			$time	= ee()->localize->now - ( $days * 60 * 60 * 24);
			$sql	.= " AND c.comment_date > $time";
		}
	
		/**	----------------------------------------
		/**	Order
		/**	----------------------------------------*/
		
		$sql	.= " ORDER BY member_id DESC, date DESC";
		
		/**	----------------------------------------
		/**	Execute
		/**	----------------------------------------*/
		
		$query	= ee()->db->query( $sql );
	
		/**	----------------------------------------
		/**	Assemble
		/**	----------------------------------------*/
		
		if ( $query->num_rows() > 0 )
		{
			$i		= ( ee()->TMPL->fetch_param('comments_per_day') ) ? ee()->TMPL->fetch_param('comments_per_day'): 3;
			
			$iarr	= range( 0, $i );
			
			foreach ( $query->result_array() as $row )
			{
				/**	----------------------------------------
				/**	Add value
				/**	----------------------------------------*/
				
				if ( $i = next($iarr) )
				{
					$users[ $row['member_id'] ]['comment'.date( "ymd", $row['date'] ).$i]	= 1;
				}
				else
				{
					reset($iarr);
				}
			}
		}
	
		/**	----------------------------------------
		/**	Begin favorites
		/**	----------------------------------------*/
		
		if ( ee()->db->table_exists('exp_favorites') )
		{
			/**	----------------------------------------
			/**	Begin favorites query
			/**	----------------------------------------*/
			
			if (APP_VER < 2.0)
			{
				$sql	= "SELECT f.member_id AS member_id, f.entry_date AS date FROM exp_favorites f 
						   LEFT JOIN exp_weblog_titles t ON t.entry_id = f.entry_id 
						   WHERE f.member_id != '0'";
			}
			else
			{
				$sql	= "SELECT f.member_id AS member_id, f.entry_date AS date FROM exp_favorites f 
						   LEFT JOIN exp_channel_titles t ON t.entry_id = f.entry_id 
						   WHERE f.member_id != '0'";
			}
			
			/**	----------------------------------------
			/**	Filter by member
			/**	----------------------------------------*/
		
			if ( ee()->TMPL->fetch_param('member_id') == 'CURRENT_USER' AND ee()->session->userdata['member_id'] != '0' )
			{
				$sql	.= " AND f.member_id = '".ee()->session->userdata['member_id']."'";
			}
	
			/**	----------------------------------------
			/**	Exclude members
			/**	----------------------------------------*/
			
			if ( ee()->TMPL->fetch_param('exclude') )
			{
				$sql	.= " AND f.member_id NOT IN (".ee()->TMPL->fetch_param('exclude').")";
			}
		
			/**	----------------------------------------
			/**	Add status declaration
			/**	----------------------------------------*/
							
			if ($status = ee()->TMPL->fetch_param('status'))
			{
				$status = str_replace('Open',   'open',   $status);
				$status = str_replace('Closed', 'closed', $status);
				
				$sstr = ee()->functions->sql_andor_string($status, 't.status');
				
				if ( strpos($sstr, "'closed'") === FALSE)
				{
					$sstr .= " AND t.status != 'closed' ";
				}
				
				$sql .= $sstr;
			}
			else
			{
				$sql .= "AND t.status = 'open' ";
			}
			
			/**	----------------------------------------
			/**	Days limit
			/**	----------------------------------------*/
			
			if ( $days = ee()->TMPL->fetch_param('days') )
			{
				$time	= ee()->localize->now - ( $days * 60 * 60 * 24);
				$sql	.= " AND t.entry_date > $time";
			}
			
			/**	----------------------------------------
			/**	Order
			/**	----------------------------------------*/
			
			$sql	.= " ORDER BY member_id DESC, date DESC";
			
			/**	----------------------------------------
			/**	Execute
			/**	----------------------------------------*/
			
			$query	= ee()->db->query( $sql );
			
			/**	----------------------------------------
			/**	Assemble
			/**	----------------------------------------*/
			
			if ( $query->num_rows() > 0 )
			{
				$i		= ( ee()->TMPL->fetch_param('favorites_per_day') ) ? ee()->TMPL->fetch_param('favorites_per_day'): 3;
				
				$iarr	= range( 0, $i );
				
				foreach ( $query->result_array() as $row )
				{
					/**	----------------------------------------
					/**	Add value
					/**	----------------------------------------*/
					
					if ( $i = next($iarr) )
					{
						$users[ $row['member_id'] ]['favorite'.date( "ymd", $row['date'] ).$i]	= 1;
					}
					else
					{
						reset($iarr);
					}
				}
			}
		}
	
		
		/**	----------------------------------------
		/**	Wrap up users array
		/**	----------------------------------------*/
		
		$total	= 0;
		
		foreach ( $users as $key => $val )
		{
			$tot			= array_sum($val);
			$users[$key]	= $tot;
			$total			= $total + $tot;
		}
		
		/**	----------------------------------------
		/**	Parse total
		/**	----------------------------------------
		/*	If we are getting totals for only one
		/*	member then we obviously don't care
		/*	about getting a ranked list, so we are
		/*	going to return once the total stuff is
		/*	done.
		/**	----------------------------------------*/
		
		if ( ee()->TMPL->fetch_param('member_id') )
		{
			$cond['total']			= $total;
			ee()->TMPL->tagdata			= ee()->functions->prep_conditionals( ee()->TMPL->tagdata, $cond );
			
			return ee()->TMPL->tagdata	= str_replace( LD.'total'.RD, $total, ee()->TMPL->tagdata );
		}
		
		/**	----------------------------------------
		/**	Empty total?
		/**	----------------------------------------*/
		
		if ( $total == 0 )
		{
			return $this->no_results('user');
		}
		
		/**	----------------------------------------
		/**	Sort users array
		/**	----------------------------------------*/
		
		arsort( $users );
		
		/**	----------------------------------------
		/**	Limit
		/**	----------------------------------------*/
		
		if ( ! $this->limit = ee()->TMPL->fetch_param('limit') )
		{
			$this->limit	= 50;
		}
		
		$users	= array_chunk( $users, $this->limit, TRUE );
		
		$users	= $users[0];
		
		/**	----------------------------------------
		/**	Grab member data
		/**	----------------------------------------*/
		
		$mems	= implode( ",", array_keys( $users ) );
		
		$mems	= ( stristr( ',', $mems ) ) ? substr( $mems, 0, -1 ): $mems;
		
		$query	= ee()->db->query( "SELECT member_id, screen_name FROM exp_members 
							   WHERE member_id IN (".ee()->db->escape_str( $mems ).")" );
		
		/**	----------------------------------------
		/**	Empty?
		/**	----------------------------------------*/
		
		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}
		
		/**	----------------------------------------
		/**	Reorder
		/**	----------------------------------------*/
		
		$new	= array();

        foreach ( $users as $key => $val )
        {
        	foreach ( $query->result_array() as $k => $row )
        	{
        		if ( $row['member_id'] == $key )
        		{
        			$new[]	= $row;
        		}
        	}
        }
		
		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/
		
		$r	= '';
		$i	= 1;
		
		foreach ( $new as $row )
		{
			$row['order']	= $i++;
			$row['count']	= $users[ $row['member_id'] ];
			$row['total']	= $users[ $row['member_id'] ];
			
			$tagdata	= ee()->TMPL->tagdata;
			
			foreach ( ee()->TMPL->var_single as $val )
			{				
				if ( isset( $row[$val] ) )
				{
					$tagdata	= ee()->TMPL->swap_var_single( $val, $row[$val], $tagdata );
				}
			}
			
			$r	.= $tagdata;
		}
		
		return $r;
	}
	
	/* END rank */
    
	// --------------------------------------------------------------------

	/**
	 *	Inbox Count
	 *
	 *	Returns the number of unread Message in one's Private Message InBox
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function inbox_count()
    {	
    	$tagdata	= ee()->TMPL->tagdata;
    	
		/**	----------------------------------------
		/**	Logged in?
		/**	----------------------------------------*/
		
		if ( ee()->session->userdata('member_id') == 0 )
		{
			return str_replace( LD."count".RD, "0", $tagdata );
		}
    	
		/**	----------------------------------------
		/**	Grab count
		/**	----------------------------------------*/
		
		$query	= ee()->db->query( "SELECT COUNT(*) AS count FROM exp_message_copies WHERE recipient_id = '".ee()->db->escape_str( ee()->session->userdata('member_id') )."' AND message_read = 'n' AND message_deleted = 'n' AND message_folder = '1'" );
    	
		/**	----------------------------------------
		/**	Conditionals
		/**	----------------------------------------*/
		
		$cond['count']	= $query->row('count');
		
		$tagdata		= ee()->functions->prep_conditionals( $tagdata, $cond );
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		$tagdata		= str_replace( LD."count".RD, $query->row('count'), $tagdata );
		
		return $tagdata;
    }
    
    /* END inbox count */
	
	// --------------------------------------------------------------------

	/**
	 *	Self Delete Confirmation Form
	 *
	 *	Do you really want to destroy your member account on this installation of EE?  I mean, isn't
	 *	that pretty darn severe?  Can't we at least be Friends?
	 *
	 *	@access		public
	 *	@return		string
	 */
	
	public function delete_form()
	{	
		/**	----------------------------------------
		/**	 Member ID
		/**	----------------------------------------*/
		
		if ( ! $this->_member_id() )
		{
    		return $this->no_results('user');
		}
		
		$this->params['member_id'] = $this->member_id;
		
		/**	----------------------------------------
		/**	 Authorized?
		/**   - Remember that the 'can_delete_self' preference is on a per-Site basis
		/**	----------------------------------------*/
		
		if ($this->member_id == ee()->session->userdata['member_id'] && (ee()->session->userdata['can_delete_self'] !== 'y' OR ee()->session->userdata['group_id'] == 1))
		{
			return $this->_output_error('general', ee()->lang->line('cannot_delete_self'));
		}
		elseif (ee()->session->userdata['member_id'] == 0)
		{
			return '';
		}
		elseif($this->member_id != ee()->session->userdata['member_id'] && ee()->session->userdata['group_id'] != 1 && ee()->session->userdata['can_delete_members'] != 'y')
		{
			return '';
		}
		else
		{	
		
			/**	----------------------------------------
			/**	Grab member data
			/**	----------------------------------------*/
			
			$query = ee()->db->query("SELECT email, group_id, member_id, screen_name, username
					   			 FROM exp_members WHERE member_id = '".ee()->db->escape_str( $this->member_id )."'");
			
			if ( $query->num_rows() == 0 )
			{
				return $this->no_results('user');
			}
			
			$query_row = $query->row_array();
			
			/**	----------------------------------------
			/**	Parse Variables
			/**	----------------------------------------*/
			
			$tagdata 	= ee()->TMPL->tagdata;
			
			foreach($query->row() as $name => $value)
			{
				$query_row['user:'.$name] = $value; // Prefixed variables
			}
			
			ee()->functions->prep_conditionals($tagdata, $query_row);
			
			foreach($query_row as $name => $value)
			{
				$tagdata	= str_replace( LD.$name.RD, $value, $tagdata );
			}
			
			/**	----------------------------------------
			/**	 Create Form
			/**	----------------------------------------*/
			
			$this->form_data['tagdata']	= $tagdata;
		
			$this->form_data['onsubmit']	= "if(!confirm('".ee()->lang->line('user_delete_confirm')."')) return false;";
			
			if ( ee()->TMPL->fetch_param('form_name') !== FALSE && ee()->TMPL->fetch_param('form_name') != '' )
			{
				$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
			}
			
			$this->form_data['id']	= ( ee()->TMPL->fetch_param('form_id') ) ? ee()->TMPL->fetch_param('form_id'): 'member_delete_form';
			
			$this->form_data['ACT']	= ee()->functions->fetch_action_id('User', 'delete_account');
			
			$this->form_data['RET']	= ( ee()->TMPL->fetch_param('return') != '' ) ? ee()->TMPL->fetch_param('return'): '';
			
			$this->params['secure_action'] = ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';
										  
			return $this->_form();
		}
	}
	/* END */

	// --------------------------------------------------------------------

	/**
	 *	Delete Member Account Processing
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function delete_account()
    {    
        /**	----------------------------------------
        /**  Authorization Check
        /**	----------------------------------------*/        
        
		if ( $this->_param('member_id') == FALSE OR ! ctype_digit($this->_param('member_id')) OR ! isset($_POST['ACT']))
		{
            return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
        }
        
        if (ee()->session->userdata['member_id'] == 0)
		{
			return $this->_output_error('general', ee()->lang->line('not_authorized'));
		}
		
		// If not deleting yourself, you must be a SuperAdmin or have Delete Member permissions
		// If deleting yourself, you must have permission to do so.
		
		if ($this->_param('member_id') != ee()->session->userdata['member_id'])
		{
			if (ee()->session->userdata['group_id'] != 1 && ee()->session->userdata['can_delete_members'] != 'y')
			{
				return $this->_output_error('general', ee()->lang->line('not_authorized'));
			}
		}
		elseif (ee()->session->userdata['can_delete_self'] !== 'y')
		{
			return $this->_output_error('general', ee()->lang->line('not_authorized'));
		}
		
		$admin = (ee()->session->userdata['member_id'] != $this->_param('member_id')) ? TRUE : FALSE;
		
		/** --------------------------------------------
        /**  Member Data
        /** --------------------------------------------*/
		
		$query = ee()->db->query("SELECT m.member_id, m.group_id, m.password, m.email, m.screen_name,
									mg.mbr_delete_notify_emails
							 FROM exp_members AS m, exp_member_groups AS mg
							 WHERE m.member_id = '".ee()->db->escape_str($this->_param('member_id'))."'
							 AND m.group_id = mg.group_id");
							 
		if ($query->num_rows() == 0)
		{
			return $this->_output_error('general', ee()->lang->line('not_authorized'));
		}
		
		/** -------------------------------------
		/**  One cannot delete a SuperAdmin from the User side.  Sorry...
		/** -------------------------------------*/
		
		if($query->row('group_id') == 1)
		{
			return $this->_output_error('general', ee()->lang->line('cannot_delete_super_admin'));
		}
		
		/** --------------------------------------------
        /**  Variables!
        /** --------------------------------------------*/
		
		$id							= $query->row('member_id');
		$check_password				= $query->row('password');
		$mbr_delete_notify_emails	= $query->row('mbr_delete_notify_emails');
		$screen_name				= $query->row('screen_name');
		$email						= $query->row('email');
		
		/** ----------------------------------------
        /**  Is IP and User Agent required for login?  Then, same here.
        /** ----------------------------------------*/
    
        if (ee()->config->item('require_ip_for_login') == 'y')
        {
			if (ee()->session->userdata['ip_address'] == '' || ee()->session->userdata['user_agent'] == '')
			{
            	return $this->_output_error('general', ee()->lang->line('unauthorized_request'));
           	}
        }
        
		/** ----------------------------------------
        /**  Check password lockout status
        /** ----------------------------------------*/
		
		if (ee()->session->check_password_lockout() === TRUE)
		{
            return $this->_output_error('general', str_replace("%x", ee()->config->item('password_lockout_interval'), ee()->lang->line('password_lockout_in_effect')));
		}
		
		/* -------------------------------------
		/*  If deleting self, you must submit your password.
		/*  If SuperAdmin deleting another, must submit your password
		/* -------------------------------------*/
		
		$password = ee()->functions->hash(stripslashes(ee()->input->post('password')));
		
		// Fetch the SAs password instead as they are the one doing the deleting
		if (ee()->session->userdata['member_id'] != $this->_param('member_id'))
		{
			$squery = ee()->db->query("SELECT password FROM exp_members 
								 WHERE member_id = '".ee()->db->escape_str(ee()->session->userdata['member_id'])."'");
								 
			$check_password = $squery->row('password');
			
			unset($squery);
		}
		
		if ($check_password != $password)
		{
			ee()->session->save_password_lockout();
			
			return $this->_output_error('general', ee()->lang->line('invalid_pw'));
		}
		
		/** -------------------------------------
		/**  No turning back, get to deletin'!
		/** -------------------------------------*/

		ee()->db->query("DELETE FROM exp_members WHERE member_id = '{$id}'");
		ee()->db->query("DELETE FROM exp_member_data WHERE member_id = '{$id}'");
		ee()->db->query("DELETE FROM exp_member_homepage WHERE member_id = '{$id}'");
		
		$message_query = ee()->db->query("SELECT DISTINCT recipient_id FROM exp_message_copies WHERE sender_id = '{$id}' AND message_read = 'n'");
		ee()->db->query("DELETE FROM exp_message_copies WHERE sender_id = '{$id}'");
		ee()->db->query("DELETE FROM exp_message_data WHERE sender_id = '{$id}'");
		ee()->db->query("DELETE FROM exp_message_folders WHERE member_id = '{$id}'");
		ee()->db->query("DELETE FROM exp_message_listed WHERE member_id = '{$id}'");
		
		if ($message_query->num_rows() > 0)
		{
			foreach($message_query->result_array() as $row)
			{
				$count_query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_message_copies WHERE recipient_id = '".$row['recipient_id']."' AND message_read = 'n'");
				ee()->db->query(ee()->db->update_string('exp_members', array('private_messages' => $count_query->row('count')), "member_id = '".$row['recipient_id']."'"));
			}
		}
				
		/** -------------------------------------
		/**  Delete Forum Posts
		/** -------------------------------------*/
		
		if (ee()->config->item('forum_is_installed') == "y")
		{
			ee()->db->query("DELETE FROM exp_forum_subscriptions  WHERE member_id = '{$id}'"); 
			ee()->db->query("DELETE FROM exp_forum_pollvotes  WHERE member_id = '{$id}'"); 
			 
			ee()->db->query("DELETE FROM exp_forum_topics WHERE author_id = '{$id}'");
			
			// Snag the affected topic id's before deleting the member for the update afterwards
			$query = ee()->db->query("SELECT topic_id FROM exp_forum_posts WHERE author_id = '{$id}'");
			
			if ($query->num_rows() > 0)
			{
				$topic_ids = array();
				
				foreach ($query->result_array() as $row)
				{
					$topic_ids[] = $row['topic_id'];
				}
				
				$topic_ids = array_unique($topic_ids);
			}
			
			ee()->db->query("DELETE FROM exp_forum_posts  WHERE author_id = '{$id}'");
			ee()->db->query("DELETE FROM exp_forum_polls  WHERE author_id = '{$id}'");
						
			// Update the forum stats			
			$query = ee()->db->query("SELECT forum_id FROM exp_forums WHERE forum_is_cat = 'n'");
			
			if ( ! class_exists('Forum'))
			{
				require PATH_MOD.'forum/mod.forum'.EXT;
				require PATH_MOD.'forum/mod.forum_core'.EXT;
			}
			
			$FRM = new Forum_Core;
			
			foreach ($query->result_array() as $row)
			{
				$FRM->_update_post_stats($row['forum_id']);
			}
			
			if (isset($topic_ids))
			{
				foreach ($topic_ids as $topic_id)
				{
					$FRM->_update_topic_stats($topic_id);
				}
			}
		}
		
		/** -------------------------------------
		/**  Va-poo-rize Weblog Entries and Comments
		/** -------------------------------------*/
		
		$entry_ids			= array();
		$channel_ids		= array();
		$recount_ids		= array();
		
		// Find Entry IDs and Channel IDs, then DELETE! DELETE, WHA HA HA HA!!
		if (APP_VER < 2.0)
		{
			$query = ee()->db->query("SELECT entry_id, weblog_id AS channel_id FROM exp_weblog_titles WHERE author_id = '{$id}'");
		}
		else
		{
			$query = ee()->db->query("SELECT entry_id, channel_id FROM exp_channel_titles WHERE author_id = '{$id}'");
		}
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$entry_ids[]	= $row['entry_id'];
				$channel_ids[]	= $row['channel_id'];
			}
			
			if (APP_VER < 2.0)
			{
				ee()->db->query("DELETE FROM exp_weblog_titles WHERE author_id = '{$id}'");
				ee()->db->query("DELETE FROM exp_weblog_data WHERE entry_id IN ('".implode("','", $entry_ids)."')");
			}
			else
			{
				ee()->db->query("DELETE FROM exp_channel_titles WHERE author_id = '{$id}'");
				ee()->db->query("DELETE FROM exp_channel_data WHERE entry_id IN ('".implode("','", $entry_ids)."')");
			}
			
			ee()->db->query("DELETE FROM exp_comments WHERE entry_id IN ('".implode("','", $entry_ids)."')");
			ee()->db->query("DELETE FROM exp_trackbacks WHERE entry_id IN ('".implode("','", $entry_ids)."')");
		}
		
		// Find the affected entries AND channel ids for author's comments
		if (APP_VER < 2.0)
		{
			$query = ee()->db->query("SELECT DISTINCT(entry_id), weblog_id AS channel_id FROM exp_comments WHERE author_id = '{$id}'");
		}
		else
		{
			$query = ee()->db->query("SELECT DISTINCT(entry_id), channel_id FROM exp_comments WHERE author_id = '{$id}'");
		}
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$recount_ids[] = $row['entry_id'];
				$channel_ids[] = $row['channel_id'];
			}
			
			$recount_ids = array_diff($recount_ids, $entry_ids);
		}
		
		// Delete comments by member
		ee()->db->query("DELETE FROM exp_comments WHERE author_id = '{$id}'");
		
		// Update stats on channel entries that were NOT deleted AND had comments by author
		
		if (count($recount_ids) > 0)
		{
			foreach (array_unique($recount_ids) as $entry_id)
			{
				$query = ee()->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".ee()->db->escape_str($entry_id)."'");
				
				$comment_date = ($query->num_rows() == 0 OR !is_numeric($query->row('max_date'))) ? 0 : $query->row('max_date');
				
				$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '{$entry_id}' AND status = 'o'");				
				
				if (APP_VER < 2.0)
				{
					ee()->db->query("UPDATE exp_weblog_titles SET	comment_total = '".ee()->db->escape_str($query->row('count'))."', 
																		recent_comment_date = '$comment_date' WHERE entry_id = '{$entry_id}'");
				}
				else
				{
					ee()->db->query("UPDATE exp_channel_titles SET comment_total = '".ee()->db->escape_str($query->row('count'))."',
																		recent_comment_date = '$comment_date' WHERE entry_id = '{$entry_id}'");
				}
			}
		}
		
		foreach (array_unique($channel_ids) as $channel_id)
		{
			if (APP_VER < 2.0)
			{
				ee()->stats->update_weblog_stats($channel_id);
			}
			else
			{
				ee()->stats->update_channel_stats($channel_id);
			}
			
			ee()->stats->update_comment_stats($channel_id);
		}
		
		/** -------------------------------------
		/**  Email notification recipients
		/** -------------------------------------*/

		if ($mbr_delete_notify_emails != '')
		{
			$notify_address = $mbr_delete_notify_emails;
			
			$swap = array(
							'name'				=> $screen_name,
							'email'				=> $email,
							'site_name'			=> stripslashes(ee()->config->item('site_name'))
						 );
			
			$email_tit = ee()->functions->var_swap(ee()->lang->line('mbr_delete_notify_title'), $swap);
			$email_msg = ee()->functions->var_swap(ee()->lang->line('mbr_delete_notify_message'), $swap);
							   
			// No notification for the user themselves, if they're in the list
			if (stristr($notify_address, $email))
			{
				$notify_address = str_replace($email, "", $notify_address);				
			}
			
			ee()->load->helper('string');
			
			$notify_address = reduce_multiples($notify_address, ',', TRUE);
			
			if ($notify_address != '')
			{				
				/** ----------------------------
				/**  Send email
				/** ----------------------------*/
				
				ee()->load->library('email');
				
				ee()->load->helper('text');
				
				foreach (explode(',', $notify_address) as $addy)
				{
					ee()->email->initialize();
					ee()->email->wordwrap = false;
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));	
					ee()->email->to($addy); 
					ee()->email->reply_to(ee()->config->item('webmaster_email'));
					ee()->email->subject($email_tit);	
					ee()->email->message(entities_to_ascii($email_msg));		
					ee()->email->Send();
				}
			}			
		}
		
		/** -------------------------------------
		/**  Trash the Session and cookies
		/** -------------------------------------*/

        ee()->db->query("DELETE FROM exp_online_users WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND ip_address = '{ee()->input->ip_address()}' AND member_id = '{$id}'");

        ee()->db->query("DELETE FROM exp_sessions WHERE member_id = '".$id."'");
        
        if ($admin === FALSE)
        {
        	ee()->functions->set_cookie(ee()->session->c_uniqueid);       
			ee()->functions->set_cookie(ee()->session->c_password);   
			ee()->functions->set_cookie(ee()->session->c_session);   
			ee()->functions->set_cookie(ee()->session->c_expire);   
			ee()->functions->set_cookie(ee()->session->c_anon);  
			ee()->functions->set_cookie('read_topics');  
			ee()->functions->set_cookie('tracker');
		}
		
		if (ee()->extensions->active_hook('user_delete_account_end') === TRUE)
		{
			$edata = ee()->extensions->universal_call_extension('user_delete_account_end', $this);
			if (ee()->extensions->end_script === TRUE) return;
		}
		
		/**	----------------------------------------
        /**	 Override Return
		/**	----------------------------------------*/
		
		if ( $this->_param('override_return') !== FALSE AND $this->_param('override_return') != '' )
		{	
			ee()->functions->redirect( $this->_param('override_return') );
			exit;
		}

		/**	----------------------------------------
		/**	 Set return
		/**	----------------------------------------*/
        
        if ( ee()->input->get_post('return') !== FALSE AND ee()->input->get_post('return') != '' )
        {
        	$return	= ee()->input->get_post('return');
        }
        elseif ( ee()->input->get_post('RET') !== FALSE AND ee()->input->get_post('RET') != '' )
        {
        	$return	= ee()->input->get_post('RET');
        }
        else
        {
        	$return = ee()->config->item('site_url');
        }
		
		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		$return	= $this->_chars_decode( $return );
		
		/** -------------------------------------
		/**  Build Success Message
		/** -------------------------------------*/
		
		$name	= stripslashes(ee()->config->item('site_name'));
		
		$data = array(	'title' 	=> ee()->lang->line('mbr_delete'),
        				'heading'	=> ee()->lang->line('thank_you'),
        				'content'	=> ee()->lang->line('mbr_account_deleted'),
        				'redirect'	=> $return,
        			 );
					
		ee()->output->show_message($data);     
	}
	
	/* END reg */
    // --------------------------------------------------------------------

	/**
	 *	Private Messaging
	 *
	 *	This returns the contebnts of a message box
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function messages()
    {	
		/**	----------------------------------------
		/**	Logged in?
		/**	----------------------------------------*/
		
		if ( ee()->session->userdata('member_id') == 0 )
		{
			return $this->no_results('user');
		}
    	
		/**	----------------------------------------
		/**	Which box?
		/**	----------------------------------------*/
		
		$folder	= '1';
		
		if ( ee()->TMPL->fetch_param( 'folder' ) !== FALSE AND ctype_digit( ee()->TMPL->fetch_param( 'folder' ) ) )
		{
			$folder	= ee()->TMPL->fetch_param( 'folder' );
		}
    	
		/**	----------------------------------------
		/**	Grab messages
		/**	----------------------------------------*/
		
		$sql	= "SELECT mc.copy_id AS message_id, md.message_subject, md.message_date FROM exp_message_copies mc LEFT JOIN exp_message_data md ON md.message_id = mc.message_id WHERE mc.recipient_id = '".ee()->db->escape_str( ee()->session->userdata('member_id') )."' AND mc.message_deleted = 'n' AND mc.message_folder = '".ee()->db->escape_str( $folder )."'";
		
		$sql	.= " ORDER BY md.message_date DESC";
    	
		/**	----------------------------------------
		/**	Limit?
		/**	----------------------------------------*/
		
		if ( ee()->TMPL->fetch_param( 'limit' ) !== FALSE AND ctype_digit( ee()->TMPL->fetch_param( 'limit' ) ) )
		{
			$sql	.= " LIMIT ".ee()->TMPL->fetch_param( 'limit' );
		}
		
		$query	= ee()->db->query( $sql );
    	
		/**	----------------------------------------
		/**	Empty?
		/**	----------------------------------------*/
		
		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}
    	
		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/
		
		$r	= "";
		
		foreach ( $query->result_array() as $row )
		{
			$tagdata	= ee()->TMPL->tagdata;
			
			/**	----------------------------------------
			/**	Conditionals
			/**	----------------------------------------*/
			
			$cond			= $row;
			
			$tagdata		= ee()->functions->prep_conditionals( $tagdata, $cond );
			
			/**	----------------------------------------
			/**	Vars
			/**	----------------------------------------*/
			
			foreach ( ee()->TMPL->var_single as $key )
			{
				if ( isset( $row[$key] ) === TRUE )
				{
					$tagdata	= str_replace( LD.$key.RD, $row[$key], $tagdata );
				}
			}
			
			/**	----------------------------------------
			/**	Concat
			/**	----------------------------------------*/
			
			$r	.= $tagdata;
		}
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		return $r;
    }
    
    /* END messages */
    
    // --------------------------------------------------------------------

	/**
	 *	Is Mine
	 *
	 *	Evaluates Wheter the Logged in Member is the Same as the Member ID sent
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function is_mine()
    {	
    	$cond['mine']		= FALSE;
    	$cond['not_mine']	= TRUE;
    	
    	if ( $this->_member_id() === TRUE )
    	{    		
    		if ( $this->member_id == ee()->session->userdata('member_id') OR ee()->session->userdata('group_id') == '1' )
    		{
				$cond['mine']		= TRUE;
				$cond['not_mine']	= FALSE;
    		}
    	}
    	
    	ee()->TMPL->tagdata	= ee()->functions->prep_conditionals( ee()->TMPL->tagdata, $cond );
    	
    	return ee()->TMPL->tagdata;
    }
    
    /* END is mine */
    
	// --------------------------------------------------------------------

	/**
	 *	Reassign Jump
	 *
	 *	@access		public
	 *	@return		string
	 */
    
	public function reassign_jump()
    {	
    	return ee()->functions->redirect( ee()->config->item('cp_url')."?C=modules&M=user&P=reassign_ownership_confirm&member_id=".ee()->input->get_post('member_id')."&entry_id=".ee()->input->get_post('entry_id') );
    }
    
    /* END reassign jump */
    
    
	// --------------------------------------------------------------------

	/**
	 * User Authors Placeholder?
	 *
	 * @access	public
	 * @return	null
	 */
	 
	public function user_authors()
	{
		if (APP_VER < 2.0 && ! class_exists('Weblog'))
		{
			require PATH_MOD.'/weblog/mod.weblog'.EXT;
		}
		
		if ( APP_VER >= 2.0 && ! class_exists('Channel'))
		{
			require PATH_MOD.'channel/mod.channel'.EXT;
		}
	}
	 /* END user_authors() */
    
    // --------------------------------------------------------------------

	/**
	 *	Screen Name Override
	 *
	 *	Used when assembling a screen name out of a set of Custom Fields
	 *
	 *	@access		public
	 *	@param		integer
	 *	@return		string
	 */

	public function _screen_name_override( $member_id = '0' )
    {	
		//we dont want to do this work twice
		if ($this->completed_override) return;
	
		//do we need to run the sql?
		$update_member_data	=	(isset($_POST['screen_name']) AND $_POST['screen_name'] == $this->screen_name_dummy);
	
    	$query = ee()->db->query(
			"SELECT preference_value 
			 FROM 	exp_user_preferences 
			 WHERE 	preference_name = 'screen_name_override' 
			 LIMIT 	1"
		);
    	
    	$screen_name_override = ($query->num_rows() == 0) ? '' : $query->row('preference_value');
    
    	if ( ! empty($screen_name_override) )
    	{
        	$this->_mfields();
        	
        	$fields			= '';
        	$screen_name	= '';
        	$update			= FALSE;
        	
			/**	----------------------------------------
			/**	Check required fields
			/**	----------------------------------------*/
			
			$name = explode( "|", $screen_name_override );
        	
			//if we need to parse member_id, we need to do a little magic.
			if (in_array('member_id', $name))
			{
				//if we have a member_id available, lets put it in the normal loop
				if ($member_id != 0)
				{
					$this->mfields['member_id'] = TRUE;
					$_POST['member_id']			= $member_id;
				}
				//if not, this is probably creation time and we are going to call it again
				//so we give it a hash and we will replace it later.
				else
				{
					$this->completed_override 	= FALSE;
					$_POST['screen_name']		= $this->screen_name_dummy;
					return;
				}
			}

        	foreach ( $name as $n )
        	{
        		$n	= trim( $n );
        		
        		if ( isset( $this->mfields[$n] ) )
        		{
        			if ( isset( $_POST[$n] ) )
        			{
        				$update	= TRUE;
        				
        				if ( $_POST[$n] == '' )
        				{
							$fields			.= "<li>".$this->mfields[$n]['label']."</li>";
        				}
        				else
        				{        		
							$screen_name	.= $_POST[$n]." ";
        				}
        			}
        		}
        	}
        	
        	$screen_name	= trim( $screen_name );
        	
			//remove these if we added them in
			if (isset($this->mfields['member_id']))
			{
				unset($this->mfields['member_id']);
				unset($_POST['member_id']);
			}

			/**	----------------------------------------
			/*	If screen name is empty at this
			/*	point, we are not updating it and
			/*	can get out.
			/**	----------------------------------------*/
			
			if ( $screen_name == '' AND ! $update )
			{
				return TRUE;
			}
        	
        	if ( $fields != '' )
        	{
				return $this->_output_error( 
					'general', 
					array( 
						str_replace( "%s", $fields, ee()->lang->line('user_field_required') ) 
					) 
				);
        	}
        	
			/**	----------------------------------------
			/**	Is screen name banned?
			/**	----------------------------------------*/
		
			if (ee()->session->ban_check('screen_name', $screen_name))
			{
				return $this->_output_error( 
					'general', 
					array( 
						str_replace( "%s", $screen_name, ee()->lang->line('banned_screen_name') ) 
					) 
				);
			}

			/**	----------------------------------------
			/**	Is screen name taken?
			/**	----------------------------------------*/
			/*
			
			$sql	= "SELECT COUNT(*) AS count FROM exp_members WHERE screen_name = '".ee()->db->escape_str($screen_name)."'";
			
			if ( $member_id != '0' )
			{
				$sql	.= " AND member_id != '".$member_id."'";
			}
			
			$query = ee()->db->query( $sql );
	
			if ($query->row('count') > 0)
			{
				return $this->_output_error( 'general', array( str_replace( "%s", $screen_name, ee()->lang->line('bad_screen_name') ) ) );
			}
			*/

			/**	----------------------------------------
			/**	Re-assign
			/**	----------------------------------------*/
			
			$_POST['screen_name']	= $screen_name;
			
			//we need to update this if it is being sent a second time
			if ($update_member_data)
			{
				ee()->db->query(
					ee()->db->update_string(
						'exp_members',
						array(
							'screen_name' => $_POST['screen_name']
						),
						'member_id = "' . ee()->db->escape_str($member_id) . '"'
					)
				);
			}
		}
		
		$this->completed_override = TRUE;
		
		return;
    }
    
    /* END screen name override */	
    
    
    // --------------------------------------------------------------------

	/**
	 *	Email is Username Error Checkin
	 *
	 *	One's Email Address can be used as one's Username.  This does synching and checking to allow that.
	 *
	 *	@access		public
	 *	@param		integer
	 *	@param		string
	 *	@return		string
	 */

	public function _email_is_username( $member_id = '0', $type = 'update' )
    {	
		/**	----------------------------------------
		/**	No member id? Fail out
		/**	----------------------------------------*/
		
		if ( $member_id == '0' AND $type == 'update' ) return FALSE;
    	
		/**	----------------------------------------
		/**	No username change allowed?
		/**	----------------------------------------*/
		
		if ( ee()->config->item('allow_username_change') != 'y' AND $type == 'update' ) return FALSE;
    	
		/**	----------------------------------------
		/**	Should we execute?
		/**	----------------------------------------*/
    	
    	if ( $this->preferences['email_is_username'] == 'n')
    	{
    		return TRUE;
    	}
    	
		/**	----------------------------------------
		/**	Get current data
		/**	----------------------------------------*/
		
		$cur_username	= '';
		$cur_email		= '';
		
		$query	= ee()->db->query( "SELECT username, email FROM exp_members
									WHERE member_id = '".ee()->db->escape_str($member_id)."'" );
		
		if ( $query->num_rows() > 0 )
		{			
			$cur_username	= $query->row('username');
			$cur_email		= $query->row('email');
		}
		
		/**	----------------------------------------
		/**	Is username empty or not changed?
		/**	----------------------------------------*/
		
		if ( $type == 'update' AND 
			 ( ! ee()->input->get_post('username') OR 
			  ee()->input->get_post('username') == $cur_username ) )
		{
			return TRUE;
		}
		
		/**	----------------------------------------
		/**	Is username banned?
		/**	----------------------------------------*/
	
		if ( ee()->session->ban_check( 'username', ee()->input->get_post('username') ) OR 
			 ee()->session->ban_check( 'email', ee()->input->get_post('username') ) )
		{
			return $this->_output_error( 'general', ee()->lang->line('banned_username') );
		}
	
		$validate	= array(
			'member_id'			=> $member_id,
			'val_type'			=> $type, // new or update
			'fetch_lang' 		=> FALSE, 
			'require_cpw' 		=> FALSE,
			'enable_log'		=> FALSE,
			'username'			=> ee()->input->get_post('username'),
			'cur_username'		=> $cur_username,
			'email'				=> ee()->input->get_post('username'),
			'cur_email'			=> $cur_email,
		);

		/**	----------------------------------------
		/**	Validate submitted data
		/**	----------------------------------------*/

		ee()->load->library('validate', $validate, 'email_validate');
		
		ee()->email_validate->validate_username();
		
		if ($this->preferences['email_is_username'] != 'n' && 
			($key = array_search(
				ee()->lang->line('username_password_too_long'), 
				ee()->email_validate->errors
			)) !== FALSE)
		{
			if (strlen(ee()->email_validate->username) <= 50)
			{
				unset(ee()->email_validate->errors[$key]);
			}
			else
			{
				ee()->email_validate->errors[$key] = str_replace('32', '50', ee()->email_validate->errors[$key]);	
			}
		}
		
		// If email is username, remove username error message
        if( $this->preferences['email_is_username'] == 'y' AND 
			($key = array_search(
				ee()->lang->line('missing_username'), 
				ee()->email_validate->errors
			)) !== FALSE)
        {
            unset(ee()->email_validate->errors[$key]);
        }
		
		ee()->email_validate->validate_email();
						
		/**	----------------------------------------
		/**	Display errors if there are any
		/**	----------------------------------------*/
		
		if (count(ee()->email_validate->errors) > 0)
		{
			return $this->_output_error('submission', ee()->email_validate->errors);
		}

		/**	----------------------------------------
		/**	Re-assign
		/**	----------------------------------------*/
		
		$_POST['email']	= ee()->input->get_post('username');
    }
    
    /* END _email_is_username() */
    
    // --------------------------------------------------------------------

	/**
	 *	Remove old User Keys
	 *
	 *	@access		public
	 *	@param		integer		$exp - Number of days ago to start deleting
	 *	@return		string
	 */

	public function _clear_old_hashes( $exp = '7' )
    {
    	$query = ee()->db->query("SELECT preference_value FROM exp_user_preferences WHERE preference_name = 'key_expiration' LIMIT 1");
    
		$exp	= ( $query->num_rows() > 0 ) ? $query->row('preference_value') : $exp;
		
		$now	= ee()->localize->now - ( $exp * 60 * 60 * 24 );
		
		ee()->db->query( "DELETE FROM exp_user_keys WHERE member_id = '0' AND date < '".$now."'" );
		
		return TRUE;
    }
    
    /* END _clean_old_hashes() */
    
    // --------------------------------------------------------------------

	/**
	 *	Characters Decoding
	 *
	 *	Converted entities back into characters
	 *
	 *	@access		public
	 *	@param		string
	 *	@return		string
	 */

	public function _chars_decode( $str = '' )
    {
    	if ( $str == '' ) return;
    	
    	if ( function_exists( 'htmlspecialchars_decode' ) )
    	{
    		$str	= htmlspecialchars_decode( $str );
    	}
    	
    	if ( function_exists( 'html_entity_decode' ) )
    	{
    		$str	= html_entity_decode( $str );
    	}
    	
    	$str	= str_replace( array( '&amp;', '&#47;', '&#39;', '\'' ), array( '&', '/', '', '' ), $str );
    	
    	$str	= stripslashes( $str );
    	
    	return $str;
    }
    
    /* END chars decode */
    
    
	// --------------------------------------------------------------------

	/**
	 *	Update User's Last Activity in Database
	 *
	 *	@access		private
	 *	@return		bool
	 */

	private function _update_last_activity()
    {	
		if ( ee()->session->userdata('member_id') == 0 )
		{
			return FALSE;
		}
		else
		{
			$member_id	= ee()->session->userdata('member_id');
		}
    	
    	ee()->db->query( ee()->db->update_string( 'exp_members', array( 'last_activity' => ee()->localize->now ), array( 'member_id' => $member_id ) ) );
    	
    	return TRUE;
    }
    
    /* END _update_last_activity() */
	
    
    // --------------------------------------------------------------------

	/**
	 *	Upload Images for Member
	 *
	 *	@access		private
	 *	@param		integer
	 *	@param		bool		$test_mode - When FALSE, it just does error checking
	 *	@return		string
	 */
    
	private function _upload_images( $member_id = 0, $test_mode = FALSE )
	{	
		/**	----------------------------------------
		/**	Should we execute?
		/**	----------------------------------------*/
		
		if ( $member_id == 0 && $test_mode === FALSE )
		{
			return FALSE;
		}
		
		foreach ( $this->images as $key => $val )
		{
			if ( isset( $_FILES[$val] ) AND $_FILES[$val]['name'] != '' )
			{
				$this->uploads[$key]	= $val;
			}
		}
		
		if ( count( $this->uploads ) == 0 )
		{
			return FALSE;
		}
		
		/**	----------------------------------------
		/**	Let's loop
		/**	----------------------------------------*/
		
		foreach ( $this->uploads as $key => $val )
		{
			$this->_upload_image( $key, $member_id, $test_mode );
		}
		
		/* END loop */
	}
	
	/* END upload image */
	
	// --------------------------------------------------------------------

	/**
	 *	Remove an Image for Member
	 *
	 *	@access		private
	 *	@param		string
	 *	@param		integer
	 * 	@param		bool
	 *	@return		string
	 */
	
	private function _remove_image($type, $member_id, $admin)
	{
		if ( ! isset($_POST['remove_'.$type]) OR ! in_array($type, array('avatar', 'photo', 'signature')))
		{
			return FALSE;
		}
		
		/**	----------------------------------------
		/**	 Password Required for Form Submission?!
		/**	----------------------------------------*/
		
		if ($admin === FALSE && ($this->check_yes($this->_param('password_required')) OR $this->_param('password_required') == 'yes'))
		{
			if (ee()->session->check_password_lockout() === TRUE)
			{		
				$line = str_replace("%x", ee()->config->item('password_lockout_interval'), ee()->lang->line('password_lockout_in_effect'));		
				return $this->_output_error('submission', $line);		
			}
			
			$query = ee()->db->query("SELECT password FROM exp_members WHERE member_id = '".ee()->db->escape_str( $member_id )."'");
		
			if ( $query->num_rows() == 0 )
			{
				return $this->_output_error('general', array(ee()->lang->line('cant_find_member')));
			}
			
			if (ee()->input->get_post('current_password') === FALSE OR
				ee()->input->get_post('current_password') == '' OR 
				ee()->functions->hash(stripslashes($_POST['current_password'])) != $query->row('password'))
			{
				return $this->_output_error( 'general', array( ee()->lang->line( 'invalid_password' ) ) );
			}
		}
		
		/**	----------------------------------------
        /**	Check Form Hash
        /**	----------------------------------------*/
        
        if ( ee()->config->item('secure_forms') == 'y' )
        {
            $secure = ee()->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes 
            								WHERE hash='".ee()->db->escape_str($_POST['XID'])."' 
            								AND ip_address = '".ee()->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");
        
            if ($secure->row('count') == 0)
            {
				return $this->_output_error('general', array(ee()->lang->line('not_authorized')));
            }
                                
			ee()->db->query("DELETE FROM exp_security_hashes 
								  WHERE (hash='".ee()->db->escape_str($_POST['XID'])."' 
								  AND ip_address = '".ee()->input->ip_address()."') 
								  OR date < UNIX_TIMESTAMP()-7200");
        }
        
        /**	----------------------------------------
		/**	Set return
		/**	----------------------------------------*/
        
        if ( ee()->input->get_post('return') !== FALSE AND ee()->input->get_post('return') != '' )
        {
        	$return	= ee()->input->get_post('return');
        }
        elseif ( ee()->input->get_post('RET') !== FALSE AND ee()->input->get_post('RET') != '' )
        {
        	$return	= ee()->input->get_post('RET');
        }
        else
        {
        	$return = ee()->config->item('site_url');
        }
		
		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) > 0 )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		elseif ( stristr( $return, "http://" ) === FALSE && stristr( $return, "https://" ) === FALSE )
		{
			$return	= ee()->functions->create_url( $return );
		}
		
		/** --------------------------------------------
        /**  Let's Delete An Image!
        /** --------------------------------------------*/
        
        $_POST['remove'] = 'Woot!';

		if ($type == 'signature') $type = 'sig';
        
        if (FALSE == $this->_upload_image($type, $member_id))
        {
        	// If FALSE, there was no image to delete...
        }
        
        /** --------------------------------------------
        /**  Success Message?
        /** --------------------------------------------*/
        
		switch ($type)
		{
			case 'avatar'	:
								$remove			= 'remove_avatar';
								$removed		= 'avatar_removed';
			break;
			case 'photo'	:	
								$remove			= 'remove_photo';
								$removed		= 'photo_removed';
								
			break;
			case 'sig'		:	
								$remove			= 'remove_sig_image';
								$removed		= 'sig_img_removed';
			break;		
		}
		
		/**	----------------------------------------
        /**	 Override Return
		/**	----------------------------------------*/
		
		if ( $this->_param('override_return') !== FALSE AND $this->_param('override_return') != '' )
		{	
			ee()->functions->redirect( $this->_param('override_return') );
			exit;
		}
		
		return ee()->output->show_message(array('title'		=> ee()->lang->line($remove), 
										'heading'	=> ee()->lang->line($remove), 
										'link'		=> array( $return, ee()->lang->line('return') ), 
										'content'	=> ee()->lang->line($removed)));
	}
	/** END remove_image() **/
	
	
    // --------------------------------------------------------------------

	/**
	 *	Upload an Image
	 *
	 *	Uploads one of the three types of images for Member Accounts
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		integer
	 *	@param		bool
	 *	@return		string|bool
	 */
		
	private function _upload_image( $type = 'avatar', $member_id = 0, $test_mode = TRUE )
	{			
		$member_id	= ee()->db->escape_str( $member_id );
		
		switch ($type)
		{
			case 'avatar'	:	
								$this->img['edit_image']	= 'edit_avatar';
								$this->img['enable_pref']	= 'allow_avatar_uploads';
								$this->img['not_enabled']	= 'avatars_not_enabled';
								$this->img['remove']		= 'remove_avatar';
								$this->img['removed']		= 'avatar_removed';
								$this->img['updated']		= 'avatar_updated';
				break;
			case 'photo'	:	
								$this->img['edit_image'] 	= 'edit_photo';
								$this->img['enable_pref']	= 'enable_photos';
								$this->img['not_enabled']	= 'photos_not_enabled';
								$this->img['remove']		= 'remove_photo';
								$this->img['removed']		= 'photo_removed';
								$this->img['updated']		= 'photo_updated';
								
				break;
			case 'sig'		:	
								$this->img['edit_image'] 	= 'edit_signature';
								$this->img['enable_pref']	= 'sig_allow_img_upload';
								$this->img['not_enabled']	= 'sig_img_not_enabled';
								$this->img['remove']		= 'remove_sig_img';
								$this->img['removed']		= 'sig_img_removed';
								$this->img['updated']		= 'signature_updated';
				break;		
		}		
		
		/**	----------------------------------------
		/**	Is this a remove request?
		/**	----------------------------------------*/
		
		if ( ! isset($_POST['remove']))
		{
			//  Is image uploading enabled?
			if (ee()->config->item( $this->img['enable_pref'] ) == 'n')
			{
				return $this->_output_error('general', array(ee()->lang->line($type.'_uploads_not_enabled')));
			}
		}
		else
		{
			if ($type == 'avatar')
			{
				$query = ee()->db->query("SELECT avatar_filename FROM exp_members WHERE member_id = '".$member_id."'");
				
				if ($query->row('avatar_filename') == '')
				{
					return FALSE;
				}
				
				ee()->db->query("UPDATE exp_members SET avatar_filename = '', avatar_width='', avatar_height='' WHERE member_id = '".$member_id."' ");
				
				if ( strpos($query->row('avatar_filename'), '/') !== FALSE)
				{
					@unlink(ee()->config->slash_item('avatar_path').$query->row('avatar_filename'));
				}
			}
			elseif ($type == 'photo')
			{
				$query = ee()->db->query("SELECT photo_filename FROM exp_members WHERE member_id = '".$member_id."'");
				
				if ($query->row('photo_filename') == '')
				{
					return FALSE;
				}
				
				ee()->db->query("UPDATE exp_members SET photo_filename = '', photo_width='', photo_height='' WHERE member_id = '".$member_id."' ");
			
				@unlink(ee()->config->slash_item('photo_path').$query->row('photo_filename'));
			}
			else
			{
				$query = ee()->db->query("SELECT sig_img_filename FROM exp_members WHERE member_id = '".$member_id."'");
				
				if ($query->row('sig_img_filename') == '')
				{
					return FALSE;
				}
				
				ee()->db->query("UPDATE exp_members SET sig_img_filename = '', sig_img_width='', sig_img_height='' WHERE member_id = '".$member_id."' ");
			
				@unlink(ee()->config->slash_item('sig_img_path').$query->row('sig_img_filename'));			
			}
			
			return TRUE;
		}		
				
		/**	----------------------------------------
		/**	Do the have the GD library?
		/**	----------------------------------------*/

		if ( ! function_exists('getimagesize')) 
		{
			return $this->_output_error('general', array(ee()->lang->line('gd_required')));
		}

		/**	----------------------------------------
		/**	Check the image size
		/**	----------------------------------------*/
		
		$size = ceil(($_FILES[$this->uploads[$type]]['size']/1024));
		
		if ($type == 'avatar')
		{
			$max_size = (ee()->config->item('avatar_max_kb') == '' OR ee()->config->item('avatar_max_kb') == 0) ? 50 : ee()->config->item('avatar_max_kb');
		}
		elseif ($type == 'photo')
		{
			$max_size = (ee()->config->item('photo_max_kb') == '' OR ee()->config->item('photo_max_kb') == 0) ? 50 : ee()->config->item('photo_max_kb');
		}
		else
		{
			$max_size = (ee()->config->item('sig_img_max_kb') == '' OR ee()->config->item('sig_img_max_kb') == 0) ? 50 : ee()->config->item('sig_img_max_kb');
		}
		
		$max_size = preg_replace("/(\D+)/", "", $max_size);

		if ($size > $max_size)
		{
			return $this->_output_error('submission', str_replace('%s', $max_size, ee()->lang->line('image_max_size_exceeded')));
		}
		
		/**	----------------------------------------
		/**	Is the upload path valid and writable?
		/**	----------------------------------------*/
		
		if ($type == 'avatar')
		{
			$upload_path = ee()->config->slash_item('avatar_path').'uploads/';
		}
		elseif ($type == 'photo')
		{
			$upload_path = ee()->config->slash_item('photo_path');
		}
		else
		{
			$upload_path = ee()->config->slash_item('sig_img_path');
		}

		if ( ! @is_dir($upload_path) OR ! is_writable($upload_path))
		{
			return $this->_output_error('general', array(ee()->lang->line('missing_upload_path')));
		}

		/**	----------------------------------------
		/**	Set some defaults
		/**	----------------------------------------*/
		
		$filename = $_FILES[$this->uploads[$type]]['name'];
		
		if ($type == 'avatar')
		{
			$max_width	= (ee()->config->item('avatar_max_width') == '' OR ee()->config->item('avatar_max_width') == 0) ? 100 : ee()->config->item('avatar_max_width');
			$max_height	= (ee()->config->item('avatar_max_height') == '' OR ee()->config->item('avatar_max_height') == 0) ? 100 : ee()->config->item('avatar_max_height');	
			$max_kb		= (ee()->config->item('avatar_max_kb') == '' OR ee()->config->item('avatar_max_kb') == 0) ? 50 : ee()->config->item('avatar_max_kb');	
		}
		elseif ($type == 'photo')
		{
			$max_width	= (ee()->config->item('photo_max_width') == '' OR ee()->config->item('photo_max_width') == 0) ? 100 : ee()->config->item('photo_max_width');
			$max_height	= (ee()->config->item('photo_max_height') == '' OR ee()->config->item('photo_max_height') == 0) ? 100 : ee()->config->item('photo_max_height');	
			$max_kb		= (ee()->config->item('photo_max_kb') == '' OR ee()->config->item('photo_max_kb') == 0) ? 50 : ee()->config->item('photo_max_kb');
		}
		else
		{
			$max_width	= (ee()->config->item('sig_img_max_width') == '' OR ee()->config->item('sig_img_max_width') == 0) ? 100 : ee()->config->item('sig_img_max_width');
			$max_height	= (ee()->config->item('sig_img_max_height') == '' OR ee()->config->item('sig_img_max_height') == 0) ? 100 : ee()->config->item('sig_img_max_height');	
			$max_kb		= (ee()->config->item('sig_img_max_kb') == '' OR ee()->config->item('sig_img_max_kb') == 0) ? 50 : ee()->config->item('sig_img_max_kb');
		}

		/**	----------------------------------------
		/**	Does the image have a file extension?
		/**	----------------------------------------*/
		
		if ( ! stristr($filename, '.'))
		{
			return $this->_output_error('submission', ee()->lang->line('invalid_image_type'));
		}
		
		/**	----------------------------------------
		/**	Is it an allowed image type?
		/**	----------------------------------------*/
		
		$xy = explode('.', $filename);
		$extension = '.'.end($xy);
		
		// We'll do a simple extension check now.
		// The file upload class will do a more thorough check later
		
		$types = array('.jpg', '.jpeg', '.gif', '.png');
		
		if ( ! in_array(strtolower($extension), $types))
		{
			return $this->_output_error('submission', ee()->lang->line('invalid_image_type'));
		}
		
		/** --------------------------------------------
        /**  END Test Mode
        /** --------------------------------------------*/
        
        if ($test_mode === TRUE)
        {
        	return TRUE;
        }

		/**	----------------------------------------
		/**	Assign the name of the image
		/**	----------------------------------------*/
		
		$new_filename = $type.'_'.$member_id . strtolower($extension);
		
		/**	----------------------------------------
		/**	Do they currently have an avatar or photo?
		/**	----------------------------------------*/
		
		if ($type == 'avatar')
		{
			$query = ee()->db->query("SELECT avatar_filename FROM exp_members WHERE member_id = '".$member_id."'");
			$old_filename = ($query->row('avatar_filename') == '') ? '' : $query->row('avatar_filename');
			
			if ( strpos($old_filename, '/') !== FALSE)
			{
				$xy = explode('/', $old_filename);
				$old_filename =  end($xy);
			}
		}
		elseif ($type == 'photo')
		{
			$query = ee()->db->query("SELECT photo_filename FROM exp_members WHERE member_id = '".$member_id."'");
			$old_filename = ($query->row('photo_filename') == '') ? '' : $query->row('photo_filename');
		}
		else
		{
			$query = ee()->db->query("SELECT sig_img_filename FROM exp_members WHERE member_id = '".$member_id."'");
			$old_filename = ($query->row('sig_img_filename') == '') ? '' : $query->row('sig_img_filename');
		}
		
				
		/**	----------------------------------------
		/**	Instantiate upload class
		/**	----------------------------------------*/
					
		// Upload the image
		//1.6.x doesnt like an extension, but 2.x does?
		$config['file_name']		= $new_filename;
		$config['upload_path']		= $upload_path;
		$config['allowed_types']	= 'gif|jpg|jpeg|png';
		$config['max_size']			= $max_kb;
		//$config['max_width']		= $max_width;
		//$config['max_height']		= $max_height;
		$config['overwrite']		= TRUE;

		if (ee()->config->item('xss_clean_uploads') == 'n')
		{
			$config['xss_clean'] = FALSE;
		}
		else
		{
			$config['xss_clean'] = (ee()->session->userdata('group_id') == 1) ? FALSE : TRUE;
		}

		ee()->load->library('upload');
		
		ee()->upload->initialize($config);
					
		if (ee()->upload->do_upload($this->uploads[$type]) === FALSE)
		{
			// Upload Failed.  Make sure that file is gone!
			@unlink($upload_path.$filename.$extension);
			
			if (REQ == 'CP')
			{
				ee()->session->set_flashdata('message_failure', ee()->upload->display_errors());
				ee()->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M='.$edit_image.AMP.'id='.$id);				
			}
			else
			{
				return ee()->output->show_user_error('submission',
													ee()->lang->line(ee()->upload->display_errors()));
			}
		}

		$file_info = ee()->upload->data();
		
		// Do we need to resize?
		$width	= $file_info['image_width'];
		$height = $file_info['image_height'];

		if ($max_width < $width OR $max_height < $height)
		{
			$resize_result = $this->_image_resize(
				$file_info['file_name'], 
				$type, 
				(($width > $height) ? 'width' : 'height')
			);
			
			//reset sizes
			if ($resize_result)
			{
				if ($max_height < $height)
				{
					$width 		= round($width * ($max_height / $height));
					$height		= $max_height;
				}
				else
				{
					$height 	= round($height * ($max_width / $width));
					$width		= $max_width;
				}
			}
		}

		// Delete the old file if necessary
		if ($old_filename != $new_filename)
		{
			@unlink($upload_path.$old_filename);
		}

		
		/**	----------------------------------------
		/**	Update DB
		/**	----------------------------------------*/

		if ($type == 'avatar')
		{
			$avatar = 'uploads/'.$new_filename;
			ee()->db->query("UPDATE exp_members SET avatar_filename = '{$avatar}', avatar_width='{$width}', avatar_height='{$height}' WHERE member_id = '".$member_id."' ");
		}
		elseif ($type == 'photo')
		{
			ee()->db->query("UPDATE exp_members SET photo_filename = '{$new_filename}', photo_width='{$width}', photo_height='{$height}' WHERE member_id = '".$member_id."' ");
		}
		else
		{
			ee()->db->query("UPDATE exp_members SET sig_img_filename = '{$new_filename}', sig_img_width='{$width}', sig_img_height='{$height}' WHERE member_id = '".$member_id."' ");
		}
        
        /**	----------------------------------------
        /**	Return
        /**	----------------------------------------*/        
	
		return TRUE;
	}
	
	/* END _upload_image() */
	
	
    // --------------------------------------------------------------------

	/**
	 *	Image Resizing for Three Types of Uploadable Member Images
	 *
	 *	@access		private
	 *	@param		string
	 *	@param		string
	 *	@param		string
	 *	@return		string
	 */
	
	private function _image_resize($filename, $type = 'avatar', $axis = 'width')
	{			
		if ($type == 'avatar')
		{
			$max_width	= (	ee()->config->slash_item('avatar_max_width') == '' OR 
							ee()->config->item('avatar_max_width') == 0) ? 
								100 : ee()->config->item('avatar_max_width');
			$max_height	= (	ee()->config->item('avatar_max_height') == '' OR 
							ee()->config->item('avatar_max_height') == 0) ? 
								100 : ee()->config->item('avatar_max_height');	
			$image_path = ee()->config->item('avatar_path') . 'uploads/';
		}
		elseif ($type == 'photo')
		{
			$max_width	= (	ee()->config->slash_item('photo_max_width') == '' OR 
							ee()->config->item('photo_max_width') == 0) ? 
								100 : ee()->config->item('photo_max_width');
			$max_height	= (	ee()->config->item('photo_max_height') == '' OR 
							ee()->config->item('photo_max_height') == 0) ? 
								100 : ee()->config->item('photo_max_height');	
			$image_path = ee()->config->item('photo_path');		
		}
		else
		{
			$max_width	= (	ee()->config->slash_item('sig_img_max_width') == '' OR 
							ee()->config->item('sig_img_max_width') == 0) ? 
								100 : ee()->config->item('sig_img_max_width');
			$max_height	= (	ee()->config->item('sig_img_max_height') == '' OR 
							ee()->config->item('sig_img_max_height') == 0) ? 
								100 : ee()->config->item('sig_img_max_height');	
			$image_path = ee()->config->item('sig_img_path');		
		}

		$config = array(
			'image_library'		=> ee()->config->item('image_resize_protocol'),
			'library_path'		=> ee()->config->item('image_library_path'),
			'maintain_ratio'	=> TRUE,
			'master_dim'		=> $axis,
			'source_image'		=> ee()->functions->remove_double_slashes($image_path . '/' . $filename),
			'quality'			=> '75%',
			'width'				=> $max_width,
			'height'			=> $max_height
		);
		
		ee()->load->library('image_lib');
		
		ee()->image_lib->initialize($config);
		
		if ( ! ee()->image_lib->resize())
		{
			return FALSE;
		}
	
		return TRUE;
	}
	/* END image resize */
	
	
	// --------------------------------------------------------------------

	/**
	 *	Variable Swapping
	 *
	 *	Available even when $TMPL is not
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		array
	 *	@return		string
	 */
 
	public function _var_swap($str, $data)
	{
		if ( ! is_array($data))
		{
			return false;
		}
	
		foreach ($data as $key => $val)
		{
			$str = str_replace('{'.$key.'}', $val, $str);
		}
	
		return $str;
	}
	/* END _var_swap() */
	
	
	// --------------------------------------------------------------------

	/**
	 *	Update Profile Views
	 *
	 *	@access		private
	 *	@return		bool
	 */
	 
	private function _update_profile_views()
	{	
		if (is_object(ee()->TMPL) && $this->check_no(ee()->TMPL->fetch_param('log_views'))) return FALSE;
		
		if ( $this->member_id == 0 OR $this->member_id == ee()->session->userdata('member_id') ) return FALSE;
		
		$query	= ee()->db->query( "SELECT profile_views FROM exp_members 
							   WHERE member_id = '".ee()->db->escape_str( $this->member_id )."' 
							   LIMIT 1" );
		
		if ( $query->num_rows() > 0 )
		{
			ee()->db->query( ee()->db->update_string( "exp_members", 
											array( 'profile_views' => $query->row('profile_views') + 1 ), 
											array( 'member_id' => ee()->db->escape_str( $this->member_id ) ) ) );
		}
		
		return TRUE;
	}
	
	/* END _update_profile_views() */
	

	// --------------------------------------------------------------------

	/**
	 *	Fetch IDs for Member Fields
	 *
	 *	@access		public
	 *	@return		array
	 */

	public function _mfields()
    {   
        if ( isset(ee()->TMPL) && is_object(ee()->TMPL) && ee()->TMPL->fetch_param('disable') !== FALSE && stristr('member_data', ee()->TMPL->fetch_param('disable')))
		{
			return array();
		}
        
        if ( count( $this->mfields ) > 0 ) return $this->mfields;
        
        $query = ee()->db->query("SELECT m_field_id, m_field_name, m_field_label, m_field_type, 
        									  m_field_list_items, m_field_required, m_field_public, m_field_fmt, m_field_description
        							   FROM exp_member_fields");
                
        foreach ($query->result_array() as $row)
        { 
            $this->mfields[$row['m_field_name']] = array(
											'id'			=> $row['m_field_id'],
											'name'			=> $row['m_field_name'],
											'label'			=> $row['m_field_label'],
											'type'			=> $row['m_field_type'],
											'list'			=> $row['m_field_list_items'],
											'required'		=> $row['m_field_required'],
											'public'		=> $row['m_field_public'],
											'format'		=> $row['m_field_fmt'],
											'description'	=> $row['m_field_description']
            );
        }
        
        return $this->mfields;
    }
    
    /* END _mfields() */
    
	
 	// --------------------------------------------------------------------

	/**
	 *	Determine Member ID for Page
	 *
	 *	@access		public
	 *	@return		bool
	 */

	public function _member_id()
    {	
    	/** --------------------------------------------
        /**  Requisite Helpers
        /** --------------------------------------------*/
    
    	ee()->load->helper('string');
    	
    	/** --------------------------------------------
        /**  Default Variables
        /** --------------------------------------------*/
    
		$cat_segment	= ee()->config->item("reserved_category_word");
		
		$dynamic = TRUE;
		
		if ( ee()->TMPL->fetch_param('dynamic') !== FALSE && $this->check_no(ee()->TMPL->fetch_param('dynamic')))
		{
			$dynamic = FALSE;
		}
		
		/**	----------------------------------------
		/**	Have we already set the member id?
		/**	----------------------------------------*/
		
		if ( $this->member_id != 0 ) return TRUE;
		
		/**	----------------------------------------
		/**	Track down the member id?
		/**	----------------------------------------*/
    	
		if ( ($member_id = ee()->TMPL->fetch_param('member_id')) !== FALSE)
		{
			if (ctype_digit($member_id))
			{
				$this->member_id = $member_id;
			
				return TRUE;
			}
			elseif($member_id == 'CURRENT_USER' && ee()->session->userdata['member_id'] != 0)
			{
				$this->member_id = ee()->session->userdata['member_id'];
				
				return TRUE;
			}
			elseif ($member_id != '')
			{
				return FALSE;
			}
		}
		
		if ( ($member_id = ee()->TMPL->fetch_param('user_author_id')) !== FALSE)
		{
			if (ctype_digit(trim(str_replace(array('not ', '|'), '', $member_id)))) // Allow for multiples or not
			{
				$this->member_id = $member_id;
			
				return TRUE;
			}
			elseif($member_id == 'CURRENT_USER' && ee()->session->userdata['member_id'] != 0)
			{
				$this->member_id = ee()->session->userdata['member_id'];
				
				return TRUE;
			}
			elseif($member_id != '')
			{
				return FALSE;
			}
		}
		
		if ( ee()->TMPL->fetch_param('username') !== FALSE )
		{
			if (ee()->TMPL->fetch_param('username') == 'CURRENT_USER' && ee()->session->userdata['member_id'] != 0)
			{
				$this->member_id = ee()->session->userdata['member_id'];
				
				return TRUE;
			}
		
			$query	= ee()->db->query("SELECT member_id FROM exp_members 
								  WHERE username = '".ee()->db->escape_str( ee()->TMPL->fetch_param('username') )."'");
			
			if ( $query->num_rows() == 1 )
			{
				$this->member_id	= $query->row('member_id');
				
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		
		/** --------------------------------------------
        /**  Magical Lookup Parameter Prefix
        /** --------------------------------------------*/
        
        $search_fields = array();
        
		if ( is_array(ee()->TMPL->tagparams))
		{
			foreach(ee()->TMPL->tagparams as $key => $value)
			{
				if (strncmp($key, 'search:', 7) == 0)
				{
					$this->_mfields();
					$search_fields[substr($key, strlen('search:'))] = $value;
				}
			}
			
			if (sizeof($search_fields) > 0)
			{	
				$sql = $this->_search_fields($search_fields);
				
				if ($sql != '')
				{
					$query = ee()->db->query("SELECT m.member_id
											  FROM exp_members AS m, exp_member_data AS md
											  WHERE m.member_id = md.member_id ".$sql);
						
					if ($query->num_rows() == 1)
					{
						$this->member_id = $query->row('member_id');
					
						return TRUE;
					}
					else
					{
						return FALSE;
					}
				}
			}
		}
		
		/** --------------------------------------------
        /**  User ID or Name in the URL?
        /** --------------------------------------------*/
		
		if ($dynamic === TRUE && preg_match( "#/".self::$trigger."/(\w+)/?#", ee()->uri->uri_string, $match ) )
		{
			$sql	= "SELECT member_id FROM exp_members";
			
			if ( is_numeric( $match['1'] ) )
			{
				$sql	.= " WHERE member_id = '".ee()->db->escape_str( $match['1'] )."'";
			}
			else
			{
				$sql	.= " WHERE username = '".ee()->db->escape_str( $match['1'] )."'";
			}
			
			$sql	.= " LIMIT 1";
			
			$query	= ee()->db->query( $sql );
			
			if ( $query->num_rows() == 1 )
			{
				$this->member_id	= $query->row('member_id');
				
				return TRUE;
			}
		}
				
		/**	----------------------------------------
		/**	No luck so far? Let's try query string
		/**	----------------------------------------*/

		if ( ee()->uri->uri_string != '' AND $dynamic === TRUE)
		{
			$qstring	= ee()->uri->uri_string;
			
			/**	----------------------------------------
			/**	Do we have a pure ID number?
			/**	----------------------------------------*/
		
			if ( is_numeric( $qstring) )
			{
				$this->member_id	= $qstring;
			}
			else
			{
				/**	----------------------------------------
				/**	Parse day
				/**	----------------------------------------*/
				
				if (preg_match("#\d{4}/\d{2}/(\d{2})#", $qstring, $match))
				{											
					$partial	= substr($match[0], 0, -3);
										
					$qstring	= trim_slashes(str_replace($match[0], $partial, $qstring));
				}
				
				/**	----------------------------------------
				/**	Parse /year/month/
				/**	----------------------------------------*/
										
				if (preg_match("#(\d{4}/\d{2})#", $qstring, $match))
				{					
					$qstring	= trim_slashes(str_replace($match['1'], '', $qstring));
				}				

				/**	----------------------------------------
				/**	Parse page number
				/**	----------------------------------------*/
				
				if (preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match))
				{					
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}

				/**	----------------------------------------
				/**	Parse category indicator
				/**	----------------------------------------*/
				
				// Text version of the category
				
				if (preg_match("#^".$cat_segment."/#", $qstring, $match) AND (ee()->TMPL->fetch_param('weblog') OR ee()->TMPL->fetch_param('channel')))
				{
					$qstring	= str_replace($cat_segment.'/', '', $qstring);
						
					if (APP_VER < 2.0)
					{
						$sql		= "SELECT DISTINCT cat_group FROM exp_weblogs WHERE ";
					
						$xsql	= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('weblog'), 'blog_name');
					}
					else
					{
						$sql		= "SELECT DISTINCT cat_group FROM exp_channels WHERE ";
					
						$xsql	= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');
					}
					
					if (substr($xsql, 0, 3) == 'AND') $xsql = substr($xsql, 3);
					
					$sql	.= ' '.$xsql;
						
					$query	= ee()->db->query($sql);
					
					if ($query->num_rows() == 1)
					{
						$result	= ee()->db->query("SELECT cat_id FROM exp_categories 
														WHERE cat_name='".ee()->db->escape_str($qstring)."'
														AND group_id='{$query->row('cat_group')}'");
					
						if ($result->num_rows() == 1)
						{
							$qstring	= 'C'.$result->row('cat_id');
						}
					}
				}

				// Numeric version of the category

				if (preg_match("#^C(\d+)#", $qstring, $match))
				{														
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}
				
				/**	----------------------------------------
				/**	Remove "N" 
				/**	----------------------------------------*/
				
				// The recent comments feature uses "N" as the URL indicator
				// It needs to be removed if presenst

				if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
				{					
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}

				/**	----------------------------------------
				/**	Numeric?
				/**	----------------------------------------*/
				
				if ( is_numeric( str_replace( "/", "", $qstring) ) )
				{
					$this->member_id	= $qstring;
				}
				elseif ( preg_match( "/(^|\/)(\d+)(\/|$)/s", $qstring, $match ) )
				{
					$this->member_id = $match[2];
				}
			}
			
			/**	----------------------------------------
			/**	Let's check the number against the DB
			/**	----------------------------------------*/
			
			if ( $this->member_id != '' )
			{
				$query	= ee()->db->query( "SELECT member_id FROM exp_members WHERE member_id = '".ee()->db->escape_str( $this->member_id )."' LIMIT 1" );
				
				if ( $query->num_rows() > 0 )
				{
					$this->member_id	= $query->row('member_id');
					
					return TRUE;
				}
			}
		}		
		
		/**	----------------------------------------
		/**	When all else fails, show current user
		/**	----------------------------------------*/
		
		if ( ee()->session->userdata('member_id') != '0' )
		{
			$this->member_id	= ee()->session->userdata('member_id');
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	/* END member id */
	
	// --------------------------------------------------------------------

	/**
	 *	Search Member Fields
	 *
	 *	Searches within the exp_members and exp_member_data fields for the user() and _member_id() methods
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		string
	 */
	
	function _search_fields($search_fields = array())
	{
		$sql = '';
		
		foreach($search_fields as $field => $values)
		{
			$field = preg_replace("/^([a-z\_]+)\[[0-9]+\]$/i", '\1', $field);
			
			// Remove 'not ' and do a little switch
			if (strncmp($values, 'not ', 4) == 0)
			{
				$values = substr($values, 4);
				$comparision		= '!=';
				$like_comparision	= 'NOT LIKE';
			}
			else
			{
				$comparision		= '=';
				$like_comparision	= 'LIKE';
			}
		
			if (in_array( $field, $this->standard) OR in_array($field, array('username', 'screen_name')))
			{
				$field = "`m`.`".$field."`";
			}
			elseif (isset($this->mfields[$field]))
			{
				$field = "`md`.`m_field_id_".$this->mfields[$field]['id']."`";
			}
			else
			{
				continue;
			}
			
			if (strpos($values, '&&') !== FALSE)
			{
				$values = explode('&&', $values);
				$andor = (strncmp($like_comparision, 'NOT', 3) == 0) ? ' OR ' : ' AND ';
			}
			else
			{
				$values = explode('|', $values);
				$andor = (strncmp($like_comparision, 'NOT', 3) == 0) ? ' AND ' : ' OR ';
			}
			
			$parts = array();
			
			foreach($values as $value)
			{
				if ($value == '') continue;
			
				if ($value == 'IS_EMPTY')
				{
					$parts[] = " ".$field." ".$comparision." ''";
				}
				elseif($value == 'IS_NOT_EMPTY')
				{
					if ($comparision == '!=')
					{
						// search:field="not IS_NOT_EMPTY" - Very screwy
						$parts[] = " ".$field." = ''";
					}
					else
					{
						$parts[] = " ".$field." != ''";
					}
				}
				elseif ( substr($value, 0, 1) == '%' OR substr($value, -1) == '%')
				{
					if ( substr($value, 0, 1) == '%' && substr($value, -1) == '%')
					{
						$parts[] = " ".$field." ".$like_comparision." '%".ee()->db->escape_like_str(substr($value, 1, -1))."%'";
					}
					elseif ( substr($value, 0, 1) == '%')
					{
						$parts[] = " ".$field." ".$like_comparision." '%".ee()->db->escape_like_str(substr($value, 1))."'";
					}
					else
					{
						$parts[] = " ".$field." ".$like_comparision." '".ee()->db->escape_like_str(substr($value, 0, -1))."%'";
					}
				}
				else
				{
					$parts[] = " ".$field." ".$comparision." '".ee()->db->escape_str($value)."'";
				}
			}
			
			if (count($parts) > 0)
			{
				$sql .= ' AND ( '.implode($andor, $parts).' ) ';
			}
						
		} // End $search_fields loop
		
		//echo $sql;
		
		return $sql;
	}
	/* END _search_fields() */
	
	
    
	
	// --------------------------------------------------------------------

	/**
	 *	Determine Entry ID for Page
	 *
	 *	@access		public
	 *	@return		bool
	 */

	public function _entry_id()
    {
    	/** --------------------------------------------
        /**  Required Helpers
        /** --------------------------------------------*/
        
        ee()->load->helper('string');
        
        /** --------------------------------------------
        /**  Work to Determine Entry Id
        /** --------------------------------------------*/
    	
		$cat_segment	= ee()->config->item("reserved_category_word");
    	
		if ( ctype_digit( ee()->TMPL->fetch_param('entry_id') ) )
		{
			if (APP_VER < 2.0)
			{
				$query	= ee()->db->query( "SELECT entry_id FROM exp_weblog_titles 
												 WHERE entry_id = '".ee()->db->escape_str( ee()->TMPL->fetch_param('entry_id') )."'" );
			}
			else
			{
				$query	= ee()->db->query( "SELECT entry_id FROM exp_channel_titles 
												 WHERE entry_id = '".ee()->db->escape_str( ee()->TMPL->fetch_param('entry_id') )."'" );
			}
			
			if ( $query->num_rows() > 0 )
			{
				$this->entry_id	= $query->row('entry_id');
				
				return TRUE;
			}
		}
		elseif ( ee()->uri->query_string != '' )
		{
			$qstring	= ee()->uri->query_string;
			
			/**	----------------------------------------
			/**	Do we have a pure ID number?
			/**	----------------------------------------*/
		
			if ( ctype_digit( $qstring ) )
			{
				if (APP_VER < 2.0)
				{
					$query	= ee()->db->query("SELECT entry_id FROM exp_weblog_titles 
												WHERE entry_id = '".ee()->db->escape_str( $qstring )."'" );
				}
				else
				{
					$query	= ee()->db->query("SELECT entry_id FROM exp_channel_titles 
												WHERE entry_id = '".ee()->db->escape_str( $qstring )."'" );
				}
				
				if ( $query->num_rows() > 0 )
				{
					$this->entry_id	= $query->row('entry_id');
					
					return TRUE;
				}
			}
			else
			{
				/**	----------------------------------------
				/**	Parse day
				/**	----------------------------------------*/
				
				if (preg_match("#\d{4}/\d{2}/(\d{2})#", $qstring, $match))
				{											
					$partial	= substr($match[0], 0, -3);
										
					$qstring	= trim_slashes(str_replace($match[0], $partial, $qstring));
				}
				
				/**	----------------------------------------
				/**	Parse /year/month/
				/**	----------------------------------------*/
										
				if (preg_match("#(\d{4}/\d{2})#", $qstring, $match))
				{					
					$qstring	= trim_slashes(str_replace($match['1'], '', $qstring));
				}				

				/**	----------------------------------------
				/**	Parse page number
				/**	----------------------------------------*/
				
				if (preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match))
				{					
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}

				/**	----------------------------------------
				/**	Parse category indicator
				/**	----------------------------------------*/
				
				// Text version of the category
				
				if (preg_match("#^".$cat_segment."/#", $qstring, $match) AND (ee()->TMPL->fetch_param('weblog') OR ee()->TMPL->fetch_param('channel')))
				{
					$qstring	= str_replace($cat_segment.'/', '', $qstring);
						
					if (APP_VER < 2.0)
					{
						$sql = "SELECT DISTINCT cat_group FROM exp_weblogs WHERE cat_group != ''";
					}
					else
					{
						$sql = "SELECT DISTINCT cat_group FROM exp_channels WHERE cat_group != ''";
					}
					
					if ( isset( ee()->TMPL->site_ids ) === TRUE )
					{
						$sql	.= " AND site_id IN ('".implode("','", ee()->TMPL->site_ids)."')";
					}
					
					if (APP_VER < 2.0)
					{
						$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('weblog'), 'blog_name');
					}
					else
					{
						$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');
					}
					
					
					$query	= ee()->db->query($sql);
					
					if ($query->num_rows() == 1)
					{
						$sql	= "SELECT cat_id FROM exp_categories WHERE cat_name='".ee()->db->escape_str($qstring)."' AND group_id='{$query->row('cat_group')}'";
					
						if ( isset( ee()->TMPL->site_ids ) === TRUE )
						{
							$sql	.= " site_id IN ('".implode("','", ee()->TMPL->site_ids)."')";
						}
					
						$result	= ee()->db->query( $sql );
					
						if ($result->num_rows() == 1)
						{
							$qstring	= 'C'.$result->row('cat_id');
						}
					}
				}

				/**	----------------------------------------
				/**	Numeric version of the category
				/**	----------------------------------------*/

				if (preg_match("#^C(\d+)#", $qstring, $match))
				{														
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}
				
				/**	----------------------------------------
				/**	Remove "N" 
				/**	----------------------------------------*/
				
				// The recent comments feature uses "N" as the URL indicator
				// It needs to be removed if presenst

				if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
				{					
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}

				/**	----------------------------------------
				/**	Parse URL title
				/**	----------------------------------------*/
				
				if (strstr($qstring, '/'))
				{
					$xe			= explode('/', $qstring);
					$qstring	= current($xe);
				}
				
				if (APP_VER < 2.0)
				{
					$sql	= "SELECT exp_weblog_titles.entry_id 
								FROM exp_weblog_titles, exp_weblogs 
								WHERE exp_weblog_titles.weblog_id = exp_weblogs.weblog_id 
								AND exp_weblog_titles.url_title = '".ee()->db->escape_str($qstring)."'";
						
					if ( isset( ee()->TMPL->site_ids ) === TRUE )
					{
						$sql	.= " AND exp_weblog_titles.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";
					}
				}
				else
				{
					$sql	= "SELECT exp_channel_titles.entry_id 
								FROM exp_channel_titles, exp_channels 
								WHERE exp_channel_titles.channel_id = exp_channels.channel_id 
								AND exp_channel_titles.url_title = '".ee()->db->escape_str($qstring)."'";
						
					if ( isset( ee()->TMPL->site_ids ) === TRUE )
					{
						$sql	.= " AND exp_channel_titles.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";
					}
				}
								
				$query	= ee()->db->query($sql);
				
				if ( $query->num_rows() > 0 )
				{
					$this->entry_id = $query->row('entry_id');
					
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
	
	/* END entry id */
    
    // --------------------------------------------------------------------

	/**
	 *	Create Select Field for Mailing Lists
	 *
	 *	Takes a string of tag data and parses it for each and every possible mailing list
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		arrray		List of Mailing List IDs to Parse
	 *	@return		string
	 */

	public function _parse_select_mailing_lists( $data, $row = array())
    {	
		/**	----------------------------------------
		/**	Fail?
		/**	----------------------------------------*/
		
		if ( $data == '' OR ee()->db->table_exists( 'exp_mailing_lists' ) === FALSE)
		{
			return '';
		}
		
		/**	----------------------------------------
		/**	Are there list items present?
		/**	----------------------------------------*/
    	
    	$sql = "SELECT DISTINCT list_id, list_title FROM exp_mailing_lists";
    			
    	$query = ee()->db->query($sql);
    	
    	if ($query->num_rows() == 0) return '';
    	
		/**	----------------------------------------
		/**	Do we have a value?
		/**	----------------------------------------*/
		
		if ( isset( $row['list_id']))
		{
			$value	= $row['list_id'];
		}
		else
		{
			$value	= '';
		}
    	
		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/
		
		$return	= '';
		
		foreach ( $query->result_array() as $row )
		{
			$out		= $data;
			$out		= ee()->functions->prep_conditionals($out,$row);
			$selected	= ($value == $row['list_id']) ? 'selected="selected"': '';
			$checked	= ($value == $row['list_id']) ? 'checked="checked"': '';
			$out		= str_replace( LD."selected".RD, $selected, $out );
			$out		= str_replace( LD."checked".RD, $checked, $out );
			$out		= str_replace( LD."list_id".RD, $row['list_id'], $out );
			$out		= str_replace( LD."list_title".RD, $row['list_title'], $out );
			$return		.= trim( $out )."\n";
		}
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		return $return;
    }
    
    /* END parse select */

    // --------------------------------------------------------------------

	/**
	 *	Parse Select Field for Member Groups
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		integer
	 *	@return		string
	 */

	public function _parse_select_member_groups( $data, $selected_group_id = 0)
    {	
		/**	----------------------------------------
		/**	Fail?
		/**	----------------------------------------*/
		
		if ( $data == '' )
		{
			return '';
		}
		
		/**	----------------------------------------
		/**	Are there list items present?
		/**	----------------------------------------*/
		
    	if ( ee()->TMPL->fetch_param('allowed_groups') === FALSE OR ee()->TMPL->fetch_param('allowed_groups') == '')
    	{
    		return '';
    	}
    	
    	$sql = "SELECT DISTINCT group_id, group_title FROM exp_member_groups
    			WHERE group_id NOT IN (1,2,3,4) 
    			".ee()->functions->sql_andor_string( ee()->TMPL->fetch_param('allowed_groups'), 'group_id');
    			
    	$query = ee()->db->query($sql);
    	
    	if ($query->num_rows() == 0) return '';
    	
		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/
		
		$return	= '';
		
		foreach ( $query->result_array() as $row )
		{
			$out		= $data;
			$out		= ee()->functions->prep_conditionals($out,$row);
			$selected	= ($selected_group_id == $row['group_id']) ? 'selected="selected"': '';
			$checked	= ($selected_group_id == $row['group_id']) ? 'checked="checked"': '';
			$out		= str_replace( LD."selected".RD, $selected, $out );
			$out		= str_replace( LD."checked".RD, $checked, $out );
			$out		= str_replace( LD."group_id".RD, $row['group_id'], $out );
			$out		= str_replace( LD."group_title".RD, $row['group_title'], $out );
			$return		.= trim( $out )."\n";
		}
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		return $return;
    }
    
    /* END parse select */
    	

	// --------------------------------------------------------------------

	/**
	 *	Parse Select Field for Member Custom Fields
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		array
	 *	@param		string
	 *	@return		string
	 */

	public function _parse_select( $key = '', $row = array(), $data = '' )
    {	
		/**	----------------------------------------
		/**	Fail?
		/**	----------------------------------------*/
		
		if ( $key == '' OR $data == '' )
		{
			return '';
		}
		
		/**	----------------------------------------
		/**	Are there list items present?
		/**	----------------------------------------*/
		
    	if ( ! isset( $this->mfields[$key]['list'] ) OR $this->mfields[$key]['list'] == '' )
    	{
    		return '';
    	}
    	
		/**	----------------------------------------
		/**	Do we have a value?
		/**	----------------------------------------*/
		
		if ( isset( $row['m_field_id_'.$this->mfields[$key]['id']] ) )
		{
			$value	= $row['m_field_id_'.$this->mfields[$key]['id']];
		}
		else
		{
			$value	= '';
		}
    	
		/**	----------------------------------------
		/**	Create an array from value
		/**	----------------------------------------*/
		
		$arr	= preg_split( "/\r|\n/", $value );
    	
		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/
		
		$return	= '';
		
		foreach ( preg_split( "/\r|\n/", $this->mfields[$key]['list'] ) as $val )
		{
			$out		= $data;
			$selected	= ( in_array( $val, $arr ) ) ? 'selected="selected"': '';
			$checked	= ( in_array( $val, $arr ) ) ? 'checked="checked"': '';
			$out		= str_replace( LD."selected".RD, $selected, $out );
			$out		= str_replace( LD."checked".RD, $checked, $out );
			$out		= str_replace( LD."value".RD, $val, $out );
			$out		= ee()->functions->prep_conditionals($out, array('value' => $val));
			$return		.= trim( $out )."\n";
		}
		
		///exit($return);
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		return $return;
    }
    
    /* END parse select */
    
	
	// --------------------------------------------------------------------

	/**
	 *	Creates a Form
	 *
	 *	Takes an Array of Form Data and Automatically Builds Our Form Output
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		string
	 */
	 
	public function _form( $form_data = array() )
    {
    	if ( count( $form_data ) == 0 AND ! isset( $this->form_data ) ) return '';
    	
    	if ( ! isset( $this->form_data['tagdata'] ) OR $this->form_data['tagdata'] == '' )
    	{
			$tagdata	=	ee()->TMPL->tagdata;
    	}
    	else
    	{
    		$tagdata	= $this->form_data['tagdata'];
    		unset( $this->form_data['tagdata'] );
    	}
    	
    	if (ee()->TMPL->fetch_param('override_return') !== FALSE && ee()->TMPL->fetch_param('override_return') != '')
    	{
    		$override_return = ee()->TMPL->fetch_param('override_return');
    		
    		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $override_return, $match ) > 0 )
			{
				$override_return = ee()->functions->create_url( $match['1'] );
			}
			elseif ( stristr( $override_return, "http://" ) === FALSE )
			{
				$override_return = ee()->functions->create_url( $override_return );
			}
    		
    		$this->params['override_return'] = ee()->TMPL->fetch_param('override_return');
    	}

		/**	----------------------------------------
		/**	Insert params
		/**	----------------------------------------*/
		
		if ( ($this->params_id = $this->_insert_params()) === FALSE )
		{
			$this->params_id	= 0;
		}
		
		$this->form_data['params_id']	= $this->params_id;
		
		/** --------------------------------------------
        /**  Special Handling for return="" parameter
        /** --------------------------------------------*/

		foreach(array('return', 'RET') as $val)
		{
			if (isset($this->form_data[$val]) && $this->form_data[$val] !== FALSE && $this->form_data[$val] != '')
			{
				$this->form_data[$val] = str_replace(T_SLASH, '/', $this->form_data[$val]);
			
				if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $this->form_data[$val], $match ))
				{
					$this->form_data[$val] = ee()->functions->create_url( $match['1'] );
				}
				elseif ( stristr( $this->form_data[$val], "http://" ) === FALSE )
				{
					$this->form_data[$val] = ee()->functions->create_url( $this->form_data[$val] );
				}
			}
		}

		/**	----------------------------------------
		/**	Generate form
		/**	----------------------------------------*/
		
		$arr	=	array(
						'action'		=> ee()->functions->fetch_site_index(),
						'id'			=> $this->form_data['id'],
						'enctype'		=> ( $this->multipart ) ? 'multi': '',
						'onsubmit'		=> ( isset($this->form_data['onsubmit'])) ? $this->form_data['onsubmit'] : ''
						);
						
		$arr['onsubmit'] = ( ee()->TMPL->fetch_param('onsubmit') ) ? ee()->TMPL->fetch_param('onsubmit') : $arr['onsubmit'];
						
		if ( isset( $this->form_data['name'] ) !== FALSE )
		{
			$arr['name']	= $this->form_data['name'];
			unset( $this->form_data['name'] );
		}
		
		unset( $this->form_data['id'] );
		unset( $this->form_data['onsubmit'] );
		
		$arr['hidden_fields']	= $this->form_data;
		
		/** --------------------------------------------
        /**  HTTPS URLs?
        /** --------------------------------------------*/
		
		if (ee()->TMPL->fetch_param('secure_action') == 'yes')
		{
			if (isset($arr['action']))
			{
				$arr['action'] = str_replace('http://', 'https://', $arr['action']);
			}
		}
		
		if (ee()->TMPL->fetch_param('secure_return') == 'yes')
		{
			foreach(array('return', 'RET') as $return_field)
			{
				if (isset($arr['hidden_fields'][$return_field]))
				{
					if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $arr['hidden_fields'][$return_field], $match ) > 0 )
					{
						$arr['hidden_fields'][$return_field] = ee()->functions->create_url( $match['1'] );
					}
					elseif ( stristr( $arr['hidden_fields'][$return_field], "http://" ) === FALSE )
					{
						$arr['hidden_fields'][$return_field] = ee()->functions->create_url( $arr['hidden_fields'][$return_field] );
					}
				
					$arr['hidden_fields'][$return_field] = str_replace('http://', 'https://', $arr['hidden_fields'][$return_field]);
				}
			}
		}
		
		/** --------------------------------------------
        /**  Custom Error Page
        /** --------------------------------------------*/
		
		if (ee()->TMPL->fetch_param('error_page') !== 'FALSE' && ee()->TMPL->fetch_param('error_page') != '')
		{
			$arr['hidden_fields']['error_page'] = str_replace(T_SLASH, '/', ee()->TMPL->fetch_param('error_page'));
		}
		
		/** --------------------------------------------
        /**  Override Form Attributes with form:xxx="" parameters
        /** --------------------------------------------*/
        
        $extra_attributes = array();
        
        if (is_object(ee()->TMPL) && ! empty(ee()->TMPL->tagparams))
		{
			foreach(ee()->TMPL->tagparams as $key => $value)
			{
				if (strncmp($key, 'form:', 5) == 0)
				{
					if (isset($arr[substr($key, 5)]))
					{
						$arr[substr($key, 5)] = $value;
					}
					else
					{
						$extra_attributes[substr($key, 5)] = $value;
					}
				}
			}
		}
		
		/** --------------------------------------------
        /**  Create Form
        /** --------------------------------------------*/
				
        $r	= ee()->functions->form_declaration( $arr );
        
        $r	.= stripslashes($tagdata);
        
        $r	.= "</form>";

		/**	----------------------------------------
		/**	 Add <form> attributes from 
		/**	----------------------------------------*/
		
		$allowed = array('accept', 'accept-charset', 'enctype', 'method', 'action',
						 'name', 'target', 'class', 'dir', 'id', 'lang', 'style',
						 'title', 'onclick', 'ondblclick', 'onmousedown', 'onmousemove',
						 'onmouseout', 'onmouseover', 'onmouseup', 'onkeydown', 
						 'onkeyup', 'onkeypress', 'onreset', 'onsubmit');
		
		foreach($extra_attributes as $key => $value)
		{
			if ( in_array($key, $allowed) == FALSE && strncmp($key, 'data-', 5) != 0) continue;
			
			$r = str_replace( "<form", '<form '.$key.'="'.htmlspecialchars($value).'"', $r );
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
        
		return str_replace('{/exp:', LD.T_SLASH.'exp:', str_replace(T_SLASH, '/', $r));
    }
    
    /* END form */
	
	// --------------------------------------------------------------------

	/**
	 *	Return a Parameter for the Submitted Form
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		string
	 *	@return		string
	 */

	public function _param( $which = '', $type = 'all' )
    {	
		/**	----------------------------------------
		/**	Which?
		/**	----------------------------------------*/
		
		if ( $which == '' ) return FALSE;
    	
		/**	----------------------------------------
		/**	Params set?
		/**	----------------------------------------*/
		
		if ( count( $this->params ) == 0 )
		{
			/**	----------------------------------------
			/**	Empty id?
			/**	----------------------------------------*/
			
			if ( ! $this->params_id = ee()->input->get_post('params_id') )
			{
				return FALSE;
			}
			
			/**	----------------------------------------
			/**	Select from DB
			/**	----------------------------------------*/
			
			$query	= ee()->db->query( "SELECT data FROM exp_user_params 
								   WHERE hash = '".ee()->db->escape_str( $this->params_id )."'" );
			
			/**	----------------------------------------
			/**	Empty?
			/**	----------------------------------------*/
			
			if ( $query->num_rows() == 0 ) return FALSE;
			
			/**	----------------------------------------
			/**	Unserialize
			/**	----------------------------------------*/
			
			$this->params			= unserialize( $query->row('data') );
			$this->params['set']	= TRUE;
			
			/**	----------------------------------------
			/**	Delete
			/**	----------------------------------------*/
			
			ee()->db->query( "DELETE FROM exp_user_params WHERE entry_date < ". (ee()->localize->now-7200) );
		}
		
		/**	----------------------------------------
		/**	Fetch from params array
		/**	----------------------------------------*/
		
		if ( isset( $this->params[$which] ) )
		{
			$return	= str_replace( "&#47;", "/", $this->params[$which] );
			
			return $return;
		}
		
		/**	----------------------------------------
		/**	Fetch TMPL
		/**	----------------------------------------*/
		
		if ( isset(ee()->TMPL) && is_object(ee()->TMPL) && ee()->TMPL->fetch_param($which) )
		{
			return ee()->TMPL->fetch_param($which);
		}
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		return FALSE;
    }
    
    /* END params */
    
	
	// --------------------------------------------------------------------

	/**
	 *	Insert Parameters for a Form
	 *
	 *	@access		private
	 *	@param		array
	 *	@return		bool
	 */

	private function _insert_params( $params = array() )
    {
		/**	----------------------------------------
		/**	Empty?
		/**	----------------------------------------*/
    	
    	if ( count( $params ) > 0 )
    	{
    		$this->params	= $params;
    	}
    	elseif ( ! isset( $this->params ) OR count( $this->params ) == 0 )
    	{
    		return FALSE;
    	}
    	
		/**	----------------------------------------
		/**	Delete excess when older than 2 hours
		/**	----------------------------------------*/
		
		ee()->db->query( "DELETE FROM exp_user_params WHERE entry_date < ". (ee()->localize->now-7200) );
    	
		/**	----------------------------------------
		/**	Insert
		/**	----------------------------------------*/
		
		$hash = ee()->functions->random('alpha', 25);
		
		ee()->db->query( ee()->db->insert_string( 'exp_user_params', 
															array(	'hash' => $hash, 
																	'entry_date' => ee()->localize->now,
																	'data' => serialize( $this->params ) ) ) );
    	
		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/
		
		return $hash;
    }
    
    /* END insert params */

    // --------------------------------------------------------------------

	/**
	 *	Automatic way of determining ORDER BY clause and adding to SQL string
	 *
	 *	@access		private
	 *	@param		string
	 *	@param		array
	 *	@return		string
	 */

	private function _order_sort( $sql , $additional = array())
	{   
        /** ----------------------------------------
        /**	Order
        /** ----------------------------------------*/
        
        $others = array( 'username', 'screen_name', 'email', 'group_id', 'join_date', 'total_entries' );
        
        $this->standard	= array_merge( $this->standard, $others );
        
        /** ----------------------------------------
        /**	Control ordering from POST / COOKIE, or
        /** ----------------------------------------*/
        
        $sub_sql	= '';
        
        if ( ee()->TMPL->fetch_param('dynamic_parameters') !== FALSE &&
        	 ee()->TMPL->fetch_param('dynamic_parameters') != 'no' &&
        	 (
        	 	( ee()->input->post('user_orderby') !== FALSE AND ee()->input->post('user_orderby') != '' )
        	 	OR
        	 	( ee()->input->cookie('user_orderby') !== FALSE AND ee()->input->cookie('user_orderby') != '' ) 
        	 )
           )
        {
        	$this->_mfields();
        
			/** ----------------------------------------
			/**	Prepare multiple orders
			/** ----------------------------------------*/
			
			if ( isset( $_POST['user_orderby'] ) === TRUE AND is_array( $_POST['user_orderby'] ) === TRUE AND count( $_POST['user_orderby'] ) > 0 )
			{
				$orderby	= $_POST['user_orderby'];
			}
			elseif ( stristr( ",", ee()->input->post('user_orderby') ) )
			{
				$orderby	= explode( ee()->input->post('user_orderby') );
			}
			elseif ( stristr( ",", ee()->input->cookie('user_orderby') ) )
			{
				$orderby	= explode( ee()->input->cookie('user_orderby') );
			}
			else
			{
				$orderby	= ( ee()->input->post('user_orderby') !== FALSE ) ? array( ee()->input->post('user_orderby') ): array( ee()->input->cookie('user_orderby') );
			}
        
			/** ----------------------------------------
			/**	Prepare multiple sorts
			/** ----------------------------------------*/
			
			$sort	= array();
			
			foreach ( $orderby as $key => $order )
			{				
				if ( stristr( $order, "|" ) )
				{
					$temp	= explode( "|", $order );
					
					$orderby[$key]	= $temp[0];
					$sort[$key]		= $temp[1];
				}
			}
			
			if ( count( $sort ) == 0 )
			{
				if ( ee()->input->post('user_sort') !== FALSE AND ee()->input->post('user_sort') != '' )
				{
					$sort	= ( ee()->input->post('user_sort') != 'asc' ) ? array('DESC'): array('ASC');
				}
				elseif ( ee()->input->cookie('user_sort') !== FALSE AND ee()->input->cookie('user_sort') != '' )
				{
					$sort	= ( ee()->input->cookie('user_sort') != 'asc' ) ? array('DESC'): array('ASC');
				}
				else
				{
					$sort	= array('ASC');
				}
			}
        
			/** ----------------------------------------
			/**	Set cookies
			/** ----------------------------------------*/
			
			if ( ee()->input->post('user_orderby') !== FALSE )
			{
				ee()->functions->set_cookie( 'user_orderby', strtolower( implode( ",", $orderby ) ), 0 );
				ee()->functions->set_cookie( 'user_sort', strtolower( implode( ",", $sort ) ), 0 );
			}			
        
			/** ----------------------------------------
			/**	Loop and order
			/** ----------------------------------------*/
			
			foreach ( $orderby as $key => $order )
			{
				$s	= "";
				
				if ( isset( $sort[$key] ) === TRUE )
				{
					$s	= " ".$sort[$key].",";
				}
				else
				{
					$s	= " ".$sort[0].",";
				}
				
				if ( $order == 'random' )
				{
					$sub_sql	.= "random";
				}
				elseif ( isset($additional[$order]))
				{
					$sub_sql	.= " ".$additional[$order].".".$order.$s;
				}
				elseif ( in_array( $order, $this->standard ) )
				{
					$sub_sql	.= " m.".$order.$s;
				}
				elseif ( isset( $this->mfields[ $order ] ) !== FALSE )
				{
					$sub_sql	.= " md.m_field_id_".$this->mfields[ $order ]['id'].$s;
				}
			}
			
			if ( $sub_sql != '' )
			{
				if ( stristr( $sub_sql, 'random' ) )
				{
					$sql	.= " ORDER BY rand()";
				}
				else
				{
					$sql	.= " ORDER BY ".substr( $sub_sql, 0, -1 );
				}
			}
        }
        
        /** ----------------------------------------
        /**	Control ordering from TMPL
        /** ----------------------------------------*/
        
        elseif ( ee()->TMPL->fetch_param('orderby') !== FALSE AND ee()->TMPL->fetch_param('orderby') != '' )
        {
        	$this->_mfields();
        
			/** ----------------------------------------
			/**	Prepare multiple orders
			/** ----------------------------------------*/
			
			if ( stristr( ee()->TMPL->fetch_param('orderby'), "|" ) )
			{
				$orderby	= explode( "|", ee()->TMPL->fetch_param('orderby') );
			}
			else
			{
				$orderby	= array( ee()->TMPL->fetch_param('orderby') );
			}
        
			/** ----------------------------------------
			/**	Prepare multiple sorts
			/** ----------------------------------------*/
			
			if ( ee()->TMPL->fetch_param('sort') !== FALSE AND ee()->TMPL->fetch_param('sort') != '' )
			{
				if ( stristr( ee()->TMPL->fetch_param('sort'), "|" ) )
				{
					$sort	= explode( "|", strtoupper( ee()->TMPL->fetch_param('sort') ) );
				}
				else
				{
					$sort	= array( strtoupper( ee()->TMPL->fetch_param('sort') ) );
				}
			}
			else
			{
				$sort	= array('DESC');
			}
        
			/** ----------------------------------------
			/**	Loop and order
			/** ----------------------------------------*/
			
			foreach ( $orderby as $key => $order )
			{
				$s	= "";
				
				if ( isset( $sort[$key] ) === TRUE )
				{
					$s	= " ".$sort[$key].",";
				}
				else
				{
					$s	= " ".$sort[0].",";
				}
				
				if ( $order == 'random' )
				{
					$sub_sql	= "random";
				}	
				elseif ( isset($additional[$order]))
				{
					$sub_sql	.= " ".$additional[$order].".".$order.$s;
				}
				elseif ( in_array( $order, $this->standard ) )
				{
					$sub_sql	.= " m.".$order.$s;
				}
				elseif ( isset( $this->mfields[ $order ] ) !== FALSE )
				{
					$sub_sql	.= " md.m_field_id_".$this->mfields[ $order ]['id'].$s;
				}
			}
			
			if ( $sub_sql != '' )
			{
				if ( stristr( $sub_sql, 'random' ) )
				{
					$sql	.= " ORDER BY rand()";
				}
				else
				{
					$sql	.= " ORDER BY ".substr( $sub_sql, 0, -1 );
				}
			}
        }
        
        /** ----------------------------------------
        /**	Limit
        /** ----------------------------------------*/
        
        if ( ee()->TMPL->fetch_param('limit') !== FALSE AND ctype_digit( ee()->TMPL->fetch_param('limit') ) )
        {
			$this->limit	= ee()->TMPL->fetch_param('limit');
        }
        
        if ( ee()->TMPL->fetch_param('dynamic_parameters') !== FALSE AND ee()->TMPL->fetch_param('dynamic_parameters') != 'no' )
        {
        	if ( ee()->input->post('user_limit') !== FALSE AND ee()->input->post('user_limit') != '' )
        	{
				$this->limit	= ee()->input->post('user_limit');
				ee()->functions->set_cookie( 'limit', ee()->input->post('user_limit'), 0 );
        	}
        	elseif ( ee()->input->cookie('user_limit') !== FALSE AND ee()->input->cookie('user_limit') != '' )
        	{
				$this->limit	= ee()->input->cookie('user_limit');
        	}
        }
        
        /** ----------------------------------------
        /**	Return
        /** ----------------------------------------*/
        
        return $sql;
	}
	
	/* END order sort */

	// --------------------------------------------------------------------

	/**
	 *	Prepare Pagination
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		string
	 *	@return		string
	 */
	 
	public function _prep_pagination( $sql, $url_suffix = '', $prefix = TRUE )
	{	
		$query = ee()->db->query(preg_replace("/SELECT(.*?)\s+FROM\s+/is", 'SELECT COUNT(*) AS count FROM ', $sql));
		
		if ($query->row('count') == 0)
		{
			return '';
		}
	
		$total_results	= $query->row('count');
		
		//get pagination info
		$pagination_data = $this->universal_pagination(array(
			'sql'					=> $sql,
			'url_suffix' 			=> $url_suffix,
			'total_results'			=> $total_results, 
			'tagdata'				=> ee()->TMPL->tagdata,
			'limit'					=> $this->limit,
			'uri_string'			=> ee()->uri->uri_string,
			//'current_page'			=> $this->p_page,
		));

		//if we paginated, sort the data
		if ($pagination_data['paginate'] === TRUE)
		{
			$this->paginate			= $pagination_data['paginate'];
			$this->page_next		= $pagination_data['page_next']; 
			$this->page_previous	= $pagination_data['page_previous'];
			$this->p_page			= $pagination_data['pagination_page'];
			$this->current_page  	= $pagination_data['current_page'];
			$this->pager 			= $pagination_data['pagination_links'];
			$this->basepath			= $pagination_data['base_url'];
			$this->total_pages		= $pagination_data['total_pages'];
			$this->paginate_data	= $pagination_data['paginate_tagpair_data'];
			$this->page_count		= $pagination_data['page_count'];
			ee()->TMPL->tagdata		= $pagination_data['tagdata'];
		}
		
		return $pagination_data['sql'];
		
		/*
		if (preg_match("/".LD."paginate".RD."(.+?)".LD.preg_quote(T_SLASH, '/')."paginate".RD."/s", ee()->TMPL->tagdata, $match))
		{
			$this->paginate = TRUE;
			$this->paginate_data = $match[1];
			
			ee()->TMPL->tagdata = str_replace( $match[0], "", ee()->TMPL->tagdata );
			
			$query = ee()->db->query(preg_replace("/SELECT(.*?)\s+FROM\s+/is", 'SELECT COUNT(*) AS count FROM ', $sql));
			
			if ($query->row('count') == 0)
			{
				return '';
			}
		
			$total_results	= $query->row('count');
			
			// --------------------------------------------
			//  Current Page
			// --------------------------------------------
	
			if ( preg_match( "/P(\d+)/s", ee()->uri->uri_string, $match ) )
			{
				$page_number	= $match['1'];
			}
			else
			{
				$page_number	= '';
			}
			
			if ( $this->cur_page == '' AND $page_number != '' )
			{
				$this->cur_page	= $page_number;
			}
			
			// ----------------------------------------
			//  Calculate total number of pages
			// ----------------------------------------
				
			$this->current_page =  ($this->cur_page / $this->limit) + 1;
				
			$this->total_pages = intval($total_results / $this->limit);
			
			if ($total_results % $this->limit) 
			{
				$this->total_pages++;
			}		
			
			$this->page_count = ee()->lang->line('page').' '.$this->current_page.' '.ee()->lang->line('of').' '.$this->total_pages;
			
			// -----------------------------
			//  Do we need pagination?
			// -----------------------------
					
			$this->pager	= '';
			
			if ( $total_results > $this->limit )
			{	
				if ( ! class_exists('Paginate'))
				{
					if (APP_VER < 2.0)
					{
						require PATH_CORE.'core.paginate'.EXT;
					}
					else
					{
						require APPPATH.'_to_be_replaced/lib.paginate'.EXT;
					}
				}
	
				$PGR = new Paginate();
				
				$this->res_page		= ( $this->res_page == '' ) ? str_replace( "P".$page_number, "", ee()->uri->uri_string ): $this->res_page;
				
				$this->res_page		= '/'.trim($this->res_page, '/').'/';
							
				$PGR->path			= ee()->functions->create_url($this->res_page.$url_suffix, (APP_VER < 2.0 && $prefix === TRUE) ? TRUE : FALSE, 0);
				$PGR->total_count 	= $total_results;
				$PGR->prefix		= ($prefix === FALSE) ? '' : 'P';
				$PGR->per_page		= $this->limit;
				$PGR->cur_page		= $this->cur_page;
				
				$this->pager		= $PGR->show_links();
				
				// --------------------------------------------
				//  Bug Fix for 2.x Stupid Extra Slash
				// --------------------------------------------
				
				if (APP_VER >= 2.0)
				{
					$this->pager = preg_replace("/(".preg_quote($this->res_page.$url_suffix, '/').')\/([0-9]+\/)/', '\\1\\2', $this->pager); 
				}
			}
		}
		else
		{
			$this->cur_page = 0;
		}
		
		$offset = ( ! ee()->TMPL->fetch_param('offset') OR ! is_numeric(ee()->TMPL->fetch_param('offset'))) ? '0' : ee()->TMPL->fetch_param('offset');
		
		$this->cur_page += $offset;
		
		return $sql .= " LIMIT ".$this->cur_page.", ".$this->limit;*/
	}
	
	/* END prep pagination	*/
	

    
	// --------------------------------------------------------------------

	/**
	 *	Force HTTPS/SSL on Form Submission
	 *
	 *	@access		public
	 *	@return		redirect
	 */
	
	private function _force_https()
	{
		if ( ! isset($_POST['ACT']) OR $this->_param('secure_action') != 'yes') return;
		
		if ( ! isset($_SERVER['HTTPS']) OR strtolower($_SERVER['HTTPS']) != 'on')
		{
			header("Location: https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
			exit;
		}
	}
    /* END force_https() */
    
    
    // --------------------------------------------------------------------

	/**
	 *	Output Custom Error Template
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		array
	 *	@return		string
	 */
	
	private function _output_error($type, $errors)
	{
		global $TMPL;
		
		if ( REQ == 'PAGE' && is_object(ee()->TMPL) && ee()->TMPL->fetch_param('error_page') !== FALSE)
		{
			$_POST['error_page'] = str_replace(T_SLASH, '/', ee()->TMPL->fetch_param('error_page'));
		}
	
		if ( ! isset($_POST['error_page']) OR $_POST['error_page'] == '' OR ! stristr($_POST['error_page'], '/'))
		{
			return ee()->output->show_user_error($type, $errors);
		}
		
		/** --------------------------------------------
        /**  Retrieve Template
        /** --------------------------------------------*/
        
        $x = explode('/', $_POST['error_page']);
        
        if ( ! isset($x[1])) $x[1] = 'index';
		
		$query = ee()->db->query(  "SELECT template_data, group_name, template_name, template_type
									FROM exp_templates AS t, exp_template_groups AS tg 
									WHERE t.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'
									AND t.group_id = tg.group_id
									AND t.template_name = '".ee()->db->escape_str($x[1])."'
									AND tg.group_name = '".ee()->db->escape_str($x[0])."'
									LIMIT 1");
							 
		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error($type, $errors);
		}
		
		$template_data = stripslashes($query->row('template_data'));
		
		/** --------------------------------------------
        /**  Template as File?
        /** --------------------------------------------*/
		
		if (ee()->config->item('save_tmpl_files') == 'y' AND ee()->config->item('tmpl_file_basepath') != '')
		{
			$basepath = rtrim(ee()->config->item('tmpl_file_basepath'), '/').'/';
			
			if (APP_VER < 2.0)
			{
				$basepath .= $query->row('group_name').'/'.$query->row('template_name').'.php';
			}
			else
			{
				ee()->load->library('api');
				ee()->api->instantiate('template_structure');
				$basepath .= ee()->config->item('site_short_name').'/'.
							 $query->row('group_name').'.group/'.
							 $query->row('template_name').
							 ee()->api_template_structure->file_extensions($query->row('template_type'));
			}
			
			if (file_exists($basepath))
			{
				$template_data = file_get_contents($basepath);	
			}
		}
		
		switch($type)
		{
			case 'submission' : $heading = ee()->lang->line('submission_error');
				break;
			case 'general'    : $heading = ee()->lang->line('general_error');
				break;
			default           : $heading = ee()->lang->line('submission_error');
				break;
		}
		
		/** --------------------------------------------
        /**  Create List of Errors for Content
        /** --------------------------------------------*/
		
        $content  = '<ul>';
        
        if ( ! is_array($errors))
        {
			$content.= "<li>".$errors."</li>\n";
        }
		else
		{
			foreach ($errors as $val)
			{
				$content.= "<li>".$val."</li>\n";
			}
        }
        
        $content .= "</ul>";
		
		/** --------------------------------------------
        /**  Data Array
        /** --------------------------------------------*/
        
        $data = array(	'title' 		=> ee()->lang->line('error'),
        				'heading'		=> $heading,
        				'content'		=> $content,
        				'redirect'		=> '',
        				'meta_refresh'	=> '',
        				'link'			=> array('javascript:history.go(-1)', ee()->lang->line('return_to_previous')),
        				'charset'		=> ee()->config->item('charset')
					 );
					 
		if (is_array($data['link']) AND count($data['link']) > 0)
		{
			$refresh_msg = ($data['redirect'] != '' AND $this->refresh_msg == TRUE) ? ee()->lang->line('click_if_no_redirect') : '';
		
			$ltitle = ($refresh_msg == '') ? $data['link']['1'] : $refresh_msg;
			
			$url = (strtolower($data['link']['0']) == 'javascript:history.go(-1)') ? $data['link']['0'] : ee()->security->xss_clean($data['link']['0']);
		
			$data['link'] = "<a href='".$url."'>".$ltitle."</a>";
		}
					 
		/** --------------------------------------------
        /**  Parse Template
        /** --------------------------------------------*/
		
		foreach ($data as $key => $val)
		{
			$template_data = str_replace('{'.$key.'}', $val, $template_data);
		}
		
		/** --------------------------------------------
        /**  For a Page Request, We Just Return and Let the Template Parser Do the Rest
        /** --------------------------------------------*/
		
		if (REQ == 'PAGE')
		{
			return str_replace('/', T_SLASH, $template_data);
		}
		
		/** --------------------------------------------
        /**  For Action Requests, We Need to Bring in the Parser
        /** --------------------------------------------*/
        
        if ( ! class_exists( 'User_parser' ) )
		{
			require_once $this->addon_path.'parser.user.php';
		}
		
		unset($TMPL, $GLOBALS['TMPL']); // Clear old $TMPL global completely out of memory
						
		global $TMPL;
		
		$TMPL = ee()->TMPL = new User_parser();
		exit(ee()->TMPL->process_string_as_template($template_data));
	}
    /* END _output_error() */    
}

/* END CLASS User */