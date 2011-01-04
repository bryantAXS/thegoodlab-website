<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
						'pi_name'			=> 'Zeebra',
						'pi_version'		=> '1.1',
						'pi_author'			=> 'Filip Vanderstappen',
						'pi_author_url'		=> 'http://filipvanderstappen.be/',
						'pi_description'	=> '',
						'pi_usage'			=> Zeebra::usage()
					);


/**
 * Zeebra Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			Filip Vanderstappen
 * @copyright		Copyright (c) 2010, Filip Vanderstappen
 * @link			http://filipvanderstappen.be/ee/details/zeebra
 */

class Zeebra {

    var $returnCount = 0;
	var $return_data;
	var $tag = '{zeebra}';
	var $tagTotal = 0;
	var $tagCount = 1;
	var $attrTips;
	var $attrTipsClass;
	var $attrInterval;
	var $attrIntervalClass;
	
	/**
	 * Constructor
	 *
	 */
	function Zeebra($str = '')
	{
		$this->EE =& get_instance();

        // Set vars
        $tagdata = $this->EE->TMPL->tagdata;
        $this->tagTotal = count(explode($this->tag, $tagdata)) - 1;
		$this->return_data = '';
		
		// Get attributes
		$this->attrTips = $this->EE->TMPL->fetch_param('tips', 'yes');
		$this->attrTipsClass = explode("|",$this->EE->TMPL->fetch_param('tipsclass', "first|last"));
		$this->attrInterval = $this->EE->TMPL->fetch_param('interval', '2');
		$this->attrIntervalClass = $this->EE->TMPL->fetch_param('intervalclass', 'item-%');
		
		// Parse tagdata
		$tmpData = $this->parseData($tagdata);
		
		// Return data
		$this->return_data = $tmpData;
	}
	
	function parseData($tagdata)
	{
	    // Search for the first zeebra in the wild
	    $tmpPos = stripos($tagdata, $this->tag);
	    $tmpClass = array();
	    
	    // If no zeebras are found in the bushes, go away 
	    if($tmpPos === false){
	        return $tagdata;
	    }
	    // Go catch them
	    else {
	        // If it's your first zeebra… shoot it!
	        if($this->tagCount == 1 && $this->attrTips == 'yes')
	        {
	            array_push($tmpClass, $this->attrTipsClass[0]);
	        }
	        
	        // If it's your last zeebra… finalize it.
	        if($this->tagCount === $this->tagTotal && $this->attrTips == 'yes')
	        {
	            array_push($tmpClass, $this->attrTipsClass[1]);
	        }
	        
	        // Get your zeebras in rows
	        if(is_numeric($this->attrInterval))
	        {
	            $tmpInterval = ($this->tagCount%$this->attrInterval == 0) ? $this->attrInterval : ($this->tagCount%$this->attrInterval);
	            array_push($tmpClass, str_replace("%", $tmpInterval, $this->attrIntervalClass));
	        }
	    }
	    
	    // Kill the zeebra and get it replaced with our classes
	    $tagdata = substr_replace($tagdata, implode(" ", $tmpClass), $tmpPos, strlen($this->tag));
	    
	    // Hunt for other zeebras
	    $this->tagCount++;
	    return $this->parseData($tagdata);
	}
	
	/**
	 * Usage
	 *
	 * Plugin Usage
	 *
	 * @access	public
	 * @return	string
	 */
	function usage()
	{
		ob_start(); 
		?>
        
        Zeebra is a very simple plugin that adds first and last classes to your lists/entries. It also add's classes for certain intervals (default 2, for odd and even rows).
        
        Attributes
        -------------
        
        tips (default: “yes”) (other values: “no”)
        tipsclass (default: “first|last”)
        interval (default: “2”) (other values: numeric, or “no”)
        intervalclass (default: “item-%”) (Can be any value, % will be replaced by the interval)
        
        
        Standard usage
        ----------------------
        
        {exp:zeebra}
         <ul>
         {exp:channel:entries}
         <li class="{zeebra}">{title}</li>
         {/exp:channel:entries}
         </ul>
        {/exp:zeebra}
        
        
        Interval attributes
        -----------------------
        
        {exp:zeebra tips="no" interval="5" intervalclass="nth-%"}
         <ul>
         {exp:channel:entries}
         <li class="{zeebra}">{title}</li>
         {/exp:channel:entries}
         </ul>
        {/exp:zeebra}
        
        
        Tips attributes
        -------------------
        
        {exp:zeebra tipsclass="uno|duo" interval="no"}
         <ul>
         {exp:channel:entries}
         <li class="{zeebra}">{title}</li>
         {/exp:channel:entries}
         </ul>
        {/exp:zeebra}

		<?php
		$buffer = ob_get_contents();
	
		ob_end_clean(); 

		return $buffer;
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file pi.classee_entries.php */
/* Location: ./system/expressionengine/third_party/classee_entries/pi.classee_entries.php */