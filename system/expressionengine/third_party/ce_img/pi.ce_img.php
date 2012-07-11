<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
/*
====================================================================================================
 Author: Aaron Waldon (Causing Effect)
 http://www.causingeffect.com
====================================================================================================
 This file must be placed in the system/expressionengine/third_party/ce_img folder in your ExpressionEngine installation.
 package 		CE Image (EE2 Version)
 version 		Version 2.4
 copyright 		Copyright (c) 2012 Causing Effect, Aaron Waldon <aaron@causingeffect.com>
 Last Update	19 June 2012
----------------------------------------------------------------------------------------------------
 Purpose: Powerful image manipulation made easy
====================================================================================================

License:
    CE Image is licensed under the Commercial License Agreement found at http://www.causingeffect.com/software/expressionengine/ce-image/license-agreement
	Here are a couple of specific points from the license to note again:
    * One license grants the right to perform one installation of CE Image. Each additional installation of CE Image requires an additional purchased license.
    * You may not reproduce, distribute, or transfer CE Image, or portions thereof, to any third party.
	* You may not sell, rent, lease, assign, or sublet CE Image or portions thereof.
	* You may not grant rights to any other person.
	* You may not use CE Image in violation of any United States or international law or regulation.
	The only exceptions to the above four (4) points are any methods clearly designated as having an MIT-style license. Those portions of code specifically designated as having an MIT-style license, and only those portions, will remain bound to the terms of that license.
*/

$plugin_info = array(
						'pi_name'			=> 'CE Image',
						'pi_version'		=> '2.4',
						'pi_author'			=> 'Aaron Waldon (Causing Effect)',
						'pi_author_url'		=> 'http://www.causingeffect.com/software/ee/ce_img',
						'pi_description'	=> 'Powerful image manipulation made easy.',
						'pi_usage'			=> Ce_img::usage()
					);

class Ce_img
{
	//---------- you can change the values for the following 10 variables ----------
	/* The directory in which the manipulated images will be cached.
	This script will first try to use the cache_dir= parameter from the tag (highest priority),
	then try and use the 'ce_image_cache_dir' item in config.php (medium priority),
	and then try the below $cache_dir value if the other two are not present (lowest priority). */
	private $cache_dir = '/images/made/';

	/* The directory in which remote images will be cached (if the image is from another domain).
	This script will first try to use the remote_dir= parameter from the tag (highest priority),
	then try and use the 'ce_image_remote_dir' item in config.php (medium priority),
	and then try the below $remote_dir value if the other two are not present (lowest priority). */
	private $remote_dir = '/images/remote/';

	/* The time in minutes that a remote image should be cached, if the last modified date cannot
	be retrieved with cURL.
	This script will first try to use the remote_cache_time= parameter from the tag (highest priority),
	then try and use the 'ce_image_remote_cache_time' item in config.php (medium priority),
	and then try the below $remote_cache_time value if the other two are not present (lowest priority). */
	private $remote_cache_time = -1;

	/* Your current domain.  Compared to URLs to determine if an image is remote or local.
	For example: http://www.example.com/
	Generally leave blank and it will figure itself out. This value can be overwritten in config.php. */
	private $current_domain = '';

	/* The default quality for images saved to jpeg format.
	This script will first try to use the quality= parameter from the tag (highest priority),
	then try and use the 'ce_image_quality' item in config.php (medium priority),
	and then try the below $quality value if the other two are not present (lowest priority). */
	private $quality = 100;

	/* This determines whether the filename, directory name, or neither will be unique.
	This script will first try to use the unique= parameter from the tag (highest priority),
	then try and use the 'ce_image_unique' item in config.php (medium priority),
	and then try the below $unique value if the other two are not present (lowest priority).
	Possible values are: 'filename', 'directory_name', or 'none' */
	private $unique = 'filename';

	/* The PHP memory limit.
	This script needs an adequate amount of memory (measured in megabytes) allocated from your server in
	order to work. Generally, 64 should be more than enough. This value can also be overwritten in config.php. */
	private $memory_limit = 64;

	/* The mode (permission level) to try and set the created image to. Must be octal.
	See http://php.net/manual/en/function.chmod.php for more info. This value can be overwritten in config.php. */
	private $image_permissions = 0644;

	/* The mode (permission level) to try and set the created directories to. Must be octal.
	See http://php.net/manual/en/function.chmod.php for more info. This value can be overwritten in config.php.*/
	private $dir_permissions = 0777;

	/* Can be '' (default), or the name of a folder that you would like to be automatically created
	in the same image directory as the source image (if the source image is above web root). The
	manipulated image will then be cached inside this directory. If the image is above web root,
	the folder will be created in the cache_dir instead. If you are pulling images from below web
	root, it is best to leave this as ''. This value can be overwritten in config.php. */
	private $auto_cache = '';

	//---------- don't change anything below here ----------

	//plugin parameters that should not be auto included in the tag attributes
	public static $valid_params = array( 'allow_overwrite_original', 'allow_scale_larger', 'ascii_art', 'attributes', 'auto_cache', 'bg_color', 'bg_color_default', 'border', 'cache', 'cache_dir', 'create_tag', 'crop', 'debug', 'dir_permissions', 'disable_xss_check', 'encode_urls', 'fallback_src', 'filename', 'filename_prefix', 'filename_suffix', 'filter', 'flip', 'force_remote', 'hash_filename', 'height', 'hide_relative_path', 'image_permissions', 'manipulate', 'max', 'max_height', 'max_width', 'min', 'min_height', 'min_width', 'output', 'overwrite_cache', 'parse', 'quality', 'reflection', 'refresh', 'remote_cache_time', 'remote_dir', 'rotate', 'rounded_corners', 'save_type', 'src', 'text', 'top_colors', 'unique', 'url_only', 'watermark', 'width' );

	private static $started = false;

	function __construct()
	{
		//EE super global
		$this->EE =& get_instance();

		if ( ! self::$started )
		{
			self::$started = true;
			if ($this->EE->extensions->active_hook('ce_img_start'))
			{
				$this->EE->extensions->call('ce_img_start');
			}
		}
	}

	/**
	 * The default settings needed to open an image without manipulating it.
	 *
	 * @return array Default settings.
	 */
	private function get_open_defaults()
	{
		//base param
		$base = $_SERVER['DOCUMENT_ROOT']; //FCPATH;
		if ( isset( $this->EE->config->_global_vars['ce_image_document_root'] ) && $this->EE->config->_global_vars['ce_image_document_root'] != '' ) //first check global array
		{
			$base = $this->EE->config->_global_vars['ce_image_document_root'];
		}
		else if ($this->EE->config->item('ce_image_document_root') != '') //then check config
		{
			$base = $this->EE->config->item('ce_image_document_root');
		}
		$defaults['base'] = $base;
		unset( $base );

		//current_domain param
		$defaults['current_domain'] = $this->current_domain;
		if ( $this->EE->config->item('ce_image_current_domain') != FALSE) //attempt to get current domain from config
		{
			$defaults['current_domain'] = $this->EE->config->item('ce_image_current_domain');
		}

		//fallback_src param
		$defaults['fallback_src'] = $this->EE->TMPL->fetch_param('fallback_src', '');

		//made_regex - check global array and config
		if ( isset( $this->EE->config->_global_vars['ce_image_made_regex'] ) && $this->EE->config->_global_vars['ce_image_made_regex'] != '' )
		{
			//since the EE team unexpectedly botched the ability to have arrays as global vars, we now have to work with a string...
			$temp = $this->EE->config->_global_vars['ce_image_made_regex'];

			$defaults['made_regex'] = $this->ensure_array( $temp );
		}
		else //config arrays still work...
		{
			$defaults['made_regex'] = $this->EE->config->item('ce_image_made_regex');
		}

		//memory_limit
		if ( $this->EE->config->item('ce_image_memory_limit') != '' )
		{
			$this->memory_limit = $this->EE->config->item('ce_image_memory_limit');
		}
		$defaults['memory_limit'] = $this->memory_limit;

		//remote_dir param
		if ( $this->EE->TMPL->fetch_param('remote_dir') != '' )
		{
			$this->remote_dir = $this->EE->TMPL->fetch_param('remote_dir');
		}
		else if ( $this->EE->config->item('ce_image_remote_dir') != FALSE )
		{
			$this->remote_dir = $this->EE->config->item('ce_image_remote_dir');
		}
		$defaults['remote_dir'] = $this->remote_dir;

		//src_regex param - check global array and config
		if ( isset( $this->EE->config->_global_vars['ce_image_src_regex'] ) && $this->EE->config->_global_vars['ce_image_src_regex'] != '' )
		{
			//since the EE team unexpectedly botched the ability to have arrays as global vars, we now have to work with a string...
			$temp = $this->EE->config->_global_vars['ce_image_src_regex'];

			$defaults['src_regex'] = $this->ensure_array( $temp );
		}
		else //config arrays still work...
		{
			$defaults['src_regex'] = $this->EE->config->item('ce_image_src_regex');
		}

		return $defaults;
	}

	/**
	 * The default settings needed to make an image. Includes the settings returned by the get_open_defaults method.
	 *
	 * @return array Default settings.
	 */
	public function get_make_defaults()
	{
		$defaults = $this->get_open_defaults();

		//allow_overwrite_original param
		$defaults['allow_overwrite_original'] = ( strtolower($this->EE->TMPL->fetch_param('allow_overwrite_original')) == 'yes' );

		//allow_scale_larger param
		$defaults['allow_scale_larger'] = ( strtolower($this->EE->TMPL->fetch_param('allow_scale_larger')) == 'yes' );

		//bg_color param
		$defaults['bg_color'] = $this->EE->TMPL->fetch_param('bg_color');

		//bg_color_default param
		$defaults['bg_color_default'] = $this->EE->TMPL->fetch_param('bg_color_default', 'ffffff');

		//border param
		$border = trim( $this->EE->TMPL->fetch_param('border') );
		if ( $border != '' )
		{
			$border = explode( '|', $border );
		}
		$defaults['border'] = $border;
		unset( $border );

		//cache_dir param
		if ($this->EE->TMPL->fetch_param('cache_dir') !== FALSE)
		{
			$this->cache_dir = $this->EE->TMPL->fetch_param('cache_dir');
		}
		else if ($this->EE->config->item('ce_image_cache_dir') != FALSE)
		{
			$this->cache_dir = $this->EE->config->item('ce_image_cache_dir');
		}
		$defaults['cache_dir'] = $this->cache_dir;
		unset( $this->cache_dir );

		//crop param
		$crop = strtolower( $this->EE->TMPL->fetch_param('crop') );
		if ( $crop == '' ||  $crop[0] == 'n' || $crop[0] == 'o'  )
		{
			$crop = FALSE;
		}
		else
		{
			//test just to make sure
			$crop = explode( '|', $crop );
			if ( $crop[0] == 'yes' || $crop[0] == 'y' || $crop[0] == 'on' )
			{
				$crop[0] = TRUE;

				$crop_count = count( $crop );

				//positions
				if ( $crop_count > 1 )
				{
					$crop[1] = explode( ',', $crop[1] );
				}

				//offsets
				if ( $crop_count > 2 )
				{
					$crop[2] = explode( ',', $crop[2] );
				}

				//smart scale
				if ( $crop_count > 3 )
				{
					$crop[3] = ($crop[3] == 'no' || $crop[3] == 'n' || $crop[3] == 'off' ) ? FALSE : TRUE;
				}

			}
			else
			{
				$crop[0] = FALSE;
			}
		}
		$defaults['crop'] = $crop;
		unset( $crop );

		//disable_xss_check param
		$defaults['disable_xss_check'] = $this->ee_string_to_bool( $this->determine_setting( 'disable_xss_check', 'yes' ) );

		//filename param
		$defaults['filename'] = trim( $this->EE->TMPL->fetch_param('filename', '') );

		//filename_prefix param
		$defaults['filename_prefix'] = trim( $this->EE->TMPL->fetch_param('filename_prefix', '') );

		//filename param
		$defaults['filename_suffix'] = trim( $this->EE->TMPL->fetch_param('filename_suffix', '') );

		//filter param
		$filter = trim( $this->EE->TMPL->fetch_param('filter', '') );
		$filters = array();
		if ( ! empty( $filter ) )
		{
			$filter = str_replace( 'emboss_color', 'cemboss', $filter );
			$filter = explode( '|', trim( $filter, '|' ) );

			foreach( $filter as $f )
			{
				$filters[] = explode( ',', $f ); //trim( $f, ',' ) );
			}
		}
		$defaults['filters'] = $filters;
		unset( $filter );
		unset( $filters );

		//hash_filename param
		$defaults['hash_filename'] = ( strtolower($this->EE->TMPL->fetch_param('hash_filename')) == 'yes' );

		//force_remote param
		$defaults['force_remote'] = ( strtolower($this->EE->TMPL->fetch_param('force_remote')) == 'yes' );

		//flip param
		$flip = strtolower( $this->EE->TMPL->fetch_param('flip') );
		if ( $flip == '' )
		{
			$defaults['flip'] = FALSE;
		}
		else
		{
			$defaults['flip'] = explode( '|', $flip );
		}

		//hide_relative_path param
		$defaults['hide_relative_path'] = ( $this->EE->TMPL->fetch_param('hide_relative_path') == 'yes' );

		//height param
		$defaults['height'] = $this->EE->TMPL->fetch_param('height', 0);
		//width param
		$defaults['width'] = $this->EE->TMPL->fetch_param('width', 0);

		//max param
		$max = $this->EE->TMPL->fetch_param('max', 0);
		//max_height param
		$defaults['max_height'] = $this->EE->TMPL->fetch_param('max_height', $max);
		//max_width param
		$defaults['max_width'] = $this->EE->TMPL->fetch_param('max_width', $max);

		//min param
		$min = $this->EE->TMPL->fetch_param('min', 0);
		//min_height param
		$defaults['min_height'] = $this->EE->TMPL->fetch_param('min_height', $min);
		//min_width param
		$defaults['min_width'] = $this->EE->TMPL->fetch_param('min_width', $min);

		//overwrite_cache param
		$defaults['overwrite_cache'] = ( strtolower($this->EE->TMPL->fetch_param('overwrite_cache')) == 'yes' );

		//image permissions
		if ( $this->EE->config->item('ce_image_image_permissions') != FALSE && is_numeric( $this->EE->config->item('ce_image_image_permissions') ) )
		{
			$this->image_permissions = $this->EE->config->item('ce_image_image_permissions');
		}
		$defaults['image_permissions'] = $this->image_permissions;

		//directory permissions
		if ( $this->EE->config->item('ce_image_dir_permissions') != FALSE && is_numeric( $this->EE->config->item('ce_image_dir_permissions') ) )
		{
			$this->dir_permissions = $this->EE->config->item('ce_image_dir_permissions');
		}
		$defaults['dir_permissions'] = $this->dir_permissions;

		//quality param
		if ( $this->EE->TMPL->fetch_param('quality') != '' )
		{
			$this->quality = $this->EE->TMPL->fetch_param('quality');
		}
		else if ($this->EE->config->item('ce_image_quality') != FALSE)
		{
			$this->quality = $this->EE->config->item('ce_image_quality');
		}
		$defaults['quality'] = $this->quality;

		//reflection param
		$reflection = trim( $this->EE->TMPL->fetch_param('reflection') );
		$reflection = ( $reflection != '' ) ? explode( ',', $reflection ) : FALSE;
		$defaults['reflection'] = $reflection;
		unset( $reflection );

		//remote_cache_time param
		if ( $this->EE->TMPL->fetch_param('remote_cache_time') != '' )
		{
			$this->remote_cache_time = $this->EE->TMPL->fetch_param('remote_cache_time');
		}
		else if ($this->EE->config->item('ce_image_remote_cache_time') != FALSE)
		{
			$this->remote_cache_time = $this->EE->config->item('ce_image_remote_cache_time');
		}
		$defaults['remote_cache_time'] = $this->remote_cache_time;

		//rotate param
		$defaults['rotate'] = strtolower( $this->EE->TMPL->fetch_param('rotate') );

		//rounded_corners param
		$corners = trim( $this->EE->TMPL->fetch_param('rounded_corners') );
		if ( $corners != '' )
		{
			$corners = explode( '|', $corners );
			foreach ( $corners as $index => $corner )
			{
				$corners[$index] = explode( ',', $corner );
			}
		}
		else
		{
			$corners = FALSE;
		}
		$defaults['rounded_corners'] = $corners;
		unset( $corners );

		//save_type param
		$defaults['save_type'] = $this->EE->TMPL->fetch_param('save_type');

		//unique param
		if ( $this->EE->TMPL->fetch_param('unique') != '' )
		{
			$this->unique = $this->EE->TMPL->fetch_param('unique');
		}
		else if ($this->EE->config->item('ce_image_unique') != FALSE)
		{
			$this->unique = $this->EE->config->item('ce_image_unique');
		}
		$defaults['unique'] = $this->unique;


		//auto_cache param
		if ( $this->EE->TMPL->fetch_param('auto_cache') !== FALSE )
		{
			$this->auto_cache = $this->EE->TMPL->fetch_param('auto_cache');
		}
		else if ($this->EE->config->item('ce_image_auto_cache') != FALSE)
		{
			$this->auto_cache = $this->EE->config->item('ce_image_auto_cache');
		}
		$defaults['auto_cache'] = $this->auto_cache;


		//watermark param
		$watermark = $this->EE->TMPL->fetch_param('watermark');
		if ( $watermark != '' )
		{
			$watermark = $this->parse_ee_paths( $watermark );

			$watermark = explode( '#', $watermark );
			foreach ( $watermark as $index => $wm )
			{
				$wm = explode( '|', $wm );
				foreach( $wm as $i => $w )
				{
					if ( strpos( $w, ',') !== FALSE )
					{
						$wm[$i] = explode( ',', $w );
					}
				}
				$watermark[$index] = $wm;
			}
		}
		else
		{
			$watermark = FALSE;
		}
		$defaults['watermark'] = $watermark;
		unset( $watermark );

		//text param
		$text = $this->EE->TMPL->fetch_param('text');
		if ( $text != '' )
		{
			$text = explode( '##', $text );
			foreach ( $text as $index => $txt )
			{
				$txt = explode( '|', $txt );
				foreach( $txt as $i => $t )
				{
					if ( $i == 0 )
					{
						continue; //we don't want to break the text on commas
					}
					if ( strpos( $t, ',') !== FALSE )
					{
						$txt[$i] = explode( ',', $t );
					}
				}
				$text[$index] = $txt;
			}
		}
		else
		{
			$text = FALSE;
		}
		$defaults['text'] = $text;
		unset( $text );

		return $defaults;
	}

	/**
	 * Simple method to log an array of debug messages to the EE Debug console.
	 *
	 * @param array $messages The debug messages.
	 * @return void
	 */
	private function log_debug_messages( $messages = array() )
	{
		foreach ( $messages as $message )
		{
			$this->EE->TMPL->log_item( '&nbsp;&nbsp;***&nbsp;&nbsp;CE Image debug: ' . $message );
		}
	}

	/**
	 * The main method. This determines the necessary parameters to make or open an image, and interfaces with the CE Image class to make it happen!
	 *
	 * @return mixed Will return the new tagdata (str) on success or no_results on failure.
	 */
	public function make()
	{
		//if the template debugger is enabled, and a super admin user is logged in, enable debug mode
		$debug = FALSE;
		if ( $this->EE->session->userdata['group_id'] == 1 && $this->EE->config->item('template_debugging') == 'y' )
		{
			$debug = TRUE;
		}

		//manipulate param
		$manipulate = ( strtolower($this->EE->TMPL->fetch_param('manipulate') ) == 'no' ) ? FALSE : TRUE;

		//src param
		$src = $this->EE->TMPL->fetch_param('src');

		//get the settings
		$defaults = ( $manipulate ) ?  $this->get_make_defaults() : $this->get_open_defaults();

		//no need to go forward if there are no src params
		if ( $src == '' && $defaults['fallback_src'] == '' )
		{
			if ( $debug )
			{
				$this->EE->TMPL->log_item( '&nbsp;&nbsp;***&nbsp;&nbsp;CE Image debug: Source and fallback source cannot both be blank.' );
			}

			return $this->EE->TMPL->no_results();
		}

		//check for file paths and replace them if needed
		$src = $this->parse_ee_paths( $src );

		$defaults['fallback_src'] = $this->parse_ee_paths( $defaults['fallback_src'] );


		//include CE Image
		if ( ! class_exists( 'Ce_image' ) )
		{
			require PATH_THIRD . 'ce_img/libraries/Ce_image.php';
		}

		//initialize with the settings
		$image = new Ce_image( $defaults );
		unset( $defaults );

		if ( $manipulate ) //make a new manipulated image
		{
			if ( ! $image->make( $src ) )
			{
				//there was a problem
				if ( $debug )
				{
					$this->log_debug_messages( $image->get_debug_messages() );
				}
				$image->close();
				return $this->EE->TMPL->no_results();
			}
		}
		else //just get an existing image
		{
			if ( ! $image->open( $src ) )
			{
				//there was a problem
				if ( $debug )
				{
					$this->log_debug_messages( $image->get_debug_messages() );
				}
				$image->close();
				return $this->EE->TMPL->no_results();
			}
		}

		//ascii_art param
		$ascii_art = trim( $this->EE->TMPL->fetch_param('ascii_art') );
		if ( $ascii_art != '' )
		{
			$temp = explode( '|', $ascii_art );
			$ascii_art = array();
			if ( is_array( $temp ) )
			{
				$t = trim( strtolower( $temp[0] ));
				if ( $t == 'yes' || $t == 'y' || $t == 'on' )
				{
					$ascii_art[0] = TRUE;
				}
				else
				{
					$ascii_art[0] = FALSE;
				}

				$ascii_art[1] = array('#', '@', '%', '=', '+', '*', ':', '-', '.', '&nbsp;' );
				if ( isset( $temp[1] ) )
				{
					$arr = explode( ',', $temp[1] );
					if ( count( $arr ) > 0 )
					{
						$ascii_art[1] = $arr;
					}
					unset( $arr );
				}

				$ascii_art[2] = FALSE;
				if ( isset( $temp[2] ) )
				{
					$ascii_art[2] = ( $temp[2] == 'yes' || $temp[2] == 'y' || $temp[2] == 'on' );
				}

				$ascii_art[3] = FALSE;
				if ( isset( $temp[3] ) )
				{
					$ascii_art[3] = ( $temp[3] == 'yes' || $temp[3] == 'y' || $temp[3] == 'on' );
				}
			}
			else
			{
				$t = trim( strtolower( $temp ));
				$ascii_art[0] = ( $t == 'yes' || $t == 'y' || $t == 'on' );
			}

			$ascii_art = call_user_func_array( array( $image, 'get_ascii_art' ), $ascii_art );
		}

		//top_colors param
		$top_colors = $this->EE->TMPL->fetch_param('top_colors');
		$top_colors = ( $top_colors != '' ) ? explode( '|', $top_colors ) : '';
		if ( $top_colors != '' )
		{
			$tc_count = count( $top_colors);
			if ( $tc_count > 1 )
			{
				$top_colors = $image->get_top_colors( $top_colors[0], $top_colors[1] );
			}
			else if ( $tc_count > 0 )
			{
				$top_colors = $image->get_top_colors( $top_colors[0] );
			}
			else
			{
				$top_colors = '';
			}
		}

		//get the var prefix
		$var_prefix = '';

		$tag_parts = $this->EE->TMPL->tagparts;
		if ( is_array( $tag_parts ) && isset( $tag_parts[2] ) )
		{
			$var_prefix = $tag_parts[2] . ':';
		}

		//get return tag param
		$create_tag = ( strtolower($this->EE->TMPL->fetch_param('create_tag')) == 'yes' );

		if ( $this->EE->TMPL->fetch_param('url_only') == 'yes' )
		{
			$tagdata = '{' . $var_prefix . 'made}';
			$create_tag = FALSE;
		}
		else if ($this->EE->TMPL->fetch_param('output') != '')
		{
			$tagdata = $this->EE->TMPL->fetch_param('output');
			$create_tag = FALSE;
		}
		else
		{
			//tagdata
			$tagdata = $this->EE->TMPL->tagdata;

			if ($create_tag)
			{
				if (strpos($tagdata, '<img ') !== FALSE)
				{
					//the image has an image tag inside, so ignore request to return a tag
					$create_tag = FALSE;
				}
			}
			else if ($this->EE->TMPL->fetch_param('create_tag') == '' && $tagdata == '') //if there is no tag data, set create_tag to TRUE
			{
				$create_tag = TRUE;
			}

			//create the tag
			if ($create_tag)
			{
				//get the settings as to whether or not to automatically add the dimensions to the tag
				$add_dimensions = $this->ee_string_to_bool( $this->determine_setting('add_dimensions', 'y' ) );

				if (trim($tagdata) == '')
				{
					//get the tag params
					$tag_params = $this->EE->TMPL->tagparams;

					//determine the attributes by getting rid of the parameters
					foreach ($tag_params as $param => $value)
					{
						if (in_array($param, self::$valid_params))
						{
							unset($tag_params[$param]);
						}
					}

					if ( $add_dimensions ) //add in the dimension attributes
					{
						$tag_params[ 'width' ] = '{width}';
						$tag_params[ 'height' ] = '{height}';
					}
					if ( ! isset( $tag_params['alt'] ) ) //add in the alt attribute if not already set
					{
						$tag_params[ 'alt' ] = '';
					}

					//add in the src attribute
					$tag_params = array_merge(array('src' => '{made}'), $tag_params);

					//get the attributes parameter
					$attributes = $this->EE->TMPL->fetch_param('attributes');

					//turn the attributes into key => value pairs
					preg_match_all('@(\S+?)\s*=\s*(\042|\047)([^\\2]*?)\\2@is', $attributes, $attributes, PREG_SET_ORDER);

					//this will hold the image attribute pairs
					$pairs = array();
					foreach ($attributes as $attribute)
					{
						//attribute => value
						$pairs[$attribute[1]] = $attribute[3];
					}

					//mess with styles if applicable
					if (!empty($pairs['style']))
					{
						//the final css array
						$css = array();

						//get the original (tag param) style attribute
						$styles = isset($tag_params['style']) ? $tag_params['style'] : FALSE;

						//unset the attribute
						unset($tag_params['style']);

						//loop through the styles and add them to the css array
						if (!empty($styles))
						{
							$style_array = explode(';', $styles); //get the rules
							foreach ($style_array as $rule)
							{
								$rule = trim($rule);

								if (empty($rule))
								{
									continue;
								}
								$rule = explode(':', $rule, 2); //split into prop, value pairs
								$css[strtolower($rule[0])] = trim($rule[1]);
							}

							unset($style_array);
						}

						//loop through the styles passed into the attributes array
						$style_array = explode(';', $pairs['style']); //get the rules
						if (count($style_array) > 0)
						{
							foreach ($style_array as $rule)
							{
								$rule = trim($rule);

								if (empty($rule))
								{
									continue;
								}
								$rule = explode(':', $rule, 2); //split into prop, value pairs

								$property = strtolower($rule[0]);
								$value = trim($rule[1]);

								//if the value is blank, the user is opting to remove it
								if ($value === '')
								{
									//unset the property
									unset($css[$property]);

									$length = strlen($property) + 1;

									//also remove any descendant rules like 'margin-left' if 'margin' is being removed
									foreach ($css as $p => $v)
									{
										if (substr($p, 0, $length) == $property . '-')
										{
											//unset the property
											unset($css[$p]);
										}
									}
								}
								else //not blank, set the new value
								{
									$css[$property] = $value;
								}
							}
						}

						//rebuild the styles
						$pairs['style'] = '';
						foreach ($css as $key => $value)
						{
							$pairs['style'] .= $key . ': ' . $value . '; ';
						}
						$pairs['style'] = trim($pairs['style']);
					}

					//combine with the parameters - the pairs will have precedence of any conflicts
					$attributes = array_merge($tag_params, $pairs);

					//if the user set the width or height to '', remove the attribute
					if ( isset( $attributes['width'] ) && $attributes['width'] == '' )
					{
						unset( $attributes['width'] );
					}
					if ( isset( $attributes['height'] ) && $attributes['height'] == '' )
					{
						unset( $attributes['height'] );
					}

					//create the tag
					$tagdata = '<img ';
					foreach ($attributes as $param => $value)
					{
						$tagdata .= (strpos($value, '"') === FALSE) ? $param.'="'.$value.'" ' : $param ."='".$value."' ";
					}
					$tagdata .= '/>';

					unset($pairs, $tag_params, $attributes);
				}
			}
		}

		//encode the URLs?
		$encode_urls = $this->ee_string_to_bool( $this->determine_setting( 'encode_urls', 'yes' ) );
		$defaults['encode_urls'] = $encode_urls;

		//---------- return data ----------
		//current data
		$relative = ( $encode_urls ) ? $this->url_encode_lite( $image->get_relative_path() ) : $image->get_relative_path();
		$filename = $image->get_filename();
		$absolute = $image->get_server_path();

		$width = $image->get_width();
		$height = $image->get_height();
		$extension = $image->get_extension();
		$filesize = ( strpos( $tagdata, '{' . $var_prefix . 'filesize}' ) !== FALSE ) ? $image->get_filesize() : '';
		$filesize_bytes = ( strpos( $tagdata, '{' . $var_prefix . 'filesize_bytes}' ) !== FALSE ) ? $image->get_filesize( TRUE ) : '';
		$type = $image->get_type();
		$base64 = ( strpos( $tagdata, '{' . $var_prefix . 'base64}' ) !== FALSE ) ? $this->base64_image( $absolute, $type ) : '';

		//original data
		$relative_orig = ( $encode_urls ) ? $this->url_encode_lite( $image->get_original_relative_path() ) : $image->get_original_relative_path();
		$filename_orig = $image->get_original_filename();
		$absolute_orig = $image->get_original_server_path();
		$width_orig = $image->get_original_width();
		$height_orig = $image->get_original_height();
		$extension_orig = $image->get_original_extension();
		$filesize_orig = ( strpos( $tagdata, '{' . $var_prefix . 'filesize_orig}' ) !== FALSE ) ? $image->get_original_filesize() : '';
		$filesize_bytes_orig = ( strpos( $tagdata, '{' . $var_prefix . 'filesize_bytes_orig}' ) !== FALSE ) ? $image->get_original_filesize( TRUE ) : '';
		$type_orig = $image->get_type_orig();
		$base64_orig = ( strpos( $tagdata, '{' . $var_prefix . 'base64_orig}' ) !== FALSE ) ? $this->base64_image( $absolute_orig, $type_orig ) : '';

		$average_color = ( strpos( $tagdata, '{' . $var_prefix . 'average_color}' ) !== FALSE ) ? $image->get_average_color() : '';

		//for extension devs - returns 'saved', 'cached', or 'none' depending on what was done with the image
		$final_action = $image->get_final_action();

		//log the debug array
		if ( $debug )
		{
			$this->log_debug_messages( $image->get_debug_messages() );
		}

		//close the image
		$image->close();
		unset( $image );

		//conditionals
		$conditionals = array();
		$conditionals['top_colors'] = ( is_array( $top_colors ) ) ? 'TRUE' : 'FALSE';
		$conditionals['ascii_art'] = ( $ascii_art == '' ) ? 'FALSE' : 'TRUE';

		$tagdata = $this->EE->functions->prep_conditionals( $tagdata, $conditionals );

		$variables = array(
			$var_prefix . 'sized' 				=> $relative, //for backward compatibility
			$var_prefix . 'made' 				=> $relative,
			$var_prefix . 'orig' 				=> $relative_orig,
			$var_prefix . 'made_url' 			=> $this->EE->functions->create_url( $relative ),
			$var_prefix . 'orig_url' 			=> $this->EE->functions->create_url( $relative_orig ),
			$var_prefix . 'width' 				=> $width,
			$var_prefix . 'width_orig' 			=> $width_orig,
			$var_prefix . 'height' 				=> $height,
			$var_prefix . 'height_orig' 		=> $height_orig,
			$var_prefix . 'type' 				=> $type,
			$var_prefix . 'type_orig' 			=> $type_orig,
			$var_prefix . 'w' 					=> $width,
			$var_prefix . 'h' 					=> $height,
			$var_prefix . 'name' 				=> $filename,
			$var_prefix . 'name_orig' 			=> $filename_orig,
			$var_prefix . 'path' 				=> $absolute,
			$var_prefix . 'path_orig' 			=> $absolute_orig,
			$var_prefix . 'extension' 			=> $extension,
			$var_prefix . 'extension_orig' 		=> $extension_orig,
			$var_prefix . 'filesize' 			=> $filesize,
			$var_prefix . 'filesize_bytes' 		=> $filesize_bytes,
			$var_prefix . 'filesize_orig' 		=> $filesize_orig,
			$var_prefix . 'filesize_bytes_orig' => $filesize_bytes_orig,
			$var_prefix . 'base64' 				=> $base64,
			$var_prefix . 'base64_orig' 		=> $base64_orig,
			$var_prefix . 'ascii_art' 			=> $ascii_art,
			$var_prefix . 'average_color' 		=> $average_color,
			$var_prefix . 'top_colors' 			=> $top_colors,
			$var_prefix . 'final_action' 		=> $final_action
		);

		//pre parse hook
		if ($this->EE->extensions->active_hook('ce_img_pre_parse'))
		{
			$tagdata = $this->EE->extensions->call('ce_img_pre_parse', $tagdata, $variables, $var_prefix );
		}

		//parse
		$parsed = $this->EE->TMPL->parse_variables_row( $tagdata, $variables );

		//free up some memory
		unset( $ascii_art, $top_colors, $base64, $base64_orig, $variables, $tagdata );

		return $parsed;
	}

	/**
	 * Parses the EE paths.
	 *
	 * @param $string
	 * @return string
	 */
	private function parse_ee_paths( $string )
	{
		//trim the string
		$string = trim( $string );

		//if no string, bail
		if ( $string == '' )
		{
			return $string;
		}

		//replace the site_url variable if applicable
		if ( strpos( $string, '{site_url}' ) !== FALSE )
		{
			$string = str_replace( '{site_url}', stripslashes( $this->EE->config->item( 'site_url' ) ), $string );
		}

		//replace the path= variables if applicable
		if ( strpos( $string, 'path=' ) !== FALSE )
		{
			$string = preg_replace_callback( '@{\s*path=(.*?)}@', array( &$this->EE->functions, 'create_url' ), $string );
		}

		//replace the file directory paths if applicable
		if ( strpos( $string, 'filedir_' ) !== FALSE)
		{
			$filedirs = $this->EE->functions->fetch_file_paths();
			foreach ( $filedirs as $id => $path )
			{
				$string = str_replace( array( '{filedir_'.$id.'}', '&#123;filedir_'.$id.'&#125;' ), $path, $string );
			}
		}

		return $string;
	}

	/**
	 * Parse images in the tagdata.
	 *
	 * @return mixed
	 */
	public function parse_html()
	{
		//get the var prefix
		$var_prefix = '';

		$tag_parts = $this->EE->TMPL->tagparts;
		if ( is_array( $tag_parts ) && isset( $tag_parts[2] ) )
		{
			$var_prefix = ':' . $tag_parts[2];
		}

		//get the tagdata
		$tagdata = $this->EE->TMPL->tagdata;

		//get the tag params
		$tag_params = $this->EE->TMPL->tagparams;
		if ( ! is_array( $tag_params ) )
		{
			$tag_params = array();
		}

		//exclude_regex - check the parameter, global array, and config
		if ( $this->EE->TMPL->fetch_param('exclude_regex') !== FALSE )
		{
			$exclude = $this->EE->TMPL->fetch_param('exclude_regex');
		}
		else if ( isset( $this->EE->config->_global_vars['ce_image_exclude_regex'] ) && $this->EE->config->_global_vars['ce_image_exclude_regex'] != '' )
		{
			//since the EE team unexpectedly botched the ability to have arrays as global vars, we now have to work with a string...
			$exclude = $this->EE->config->_global_vars['ce_image_exclude_regex'];
		}
		else //config arrays still work...
		{
			$exclude = $this->EE->config->item('ce_image_exclude_regex');
		}
		if ( is_bool( $exclude ) )
		{
			$exclude = array();
		}
		if ( is_string( $exclude ) )
		{
			$exclude = explode( '@', $exclude );
		}
		//this may be overkill, but I want to make sure this is an array
		if ( ! is_array( $exclude ) )
		{
			$exclude = array();
		}

		//get rid of attributes we don't want in our tag
		unset( $tag_params['parse'], $tag_params['cache'], $tag_params['refresh'], $tag_params['dimensions_from_style'], $tag_params['exclude_regex'] );

		//grab all of the images
		preg_match_all( '@<img([^>]*?)/?>@uSi', $tagdata, $matches, PREG_SET_ORDER );

		//loop through the images
		foreach ( $matches as $match )
		{
			//get the attributes
			preg_match_all( '@(\S+?)\s*=\s*(\042|\047)([^\\2]*?)\\2@is', $match[1], $attributes, PREG_SET_ORDER);

			//this will hold the image attribute pairs
			$pairs = array();
			foreach ( $attributes as $attribute )
			{
				//attribute => value
				$pairs[ $attribute[1] ] = $attribute[3];
			}

			//if the src matches an exclude regex, let's skip it
			if ( ! empty( $exclude ) && isset( $pairs['src'] ) )
			{
				foreach( $exclude as $ex )
				{
					if ( preg_match( '@' . $ex . '@', $pairs['src'] ) )
					{
						continue 2;
					}
				}
			}

			//attempt to get the dimensions from the image style tag if applicable - these will have precedence over the width and height tag attributes.
			if ( $this->EE->TMPL->fetch_param('dimensions_from_style') != 'no' )
			{
				if ( isset( $pairs['style'] ) )
				{
					$styles = rtrim( $pairs['style'], ' ;');
					$rules = explode( ';', $styles );
					foreach ( $rules as $index => $rule )
					{
						list( $key, $value ) = explode( ':', $rule ) + Array( null, null );

						if ( trim( $key ) == 'width' && $value != null )
						{
							//override the width with the style value
							$pairs['width'] = str_ireplace( 'px', '', trim( $value ) );
							//unset the width style, as we don't want this to supersede the manipulated image height
							unset( $rules[ $index ] );
						}
						else if (trim($key) == 'height' && $value != null)
						{
							//override the height with the style value
							$pairs['height'] = str_ireplace('px', '', trim($value));
							//unset the height style, as we don't want this to supersede the manipulated image height
							unset($rules[$index]);
						}
					}

					//re-construct the style attribute if applicable
					if ( count( $rules) > 0 ) //There are rules.
					{
						$pairs['style'] = implode( ';', $rules ) . ';';
					}
					else //No more rules, remove the style attribute.
					{
						unset( $pairs['style'] );
					}
				}
			}

			//merge with the tag params - the tag params will have precedence of any conflicts
			$pairs = array_merge( $pairs, $tag_params );

			//create the CE Image single tag from the attributes
			$attributes = '';
			foreach ( $pairs as $param => $value )
			{
					//$attributes .= $param . '="' . $value . '" ';
					$attributes .= ( strpos( $value, '"' ) === FALSE) ? "{$param}=\"{$value}\" " : "{$param}='{$value}' ";
			}
			$tag = '{exp:ce_img:single' . $var_prefix . ' ' . $attributes . '}';

			//replace the tag
			$tagdata = preg_replace( '@' . preg_quote( $match[0], '@' ) . '@', $tag, $tagdata, 1 );
		}

		return $tagdata;
	}

	public function bulk()
	{
		return $this->parse_html();
	}

	/**
	 * For backward compatibility.
	 * @return string
	 */
	public function size()
	{
		return $this->make();
	}

	/**
	 * Should not have a closing tag when used. Helps prevent EE from getting confused.
	 * @return string
	 */
	public function single()
	{
		return $this->make();
	}

	/**
	 * Should have a closing tag when used. Helps prevent EE from getting confused.
	 * @return string
	 */
	public function pair()
	{
		return $this->make();
	}

	/**
	 * Base64 encodes an image.
	 * @param string $path
	 * @param string $type
	 * @return string The encoded image or ''
	 */
	protected function base64_image( $path, $type )
	{
		$contents = @file_get_contents( $path );
		if ( ! $contents )
		{
			$this->EE->TMPL->log_item( '&nbsp;&nbsp;***&nbsp;&nbsp;CE Image debug: There was an error getting the contents of the file "' . $path . '" to convert to base64.' );
		}
		else
		{
			$contents = @base64_encode( $contents );
			if ( ! $contents )
			{
				$this->EE->TMPL->log_item( '&nbsp;&nbsp;***&nbsp;&nbsp;CE Image debug: There was an error converting the contents of the file "' . $path . '" to base64.' );
			}
			else
			{
				$mime = ( $type == 'jpg' ) ? 'jpeg' : $type;
				return 'data:image/' . $mime . ';base64,' . $contents;
			}
		}

		unset( $contents );
		return FALSE;
	}

	/**
	 * Swaps spaces for %20 in a URL
	 *
	 * @param $url
	 * @return mixed
	 */
	protected function url_encode_lite( $url )
	{
		return str_replace( ' ', '%20', $url );
	}

	/**
	 * Determines the given setting by checking for the param, and then for the global var, and then for the config item.
	 * @param string $name The name of the parameter. The string 'ce_image_' will automatically be prepended for the global and config setting checks.
	 * @param string $default The default setting value
	 * @return string The setting value if found, or the default setting if not found.
	 */
	protected function determine_setting( $name, $default = '' )
	{
		$long_name = 'ce_image_' . $name;
		if ( $this->EE->TMPL->fetch_param( $name ) !== FALSE ) //param
		{
			$default = $this->EE->TMPL->fetch_param( $name );
		}
		else if ( isset( $this->EE->config->_global_vars[ $long_name ] ) && $this->EE->config->_global_vars[ $long_name ] !== FALSE ) //first check global array
		{
			$default = $this->EE->config->_global_vars[ $long_name ];
		}
		else if ( $this->EE->config->item( $long_name ) !== FALSE ) //then check config
		{
			$default = $this->EE->config->item( $long_name );
		}

		return $default;
	}

	/**
	 * Little helper method to convert parameters to a boolean value.
	 *
	 * @param $string
	 * @return bool
	 */
	protected function ee_string_to_bool( $string )
	{
		if ( is_bool( $string ) )
		{
			return $string;
		}
		$string = strtolower( $string );
		return ( $string == 'y' || $string == 'yes' || $string == 'on' );
	}

	/**
	 * This will convert string arrays to actual arrays if needed. This is because EE broke the ability for global variables to be arrays, and it can be useful for tag params as they are all strings too.
	 *
	 * So something like this: '^/=>/~user/|foo=>bar'
	 * will become this: array( '^/' => '/~user/', 'foo' => 'bar' );
	 *
	 * @param string|array $value
	 * @return array
	 */
	protected function ensure_array( $value )
	{
		if ( is_bool( $value ) )
		{
			return array();
		}

		if ( is_string( $value ) )
		{
			//explode the string by '|'
			$old = explode( '|', $value );
			$value = array();

			foreach ( $old as $k => $v )
			{
				//explode the value by ','
				list( $key, $value ) = explode( '=>', $v, 2 ) + array( '', '' );
				//trim the key and value and add them to the array
				$value[ trim( $key) ] = trim( $value );
			}

			unset( $old );
		}

		return $value;
	}

	/**
	 * Simple plugin examples and link to documentation. Called by EE.
	 * @return string
	 */
	public function usage()
	{
		ob_start();
?>
Single Tag
{exp:ce_img:single src="{your_custom_field}" max="500"}

Tag Pair
{exp:ce_img:pair src="{your_custom_field}" max="500"}
<img src="{made}" alt="" width="{width}" height="{height}" />
{/exp:ce_img:pair}

Bulk Tag
{exp:ce_img:bulk filter="sepia" width="100"}
<p><img src="/images/example/cow_square.png" alt="" /></p>
<img src="{filedir_1}cow_square.jpg" alt="" />
{/exp:ce_img:bulk}

View the full documentation at http://www.causingeffect.com/software/ee/ce_img for information on the available parameters, filters, and variables.
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	} /* End of usage() function */

} /* End of class */
/* End of file pi.ce_img.php */
/* Location: system/expressionengine/third_party/ce_img/pi.ce_img.php */