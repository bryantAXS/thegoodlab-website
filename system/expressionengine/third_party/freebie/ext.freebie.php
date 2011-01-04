<?php 

/**
 * Freebie Extension Class for ExpressionEngine 2
 *
 * @package   Freebie
 * @author    Doug Avery <doug.avery@viget.com>
 */
class Freebie_ext {

  /**
   * Required vars
   */
  var $name = 'Freebie';
  var $description = 'Tell EE to ignore specific segments when routing URLs'; 
  var $version = '0.0.5';
  var $settings_exist = 'y';
  var $docs_url = 'http://github.com/averyvery/Freebie#readme';
  
  /**
   * Settings
   */
  var $settings = array();
  var $settings_default = array(
    'to_ignore'      => 'success|error|preview',
    'ignore_beyond'  => '',
    'break_category' => 'no',
    'remove_numbers' => 'no',
    'always_parse'   => ''
  );

  function settings(){
    $settings['to_ignore']      = array('t', null, $this->settings_default['to_ignore']);
    $settings['ignore_beyond']  = array('t', null, $this->settings_default['ignore_beyond']);
    $settings['break_category'] = array('r', array('yes' => 'yes', 'no' => 'no'),   
                                         $this->settings_default['break_category']);
    $settings['remove_numbers'] = array('r', array('yes' => 'yes', 'no' => 'no'),   
                                         $this->settings_default['remove_numbers']);                                         
    $settings['always_parse']   = array('t', null, $this->settings_default['always_parse']);
    return $settings;
  }


  /**
   * Extension constructor
   *   the unaltered URI is 'dirty' — it potentially has /segments/ that will break our site
   *   the final URI and segments will be 'clean' — EE will use them for routing, and all will be well
   */
  function Freebie_ext($settings='')
  {
        
    // get EE global instance and extension settings
    $this->settings = $settings;
    $this->EE =& get_instance();
    
    if($this->should_execute()){
      
      // clear cache if necessary
      $this->clear_cache();
      
      // strips out any & variables
      $this->strip_variables();
      
     /**
      * EE 2.0 relies on an internal array of segments for routing
      *   we'll be 'cleaning' our URI and producing shiny new segments from it,
      *   but we still want to have access to the 'dirty' segments as {segment_1}, etc.
      */
     $this->set_dirty_segments_as_global_vars();

     // prep the user settings to use for cleaning the URI
     $this->settings['to_ignore']     = $this->parse_settings($this->settings['to_ignore']);
     $this->settings['ignore_beyond'] = $this->parse_settings($this->settings['ignore_beyond']);

     // if category breaking is on, retrieve the category url indicator and set it as a break segment
     $this->break_on_category_indicator();
     
     // determine which segments to ALWAYS parse, and to always parse beyond
     $this->get_always_parse();

     // remove the 'dirty' bits from the URI, which a user has specified in the settings
     $this->clean_uri();
        
     // re-fill the segment arrays from our new, clean URI
     $this->that_was_a_freebie();

     // re-execute the routing based on clean segments
     $RTR =& load_class('Router', 'core');
     $RTR->_parse_routes();

     // re-indexing segments (moving 0 to 1, 1 to 2, etc) is required after routing
     $this->EE->uri->_reindex_segments();
    
    }

  }
  
   
  /**
   * check to see if the conditions are in place to run freebie
   */
  function should_execute(){
    
    // is a URI? (lame test for checking to see if we're viewing the CP or not)
    return isset($this->EE->uri->uri_string) &&
           $this->EE->uri->uri_string != '' &&
               
           // Freebie actually executes twice - but the second time,
           // the "settings" object isn't an array, which breaks it.
           // (No idea why). Checking type fixes this.
           gettype($this->settings) == 'array' &&
           
           // If the settings don't exist, don't check if they're blank
           (
             (
               isset($this->settings['to_ignore']) &&        
               $this->settings['to_ignore'] != ''
             ) || (
               isset($this->settings['ignore_beyond']) &&        
               $this->settings['ignore_beyond'] != ''               
             )
           );
    
  }
  
  /**
   * Remove any variables from the segments
   */
  function strip_variables(){

    $get_pattern = '#&.*?$#';
    $to_match = $this->EE->uri->uri_string;
    $matches = array();
    
    preg_match( $get_pattern, $to_match, $matches); 
    $get_vars_unparsed = substr( $matches[0], 1 );
    $get_vars_parsed = explode('&', $get_vars_unparsed);
    
    foreach($get_vars_parsed as $var) {
      $var = explode('=', $var);
      $key = $var[0];
      $val = ( isset( $var[1] ) ) ? $var[1] : true;
      $_GET[$key] = $val;
    }
                    
    // remove them from the URI string
    $this->EE->uri->uri_string = preg_replace('#&.*?$#', '', $to_match); 
    
  }
  
  /**
   * Clear the cache on the first (uncached) pageload since saving
   */
  function clear_cache(){
    
    $results = $this->EE->db->query("SELECT * FROM exp_extensions WHERE class='Freebie_ext'");
    $db_settings = array();

    if ( $results->num_rows() > 0 ) {
    	foreach( $results->result_array() as $row ) {
    		$db_settings = ( unserialize( $row['settings'] ) );
    	}
    }
    
    if ( ! isset( $db_settings['cache_cleared'] ) ) {
      
      // clear the DB cache
      $this->EE->functions->clear_caching('db');      
      
      // add 'cache_cleared' to the settings
      $db_settings['cache_cleared'] = 'yes';
      $data = array('settings' => serialize($db_settings) );   

      $sql = $this->EE->db->update_string('exp_extensions', $data, "class = 'Freebie_ext'");
      $this->EE->db->query($sql);

    }
      
  }
  
  /**
   * convert the original segments from the URI to {segment_n}-type global variables
   */
  function set_dirty_segments_as_global_vars(){
    $segments = $this->EE->uri->segments;
    $segments = array_pad($segments, 10, '');
    for ($i = 1; $i <= count($segments); $i++){
      $this->EE->config->_global_vars['freebie_'.$i] = $segments[$i - 1];      
    }
  }
  
  /**
   * translate user settings to stuff we can use in the code
   */
  function parse_settings($original_str){

    // convert newline- and space-delimited settings to pipe-delimited ones
    $str = preg_replace('/(\n| )/', '|', $original_str, -1);

    // turn *s into true regex wildcards
    return preg_replace('/\*/', '.*?', $str, -1);

  }

  /**
   * add the category url indicator to the "break" array
   */  
  function break_on_category_indicator(){
        
    // did user set 'break category' to 'yes'?
    $break_category = isset( $this->settings['break_category'] ) &&
                      $this->settings['break_category'] == 'yes' &&
                      $this->EE->config->config['use_category_name'] == 'y';

    if ( $break_category ) {
      $this->settings['to_ignore']     .= '|'.$this->EE->config->config['reserved_category_word'];
      $this->settings['ignore_beyond'] .= '|'.$this->EE->config->config['reserved_category_word'];
    }
        
  }
  
  /**
   * get specific segments that we ALWAYS want to parse, and to parse beyond
   */  
  function get_always_parse(){

    $this->settings['always_parse'] .= '|' . $this->EE->config->config['profile_trigger'];
        
  }
  

  /**
   * remove segments, based on the user's settings
   */  
  function clean_uri(){
    
    // make an array full of "original" segments, 
    // and a blank array to move the good ones too
    $dirty_array    = explode('/', $this->EE->uri->uri_string);
    $clean_array    = array();

    // did user set 'remove numbers' to 'yes'?
    $remove_numbers = isset($this->settings['remove_numbers']) &&
                      $this->settings['remove_numbers'] == 'yes';

    $break = false;
    $parse_all_remaining = false;

    // move any segments that don't match patterns to clean array
    foreach ($dirty_array as $segment){

      $is_not_a_always_parse_segment = 
        preg_match('#^('.$this->settings['always_parse'].')$#', $segment ) == false;
                  
      if( $is_not_a_always_parse_segment && $parse_all_remaining == false ){

        $should_be_ignored = preg_match('#^('.$this->settings['to_ignore'].')$#', $segment ) == false;

        if( $should_be_ignored && $break == false ){

          // if this segment isn't killed by the "no numbers" setting, 
          // move it to the new array
          if(!$remove_numbers || !preg_match('/(\/[0-9]+|^[0-9]+)/', $segment)){
            array_push($clean_array, $segment);  
          }

        } 

        // if this segment is one of the breakers, stop looping        
        if( preg_match('#^('.$this->settings['ignore_beyond'].')$#', $segment) ){
          $break = true;
        }

      } else {

        array_push( $clean_array, $segment );  
        $parse_all_remaining = true;

      }
      
    } 
                    
    if(count($clean_array) != 0){
      $this->EE->uri->uri_string = implode('/', $clean_array);      
    } else {
      $this->EE->uri->uri_string = '';      
    }
                
  }
    
  /**
   * Unset existing internal segment arrays,
   *   fetch new ones from the clean URI
   */
  function that_was_a_freebie(){
    $this->EE->uri->segments = array();
    $this->EE->uri->rsegments = array();
    $this->EE->uri->_explode_segments();
  }
  
  /**
   * Activate Extension
   */
  function activate_extension()
  {
    
    $data = array(
      'class'       => 'Freebie_ext',
      'hook'        => 'sessions_start',
      'method'      => 'Freebie_ext',
      'settings'    => serialize($this->settings_default),
      'priority'    => 10,
      'version'     => $this->version,
      'enabled'     => 'y'
    );

    // insert in database
    $this->EE->functions->clear_caching('db');
    $this->EE->db->insert('exp_extensions', $data);
          
  }

  /**
   * Delete extension
   */
  function disable_extension()
  {
    $this->EE->functions->clear_caching('db');
    $this->EE->db->where('class', 'Freebie_ext');
    $this->EE->db->delete('exp_extensions');
  }


}
