<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CE Cache - Module file.
 *
 * @author		Aaron Waldon
 * @copyright	Copyright (c) 2011 Causing Effect
 * @license		http://www.causingeffect.com/software/expressionengine/ce-cache/license-agreement
 * @link		http://www.causingeffect.com
 */

class Ce_cache
{
	//a reference to the instantiated class factory
	private $drivers;

	//debug mode flag
	private $debug = FALSE;

	//the relative directory path to be appended to the cache path
	private $id_prefix = '';

	//a flag to indicate whether or not the cache is setup
	public $is_cache_setup = FALSE;

	public static $is_404 = FALSE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		//if the template debugger is enabled, and a super admin user is logged in, enable debug mode
		$this->debug = FALSE;
		if ( $this->EE->session->userdata['group_id'] == 1 && $this->EE->config->item('template_debugging') == 'y' )
		{
			$this->debug = TRUE;
		}

		//include CE Cache Utilities
		if ( ! class_exists( 'Ce_cache_utils' ) )
		{
			include PATH_THIRD . 'ce_cache/libraries/Ce_cache_utils.php';
		}
	}

	/**
	 * This method will check if the cache id exists, and return it if it does. If the cache id does not exists, it will cache the data and return it.
	 * @return string
	 */
	public function it()
	{
		//setup the cache drivers if needed
		$this->setup_cache();

		//get the tagdata
		$tagdata = $this->no_results_tagdata();

		//get the id
		if ( FALSE === $id = $this->fetch_id( __METHOD__ ) )
		{
			return $tagdata;
		}

		//check the cache for the id
		$item = $this->get( TRUE );

		if ( $item === FALSE ) //the item could not be found for any of the drivers
		{
			//specify that we want the save method to return the content
			$this->EE->TMPL->tagparams['show'] = 'yes';

			//attempt to save the content
			return $this->save();
		}

		//the item was found, parse the action ids and return the item
		return $this->insert_action_ids( $item );
	}

	/**
	 * Save an item to the cache.
	 * @return string
	 */
	public function save()
	{
		//setup the cache drivers if needed
		$this->setup_cache();

		//did the user elect to ignore this tag?
		if ( strtolower( $this->EE->TMPL->fetch_param( 'ignore_if_dummy' ) ) == 'yes' && $this->drivers[0]->name() == 'dummy' ) //ignore this entire tag if the dummy driver is being used
		{
			return $this->EE->TMPL->no_results();
		}

		//don't cache googlebot requests, as it can caused problems by hitting an insane number of non-existant URLs
		if ( $this->EE->config->item( 'ce_cache_block_bots' ) != 'no' && $this->is_bot() )
		{
			$this->drivers = Ce_cache_factory::factory( array( 'dummy' ) );
			$this->is_cache_setup = FALSE;
		}

		//get the tagdata
		$tagdata = $this->no_results_tagdata();

		//trim the tagdata?
		$should_trim = strtolower( $this->determine_setting( 'trim', 'no' ) );
		$should_trim = ( $should_trim == 'yes' || $should_trim == 'y' || $should_trim == 'on' );

		//trim here in case the data needs to be returned early
		if ( $should_trim )
		{
			$tagdata = trim( $tagdata );
		}

		//get the id
		if ( FALSE === $id = $this->fetch_id( __METHOD__ ) )
		{
			return $tagdata;
		}

		//get the time to live (defaults to 60 minutes)
		$ttl = (int) $this->determine_setting( 'seconds', '3600' );

		//flag that caching is happening--important for escaped content
		$this->EE->session->cache[ 'Ce_cache' ]['is_caching'] = TRUE;

		//do we need to process the data?
		if ( $this->EE->TMPL->fetch_param( 'process' ) != 'no' ) //we need to process the data
		{
			//we're going to escape the logged_in and logged_out conditionals, since the Channel Entries loop adds them as variables.
			$tagdata = str_replace( array( 'logged_in', 'logged_out' ), array( 'ce_cache-in_logged', 'ce_cache-out_logged' ), $tagdata );

			//parse the data
			$tagdata = $this->parse_as_template( $tagdata );
		}

		$tagdata = $this->unescape_tagdata( $tagdata );

		//make sure the template debugger is not getting cached, as that is bad
		$debugger_pos = strpos( $tagdata, '<div style="color: #333; background-color: #ededed; margin:10px; padding-bottom:10px;"><div style="text-align: left; font-family: Sans-serif; font-size: 11px; margin: 12px; padding: 6px"><hr size=\'1\'><b>TEMPLATE DEBUGGING</b><hr' );
		if ( $debugger_pos !== FALSE )
		{
			$tagdata = substr_replace( $tagdata, '', $debugger_pos, -1 );
		}

		//trim again since the data may be much different now
		if ( $should_trim )
		{
			$tagdata = trim( $tagdata );
		}

		//loop through the drivers and try to save the data
		foreach ( $this->drivers as $driver )
		{
			if ( $driver->set( $id, $tagdata, $ttl ) === FALSE ) //save unsuccessful
			{
				$this->log_debug_message( __METHOD__, "Something went wrong and the data for '{$id}' was not cached using the " . $driver->name() . " driver." );
			}
			else //save successful
			{
				$this->log_debug_message( __METHOD__, "The data for '{$id}' was successfully cached using the " . $driver->name() . " driver." );

				//if we are saving the item for the first time, we are going to keep track of the drivers and ids, so we can clear the cached items later if this ends up being a 404 page
				if ( $driver->name() != 'dummy' )
				{
					$this->EE->session->cache[ 'Ce_cache' ]['cached_items'][] = array( 'driver' => $driver->name(), 'id' => $id );

					//register the shutdown function if needed
					if ( empty( $this->EE->session->cache[ 'Ce_cache' ][ 'shutdown_is_registered' ] ) )
					{
						//register the shutdown function
						register_shutdown_function( array( $this, 'shut_it_down' ) );

						$this->EE->session->cache[ 'Ce_cache' ][ 'shutdown_is_registered' ] = TRUE;
					}
				}

				break;
			}
		}

		//flag that caching is finished--important for escaped content
		$this->EE->session->cache[ 'Ce_cache' ]['is_caching'] = FALSE;

		//tag the content if applicable
		$temp = $this->EE->TMPL->fetch_param( 'tags' );
		if ( $temp !== FALSE )
		{
			$temps = explode( '|', $temp );

			$data = array();

			//loop through the items
			foreach ( $temps as $temp )
			{
				$temp = trim( strtolower( $temp ) );
				if ( $temp == '' )
				{
					$this->log_debug_message( __METHOD__, 'An empty tag was found and will not be applied to the saved item "' . $id . '".' );
					continue;
				}

				if ( strlen( $temp ) > 100 )
				{
					$this->log_debug_message( __METHOD__, 'The tag "' . $temp . '" could not be saved for the "' . $id . '" item, because it is over 100 characters long.' );
					continue;
				}

				$data[] = array( 'item_id' => $id, 'tag' => $temp );
			}
			unset( $temps );

			//delete all of the current tags for this item
			$this->EE->db->query( 'DELETE FROM exp_ce_cache_tagged_items WHERE item_id = ?', array( $id ) );

			//add in the new tags
			if ( count( $data ) > 1 )
			{
				$this->EE->db->insert_batch( 'ce_cache_tagged_items', $data );
			}
			else if ( count( $data ) > 0 )
			{
				$this->EE->db->insert( 'ce_cache_tagged_items', $data[0] );
			}

			unset( $data );
		}

		if ( $this->EE->TMPL->fetch_param( 'show' ) == 'yes' )
		{
			//parse any segment variables
			return $this->parse_vars( $tagdata );
		}

		unset( $tagdata );

		return '';
	}

	/**
	 * Escapes the passed-in content so that it will not be parsed before being cached.
	 * @return string
	 */
	public function escape()
	{
		$tagdata = FALSE;

		//if there is pre_escaped tagdata, use it
		$tag_parts = $this->EE->TMPL->tagparts;
		if ( is_array( $tag_parts ) && isset( $tag_parts[2] ) )
		{
			if ( isset( $this->EE->session->cache[ 'Ce_cache' ]['pre_escape'][ 'id_' . $tag_parts[2] ] ) )
			{
				$tagdata = $this->EE->session->cache[ 'Ce_cache' ]['pre_escape'][ 'id_' . $tag_parts[2] ];
			}
		}

		if ( $tagdata === FALSE ) //there was no pre-escaped tagdata, get the no_results tagdata
		{
			$tagdata = $this->no_results_tagdata();
		}

		if ( trim( $tagdata ) == '' ) //there is no tagdata
		{
			return $tagdata;
		}
		else if ( empty( $this->EE->session->cache[ 'Ce_cache' ]['is_caching'] ) ) //we're not inside of a tagdata loop
		{
			return $this->parse_vars( $tagdata );
		}

		//create a 16 character placeholder
		$placeholder = '-ce_cache_placeholder:' . hash( 'md5', $tagdata );// . '_' . mt_rand( 0, 1000000 );

		//add to the cache
		$this->EE->session->cache[ 'Ce_cache' ]['placeholder-keys'][] = $placeholder;
		$this->EE->session->cache[ 'Ce_cache' ]['placeholder-values'][] = $tagdata;

		//return the placeholder
		return $placeholder;
	}

	/**
	 * Returns whether or not a driver is supported.
	 * @return int
	 */
	public function is_supported()
	{
		//get the driver
		$driver = $this->EE->TMPL->fetch_param( 'driver' );

		//load the class if needed
		if ( ! class_exists( 'Ce_cache_factory' ) )
		{
			include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
		}

		//see if the driver is supported
		return ( Ce_cache_factory::is_supported( $driver ) ) ? 1 : 0;
	}

	/**
	 * Get an item from the cache.
	 * @param bool $internal_request Was this method requested from this class (TRUE) or from the template (FALSE).
	 * @return bool|int
	 */
	public function get( $internal_request = FALSE )
	{
		//setup the cache drivers if needed
		$this->setup_cache();

		//get the id
		if ( FALSE === $id = $this->fetch_id( __METHOD__ ) )
		{
			return $this->EE->TMPL->no_results();
		}

		//loop through the drivers and attempt to find the cache item
		foreach ( $this->drivers as $driver )
		{
			$item = $driver->get( $id );

			if ( $item !== FALSE ) //we found the item
			{
				//insert the action ids and return the data
				return $this->insert_action_ids( $item );
			}
		}

		//the item was not found in the cache of any of the drivers
		return ( $internal_request ) ? FALSE : $this->EE->TMPL->no_results();
	}

	/**
	 * Delete something from the cache.
	 * @return string|void
	 */
	public function delete()
	{
		//setup the cache drivers if needed
		$this->setup_cache();

		//get the id
		if ( FALSE === $id = $this->fetch_id( __METHOD__ ) )
		{
			return $this->EE->TMPL->no_results();
		}

		//loop through the drivers and attempt to delete the cache item for each one
		foreach ( $this->drivers as $driver )
		{
			if ( $driver->delete( $id ) !== FALSE )
			{
				$this->log_debug_message( __METHOD__, "The '{$id}' item was deleted for the " . $driver->name() . " driver." );
			}
		}

		//delete all of the current tags for this item
		$this->EE->db->query( 'DELETE FROM exp_ce_cache_tagged_items WHERE item_id = ?', array( $id ) );
	}

	/**
	 * Get information about a cached item.
	 *
	 * @return string
	 */
	public function get_metadata()
	{
		//setup the cache drivers if needed
		$this->setup_cache();

		//get the id
		if ( FALSE === $id = $this->fetch_id( __METHOD__ ) )
		{
			return $this->EE->TMPL->no_results();
		}

		//the array of meta data items
		$item = array();

		//loop through the drivers and attempt to find the cache item
		foreach ( $this->drivers as $driver )
		{
			//get the info
			if( !! $info = $driver->meta( $id, FALSE  ) )
			{
				//info contains the keys: 'expiry', 'made', 'ttl', 'ttl_remaining', and 'content'
				//add in legacy keys
				$info['expire'] = $info['expiry'];
				$info['mtime'] = $info['made'];
				//add in driver key
				$info['driver'] = $driver->name();
				$item = $info;
				break;
			}
		}

		//make sure we have at least one result
		if ( count( $item ) == 0 )
		{
			return $this->EE->TMPL->no_results();
		}

		//get the tagdata
		$tagdata = $this->no_results_tagdata();

		//parse the conditionals
		$tagdata = $this->EE->functions->prep_conditionals( $tagdata, $item );

		//return the parsed tagdata
		return $this->EE->TMPL->parse_variables_row( $tagdata, $item );
	}

	/**
	 * Purges the cache.
	 * @return void
	 */
	public function clean()
	{
		$site_only = trim( $this->EE->TMPL->fetch_param( 'site_only', 'yes' ) );

		//setup the cache drivers if needed
		$this->setup_cache();

		//loop through the drivers and purge their respective caches
		foreach ( $this->drivers as $driver )
		{
			if ( $this->ee_string_to_bool( $site_only ) )
			{
				//get the site name
				$site = Ce_cache_utils::get_site_label();
				$site = 'ce_cache/' . $site;
				$site = rtrim( $site ) . '/'; //make sure there is a trailing slash for this to work

				//attempt to get the items for the path
				if ( FALSE === $items = $driver->get_all( $site ) )
				{
					$this->log_debug_message( __METHOD__, "No items were found for the current site cache for the " . $driver->name() . " driver." );
					return;
				}

				//we've got items
				foreach ( $items as $item )
				{
					if ( $driver->delete( $site . ( ( $driver == 'db' || $driver == 'apc' ) ? $item['id'] : $item ) ) === FALSE )
					{
						$this->log_debug_message( __METHOD__, "Something went wrong, and the current site cache for the " . $driver->name() . " driver was not cleaned successfully." );
					}
				}
				unset( $items );

				return;
			}
			else
			{
				if ( $driver->clear() === FALSE )
				{
					$this->log_debug_message( __METHOD__, "Something went wrong, and the cache for the " . $driver->name() . " driver was not cleaned successfully." );
				}
				else
				{
					$this->log_debug_message( __METHOD__, "The cache for the " . $driver->name() . " driver was cleaned successfully." );
				}
			}
		}
	}

	/**
	 * Deprecated. Doesn't return anything.
	 * @return mixed
	 */
	public function cache_info()
	{
		return $this->EE->TMPL->no_results();
	}

	/**
	 * Breaks the cache. This method is an EE action (called from the CE Cache extension).
	 *
	 * @return void
	 */
	public function break_cache()
	{
		//this method is not intended to be called as an EE template tag
		if ( isset( $this->EE->TMPL ) )
		{
			return;
		}

		//load the cache break class, if needed
		if ( ! class_exists( 'Ce_cache_break' ) )
		{
			include PATH_THIRD . 'ce_cache/libraries/Ce_cache_break.php';
		}

		//instantiate the class break and call the break cache method
		$cache_break = new Ce_cache_break();
		$cache_break->break_cache_hook( null, null );
	}

	/**
	 * Simple method to log a debug message to the EE Debug console.
	 *
	 * @param string $method
	 * @param string $message
	 * @return void
	 */
	private function log_debug_message( $method = '', $message = '' )
	{
		if ( $this->debug )
		{
			$this->EE->TMPL->log_item( "&nbsp;&nbsp;***&nbsp;&nbsp;CE Cache $method debug: " . $message );
		}
	}

	/**
	 * Sets up the cache if needed. This is its own method, as opposed to being in the constructor, because some methods will not need it.
	 */
	private function setup_cache()
	{
		if ( ! $this->is_cache_setup ) //only run if the flag indicated it has not already been setup
		{
			//set the set up flag
			$this->is_cache_setup = TRUE;

			//get the user-specified drivers
			$drivers = $this->determine_setting( 'drivers', '' );
			if ( ! empty( $drivers ) ) //we have driver settings
			{
				if ( ! is_array( $drivers ) )
				{
					$drivers = explode( '|', $drivers );
				}
			}
			else //no drivers specified, see if we have some legacy settings
			{
				$drivers = array();

				//determine the adapter
				$adapter = $this->determine_setting( 'adapter' );
				if ( ! empty( $adapter ) ) //if not set to a valid value, set to 'file'
				{
					$drivers[] = $adapter;
				}

				//determine the backup adapter
				$backup = $this->determine_setting( 'backup' );
				if ( ! empty( $backup ) ) //if not set to a valid value, set to 'dummy'
				{
					$drivers[] = $backup;
				}
			}

			if ( count( $drivers ) == 0 ) //still no drivers specified, default to 'file'
			{
				$drivers[] = 'file';
			}

			//see if there is a reason to prevent caching the current page
			if ( isset( $this->EE->session->cache['ep_better_workflow']['is_draft'] ) && $this->EE->session->cache['ep_better_workflow']['is_draft'] === TRUE ) //better workflow draft
			{
				//set the drivers to dummy
				$drivers = array( 'dummy' );
			}

			//get the site name
			$site = Ce_cache_utils::get_site_label();

			$this->id_prefix = 'ce_cache/' . $site;

			if ( $this->EE->TMPL->fetch_param( 'global' ) == 'yes' ) //global cache
			{
				$this->id_prefix .= '/global/';
			}
			else //page specific cache
			{
				$override = $this->EE->TMPL->fetch_param( 'url_override' );
				if ( $override != FALSE )
				{
					$url = $override;
				}
				else
				{
					if ( isset( $this->EE->config->_global_vars[ 'triggers:original_uri' ] ) ) //Zoo Triggers hijacked the URL
					{
						$url = $this->EE->config->_global_vars[ 'triggers:original_uri' ];
					}
					else if ( isset( $this->EE->config->_global_vars[ 'freebie_original_uri' ] ) ) //Freebie hijacked the URL
					{
						$url = $this->EE->config->_global_vars[ 'freebie_original_uri' ];
					}
					else //the URL was not hijacked
					{
						$url = $this->EE->uri->uri_string();
					}
				}

				//set the id prefix
				$this->id_prefix .= '/local/' . $this->EE->security->sanitize_filename( $url, true );
			}

			$this->id_prefix = trim( $this->id_prefix, '/' ) . '/';

			//load the class if needed
			if ( ! class_exists( 'Ce_cache_factory' ) )
			{
				include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
			}

			//get the driver classes
			$this->drivers = Ce_cache_factory::factory( $drivers, TRUE );
		}
	}

	/**
	 * Determines the given setting by checking for the param, and then for the global var, and then for the config item.
	 * @param string $name The name of the parameter. The string 'ce_cache_' will automatically be prepended for the global and config setting checks.
	 * @param string $default The default setting value
	 * @return string The setting value if found, or the default setting if not found.
	 */
	private function determine_setting( $name, $default = '' )
	{
		$long_name = 'ce_cache_' . $name;
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
	 * Parses the tagdata as if it were a template.
	 * @param string $tagdata
	 * @return string
	 */
	private function parse_as_template( $tagdata = '' )
	{
		//store the current template object
		$TMPL2 = $this->EE->TMPL;
		unset($this->EE->TMPL);

		//create a new template object
		$this->EE->TMPL = new EE_Template();
		$this->EE->TMPL->start_microtime = $TMPL2->start_microtime;
		$this->EE->TMPL->plugins = $TMPL2->plugins;
		$this->EE->TMPL->modules = $TMPL2->modules;

		//parse the current tagdata
		$this->EE->TMPL->parse( $tagdata );

		//get the parsed tagdata back
		$tagdata = $this->EE->TMPL->final_template;

		if ( $this->debug )
		{
			//these first items are boilerplate, and were already included in the first log - like "Parsing Site Variables", Snippet keys and values, etc
			unset( $this->EE->TMPL->log[0], $this->EE->TMPL->log[1], $this->EE->TMPL->log[2], $this->EE->TMPL->log[3], $this->EE->TMPL->log[4], $this->EE->TMPL->log[5], $this->EE->TMPL->log[6] );

			$TMPL2->log = array_merge( $TMPL2->log, $this->EE->TMPL->log );
		}

		//now let's check to see if this page is a 404 page
		if ( isset( $this->EE->output->headers[0] ) )
		{
			foreach ( $this->EE->output->headers as $key => $value )
			{
				foreach ( $value as $k => $v )
				{
					if ( strpos( $v, '404' ) !== FALSE )
					{
						$this->EE->output->out_type = '404';
						$this->EE->TMPL->template_type = '404';
						$this->EE->TMPL->final_template = $this->unescape_tagdata( $tagdata );
						$this->EE->TMPL->cease_processing = TRUE;
						$this->EE->TMPL->no_results();
						self::$is_404 = TRUE;
					}
				}
			}
		}

		//restore the original template object
		$this->EE->TMPL = $TMPL2;

		unset($TMPL2);

		//return the tagdata
		return $tagdata;
	}

	/**
	 * Parses segment and global variables. Used to parse data in escape tags.
	 * @param $str
	 * @return mixed
	 */
	private function parse_vars( $str )
	{
		//remove the comments
		$str = $this->EE->TMPL->remove_ee_comments( $str );

		//parse segment variables
		if ( strpos( $str, '{segment_' ) !== FALSE )
		{
			for ( $i = 1; $i < 10; $i++ )
			{
				$str = str_replace( '{segment_' . $i . '}', $this->EE->uri->segment( $i ), $str );
			}
		}

		//parse gloval variables
		$str = $this->EE->TMPL->parse_variables_row( $str, $this->EE->config->_global_vars );

		return $str;
	}

	/**
	 * The following is a lot like the Functions method of inserting the action ids, except that this will first find the actions. The original method does not look to find the ids on cached data (it just stores them in an array as they are called).
	 *
	 * @param $str
	 * @return mixed
	 */
	private function insert_action_ids( $str )
	{
		//will hold the actions
		$actions = array();

		//do we need to check for actions?
		if ( strpos( $str, LD . 'AID:' ) !== FALSE && preg_match_all( '@' . LD . 'AID:([^:}]*):([^:}]*)' . RD . '@Us', $str, $matches, PREG_SET_ORDER ) ) //actions found
		{
			foreach ( $matches as $match )
			{
				$actions[ $match[ 1 ] ] = $match[ 2 ];
			}
		}
		else //no actions to parse
		{
			return $str;
		}

		//create the sql
		$sql = "SELECT action_id, class, method FROM exp_actions WHERE";
		foreach ( $actions as $key => $value )
		{
			$sql .= " (class= '" . $this->EE->db->escape_str( $key ) . "' AND method = '" . $this->EE->db->escape_str( $value ) . "') OR";
		}

		//run the query
		$query = $this->EE->db->query( substr( $sql, 0, -3 ) );

		if ( $query->num_rows() > 0 )
		{
			foreach ( $query->result_array() as $row )
			{
				$str = str_replace( LD . 'AID:' . $row[ 'class' ] . ':' . $row[ 'method' ] . RD, $row[ 'action_id' ], $str );
			}
		}

		return $str;
	}

	/**
	 * Determines the id to use.
	 *
	 * @param string $method The calling method.
	 * @return string|bool The id on success, or FALSE on failure.
	 */
	private function fetch_id( $method )
	{
		if ( $this->EE->TMPL->fetch_param( 'global' ) == 'yes' ) //global cache
		{
			$id = $this->EE->TMPL->fetch_param( 'id', '' );
		}
		else //page specific cache
		{
			$id = $this->determine_setting( 'id', 'item' );
		}

		$id = trim( $id );

		//get the id
		if ( empty( $id ) )
		{
			$this->log_debug_message( $method, "An id was not specified." );

			return FALSE;
		}
		if ( ! $this->id_is_valid( $id ) )
		{
			$this->log_debug_message( $method, "The specified id '{$id}' is invalid. An id may only contain alpha-numeric characters, dashes, and underscores." );
			return FALSE;
		}

		//add the id prefix
		return trim( $this->EE->functions->remove_double_slashes( $this->id_prefix . $id ), '/' );
	}

	/**
	 * Validates an id.
	 *
	 * @param string $id
	 * @return int 1 for valid, 0 for invalid
	 */
	private function id_is_valid( $id )
	{
		return preg_match( '@[^\s]+@i', $id );
	}

	/**
	 * Little helper method to convert parameters to a boolean value.
	 *
	 * @param $string
	 * @return bool
	 */
	private function ee_string_to_bool( $string )
	{
		return ( $string == 'y' || $string == 'yes' || $string == 'on' || $string === TRUE );
	}

	private function no_results_tagdata()
	{
		//$tagdata = $this->EE->TMPL->tagdata;
		$index = 0;
		foreach ( $this->EE->TMPL->tag_data as $i => $tag_dat )
		{
			if ( $this->EE->TMPL->tagchunk == $tag_dat['chunk'] )
			{
				$index = $i;
			}
		}

		return $this->EE->TMPL->tag_data[$index]['block'];
	}

	/**
	 * This is a shutdown function registered when an item is saved.
	 */
	public function shut_it_down()
	{
		//no reason to go any further if we want do not want to exclude 404 page caching, if there are no cached items, or this is not a 404 page
		if ( $this->EE->config->item( 'ce_cache_exclude_404s' ) == 'no' || ! self::$is_404 || ! isset ( $this->EE->session->cache[ 'Ce_cache' ]['cached_items'] ) )
		{
			return;
		}

		//loop through each driver
		foreach ( $this->drivers as $driver )
		{
			foreach ( $this->EE->session->cache[ 'Ce_cache' ]['cached_items'] as $index => $item )
			{
				if ( $item['driver'] == $driver->name() )
				{
					$driver->delete( $item['id'] );
					unset( $this->EE->session->cache[ 'Ce_cache' ]['cached_items'][$index] );
				}
			}
		}

		unset( $this->EE->session->cache[ 'Ce_cache' ]['cached_items'] );
	}

	/**
	 * A very simple method to attempt to determine if the current user agent is a bot
	 *
	 * @return bool
	 */
	private function is_bot()
	{
		if ( ! isset( $this->EE->session->cache[ 'Ce_cache' ][ 'is_bot' ] ) )
		{
			$user_agent = $this->EE->input->user_agent();

			$this->EE->session->cache[ 'Ce_cache' ][ 'is_bot' ] = (bool)( ! empty( $user_agent ) && preg_match( '@bot|spider|crawler|curl@i', $user_agent ) );
		}

		return $this->EE->session->cache[ 'Ce_cache' ][ 'is_bot' ];
	}


	private function unescape_tagdata( $tagdata )
	{
		//unescape any content escaped by the escape() method
		if ( isset( $this->EE->session->cache[ 'Ce_cache' ]['placeholder-keys'] ) )
		{
			$tagdata = str_replace( $this->EE->session->cache[ 'Ce_cache' ]['placeholder-keys'], $this->EE->session->cache[ 'Ce_cache' ]['placeholder-values'], $tagdata);

			$tagdata = str_replace( '{::segment_', '{segment_', $tagdata );
		}

		//unescape any escaped logged_in and logged_out conditionals if they were escaped above
		if ( $this->EE->TMPL->fetch_param( 'process' ) != 'no' )
		{
			//now we'll swap the logged_in and logged_out variables back to their old selves
			$tagdata = str_replace( array( 'ce_cache-in_logged', 'ce_cache-out_logged' ), array( 'logged_in', 'logged_out' ), $tagdata );
		}

		return $tagdata;
	}
}
/* End of file mod.ce_cache.php */
/* Location: /system/expressionengine/third_party/ce_cache/mod.ce_cache.php */