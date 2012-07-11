<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CE Cache - Module Control Panel File
 *
 * @author		Aaron Waldon
 * @copyright	Copyright (c) 2011 Causing Effect
 * @license		http://www.causingeffect.com/software/expressionengine/ce-cache/license-agreement
 * @link		http://www.causingeffect.com
 */

class Ce_cache_mcp {

	public $return_data;
	private $name = 'ce_cache';
	private $base_url;
	private $valid_drivers = array( 'file', 'db', 'apc', 'memcache', 'memcached', 'redis', 'dummy' );

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->add_package_path( $this->name );

		if ( ! $this->EE->input->is_ajax_request() )
		{
			//ensure BASE is defined - http://devot-ee.com/add-ons/support/wyvern/viewthread/2238#7469
			if ( ! defined('BASE') )
			{
				$s = ( $this->EE->config->item('admin_session_type' ) != 'c') ? $this->EE->session->userdata('session_id') : 0;
				define( 'BASE', SELF.'?S='.$s.'&amp;D=cp' );
			}

			$this->base_url = BASE . AMP . 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=' . $this->name;

			$this->EE->cp->set_right_nav(
				array(
					'module_home'	=> $this->base_url,
					'ce_cache_channel_cache_breaking'	=> $this->base_url . AMP . 'method=breaking',
					'ce_cache_clear_tagged_items'	=> $this->base_url . AMP . 'method=clear_tags'
				)
			);

			//include CE Cache Utilities
			if ( ! class_exists( 'Ce_cache_utils' ) )
			{
				include PATH_THIRD . 'ce_cache/libraries/Ce_cache_utils.php';
			}
		}
	}

	/**
	 * Ensures the constructor is called.
	 *
	 * @return void
	 */
	public function Ce_cache_mcp()
	{
		$this->__construct();
	}

	/**
	 * Index Function
	 *
	 * @return string
	 */
	public function index()
	{
		$this->EE->cp->set_variable( 'cp_page_title', lang('ce_cache_module_name') );

		//load needed classes
		$this->EE->load->library('table');
		$this->EE->load->helper('form');

		//load the class if needed
		if ( ! class_exists( 'Ce_cache_factory' ) )
		{
			include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
		}

		//the drivers array
		$drivers = array();

		//get the user-specified drivers - these will show first in the list
		if ( isset( $this->EE->config->_global_vars[ 'ce_cache_drivers' ] ) && $this->EE->config->_global_vars[ 'ce_cache_drivers' ] !== FALSE ) //first check global array
		{
			$temp = $this->EE->config->_global_vars[ 'ce_cache_drivers' ];
		}
		else if ( $this->EE->config->item( 'ce_cache_drivers' ) !== FALSE ) //then check config
		{
			$temp = $this->EE->config->item( 'ce_cache_drivers' );
		}
		//we have driver settings
		if ( ! empty( $temp ) )
		{
			if ( ! is_array( $temp ) )
			{
				//add the user defined drivers to the drivers array first
				$drivers = explode( '|', $temp );

				//make sure each driver is only included once
				$drivers = array_unique( $drivers );
			}
		}

		//add the other drivers into the list
		foreach ( $this->valid_drivers as $valid_driver )
		{
			if ( ! in_array( $valid_driver, $drivers ) )
			{
				$drivers[] = $valid_driver;
			}
		}

		//get the driver classes
		$classes = Ce_cache_factory::factory( $drivers );

		$supported = array();

		foreach ( $classes as $class )
		{
			$supported[] = $class->name();
		}

		unset( $classes, $drivers );

		//get the site name
		$site = Ce_cache_utils::get_site_label();

		//the prefix for this site
		$site = 'ce_cache/' . $site . '/';

		//view data
		$data = array(
			'module' => $this->name,
			'drivers' => $supported,
			'site' => $site
		);

		//return the index view
		return $this->EE->load->view('index', $data, TRUE);
	}

    /**
     * View the cache items for the specified driver. This method expects the 'driver' get_post variable, and the 'offset' variable if paginating and the offset is not 0.
     *
     * @return string
     */
	public function view_items()
	{
		//set the breadcrumb for the index page
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('ce_cache_module_name') );

		//grab the driver from the get/post data
		$driver = $this->EE->input->get_post( 'driver', TRUE );

		$back_link = '<p><a class="submit" href="' . $this->base_url . AMP . 'method=index' . '">' . lang( "ce_cache_back_to" ) . ' ' . lang( 'module_home' ) . '</a></p>';

		if ( empty( $driver ) )
		{
			return '<p cass="error">' . lang( "ce_cache_error_no_driver" ) . '</p>' . PHP_EOL . $back_link;
		}
		else if ( ! in_array( $driver, $this->valid_drivers ) )
		{
			return '<p cass="error">' . lang( "ce_cache_error_invalid_driver" ) . '</p>' . PHP_EOL . $back_link;
		}

		//load the class if needed
		if ( ! class_exists( 'Ce_cache_factory' ) )
		{
			include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
		}

		//make sure the driver is valid
		if ( ! Ce_cache_factory::is_supported( $driver ) )
		{
			return '<p cass="error">'. lang( "ce_cache_error_invalid_driver" ) . '</p>';
		}

        //set the page title
        $this->EE->cp->set_variable( 'cp_page_title', lang( "ce_cache_driver_{$driver}" ) . ' ' . lang( "ce_cache_items" ) );

		//get the site name
		$site = Ce_cache_utils::get_site_label();

		//the prefix for this site
        $prefix = 'ce_cache/' . $site . '/';

		//get the current site
		$site_id = $this->EE->config->item( 'site_id' );

		//load the libraries
		$this->EE->load->library( 'javascript' );

		//load cp jquery files
		$this->EE->cp->add_js_script(
			array(
				'ui' => array(
					'core',
					'widget',
					'button',
					'mouse',
					'draggable',
					'resizable',
					'position',
					'dialog'
				)
			)
		);

		//add the css and options
		$this->EE->cp->add_to_head( PHP_EOL . '<link rel="stylesheet" href="' . $this->EE->config->item( 'theme_folder_url' ) . 'third_party/ce_cache/css/ce_cache_ui.css">
		<script type="text/javascript">
			var ceCacheOptions = {
				site_id : "' . $site_id . '",
				prefix : "' . $prefix . '",
				urls : {
					getLevel : "' . $this->EE->functions->create_url( QUERY_MARKER . 'ACT=' . $this->EE->cp->fetch_action_id( __CLASS__, 'ajax_get_level' ) ) . '",
					deleteItem : "' . $this->EE->functions->create_url( QUERY_MARKER . 'ACT=' . $this->EE->cp->fetch_action_id( __CLASS__, 'ajax_delete' ) ) . '"
				},
				driver : "' . $driver . '",
				lang : {
					unknown_error : "' . lang( 'ce_cache_ajax_unknown_error' ) . '",
					no_items_found : "' . lang( 'ce_cache_ajax_no_items_found' ) . '",
					ajax_error : "' . lang( 'ce_cache_ajax_error' ) . '",
					ajax_error_title : "' . lang( 'ce_cache_ajax_error_title' ) . '",
					install_error : "' . lang( 'ce_cache_ajax_install_error' ) . '",

					delete_child_items_confirmation : "' . lang( 'ce_cache_ajax_delete_child_items_confirmation' ) . '",
					delete_child_items_button : "' . lang( 'ce_cache_ajax_delete_child_items_button' ) . '",
					delete_child_items_refresh : "' . lang( 'ce_cache_ajax_delete_child_items_refresh' ) . '",
					delete_child_items_refresh_time : "' . lang( 'ce_cache_ajax_delete_child_items_refresh_time' ) . '",
					delete_child_item_confirmation : "' . lang( 'ce_cache_ajax_delete_child_item_confirmation' ) . '",
					delete_child_item_button : "' . lang( 'ce_cache_ajax_delete_child_item_button' ) . '",
					delete_child_item_refresh : "' . lang( 'ce_cache_ajax_delete_child_item_refresh' ) . '",
					cancel : "' . lang( 'ce_cache_ajax_cancel' ) . '"
				}
			};
		</script>' );

		//load in the CE Cache JavaScript
		$this->EE->cp->load_package_js( 'ce_cache_ui' );

		//view data
		$data = array(
			'module' => $this->name,
			'driver' => $driver,
			'back_link' => $back_link
		);

		//return the view
		return $this->EE->load->view( 'view_items', $data, TRUE );
	}

	/**
	 * Get a level of items.
	 */
	public function ajax_get_level()
	{
		//header('Access-Control-Allow-Origin: *');

		//load the language file
		$this->EE->lang->loadfile( 'ce_cache' );

		//the ajax response
		$response = array(
			'success' => TRUE
		);

		//get the path
		$path = $this->EE->input->post( 'path', TRUE );
		if ( empty( $path ) ) //the item path was not received
		{
			$response['success'] = FALSE;
			$response['message'] = lang( 'ce_cache_error_invalid_path' );
			echo json_encode( $response );
			exit();
		}

		//get the prefix
		$prefix = $this->EE->input->post( 'prefix', TRUE );
		if ( empty( $prefix ) ) //the item path was not received
		{
			$response['success'] = FALSE;
			$response['message'] = lang( 'ce_cache_error_invalid_path' );
			echo json_encode( $response );
			exit();
		}

		//get the driver
		$driver = $this->EE->input->post( 'driver', TRUE );
		if ( ! in_array( $driver, $this->valid_drivers ) ) //the driver is not valid
		{
			$response['success'] = FALSE;
			$response['message'] = lang( 'ce_cache_error_invalid_driver' );
			echo json_encode( $response );
			exit();
		}

		//load the class if needed
		if ( ! class_exists( 'Ce_cache_factory' ) )
		{
			include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
		}

		$drivers = Ce_cache_factory::factory( $driver );

		//make sure the driver is valid
		if ( ! Ce_cache_factory::is_supported( $driver ) )
		{
			$response['success'] = FALSE;
			$response['message'] = lang( 'ce_cache_error_invalid_driver' );
		}

		$class = $drivers[0];

		//attempt to get the items for this site
		if ( FALSE === $items = $class->get_level(  $prefix . ltrim( $path, '/' ) ) )
		{
			$response['success'] = FALSE;
			$response['message'] = lang( 'ce_cache_error_getting_items' );
		}

		if ( $driver != 'db' && $driver != 'apc' ) //the database & apc drivers already come with all of the meta data (more efficient due to how they work)
		{
			$temps = $items;
			$folders = array();
			$files = array();

			//get the meta data without the content for each item
			foreach ( $temps as $temp )
			{
				if ( ! $this->ends_with( $temp, '/' ) ) //cache item
				{
					//attempt to get the items for this site
					$data = $class->meta( $prefix . ltrim( $path, '/' ) . $temp, FALSE );
					if ( $data !== FALSE )
					{
						$data['id'] = $temp;
						$data['id_full'] = ltrim( $path, '/' ) . $temp;
						$data['type'] = 'file';
						$files[] = $data;
					}
				}
				else //directory
				{
					$folders[] = array(
						'id' => $temp,
						'id_full' => ltrim( $path, '/' ) . $temp,
						'type' => 'folder',
						'expiry' => '',
						'made' => '',
						'ttl' => '',
						'ttl_remaining' => '',
						'size' => '',
						'size_raw' => ''
					);
				}
			}

			$items = array_merge( $folders, $files );
			unset( $temps, $folders, $files );
		}
		else //db and apc
		{
			foreach ( $items as $index => $item )
			{
				if ( ! $this->ends_with( $item['id'], '/' ) ) //cache item
				{
					$items[$index]['id_full'] = ltrim( $path, '/' ) .  $item['id'];
					$items[$index]['type'] = 'file';
				}
				else //directory
				{
					$items[$index]['id_full'] = ltrim( $path, '/' ) .  $item['id'];
					$items[$index]['type'] = 'folder';
					$items[$index]['expiry'] = '';
					$items[$index]['made'] = '';
					$items[$index]['ttl'] = '';
					$items[$index]['ttl_remaining'] = '';
					$items[$index]['size'] = '';
					$items[$index]['size_raw'] = '';
				}
			}
		}

		//load the CE Cache control panel library
		$this->EE->load->library( 'Ce_cache_cp' );

		$response['data'] = array(
			'items_html' => $this->EE->ce_cache_cp->items_to_html_list( $items, $driver ),
			'breadcrumbs_html' => $this->EE->ce_cache_cp->breadcrumb_html( $driver, $path )
		);
		unset( $items );

		echo json_encode( $response );
		exit();
	}

	/**
	 * Delete a child.
	 */
	public function ajax_delete()
	{
		//header('Access-Control-Allow-Origin: *');

		//load the language file
		$this->EE->lang->loadfile( 'ce_cache' );

		//the ajax response
		$response = array(
			'success' => TRUE
		);

		//get the path
		$path = $this->EE->input->post( 'path', TRUE );
		if ( empty( $path ) ) //the item path was not received
		{
			$response['success'] = FALSE;
			$response['message'] = lang( 'ce_cache_error_invalid_path' );
			echo json_encode( $response );
			exit();
		}

		//get the prefix
		$prefix = $this->EE->input->post( 'prefix', TRUE );
		if ( empty( $prefix ) ) //the item path was not received
		{
			$response['success'] = FALSE;
			$response['message'] = lang( 'ce_cache_error_invalid_path' );
			echo json_encode( $response );
			exit();
		}

		//get the driver
		$driver = $this->EE->input->post( 'driver', TRUE );
		if ( ! in_array( $driver, $this->valid_drivers ) ) //the driver is not valid
		{
			$response['success'] = FALSE;
			$response['message'] = lang( 'ce_cache_error_invalid_driver' );
			echo json_encode( $response );
			exit();
		}

		//get the path
		$refresh = $this->EE->input->post( 'refresh', TRUE );
		if ( empty( $refresh ) || $refresh == 'false' )
		{
			$refresh = FALSE;
		}
		else
		{
			$refresh = TRUE;
		}

		//load the class if needed
		if ( ! class_exists( 'Ce_cache_factory' ) )
		{
			include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
		}

		$drivers = Ce_cache_factory::factory( $driver );

		//make sure the driver is valid
		if ( ! Ce_cache_factory::is_supported( $driver ) )
		{
			$response['success'] = FALSE;
			$response['message'] = lang( 'ce_cache_error_invalid_driver' );
		}

		$class = $drivers[0];

		if ( ! $this->ends_with( $path, '/' ) ) //if the item is not a directory
		{
			//attempt to delete the item
			if ( $class->delete( $prefix . ltrim( $path, '/' ) ) === FALSE )
			{
				$response['success'] = FALSE;
				$response['message'] = sprintf( lang( "ce_cache_error_deleting_item" ), $path );
				echo json_encode( $response );
				exit();
			}

			if ( $refresh !== FALSE && substr( $path, 0, 5 ) == 'local' )
			{
				//create the URL
				$url = $this->EE->functions->fetch_site_index( 0, 0 );

				//trim the 'local/' from the beginning of the path
				$path = substr( $path, 6 );

				//find the last '/'
				$last_slash = strrpos( $path, '/' );

				//if a last '/' was found, get the path up to that point (remove the cache id name)
				$path = ( $last_slash === FALSE ) ? '' : substr( $path, 0, $last_slash );

				//load the cache break class, if needed
				if ( ! class_exists( 'Ce_cache_break' ) )
				{
					include PATH_THIRD . 'ce_cache/libraries/Ce_cache_break.php';
				}

				//instantiate the class break and call the break cache method
				$cache_break = new Ce_cache_break();

				//make sure that allow_url_fopen is set to true if permissible
				@ini_set('allow_url_fopen', TRUE);
				//some servers will not accept the asynchronous requests if there is no user_agent
				@ini_set('user_agent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:5.0) Gecko/20100101');

				//try to request the page, first using cURL, then falling back to fsocketopen
				if ( ! $cache_break->curl_it( $url . $path ) ) //send a cURL request
				{
					//attempt a fsocketopen request
					$cache_break->fsockopen_it( $url . $path );
				}
			}
		}
		else //the item is a directory
		{
			//attempt to get the items for the path
			if ( FALSE === $items = $class->get_all( $prefix . ltrim( $path, '/' ) ) )
			{
				$response['success'] = FALSE;
				$response['message'] = lang( 'ce_cache_error_getting_items' );
				echo json_encode( $response );
				exit();
			}

			//we've got items
			$errors = array();

			if ( $refresh ) //delete and refresh
			{
				$refresh_time = $this->EE->input->post( 'refresh_time', TRUE );

				if ( empty( $refresh_time ) || ! is_numeric( $refresh_time ) )
				{
					$refresh_time = 0;
				}

				//make sure that allow_url_fopen is set to true if permissible
				@ini_set('allow_url_fopen', TRUE);
				//some servers will not accept the asynchronous requests if there is no user_agent
				@ini_set('user_agent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:5.0) Gecko/20100101');

				//create the URL
				$url = $this->EE->functions->fetch_site_index( 0, 0 );

				//load the cache break class, if needed
				if ( ! class_exists( 'Ce_cache_break' ) )
				{
					include PATH_THIRD . 'ce_cache/libraries/Ce_cache_break.php';
				}

				//instantiate the class break
				$cache_break = new Ce_cache_break();

				//loop through the items, and delete and refresh each one
				foreach ( $items as $item )
				{
					@sleep( $refresh_time );

					$url_string = $prefix . ltrim( $path, '/' ) . ( ( $driver == 'db' || $driver == 'apc' ) ? $item['id'] : $item );

					//delete the item
					if ( $class->delete( $url_string ) === FALSE )
					{
						$errors[] = sprintf( lang( 'ce_cache_error_deleting_item' ), $url_string );
					}

					//remove the prefix
					$url_string = substr( $url_string , strlen( $prefix ) );

					//trim the 'local/' from the beginning of the path
					$url_string = substr( $url_string, 6 );

					//find the last '/'
					$last_slash = strrpos( $url_string, '/' );

					//if a last '/' was found, get the path up to that point
					$url_string = ( $last_slash === FALSE ) ? '' : substr( $url_string, 0, $last_slash );

					//try to request the page, first using cURL, then falling back to fsocketopen
					if ( ! $cache_break->curl_it( $url . $url_string ) ) //send a cURL request
					{
						//attempt a fsocketopen request
						$cache_break->fsockopen_it( $url . $url_string );
					}
				}
			}
			else //just delete, don't refresh
			{
				foreach ( $items as $item )
				{
					$t = $prefix . ltrim( $path, '/' ) . ( ( $driver == 'db' || $driver == 'apc' ) ? $item['id'] : $item );

					if ( $class->delete( $t ) === FALSE )
					{
						$errors[] = sprintf( lang( 'ce_cache_error_deleting_item' ), $t );
					}
				}
			}

			unset( $items );

			//show the errors if there were any
			if ( count( $errors ) > 0 )
			{
				$response['success'] = FALSE;
				$response['message'] = implode( "\n", $errors );
				echo json_encode( $response );
				exit();
			}
		}

		echo json_encode( $response );
		exit();
	}

	/**
	 * View a cache item by id. This method expects the 'item' and 'driver' get_post variables.
	 *
	 * @return string
	 */
    public function view_item()
    {
		//set the breadcrumb for the index page
        $this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('ce_cache_module_name') );

        //set the page title
        $this->EE->cp->set_variable( 'cp_page_title', lang( "ce_cache_view_item" ) );

        //load classes/helpers
        $this->EE->load->helper( array('url') );

        //grab the driver from the get/post data
        $driver = $this->EE->input->get_post( 'driver', TRUE );

        if ( empty( $driver ) )
        {
            return '<p>' . lang( "ce_cache_error_no_driver" ) . '</p>';
        }
        else if ( ! in_array( $driver, $this->valid_drivers ) )
        {
            return '<p>' . lang( "ce_cache_error_invalid_driver" ) . '</p>';
        }

		//set the breadcrumb for the view items page for the specified driver
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=view_items' . AMP . "driver={$driver}", lang( "ce_cache_driver_{$driver}" ) . ' ' . lang( "ce_cache_items" ) );

        //grab the item from the get/post data
        $item = $this->EE->input->get_post( 'item', TRUE );

        if ( empty( $item ) )
        {
            return '<p>' . lang( "ce_cache_error_no_item" ) . '</p>';
        }

        $item = urldecode( $item );

		//load the class if needed
		if ( ! class_exists( 'Ce_cache_factory' ) )
		{
			include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
		}

		//make sure the driver is valid
		if ( ! Ce_cache_factory::is_supported( $driver ) )
		{
			return '<p cass="error">'. lang( "ce_cache_error_invalid_driver" ) . '</p>';
		}

		$drivers = Ce_cache_factory::factory( $driver );

		$class = $drivers[0];

		//attempt to get the item metadata
		if ( FALSE === $meta = $class->meta( $item ) )
		{
			return '<p cass="error">'. lang( "ce_cache_error_getting_meta" ) . '</p>';
		}

		//load the date helper
		$this->EE->load->helper( 'date' );

		//the time format string
		$time_string = '%Y-%m-%d - %h:%i:%s %a';

		//determine and set the expiry
		$expiry = ( $meta['expiry'] == 0 ) ? '&infin;' : mdate( $time_string, $meta['expiry'] );
		$ttl = $meta['ttl'];
		$ttl_remaining = $meta['ttl_remaining'];
		$made = mdate( $time_string, $meta['made'] );
		$content = $meta['content'];
		$size = $meta['size'];
		$size_raw = $meta['size_raw'];

		unset( $meta );

        //get the site name
		$site = Ce_cache_utils::get_site_label();

		$this->EE->cp->add_to_head( '<link rel="stylesheet" href="' . $this->EE->config->item( 'theme_folder_url' ) . 'third_party/ce_cache/css/ce_cache_ui.css">' );

        //view data
        $data = array(
			'module' => $this->name,
            'item' => $item,
			'made' => $made,
			'expiry' => $expiry,
			'ttl' => $ttl,
			'size' => $size,
			'size_raw' => $size_raw,
			'ttl_remaining' => $ttl_remaining,
			'content' => $content,
            'prefix' => 'ce_cache/' . $site . '/',
			'back_link' => '<a class="submit" href="' . $this->base_url . AMP . 'method=view_items' . AMP . "driver={$driver}" . '">' . lang( "ce_cache_back_to" ) . ' ' . lang( "ce_cache_driver_{$driver}" ) . ' ' . lang( "ce_cache_items" ) . '</a>'
        );

        //return the view
        return $this->EE->load->view( 'view_item', $data, TRUE );
    }

	/**
	 * Method to clear the cache for a specific driver
     *
	 * @return string
	 */
	public function clear_cache()
	{
		//set the breadcrumb for the index page
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('ce_cache_module_name') );

		//set the page title
		$this->EE->cp->set_variable( 'cp_page_title', lang( "ce_cache_clear_cache" ) );

		//load classes/helpers
		$this->EE->load->helper( array('form', 'url') );
		$this->EE->load->library( 'form_validation' );

		//grab the driver from the get/post data
		$driver = $this->EE->input->get_post( 'driver', TRUE );

		if ( empty( $driver ) )
		{
			return '<p>' . lang( "ce_cache_error_no_driver" ) . '</p>';
		}
		else if ( ! in_array( $driver, $this->valid_drivers ) )
		{
			return '<p>' . lang( "ce_cache_error_invalid_driver" ) . '</p>';
		}

		$site_only = ( $this->EE->input->get_post( 'site_only' ) == 'y' );

		//flag to show the form (TRUE) or the confirmation page (FALSE)
		$show_form = TRUE;

		if ( $this->EE->input->post( 'submit', TRUE ) !== FALSE ) //the form was submitted
		{
			//load the class if needed
			if ( ! class_exists( 'Ce_cache_factory' ) )
			{
				include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
			}

			//make sure the driver is valid
			if ( ! Ce_cache_factory::is_supported( $driver ) )
			{
				return '<p cass="error">'. lang( "ce_cache_error_invalid_driver" ) . '</p>';
			}

			$drivers = Ce_cache_factory::factory( $driver );

            $class = $drivers[0];

			if ( $site_only ) //clear only for this site
			{
				$path = 'ce_cache/' . Ce_cache_utils::get_site_label() . '/';

				//attempt to get the items for the path
				if ( FALSE === $items = $class->get_all( $path ) )
				{
					return '<p cass="error">'. lang( "ce_cache_error_getting_items" ) . '</p>';
				}

				//we've got items
				$errors = array();

				foreach ( $items as $item )
				{
					$t = $path . ( ( $driver == 'db' || $driver == 'apc' ) ? $item['id'] : $item );

					if ( $class->delete( $t ) === FALSE )
					{
						$errors[] = '<p cass="error">'. sprintf( lang( "ce_cache_error_deleting_item" ), $t ) . '</p>';
					}
				}

				//show the errors if there were any
				if ( count( $errors ) > 0 )
				{
					return implode( PHP_EOL, $errors );
				}
			}
			else //attempt to clear the driver cache
			{
				if ( $class->clear() === FALSE )
				{
					return '<p cass="error">'. lang( "ce_cache_error_cleaning_cache" ) . '</p>';
				}
			}

			//cache was cleared successfully
			$show_form = FALSE;
		}

		//view data
		$data = array(
			'module' => $this->name,
			'action_url' => 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . "module=ce_cache" . AMP . 'method=clear_cache',
			'show_form' => $show_form,
			'driver' => $driver,
			'back_link' => '<a class="submit" href="' . $this->base_url . AMP . 'method=index' . '">' . lang( "ce_cache_back_to" ) . ' ' . lang( 'module_home' ) . '</a>'
		);

		//return the view
		return $this->EE->load->view( 'clear_cache', $data, TRUE );
	}

	/**
	 * Method to clear all caches.
	 *
	 * @return string
	 */
	public function clear_all_caches()
	{
		//set the breadcrumb for the index page
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('ce_cache_module_name') );

		//set the page title
		$this->EE->cp->set_variable( 'cp_page_title', lang( 'ce_cache_clear_cache_all_drivers' ) );

		//load classes/helpers
		$this->EE->load->helper( array('form', 'url') );
		$this->EE->load->library( 'form_validation' );

		//flag to show the form (TRUE) or the confirmation page (FALSE)
		$show_form = TRUE;

		if ( $this->EE->input->post( 'submit', TRUE ) !== FALSE ) //the form was submitted
		{
			//load the class if needed
			if ( ! class_exists( 'Ce_cache_factory' ) )
			{
				include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
			}

			$classes = Ce_cache_factory::factory( $this->valid_drivers );

			//set a blank error string
			$errors = '';

			foreach ( $classes as $class )
			{
				//attempt to clear the cache
				if ( $class->clear() === FALSE )
				{
					//add the error for the current driver
					$errors .= '<p cass="error">'. sprintf( lang( "ce_cache_error_cleaning_driver_cache" ), lang( 'ce_cache_driver_' . $class->name() ) ) . '</p>';
				}
			}

			//if the error string is not blank, return the error(s)
			if ( $errors != '' )
			{
				return $errors;
			}

			//cache was cleared successfully
			$show_form = FALSE;
		}

		//view data
		$data = array(
			'action_url' => 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . "module=ce_cache" . AMP . 'method=clear_all_caches',
			'show_form' => $show_form,
			'back_link' => '<a class="submit" href="' . $this->base_url . AMP . 'method=index' . '">' . lang( "ce_cache_back_to" ) . ' ' . lang( 'module_home' ) . '</a>'
		);

		//return the view
		return $this->EE->load->view( 'clear_all_caches', $data, TRUE );
	}

	/**
	 * Method to clear all site caches.
	 *
	 * @return string
	 */
	public function clear_site_caches()
	{
		//set the breadcrumb for the index page
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('ce_cache_module_name') );

		//set the page title
		$this->EE->cp->set_variable( 'cp_page_title', lang( 'ce_cache_clear_cache_site_all' ) );

		//load classes/helpers
		$this->EE->load->helper( array('form', 'url') );
		$this->EE->load->library( 'form_validation' );

		//flag to show the form (TRUE) or the confirmation page (FALSE)
		$show_form = TRUE;

		if ( $this->EE->input->post( 'submit', TRUE ) !== FALSE ) //the form was submitted
		{
			//load the class if needed
			if ( ! class_exists( 'Ce_cache_factory' ) )
			{
				include PATH_THIRD . 'ce_cache/libraries/Ce_cache_factory.php';
			}

			$classes = Ce_cache_factory::factory( $this->valid_drivers );

			//set a blank error string
			$errors = '';

			//get the site name
			$site = Ce_cache_utils::get_site_label();

			//the prefix for this site
			$prefix = 'ce_cache/' . $site . '/';

			foreach ( $classes as $class )
			{
				//attempt to get the items for the path
				$items = $class->get_all( $prefix );

				$driver = $class->name();

				if ( $items !== FALSE )
				{
					foreach ( $items as $item )
					{
						$t = $prefix . ( ( $driver == 'db' || $driver == 'apc' ) ? $item['id'] : $item );
						if ( $class->delete( $t  ) === FALSE )
						{
							$errors .= '<p cass="error">'. sprintf( lang( "ce_cache_error_deleting_item" ), $t ) . '</p>';
						}
					}
				}
			}

			//if the error string is not blank, return the error(s)
			if ( $errors != '' )
			{
				return $errors;
			}

			//cache was cleared successfully
			$show_form = FALSE;
		}

		//view data
		$data = array(
			'action_url' => 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . "module=ce_cache" . AMP . 'method=clear_site_caches',
			'show_form' => $show_form,
			'back_link' => '<a class="submit" href="' . $this->base_url . AMP . 'method=index' . '">' . lang( "ce_cache_back_to" ) . ' ' . lang( 'module_home' ) . '</a>'
		);

		//return the view
		return $this->EE->load->view( 'clear_site_caches', $data, TRUE );
	}

	public function breaking()
	{
		//set the breadcrumb for the index page
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('ce_cache_module_name') );

		//set the page title
		$this->EE->cp->set_variable( 'cp_page_title', lang( "ce_cache_channel_cache_breaking" ) );

		//get the current site
		$site_id = $this->EE->config->item( 'site_id' );

		//get all of the channels
		$results = $this->EE->db->query( '
		SELECT channel_title AS title, channel_id AS id
		FROM exp_channels
		WHERE site_id = ?
		ORDER BY channel_title ASC', array( $site_id ) );

		$channels = array();
		if ($results->num_rows() > 0)
		{
			$channels = $results->result_array();
		}

		//load needed classes
		$this->EE->load->library('table');
		$this->EE->load->helper('form');

		//view data
		$data = array(
			'module' => $this->name,
			'channels' => $channels
		);

		//return the index view
		return $this->EE->load->view( 'cache_break_index', $data, TRUE );
	}

	public function breaking_settings()
	{
		//set the breadcrumb for the index page
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('ce_cache_module_name') );

		//set the breadcrumb for the breaking page
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=breaking', lang('ce_cache_channel_cache_breaking') );

		//load classes/helpers
		$this->EE->load->helper( array( 'form', 'url' ) );
		$this->EE->load->library( 'form_validation' );

		//grab the channel_id from the get/post data
		$channel_id = $this->EE->input->get_post( 'channel_id', TRUE );

		if ( ! isset( $channel_id ) || ! is_numeric( $channel_id ) )
		{
			return '<p>' . lang( 'ce_cache_error_no_channel' ) . '</p>';
		}

		if ( $channel_id === '0' ) //this id is designated to include settings for all channel entries
		{
			$channel_title = lang( 'ce_cache_any_channel' );
		}
		else //a specific channel id is possibly set
		{
			//get all of the channels
			$results = $this->EE->db->query( 'SELECT channel_title FROM exp_channels WHERE channel_id = ?', $channel_id );

			if ( $results->num_rows() == 1 ) //we found the channel
			{
				$result = $results->row_array();
				$channel_title = $result['channel_title'];
			}
			else //the channel was not found
			{
				return '<p>' . lang( 'ce_cache_error_channel_not_found' ) . '</p>';
			}
		}

		//load the styles
		$this->EE->cp->add_to_head( PHP_EOL . '<link rel="stylesheet" href="' . $this->EE->config->item( 'theme_folder_url' ) . 'third_party/ce_cache/css/ce_cache_break.css">' );

		//load in the CE Cache JavaScript
		$this->EE->cp->load_package_js( 'ce_cache_break' );

		//set the page title
		$this->EE->cp->set_variable( 'cp_page_title', sprintf( lang( "ce_cache_channel_breaking_settings" ), $channel_title ) );

		//flag to show the form (TRUE) or the confirmation page (FALSE)
		$show_form = TRUE;

		//make sure the correct version of the module is installed
		if ( ! $this->EE->db->table_exists( 'ce_cache_breaking' ) )
		{
			return '<p>' . lang( 'ce_cache_error_module_not_installed' ) . '</p>';
		}

		//defaults
		$refresh_time = 0;
		$refresh = FALSE;
		$tags = array();
		$items = array();
		$errors = array(
			'ce_cache_item' => array(),
			'ce_cache_tag' => array(),
			'ce_cache_refresh_time' => ''
		);

		//get saved settings if they exist
		$results = $this->EE->db->query( 'SELECT * FROM exp_ce_cache_breaking WHERE channel_id = ?', $channel_id );

		if ( $results->num_rows() == 1 ) //we found the channel
		{
			$result = $results->row_array();
			$refresh = ( $result['refresh'] == 'y' );
			$refresh_time = $result['refresh_time'];
			$tags = explode( '|', $result['tags'] );
			$items = explode( '|', $result['items'] );
		}

		if ( $this->EE->input->post( 'submit', TRUE ) !== FALSE ) //the form was submitted
		{
			$has_errors = FALSE;

			$submitted_items = $this->EE->input->post( 'ce_cache_item', TRUE );
			$items = ( ! empty( $submitted_items ) && is_array( $submitted_items ) ) ? $submitted_items : array();
			$submitted_tags = $this->EE->input->post( 'ce_cache_tag', TRUE );
			$tags = ( ! empty( $submitted_tags ) && is_array( $submitted_tags ) ) ? $submitted_tags : array();

			$submitted_refresh = $this->EE->input->post( 'ce_cache_refresh', TRUE );
			$refresh = ( $submitted_refresh == 'y' );

			$submitted_refresh_time = $this->EE->input->post( 'ce_cache_refresh_time', TRUE );
			if ( is_numeric( $submitted_refresh_time ) && in_array( $submitted_refresh_time, array( 0, 1, 2, 3, 4, 5 ) ) )
			{
				$refresh_time = $submitted_refresh_time;
			}
			else //invalid refresh time
			{
				$has_errors = TRUE;
				$errors['ce_cache_refresh_time'] = 'ce_cache_error_invalid_refresh_time';
			}

			//validate the items
			foreach ( $items as $index => $item )
			{
				$item = trim( $item );

				if ( empty( $item ) )
				{
					unset( $items[$index] );
					continue;
				}

				$items[$index] = $item;

				//make sure the item starts with local/ or global/
				if ( ! preg_match( '@^(local/|global/)@si', $item ) )
				{
					$has_errors = TRUE;
					$errors['ce_cache_item'][$index] = 'ce_cache_error_invalid_item_start';
					continue;
				}

				if ( strlen( $item ) > 250 )
				{
					$has_errors = TRUE;
					$errors['ce_cache_item'][$index] = 'ce_cache_error_invalid_item_length';
					continue;
				}
			}

			//validate the tags
			foreach ( $tags as $index => $tag )
			{
				$tag = trim( $tag );

				if ( empty( $tag ) )
				{
					unset( $tags[$index] );
					continue;
				}

				$tags[$index] = $tag;

				//make sure the item starts with local/ or global/
				if ( strpos( $tag, '|' ) !== FALSE )
				{
					$has_errors = TRUE;
					$errors['ce_cache_tag'][$index] = 'ce_cache_error_invalid_tag_character';
					continue;
				}

				if ( strlen( $tag ) > 100 )
				{
					$has_errors = TRUE;
					$errors['ce_cache_tag'][$index] = 'ce_cache_error_invalid_tag_length';
					continue;
				}
			}

			if ( ! $has_errors ) //no errors
			{
				//delete any previously saved data for this chanel
				$this->EE->db->delete( 'ce_cache_breaking', array( 'channel_id' => $channel_id ) );

				$data = array(
					'channel_id' => $channel_id,
					'tags' => implode( '|', $tags ),
					'items' => implode( '|', $items ),
					'refresh' => ( $refresh ) ? 'y' : 'n',
					'refresh_time' => $refresh_time
				);

				//save the data
				$this->EE->db->insert( 'ce_cache_breaking', $data );
			}

			//cache was cleared successfully
			$show_form = $has_errors;
		}

		//trim all tags and make sure empty tags are removed
		foreach ( $tags as $index => $tag )
		{
			$tag = trim( $tag );
			if ( empty( $tag ) )
			{
				unset( $tags[ $index ] );
			}
			else
			{
				$tags[$index] = $tag;
			}
		}

		//trim all items and make sure empty items are removed
		foreach ( $items as $index => $item )
		{
			$item = trim( $item );
			if ( empty( $item ) )
			{
				unset( $items[ $index ] );
			}
			else
			{
				$items[$index] = $item;
			}
		}

		//view data
		$data = array(
			'action_url' => 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . "module=ce_cache" . AMP . 'method=breaking_settings',
			'show_form' => $show_form,
			'channel_id' => $channel_id,
			'channel_title' => $channel_title,
			'back_link' => '<a class="submit" href="' . $this->base_url . AMP . 'method=breaking' . '">' . lang( "ce_cache_back_to" ) . ' ' . lang( 'ce_cache_channel_cache_breaking' ) . '</a>',
			'items' => $items,
			'tags' => $tags,
			'refresh_time' => $refresh_time,
			'refresh' => $refresh,
			'errors' => $errors
		);

		//return the view
		return $this->EE->load->view( 'cache_break_settings', $data, TRUE );
	}

	/**
	 * Clear the tags. //TODO add item refreshing
	 *
	 * @return mixed
	 */
	public function clear_tags()
	{
		//set the breadcrumb for the index page
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('ce_cache_module_name') );

		$this->EE->cp->set_variable( 'cp_page_title', lang('ce_cache_clear_tagged_items') );

		//load needed classes
		$this->EE->load->library('table');
		$this->EE->load->helper('form');

		//get the site name and prefix
		$site = Ce_cache_utils::get_site_label();

		//the prefix for this site
		$prefix = 'ce_cache/' . $site . '/';

		//whether or not to show the form
		$show_form = TRUE;

		//the tags
		$tags = array();
		$selected = array();

		//get all of the tags for the current site
		$tagged_items = $this->EE->db->query( "
		SELECT
		DISTINCT tag
		FROM exp_ce_cache_tagged_items
		WHERE SUBSTRING( item_id, 1, " . strlen( $prefix )  . " ) = '" . $this->EE->db->escape_str( $prefix ) . "'
		ORDER BY tag ASC" );
		if ( $tagged_items->num_rows() > 0 )
		{
			$rows = $tagged_items->result_array();
			foreach ( $rows as $row )
			{
				$tags[] = $row['tag'];
			}
		}
		$tagged_items->free_result();


		if ( $this->EE->input->post( 'submit', TRUE ) !== FALSE ) //the form was submitted
		{
			//selected
			$selected = $this->EE->input->post( 'ce_cache_tags', TRUE );
			if ( ! is_array( $selected ) )
			{
				$selected = array();
			}

			//make sure the selected items have tags that exist, mostly for validation purposes
			foreach ( $selected as $index => $tag )
			{
				if ( ! in_array( $tag, $tags ) )
				{
					unset( $selected[ $index ] );
				}
			}

			//check if we have tags
			if ( count( $selected ) > 0 ) //we have valid selected tags to delete
			{
				//load the cache break class, if needed
				if ( ! class_exists( 'Ce_cache_break' ) )
				{
					include PATH_THIRD . 'ce_cache/libraries/Ce_cache_break.php';
				}

				//instantiate the class break and call the break cache method
				$cache_break = new Ce_cache_break();

				//clear the tag items and tags
				$cache_break->break_cache( array(), $selected, FALSE );

				$show_form = FALSE;
			}
			else //we have no tags
			{
				$show_form = TRUE;
			}
		}

		//view data
		$data = array(
			'action_url' => 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . "module=ce_cache" . AMP . 'method=clear_tags',
			'show_form' => $show_form,
			'back_link' => '<a class="submit" href="' . $this->base_url . AMP . 'method=index' . '">' . lang( "ce_cache_back_to" ) . ' ' . lang( 'module_home' ) . '</a>',
			'tags' => $tags,
			'selected' => $selected
		);

		//return the view
		return $this->EE->load->view( 'clear_tags', $data, TRUE );
	}

	private function ends_with( $string, $test )
	{
		$strlen = strlen( $string );
		$testlen = strlen( $test );
		if ( $testlen > $strlen )
		{
			return false;
		}
		return substr_compare( $string, $test, -$testlen ) === 0;
	}
}
/* End of file mcp.ce_cache.php */
/* Location: /system/expressionengine/third_party/ce_cache/mcp.ce_cache.php */