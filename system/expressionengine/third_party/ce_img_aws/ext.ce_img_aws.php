<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CE Image <-> AWS
 *
 * Last Updated: 19 June 2012
 *
 * @author		Aaron Waldon
 * @copyright	Copyright (c) 2011 Causing Effect
 * @license		http://www.causingeffect.com/software/expressionengine/ce-image/license-agreement
 * @link		http://www.causingeffect.com
 */

class Ce_img_aws_ext
{
	public $settings 		= array();
	public $description		= 'Amazon Web Services integration with CE Image';
	public $docs_url		= '';
	public $name			= 'CE Image - AWS';
	public $settings_exist	= 'n';
	public $version			= '1.0';

	private $EE;
	private static $debug = null;

	//Amazon S3 settings
	private static $bucket = '';
	private static $aws_key = '';
	private static $aws_secret_key = '';
	private static $aws_storage_class = 'STANDARD';
	private static $aws_request_headers = array();

	/**
	 * Constructor
	 *
	 * @param string|array $settings array or empty string if none exist.
	 */
	public function __construct( $settings = '' )
	{
		$this->EE =& get_instance();
		$this->settings = $settings;

		if ( ! isset( self::$debug ) )
		{
			//if the template debugger is enabled, and a super admin user is logged in, enable debug mode
			self::$debug = FALSE;
			if ( $this->EE->session->userdata['group_id'] == 1 && $this->EE->config->item('template_debugging') == 'y' )
			{
				self::$debug = TRUE;
			}
		}
	}

	/**
	 * Activate the extension by entering it into the exp_extensions table
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		//settings
		$this->settings = array();

		$hooks = array(
			'ce_img_pre_parse' => 'pre_parse',
			'ce_img_start' => 'update_valid_params'
		);

		foreach ( $hooks as $hook => $method )
		{
			//sessions end hook
			$data = array(
				'class'		=> __CLASS__,
				'method'	=> $method,
				'hook'		=> $hook,
				'settings'	=> serialize( $this->settings ),
				'priority'	=> 9,
				'version'	=> $this->version,
				'enabled'	=> 'y'
			);
			$this->EE->db->insert( 'extensions', $data );
		}
	}

	/**
	 * Disables the extension by removing it from the exp_extensions table.
	 *
	 * @return void
	 */
	public function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}

	/**
	 * Updates the extension by performing any necessary db updates when the extension page is visited.
	 *
	 * @param string $current
	 * @return mixed void on update, false if none
	 */
	public function update_extension( $current = '' )
	{
		if ( $current == '' OR $current == $this->version )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Update which tag params to ignore in CE Image output. This method will only be called once by CE Image.
	 */
	public function update_valid_params()
	{
		//add the bucket parameter to the list of valid params
		Ce_img::$valid_params[] = 'bucket';
		//these are not ever used as params, but we'll exclude them anyway to prevent people that don't read instructions from accidentally disclosing their settings in the tag
		Ce_img::$valid_params[] = 'aws_request_headers';
		Ce_img::$valid_params[] = 'aws_storage_class';
		Ce_img::$valid_params[] = 'aws_key';
		Ce_img::$valid_params[] = 'aws_secret_key';

		//bucket
		if (isset($this->EE->config->_global_vars['ce_image_bucket']) && $this->EE->config->_global_vars['ce_image_bucket'] != '') //global array
		{
			self::$bucket = $this->EE->config->_global_vars['ce_image_bucket'];
		}
		else if ($this->EE->config->item('ce_image_bucket') != FALSE) //config
		{
			self::$bucket = $this->EE->config->item('ce_image_bucket');
		}

		//aws_key
		if ( $this->EE->config->item('ce_image_aws_key') != FALSE )
		{
			self::$aws_key = $this->EE->config->item('ce_image_aws_key');
		}

		//aws_secret_key
		if ( $this->EE->config->item('ce_image_aws_secret_key') != FALSE )
		{
			self::$aws_secret_key = $this->EE->config->item('ce_image_aws_secret_key');
		}

		//aws_storage_class
		if ( $this->EE->config->item('ce_image_aws_storage_class') != FALSE )
		{
			self::$aws_storage_class = $this->EE->config->item('ce_image_aws_storage_class');
		}

		//aws_request_headers
		if ( $this->EE->config->item('ce_image_aws_request_headers') != FALSE )
		{
			self::$aws_request_headers = $this->EE->config->item('ce_image_aws_request_headers');
		}
	}

	/**
	 * This method will be called right before a CE Image single or pair tag is parsed.
	 *
	 * @param string $tagdata   The un-parsed tagdata.
	 * @param array  $variables The CE Image variables.
	 * @param string $var_prefix The variable prefix.
	 * @return string $tagdata The manipulated tagdata.
	 */
	public function pre_parse( $tagdata, $variables, $var_prefix )
	{
		//bucket param
		$bucket = $this->EE->TMPL->fetch_param('bucket', self::$bucket );

		//status flag
		$success = true;

		$aws_url = '';

		//if the bucket is empty, let's bail
		if ( empty( $bucket ) )
		{
			return str_replace( '{' . $var_prefix . 'aws_url}', $aws_url, $tagdata );
		}

		//the final_action will be 'saved', 'cached', or 'none' depending on what was done with the image
		$action = $variables[ $var_prefix . 'final_action' ];

		//Include the S3 Class if needed
		if ( ! class_exists( 'Ce_s3' ) )
		{
			require PATH_THIRD . 'ce_img_aws/libraries/Ce_s3.php';
		}
		//instantiate the class
		$s3 = new Ce_s3( self::$aws_key, self::$aws_secret_key );

		$path = $variables[ $var_prefix . 'path' ];
		$relative = $variables[ $var_prefix . 'made' ];
		$relative = ltrim( $relative, '/' );

		if ( $action == 'saved' ) //upload the image
		{
			$success = $this->upload_resource( $s3, $bucket, $path, $relative );
		}
		else //make sure the image is uploaded
		{
			//see if the object already exists
			$status = false;
			try
			{
				$status = $s3->getObjectInfo( $bucket, $relative, false );

				if ( $status === false ) //the file does not exist
				{
					$success = $this->upload_resource( $s3, $bucket, $path, $relative );
				}
			}
			catch ( Exception $e )
			{
				$this->log_debug_messages( 'An error occurred while trying to see if the S3 object existed:' . $e );
				$success = false;
			}
		}

		if ( ! $success ) //the file was not found and/or upload was not successful
		{
			return str_replace( '{' . $var_prefix . 'aws_url}', $aws_url, $tagdata );
		}

		//create the AWS URL
		$aws_url = 'http://' . $bucket . '.s3.amazonaws.com/' . $relative;
		//remove duplicate slashes, if any
		$aws_url = preg_replace( '#(?<!:)//+#', '/', $aws_url );
		//encode spaces, if any
		$aws_url = str_replace( ' ', '%20', $aws_url );

		//replace any {aws_url} and src="{made}" variables with the AWS URL
		return str_replace(
			array(
				'{' . $var_prefix . 'aws_url}',
				' src="{' . $var_prefix . 'made}" ',
				" src='{' . $var_prefix . 'made}' "
			),
			array(
				$aws_url,
				' src="' . $aws_url . '" ',
				" src='" . $aws_url . "'"
			),
			$tagdata
		);
	}

	/**
	 * @param $s3 mixed The Amazon S3 Class
	 * @param $bucket string The bucket name.
	 * @param $path string The image's server path.
	 * @param $relative string The image's relative path.
	 * @return bool Whether or not the image was uploaded successfully.
	 */
	private function upload_resource( &$s3, $bucket, $path, $relative )
	{
		$success = true;

		$resource = Ce_s3::inputFile( $path ); //load the file

		if ( $resource != false ) //the file was loaded successfully
		{
			if ( ! $s3->putObject( $resource, $bucket, $relative, Ce_s3::ACL_PUBLIC_READ, array(), self::$aws_request_headers, self::$aws_storage_class ) ) //fail
			{
				$this->log_debug_messages( 'There was a problem uploading your file to AWS.' );
				$success = false;
			}
			else //success
			{
				$this->log_debug_messages( 'Your image was successfully uploaded to AWS.' );
			}
		}
		else //there was a problem with the file
		{
			$this->log_debug_messages( 'The resource to be uploaded to AWS is not valid.' );
			$success = false;
		}

		//remove the resource
		if ( is_resource( $resource ) )
		{
			imagedestroy( $resource );
		}
		unset( $resource );

		return $success;
	}

	/**
	 * Simple method to log an array of debug messages to the EE Debug console.
	 *
	 * @param array $messages The debug messages.
	 * @return void
	 */
	protected function log_debug_messages( $messages = array() )
	{
		//no need to stick around if the template debugger is not on, or we are not in a template
		if ( empty( self::$debug ) || ! isset( $this->EE->TMPL ) )
		{
			return;
		}

		if ( is_string( $messages ) )
		{
			$messages = array( $messages );
		}

		if ( ! is_array( $messages ) )
		{
			return;
		}

		foreach ( $messages as $message )
		{
			$this->EE->TMPL->log_item( '&nbsp;&nbsp;***&nbsp;&nbsp;CE Image - AWS debug: ' . $message );
		}
	}
}
/* End of file ext.ce_img_aws.php */
/* Location: /system/expressionengine/third_party/ce_aws/ext.ce_img_aws.php */