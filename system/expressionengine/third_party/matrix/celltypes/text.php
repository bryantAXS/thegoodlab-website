<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Text Celltype Class for EE2
 * 
 * @package   Matrix
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2010 Pixel & Tonic, LLC
 */
class Matrix_text_ft {

	var $info = array(
		'name' => 'Text'
	);

	/**
	 * Constructor
	 */
	function Matrix_text_ft()
	{
		$this->EE =& get_instance();

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------

		if (! isset($this->EE->session->cache['matrix']['celltypes']['text']))
		{
			$this->EE->session->cache['matrix']['celltypes']['text'] = array();
		}
		$this->cache =& $this->EE->session->cache['matrix']['celltypes']['text'];
	}

	// --------------------------------------------------------------------

	/**
	 * Display Cell Settings
	 */
	function display_cell_settings($data)
	{
		if (! isset($data['maxl'])) $data['maxl'] = '';
		if (! isset($data['multiline'])) $data['multiline'] = 'n';

		return array(
			array(lang('maxl'), form_input('maxl', $data['maxl'], 'class="matrix-textarea"')),
			array(lang('multiline'), form_checkbox('multiline', 'y', ($data['multiline'] == 'y')))
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Display Cell
	 */
	function display_cell($data)
	{
		if (! isset($this->cache['displayed']))
		{
			// include matrix_text.js
			$theme_url = $this->EE->session->cache['matrix']['theme_url'];
			$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_url.'scripts/matrix_text.js"></script>');

			$this->cache['displayed'] = TRUE;
		}

		$r['class'] = 'matrix-text';
		$r['data'] = '<textarea class="matrix-textarea" name="'.$this->cell_name.'" rows="1">'.$data.'</textarea>';

		if (isset($this->settings['maxl']) && $this->settings['maxl'])
		{
			$r['data'] .= '<div><div></div></div>';
		}

		return $r;
	}

}
