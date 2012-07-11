<?php if (! defined('BASEPATH')) die('No direct script access allowed');


// load dependencies
require_once PATH_THIRD.'assets/helper.php';


/**
 * Assets File
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Assets_file {

	private $EE;
	private $helper;
	private $row;
	private $path;

	private $extension;
	private $filedir;
	private $image_size;
	private $kind;
	private $server_path;
	private $subfolder;
	private $subpath;

	var $selected = FALSE;

	/**
	 * Constructor
	 */
	function __construct($path)
	{
		$this->path = $path;

		// -------------------------------------------
		//  Make a local reference to the EE super object
		// -------------------------------------------

		$this->EE =& get_instance();

		// -------------------------------------------
		//  Get helper
		// -------------------------------------------

		$this->helper = get_assets_helper();

		// -------------------------------------------
		//  Parse the path
		// -------------------------------------------

		$this->helper->parse_filedir_path($this->path, $this->filedir, $this->subpath);

		if ($this->filedir)
		{
			$this->server_path = $this->filedir->server_path . $this->subpath;
		}
	}

	// -----------------------------------------------------------------------

	/**
	 * Call
	 */
	function __call($name, $arguments)
	{
		return $this->row($name);
	}

	/**
	 * Set Row
	 */
	function set_row($row)
	{
		$this->row = $row;
	}

	/**
	 * Load Row
	 */
	function load_row()
	{
		if (! isset($this->row))
		{
			$query = $this->EE->db->get_where('assets', array('file_path' => $this->path));

			$this->row = $query->num_rows() ? $query->row_array() : FALSE;
		}

		return !! $this->row;
	}

	function row($key = FALSE)
	{
		if ($key === FALSE)
		{
			// just return the whole row
			return $this->load_row() ? $this->row : array();
		}

		return ($this->load_row() && isset($this->row[$key])) ? $this->row[$key] : '';
	}

	// -----------------------------------------------------------------------

	private function _set_image_size()
	{
		if (! isset($this->image_size))
		{
			$this->image_size = ($this->kind() == 'image') ? getimagesize($this->server_path) : array('', '');
		}
	}

	// -----------------------------------------------------------------------

	function date_modified()
	{
		return filemtime($this->server_path);
	}

	function exists()
	{
		return (isset($this->server_path) && file_exists($this->server_path) && is_file($this->server_path));
	}

	function extension()
	{
		if (! isset($this->extension))
		{
			$this->extension = strtolower(pathinfo($this->server_path, PATHINFO_EXTENSION));
		}

		return $this->extension;
	}

	function filedir_id()
	{
		return $this->filedir->id;
	}

	function filedir_path()
	{
		return $this->filedir->server_path;
	}

	function filedir_url()
	{
		return $this->filedir->url;
	}

	function filename()
	{
		return basename($this->server_path);
	}

	function folder()
	{
		return $this->filedir->name . ($this->subpath ? '/'.$this->subpath : '');
	}

	function height()
	{
		$this->_set_image_size();

		return $this->image_size[1];
	}

	function kind()
	{
		if (! isset($this->kind))
		{
			$this->kind = $this->helper->get_kind($this->server_path);
		}

		return $this->kind;
	}

	function path()
	{
		return $this->path;
	}

	function server_path()
	{
		return $this->server_path;
	}

	function size()
	{
		return filesize($this->server_path);
	}

	function subfolder()
	{
		if (! isset($this->subfolder))
		{
			$this->subfolder = dirname($this->subpath);
			if ($this->subfolder == '.') $this->subfolder = '';
		}

		return $this->subfolder;
	}

	function subpath()
	{
		return $this->subpath;
	}

	function url()
	{
		return $this->filedir->url . str_replace(' ', '%20', $this->subpath);
	}

	function width()
	{
		$this->_set_image_size();

		return $this->image_size[0];
	}

}
