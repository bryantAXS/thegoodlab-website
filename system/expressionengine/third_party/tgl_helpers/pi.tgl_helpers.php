<?php

$plugin_info = array(
  'pi_name' => 'TGL Helpers',
  'pi_version' =>'0.1',
  'pi_author' =>'Bryant Hughes',
  'pi_author_url' => 'http://thegoodlab/',
  'pi_description' => 'Just a few text helper functions',
  'pi_usage' => tgl_helpers::usage()
  );

class tgl_helpers {
	
	var $return_data = '';
	
	/** 
	 * Constructor
	 *
	 * Evaluates case values and extracts the content of the 
	 * first case that matches the variable parameter
	 *
	 * @access public
	 * @return void
	 */
	public function tgl_helpers() 
	{
		$this->EE =& get_instance();
	}

	public function clean_page_title()
	{

		$title = $this->EE->TMPL->fetch_param('value');

		$title = str_replace('&amp;', '-', $title);
		$title = str_replace("'", '', $title);

		return $title;
		
	}

	public function clean_html_entities(){
		
		$wrapped_in = $title = $this->EE->TMPL->fetch_param('value');
		$tagdata = $this->EE->TMPL->tagdata;

		//to html entities;  assume content is in the "content" variable
		$content = preg_replace_callback('/<pre.*?>(.*?)<\/pre>/imsu', create_function('$matches', 'return str_replace($matches[1],htmlentities($matches[1]),$matches[0]);'), $tagdata);

		return $content;

	} 

	//public 

	// usage instructions
	public function usage() 
	{
  		ob_start();
?>
-------------------
HOW TO USE
-------------------

Sorry no docs yet.

	<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}	
}