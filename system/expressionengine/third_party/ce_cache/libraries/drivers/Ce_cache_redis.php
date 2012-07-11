<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CE Cache - Redis driver.
 *
 * //TODO add link here
 *
 * @author		Aaron Waldon
 * @copyright	Copyright (c) 2011 Causing Effect
 * @license		http://www.causingeffect.com/software/expressionengine/ce-cache/license-agreement
 * @link		http://www.causingeffect.com
 */
class Ce_cache_redis extends Ce_cache_driver
{
	private $redis;
	private $is_setup = FALSE;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Is the driver supported?
	 *
	 * @return bool
	 */
	public function is_supported()
	{
		//make sure the extension is loaded
		return extension_loaded( 'redis' );
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
	 * Store a cache item. The item will be stored in an array with the keys 'ttl', 'made', and 'content'.
	 *
	 * @link http://redis.io/commands/set
	 * @param $id The cache item's id.
	 * @param string $content The content to store.
	 * @param int $seconds The time to live for the cached item in seconds. Zero (0) seconds will store the item for a long time. Default is 360 seconds.
	 * @return bool
	 */
	public function set( $id, $content = '', $seconds = 360 )
	{
		if ( ! $this->setup() )
		{
			return FALSE;
		}

		//create the data array
		$data = array(
			'ttl' => $seconds,
			'made' => time(),
			'content' => $content
		);

		$success = $this->redis->hMset( $id, $data );

		if ( $success )
		{
			if ( $seconds )
			{
				$this->redis->expire( $id, $seconds );
			}

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Retrieve an item from the cache.
	 *
	 * @link http://redis.io/commands/get
	 * @param $id The cache item's id.
	 * @return mixed
	 */
	public function get( $id )
	{
		if ( ! $this->setup() )
		{
			return FALSE;
		}

		//fetch the data
		$data = $this->redis->hmGet( $id, array( 'ttl', 'made', 'content' ) );

		//the data should be in the array format we left it in. If it isn't, we'll just return FALSE.
		return ( is_array( $data ) && isset( $data['content'] ) ) ? $data['content'] : FALSE;
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @link http://redis.io/commands/del
	 * @param $id The cache item's id.
	 * @return bool
	 */
	public function delete( $id )
	{
		if ( ! $this->setup() )
		{
			return FALSE;
		}

		return ( $this->redis->del( $id ) === 1 );
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
		if ( ! $this->setup() )
		{
			return FALSE;
		}

		//attempt to get the stored data
		$data = $this->redis->hmGet( $id, array( 'ttl', 'made', 'content' ) );

		//make sure the data is in the format we save data in
		if ( empty( $data ) || ! is_array( $data ) || count( $data ) != 3 )
		{
			return FALSE;
		}

		//determine the expiration timestamp
		$expiry = ( $data['ttl'] == 0 ) ? 0 : $data['made'] + $data['ttl'];

		//get the content size
		$size = parent::size( $data['content'] );

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
	 * @link http://redis.io/commands/flushall
	 * @return bool
	 */
	public function clear()
	{
		if ( ! $this->setup() )
		{
			return FALSE;
		}

		//clear the 'user' cache
		return $this->redis->flushAll();
	}

	/**
	 * Retrieves all of the cached items at the specified relative path.
	 *
	 * @link http://redis.io/commands/keys
	 * @param $relative_path The relative path from the cache base.
	 * @return array
	 */
	public function get_all( $relative_path )
	{
		if ( ! $this->setup() )
		{
			return FALSE;
		}

		//ensure a trailing slash - to maintain consistency with other drivers
		$relative_path = rtrim( $relative_path, '/' ) . '/';

		//get all keys that start with the path
		$keys = $this->redis->keys( $relative_path . '*' );

		$items = array();

		foreach ( $keys as $key )
		{
			$items[] = substr( $key, strlen( $relative_path ) );
		}

		sort( $items, SORT_STRING );

		return $items;
	}

	/**
	 * Retrieves basic info about the cache.
	 *
	 * @link http://redis.io/commands/info
	 * @return array|bool
	 */
	public function info()
	{
		if ( ! $this->setup() )
		{
			return FALSE;
		}

		return $this->redis->info();
	}

	/**
	 * Sets up the Redis object if it has not been setup already and adds any servers from the config or global array, or uses the default if none are found.
	 *
	 * @return bool Return TRUE if the class is setup, or FALSE on failure.
	 */
	private function setup()
	{
		if ( ! $this->is_setup ) //if not already setup
		{
			//servers
			$servers = array();

			//make sure this driver is supported
			if ( ! $this->is_supported() )
			{
				return FALSE;
			}

			//instantiate the class
			$redis = new Redis();

			//default server array
			$servers[] = array( '127.0.0.1' );

			//server config
			$config = '';

			//check for config settings
			if ( ! empty( $this->EE->config->_global_vars[ 'ce_cache_redis_servers' ] ) ) //first check the global array
			{
				$config = $this->EE->config->_global_vars[ 'ce_cache_redis_servers' ];
			}
			else if ( $this->EE->config->item( 'ce_cache_redis_servers' ) != FALSE ) //then check the config
			{
				$config = $this->EE->config->item( 'ce_cache_redis_servers' );
			}

			if ( ! empty( $config ) ) //we have some settings
			{
				//if the config is not an array, let's explode it out to be one
				if ( ! is_array( $config ) )
				{
					$servers = explode( '|', $config );
					foreach ( $servers as $index => $server )
					{
						$servers[$index] = explode( ',', $server );
					}
				}
				else
				{
					$servers = $config;
				}
			}

			$success = FALSE;

			//add each server
			foreach ( $servers as $server )
			{
				try
				{
					call_user_func_array( array( $redis, 'connect' ), $server );
					$success = TRUE;
				}
				catch ( RedisException $e ){}
			}

			if ( ! $success )
			{
				return FALSE;
			}

			$this->redis = $redis;

			//flag that setup has been completed
			$this->is_setup = TRUE;
		}

		return TRUE;
	}

	public function __destruct()
	{
		if ( $this->is_setup )
		{
			$this->redis->close();
		}
	}
}