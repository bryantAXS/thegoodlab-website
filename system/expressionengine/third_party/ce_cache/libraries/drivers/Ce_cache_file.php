<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CE Cache - File driver.
 *
 * @author		Aaron Waldon
 * @copyright	Copyright (c) 2011 Causing Effect
 * @license		http://www.causingeffect.com/software/expressionengine/ce-cache/license-agreement
 * @link		http://www.causingeffect.com
 */
class Ce_cache_file extends Ce_cache_driver
{
	private $cache_base = '';

	public function __construct()
	{
		parent::__construct();
		$this->EE->load->helper( 'file' );

		//set the base cache path
		$this->cache_base = str_replace( '\\', '/', APPPATH ) . 'cache/';

		//override this setting if set in the config or global vars array
		if ( isset( $this->EE->config->_global_vars[ 'ce_cache_file_path' ] ) && $this->EE->config->_global_vars[ 'ce_cache_file_path' ] !== FALSE ) //first check global array
		{
			$this->cache_base = $this->EE->config->_global_vars[ 'ce_cache_file_path' ];
		}
		else if ( $this->EE->config->item( 'ce_cache_file_path' ) !== FALSE ) //then check config
		{
			$this->cache_base = $this->EE->config->item( 'ce_cache_file_path' );
		}
	}

	/**
	 * Is the driver supported?
	 *
	 * @return bool
	 */
	public function is_supported()
	{
		return is_really_writable( rtrim( $this->cache_base, '/' ) );
	}

	/**
	 * The driver's name.
	 *
	 * @return mixed
	 */
	public function name()
	{
		return str_replace( 'Ce_cache_', '', __CLASS__ );
	}

	/**
	 * Store a cache item.
	 *
	 * @param $id The cache item's id.
	 * @param string $content The content to store.
	 * @param int $seconds The time to live for the cached item in seconds. Zero (0) seconds will result store the item for a long, long time. Default is 360 seconds.
	 * @return bool
	 */
	public function set( $id, $content = '', $seconds = 360 )
	{
		//create the data array
		$data = array(
			'ttl'		=> $seconds,
			'made'		=> time(),
			'content'	=> $content
		);

		//the file
		$file = $this->cache_base . $id;

		//figure out the base cache directory
		$base = rtrim( $this->cache_base, '/' );

		//figure out the directory path
		$directories = $file;
		if ( FALSE !== $pos = strrpos( $directories, '/' ) ) //get the substring before the last slash
		{
			//get the substring before the last 'segment'
			$directories = rtrim( substr( $directories, 0, $pos ), '/' );
		}
		else
		{
			//if there were no slashes in the id, we have bigger problems...
			return FALSE;
		}

		//create the directories with the correct permissions as needed
		if ( ! @is_dir( $directories ) )
		{
			//turn the directory path into an array of directories
			$directories = explode( '/', substr( $file, strlen( $base ) ) );

			//remove the last item, as it is not a directory
			array_pop( $directories );

			//assign the current variable
			$current = $base;

			//start with base, and add each directory and make sure it exists with the proper permissions
			foreach ( $directories as $directory )
			{
				$current .= '/' . $directory;

				//check if the directory exists
				if ( ! @is_dir( $current ) )
				{
					//try to make the directory with full permissions
					if ( ! @mkdir( $current . '/', 0777, TRUE ) )
					{
						$this->log_debug_message( __METHOD__, "Could not create the cache directory '$current/'." );
						break;
					}
				}
			}

			//ensure the directory is writable
			if ( ! is_really_writable( $current ) )
			{
				$this->log_debug_message( __METHOD__, "Cache directory '$current' is not writable." );
				//$this->cache->supported_drivers['file'] = FALSE;
				return FALSE;
			}
		}

		unset( $directories );

		//write the file
		if ( write_file( $file, @serialize( $data ) ) )
		{
			//set the file to full permissions
			@chmod( $file, 0777 );
			unset( $file, $data );
			return TRUE;
		}

		unset( $file, $data );

		return FALSE;
	}

	/**
	 * Retrieve an item from the cache.
	 *
	 * @param $id The cache item's id.
	 * @return mixed
	 */
	public function get( $id )
	{
		//the file does not exist
		if ( ! is_readable( $this->cache_base . $id ) )
		{
			return FALSE;
		}

		//the file exists read it
		$data = @file_get_contents( $this->cache_base . $id );

		if ( empty( $data ) )
		{
			return FALSE;
		}

		//try to unserialize the data
		$data = @unserialize( $data );

		//make sure the data is unserialized and in the expected format
		if ( empty( $data ) || ! is_array( $data ) || count( $data ) != 3 )
		{
			return FALSE;
		}

		//if seconds is set to 0 then the cache is never deleted, unless done so manually
		if ( $data['ttl'] != 0 && time() > $data['made'] + $data['ttl'] )
		{
			//the file has expired, get rid of it
			@unlink( $this->cache_base . $id );
			return FALSE;
		}

		//return the data
		return $data['content'];
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param $id The cache item's id.
	 * @return bool
	 */
	public function delete( $id )
	{
		//remove if the file exists
		if ( file_exists( $this->cache_base . $id ) )
		{
			return @unlink( $this->cache_base . $id );
		}

		return TRUE;
	}

	/**
	 * Gives information about the item.
	 *
	 * @param $id The cache item's id.
	 * @param bool $get_content Include the content in the return array?
	 * @return array|bool
	 */
	public function meta( $id, $get_content = TRUE )
	{
		$file = $this->cache_base . $id;

		//make sure the file exists and we get the data
		if ( ! file_exists( $file ) || FALSE === $data = read_file( $file ) )
		{
			return FALSE;
		}

		$data = @unserialize( $data );

		//make sure the data is unserialized and in the expected format
		if ( empty( $data ) || ! is_array( $data ) || count( $data ) != 3 )
		{
			return FALSE;
		}

		//if seconds is set to 0 then the cache is never deleted, unless done so manually
		if ( $data['ttl'] != 0 && time() > $data['made'] + $data['ttl'] )
		{
			//the file has expired, get rid of it
			unlink( $this->cache_base . $id );
			return FALSE;
		}

		//determine the expiration timestamp
		$expiry = ( $data['ttl'] == 0 ) ? 0 : $data['made'] + $data['ttl'];

		//get the content size
		$size = @filesize( $file );
		if ( $size === FALSE )
		{
			$size = parent::size( $data['content'] );
		}

		//return the meta array
		$final = array(
			//if the time to live is 0, the data will not auto-expire
			'expiry' => $expiry,
			'made' => $data['made'],
			'ttl' => $data['ttl'],
			'ttl_remaining' => ( $data['ttl'] == 0 ) ? 0 : ( $expiry - time() ),
			'size' => parent::convert_size( $size ),
			'size_raw' => $size
		);

		//include the content in the final array?
		if ( $get_content )
		{
			$final['content'] = $data['content'];
		}

		unset( $data, $expiry );

		return $final;
	}

	/**
	 * Purges the entire cache.
	 *
	 * @return bool
	 */
	public function clear()
	{
		//if the cache directory doesn't exist, consider the cache cleared
		if ( ! is_dir( $this->cache_base . 'ce_cache' ) )
		{
			return TRUE;
		}

		//delete files and directories
		delete_files( $this->cache_base . 'ce_cache', TRUE );

		//remove the base directory
		@rmdir( $this->cache_base . 'ce_cache' );

		return ! is_dir( $this->cache_base . 'ce_cache' );
	}

	/**
	 * Retrieves all of the cached items at the specified relative path.
	 *
	 * @param $relative_path The relative path from the cache base.
	 * @return array|bool
	 */
	public function get_all( $relative_path )
	{
		$path = rtrim( $this->remove_duplicate_slashes( $this->cache_base . $relative_path ), '/' );

		//check if the directory exists
		if ( ! @is_dir( $path ) )
		{
			return FALSE;
		}

		//will hold the final file path
		$files = array();

		$path_length = strlen( $path . '/' );

		$iterator = new RecursiveDirectoryIterator( $path );
		foreach( new RecursiveIteratorIterator( $iterator ) as $path => $current )
		{
			if ( $current->isFile() )
			{
				array_push( $files, substr( str_replace( '\\', '/', $path ), $path_length ) );
			}
		}

		return $files;
	}

	/**
	 * Retrieves basic info about the cache.
	 *
	 * @return array|bool
	 */
	public function info()
	{
		//TODO make this more useful
		return get_dir_file_info( $this->cache_base . 'ce_cache', FALSE );
	}
}