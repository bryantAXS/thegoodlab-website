<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

/**
 * CE Image: Powerful image manipulation made easy.
 * Last Updated: 19 June 2012
 *
 * License:
 * CE Image is licensed under the Commercial License Agreement found at http://www.causingeffect.com/software/expressionengine/ce-image/license-agreement
 * Here are a couple of specific points from the license to note again:
 *	 - One license grants the right to perform one installation of CE Image. Each additional installation of CE Image requires an additional purchased license.
 * 	 - You may not reproduce, distribute, or transfer CE Image, or portions thereof, to any third party.
 * 	 - You may not sell, rent, lease, assign, or sublet CE Image or portions thereof.
 * 	 - You may not grant rights to any other person.
 * 	 - You may not use CE Image in violation of any United States or international law or regulation.
 * 	 - The only exceptions to the above four (4) points are any methods clearly designated as having an MIT-style license. Those portions of code specifically designated as having an MIT-style license, and only those portions, will remain bound to the terms of that license.
 *  If you have any questions about the terms of the license, or would like to report abuse of its terms, please contact software@causingeffect.com.
 *
 * @package CE Image
 * @author Causing Effect, Aaron Waldon
 * @link http://www.causingeffect.com
 * @copyright 2012
 * @version 1.6.5
 * @license http://www.causingeffect.com/software/expressionengine/ce-image/license-agreement Causing Effect Commercial License Agreement
 */

class Ce_image
{
	private $valid_filters = array(
		//native IMG_FILTER_ filters
		'brightness' => 'imagefilter',
		'colorize' => 'imagefilter',
		'contrast' => 'imagefilter',
		'edgedetect' => 'imagefilter',
		'emboss' => 'imagefilter',
		'grayscale' => 'imagefilter',
		'mean_removal' => 'imagefilter',
		'negate' => 'imagefilter',
		'pixelate' => 'imagefilter',
		'smooth' => 'imagefilter',

		//custom filters
		'_pixelate' => array( 'self', 'pixelate' ),
		'gaussian_blur' => array( 'self', 'gaussian_blur' ),
		'selective_blur' => array( 'self', 'selective_blur' ),
		'auto_sharpen' => array( 'self', 'auto_sharpen' ),
		'cemboss' => array( 'self', 'emboss_color' ),
		'dot' => array( 'self', 'dot' ),
		'noise' => array( 'self', 'noise' ),
		'opacity' => array( 'self', 'opacity' ),
		'replace_colors' => array( 'self', 'replace_colors' ),
		'scatter' => array( 'self', 'scatter' ),
		'sepia' => array( 'self', 'sepia' ),
		'sharpen' => array( 'self', 'unsharp_mask' ),
		'sobel_edgify' => array( 'self', 'prep_edgify' ) //calls class method before calling filter
	);


	private $factory_defaults = array(
		'allow_overwrite_original' => false,
		'allow_scale_larger' => false,
		'auto_cache' => false,
		'base' => '',
		'bg_color' => false,
		'bg_color_default' => 'ffffff',
		'border' => false,
		'cache_dir' => '/images/made/',
		'crop' => false,
		'current_domain' => '',
		'dir_permissions' => 0775,
		'disable_xss_check' => false,
		'fallback_src' => '',
		'filename' => '',
		'filename_prefix' => '',
		'filename_suffix' => '',
		'filters' => array(),
		'flip' => false,
		'force_remote' => false,
		'hash_filename' => false,
		'height' => 0,
		'hide_relative_path' => false,
		'image_permissions' => 0644,
		'made_regex' => '',
		'max_height' => 0,
		'max_width' => 0,
		'memory_limit' => 64,
		'min_height' => 0,
		'min_width' => 0,
		'overwrite_cache' => false,
		'quality' => 100,
		'reflection' => false,
		'remote_cache_time' => 1440,
		'remote_dir' => '/images/remote/',
		'rotate' => 0,
		'rounded_corners' => false,
		'save_type' => false,
		'src_regex' => '',
		'text' => false,
		'unique' => 'filename',
		'watermark' => false,
		'width' => 0
	);

	//---------- don't change anything below here ----------
	private $is_open = false; //whether or not an image is actually open
	private $is_open_ready = false; //whether or not the image is ready to be opened
	private $is_data_ready = false; //whether or not the image data is ready
	private $image_data = array( 'width' => '', 'height' => '', 'type' => '', 'ext' => '' );
	private $image_data_orig = array( 'width' => '', 'height' => '', 'type' => '', 'ext' => '', 'src' => '' );
	private $image_types =  array( 'png', 'gif', 'jpg', 'jpeg' );
	private $is_remote = false;
	private $remote_src = '';
	private $x_offset = 0;
	private $y_offset = 0;
	private $src_x_offset = 0;
	private $src_y_offset = 0;
	private $width_desired = 0;
	private $height_desired = 0;
	private $x_options = array('left', 'right', 'center');
	private $y_options = array('top', 'bottom', 'center');
	private $same_as_source = false;
	private $debug_messages = array();
	private $is_transparent = false;
	private $defaults = array();
	//watermark
	private $filetime_wm = 0;
	private $wm_image_data = array( 'width' => '', 'height' => '', 'type' => '', 'src' => '' );
	private $wm_array;
	//rounded corners
	private $corner_options = array('all', 'tl', 'tr', 'bl', 'br');
	private $corners = false;
	private $corners_array;
	//reflection
	private $reflection = false;
	private $reflection_array = array();
	//flag if actual image dimensions needs to be checked
	private $check_size = false;
	//flip
	private $flip_options = array('h', 'v');
	//disable XSS check
	private $disable_xss_check = false;
	//For dev testing with versions of PHP before 5.2.0, windows has to swap / with \
	private $windows_dev = false;
	private $final_action = 'none';


	/**
	 * Initiates the class and optionally accepts an array of default settings. Also determines whether or not CodeIgniter is installed and available to use.
	 *
	 * @param array $default_settings The default settings.
	 */
	function __construct( $default_settings = array() )
	{
		//set the factory defaults
		$this->defaults = $this->factory_defaults;

		//merge the params with the defaults
		$this->set_default_settings( $default_settings );

		$this->EE =& get_instance();
	}

// ---------------------------------------------------------------------------- SETTINGS ----------------------------------------------------------------------------
	/**
	 * Accepts settings to integrate with the default settings.
	 *
	 * @param array $default_settings The default settings that will override the previously set default settings.
	 * @return void
	 */
	public function set_default_settings( $default_settings = array() )
	{
		$defaults = $this->defaults;

		//override the defaults
		if ( count( $default_settings ) > 0 )
		{
			foreach ( $default_settings as $key => $value )
			{
				if ( ! in_array( $key, $defaults ) )
				{
					unset( $default_settings[$key] );
				}
			}
			$defaults = array_merge( $defaults, $default_settings );
		}

		//reset the defaults array
		$this->defaults = $defaults;

		unset( $defaults );

		//now that our default array is created, apply them as class properties
		$this->reset_to_default_settings();
	}


	/**
	 * Accepts settings to temporarily override the default settings. The settings will not be persistent when subsequent images are opened/made.
	 *
	 * @param array $temp_settings The temporary settings that will override any previously set default settings.
	 * @return void
	 */
	private function set_temp_settings( $temp_settings = array() )
	{
		//reset class properties
		$this->check_size = false;
		$this->disable_xss_check = false;
		$this->filetime_wm = 0;
		$this->height_desired = 0;
		$this->is_remote = false;
		$this->remote_src = '';
		$this->src_x_offset = 0;
		$this->src_y_offset = 0;
		$this->width_desired = 0;
		$this->wm_array = array();
		$this->x_offset = 0;
		$this->y_offset = 0;
		$this->same_as_source = false;

		//reset the defaults
		$this->reset_to_default_settings();

		//set the temp settings
		if ( count( $temp_settings ) > 0 )
		{
			foreach ($temp_settings as $key => $val)
			{
				if ( in_array( $key, $this->factory_defaults ) )
				{
					$this->$key = $val;
				}
			}
		}
	}


	/**
	 * Resets the class properties to match the default array
	 *
	 * @return void
	 */
	private function reset_to_default_settings()
	{
		$defaults = $this->defaults;

		//set the class properties to the defaults array
		if ( count( $defaults ) > 0 )
		{
			foreach ($defaults as $key => $val)
			{
				$this->$key = $val;
			}
		}
	}


	/**
	 * Resets the settings back to the 'factory defaults'
	 *
	 * @return void
	 */
	public function reset_to_factory_settings()
	{
		$this->defaults = $this->factory_defaults;

		$this->reset_to_default_settings();
	}


	/**
	 * Retrieves all of the current settings.
	 *
	 * @return array
	 */
	private function get_current_settings()
	{
		$settings = array();
		foreach ( $this->factory_defaults as $name => $setting )
		{
			$settings[$name] = $this->$name;
		}

		return $settings;
	}

	/**
	 * Removes double slashes, except when they are preceded by ':', so that 'http://', etc are preserved.
	 *
	 * @param string $str The string from which to remove the double slashes.
	 * @return string The string with double slashes removed.
	 */
	private function remove_duplicate_slashes( $str )
	{
		return preg_replace( '#(?<!:)//+#', '/', $str );
	}


// ---------------------------------------------------------------------------- OPEN ----------------------------------------------------------------------------

	/**
	 * Opens an image. Call this function if you would like to get data from an image without manipulating it. This function is also called by the 'make' method.
	 *
	 * @param string|resource $src The image source. Can be a relative path to document root, a full server (absolute) path, a URL (remote or local), an HTML snippet, or a GD2 image resource.
	 * @param array $temp_settings The settings that will temporary override the previously set default settings, for this image only.
	 * @return bool Will return true on success and false on failure.
	 */
	public function open( $src = '', $temp_settings = array() )
	{
		$is_string = ! is_resource( $src );

		$this->set_temp_settings( $temp_settings );

		$this->is_data_ready = false;
		$this->is_open_ready = false;

		$this->is_open = false;

		//base path
		$base = ( $this->base != '') ? $this->base : $_SERVER['DOCUMENT_ROOT'];
		$base = str_replace('\\', '/', $this->EE->security->xss_clean( $base ) );
		$this->base = $this->remove_duplicate_slashes( $base . '/' );
		$this->debug_messages[] = "Base path: '$this->base'";

		if ( $is_string  ) //string src
		{
			$this->src = $src;

			//check to make sure the source is not blank
			if ( $this->src == '' && $this->fallback_src == '' )
			{
				$this->debug_messages[] = 'Source and fallback source cannot both be blank.';
				return false;
			}

			$this->src = str_replace('\\', '/', $this->src);

			//get the source from the first image tag, if one exists
			if ( preg_match('#<img.+src="(.*)".*>#Uims', $this->src, $matches) )
			{
				$this->src = $matches[1];
			}
			$this->src = $this->EE->security->xss_clean( $this->src );
			$this->fallback_src = str_replace('\\', '/', $this->fallback_src);
			$this->fallback_src = $this->EE->security->xss_clean( $this->fallback_src );

			//source and fallback source
			$this->debug_messages[] = "Source image: '$this->src', Fallback image: '$this->fallback_src'";

			if ( $this->src == '' || $this->check_src_image() == false )
			{
				//source is not readable or does not exist, write debug message and check fallback source
				$this->debug_messages[] = "Source image is not readable or does not exist: '$this->src'.";

				$this->src = $this->fallback_src;

				if ( $this->src == '' || $this->check_src_image() == false )
				{
					//fallback source is not readable, not much more to do...
					$this->debug_messages[] = "Fallback source image is not readable or does not exist: '$this->src'.";
					return false;
				}
			}

			//get the path info
			$info = pathinfo( $this->src );

			//get the relative path
			if ( $this->is_above_root ) //normal
			{
				$this->relative = ( $this->hide_relative_path ) ? '' : preg_replace( '@' . preg_quote( $this->base, '@' ) . '@', '', '/' . $info['dirname'] . '/', 1 );
			}
			else //we don't want to reveal server info below web root
			{
				$this->relative = '';
				if ( ! $this->hide_relative_path && ! empty( $info['dirname'] ) )
				{
					$this->relative = substr( md5( $info['dirname'] ), 0, 16 ) . '/';
				}
			}
		}
		else //resource
		{
			$this->src = '';
			$this->handle = $src;
			$this->is_above_root = true;
			$this->relative = '';
		}

		//auto cache directory
		if ( $this->auto_cache != '' && $this->is_above_root )
		{
			$this->auto_cache = $this->EE->security->xss_clean( $this->remove_duplicate_slashes( '/' . $this->auto_cache . '/' ) );
			$this->cache_full = $this->remove_duplicate_slashes( $this->base . $this->relative . $this->auto_cache );
		}
		else
		{
			//cache paths
			$this->cache_dir = $this->remove_duplicate_slashes( '/' . $this->cache_dir . '/' );
			$this->cache_full = $this->remove_duplicate_slashes( $this->base . $this->cache_dir . $this->relative );
		}

		if ( $this->windows_dev )
		{
			$this->cache_full = str_replace( '/', '\\', $this->cache_full);
		}

		if ( $is_string  ) //string src
		{
			//get the image data
			$status = $this->get_image_data();
			if ( $status === 'try again' )
			{
				$this->open( $this->fallback_src );
				return false;
			}
			else if ($status === false)
			{
				return false;
			}
			//original extension (use this if possible to preserve casing)
			if ( isset( $info['extension'] ) )
			{
				$this->image_data_orig['ext'] = $info['extension'];
			}
			else
			{
				$this->image_data_orig['ext'] = '';
			}

			//get original filename
			if ( isset( $info['filename'] ) ) //>= PHP 5.2.0
			{
				$filename = $info['filename'];
			}
			else //< PHP 5.2.0
			{
				$filename = substr_replace( $info['basename'], '', strrpos( $info['basename'], '.' . $this->image_data_orig['ext'] ), strlen( '.' . $this->image_data_orig['ext'] ) );
			}
			//store original filename
			$this->filename_orig = $this->EE->security->sanitize_filename( $filename );
			$this->filename_final = $this->filename_orig;
		}
		else //resource
		{
			$filename = 'temp';
			$width = round( imagesx( $this->handle ) );
			$height = round( imagesy( $this->handle ) );
			$this->image_data = array( 'width' => $width, 'height' => $height, 'type' => '', 'ext' => '' );
			$this->image_data_orig = array( 'width' => $width, 'height' => $height, 'type' => '', 'ext' => '', 'src' => '' );
			$this->filename_orig = '';
			$this->filename_final = '';
			$this->is_open = true;
		}

		//---------- filename ----------
		$temp = trim( $this->filename );
		$filename = ( $temp === '' ) ? $filename : $temp;
		$this->filename = $this->EE->security->sanitize_filename( $filename );

		$this->is_open_ready = true;
		$this->is_data_ready = true;

		return true;
	}

	/**
	 * Retrieves the image data for the current src image.
	 *
	 * @return bool Will return true on success and false on failure.
	 */
	private function get_image_data()
	{
		//get the image info
		$data = @getimagesize( $this->src );

		$success = true;

		if ( ! $data )
		{
			$success = false;
		}
		else
		{
			$ext = '';
			switch ( $data[2] )
			{
				case IMAGETYPE_GIF:
					$ext = 'gif';
					break;
				case IMAGETYPE_PNG:
					$ext = 'png';
					break;
				case IMAGETYPE_JPEG:
					$ext = 'jpg';
					break;
				default:
					$success = false;
			}
		}

		if ( ! $success )
		{
			$this->debug_messages[] = 'Unknown image format.';
			//there is a small chance the image was an error page, try and use the fallback source
			if ( $this->src != $this->fallback_src && $this->fallback_src != '' ) //retry with the fallback as the source
			{
				$this->debug_messages[] = 'Reprocessing using the fallback image.';
				return 'try again'; //try again
			}
			return false;
		}

		//$this->image_data_orig['type'] = $data[2];
		$this->image_data = array( 'width' => $data[0], 'height' => $data[1], 'ext' => $ext );
		$this->image_data_orig = array( 'width' => $data[0], 'height' => $data[1], 'ext' => $ext, 'type' => $data[2], 'src' => $this->src );
		return true;
	}

	/**
	 * Opens the actual image resource.
	 * @param bool $original
	 * @return bool Returns true on success and false on failure.
	 */
	private function open_real( $original = false )
	{
		if ( ! $this->is_open_ready )
		{
			//return false;
			$this->open( $this->src );
		}

		if ( $this->is_open )
		{
			return true;
		}

		//set the memory limit
		$this->set_memory_limit();

		if ( $this->src != '' ) //do not try to open if the src is '', because it's a resource
		{
			$which = ( $original ) ? 'image_data_orig' : 'image_data';

			if ( isset( $this->{$which}['type'] ) && $this->{$which}['type'] != '' )
			{
				switch ( $this->{$which}['type'] )
				{
					case IMAGETYPE_GIF:
						$type = 'gif';
						break;
					case IMAGETYPE_PNG:
						$type = 'png';
						break;
					case IMAGETYPE_JPEG:
						$type = 'jpg';
						break;
					default:
						$type = '';
				}
			}
			else if (in_array(strtolower($this->{$which}['ext']), $this->image_types))
			{
				$type = strtolower($this->{$which}['ext']);
			}

			//open the file and create main image handle
			switch ( $type )
			{
				case 'gif':
					$success = @imagecreatefromgif( $this->src );
					break;
				case 'png':
					$success = @imagecreatefrompng( $this->src );
					break;
				case 'jpg':
				case 'jpeg':
					$success = @imagecreatefromjpeg( $this->src );
					break;
				default:
					$this->debug_messages[] = 'Unknown image format for image creation.';
					$success = false;
			}

			if ( $success === false )
			{
				$this->debug_messages[] = "A problem occurred trying to open the image '$this->src'.";
				return false;
			}
			else
			{
				$this->handle = $success;
			}

		}

		$this->is_open = true;
		$this->debug_messages[] = "Image opened '$this->src'.";
		return true;
	}

	/**
	 * Attempts to determine the server path from the image src parameter. Will also download a remote image if applicable and return its server path.
	 *
	 * @return bool Returns true if the image is found successfully and false otherwise.
	 */
	private function check_src_image()
	{
		//perform any advanced preg_replaces if they exist
		$replacements = $this->src_regex;
		if ( $replacements != false && is_array( $replacements ) && count( $replacements ) > 0 )
		{
			$find = array();
			$replace = array();
			foreach( $replacements as $f => $r )
			{
				$find[] = '@' . $f . '@';
				$replace[] = $r;
			}

		 	$this->src = preg_replace( $find, $replace, $this->src );

		 	//show the new source
			$this->debug_messages[] = "Regexed source: '$this->src'";
		}

		//check if a URL
		if ( substr( $this->src, 0, 4 ) == 'http' )
		{
			$info = parse_url( $this->src );
			if ( $info === false )
			{
				$this->debug_messages[] = "The url '{$this->src}' could not be parsed. Is your URL malformed?";
				return false;
			}

			$current_domain_override = $this->current_domain;
			if ( $current_domain_override == '' ) //no domain specified, try to figure it out
			{
				$current_domain_override = $this->EE->security->xss_clean( preg_replace( '@' . preg_quote( 'www.', '@' ) . '@', '', $_SERVER['SERVER_NAME'], 1 ) );
			}
			else //use the current domain value as set in plugin or config
			{
				$temp = parse_url( $current_domain_override );
				if ( $temp === false )
				{
					$this->debug_messages[] = "The url '{$current_domain_override}' could not be parsed. Is it malformed?";
					return false;
				}

				if ( ! isset( $temp['host'] ) ) //verify that the host key is set
				{
					$this->debug_messages[] = "The value specified for the 'current_domain' is invalid.";

					return false;
				}

				$current_domain_override = preg_replace( '@' . preg_quote( 'www.', '@') . '@', '', $temp['host'], 1 );
			}

			$path = isset( $info['path'] ) ? $info['path'] : '';

			if ( $this->force_remote || preg_replace( '@' . preg_quote( 'www.', '@') . '@', '', $info['host'], 1 ) != $current_domain_override ) //remote
			{
				$this->is_remote = true;
				$this->remote_src = $this->src;

				//get remote_dir param
				$this->remote_dir = $this->remove_duplicate_slashes( '/' . $this->remote_dir . '/' );

				$temp = $this->base . '/' . $this->remote_dir . $info['scheme'] . '_' . $info['host'] . $path;

				//create and sanitize the file path
				$temp = $this->EE->security->xss_clean( $this->remove_duplicate_slashes( $temp ) );

				$path_info =  pathinfo( $temp );

				//make sure the query string gets worked in if applicable
				if ( isset( $info['query'] ) )
				{
					$path_info['dirname'] = $this->remove_duplicate_slashes( $path_info['dirname'] . '/' . md5( $info['query'] ) );
				}

				$remote_cache = $path_info['dirname'];
				$remote_filename = $this->EE->security->sanitize_filename( $path_info['basename'] );

				$file = $remote_cache . '/' . $remote_filename;
				$file_exists = @file_exists( $file );

				if ( $file_exists )
				{
					//determine the course of action for refreshing the remote image depending on the settings
					$file_m_time = @filemtime( $file ); //filetime of cached image

					if ( $this->remote_cache_time != -1 ) //don't worry about re-downloading the remote image if remote_cache_time is -1
					{
						//check the timestamp of the image
						$remote_timestamp = 0;

						//see if the user has curl so we can check the timestamp
						if ( $this->remote_cache_time == 0 && function_exists( 'curl_init' ) ) //check if the remote image is newer than the downloaded version if remote_cache_time is 0
						{
							$this->debug_messages[] = "Checking whether the remote image '{$this->remote_src}' is newer than the cached version via cURL.";
							$curl = curl_init( $this->remote_src );
							curl_setopt($curl, CURLOPT_NOBODY, true); //only fetch headers
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //no output
							curl_setopt($curl, CURLOPT_FILETIME, true); //attempt to get date modified
							//follow location will throw an error if safe mode or an open basedir restrictions is enabled
							if ( ! ini_get('open_basedir') && ! ini_get('safe_mode') )
							{
								curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
							}

							if ( curl_exec($curl) !== false)
							{
								$remote_timestamp = curl_getinfo($curl, CURLINFO_FILETIME);
							}
						}

						$delete_cache = false;
						if ( $remote_timestamp != 0 && $remote_timestamp > $file_m_time ) //the remote image is newer
						{
							$this->debug_messages[] = "The remote image '{$this->remote_src}' is newer than the cached version.";
							$delete_cache = true;
						}
						else if (time() - $file_m_time > $this->remote_cache_time * 60) //the remote file has expired
						{
							$this->debug_messages[] = "The local cache of the remote image has expired.";
							$delete_cache = true;
						}

						//delete the current remote cache file
						if ( $delete_cache )
						{
							@unlink( $file );
							//recheck if the file exists
							$file_exists = @file_exists( $file );
						}
					}
				}

				//if the image is not on the server, download it
				if ( ! $file_exists )
				{
					if ( $this->windows_dev )
					{
						$remote_cache = str_replace('/', '\\', $remote_cache);
					}
					if ( $this->save_remote_image( $remote_cache, $remote_filename ) === false )
					{
						return false;
					}
				}

				//change the source from the remote image to the downloaded image
				$this->src = $this->remove_duplicate_slashes( $remote_cache . '/' . $remote_filename );
			}
			else //local
			{
				$this->src = $path;
			}
		}

		//---------- figure out source path ----------
		$this->is_above_root = true;
		$temp = $this->remove_duplicate_slashes( $this->base . $this->src );

		if ( @is_readable( $temp ) === true ) //relative path
		{
			//create full path for source
			$this->src = $this->remove_duplicate_slashes( realpath($temp) );
			$this->src = str_replace('\\', '/', $this->src);
		}
		//see if path below web root
		if ( strpos($this->src, $this->base) === false )
		{
			$this->is_above_root = false;
		}
		//make sure the image is readable and is not a directory
		if ( @is_dir( $this->src ) || @is_readable( $this->src ) !== true )
		{
			return false;
		}

		return true;
	}

	/**
	 * Saves a remote image.
	 *
	 * @param string $remote_cache The server path to the remote images' cache folder.
	 * @param string $remote_filename The URL of the remote image.
	 * @return bool Returns true if the image is saved successfully and false otherwise.
	 */
	private function save_remote_image( $remote_cache, $remote_filename )
	{
		$this->set_memory_limit();

		@ini_set('default_socket_timeout', 30);
		@ini_set('allow_url_fopen', true);
		@ini_set('user_agent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:5.0) Gecko/20100101');

        $url = str_replace( ' ', '%20', $this->src );

		if ( function_exists( 'curl_init' ) ) //try to get the image using cURL
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url ); //set the URL
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //no output
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, false); //no timeout
			//follow location will throw an error if safe mode or an open basedir restrictions is enabled
			if ( ! ini_get('open_basedir') && ! ini_get('safe_mode') )
			{
				curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
			}

			$remote_image = curl_exec($curl);
			if ( empty( $remote_image ) ) //cURL failed
			{
				$this->debug_messages[] = 'Could not get remote image using cURL.';
				return false;
			}
		}
		else //try to get the image using file_get_contents
		{
			$remote_image = @file_get_contents( $url );

			if ( empty( $remote_image ) ) //file_get_contents failed and cURL is not enabled, not much more we can do...
			{
				$this->debug_messages[] = 'Could not get remote image using file_get_contents.';
				return false;
			}
		}

		if ( $this->disable_xss_check || $this->EE->security->xss_clean( $remote_image, true ) === true )
		{
			//check if the directory exists
			if ( ! @is_dir( $remote_cache ) )
			{
				//try to make the directory
				if ( ! @mkdir( $remote_cache . '/', $this->dir_permissions, true ) )
				{
					$this->debug_messages[] = "Could not create the cache directory '$remote_cache/' for remote images.";
					return false;
				}
			}

			//ensure the directory is writable
			if ( ! @is_writable( $remote_cache ) )
			{
				$this->debug_messages[] = "Cache directory for remote images '$remote_cache' is not writable.";
				return false;
			}

			if ( file_put_contents( $remote_cache . '/' . $remote_filename, $remote_image) === false )
			{
				$this->debug_messages[] = "Could not write the remote image '{$remote_cache}/{$remote_filename}'.";
				return false;
			}
		}
		else	//file failed the XSS test
		{
			$this->debug_messages[] = 'Remote image failed XSS test.';
			return false;
		}

		//the image should have been saved successfully, let's attempt to update permissions
		if ( ! @chmod( $remote_cache . '/' . $remote_filename, $this->image_permissions ) )
		{
			$this->debug_messages[] = "File permissions for '{$remote_cache}/{$remote_filename}' could not be changed to '$this->image_permissions'.";
		}

		$remote_image = null;

		$this->debug_messages[] = "The remote image '{$url}' was downloaded successfully.";
		return true;
	}
// ---------------------------------------------------------------------------- RETURN FUNCTIONS ----------------------------------------------------------------------------
	/**
	 * The currently open image resource.
	 *
	 * @return resource|bool The resource on success and false on failure.
	 */
	public function get_resource()
	{
		if ( $this->open_real() == false )
		{
			$this->debug_messages[] = 'A resource could not be returned, because an image is not open.';
			return false;
		}

		if ( isset( $this->handle ) && is_resource($this->handle) )
		{
			return $this->handle;
		}
		else
		{
			$this->debug_messages[] = 'A resource could not be returned, because a valid resource was not found.';
			return false;
		}
	}

	/**
	 * Returns a clone of the currently open image resource.
	 *
	 * @return resource|bool The resource clone on success and false on failure.
	 */
	public function clone_resource()
	{
		if ( $this->open_real() == false )
		{
			$this->debug_messages[] = 'A resource could not be cloned, because an image is not open.';
			return false;
		}

		if ( isset( $this->handle ) && is_resource( $this->handle ) )
		{
			$width = round( imagesx( $this->handle ) );
			$height = round( imagesy( $this->handle ) );
			$clone = imagecreatetruecolor( $width, $height );
			if ( @imagecopy( $clone, $this->handle, 0, 0, 0, 0, $width, $height ) )
			{
				return $clone;
			}
			else
			{
				$this->debug_messages[] = 'There was an error cloning the image.';
				return false;
			}
		}
		else
		{
			$this->debug_messages[] = 'There image could not be cloned, because a valid resource was not found.';
			return false;
		}
	}

	/**
	 * Returns the type of action that was taken. Can be one of the following:
	 * none - No action was taken.
	 * saved - The image was successfully saved.
	 * cached - A cached image was found and used.
	 *
	 * @return string
	 */
	public function get_final_action()
	{
		return $this->final_action;
	}

	/**
	 * The height of the current image.
	 *
	 * @return int The current image's height.
	 */
	public function get_height()
	{
		return $this->image_data['height'];
	}

	/**
	 * The height of the original image.
	 *
	 * @return int The original image's height
	 */
	public function get_original_height()
	{
		return $this->image_data_orig['height'];
	}

	/**
	 * The width of the current image.
	 *
	 * @return int The current image's width.
	 */
	public function get_width()
	{
		return $this->image_data['width'];
	}

	/**
	 * The width of the original image.
	 *
	 * @return int The original image's width.
	 */
	public function get_original_width()
	{
		return $this->image_data_orig['width'];
	}

	/**
	 * The extension of the current image.
	 *
	 * @return string The current image's extension.
	 */
	public function get_extension()
	{
		return ( $this->get_server_path() != '' ) ? $this->image_data['ext'] : '';
	}

	/**
	 * The extension of the original image.
	 *
	 * @return string The original image's extension.
	 */
	public function get_original_extension()
	{
		return $this->image_data_orig['ext'];
	}

	/**
	 * The relative path (from document root) of the current image.
	 *
	 * @return string The current image's relative path.
	 */
	public function get_relative_path()
	{
		$path = preg_replace( '@' . preg_quote( $this->base, '@' ) . '@', '', '/' . $this->src, 1 );

		//perform any advanced preg_replaces if they exist
		$replacements = $this->made_regex;
		if ( $replacements != false && is_array( $replacements ) && count( $replacements ) > 0 )
		{
			$find = array();
			$replace = array();
			foreach( $replacements as $f => $r )
			{
				$find[] = '#' . $f . '#';
				$replace[] = $r;
			}
		 	$path = preg_replace( $find, $replace, $path );

		 	//show the new made path
			$this->debug_messages[] = "Regexed made: '$path'.";
		}

		return $path;
	}

	/**
	 * The relative path (from document root) of the original image.
	 *
	 * @return string The original image's relative path.
	 */
	public function get_original_relative_path()
	{
		$path = preg_replace( '@' . preg_quote( $this->base, '@' ) . '@', '', '/' . $this->image_data_orig['src'], 1 );

		return $path;
	}

	/**
	 * The file type of the current image. Will return 'jpg', 'png', or 'gif'.
	 *
	 * @return string
	 */
	public function get_type()
	{
		$type = strtolower( $this->image_data['ext'] );

		if ( $type == 'jpeg' )
		{
			$type = 'jpg';
		}

		return $type;
	}

	/**
	 * The file type of the original image. Will return 'jpg', 'png', or 'gif'.
	 *
	 * @return string
	 */
	public function get_type_orig()
	{
		switch ( $this->image_data_orig['type'] )
		{
			case 1;
				return 'gif';
				break;
			case 2:
				return 'jpg';
				break;
			case 3:
				return 'png';
				break;
		}

		return 'unknown';
	}

	/**
	 * The file name of the current image, without the file extension.
	 *
	 * @return string The current image's file name.
	 */
	public function get_filename()
	{
		return ( $this->get_server_path() != '' ) ? $this->filename_final : '';
	}

	/**
	 * The file name of the original image, without the file extension.
	 *
	 * @return string The original image's file name.
	 */
	public function get_original_filename()
	{
		return $this->filename_orig;
	}

	/**
	 * The server path of the current image.
	 *
	 * @return string The current image's server path.
	 */
	public function get_server_path()
	{
		return $this->src;
	}

	/**
	 * The server path of the original image.
	 *
	 * @return string The original image's server path.
	 */
	public function get_original_server_path()
	{
		return $this->image_data_orig['src'];
	}

	/**
	 * The array of debug messages.
	 *
	 * @return array The debug messages.
	 */
	public function get_debug_messages()
	{
		return $this->debug_messages;
	}

	/**
	 * Returns the file size of the image.
	 *
	 * @param bool $bytes_only If the file size should be returned in bytes, or converted to a human readable format (default).
	 * @return int|string|bool Returns int if $bytes_only is true, string if $bytes_only is false, and false if an error.
	 */
	public function get_filesize( $bytes_only = false )
	{
		if ( $this->src != '' && ( $this->is_data_ready || $this->is_open_ready ) )
		{
			$filesize = @filesize( $this->src );
			if ( $filesize !== false)
			{
				return ( $bytes_only ) ? $filesize : Ce_image_tools::convert( $filesize );
			}
			else
			{
				$this->debug_messages[] = "The file size could not be found for '$this->src'.";
				return false;
			}
		}
		else
		{
			$this->debug_messages[] = 'The file size could not be retrieved, because an image is not open.';
			return false;
		}
	}

	/**
	 * Returns the file size of the original image.
	 *
	 * @param bool $bytes_only If the file size should be returned in bytes, or converted to a human readable format (default).
	 * @return int|string|bool Returns int if $bytes_only is true, string if $bytes_only is false, and false if an error.
	 */
	public function get_original_filesize( $bytes_only = false )
	{
		if ( $this->get_original_server_path() != '' && ( $this->is_data_ready || $this->is_open_ready ) )
		{
			$filesize = @filesize( $this->image_data_orig['src'] );
			if ( $filesize !== false)
			{
				return ( $bytes_only ) ? $filesize : Ce_image_tools::convert( $filesize );
			}
			else
			{
				$this->debug_messages[] = "The original file size could not be found for '{$this->image_data_orig['src']}'.";
				return false;
			}
		}
		else
		{
			$this->debug_messages[] = 'The original file size could not be retrieved, because an image is not open.';
			return false;
		}
	}

	/**
	 * Creates a tag for the current image. It will automatically add in the images width, height, and alt tag if they are not passed in as attributes.
	 *
	 * @param array $attributes The attributes to be used for the image.
	 * @param bool $closing_slash Whether or not to include a closing slash (defaults to true).
	 * @return string The HTML image tag.
	 */
	public function create_tag( $attributes = array(), $closing_slash = true )
	{
		if ( $this->src == '' OR ! $this->is_data_ready )
		{
			$this->debug_messages[] = 'The image tag could not be created, because an image is not open.';
			return false;
		}

		if ( ! array_key_exists( 'src', $attributes ) )
		{
			$attributes['src'] = $this->get_relative_path();
		}
		if ( ! array_key_exists( 'width', $attributes ) )
		{
			$attributes['width'] = $this->get_width();
		}
		if ( ! array_key_exists( 'height', $attributes ) )
		{
			$attributes['height'] = $this->get_height();
		}
		if ( ! array_key_exists( 'alt', $attributes ) )
		{
			$attributes['alt'] = '';
		}

		$attr = '';
		foreach( $attributes as $attribute => $value )
		{
			$value = str_replace('"', '&quot;', $value );
			$attr .= $attribute . '="' . $value . '" ';
		}

		$closing_slash = ($closing_slash) ? '/' : '';

		return '<img ' . $attr . $closing_slash . '>';
	}

	/**
	 * Creates a tag for the original image. It will automatically add in the images width, height, and alt tag if they are not passed in as attributes.
	 *
	 * @param array $attributes The attributes to be used for the image.
	 * @param bool $closing_slash Whether or not to include a closing slash (defaults to true).
	 * @return string The HTML image tag.
	 */
	public function create_original_tag( $attributes = array(), $closing_slash = true )
	{
		if ( $this->get_original_server_path() == '' OR ! $this->is_data_ready )
		{
			$this->debug_messages[] = 'The image tag could not be created, because an image is not open.';
			return false;
		}

		if ( ! array_key_exists( 'src', $attributes ) )
		{
			$attributes['src'] = $this->get_original_relative_path();
		}
		if ( ! array_key_exists( 'width', $attributes ) )
		{
			$attributes['width'] = $this->get_original_width();
		}
		if ( ! array_key_exists( 'height', $attributes ) )
		{
			$attributes['height'] = $this->get_original_height();
		}
		if ( ! array_key_exists( 'alt', $attributes ) )
		{
			$attributes['alt'] = '';
		}

		$attr = '';
		foreach( $attributes as $attribute => $value )
		{
			$value = str_replace('"', '&quot;', $value );
			$attr .= $attribute . '="' . $value . '" ';
		}

		$closing_slash = ($closing_slash) ? '/' : '';

		return '<img ' . $attr . $closing_slash . '>';
	}

	/**
	 * Creates ASCII art for an image. Each character represents one pixel from the image.
	 *
	 * @param bool $use_colors Whether or not to colorize the characters.
	 * @param array $ascii_characters The array of characters to be used for the ASCII art.
	 * @param bool $repeat Whether or not the characters should repeat in consecutive order (true) or be placed depending on the darkness of the pixel (false).
	 * @param bool $space_for_trans If the $repeat parameter is set to true, you can set this parameter to determine whether or not a space should be used for transparent pixels.
	 * @return string|bool The HTML for the ASCII art on success, false on failure.
	 */
	public function get_ascii_art( $use_colors = true, $ascii_characters = array('#', '@', '%', '=', '+', '*', ':', '-', '.', '&nbsp;'), $repeat = false, $space_for_trans = false )
	{
		//check the settings
		if ( ! is_bool( $use_colors) )
		{
			$use_colors = true;
		}

		//repeat characters?
		if ( ! is_bool( $repeat) )
		{
			$repeat = false;
		}

		//use space character for transparent pixels?
		if ( ! is_bool( $space_for_trans ) )
		{
			$space_for_trans = false;
		}

		if ( $this->open_real() == false )
		{
			$this->debug_messages[] = 'The ASCII art could not be generated, because an image is not open.';
			return false;
		}

		return $this->create_ascii_art( $this->handle, $use_colors, $ascii_characters, $repeat, $space_for_trans );
	}

	/**
	 * Organizes the colors used in your image by frequency of occurrence, and groups them by a threshold.
	 *
	 * @param int $quantity The maximum number of colors (color groups) to return.
	 * @param int $threshold Value from 0 (very low grouping) to 100 (very high grouping).
	 * @return array|bool On success, returns an array of results with each result being an array with the following keys: 'color', 'color_count', 'color_percent'. On failure, returns false.
	 */
	public function get_top_colors( $quantity = 5, $threshold = 33 )
	{
		if ( ! is_numeric( $quantity) )
		{
			$this->debug_messages[] = 'Invalid quantity of return colors for top colors.';
			return false;
		}
		if ( ! is_numeric( $threshold ) || $threshold < 0 || $threshold > 100 )
		{
			$this->debug_messages[] = 'Invalid threshold for top colors. Must be between 0 and 100, inclusive.';
			return false;
		}

		if ( $this->open_real() )
		{
			return $this->find_top_colors( $this->handle, $quantity, $threshold );
		}
		else
		{
			$this->debug_messages[] = 'The top colors could not be retrieved, because an image is not open.';
			return false;
		}
	}

	/**
	 * Gets the average color for the image.
	 *
	 * @return string The hexadecimal color value of the average color of the generated image.
	 */
	public function get_average_color()
	{
		if ( $this->open_real() )
		{
			return $this->find_average_color( $this->handle );
		}
		else
		{
			$this->debug_messages[] = 'The average color could not be retrieved, because no image is open.';
			return false;
		}
	}

// ---------------------------------------------------------------------------- CLOSE ----------------------------------------------------------------------------
	/**
	 * Closes the image.
	 *
	 * @return void
	 */
	public function close()
	{
		if ( $this->is_open )
		{
			$this->close_real();
		}
		$this->src = '';
		$this->fallback_src = '';
		$this->image_data = array( 'width' => '', 'height' => '', 'ext' => '');
		$this->image_data_orig = array( 'width' => '', 'height' => '', 'type' => '', 'ext' => '', 'src' => '');
		$this->wm_image_data = array( 'width' => '', 'height' => '', 'type' => '', 'src' => '' );
		$this->debug_messages = array();
		$this->is_data_ready = false;
	}

	/**
	 * Closes the open image resource(s).
	 *
	 * @return void
	 */
	private function close_real()
	{
		if ( is_resource( $this->handle ) )
		{
			imagedestroy( $this->handle );
			unset( $this->handle );
		}

		$this->is_open = false;
	}

// ---------------------------------------------------------------------------- PREPARE SETTINGS ----------------------------------------------------------------------------
	/**
	 * Cleans up some of the settings to ensure they are in the correct format for the class to use.
	 *
	 * @return bool true on success, false on failure
	 */
	private function prep_settings()
	{
		//---------- get other params ----------
		//allow_scale_larger parameter
		if ( ! is_bool( $this->allow_scale_larger ) )
		{
			$this->allow_scale_larger = false;
		}

		//get bg_color param
		$this->bg_color = Ce_image_tools::hex_cleanup( $this->bg_color );

		//get bg_color_default param
		$this->bg_color_default = Ce_image_tools::hex_cleanup( $this->bg_color_default );
		if ( $this->bg_color_default == false )
		{
			$this->bg_color_default = 'ffffff';
		}

		//get filter param
		$filter_array = $this->filters;
		if ( ! is_array( $filter_array ) )
		{
			$filter_array = ( trim( $filter_array ) != '' ) ? array( $filter_array ) : array();
		}
		$filters = array();
		$count = count( $filter_array );
		if ( $count != 0 )
		{
			foreach( $filter_array as $filter )
			{
				//make filter an array if applicable
				if ( ! is_array($filter) && trim( $filter ) != '' )
				{
					$filter = array( $filter );
				}

				//make sure the filter has a name
				if ( ! isset( $filter['0'] ) || trim($filter['0']) == '' )
				{
					continue;
				}

				//make sure the filter exists
				$filter_name = trim($filter['0']);
				if ( isset( $this->valid_filters[$filter_name] ) )
				{
					//if a native image filter is being used, make sure it exists with this installation
					if ( $this->valid_filters[$filter_name] === 'imagefilter' && ! defined( 'IMG_FILTER_' . strtoupper($filter_name) ) )
					{
						continue;
					}

					//add the filter
					$filters[] = $filter;
				}
			}

		}
		unset( $filter_array );
		$this->filters = $filters;
		unset( $filters );

		//get crop param
		if ( ! is_array( $this->crop ) )
		{
			$this->crop = array( $this->crop );
		}
		$crop = $this->crop;
		$this->do_crop =  ( isset( $crop[0] ) && $crop[0] === true );

		//get border param
		$border = $this->border;
		if ( $border != false && isset( $border[0] ) && is_numeric( $border[0] ) && $border[0] > 0 )
		{
			$this->check_size = true;

			if ( isset( $border[1] ) )
			{
				$temp = Ce_image_tools::hex_cleanup( $border[1] );
				if ( $temp != '' )
				{
					$this->border[1] = $temp;
				}
				else
				{
					$this->border[1] = 'ffffff';
				}
			}
			else
			{
				$this->border[1] = 'ffffff';
			}
		}
		else
		{
			$this->border[0] = 0;
			$this->border[1] = 'ffffff';
		}

		//get rounded_corners param
		$corners = $this->rounded_corners;
		$corners_array = array();
		if ( is_array( $corners ) && count( $corners ) > 0 )
		{
			if ( ! is_array( $corners[0] ) )
			{
				$corners = array( $corners );
			}

			foreach ( $corners as $corner )
			{
				//get the position
				if (  isset( $corner[0] ) && in_array( $corner[0], $this->corner_options )  )
				{
					$corner_array = array();
					$pos = $corner[0];

					//get the radius
					$rad = 15;
					if (  isset( $corner[1] ) && is_numeric( $corner[1] ) )
					{
						$rad = $corner[1];
					}

					//get the color (will end up being: hex, or '')
					$col = '';
					if (  isset( $corner[2] ) )
					{
						$col = Ce_image_tools::hex_cleanup( $corner[2] );
					}

					if ( $pos == 'all' )
					{
						if ( $rad > 0 )
						{
							$corners_array['tl'] = array( $rad, $col );
							$corners_array['tr'] = array( $rad, $col );
							$corners_array['bl'] = array( $rad, $col );
							$corners_array['br'] = array( $rad, $col );
						}
						else
						{
							$corners_array = array();
						}
					}
					else
					{
						if ( $rad > 0 )
						{
							$corners_array[$pos] = array( $rad, $col );
						}
						else
						{
							unset( $corners_array[$pos] );
						}
					}
				}
			}
		}
		$this->corners_array = $corners_array;
		$this->corners = ( count($this->corners_array) > 0 );

		//get flip param
		if ( ! is_array( $this->flip ) && in_array( $this->flip, $this->flip_options ) )
		{
			$this->flip = array( $this->flip );
		}

		$flip = $this->flip;

		if ( is_array( $flip ) )
		{
			foreach( $flip as $i => $f )
			{
				if ( ! in_array( $f, $this->flip_options ) )
				{
					unset( $flip[$i] );
				}
			}

			//remove duplicate values
			$this->flip = array_unique( $flip );

			if ( count( $flip ) > 0 )
			{
				$this->flip = $flip;
			}
			else
			{
				$this->flip = false;
			}
		}
		else
		{
			$this->flip = false;
		}

		//get reflection param
		$ref = $this->reflection;
		$reflection_array = array();
		if ( is_array( $ref ) && count( $ref ) > 0 )
		{
			$reflection_array[0] = ( isset( $ref[0] ) && is_numeric( $ref[0] ) ) ? trim( $ref[0] ) : 0; //gap
			$reflection_array[1] = ( isset( $ref[1]) && is_numeric( $ref[1]) ) ? trim( $ref[1] ): 80; //start opacity
			$reflection_array[2] = ( isset( $ref[2]) && is_numeric( $ref[2]) ) ? trim( $ref[2] ): 0; //end opacity
			$reflection_array[3] = ( isset( $ref[3]) && is_numeric( str_replace( '%', '', $ref[3] ) ) ) ? trim( $ref[3] ) : '50%'; //ref_height
		}
		$this->reflection_array = $reflection_array;
		if ( count($this->reflection_array) > 0 )
		{
			$this->reflection = true;
			$this->check_size = true;
		}

		//get rotate param
		if ( $this->rotate != 0 && is_numeric( $this->rotate ) && $this->rotate % 360 != 0 )
		{
			$this->check_size = true;
		}
		else
		{
			$this->rotate = false;
		}

		//get overwrite_cache param
		if ( ! is_bool( $this->overwrite_cache ) )
		{
			$this->overwrite_cache = false;
		}

		//get hash_filename param
		if ( ! is_bool( $this->hash_filename ) )
		{
			$this->hash_filename = false;
		}

		//get save_type param
		$save_type = trim( $this->save_type );
		if ( $save_type == '' )
		{
			$save_type = ( $this->image_data_orig['ext'] ) ? $this->image_data_orig['ext'] : $this->get_type_orig();
		}
		if ( ! in_array( strtolower( $save_type ), $this->image_types ) )
		{
			$save_type = 'jpg';
		}
		$this->image_data['ext'] = $save_type;


		//check quality to be valid
		if ( ! is_numeric( $this->quality ) || $this->quality < 0 || $this->quality > 100 )
		{
			$this->quality = 100;
		}

		//---------- find new dimensions ----------
		if ( $this->windows_dev )
		{
			$this->src = str_replace('/', '\\', $this->src);
		}

		$result = $this->get_dimensions();
		if ( ! $result )
		{
			return false;
		}

		//prepare the text
		if ( $this->text != false )
		{
			$this->get_text_preference();
		}

		return true;
	}

	/**
	 * Determines which text preference, if any, meets the minimum dimensions requirement and sets it as the class' text property.
	 *
	 * @return void
	 */
	function get_text_preference()
	{
		//if a single text array, wrap it in an array
		if ( is_array( $this->text ) && ! is_array( $this->text[0] ) )
		{
			$this->text = array( $this->text );
		}

		//---------- get preference by minimum dimension ----------
		//let's see what preferences are being passed in for minimum dimensions, and see if we can find one that works
		$final_pref_ind = -1;
		foreach ( $this->text as $index => $text_pref )
		{
			//break up the chunks
			if ( is_array( $text_pref ) && count( $text_pref ) > 1 )
			{
				//size limits should be here
				if ( count( $text_pref[1] ) == 2 )
				{
					list( $min_w, $min_h ) = $text_pref[1];

					//check the dimensions
					if ( $this->do_crop == false )
					{
						if ( ($min_w == 0 || $min_w <= $this->width_final) && ($min_h == 0 || $min_h <= $this->height_final) )
						{
							//this pref will work
							$final_pref_ind = $index;
							break 1;
						}
					}
					else
					{
						if ( ($min_w == 0 || $min_w <= $this->width_desired) && ($min_h == 0 || $min_h <= $this->height_desired) )
						{
							//this pref will work
							$final_pref_ind = $index;
							break 1;
						}
					}
				}
				else
				{
					//both params are not there...we'll assume this is the one they wanted
					$final_pref_ind = $index;
					break 1;
					return;
				}
			}
			else
			{
				//no size limits found, so this pref will do
				$final_pref_ind = $index;
				break 1;
			}
		}

		if ( $final_pref_ind == -1 )
		{
			//we could not find a preference that fit the specified limits, so no watermark
			$this->text = false;
			return;
		}

		$this->text = $this->text[$final_pref_ind];
		if ( ! isset( $this->text[0] ) || trim( $this->text[0] == '' ) ) //there is no text
		{
			$this->text = false;
			return;
		}
	}


	/**
	 * Validate and prepare the text settings.
	 *
	 * @return void
	 */
	function prepare_text()
	{
		//0 - the text
		$this->text[0] = preg_replace( '@(\r\n|\r|\n)@Usmi', ' \n ', $this->text[0] ); //convert all line breaks to ' \n '

		//1 - minimum dimensions (these will be removed later)

		//2 - font size
		if ( ! isset( $this->text[2] ) || ! is_numeric( $this->text[2] ) || $this->text[2] < 0 )
		{
			$this->text[2] = 12;
		}

		//3 - line height
		if ( isset( $this->text[3] ) )
		{
			if ( ! is_numeric( $this->text[3] ) )
			{
				if ( strpos( $this->text[3], '%' ) !== false ) //% of the font size
				{
					$this->text[3] = str_replace( '%', '', $this->text[3] ); //remove the %

					if ( is_numeric( $this->text[3] ) ) //if numeric determine the % of font size
					{
						$this->text[3] = round( $this->text[2] * $this->text[3] * .01 );
					}
				}
			}
		}
		if ( ! isset( $this->text[3] ) || ! is_numeric( $this->text[3] ) || $this->text[3] < 0 ) //default
		{
			$this->text[3] = round( $this->text[2] * 1.25 ); //default to 1.25 of the font size
		}

		//4 - color
		if ( isset( $this->text[4] ) )
		{
			$this->text[4] = Ce_image_tools::hex_cleanup( $this->text[4] );
		}
		if ( ! isset( $this->text[4] ) || $this->text[4] == '' )
		{
			$this->text[4] = 'ffffff';
		}

		//5 - font - will default to the packaged heros-bold.ttf if no font is included
		$font = str_replace( '\\', '/', dirname(__FILE__) . '/../fonts/heros-bold.ttf' );
		if ( isset( $this->text[5] ) && $this->text[5] != '' )
		{
			//clean the filename
			$orig = $this->EE->security->xss_clean( $this->text[5] );

			if ( @file_exists( $orig ) === true ) //full server path
			{
				$this->debug_messages[] =  "The full server font path '$orig' was found.";

				//create full path for source
				$font = $orig;
			}
			else
			{
				$this->debug_messages[] =  "The full server font path '$orig' was not found, checking if it is a default font.";

				$temp = $this->EE->security->xss_clean( $this->remove_duplicate_slashes( str_replace( '\\', '/', dirname(__FILE__) . '/../fonts/' . $orig ) ) );
				if ( @file_exists( $temp ) === true ) //a default font
				{
					$this->debug_messages[] =  "The font '$temp' was found in the default folder.";
					$font = $temp;
				}
				else //relative to document root
				{
					$this->debug_messages[] =  "The font '$temp' does not appear to be in the default folder.";

					$temp = $this->EE->security->xss_clean( $this->remove_duplicate_slashes( $this->base . '/' . $orig ) );
					if ( @file_exists( $temp ) === true )
					{
						$this->debug_messages[] =  "The font '$temp' was found as a relative path.";
						$font = $temp;
					}
					else
					{
						$this->debug_messages[] =  "The font '$temp' could not be found by its relative path, the default font will be used.";
					}
				}
			}
		}
		$this->text[5] = realpath( $font );

		//6 - text alignment
		if ( ! isset( $this->text[6] ) || ! in_array( $this->text[6], $this->x_options ) )
		{
			$this->text[6] = 'center';
		}

		//7 - width adjustment
		if ( ! isset( $this->text[7] ) || ! is_numeric( $this->text[7] ) )
		{
			$this->text[7] = 0;
		}

		//8 - position
		$position = array( 'center', 'center' );
		if ( isset( $this->text[8] ) && is_array( $this->text[8] ) )
		{
			if ( isset( $this->text[8][0] ) && in_array( $this->text[8][0], $this->x_options ) )
			{
				$position[0] = $this->text[8][0];
			}
			if ( isset( $this->text[8][1] ) && in_array( $this->text[8][1], $this->y_options ) )
			{
				$position[1] = $this->text[8][1];
			}
		}
		$this->text[8] = $position;

		//9 - offset
		$offset = array( 0, 0 );
		if ( isset( $this->text[9] ) && is_array( $this->text[9] ) )
		{
			if ( isset( $this->text[9][0] ) && is_numeric( $this->text[9][0] ) )
			{
				$offset[0] = $this->text[9][0];
			}
			if ( isset( $this->text[9][1] ) && is_numeric( $this->text[9][1] ) )
			{
				$offset[1] = $this->text[9][1];
			}
		}
		$this->text[9] = $offset;

		//10 - opacity
		if ( ! isset( $this->text[10] ) || ! is_numeric( $this->text[10] ) || $this->text[10] < 0 || $this->text[10] > 100 )
		{
			$this->text[10] = 100;
		}

		//11 - shadow color
		//color
		if ( isset( $this->text[11] ) )
		{
			$this->text[11] = Ce_image_tools::hex_cleanup( $this->text[11] );
		}
		else
		{
			$this->text[11] = '000000';
		}

		//12 - shadow offset
		$offset = array( 1, 1 );
		if ( isset( $this->text[12] ) && is_array( $this->text[12] ) )
		{
			if ( isset( $this->text[12][0] ) && is_numeric( $this->text[12][0] ) )
			{
				$offset[0] = $this->text[12][0];
			}
			if ( isset( $this->text[12][1] ) && is_numeric( $this->text[12][1] ) )
			{
				$offset[1] = $this->text[12][1];
			}
		}
		$this->text[12] = $offset;

		//13 - shadow opacity
		if ( ! isset( $this->text[13] ) || ! is_numeric( $this->text[13] ) || $this->text[13] < 0 || $this->text[13] > 100 )
		{
			$this->text[13] = 50;
		}

		//remove the minimum dimension, as it will no longer be needed
		unset( $this->text[1] );
	}

// ---------------------------------------------------------------------------- CREATE ----------------------------------------------------------------------------
	/**
	 * Opens and manipulates an image.
	 *
	 * @param string|resource $src The image source. Can be a relative path to document root, a full server (absolute) path, a URL (remote or local), an HTML snippet, or a GD2 image resource.
	 * @param array $temp_settings The settings that will temporary override the previously set default settings, for this image only.
  	 * @param bool $save Whether or not to save the image after it is 'made.'
	 * @return bool Will return true on success and false on failure.
	 */
	public function make( $src = '', $temp_settings = array(), $save = true )
	{
		if ( $this->open( $src, $temp_settings ) === false )
		{
			return false;
		}

		//prepare the settings
		if ( $this->prep_settings() == false )
		{
			return false;
		}

		if ( $save == true )
		{
			//get remote_cache_time param
			if ( $this->is_remote )
			{
				if ( ! is_numeric( $this->remote_cache_time ) || $this->remote_cache_time < 0 )
				{
					$this->remote_cache_time = -1;
				}
			}

			//---------- check the cache ----------
			$is_cached = $this->is_cached();

			if ( ! $is_cached  || $this->overwrite_cache ) //the image is not cached
			{
				if ( $is_cached && $this->same_as_source && ! $this->allow_overwrite_original ) //don't overwrite cache
				{
					$this->debug_messages[] = "The source image '$this->path_final' will not be overwritten in order to prevent image degradation.";
				}
				else //not cached
				{
					$this->debug_messages[] = "The image '$this->path_final' is not cached.";

					if ( $this->open_real( true ) == false )
					{
						return false;
					}

					//image creation
					if ( $this->create() == false )
					{
						return false;
					}

					//return results
					if ( ! $this->save( $this->image_data['ext'] ) )
					{
						return false;
					}

					$this->final_action = 'saved';
				}

				//make the final path the source path
				$this->src = $this->path_final;

				$this->is_data_ready = true;

				$width = round($this->width_final);
				$height = round($this->height_final);
			}
			else //the image is cached
			{
				$this->debug_messages[] =  "The image '$this->path_final' is cached.";
				//make the final path the source path
				$this->src = $this->path_final;
				$this->final_action = 'cached';

				if ( ! $this->check_size ) //the dimension already determined should work, and the cached file doesn't need to be touched
				{
					$width = ( $this->do_crop == false ) ? round($this->width_final) : round($this->width_desired);
					$height = ( $this->do_crop == false ) ? round($this->height_final) : round( $this->height_desired );
				}
				else //check cached image, since it may be different from the requested size (from rotating or reflecting)
				{
					$data = @getimagesize( $this->src );
					if ( $data )
					{
						$width = $data[0];
						$height = $data[1];
					}
					else
					{
						$width = '';
						$height = '';

						$this->debug_messages[] = "Could not get dimensions of the cached image '$this->src'.";
					}
				}
			}

			//---------- return data ----------
			$this->image_data['width'] = $width;
			$this->image_data['height'] = $height;
		}
		else //elected not to save
		{
			if ( $this->open_real() == false )
			{
				return false;
			}

			//image creation
			if ( $this->create() == false )
			{
				return false;
			}

			$this->is_open_ready = true;
			$this->is_open = true;

			$this->src = '';
		}

		return true;
	}

	/**
	 * Determines the filename that should be created for the manipulated image and whether or not it is cached.
	 *
	 * @return bool Returns true if the image is cached and does not need to be recreated, false otherwise.
	 */
	private function is_cached()
	{
		$is_jpg = (strtolower( $this->image_data['ext'] ) == 'jpg' || strtolower( $this->image_data['ext'] ) == 'jpeg');
		$info = '';
		$info .= ( $is_jpg && $this->quality != 100 ) ? '_' . $this->quality : ''; //quality
		$info .=  ($this->bg_color != '') ? '_' . $this->bg_color : '';
		$info .= ( $this->bg_color == '' && $this->bg_color_default != '' && $this->bg_color_default != 'ffffff' && $is_jpg ) ? '_dbg-' . $this->bg_color_default : '';

		//filters
		if ( is_array($this->filters) )
		{
			foreach ( $this->filters as $filter )
			{
				foreach ($filter as $index => $piece)
				{
					if ( $index == '0' )
					{
						//only add the first 3 characters of the filter name to the info, to cut down on length
						$info .= substr( str_replace('img_filter', '',strtolower( $piece )), 0, 4 );
					}
					else
					{
						if ( is_array( $piece ) )
						{
							$piece = Ce_image_tools::recursive_implode( '_', $piece );
						}
						$piece = (string) $piece;
						$info .= '-' . strtolower( $piece );
					}
				}
			}
		}

		//flip
		if ( $this->flip !== false )
		{
			$info .= '_f' . implode( '', $this->flip );
		}

		//watermark
		if ( $this->watermark != '' )
		{
			$info .= '_' . Ce_image_tools::recursive_implode( '|', $this->watermark );
		}

		//text
		if ( $this->text != '' && is_array( $this->text ) && count( $this->text ) > 0 )
		{
			//add to the info array
			$info .= '_' . substr( md5( Ce_image_tools::recursive_implode( '|', $this->text ) ), 0, 16);
		}

		//borders
		if ( $this->border[0] > 0 )
		{
			$info .= '_bor' . $this->border[0] . '_' . $this->border[1];
		}

		//rounded corners
		if ( $this->corners === true )
		{
			$corners_all = false;
			//check to see if all of the corners are the same
			if ( count( $this->corners_array ) == 4 )
			{
				if ( array_diff( $this->corners_array['tl'], $this->corners_array['tr'], $this->corners_array['bl'], $this->corners_array['br'] ) == array() )
				{
					$corners_all = true;
				}
			}

			if ( $corners_all )
			{
				$info .= '_all_' . $this->corners_array['tl'][0];
				if ($this->corners_array['tl'][1] != '')
				{
					$info .= '_' . $this->corners_array['tl'][1];
				}
			}
			else
			{
				foreach( $this->corners_array as $pos => $corner )
				{
					$info .= '_' . $pos . '_' . $corner[0];
					if ( $corner[1] != '')
					{
						$info .= '_' . $corner[1];
					}
				}
			}
		}

		//reflection
		if ( $this->reflection === true )
		{
			$info .= '_ref' . implode('-', $this->reflection_array);

		}

		//rotate
		if ( $this->rotate !== false )
		{
			$info .= '_rot' . $this->rotate;
		}

		//clean up info a bit
		$info = str_replace( array('/','|',',','center','top','bottom','left','right','yes','no','%','#'), array('','_','_','c','t','b','l','r','y','n','p',''), $info);

		//ensure the unique parameter is set correctly
		if ( ! in_array( $this->unique, array( 'filename', 'directory_name', 'none' ) ) )
		{
			$this->unique = 'filename';
		}

		$width = (($this->do_crop === true) ? round($this->width_desired) : round($this->width_final));
		$height = (($this->do_crop === true) ? round($this->height_desired) : round($this->height_final));

		//determine whether or not the image name needs to change
		if ( $info == '' && $this->image_data_orig['width'] == $width && $this->image_data_orig['height'] == $height && $this->image_data['ext'] == $this->image_data_orig['ext']  && $this->hash_filename == false && $this->is_above_root && $this->filename == $this->filename_orig && $this->filename_prefix == '' && $this->filename_suffix == '' ) //this is the original
		{
			$this->path_final = $this->get_original_server_path();
			$this->same_as_source = true;
		}
		else if ($this->unique == 'none' && $this->hide_relative_path && ($this->image_data_orig['width'] != $width || $this->image_data_orig['height'] != $height) && $this->filename == $this->filename_orig && $this->image_data['ext'] == $this->image_data_orig['ext'] && $this->hash_filename == false ) //the user may be trying to overwrite the original, once
		{
			$this->path_final = $this->get_original_server_path();
			$this->same_as_source = false;
			return false;
		}
		else
		{
			$info .= ($this->allow_scale_larger === true) ? '_s' : ''; //scale larger
			$info .= ($this->do_crop === true) ? '_c' . Ce_image_tools::recursive_implode('_', $this->crop) : ''; //crop
			$info = $width . '_' . $height . $info;

			//if the filename is unique (default)
			if ($this->unique == 'filename' && $info != '')
			{
				//concatenate filename to original filename
				$filename = $this->filename . '_' . $info;
			}
			else
			{
				$filename = $this->filename;
			}

			//hash if specified
			if ($this->hash_filename)
			{
				$filename = sha1($filename);
			}
			$this->filename_final = $filename;

			//add the prefix
			if (!empty($this->filename_prefix))
			{
				$filename = $this->filename_prefix . $filename;
			}

			//add the suffix
			if (!empty($this->filename_suffix))
			{
				$filename .= $this->filename_suffix;
			}

			//add file extension
			$filename .= '.' . $this->image_data['ext'];

			//if the directory name is not unique
			if ($this->unique != 'directory_name')
			{
				$this->path_final = $this->cache_full . $filename; //final path
			}
			else
			{
				$this->cache_full = $this->cache_full . $info . '/';
				$this->relative = $this->relative . $info . '/';
				if ($this->windows_dev)
				{
					$this->cache_full = str_replace('/', '\\', $this->cache_full);
				}
				$this->path_final = $this->cache_full . $filename; //final path
			}
			$this->filename = $filename; //class filename
		}

		if ( ! @file_exists( $this->path_final ) ) //not cached if the file doesn't exist
		{
			return false;
		}

		$file_m_time = @filemtime( $this->path_final ); //filetime of cached image
		return  ( ( $file_m_time >= $this->filetime_orig ) && ( $file_m_time >= $this->filetime_wm ) ); //true if the file was made after the source image & optional watermark image, false otherwise
	}

	/**
	 * Attempts to resolve any relative dimensions to actual dimensions and ensure that a valid number is returned.
	 *
	 * @param int|string $dimension
	 * @param int|string $actual
	 * @return int
	 */
	private function resolve_dimension( $dimension, $actual )
	{
		if ( strpos( $dimension, '%' ) !== false )
		{
			$dimension = str_replace( '%', '', $dimension );
			$dimension = ( is_numeric( $dimension ) ) ? round( $actual * $dimension * .01 ) : 0;
		}

		if ( ! is_numeric( $dimension ) || $dimension < 0 )
		{
			$dimension = 0;
		}

		return $dimension;
	}

	/**
	 * Attempts to figure out the final width and height of the manipulated image.
	 *
	 * @return void
	 */
	private function get_dimensions()
	{
		$this->filetime_orig = @filemtime( $this->src );

		$width_initial = $this->image_data['width'];
		$height_initial = $this->image_data['height'];
		$this->width_initial = $width_initial;
		$this->height_initial = $height_initial;
		$this->width_final = $width_initial;
		$this->height_final = $height_initial;
		$this->width_desired = $width_initial;
		$this->height_desired = $height_initial;

		//resolve any relative dimensions and ensure all dimensions are usable
		$width = $this->resolve_dimension( $this->width, $width_initial );
		$min_width = $this->resolve_dimension( $this->min_width, $width_initial );
		$max_width = $this->resolve_dimension( $this->max_width, $width_initial );
		$height = $this->resolve_dimension( $this->height, $height_initial );
		$min_height = $this->resolve_dimension( $this->min_height, $height_initial );
		$max_height = $this->resolve_dimension( $this->max_height, $height_initial );

		//min width
		if ( ! empty($min_width) )
		{
			if ( empty($width) )
			{
				if ( $min_width > $width_initial )
				{
					$width = $min_width;
				}
			}
			else if ($min_width > $width)
			{
				$width = $min_width;
			}
		}

		//min height
		if ( ! empty($min_height) )
		{
			if ( empty($height) )
			{
				if ( $min_height > $height_initial )
				{
					$height = $min_height;
				}
			}
			else if ($min_height > $height)
			{
				$height = $min_height;
			}
		}

		//max width
		if ( ! empty($max_width) && (empty($width) || $max_width < $width) )
		{
			$width = $max_width;
		}

		//max height
		if ( ! empty($max_height) && (empty($height) || $max_height < $height) )
		{
			$height = $max_height;
		}

		//if they don't want the image to scale up, and the desired dimensions are larger than the original, just return the original size
		if ( ! $this->allow_scale_larger )
		{
			if ($width > $width_initial)
			{
				$width = $width_initial;
			}
			else if ($height > $height_initial)
			{
				$height = $height_initial;
			}
		}

		$width_new = $width;
		$height_new = $height;
		$x_offset = 0;
		$y_offset = 0;

		//if width and height are 0, just return original size
		if ( ($width == 0) && ($height == 0) )
		{
			if ( $this->watermark )
			{
				$this->get_watermark_dimensions();
			}

			return true;
		}

		//calculate new dimensions
		if ( $this->do_crop == false ) //resize to fit within dimensions
		{
			$ratio_x = ($width == 0) ? 0 : round( $width_initial / $width, 2 );
			$ratio_y = ($height == 0 ) ? 0 : round( $height_initial / $height, 2);

			if ( $ratio_x < $ratio_y )
			{
				$width_new = $width_initial / ( $height_initial / $height );
			}
			else
			{
				$height_new = $height_initial / ( $width_initial / $width );
			}

			$this->x_offset = $x_offset;
			$this->y_offset = $y_offset;
			$this->width_final = $width_new;
			$this->height_final = $height_new;

			if ( $this->watermark != '')
			{
				$this->get_watermark_dimensions();
			}

			return true;
		}
		else //crop to dimensions
		{
			//if width or height are 0, determine what the other dimension should be
			if ($width == 0)
			{
				$width = $width_initial * ($height / $height_initial);
				$width_new = $width;
			}
			if ($height == 0)
			{
				$height = $height_initial * ($width / $width_initial);
				$height_new = $height;
			}

			if ( $this->allow_scale_larger == false )
			{
				if ($width > $width_initial)
				{
					$width = $width_initial;
				}
				else if ($height > $height_initial)
				{
					$height = $height_initial;
				}

				$width_new = $width;
				$height_new = $height;
			}

			$this->width_desired = round( $width );
			$this->height_desired = round( $height );

			//defaults
			$smart_scale = true;
			$crop_pos_x = 'center';
			$crop_pos_y = 'center';
			$crop_offset_x = 0;
			$crop_offset_y = 0;

			$crop_count = count( $this->crop );

			//positions
			if ( $crop_count > 1 )
			{
				$temp = $this->crop[1];
				if ( is_array( $temp ) && count( $temp ) == 2 )
				{
					list( $temp_x, $temp_y ) = $temp;
					if ( in_array( $temp_x, $this->x_options) )
					{
						$crop_pos_x = $temp_x;
					}
					if ( in_array( $temp_y, $this->y_options) )
					{
						$crop_pos_y = $temp_y;
					}
				}
			}

			//offsets
			if ( $crop_count > 2 )
			{
				$temp = $this->crop[2];
				if ( is_array( $temp ) && count( $temp ) == 2 )
				{
					list( $temp_x, $temp_y ) = $temp;
					if ( is_numeric( $temp_x ) )
					{
						$crop_offset_x = $temp_x;
					}
					if ( is_numeric( $temp_y ) )
					{
						$crop_offset_y = $temp_y;
					}
				}
			}

			//smart scale
			if ( $crop_count > 3 )
			{
				$smart_scale = $this->crop[3];
			}

			//smart scale is enabled, figure out crop info
			if ($smart_scale)
			{
				$ratio_x = ($width_initial == 0) ? 0 : round( $width_initial / $width_new, 2 );
				$ratio_y = ($height_initial == 0 ) ? 0 : round( $height_initial / $height_new, 2);

				//determine new dimensions
				if ( $ratio_x > $ratio_y )
				{
					$width_new = $width_initial / ( $height_initial / $height );
				}
				else
				{
					$height_new = $height_initial / ($width_initial / $width);
				}

				switch( $crop_pos_x )
				{
					case 'left':
						$x_offset = 0;
						break;
					case 'right':
						$x_offset = $width - $width_new;
						break;
					default:
						$x_offset = - ($width_new - $width) * .5;
				}

				switch( $crop_pos_y )
				{
					case 'top':
						$y_offset = 0;
						break;
					case 'bottom':
						$y_offset = $height - $height_new;
						break;
					default:
						$y_offset = - ($height_new - $height) * .5;
				}

				$this->x_offset = $x_offset + $crop_offset_x;
				$this->y_offset = $y_offset + $crop_offset_y;
			}

			$this->width_final = round( $width_new );
			$this->height_final = round( $height_new );

			//smart scale is disabled, figure out crop info
			if (! $smart_scale ) //no smart scale
			{
				$this->x_offset = 0;
				$this->y_offset = 0;

				switch( $crop_pos_x )
				{
					case 'left':
						$x_offset = 0;
						break;
					case 'right':
						$x_offset = $width_initial - $width_new;
						break;
					default:
						$x_offset = - ($width_new - $width_initial) * .5;
				}

				switch( $crop_pos_y )
				{
					case 'top':
						$y_offset = 0;
						break;
					case 'bottom':
						$y_offset = $height_initial - $height_new;
						break;
					default:
						$y_offset = - ($height_new - $height_initial) * .5;
				}

				$this->src_x_offset = $x_offset + $crop_offset_x;
				$this->src_y_offset = $y_offset + $crop_offset_y;

				$this->width_initial = $width_new;
				$this->height_initial = $height_new;
			}

			if ( $this->watermark != '')
			{
				$this->get_watermark_dimensions();
			}

			return true;
		}
	}

	/**
	 * Determines whether or not a suitable watermark exists and determines its dimensions if it does.
	 *
	 * @return void
	 */
	private function get_watermark_dimensions()
	{
		//if a single watermark, wrap it in an array
		if ( is_array( $this->watermark ) && ! is_array( $this->watermark[0] ) )
		{
			$this->watermark = array( $this->watermark );
		}

		//lets see what preferences are being passed in for watermarking, and see if we can find one that works
		$wm_prefs = $this->watermark;

		$final_pref_ind = -1;
		foreach ( $wm_prefs as $ind => $wm_pref )
		{
			//break up the chunks
			if ( count( $wm_pref ) > 1 )
			{
				//size limits should be here
				if ( count( $wm_pref[1] ) == 2 )
				{
					list( $min_w, $min_h ) = $wm_pref[1];

					//check the dimensions
					if ( $this->do_crop == false )
					{
						if ( ($min_w == 0 || $min_w <= $this->width_final) && ($min_h == 0 || $min_h <= $this->height_final) )
						{
							//this pref will work
							$final_pref_ind = $ind;
							break 1;
						}
					}
					else
					{
						if ( ($min_w == 0 || $min_w <= $this->width_desired) && ($min_h == 0 || $min_h <= $this->height_desired) )
						{
							//this pref will work
							$final_pref_ind = $ind;
							break 1;
						}
					}
				}
				else
				{
					//both params are not there...bad syntax, bail out
					$this->watermark = '';
					return;
				}
			}
			else
			{
				//no size limits found, so this pref will do
				$final_pref_ind = $ind;
				break 1;
			}
		}

		if ( $final_pref_ind == -1 )
		{
			//we could not find a preference that fit the specified limits, so no watermark
			$this->watermark = '';
			return;
		}

		//we found a watermark that will do, lets work our magic!
		$this->watermark = $wm_prefs[$final_pref_ind];

		//get the watermark source
		$this->wm_array = $this->watermark;
		$wm_src = $this->wm_array[0];
		//check if URL
		if ( substr( $wm_src, 0, 4 ) == 'http' )
		{
			$info = parse_url( $wm_src );
			if ( $info === false )
			{
				return;
			}

			$wm_src = $info['path'];
		}
		//create full path for wm source
		$temp = $this->remove_duplicate_slashes( $this->base . $wm_src );

		//check to make sure the watermark source is readable
		if ( @is_readable( $temp ) === true ) //relative path
		{
			//create full path for source
			$wm_src = $this->remove_duplicate_slashes( realpath($temp) );
			$wm_src = str_replace('\\', '/', $wm_src);
		}

		$this->wm_image_data['src'] = $wm_src;

		if ( ! @is_readable( $this->wm_image_data['src'] ) )
		{
			$this->watermark = '';
			$this->debug_messages[] = "Watermark file '$this->wm_image_data['src']' is not readable or does not exist.";
			return;
		}
		else
		{
			$this->filetime_wm = @filemtime( $this->wm_image_data['src'] );

			//get the image info
			$wm_data = @getimagesize( $this->wm_image_data['src'] );

			if ( ! $wm_data )
			{
				$this->watermark = '';
				$this->debug_messages[] = 'Unknown image format for watermark.';
				return;
			}
			else
			{
				$this->wm_image_data['width'] = $wm_data[0];
				$this->wm_image_data['height'] = $wm_data[1];
				$this->wm_image_data['type'] = $wm_data[2];
			}
		}
	}

	/**
	 * Does the actual heavy lifting to create a manipulated image.
	 *
	 * @return bool Returns true if the image is created successfully and false on failure.
	 */
	private function create()
	{
		//round all values to get nice results
		$this->width_final = round( $this->width_final );
		$this->height_final = round( $this->height_final );

		//create new image
		if ( $this->do_crop == false )
		{
			$dest = imagecreatetruecolor( $this->width_final, $this->height_final );
		}
		else
		{
			$dest = imagecreatetruecolor( $this->width_desired, $this->height_desired );
		}

		//transparency and background color
		if ( ($this->image_data_orig['type'] == 3 || $this->image_data_orig['type'] == 1) && strtolower( $this->image_data['ext'] ) == 'gif' )  //png/gif to gif
		{
			if ( $this->bg_color == false ) //no bg_color
			{
				$this->is_transparent = true;
				//see if there is a transparent color index
				$transparency_index = imagecolortransparent( $this->handle );

				if ( $transparency_index >= 0 ) //there is a transparency index (gif)
				{
					$transparency_color = @imagecolorsforindex( $this->handle, $transparency_index);
					if ( $this->image_data_orig['type'] == 1 ) //gif to gif
					{
						$transparency_index = imagecolorallocate($dest, $transparency_color['red'], $transparency_color['green'], $transparency_color['blue']);
					}
					else //png to gif
					{
						$transparency_index = imagecolorallocatealpha($dest, $transparency_color['red'], $transparency_color['green'], $transparency_color['blue'], 127);
					}
					imagefill($dest, 0, 0, $transparency_index);
					imagecolortransparent($dest, $transparency_index);

				}
				else //there is not a transparency index
				{
					//find a color that does not exist in the image
					$found = false;
					$attempts = 0;
					while ($found == false && $attempts < 300)
					{
						$attempts++;
						$r = rand(0, 255);
						$g = rand(0, 255);
						$b = rand(0, 255);
						if ( imagecolorexact( $this->handle, $r, $g, $b ) != -1 )
						{
							$found = true;
							break;
						}
					}

					if ( $attempts < 300 )
					{
						$transparency_index = imagecolorallocatealpha($dest, $r, $g, $b, 127);

						imagefill($dest, 0, 0, $transparency_index);
						imagecolortransparent($dest, $transparency_index);
					}
					imagesavealpha($dest, true);
					imagealphablending($dest, true);
				}
			}
			else //has bg_color
			{
				list($r,$g,$b) = sscanf($this->bg_color, '%2x%2x%2x');
				$fill = imagecolorallocate($dest, $r, $g, $b);
				imagefill($dest, 0, 0, $fill);
				imagealphablending($dest, true);
				imagesavealpha($dest, true);
			}
		}
		else if (($this->image_data_orig['type'] == 3 || strtolower($this->image_data['ext']) == 'png') && $this->bg_color == false) //a png image
		{
			$this->is_transparent = true;
			imagealphablending($dest, false);
			imagesavealpha($dest, true);
			$bg = Ce_image_tools::hex_to_rgb($this->bg_color_default, 'ffffff');

			$transparent = imagecolorallocatealpha($dest, $bg[0], $bg[0], $bg[0], 127);
			if ($this->do_crop == false)
			{
				imagefilledrectangle($dest, 0, 0, $this->width_final, $this->height_final, $transparent);
			}
			else
			{
				imagefilledrectangle($dest, 0, 0, $this->width_desired, $this->height_desired, $transparent);
			}
		}
		else
		{
			$this->is_transparent = false;
			//background fill
			if ($this->bg_color == false)
			{
				//make default fill bg_color white instead of black
				$this->bg_color = $this->bg_color_default;
			}

			//only background fill if the image is less than 2000px by 2000px if memory is > 64 to prevent out of memory error
			if ((!$this->do_crop && $this->width_final < 2000 && $this->height_final < 2000) || ($this->do_crop && $this->width_desired < 2000 && $this->height_desired < 2000) || $this->memory_limit > 64)
			{
				list($r, $g, $b) = sscanf($this->bg_color, '%2x%2x%2x');
				$fill = imagecolorallocate($dest, $r, $g, $b);
				imagefill($dest, 0, 0, $fill);
			}
		}

		//flip
		$width_initial = $this->width_initial;
		$height_initial = $this->height_initial;
		$src_x_offset = $this->src_x_offset;
		$src_y_offset = $this->src_y_offset;

		if ( $this->flip !== false )
		{
			//offsets
			$crop_offset_x = 0;
			$crop_offset_y = 0;
			if ( count( $this->crop ) > 2 )
			{
				$temp = $this->crop[2];
				if ( count( $temp ) == 2 )
				{
					list( $temp_x, $temp_y ) = $temp;
					if ( is_numeric( $temp_x ) )
					{
						$crop_offset_x = $temp_x;
					}
					if ( is_numeric( $temp_y ) )
					{
						$crop_offset_y = $temp_y;
					}
				}
			}

			if ( in_array( 'h', $this->flip ) )
			{
				$src_x_offset = $width_initial + $this->src_x_offset - 1;
				$width_initial = - $width_initial;
				$this->x_offset -= $crop_offset_x * 2;
			}
			if ( in_array( 'v', $this->flip ) )
			{
				$src_y_offset = $height_initial + $this->src_y_offset - 1;
				$height_initial = - $height_initial;
				$this->y_offset -= $crop_offset_y * 2;
			}
		}

		//copy original image to the new image
		if ( @imagecopyresampled( $dest, $this->handle, $this->x_offset, $this->y_offset, $src_x_offset, $src_y_offset, $this->width_final, $this->height_final, $width_initial, $height_initial) == false )
		{
			$this->debug_messages[] = 'Couldn\'t resize image.';
			return false;
		}

		//destroy original image resource
		imagedestroy($this->handle);

		//apply filter(s) if applicable
		if ( is_array( $this->filters ) )
		{
			foreach ( $this->filters as $filter )
			{
				$filter_call = $this->valid_filters[ $filter['0'] ];

				if ( $filter_call === 'imagefilter' )
				{
					$filter['0'] = constant( 'IMG_FILTER_' . strtoupper( $filter['0'] ) );
					array_unshift( $filter, $dest );
					@call_user_func_array( $filter_call, $filter );
				}
				else
				{
						$filter['0'] = $dest; //add the image resource as the first parameter
						$dest = @call_user_func_array( $filter_call, $filter );
				}
			}
		}

		$this->width_final = imagesx( $dest );
		$this->height_final = imagesy( $dest );

		//if this is a png that will be saved to a jpg, turn it opaque so that the watermark can be added in correctly
		if ( $this->image_data_orig['type'] == 3 && $this->bg_color == false && ( strtolower( $this->image_data['ext'] ) == 'jpg' || strtolower( $this->image_data['ext'] ) == 'jpeg' )  ) //png being saved to jpg format
		{
			$this->bg_color = $this->bg_color_default;
			if ( $this->bg_color == false )
			{
				$this->bg_color = 'ffffff';
			}
			$dest = $this->transparent_to_opaque( $dest, $this->width_final, $this->height_final );
			imagealphablending($dest, true);
			imagesavealpha($dest, false);
			$this->is_transparent = false;
		}

		//watermark
		if ( $this->watermark != '' )
		{
			$dest = $this->add_watermark( $dest );
		}

		//text
		if ( $this->text != '' )
		{
			$this->prepare_text();
			array_unshift( $this->text, $dest );
			$dest = call_user_func_array( array( 'self', 'add_text' ) , $this->text );
		}

		//border
		if ( $this->border[0] > 0 )
		{
			$dest = $this->add_border( $dest, $this->width_final, $this->height_final, $this->border[0], $this->border[1] );
			$this->width_final = round( imagesx( $dest ) );
			$this->height_final = round( imagesy( $dest ) );
		}

		//rounded corners
		if ( $this->corners )
		{
			$dest = $this->prep_round_corners( $dest, $this->width_final, $this->height_final, $this->border[0], $this->border[1] );
		}

		//reflection
		if ( $this->reflection )
		{
			$dest = $this->reflect( $dest, $this->width_final, $this->height_final, $this->reflection_array[0], $this->reflection_array[1], $this->reflection_array[2], $this->reflection_array[3] );
			//set the new width and height
			$this->width_final = round( imagesx( $dest ) );
			$this->height_final = round( imagesy( $dest ) );
		}

		//rotate
		if ( $this->rotate !== false )
		{
			//rotate the image
			if ( $this->bg_color != false || strtolower( $this->image_data['ext'] ) == 'jpg' ) //create opaque bg
			{
				$color = Ce_image_tools::hex_to_rgb( $this->bg_color, 'fff' );
				$dest = imagerotate( $dest, $this->rotate, imagecolorallocate($dest, $color[0], $color[1], $color[2]) );
			}
			else //attempt transparency
			{
				$color = Ce_image_tools::hex_to_rgb( 'fff' );
				$dest = imagerotate( $dest, $this->rotate, imagecolorallocatealpha($dest, $color[0], $color[1], $color[2], 127) );
			}

			imagealphablending($dest, false);
			imagesavealpha($dest, true);

			//set the new width and height
			$this->width_final = round( imagesx( $dest ) );
			$this->height_final = round( imagesy( $dest ) );
		}

		//set the final image resource
		$this->handle = $dest;

		return true;
	}

	/**
	 * This function allows png images with no background to be converted to be completely opaque before saving to jpg format. It blends the new background color with the semi-transparent pixels for a smooth transition.
	 *
	 * @param resource $image The image to be maniplulated.
	 * @param int $width The image width.
	 * @param int $height The image height.
	 * @return resource The opaque image.
	 */
	private function transparent_to_opaque( $image, $width, $height )
	{
		//setup the background color
		$bg_rgb = Ce_image_tools::hex_to_rgb( $this->bg_color, 'ffffff' );

		//loop through the pixels
		for ( $y = 0; $y < $height; $y++ ) //row
		{
			//loop through the pixels in the row and set their opacity
			for ( $x = 0; $x < $width; $x++ ) //column
			{
				//get the current color info
				$color = imagecolorat($image, $x, $y);
				$r = $color >> 16 & 0xFF;
				$g = $color >> 8 & 0xFF;
				$b = $color & 0xFF;
				$a = $color >> 24 & 0xFF;

				//no transparency support, blend foreground and background colors using custom solution
				$alpha_inverse = $a / 127;
				$alpha = 1 - $alpha_inverse;
				$r = round( $r * $alpha + $bg_rgb[0] * $alpha_inverse );
				$g = round( $g * $alpha + $bg_rgb[1] * $alpha_inverse );
				$b = round( $b * $alpha + $bg_rgb[2] * $alpha_inverse );
				$color_new = imagecolorallocatealpha($image, $r, $g, $b, 0);
				imagesetpixel($image, $x, $y, $color_new);
			}
		}

		return $image;
	}

	/**
	 * Sets the memory limit to the specified limit, if it is less than the default limit (set in .htaccess or php.ini)
	 */
	private function set_memory_limit()
	{
		//get the memory_limit
		$default_memory_limit = ini_get( 'memory_limit' );
		if ( empty( $default_memory_limit ) ) //the default memory_limit setting could not be retrieved
		{
			$default_memory_limit = 0;
		}
		//convert the default to megabytes
		$default_memory_limit = Ce_image_tools::return_bytes( $default_memory_limit, 'M' );

		//get the CE Image defined memory limit (which should be an int in megabytes)
		$this->memory_limit = ( ! empty( $this->memory_limit ) && is_numeric( $this->memory_limit ) ) ? (int) $this->memory_limit : 64;

		if ( $this->memory_limit > $default_memory_limit ) //the CE Image memory_limit setting is greater than the current memory_limit
		{
			//set the new limit
			@ini_set( 'memory_limit', $this->memory_limit . 'M' );
		}
		else if ($this->memory_limit < $default_memory_limit)
		{
			$this->memory_limit = $default_memory_limit;
		}
	}

	/**
	 * Adds a border to the image.
	 *
	 * @param resource $old The current image.
	 * @param int $width_old The current image width.
	 * @param int $height_old The current image height.
	 * @param int $border_width The border width.
	 * @param string $border_color The border color. Should be a hexadecimal number (with or without the '#').
	 * @return resource
	 */
	private function add_border( $old, $width_old, $height_old, $border_width = 10, $border_color = 'ffffff' )
	{
		$border_width = round( $border_width );
		//border color
		$width = round( $width_old + $border_width * 2 );
		$height = round( $height_old + $border_width * 2);
		$new = $this->prep_transparency( $old, $width, $height, false );
		$color = Ce_image_tools::hex_to_rgb( $border_color, 'fff' );
		$border_color = imagecolorallocate($new, $color[0], $color[1], $color[2]);

		//top border
		imagefilledrectangle( $new, 0, 0, $width, $border_width, $border_color );
		//right border
		imagefilledrectangle( $new, $width_old + $border_width, $border_width, $width, $height_old + $border_width, $border_color );
		//bottom border
		imagefilledrectangle( $new, 0, $height_old + $border_width, $width, $height, $border_color );
		//left border
		imagefilledrectangle( $new, 0, $border_width, $border_width, $height_old + $border_width, $border_color );
		//copy image over top
		imagecopyresized( $new, $old, $border_width, $border_width, 0, 0, $width_old, $height_old, $width_old, $height_old );

		//free up memory
		imagedestroy( $old );
		return $new;
	}

	/**
	 * Adds the watermark to the image.
	 *
	 * @param resource $image The image resource to add the watermark to.
	 * @return resource The image resource with the watermark added.
	 */
	private function add_watermark( $image )
	{
		$wm_length = count( $this->wm_array );

		//opacity
		$wm_opacity = ( $wm_length > 2 ) ? trim($this->wm_array[2]) : 100;
		if ($wm_opacity > 100 )
		{
			$wm_opacity = 100;
		}
		else if ($wm_opacity < 0)
		{
			$wm_opacity = 0;
		}

		//position
		$wm_position = ( $wm_length > 3 ) ? $this->wm_array[3] : '';

		$wm_pos_x = 'center';
		$wm_pos_y = 'center';
		if ( $wm_position != 'repeat' )
		{
			//get user defined position
			if ( is_array( $wm_position ) && count( $wm_position ) == 2 )
			{
				if ( in_array( $wm_position[0], $this->x_options ) )
				{
					$wm_pos_x = $wm_position[0];
				}
				if ( in_array( $wm_position[1], $this->y_options ) )
				{
					$wm_pos_y = $wm_position[1];
				}
			}
		}
		else
		{
			$wm_pos_x = 0;
			$wm_pos_y = 0;
		}

		//offset
		$wm_offset = ( $wm_length > 4 ) ? $this->wm_array[4] : '';
		$wm_offset_x = 0;
		$wm_offset_y = 0;

		//get user defined offsets
		if ( is_array( $wm_offset ) )
		{
			if ( count( $wm_offset ) == 2 )
			{
				if ( is_numeric( trim($wm_offset[0]) ) )
				{
					$wm_offset_x = trim($wm_offset[0]);
				}
				if ( is_numeric( trim($wm_offset[1]) ) )
				{
					$wm_offset_y = trim($wm_offset[1]);
				}
			}
		}

		//create watermark image handle
		switch ( $this->wm_image_data['type'] )
		{
			case IMAGETYPE_GIF:
				$wm_image = imagecreatefromgif( $this->wm_image_data['src'] );
				break;
			case IMAGETYPE_PNG:
				$wm_image = imagecreatefrompng( $this->wm_image_data['src'] );
				break;
			case IMAGETYPE_JPEG:
				$wm_image = imagecreatefromjpeg( $this->wm_image_data['src'] );
				break;
			default:
				$this->debug_messages[] = 'Unknown image format for watermark creation.';
				return false;
		}

		$final_x = 0;  //default to top
		$final_y = 0;  //default to left


		//the repeat offset
		$repeat_offset = false;

		//figure out if the background image should repeat
		if ( $wm_position == 'repeat' )
		{
			$repeat_offset = 50;
		}
		else if (isset($wm_position[0]) && $wm_position[0] == 'repeat') //there is a repeat
		{
			if (isset($wm_position[1]) && is_numeric($wm_position[1]) && $wm_position[1] >= 0 && $wm_position[1] <= 100) //there is a valid repeat offset
			{
				$repeat_offset = $wm_position[1];
			}
			else //default repeat
			{
				$repeat_offset = 50;
			}
		}

		//figure out logo position
		if ( $repeat_offset === false )
		{
			switch ( $wm_pos_x )
			{
				case 'right':
					$final_x = $this->width_final - $this->wm_image_data['width'];
					break;
				case 'left':
					$final_x = 0;
					break;
				default: //center
					$final_x = ($this->width_final - $this->wm_image_data['width']) * .5;
					break;
			}
			switch ( $wm_pos_y )
			{
				case 'bottom':
					$final_y = $this->height_final - $this->wm_image_data['height'];
					break;
				case 'top':
					$final_y = 0;
					break;
				default: //center
					$final_y = ($this->height_final - $this->wm_image_data['height']) * .5;
					break;
			}
		}

		$blend = 'none';
		if ( isset( $this->wm_array[5] ) && in_array( $this->wm_array[5], array( 'multiply' ) ) )
		{
			$blend = $this->wm_array[5];
		}

		$final_x += $wm_offset_x;
		$final_y += $wm_offset_y;

		$is_png = ( strtolower( $this->image_data['ext'] ) == 'png' && ! $this->bg_color );

		if ( $this->is_transparent || $blend != 'none' )
		{
			//set the watermark opacity here
			$wm_image = $this->opacity( $wm_image, $wm_opacity );
		}

		if ( $repeat_offset !== false ) //if repeat is specified, repeat away...
		{
			//calculate the number of rows and columns
			$columns = ceil($this->width_final / ( $this->wm_image_data['width'] + $wm_offset_x));
			$columns++;
			$rows = ceil($this->height_final / ( $this->wm_image_data['height'] + $wm_offset_y));

			for ( $r = 0; $r < $rows; $r++ )
			{
				for ( $c = 0; $c < $columns; $c++ )
				{
					$pos_x = ($this->wm_image_data['width'] + $wm_offset_x) * $c  + $wm_offset_x;
					$remainder = $r * $repeat_offset * .01;
					$remainder = $remainder - floor( $remainder );
					$pos_x += ($this->wm_image_data['width'] + $wm_offset_x) * - $remainder;
					$pos_y = ($this->wm_image_data['height'] + $wm_offset_y) * ( $r )  + $wm_offset_y;

					if ( $is_png || $blend != 'none' )
					{
						$this->overlay_image( $image, $wm_image, $pos_x, $pos_y, $this->wm_image_data['width'], $this->wm_image_data['height'], $blend );
					}
					else
					{
						$this->imagecopymerge_alpha($image, $wm_image, $pos_x, $pos_y, 0, 0, $this->wm_image_data['width'], $this->wm_image_data['height'], $wm_opacity );
					}
				}
			}
		}
		else //if no repeat, add the logo -- just once!
		{
			if ( $is_png || $blend != 'none' )
			{
				$this->overlay_image($image, $wm_image, $final_x, $final_y, $this->wm_image_data['width'], $this->wm_image_data['height'], $blend );
			}
			else
			{
				$this->imagecopymerge_alpha($image, $wm_image, $final_x, $final_y, 0, 0, $this->wm_image_data['width'], $this->wm_image_data['height'], $wm_opacity );
			}
		}

		//free up memory
		imagedestroy($wm_image);
		return $image;
	}


	/**
	 * A function to overlay a png image watermark over a png image, since the built in (faster) PHP methods will not support transparency properly.
	 *
	 * @param resource $dst_im The destination (main) image.
	 * @param resource $src_im The source (watermark) image.
	 * @param int $dst_x The x position of the destination image to start the left most pixel of the watermark.
	 * @param int $dst_y The y position of the destination image to start the top most pixel of the watermark.
	 * @param int $src_w The width of the source (watermark) image.
	 * @param int $src_h The height of the source (watermark) image.
	 * @param string $blend Can be 'none' or 'multiply'.
	 * @return resource The new image.
	 */
	private function overlay_image( $dst_im, $src_im, $dst_x, $dst_y, $src_w, $src_h, $blend = 'none' )
	{
		//get the dst image width and height
		$width = imagesx( $dst_im );
		$height = imagesy( $dst_im );

		//loop through the rows
		for ( $y = 0; $y < $src_h; $y++ ) //row
		{
			$dy = $dst_y + $y;
			if ( $dy < 0 || $dy > $height -1 )
			{
				continue;
			}

			//loop through the pixels in the row
			for ( $x = 0; $x < $src_w; $x++ ) //column
			{
				$dx = $dst_x + $x;
				if ( $dx < 0 || $dx > $width - 1 )
				{
					continue;
				}

				//get the current color info for the source image
				$src_color = imagecolorat($src_im, $x, $y);
				$src_r = $src_color >> 16 & 0xFF;
				$src_g = $src_color >> 8 & 0xFF;
				$src_b = $src_color & 0xFF;
				$src_alpha = $src_color >> 24 & 0xFF;

				//get the color info for the destination image
				$dst_color = imagecolorat($dst_im, $dx, $dy);
				$dst_r = $dst_color >> 16 & 0xFF;
				$dst_g = $dst_color >> 8 & 0xFF;
				$dst_b = $dst_color & 0xFF;
				$dst_alpha = $dst_color >> 24 & 0xFF;

				if ( $blend == 'none' )
				{
					if ( $src_alpha == 127 ) //the pixel is completely transparent
					{
						continue;
					}

					if ( $dst_alpha == 127 )
					{
						//since the dst pixel is completely transparent, set to the src pixel
						$color_new = imagecolorallocatealpha($dst_im, $src_r, $src_g, $src_b, $src_alpha);
						imagesetpixel($dst_im, $dx, $dy, $color_new);
					}
					else
					{
						$t = min( $src_alpha, $dst_alpha );
						$src_alpha = 1 - $src_alpha / 127;
						$dst_alpha = 1 - $src_alpha;

						$total_alpha = $src_alpha + $dst_alpha;
						$src_alpha /= $total_alpha;
						$dst_alpha /= $total_alpha;

						$final_r = round( $src_r * $src_alpha + $dst_r * $dst_alpha );
						$final_g = round( $src_g * $src_alpha + $dst_g * $dst_alpha );
						$final_b = round( $src_b * $src_alpha + $dst_b * $dst_alpha );
						$color_new = imagecolorallocatealpha($dst_im, $final_r, $final_g, $final_b, $t );
						imagesetpixel($dst_im, $dx, $dy, $color_new);
					}
				}
				else if ($blend == 'multiply')
				{
					$t = $dst_alpha;
					if ($dst_alpha == 127) //the main image is transparent, just use the watermark
					{
						$final_r = $src_r;
						$final_g = $src_g;
						$final_b = $src_b;
						$t = $src_alpha;
					}
					else //the main image is not transparent
					{
						if ($src_alpha != 0) //there is some opacity for the watermark, so we need to compensate by blending with white
						{
							$src_alpha = 1 - $src_alpha / 127;
							$inverse_alpha = 1 - $src_alpha;

							$src_r = round($src_r * $src_alpha + 255 * $inverse_alpha);
							$src_g = round($src_g * $src_alpha + 255 * $inverse_alpha);
							$src_b = round($src_b * $src_alpha + 255 * $inverse_alpha);

						}

						$final_r = round($src_r * $dst_r / 255);
						$final_g = round($src_g * $dst_g / 255);
						$final_b = round($src_b * $dst_b / 255);
					}

					$color_new = imagecolorallocatealpha($dst_im, $final_r, $final_g, $final_b, $t);
					imagesetpixel($dst_im, $dx, $dy, $color_new);
				}
			}
		}

		return $dst_im;
	}

	/**
	 * A workaround to get transparency support to work for watermark images. Does not seem to work, however, if the destination (main) image is transparent.
	 *
	 * This method is a derivative work of a function originally posted by Sina Salek 09-Aug-2009 08:26: http://www.php.net/manual/en/function.imagecopymerge.php#92787
	 * Original function license: http://creativecommons.org/licenses/by/3.0/legalcode
	 *
	 * @param resource $dst_im The destination (main) image
	 * @param resource $src_im The source (watermark) image
	 * @param int $dst_x The x destination position
	 * @param int $dst_y The y destination position
	 * @param int $src_x The x source position
	 * @param int $src_y The y source position
	 * @param int $src_w The source image width dimension
	 * @param int $src_h The source image height dimension
	 * @param int $opacity The source (watermark) image opacity. Ranges from 0 to 100.
	 * @return void
	 */
	private function imagecopymerge_alpha( $dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity )
	{
        //creating a cut resource
       	$cut = imagecreatetruecolor($src_w, $src_h);

        //copying that section of the background to the cut
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
		//copy the watermark into the cut
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
		//place the cut back into the image
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);

		//free up resources
		imagedestroy( $cut );
    }

	/**
	 * Prepares the image as needed and rounds its corners
	 *
	 * @param resource $old The image that will need it
	 * @param int $width
	 * @param int $height
	 * @param int $border_width
	 * @param string $border_color
	 * @return resource The manipulated image
	 */
	private function prep_round_corners( $old, $width, $height, $border_width = 0, $border_color = 'ff0000')
	{
		$new = $this->prep_transparency( $old, $width, $height );

		foreach ( $this->corners_array as $pos => $corner )
		{
			switch ( $pos )
			{
				case 'tl':
					$left = 0;
					$top = 0;
					break;
				case 'tr':
					$left = 1;
					$top = 0;
					break;
				case 'bl':
					$left = 0;
					$top = 1;
					break;
				case 'br':
					$left = 1;
					$top = 1;
					break;
				default:
					break 2;
			}

			//set the radius and color
			$radius = $corner[0];
			$color = $corner[1];

			if ( $radius == 0 )
			{
				continue;
			}

			//check transparency
			$transparency = 0;
			if ( $this->is_transparent && $color == '' ) //transparent
			{
				$transparency = 127;
			}

			//check color
			if ( $color == '' ) //transparent
			{
				$color = $this->bg_color;
			}

			//check radius
			if ( $radius >= $width || $radius >= $height )
			{
				$radius = ( $width >= $height ) ? $height : $width;
			}

			//round the corner
			$this->round_corners( $new, $width, $height, $radius , $color, $transparency, $top, $left, $border_width, $border_color );
		}

		return $new;
	}


	/**
	 * Prepares image transparency.
	 *
	 * @param resource $old The current image resource.
	 * @param int $width The image width.
	 * @param int $height The image height.
	 * @param bool $copy Copy the old image into the new one?
	 * @return resource The new, transparency prepared image resource.
	 */
	private function prep_transparency( $old, $width, $height, $copy = true )
	{
		$new = imagecreatetruecolor($width, $height);

		if ( $this->is_transparent )
		{
			if ( ($this->image_data_orig['type'] == 3 || $this->image_data_orig['type'] == 1) && strtolower( $this->image_data['ext'] ) == 'gif' )
			{
				//see if there is a transparent color index
				$transparency_index = imagecolortransparent( $old );

				if ( $transparency_index >= 0 ) //there is a transparency index (gif)
				{
					$transparency_color = @imagecolorsforindex( $old, $transparency_index);
					if ( $this->image_data_orig['type'] == 1 ) //gif to gif
					{
						$transparency_index = imagecolorallocate($new, $transparency_color['red'], $transparency_color['green'], $transparency_color['blue']);
					}
					else //png to gif
					{
						$transparency_index = imagecolorallocatealpha($new, $transparency_color['red'], $transparency_color['green'], $transparency_color['blue'], 127);
					}
					imagefill($new, 0, 0, $transparency_index);
					imagecolortransparent($new, $transparency_index);

				}
				else //there is not a transparency index
				{
					//find a color that does not exist in the image
					$found = false;
					while ($found == false)
					{
						$r = rand(0, 255);
						$g = rand(0, 255);
						$b = rand(0, 255);
						if ( imagecolorexact( $old, $r, $g, $b ) != -1 )
						{
							$found = true;
							break;
						}
					}
					$transparency_index = imagecolorallocatealpha($new, $r, $g, $b, 127);

					imagefill($new, 0, 0, $transparency_index);
					imagecolortransparent($new, $transparency_index);

					imagesavealpha($new, true);
					imagealphablending($new, true);
				}
			}
			else
			{
				imagealphablending($new, false);
				imagesavealpha($new, true);
				$transparent = imagecolorallocatealpha($new, 255, 255, 255, 127);
				imagefilledrectangle($new, 0, 0, $width, $height, $transparent);
			}
		}
		else
		{
			imagealphablending($new, true);
			imagesavealpha($new, false);
		}

		if ( $copy === true )
		{
			imagecopy($new, $old, 0, 0, 0, 0, $width, $height);
			imagedestroy($old);
		}

		return $new;
	}

	/**
	 * Reflects an image
	 *
	 * @param resource $old The current image (without the reflection).
	 * @param int $width The current image's width
	 * @param int $height The current image's height
	 * @param int $gap The gap between the image and the reflection
	 * @param int $start_opacity The starting opacity of the reflection
	 * @param int $end_opacity The ending opacity of the reflection
	 * @param int|string $ref_height The desired reflection height. Can be an integer or a % of the original.
	 * @return resource The new image resource with the created reflection.
	 */
	private function reflect( $old, $width, $height, $gap = 0, $start_opacity = 80, $end_opacity = 0, $ref_height = '50%' )
	{
		//---------- figure out the params ----------
		if ( ! is_numeric( $gap ) )
		{
			$gap = 0;
		}

		//figure out the height
		if ( substr( $ref_height, -1 ) == '%' ) //percentage
		{
			$ref_height = (int) substr( $ref_height, 0, -1 ); //remove the % sign
			if ( ! is_numeric( $ref_height ) )
			{
				$ref_height = 1;
			}
			$ref_height = ceil($height * $ref_height * .01 ); //% height to actual height
		}
		//validate height
		$ref_height = round( $ref_height );
		if ( $ref_height > $height )
		{
			$ref_height = $height;
		}
		else if ($ref_height <= 0) //no reflection, not much more to do
		{
			return $old;
		}

		//start opacity
		if ( ! is_numeric( $start_opacity ) )
		{
			$start_opacity = 80;
		}
		$start_opacity = round(127 * $start_opacity / 100);
		if ( $start_opacity < 0 )
		{
			$start_opacity = 0;
		}
		else if ($start_opacity > 127)
		{
			$start_opacity = 127;
		}

		//end opacity
		$end_opacity =  round( 127 * $end_opacity / 100 );
		if ( ! is_numeric( $end_opacity ) )
		{
			$end_opacity = 0;
		}
		if ( $end_opacity < 0 )
		{
			$end_opacity = 0;
		}
		else if ($end_opacity > 127)
		{
			$end_opacity = 127;
		}

		//---------- create the images and the reflection ----------
		//create the final image and a temp image
		$final = $this->prep_transparency( $old, $width, $height + $ref_height + $gap, false);
		$temp = $this->prep_transparency( $old, $width, $ref_height, false);

		//copy the old into the final
		imagecopy( $final, $old, 0, 0, 0, 0, $width, $height );

		//copy the bottom part of the old image into the temp
		imagecopy( $temp, $old, 0, 0, 0, $height - $ref_height, $width, $ref_height );

		//destroy the original image
		imagedestroy( $old );

		$opacity_range = abs( $start_opacity - $end_opacity );

		//setup the background color
		$bg_rgb = Ce_image_tools::hex_to_rgb( $this->bg_color, 'ffffff' );

		$is_png = ( strtolower( $this->image_data['ext'] ) == 'png' && ! $this->bg_color );

		$bg_transparency = ( $is_png ) ? 127 : 0;

		//allocate the background color
		$bg_color = imagecolorallocatealpha($final, $bg_rgb[0], $bg_rgb[1], $bg_rgb[2], $bg_transparency);

		//make the gap transparent
		for ( $y = 0; $y < $gap; $y++ ) //row
		{
			for ( $x = 0; $x < $width; $x++ ) //column
			{
				imagesetpixel($final, $x, $y + $height, $bg_color);
			}
		}

		//add in the reflection line by line
		for ( $y = 0; $y < $ref_height; $y++ ) //row
		{
			//copy temp row to final
			imagecopy( $final, $temp, 0, $y + $height + $gap, 0, $ref_height - $y - 1, $width, 1 );

			//determine opacity
			if ( $start_opacity > $end_opacity )
			{
				$opacity = 127 - ($start_opacity - ($y / $ref_height * $opacity_range));
			}
			else
			{
				$opacity = 127 - ($start_opacity + ($y / $ref_height * $opacity_range));
			}

			//loop through the pixels in the row and set their opacity
			for ( $x = 0; $x < $width; $x++ ) //column
			{
				//get the current color info
				$current_color = imagecolorat($final, $x, $y + $height + $gap);
				$current_r = $current_color >> 16 & 0xFF;
				$current_g = $current_color >> 8 & 0xFF;
				$current_b = $current_color & 0xFF;
				$current_alpha = $current_color >> 24 & 0xFF;

				if ( $is_png )
				{
					$t = 127 * (1 - ( 1 - $current_alpha / 127 ) * ( 1 - $opacity / 127));

					if ( $current_alpha != 127 )
					{
						$color_new = imagecolorallocatealpha($final, $current_r, $current_g, $current_b, $t);
						imagesetpixel($final, $x, $y + $height + $gap, $color_new);
						unset($color_new);
					}
					unset( $t );
				}
				else
				{
					//no transparency support, blend foreground and background colors using custom solution
					$alpha_inverse = $opacity / 127;
					$alpha_level = 1 - $alpha_inverse;
					$final_r = round( $current_r * $alpha_level + $bg_rgb[0] * $alpha_inverse );
					$final_g = round( $current_g * $alpha_level + $bg_rgb[1] * $alpha_inverse );
					$final_b = round( $current_b * $alpha_level + $bg_rgb[2] * $alpha_inverse );
					$color_new = imagecolorallocatealpha($final, $final_r, $final_g, $final_b, 0);
					imagesetpixel($final, $x, $y + $height + $gap, $color_new);
					unset($alpha_inverse);
					unset($alpha_level);
					unset($final_r);
					unset($final_g);
					unset($final_b);
					unset($color_new);
				}

				unset($current_color);
				unset($current_r);
				unset($current_g);
				unset($current_b);
				unset($current_alpha);
			}
		}

		imagedestroy( $temp );

		return $final;
	}

	/**
	 * Sets the image opacity manually, pixel by pixel.
	 *
	 * @param resource $image The current image resource.
	 * @param int $value The opacity level to set the new image to. Ranges from 0 to 100.
	 * @param bool|string $bg false if the default background should be used, a hexadecimal string otherwise.
	 * @return resource The new image resource.
	 */
	private function opacity( $image, $value = 100, $bg = false )
	{
		$value = ( is_null( $value ) ) ? 100 : $value ;

		if ( $value == 100 )
		{
			//no need to set the opacity, it's completely opaque already...
			return $image;
		}

		//get the width and height
		$width = imagesx( $image );
		$height = imagesy( $image );

		if ( ! is_numeric( $value ) )
		{
			$value = 100;
		}
		$value = round(127 * $value / 100);
		if ( $value < 0 )
		{
			$value = 0;
		}
		else if ($value > 127)
		{
			$value = 127;
		}

		//reverse the value
		$value = 127 - $value;

		//setup the background color
		if ( $bg == false )
		{
			$bg_rgb = Ce_image_tools::hex_to_rgb( $this->bg_color, 'ffffff' );
		}
		else
		{
			$bg_rgb = Ce_image_tools::hex_to_rgb( $bg, 'ffffff' );
		}

		//---------- prep and create the new image ----------
		//create the final image and a temp image
		$final = $this->prep_transparency( $image, $width, $height, true);


		//add in the reflection line by line
		for ( $y = 0; $y < $height; $y++ ) //row
		{
			//loop through the pixels in the row and set their opacity
			for ( $x = 0; $x < $width; $x++ ) //column
			{
				//get the current color info
				$current_color = imagecolorat($final, $x, $y);
				$current_r = $current_color >> 16 & 0xFF;
				$current_g = $current_color >> 8 & 0xFF;
				$current_b = $current_color & 0xFF;
				$current_alpha = $current_color >> 24 & 0xFF;

				if ( $this->is_transparent )
				{
					$t = 127 * (1 - ( 1 - $current_alpha / 127 ) * ( 1 - $value / 127));

					if ( $current_alpha != 127 )
					{
						$color_new = imagecolorallocatealpha($final, $current_r, $current_g, $current_b, $t);
						imagesetpixel($final, $x, $y, $color_new);
					}
					unset( $t );
				}
				else
				{
					//no transparency support, blend foreground and background colors using custom solution
					$alpha_inverse = $value / 127;
					$alpha_level = 1 - $alpha_inverse;
					$final_r = round( $current_r * $alpha_level + $bg_rgb[0] * $alpha_inverse );
					$final_g = round( $current_g * $alpha_level + $bg_rgb[1] * $alpha_inverse );
					$final_b = round( $current_b * $alpha_level + $bg_rgb[2] * $alpha_inverse );
					$color_new = imagecolorallocatealpha($final, $final_r, $final_g, $final_b, 0);
					imagesetpixel($final, $x, $y, $color_new);
				}
			}
		}

		return $final;
	}

	/**
	 * Sepia filter with transparency support.
	 *
	 * @param resource $image The original image.
	 * @return resource $image The manipulated image.
	 */
	private function sepia( $image )
	{
		//get the width and height
		$width = imagesx( $image );
		$height = imagesy( $image );

		//add in the reflection line by line
		for ( $y = 0; $y < $height; $y++ ) //row
		{
			//loop through the pixels in the row and set their opacity
			for ( $x = 0; $x < $width; $x++ ) //column
			{
				//get the current color info
				$color = imagecolorat($image, $x, $y);
				$r = $color >> 16 & 0xFF;
				$g = $color >> 8 & 0xFF;
				$b = $color & 0xFF;
				$a = $color >> 24 & 0xFF;

				$r = 0.393 * $r + 0.769 * $g + 0.189 * $b;
				$g = (int) min( $r / 1.125, 255 ); //(int) min( 0.349 * $current_r + 0.686 * $current_g + 0.168 * $current_b, 255 );
				$b = (int) min( $r / 1.44, 255 ); //(int) min( 0.272 * $current_r + 0.534 * $current_g + 0.131 * $current_b, 255 );
				$r = (int) min( $r, 255 );

				$color_new = imagecolorallocatealpha($image, $r, $g, $b, ( $this->is_transparent ) ? $a : 0 );
				imagesetpixel($image, $x, $y, $color_new);
			}
		}

		return $image;
	}

	/**
	 * Replace colors.
	 *
	 * Examples or the rules parameter:
	 * $some_gd_image, array( array( '#fffffe-#111', 'f00' ), array( '#f00', '#000' ) )
	 * $some_gd_image, '#fffffe-#111', 'f00', '#f00', '#000'
	 *
	 * @param resource $image The original image
	 * @param mixed $rules An array containing arrays of color_range, replacement_color values. If a replacement_color is false, the color will become transparent. Additionally, this function can take comma delimited color_range, replacement_color sets. See the examples in the description above
	 * @return resource $image The new image.
	 */
	private function replace_colors( $image, $rules = array() )
	{
		//validate the rules array
		if ( is_null( $rules ) )
		{
			return $image;
		}
		else if ( !is_array( $rules) && func_num_args() > 1 )
		{

			//get the function arguments
			$args = func_get_args();
			array_shift( $args );

			$rules = array();

			//loop through the args and turn into an associative array
			foreach ( $args as $index => $arg )
			{
				if ( $index & 1 ) //is the index odd?
				{
					$rules[] = array( $args[$index - 1], $args[$index]  );
				}
			}
		}

		if ( count( $rules ) < 1 )
		{
			return $image;
		}

		$regs = array();

		if ( ! is_array( $rules[0] ) )
		{
			$temp = array();
			$temp[0] = $rules;
			$rules = $temp;
			unset( $temp );
		}

		//validate the rules values
		foreach ( $rules as $rule )
		{
			if ( isset( $rule[0] ) )
			{
				$range = $rule[0];
			}
			else
			{
				$this->debug_messages[] = 'No color range was received, so the rule is being skipped.';
				continue;
			}

			if ( isset( $rule[1] ) )
			{
				$color = $rule[1];
			}
			else
			{
				$color = false;
			}

			$range = explode( '-', $range );
			$data = array();

			//range start value
			if ( isset( $range[0] ) )
			{
				$range[0] = Ce_image_tools::hex_cleanup( $range[0] );

				if ( $range[0] === false ) //the hex is not valid, continue
				{
					$this->debug_messages[] = 'The replace color start range was invalid, so the rule is being skipped.';
					continue;
				}
				else
				{
					$data['start'] = hexdec( $range[0] );
				}
			}
			else //no color is set
			{
				continue;
			}

			//range end value
			if ( isset( $range[1] ) && $range[1] != false )
			{
				$range[1] = Ce_image_tools::hex_cleanup( $range[1] );

				if ( $range[1] === false ) //the hex is not valid, set to start
				{
					$this->debug_messages[] = 'The replace color end range was invalid, so the rule is being skipped.';
					continue;
				}
				else
				{
					$data['end'] = hexdec( $range[1] );
				}
			}
			else
			{
				$data['end'] = hexdec( $range[0] );
			}

			//swap the end and start if needed
			if ( $data['end'] < $data['start'] )
			{
				$temp = $data['end'];
				$data['end'] = $data['start'];
				$data['start'] = $temp;
			}

			//color replace value
			if ( isset( $color ) && $color != false ) //the color value is set
			{
				$color = Ce_image_tools::hex_cleanup( $color );
				if ( $color == false ) //the hex is not valid
				{
					$this->debug_messages[] = 'The replace color was invalid, so the rule is being skipped.';
					continue;
				}
				else //the color is set
				{
					$data['rgb'] = Ce_image_tools::hex_to_rgb( $color );
					$data['color'] = hexdec( $color );
				}
			}
			else //the color equals false
			{
				$data['color'] = false;
			}

			$regs[] = $data;
		}

		unset ( $rules );

		if ( count( $regs ) < 1 )
		{
			return $image;
		}

		//get the width and height
		$width = imagesx( $image );
		$height = imagesy( $image );

		//---------- prep and create the new image ----------
		//create the final image and a temp image
		if ( $this->is_transparent )
		{
			$final = $this->prep_transparency( $image, $width, $height, true);
		}
		else
		{
			$final = $image;
			//background color
			$bg_rgb = Ce_image_tools::hex_to_rgb( $this->bg_color, $this->bg_color_default );
		}

		for ( $y = 0; $y < $height; $y++ ) //row
		{
			//loop through the pixels in the row and set their opacity
			for ( $x = 0; $x < $width; $x++ ) //column
			{
				//get the current color info
				$color = imagecolorat($final, $x, $y);
				$r = $color >> 16 & 0xFF;
				$g = $color >> 8 & 0xFF;
				$b = $color & 0xFF;
				$a = $color >> 24 & 0xFF;

				if ( $a == 127 )
				{
					continue;
				}

				$hex = hexdec( Ce_image_tools::rgb_to_hex( $r, $g, $b ) );

				foreach ( $regs as $reg ) //loop through all the rule sets and see if any match
				{
					if ( $hex >= $reg['start'] && $hex <= $reg['end'] ) //replace the color
					{
						if ( $reg['color'] === false ) //no color
						{
							if ( $this->is_transparent ) //keep the color, but make it transparent
							{
								$a = 127;
								imagesetpixel($final, $x, $y, imagecolorallocatealpha($final, $r, $g, $b, $a ) );
							}
							else //use the background color
							{
								$r = $bg_rgb[0];
								$g = $bg_rgb[1];
								$b = $bg_rgb[2];
								imagesetpixel($final, $x, $y, imagecolorallocate($final, $r, $g, $b ) );

								//calculate the new color value
								$hex = hexdec( Ce_image_tools::rgb_to_hex( $r, $g, $b ) );
							}
						}
						else //new color
						{
							if ( $this->is_transparent ) //same transparency
							{
								$r = $reg['rgb'][0];
								$g = $reg['rgb'][1];
								$b = $reg['rgb'][2];

								imagesetpixel($final, $x, $y, imagecolorallocatealpha($final, $r, $g, $b, $a ) );
							}
							else //fake transparency
							{
								$alpha_inverse = $a / 127;
								$alpha_level = 1 - $alpha_inverse;
								$r = round( $reg['rgb'][0] * $alpha_level + $r * $alpha_inverse );
								$g = round( $reg['rgb'][1] * $alpha_level + $g * $alpha_inverse );
								$b = round( $reg['rgb'][2] * $alpha_level + $b * $alpha_inverse );
								$a = 0;
								imagesetpixel($final, $x, $y, imagecolorallocate($final, $r, $g, $b) );
							}

							//calculate the new color value
							$hex = hexdec( Ce_image_tools::rgb_to_hex( $r, $g, $b ) );
						}
					}
				}
			}
		}

		return $final;
	}


	/**
	 * Adds text to the image. This method does no validation (should be taken care of before hand by the prepare_text() method) and expects the parameters to be valid.
	 *
	 * @param mixed $old_image The resource to add the text to.
	 * @param string $text The text to add.
	 * @param int $font_size The font size, in pixels.
	 * @param int|string $line_height The line height. This can be an integer (to specify the pixel value, eg: 15), or a percentage of the font_size (eg: 120%).
	 * @param string $font_color The text color. Can be a 3 or 6 digit hexadecimal number (with or without the #).
	 * @param string $font_path The full server path to the TrueType font (.ttf) file to use for the font.
	 * @param string $text_align The way the text should align. The options are: 'left', 'center', or 'right'
	 * @param int $width_adjustment An integer to adjust the width by. By default, the line width for the text will be the manipulated image's width.
	 * @param array $position This parameter will position the block of text in relation to the image. This can be specified as a pair of positions in the form of horizontal_position_string,vertical_position_string. The accepted values for the horizontal position string are: 'left', 'center', or 'right'. The accepted values for the vertical position string are: 'top', 'center', or 'bottom'.
	 * @param array $offset The offset of the text after it is positioned in the form offset_x,offset_y.
	 * @param int $opacity The transparency of the text, ranging from 0 (completely transparent) to 100 (completely opaque).
	 * @param string $shadow_color The shadow text color. Can be a 3 or 6 digit hexadecimal number (with or without the #). The default is #000000. If not specified to a valid hexadecimal color value, the shadow text will not be applied.
	 * @param mixed $shadow_offset The offset of the shadow text (in pixels), in the form shadow_offset_x,shadow_offset_y. The default shadow offset is 1,1.
	 * @param int $shadow_opacity The transparency of the shadow text, ranging from 0 (completely transparent) to 100 (completely opaque). Defaults to 50.
	 * @return bool|resource False on failure. An image of the text on success
	 */
	private function add_text( $old_image, $text = '', $font_size = 12, $line_height = 15, $font_color = '000', $font_path = '', $text_align = 'center', $width_adjustment = 0, $position = array( 'center, center'), $offset = array( 0, 0 ), $opacity = 100, $shadow_color = '', $shadow_offset = array( 1, 1 ), $shadow_opacity = 100 )
	{
		//convert html entities to their text equivalent
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		//set the adjusted width
		$width = $this->width_final + $width_adjustment;
		if ( $width <= 0 ) //check the width
		{
			return $old_image;
		}

		if ( $this->is_transparent )
		{
			$image = imagecreatetruecolor( $this->width_final, $this->height_final );
			imagesavealpha( $image, true );
			imagefill( $image, 0, 0, imagecolorallocatealpha( $image, 0, 0, 0, 127 ) );
		}
		else
		{
			$image = $old_image;
		}

		$words = explode( ' ', $text ); //break up the text into words
		$lines[0] = ''; //the lines
		$current = 0; //the current line index

		//separate the words to lines
		foreach ( $words as $index => $word )
		{
			if ( $word == '\n' )
			{
				$current++;
				$lines[ $current ] = '';
				$current++;
				$lines[ $current ] = '';
				continue;
			}

			$temp = ( $index != 0 ) ? ' ' . $word : $word;
			$line_box = imagettfbbox( $font_size, 0, $font_path, $lines[ $current ] . $temp );
			if ( $line_box[2] - $line_box[0] < $width || $index == 0 ) //if the lower right x - the lower left x < image width
			{
				$lines[ $current ] .= $temp; //add the word to the line
			}
			else
			{
				$current++; //increase the line
				$lines[ $current ] = $word; //add the word to the new line
			}
		}

		//prep the color
		$color = Ce_image_tools::hex_to_rgb( $font_color ); //color rgb from color hex
		$alpha = round( 127 - $opacity * .01 * 127 );
		if ( $alpha < 0 )
		{
			$alpha = 0;
		}
		else if ($alpha > 127)
		{
			$alpha = 127;
		}
		$font_color = imagecolorallocatealpha( $image, $color[0], $color[1], $color[2], $alpha ); //allocate the color

		//check the text shadow settings
		if ( $shadow_opacity == 0 )
		{
			$shadow_color = false;
		}
		if ( $shadow_color !== false )
		{
			$shadow_color = Ce_image_tools::hex_to_rgb( $shadow_color ); //color rgb from color hex
			$alpha = round( 127 - $shadow_opacity * .01 * 127 );
			if ( $alpha < 0 )
			{
				$alpha = 0;
			}
			else if ($alpha > 127)
			{
				$alpha = 127;
			}
			$shadow_font_color = imagecolorallocatealpha( $image, $shadow_color[0], $shadow_color[1], $shadow_color[2], $alpha ); //allocate the color
		}

		//determine the final height
		$height = count( $lines ) * $line_height;

		$start_x = 0;
		$start_y = 0;
		//position
		switch( $position[0] ) //horizontal position
		{
			case 'left':
				$start_x = 0;
				break;
			case 'right':
				$start_x = $this->width_final - $width;
				break;
			default:
				$start_x = ($this->width_final - $width) * .5;
		}

		switch( $position[1] ) //vertical position
		{
			case 'top':
				$start_y = 0;
				break;
			case 'bottom':
				$start_y = $this->height_final - $height;
				break;
			default:
				$start_y = ($this->height_final - $height) * .5;
		}

		//offset
		$start_x += $offset[0];
		$start_y += $offset[1];

		//add the lines to the image
		foreach ( $lines as $index => $line )
		{
			$line_box = imagettfbbox( $font_size, 0, $font_path, $line );
			$line_width = abs( $line_box[0] ) + abs( $line_box[4] ); //lower left x + upper right x

			switch ( $text_align )
			{
				case 'left':
					$line_x = 0;
					break;
				case 'right':
					$line_x = round($width) - round($line_width);
					break;
				case 'center':
				default:
					$line_x = round( ( $width - $line_width ) * .5 ); //centers the text
			}

			$line_x += $start_x; //add in the position and offset

			$line_y = ( ( $line_height ) * ( $index + 1) ) + $start_y; //spaces the text vertically, taking into account the positioning and offset

			//add in the text shadow
			if ( $shadow_color !== false )
			{
				imagettftext( $image, $font_size, 0, $line_x + $shadow_offset[0], $line_y + $shadow_offset[1], $shadow_font_color, $font_path, $line );
			}

			//add the text
			imagettftext( $image, $font_size, 0, $line_x, $line_y, $font_color, $font_path, $line );
		}

		if ( $this->is_transparent )
		{
			$this->overlay_image( $old_image, $image, 0, 0, $this->width_final, $this->height_final );
		}

		return $old_image;
	}

	/**
	 * Prepares an image for the edgify filter and calls the edgify filter.
	 *
	 * @param resource $image The image to apply the filter to.
	 * @param int $threshold Lower thresholds will show more lines. Range from 0 to 255 (defaults to 40).
	 * @param string $fg_color Hexadecimal color value (3 or 6 digits) or ''(transparent).
	 * @return resource The image with the filter applied.
	 */
	private function prep_edgify( $image, $threshold = 40, $fg_color = null )
	{
		$threshold = ( is_null( $threshold ) ) ? 40 : $threshold ;

		if ( $this->is_transparent && $this->bg_color == false ) //transparent
		{
			$bg_transparency = 127;
		}
		else
		{
			$bg_transparency = 0;
		}

		$bg_color = ( $this->bg_color == false ) ? 'ffffff' : $this->bg_color;

		if ( is_null( $fg_color ) || $fg_color == '' )
		{
			$fg_transparency = 127;
			$fg_color = '000000';
		}
		else
		{
			$fg_transparency = 0;
		}

		$bg_color = Ce_image_tools::hex_cleanup( $bg_color );
		if ( $bg_color == '' )
		{
			$bg_color == 'ffffff';
		}
		$fg_color = Ce_image_tools::hex_cleanup( $fg_color );
		if ( $fg_color == '' )
		{
			$fg_color == '000000';
		}

		return $this->edgify( $image, $threshold, $bg_color, $fg_color, $bg_transparency, $fg_transparency);
	}

	/**
	 * Saves the manipulated image to the specified format.
	 *
	 * @param string $type
	 * @return bool Returns true if successful and false otherwise.
	 */
	private function save( $type )
	{
		//save to cache folder
		//check if the directory exists
		if ( ! @is_dir( $this->cache_full ) )
		{
			//turn the cache path into an array of directories
			$directories = explode( '/', substr( $this->cache_full, strlen( $this->base ) ) );

			//assign the current variable
			$current = $this->base;

			//start with base, and add each directory and make sure it exists with the proper permissions
			foreach ( $directories as $directory )
			{
				$current .= '/' . $directory;

				//check if the directory exists
				if ( ! @is_dir( $current ) )
				{
					//try to make the directory with full permissions
					if ( ! @mkdir( $current . '/', $this->dir_permissions, true ) )
					{
						$this->debug_messages[] = "Could not create the cache directory '{$current}'.";
						break;
					}
				}
			}

			/*
			//try to make the directory
			if ( ! @mkdir( $this->cache_full, $this->dir_permissions, true ) )
			{
				$this->debug_messages[] = "Could not create the cache directory '$this->cache_full'";
				return false;
			}
			*/
		}
		//ensure the directory is writable
		if ( ! @is_writable( $this->cache_full ) )
		{
			$this->debug_messages[] = "Cache directory '$this->cache_full' is not writable.";
			return false;
		}

		//save as desired type
		$type = strtolower( $type );
		switch ( $type )
		{
			case 'gif':
				$result = imagegif( $this->handle, $this->path_final );
				break;
			case 'png':
				//try to losslessly compress the image as much as possible. Higher compression may take a little longer, but this will only need to happen once as the image will be cached.
				if ( version_compare(PHP_VERSION, '5.1.2', '>=') )
				{
					$result = ( version_compare(PHP_VERSION, '5.1.3', '>=') && defined( 'PNG_ALL_FILTERS' ) ) ? imagepng( $this->handle, $this->path_final, 9, PNG_ALL_FILTERS ) : imagepng( $this->handle, $this->path_final, 9 );
				}
				else
				{
					$result = imagepng( $this->handle, $this->path_final );
				}
				break;
			case 'jpg':
			case 'jpeg':
			default:
				$result = imagejpeg( $this->handle, $this->path_final, $this->quality );
				$type = 'jpg';
				break;
		}

		if ( ! $result )
		{
			$this->debug_messages[] =  'There was a problem saving your file to ' . strtolower( $type ) . ' format.';
			return false;
		}
		else
		{
			$this->debug_messages[] =  "Image saved to {$this->path_final}.";
		}

		//saved hook
		if ($this->EE->extensions->active_hook('ce_img_saved'))
		{
			$this->EE->extensions->call('ce_img_saved', $this->path_final, $type );
		}

		//attempt to set permission
		if ( ! @chmod( $this->path_final, $this->image_permissions ) )
		{
			$this->debug_messages[] = "File permissions for '$this->path_final' could not be changed to '$this->image_permissions'.";
		}

		return true;
	}


	/**
	 * Converts an image to ASCII art
	 *
	 * @param resource $image The image to generate ASCII art for.
	 * @param bool $use_colors Whether or not to colorize the characters.
	 * @param array $ascii_characters The array of characters to be used for the ASCII art.
	 * @param bool $repeat Whether or not the characters should repeat in consecutive order (true) or be placed depending on the darkness of the pixel (false).
	 * @param bool $space_for_trans If the $repeat parameter is set to true, you can set this parameter to determine whether or not a space should be used for transparent pixels.
	 * @return string The HTML for the ASCII art.
	 */
	private function create_ascii_art( $image, $use_colors = true, $ascii_characters = array('#', '@', '%', '=', '+', '*', ':', '-', '.', '&nbsp;'), $repeat = false, $space_for_trans = false  )
	{
		//dimensions
		$width = imagesx( $image );
		$height = imagesy( $image );

		//art
		$num_asciis = count($ascii_characters);
		$size = 1 / $num_asciis;

		//background color
		$bg_rgb = Ce_image_tools::hex_to_rgb( $this->bg_color, $this->bg_color_default );

		$ascii_image = '';

		$index = -1;
		for ( $y = 0; $y < $height; $y++ ) //rows
		{
			$row = '';
			for ( $x = 0; $x < $width; $x++ ) //columns
			{
				//get the color info for the pixel
				//$rgb = @imagecolorat($image, $x, $y);
				$current_color = @imagecolorat($image, $x, $y);
				$r = $current_color >> 16 & 0xFF;
				$g = $current_color >> 8 & 0xFF;
				$b = $current_color & 0xFF;
				$a = $current_color >> 24 & 0xFF;

				//blend foreground and background colors
				$alpha_inverse = $a / 127;
				$alpha_level = 1 - $alpha_inverse;

				$r = round( $r * $alpha_level + $bg_rgb[0] * $alpha_inverse );
				$g = round( $g * $alpha_level + $bg_rgb[1] * $alpha_inverse );
				$b = round( $b * $alpha_level + $bg_rgb[2] * $alpha_inverse );

				if ( $repeat == false )
				{
					//to grayscale
					$gray = ( 0.299 * $r + 0.587 * $g + 0.114 * $b );
					$black_fraction = $gray / 255; //determine amount of black


					//find the best character
					for ($index = 0; $index < $num_asciis; $index++ )
					{
						if ( $black_fraction < ($index + 1) * $size )
						{
							break; //this is the character index
						}
					}
					$index = min( $index, $num_asciis - 1 ); //verify index
					unset( $gray );
					unset( $black_fraction );
				}
				else
				{
					$index++;
					if ( $index >= $num_asciis )
					{
						$index = 0;
					}
				}

				//changed from == 127 to >= 126 because sometimes GD on Ubuntu does not preserve the transparency completely after an image is resized. Weird.
				if (  $repeat && $space_for_trans && $a >= 126 )
				{
					$temp = '&nbsp;';
				}
				else
				{
					$temp = $ascii_characters[$index];
				}

				if ( $use_colors )
				{
					$temp = '<span style="color:' . Ce_image_tools::rgb_to_hex($r,$g,$b) . ';">' . $temp . '</span>';
				}

				unset( $current_color );
				unset( $r );
				unset( $g );
				unset( $b );

				$row .= $temp;
				unset( $temp );
			}
			$ascii_image .= "$row<br />" . PHP_EOL;
			unset( $row );
		}

		//return original image
		return $ascii_image;
	}

	/**
	 * Gets the average color for the image.
	 * @param resource $old_image
	 * @return string The hexadecimal color value of the average color of the generated image.
	 */
	private function find_average_color( $old_image )
	{
		$width = imagesx( $old_image );
		$height = imagesy( $old_image );
		$resized = false;

		//size the image down to improve performance
		if ( $width > 100 && $height > 100 )
		{
			//scale image down
			$width_new = 100;
			$height_new = 100;
			$ratio_x = round( $width / $width_new, 2 );
			$ratio_y = round( $height / $height_new, 2);
			if ( $ratio_x < $ratio_y )
			{
				$width_new = round( $width / ( $height / $height_new ));
			}
			else
			{
				$height_new = round( $height / ( $width / $width_new ));
			}

			$image = $this->prep_transparency($old_image, $width_new, $height_new, false);
			if ( @imagecopyresampled( $image, $old_image, 0, 0, 0, 0, $width_new, $height_new, $width, $height ) == false )
			{
				//there was an error resizing, so use the old image
				$image = $old_image;
			}
			else
			{
				//switch image, and width and height
				$resized = true;
				$width = $width_new;
				$height = $height_new;
			}
		}
		else
		{
			$image = $old_image;
		}
		$r = 0;
		$g = 0;
		$b = 0;
		$pixels = 0;
		for( $y = 0; $y < $height; $y++ )
		{
			for( $x = 0; $x < $width; $x++ )
			{
				$rgb = imagecolorat( $image, $x, $y );
				$a = $rgb >> 24 & 0xFF;
				if ( $a != 127 )
				{
					//attempt to account for transparency
					$r += ($rgb >> 16 & 0xFF);
					$g += ($rgb >> 8 & 0xFF);
					$b += ($rgb & 0xFF);
					$pixels++;
				}
				unset( $rgb );
				unset( $a );
			}
		}
		if ( $resized )
		{
			imagedestroy( $image );
		}

		$final = Ce_image_tools::rgb_to_hex( round($r / $pixels), round($g / $pixels), round($b / $pixels) );
		return $final;
	}

	/**
	 * Organizes the colors used in your image by frequency of occurrence, and groups them by a threshold.
	 *
	 * @param resource $old_image The image to find the top colors for.
	 * @param int $how_many The maximum number of colors (color groups) to return.
	 * @param int $threshold Value from 0 (very low grouping) to 100 (very high grouping).
	 * @return array|bool On success, returns an array of results with each result being an array with the following keys: 'color', 'color_count', 'color_percent'. On failure, returns false.
	 */
	private function find_top_colors($old_image, $how_many = 5, $threshold = 33)
	{
		if ( $how_many === 0 || ! is_numeric( $how_many) )
		{
			return false;
		}
		$width = imagesx( $old_image );
		$height = imagesy( $old_image );
		$resized = false;

		//size the image down to improve performance
		if ( $width > 100 && $height > 100 )
		{
			//scale image down
			$width_new = 100;
			$height_new = 100;
			$ratio_x = round( $width / $width_new, 2 );
			$ratio_y = round( $height / $height_new, 2);
			if ( $ratio_x < $ratio_y )
			{
				$width_new = round( $width / ( $height / $height_new ));
			}
			else
			{
				$height_new = round( $height / ( $width / $width_new ));
			}

			$image = $this->prep_transparency($old_image, $width_new, $height_new, false);
			if ( @imagecopyresampled( $image, $old_image, 0, 0, 0, 0, $width_new, $height_new, $width, $height ) == false )
			{
				//there was an error resizing, so use the old image
				$image = $old_image;
			}
			else
			{
				//switch image, and width and height
				$resized = true;
				$width = $width_new;
				$height = $height_new;
			}
		}
		else
		{
			$image = $old_image;
		}

		//convert threshold value of 0-100 to 0-255
		$threshold = round( 255 / 100 * $threshold );

		//check threshold
		if ( $threshold < 1)
		{
			$threshold = 1;
		}
		else if ($threshold > 255)
		{
			$threshold = 255;
		}
		$threshold_counts = array();
		$threshold_colors = array();
		$top_colors = array();

		for( $y = 0; $y < $height; $y++ )
		{
			for( $x = 0; $x < $width; $x++ )
			{
				$rgb = imagecolorat( $image, $x, $y );
				$a = $rgb >> 24 & 0xFF;
				if ( $a == 127 )
				{
					continue;
				}
				$r = $rgb >> 16 & 0xFF;
				$g = $rgb >> 8 & 0xFF;
				$b = $rgb & 0xFF;

				$tr = floor( $r / $threshold) * $threshold;
				$tg = floor( $g / $threshold) * $threshold;
				$tb = floor( $b / $threshold) * $threshold;

				//get the color
				$tcolor = Ce_image_tools::rgb_to_hex( round($tr), round($tg), round($tb), false );

				//add the color to the array
				if ( ! isset( $threshold_counts[$tcolor] ) )
				{
					$threshold_counts[$tcolor] = 1;
					$threshold_colors[$tcolor] = array( 'r' => $r, 'g' => $g, 'b' => $b );
				}
				else
				{
					$threshold_counts[$tcolor]++;
					$threshold_colors[$tcolor]['r'] += $r;
					$threshold_colors[$tcolor]['g'] += $g;
					$threshold_colors[$tcolor]['b'] += $b;
				}
			}
		}

		if ( $resized )
		{
			imagedestroy( $image );
		}

		foreach ( $threshold_counts as $average => $count )
		{
			$color = Ce_image_tools::rgb_to_hex( round( $threshold_colors[$average]['r'] / $count ), round( $threshold_colors[$average]['g'] / $count ), round( $threshold_colors[$average]['b'] / $count ) );
			$top_colors[$color] = $count;
			unset( $color );
		}

		unset( $threshold_counts );
		unset( $threshold_colors );

		//sort the keys, get the keys, and limit to the requested number of items
		arsort( $top_colors );

		// array_keys( $top_colors )
		$top_colors =  array_slice( $top_colors, 0, $how_many );
		$colors = array();
		$pixels = $width * $height;
		foreach ( $top_colors as $index => $times )
		{
			$colors[] = array( 'color' => $index, 'color_count' => $times, 'color_percent' => round( $times / $pixels * 100, 1) );
		}

		unset( $top_colors );
		unset( $pixels );

		//return the number of items requested
		return $colors;
	}

	/**
	 * Colorized emboss filter.
	 *
	 * @param resource $image
	 * @return resource
	 */
	private function emboss_color( $image )
	{
		if ( function_exists('imageconvolution') ) //PHP >= 5.1
		{
			imageconvolution( $image, array(
				array( 1,  1, -1 ),
				array( 1,  1, -1 ),
				array( 1, -1, -1 )
			), 1, 0);
		}

		return $image;
	}

	/**
	 * Adds noise.
	 *
	 * @param resource $image The original image.
	 * @param int $level The noise level.
	 * @return resource $image The manipulated image.
	 */
	private function noise( $image, $level = 30 )
	{
		//get the width and height
		$width = imagesx( $image );
		$height = imagesy( $image );

		for ($x = 0; $x < $width; ++$x)
		{
			for ($y = 0; $y < $height; ++$y)
			{
				if (rand(0, 1))
				{
					//modified color
					$modifier = rand( $level * -1, $level);

					//current color
					$color = imagecolorat( $image, $x, $y );
					$a = ($color >> 24 & 0xFF);
					if ( $a == 127 )
					{
						continue;
					}

					//add noise
					$a += $modifier;
					$r = ($color >> 16 & 0xFF) + $modifier;
					$g = ($color >> 8 & 0xFF) + $modifier;
					$b = ($color & 0xFF) + $modifier;

					//check values
					if ($r > 255) $r = 255;
					if ($r < 0) $r = 0;
					if ($g > 255) $g = 255;
					if ($g < 0) $g = 0;
					if ($b > 255) $b = 255;
					if ($b < 0) $b = 0;
					if ($a > 127) $a = 127;
					if ($a < 0) $a = 0;

					//create and set new color
					$color = imagecolorallocatealpha($image, $r, $g, $b, ( $this->is_transparent ) ? $a : 0);
					imagesetpixel($image, $x, $y, $color);
				}
			}
		}

		return $image;
	}

	/**
	 * Scatters the pixels in an image.
	 *
	 * @param resource $image The original image.
	 * @param int $level The scatter level.
	 * @return resource $image The manipulated image.
	 */
	private function scatter( $image, $level = 4 )
	{
		//get the width and height
		$width = imagesx( $image );
		$height = imagesy( $image );

		//loop through all of the pixels
		for ($x = 0; $x < $width; ++$x)
		{
			for ($y = 0; $y < $height; ++$y)
			{
				//get the random offsets
				$offset_x = rand( $level * -1, $level);
				$offset_y = rand( $level * -1, $level);

				//make sure we're in bounds
				if ($x + $offset_x >= $width || $x + $offset_x < 0 || $y + $offset_y >= $height || $y + $offset_y < 0)
				{
					continue;
				}

				//swap the pixels
				$color = imagecolorat($image, $x, $y);
				$color_new = imagecolorat($image, $x + $offset_x, $y + $offset_y);
				imagesetpixel($image, $x, $y, $color_new);
				imagesetpixel($image, $x + $offset_x, $y + $offset_y, $color);
			}
		}

		return $image;
	}

	/**
	 * Auto-sharpen the image. It's magic. PHP >= 5.1
	 *
	 * @param resource $image The original image.
	 * @return resource $image The manipulated image.
	 */
	private function auto_sharpen( $image )
	{
		if ( function_exists('imageconvolution') )
		{
			$width = imagesx( $image );
			$width_orig = $this->image_data_orig['width'];

			//if the image size has not been reduced, then no need to proceed
			if ( $width >= $width_orig )
			{
				return $image;
			}

			$ratio = $width / $width_orig;
			$value = 11 + round( 7 * $ratio );

			$matrix = array(
				array(0, -1, 0),
				array(-1, $value , -1),
				array(0, -1, 0)
			);

			imageconvolution($image, $matrix, array_sum(array_map('array_sum', $matrix)), 0);
		}

		return $image;
	}

	/**
	 * A wrapper to easily call the gaussian_blur filter multiple times.
	 *
	 * @param resource $image The original image.
	 * @param int $passes
	 * @return resource The manipulated image.
	 */
	private function gaussian_blur( $image, $passes = 1 )
	{
		$passes = (int) $passes;
		if ($passes < 1)
		{
			$passes = 1;
		}

		for ($i = 0; $i < $passes; $i++)
		{
			imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
		}

		return $image;
	}

	/**
	 * A wrapper to easily call the selective_blur filter multiple times.
	 *
	 * @param resource $image The original image.
	 * @param int $passes
	 * @return resource The manipulated image.
	 */
	private function selective_blur( $image, $passes = 1 )
	{
		$passes = (int) $passes;
		if ($passes < 1)
		{
			$passes = 1;
		}

		for ($i = 0; $i < $passes; $i++)
		{
			imagefilter($image, IMG_FILTER_SELECTIVE_BLUR);
		}

		return $image;
	}

	/**
	 * Dot image filter.
	 *
	 * @param resource $image The original image.
	 * @param int $block The block size.
	 * @param string $color Optional hexadecimal color. If left blank, the image will be created in color.
	 * @param string $type The dot type. Can be 'square' or simply 's' for square, or anything else for 'circle'.
	 * @param int $multiplier Adjust the dot size ratio.
	 * @return resource $image The manipulated image.
	 */
	private function dot( $image, $block = 6, $color = '', $type = 'circle', $multiplier = 1 )
	{
		$type = strtolower( $type );
		if (  $type == 'square' )
		{
			$type = 's';
		}

		if ( ! isset( $block ) )
		{
			$block = 6;
		}
		$block = (int) $block;

		//make sure the block is valid side
		if ( $block < 2 )
		{
			return $image;
		}

		if ( ! isset( $multiplier ) )
		{
			$multiplier = 1;
		}

		//make sure the multiplier is valid
		if ( $multiplier <= 0 )
		{
			$multiplier = .1;
		}

		//get the width and height
		$width = imagesx( $image );
		$height = imagesy( $image );

		//determine the ratio
		$ratio = $block * 0.00784313725 * $multiplier;

		$block_half = $block * .5;

		//create the new image
		$new_image = imagecreatetruecolor($width, $height);
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
		$bg_rgb = Ce_image_tools::hex_to_rgb( $this->bg_color, $this->bg_color_default );
		$background = imagecolorallocatealpha($new_image, $bg_rgb[0], $bg_rgb[1], $bg_rgb[2], ( $this->is_transparent && empty( $this->background_color ) ) ? 127 : 0 );
		imagefilledrectangle($new_image, 0, 0, $width, $height, $background);

		//determine color
		if ( isset( $color ) && $color !== '' )
		{
			$main_color = Ce_image_tools::hex_to_rgb( $color, '000' );
		}

		//there is no previous color to start off
		$current_level = null;

		//loop through the pixels and create the new image
		for ($y = 0; $y < $height + $block; $y += $block)
		{
			for ( $x = 0; $x < $width + $block; $x += $block )
			{
				//save previous current level to the last
				$last_level = $current_level;

				//determine the color and radius of the block
				$r = $g = $b = $a = $count = 0;
				for ($yy = $y; $yy < $y + $block; ++$yy)
				{
					if ( $yy >= $height)
					{
						continue;
					}
					for ($xx = $x; $xx < $x + $block; ++$xx)
					{
						if ( $xx >= $width )
						{
							continue;
						}

						$temp = imagecolorat($image, $xx, $yy);
						$alpha = $temp >> 24 & 0xFF;
						if ( $alpha == 127 )
						{
							continue;
						}
						$count++;
						$r += $temp >> 16 & 0xFF;
						$g += $temp >> 8 & 0xFF;
						$b += $temp & 0xFF;
						$a += $alpha;
					}
				}

				if ( ! $count )
				{
					continue;
				}
				$r = round($r /= $count);
				$g = round($g /= $count);
				$b = round($b /= $count);
				$a = round($a /= $count);
				$new_color = isset( $main_color )
					? imagecolorallocatealpha($new_image, $main_color[0], $main_color[1], $main_color[2], ( $this->is_transparent ) ? $a : 0 )
					: imagecolorallocatealpha($new_image, $r, $g, $b, ( $this->is_transparent ) ? $a : 0 );

				$current_level = max( round( 255 - ( ( $r + $g + $b ) / 3 ) ), 0);
				$size = ( $x != 0 ) ? ($last_level + $current_level) * .5 * $ratio : $current_level * $ratio;

				//add the dot
				if ( $type == 's' )
				{
					$size *= 2/3; //makes the square and circle dot sizes a little more comparable
					$size_half = $size * .5;
					imagefilledrectangle ($new_image, $x + $block_half - $size_half, $y + $block_half - $size_half, $x + $block_half + $size_half, $y + $block_half + $size_half, $new_color);
				}
				else
				{
					imagefilledellipse($new_image, $x + $block_half, $y + $block_half, $size, $size, $new_color);
				}
			}
		}

		return $new_image;
	}


	/**
	 * Pixelate an image
	 *
	 * @param resource $image
	 * @param int $size
	 * @param null $advanced
	 * @return resource
	 */
	private function pixelate( $image, $size = 0, $advanced = null )
	{
		if ( $size == 0 || ! is_numeric( $size ) )
		{
			return $image;
		}

		//get the width and height
		$width = imagesx( $image );
		$height = imagesy( $image );

		$y = 0;
		$x = 0;
		while ( $x < $width ) //move the block horizontally
		{
			while ( $y < $height ) //move the block vertically
			{
				//block data
				$block_pixels = 0;
				$rs = array();
				$gs = array();
				$bs = array();
				$as = array();

				//get all the pixels in the block
				for ( $xi = $x; $xi < $x + $size; $xi++  )
				{
					if ( $xi >= $width )
					{
						continue;
					}

					for ( $yi = $y; $yi < $y + $size; $yi++ )
					{
						if ( $yi >= $height )
						{
							continue;
						}

						$block_pixels += 1;
						$color = imagecolorat( $image, $xi, $yi );
						$trans = $color >> 24 & 0xFF;
						$as[] = $trans;
						if ( $trans != 127 ) //only include the colors if the pixel is not completely transparent
						{
							$rs[] = $color >> 16 & 0xFF;
							$gs[] = $color >> 8 & 0xFF;
							$bs[] = $color & 0xFF;
						}
					}
				}

				//average the color data
				$r = array_sum( $rs ) / count( $rs );
				$g = array_sum( $gs ) / count( $gs );
				$b = array_sum( $bs ) / count( $bs );
				$a = array_sum( $as ) / count( $as );

				//create the new color
				$color_new = imagecolorallocatealpha($image, $r, $g, $b, ( $this->is_transparent ) ? $a : 0 );

				//set the color for each of the pixels
				for ( $xi = $x; $xi < $x + $size; $xi++  ) //get the chunk horizontally
				{
					if ( $xi >= $width )
					{
						continue;
					}
					for ( $yi = $y; $yi < $y + $size; $yi++ ) //get the chunk vertically
					{
						if ( $yi >= $height )
						{
							continue;
						}

						imagesetpixel( $image, $xi, $yi, $color_new );
					}
				}
				$y += $size;
			}
			$y = 0;
			$x += $size;
		}

		return $image;
	}

// ---------------------------------------------------------------------------- 3rd Party Methods ----------------------------------------------------------------------------
	/**
	 * Unsharp Mask for PHP - version 2.1.1
	 * Unsharp mask algorithm by Torstein Hønsi 2003-07. thoensi_at_netcom_dot_no.
	 * Please leave this notice.
	 *
	 * This method has some minor formatting and code changes by Aaron Waldon of Causing Effect for use with CE Image.
	 *
	 * @param resource $img The image to apply the filter to.
	 * @param int $amount How much of the effect you want, 100 is 'normal.' (typically 50 to 200, defaults to 80).
	 * @param float $radius Radius of the blurring circle of the mask. (typically 0.5 to 1, defaults to .5).
	 * @param mixed $threshold The least difference in color values that is allowed between the original and the mask. In practice this means that low-contrast areas of the picture are left unrendered whereas edges are treated normally. This is good for pictures of e.g. skin or blue skies. (typically 0 to 5, defaults to 3).
	 * @return resource The sharpened image.
	 */
	private function unsharp_mask( $img, $amount = 80, $radius = .5, $threshold = 3 )
	{
		$amount = ( is_null( $amount ) ? 80 : $amount );
  		$radius = ( is_null( $radius ) ? .5 : $radius );
		$threshold = ( is_null( $threshold ) ? 3 : $threshold );

		//Attempt to calibrate the parameters to Photoshop:
		if ( $amount > 500 )
		{
			$amount = 500;
		}
		$amount = $amount * 0.016;
		if ( $radius > 50 )
		{
			$radius = 50;
		}
		$radius = $radius * 2;
		if ( $threshold > 255 )
		{
			$threshold = 255;
		}
		$radius = abs( round($radius) ); //Only integers make sense.
		if ( $radius == 0 )
		{
			return $img;
			//imagedestroy($img);
			//break;
		}
		$w = imagesx($img);
		$h = imagesy($img);
		$imgCanvas = imagecreatetruecolor($w, $h);
		$imgBlur = imagecreatetruecolor($w, $h);

		//Gaussian blur matrix
		if ( function_exists('imageconvolution') ) //PHP >= 5.1
		{
			$matrix = array(
				array( 1, 2, 1 ),
				array( 2, 4, 2 ),
				array( 1, 2, 1 )
			);
			imagecopy($imgBlur, $img, 0, 0, 0, 0, $w, $h);
			imageconvolution($imgBlur, $matrix, 16, 0);
		}
		else
		{
			//Move copies of the image around one pixel at the time and merge them with weight
			//according to the matrix. The same matrix is simply repeated for higher radii.
			for ($i = 0; $i < $radius; $i++)
			{
				imagecopy ($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); //left
				imagecopymerge ($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); //right
				imagecopymerge ($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); //center
				imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);
				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); //up
				imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); //down
			}
		}

		if ( $threshold > 0 )
		{
			//Calculate the difference between the blurred pixels and the original
			//and set the pixels
			for ( $x = 0; $x < $w-1; $x++ ) //each row
			{
				for ( $y = 0; $y < $h; $y++ ) //each pixel
				{
					$rgbOrig = imagecolorat($img, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);
					$rgbBlur = imagecolorat($imgBlur, $x, $y);
					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					//When the masked pixels differ less from the original
					//than the threshold specifies, they are set to their original value.
					$rNew = ( abs($rOrig - $rBlur) >= $threshold ) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
					$gNew = ( abs($gOrig - $gBlur) >= $threshold ) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
					$bNew = ( abs($bOrig - $bBlur) >= $threshold ) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;
					if ( ($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew) )
					{
						$pixCol = imagecolorallocate($img, $rNew, $gNew, $bNew);
						imagesetpixel($img, $x, $y, $pixCol);
					}
				}
			}
		}
		else
		{
			for ( $x = 0; $x < $w; $x++ ) //each row
			{
				for ( $y = 0; $y < $h; $y++ ) //each pixel
				{
					$rgbOrig = imagecolorat($img, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);

					$rgbBlur = imagecolorat($imgBlur, $x, $y);
					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					$rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
					if ( $rNew > 255 )
					{
						$rNew=255;
					}
					else if ($rNew < 0)
					{
						$rNew = 0;
					}

					$gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
					if ( $gNew > 255 )
					{
						$gNew = 255;
					}
					else if ($gNew < 0)
					{
						$gNew = 0;
					}

					$bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
					if ( $bNew>255 )
					{
						$bNew=255;
					}
					else if ($bNew < 0)
					{
						$bNew = 0;
					}

					$rgbNew = ($rNew << 16) + ($gNew <<8) + $bNew;
					imagesetpixel($img, $x, $y, $rgbNew);
				}
			}
		}
		imagedestroy($imgCanvas);
		imagedestroy($imgBlur);
		return $img;
	}

	/*
	---------- MIT Style License applicable to the following 4 methods only: ----------
	Permission is hereby granted, free of charge, to any person obtaining a copy of these
	algorithms (the "Software"), to deal in the Software without restriction, including without
	limitation the rights to use, copy, modify, merge, publish, distribute, distribute with
	modifications, sublicense, and/or sell copies of the Software, and to permit persons to whom
	the Software is furnished to do so, subject to the following conditions:

	This permission notice shall be included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING
	BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE ABOVE COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES
	OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF
	OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
	*/

	/**
	* The following method is licensed under an MIT-style license, http://www.exorithm.com/home/show/license
	*
	* Round the corners of an image. Transparency and anti-aliasing are supported.
	*
	* Heavily modified by Aaron Waldon of Causing Effect, to include the ability to round 1 corner at a time and add borders that are anti-aliased on each side
	*
	* @version 0.1
	* @author Contributors at eXorithm, modified by Aaron Waldon (aaron@causingeffect.com)
	* @link http://www.exorithm.com/algorithm/view/round_corners Listing at eXorithm
	* @link http://www.exorithm.com/algorithm/history/round_corners History at eXorithm
	* @license for this function http://www.exorithm.com/home/show/license
	*
	* @param resource $image (GD image)
	* @param int $width The image width.
	* @param int $height The image height.
	* @param int $radius Radius of the rounded corners.
	* @param string $color (hex color code) Color of the background.
	* @param int $transparency Level of transparency. 0 is no transparency, 127 is full transparency.
	* @param int $top
	* @param int $left
	* @param int $border_width The width of the border
	* @param string $border_color Hexadecimal color of the border
	* @return resource GD image
	*/
	private function round_corners( $image, $width, $height, $radius, $color, $transparency, $top, $left, $border_width, $border_color )
	{
		$full_color = $this->allocate_color($image, $color, $transparency);
		$border_color = $this->allocate_color($image, $border_color, 0);

		$start_x = $left * ( $width - $radius - $border_width );
		$start_y = $top * ( $height - $radius - $border_width );
		$end_x = $start_x + $radius + $border_width;
		$end_y = $start_y + $radius + $border_width;

		$radius_origin_x = $left * ( $start_x - 1 ) + ( ! $left ) * $end_x;
		$radius_origin_y = $top * ( $start_y - 1 ) + ( ! $top ) * $end_y;

		for ( $x = $start_x; $x < $end_x; $x++ )
		{
			for ( $y = $start_y; $y < $end_y; $y++ )
			{
				$dist = sqrt( pow( $x - $radius_origin_x, 2 ) + pow( $y - $radius_origin_y, 2 ) );

				//anti-alias border inside
				if ( $dist > $radius + 1 && $dist <= $radius + $border_width + 1  )
				{
					imagesetpixel($image, $x, $y, $border_color);
				}
				else if ($dist > $radius && $dist <= $radius + 1)
				{
					$pct = 1 - ($dist - $radius);
					$color2 = $this->antialias_pixel($image, $x, $y, $border_color, $pct);
					//don't set the pixel there was an error with anti-aliasing
					if ($color2 !== false)
					{
						imagesetpixel($image, $x, $y, $color2);
					}
				}
				//anti-alias border outside
				if ( $dist > $radius + $border_width + 1 )
				{
					imagesetpixel($image, $x, $y, $full_color);
				}
				else if ($dist > $radius + $border_width && $dist <= $radius + $border_width + 1)
				{
					$pct = 1 - ($dist - $radius - $border_width);
					$color2 = $this->antialias_pixel($image, $x, $y, $full_color, $pct);
					//don't set the pixel there was an error with anti-aliasing
					if ($color2 !== false)
					{
						imagesetpixel($image, $x, $y, $color2);
					}
				}
			}
		}

		return $image;
	}

	/**
	* The following method is licensed under an MIT-style license, http://www.exorithm.com/home/show/license
	* allocate_color
	*
	* Helper function to allocate a color to an image. Color should be a 6-character hex string.
	*
	* @version 0.2
	* @author Contributors at eXorithm, modified by Aaron Waldon (Causing Effect)
	* @link http://www.exorithm.com/algorithm/view/allocate_color Listing at eXorithm
	* @link http://www.exorithm.com/algorithm/history/allocate_color History at eXorithm
	* @license for this function http://www.exorithm.com/home/show/license
	*
	* @param resource $image (GD image) The image that will have the color allocated to it.
	* @param string $color (hex color code) The color to allocate to the image.
	* @param int $transparency The level of transparency from 0 to 127.
	* @return mixed
	*/
	private function allocate_color( $image = null, $color, $transparency)
	{
		$r  = hexdec(substr($color, 0, 2));
		$g  = hexdec(substr($color, 2, 2));
		$b  = hexdec(substr($color, 4, 2));
		if ( $transparency > 127 )
		{
			$transparency = 127;
		}

		if ( $transparency <= 0 )
		{
			return imagecolorallocate($image, $r, $g, $b);
		}
		else
		{
			return imagecolorallocatealpha($image, $r, $g, $b, $transparency);
		}
	}

	/**
	 * The following method is licensed under an MIT-style license, http://www.exorithm.com/home/show/license
	 * antialias_pixel
	 *
	 * Helper function to apply a certain weight of a certain color to a pixel in an image. The index of the resulting color is returned.
	 *
	 * @version 0.1
	 * @author Contributors at eXorithm, modified by Aaron Waldon (Causing Effect)
	 * @link http://www.exorithm.com/algorithm/view/antialias_pixel Listing at eXorithm
	 * @link http://www.exorithm.com/algorithm/history/antialias_pixel History at eXorithm
	 * @license for this function http://www.exorithm.com/home/show/license
	 *
	 * @param resource $image (GD image) The image containing the pixel.
	 * @param int $x X-axis position of the pixel.
	 * @param int $y Y-axis position of the pixel.
	 * @param int $color The index of the color to be applied to the pixel.
	 * @param float|int $weight Should be between 0 and 1,  higher being more of the original pixel color, and 0.5 being an even mixture.
	 * @return mixed
	 */
	//Modified by Aaron Waldon at Causing Effect to return false if a pixel is out of bounds
	private function antialias_pixel( $image, $x = 0, $y = 0, $color = 0, $weight = 0.5)
	{
		$c = @imagecolorsforindex( $image, $color );
		$r1 = $c['red'];
		$g1 = $c['green'];
		$b1 = $c['blue'];
		$t1 = $c['alpha'];

		//error suppression if the pixel is out of bounds
		$color2 = @imagecolorat($image, $x, $y);
		if ( $color2 === false )
		{
			return false;
		}
		$c = @imagecolorsforindex($image, $color2);
		$r2 = $c['red'];
		$g2 = $c['green'];
		$b2 = $c['blue'];
		$t2 = $c['alpha'];

		$cweight = $weight + ( $t1 / 127 ) * ( 1 - $weight ) - ( $t2 / 127 ) * ( 1 - $weight );

		$r = round( $r2 * $cweight + $r1 * ( 1 - $cweight ) );
		$g = round( $g2 * $cweight + $g1 * ( 1 - $cweight ) );
		$b = round( $b2 * $cweight + $b1 * ( 1 - $cweight ) );

		$t = round( $t2 * $weight + $t1 * ( 1 - $weight ) );

		return imagecolorallocatealpha( $image, $r, $g, $b, $t );
	}

	/**
	* The following method is licensed under an MIT-style license, http://www.exorithm.com/home/show/license
	* edgify
	*
	* Highlight the edges on an image using the Sobel Technique.
	*
	* Heavy modified by Aaron Waldon of Causing Effect, to include the ability to use different foreground and background colors
	*
	* @version 0.1
	* @author Contributors at eXorithm, modified by Aaron Waldon (Causing Effect)
	* @link http://www.exorithm.com/algorithm/view/edgify Listing at eXorithm
	* @link http://www.exorithm.com/algorithm/history/edgify History at eXorithm
	* @license http://www.exorithm.com/home/show/license
	*
	* @param resource $image (GD image)
	* @param int $threshold The threshold value. Lower is less picky about detecting an edge.
	* @param string $bg_color The hexadecimal background color.
	* @param string $fg_color The hexadecimal foreground color.
	* @param int $bg_transparency The background transparency.
	* @param int $fg_transparency The foreground transparency.
	* @return resource GD image
	*/
	private function edgify( $image, $threshold, $bg_color, $fg_color, $bg_transparency, $fg_transparency )
	{
		$bg_color = $this->allocate_color($image, $bg_color, $bg_transparency);
		$fg_color = $this->allocate_color($image, $fg_color, $fg_transparency);

		$height = imagesy($image);
		$width = imagesx($image);

		$new_image = imagecreatetruecolor($width, $height);
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
		$transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
		imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);

		if ( defined( 'IMG_FILTER_GRAYSCALE' ) )
		{
			@imagefilter($image, IMG_FILTER_GRAYSCALE );
		}
		else
		{
			return $image;
		}

		//add edges using sobel technique
		for ($x=0; $x < $width; $x++)
		{
			for ($y=0; $y < $height; $y++)
			{
				$x2 = $x+1;
				$y2 = $y+1;
				if ($x2>=$width)
				{
					$x2 = $x;
				}
				if ($y2>=$height)
				{
					$y2 = $y;
				}
				$p1 = imagecolorat($image,$x,$y2) & 0xFF;
				$p2 = imagecolorat($image,$x2,$y2) & 0xFF;
				$p3 = imagecolorat($image,$x,$y) & 0xFF;
				$p4 = imagecolorat($image,$x2,$y) & 0xFF;
				$h = abs($p1 - $p4);
				$k = abs($p2 - $p3);
				$g = $h + $k;
				if ($g > $threshold)
				{
					imagesetpixel($new_image, $x, $y, $fg_color);
				}
				else
				{
					imagesetpixel($new_image, $x, $y, $bg_color);
				}
			}
		}

		//free up memory
		imagedestroy($image);

		return $new_image;
	}
} /* End of class */







/**
 * Ce_image_tools
 *
 * @package CE Image
 * @author Causing Effect, Aaron Waldon
 * @link http://www.causingeffect.com
 * @copyright 2011
 * @license http://www.causingeffect.com/software/php/ce_img/license_agreement Causing Effect Commercial License Agreement
 */
class Ce_image_tools
{
	/**
	 * Converts a file size from bytes to a human readable format.
	 *
	 * A method derived from a function originally posted by xelozz -at- gmail.com 18-Feb-2010 10:34 to http://us2.php.net/manual/en/function.memory-get-usage.php#96280
	 * Original code licensed under: http://creativecommons.org/licenses/by/3.0/legalcode
	 *
	 * @static
	 * @param int $size Bytes.
	 * @param int $precision
	 * @return string Human readable file size.
	 */
	public static function convert( $size, $precision = 2 )
	{
		$negative = false;
		if ( $size < 0 )
		{
			$negative = true;
			$size *= -1;
		}

		$unit = array('b','KiB','MiB','GiB','TiB','PiB');
		$size = @round( $size / pow( 1024, ( $i = floor( log( $size, 1024 ) ) ) ), $precision ) . ' ' . $unit[$i];
		return ( $negative ) ? '-' . $size : $size;
	}

	/**
	 * Returns the number of bytes, even for strings that use g, gb, m, mb, k, or kb
	 *
	 * A method derived from a function originally posted by Ivo Mandalski 15-Nov-2011 01:27 http://us3.php.net/manual/en/function.ini-get.php#106518
	 * Original code licensed under: http://creativecommons.org/licenses/by/3.0/legalcode
	 *
	 * @static
	 * @param int:string $value
	 * @param string $return_as Can be 'b', 'k', 'm', or 'g'.
	 * @return int|string
	 */
	public static function return_bytes( $value, $return_as = 'b' )
	{
		//return if empty
		if ( empty( $value ) )
		{
			return 0;
		}

		//trim the value
		$value = trim( $value );

		//match the number and the characters (if applicable)
		preg_match( '#([0-9]+)[\s]*([a-z]+)#i', $value, $matches );

		//get the characters
		$last = '';
		if ( isset( $matches[ 2 ] ) )
		{
			$last = $matches[ 2 ];
		}

		//get the numbers
		if ( isset( $matches[ 1 ] ) )
		{
			$value = (int) $matches[ 1 ];
		}

		//determine the number of bytes
		switch ( strtolower( $last ) )
		{
			case 'g':
			case 'gb':
				$value *= 1024;
			case 'm':
			case 'mb':
				$value *= 1024;
			case 'k':
			case 'kb':
				$value *= 1024;
		}

		//determine the return format
		switch ( strtolower( $return_as ) )
		{
			case 'b':
				return (int) $value;
			case 'm':
			case 'mb':
				return (int) $value / ( 1024 * 1024 );
				break;
			case 'k':
			case 'kb':
				return (int) $value / ( 1024 );
				break;
			case 'g':
			case 'gb':
				return (int) $value / ( 1024 * 1024 * 1024 );
				break;
		}

		return 0;
	}

	/**
	 * Recursively implodes an array.
	 *
	 * A method derived from a function posted by kromped at yahoo dot com 09-Feb-2010 12:29 to http://www.php.net/manual/en/function.implode.php#96100
	 * Original code licensed under: http://creativecommons.org/licenses/by/3.0/legalcode
	 *
	 * @param string $glue
	 * @param array $pieces
	 * @return string The string of the recursively imploded array.
	 */
	public static function recursive_implode( $glue, $pieces )
	{
		$final = array();
		foreach( $pieces as $piece )
		{
			$final[] = ( ! is_array( $piece ) ) ? $piece : Ce_image_tools::recursive_implode( $glue, $piece );
		}
		return implode( $glue, $final );
	}

	/**
	 * Cleans up a hex value and converts it to RGB
	 *
	 * @static
	 * @param string $hex Hexadecimal color value.
	 * @param string $default_hex Fall-back hexadecimal color value.
	 * @return array|bool Returns an array on success with the values for red, green, and blue. Returns false on failure.
	 */
	public static function hex_to_rgb( $hex, $default_hex = '' )
	{
		$hex = Ce_image_tools::hex_cleanup( $hex );
		if ( $hex == false )
		{
			if ( $default_hex != '' )
			{
				return Ce_image_tools::hex_to_rgb( $default_hex );
			}
			else
			{
				return false;
			}
		}
		return sscanf($hex, '%2x%2x%2x');
	}

	/**
	 * Takes a 3 or 6 digit hex color value, strips off the # (if applicable), and returns a 6 digit hex or ''
	 *
	 * @static
	 * @param string $hex A 3 or 6 digit color value.
	 * @return string A 6 digit hex color value or ''.
	 */
	public static function hex_cleanup( $hex )
	{
		$hex = ( preg_match('/#?[0-9a-fA-F]{3,6}/', $hex) ) ? $hex : false;
		if ( ! $hex )
		{
			return false;
		}
		if ($hex[0] == '#')
		{
			$hex = substr($hex, 1);	//trim off #
		}
		if (strlen($hex) == 3)
		{
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if (strlen($hex) != 6)
		{
			$hex = false;
		}
		return $hex;
	}

	/**
	 * Converts RGB color values to a hexadecimal color format.
	 *
	 * @static
	 * @param int $red The red color value. Ranges from 0 to 255.
	 * @param int $green The green color value. Ranges from 0 to 255.
	 * @param int $blue The blue color value. Ranges from 0 to 255.
	 * @param bool $prepend_hash Whether or not to prepend '#' to the hex color value. Defaults to true.
	 * @return string The hexadecimal color.
	 */
	public static function rgb_to_hex($red, $green, $blue, $prepend_hash = true )
	{
		return (( $prepend_hash ) ? '#' : '') . str_pad(dechex($red), 2, '0', STR_PAD_LEFT) . str_pad(dechex($green), 2, '0', STR_PAD_LEFT) . str_pad(dechex($blue), 2, '0', STR_PAD_LEFT);
	}
}
/* End of class Ce_image_tools */
/* End of file Ce_image.php */