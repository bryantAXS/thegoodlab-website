<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *  MX Google Map Class for ExpressionEngine2
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author    Max Lazar <max@eec.ms>
 * @copyright Copyright (c) 2010 Max Lazar
 * @license   http://creativecommons.org/licenses/MIT/  MIT License
 */
 
class Mx_google_map_ft extends EE_Fieldtype {
	
	var $info = array(
		'name'		=> 'MX Google Maps',
		'version'	=> '1.3.0'
	);
	
	var $addon_name = 'mx_google_map';
	
	var $has_array_data = TRUE;
	
	function Mx_google_map_ft()
	{
		parent::EE_Fieldtype();
		if(defined('SITE_ID') == FALSE)
		define('SITE_ID', $this->EE->config->item('site_id'));
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Display Field on Publish
	 *
	 * @access	public
	 * @param	existing data
	 * @return	field html
	 *
	 */
	function display_field($data)
	{
		$custom_fields_js = '';
	
		$this->EE->lang->loadfile('mx_google_map');
		
		$data_points = array('latitude', 'longitude', 'zoom');	
		
		$entry_id = $this->EE->input->get('entry_id');

		if ($entry_id && $data)
		{
				list($latitude, $longitude, $zoom) = explode('|', $data);			
		//list($latitude, $longitude, $zoom, $e_id) = explode('|', $data);							
		}
		else
			{
				foreach($data_points as $key)
				{
					$$key = $this->settings[$key];
				}
		}
		
		$default_icon =  (isset($this->settings['icon'])) ? (($this->settings['icon'] != "") ?  $this->settings['icon']  : 'default') : 'default';
		$max_points =   (isset($this->settings['max_points'])) ? (($this->settings['max_points'] != "") ?  $this->settings['max_points']  : '2909') : '2909';
		$slide_bar = (isset($this->settings['slide_bar'])) ? (($this->settings['slide_bar'] != "y" && $this->settings['slide_bar'] != "o") ?  false : true) : true;
		
		$custom_fields = $this->EE->db->get_where('exp_mx_google_map_fields', array('site_id' => SITE_ID))->result_array();
		
		$marker_template = "";
		foreach ($custom_fields as $row)
		{
			$custom_fields_js .=  '{f_name: "'.$row['field_name'].'", type :"'.$row['field_type'].'", label: "'.$row['field_label'].'", pattern: "'.$row['field_pattern'].'"},';
			$marker_template  .= ','.$row['field_name'].' : "{'.$row['field_name'].'}"';
		}
	

		
		$zoom = (int) $zoom;
		$options = compact($data_points);
		$out = '';	
		
		$url_markers_icons	= (!empty($this->settings['url_markers_icons'])) ? $this->settings['url_markers_icons'] : $this->EE->functions->remove_double_slashes($this->EE->config->item('theme_folder_url').'/third_party/mx_google_map/maps-icons/');
		$path_markers_icons	= (!empty($this->settings['path_markers_icons'])) ? $this->settings['path_markers_icons'] : $this->EE->functions->remove_double_slashes($this->EE->config->item('theme_folder_path').'/third_party/mx_google_map/maps-icons/');

		
		if (!isset($this->cache[$this->addon_name]['header']))
		{
			$this->EE->cp->add_to_head('<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>');
			$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$this->EE->config->item('theme_folder_url').'third_party/mx_google_map/mxgooglemap.js"></script>');
			$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->EE->config->item('theme_folder_url').'third_party/mx_google_map/css/mx_google_map.css" />');
			$this->EE->javascript->output('
			marker_icons_path = "'.$url_markers_icons.'";');
			$this->cache[$this->addon_name]['header'] = TRUE;
		}
		
		$entry_id = $this->EE->input->get('entry_id');
		
		$markers = '';

		if ($entry_id)
		{
			$query = $this->EE->db->get_where('exp_mx_google_map', array('entry_id' => $entry_id, 'field_id' => $this->field_id))->result_array();
			foreach ($query as $row)
			{
				$custom_fields_set = "";	
		
				$markers .= '{'.'marker_id : '.$row['point_id'].'
									,latitude: 	'.$row['latitude'].'
									,longitude: '.$row['longitude'].'
									,draggable: true
									,icon: "'.(($row['icon'] != "") ?  $row['icon'] : $default_icon).'"
									'. $this->EE->functions->var_swap($marker_template, $row).'},';
			}
			
			$markers = rtrim($markers, ',');
		};

		$this->EE->javascript->output('
		jQuery(document).ready(function() { 
		'.(($slide_bar) ?'
		'.(($this->settings['slide_bar'] == 'y') ? '
	   jQuery("#panel_main_'.$this->field_name.'").stop().animate({width:"0", opacity:0.1}, 220); 
	   jQuery("#panel_main_el_'.$this->field_name.'").hide();
		' : '').'		
		   jQuery("#panel_button_'.$this->field_name.'").toggle(function(){
			  jQuery("#panel_main_'.$this->field_name.'").stop().animate({width:"240px", opacity:0.8}, 220, function() {
			  });  
			 jQuery("#panel_main_el_'.$this->field_name.'").show();	
		   },
		   function(){ 
		   jQuery("#panel_main_'.$this->field_name.'").stop().animate({width:"0", opacity:0.1}, 220); 
			  jQuery("#panel_main_el_'.$this->field_name.'").hide();
		   
		   });
			' : '').'
			jQuery("#'.$this->field_name.'_map").mxgoogleMaps({
					latitude: '.$latitude.',
					longitude: '.$longitude.',
					zoom:  '.$zoom.',
					markers: ['.$markers.']
					,field_id : "'.$this->field_name.'"
					,cp: true
					,scrollwheel : false
					,icon:"'.$default_icon.'"
					,custom_fields : ['.rtrim($custom_fields_js,',').']
					'.(($max_points != "") ? ",max_points : ".$max_points : "").'
				}
			); 
			
		}); 
		');
		
		$hidden_input = '<div style="display:none;" id="'.$this->field_name.'_data"><input name="'.$this->field_name.'[field_data]"  value="'.$latitude.'|'.$longitude.'|'.$zoom.'" type="hidden"/></div>';
 
		$value = implode('|', array_values($options));

		$field = ' <div style="padding: 0 10px;"><div style="padding-bottom:10px;">'.$this->EE->lang->line('f_tip').'<br/><input type="text" id="'.$this->field_name.'_address" style="width:250px;" />';

		$button = ' <a href="javascript:;" class="minibutton '.$this->field_name.'_btn_geocode"><span>'.$this->EE->lang->line('find_it').'</span></a> <a  href="javascript:;" class="minibutton btn-download '.$this->field_name.'_btn_addmarker"><span><span class="smallicon"></span>'.$this->EE->lang->line('marker_at_c').'</span></a>';
		
		return $out.$field.$button.$hidden_input.'
		<div class="map_frame">
			<div id="'.$this->field_name.'_map" class="map_container"></div>

			<div class="panel_main" id="panel_main_'.$this->field_name.'" '.(($slide_bar) ? '': 'style="display:none;"').'>
				<div class="panel_main_el"  id="panel_main_el_'.$this->field_name.'">
					<div class="custom_fields">

					
					</div>
					<label  for="gmap-icon">'.$this->EE->lang->line('icon').'</label>
					'.$this->_get_dir_list($path_markers_icons, $this->field_name,'').'			
					<label  for="latitude">'.$this->EE->lang->line('latitude').'</label>
					<input autocomplete="off" spellcheck="false" id="latitude_'.$this->field_name.'" name="latitude_'.$this->field_name.'"  disabled="disabled" type="text">
					<label  for="longitude">'.$this->EE->lang->line('longitude').'</label>
					<input autocomplete="off" spellcheck="false" id="longitude_'.$this->field_name.'" name="longitude_'.$this->field_name.'" disabled="disabled" type="text">
					
					<div style="width:100%;padding-top:20px;">
					<a href="javascript:;" class="minibutton '.$this->field_name.'_btn_delete" rel="'.$this->field_name.'"><span>'.$this->EE->lang->line('delete').'</span></a>
					<a href="javascript:;" class="minibutton '.$this->field_name.'_btn_move" rel="'.$this->field_name.'"><span>'.$this->EE->lang->line('move2center').'</span></a>
					<span style="float:right;"> <a href="javascript:;" class="minibutton '.$this->field_name.'_btn_apply" rel="'.$this->field_name.'"><span>'.$this->EE->lang->line('apply').'</span></a></span>
					</div>
				</div>
			</div>
		
			<div class="panel_button" id="panel_button_'.$this->field_name.'" '.(($slide_bar) ? '': 'style="display:none;"').'></div> 

	 
		</div>
		</div></div>';
	}

	
	// Icons list
	
	function _get_dir_list ($directory, $field_name,  $data, $mode = 0) {

	   $results = array();
	   
	   $handler = opendir($directory);
	   
		while ($file = readdir($handler)) {
			$f_name = explode(".", $file);

			if ($file != '.' && $file != '..' )
			if  ($mode == 0) {
			  if  ($f_name[1]  == 'png')
			  $results[] = $file;
			  }
		   else
			  $results[] = $file;
		}

    closedir($handler);
	
      $result = "<select name=\"gmap-icon\" id=\"gmap-icon_".$field_name."\">"; 
      $result .= "<option value=\"\"></option>";
      foreach($results as $icon_file)
      {
		$selected = ($icon_file == $data) ? " selected=\"true\"" : "";
        $result .= "<option value=\"{$icon_file}\" $selected>{$icon_file}</option>";
      }
      
      $result .= "</select>";
      
    return $result;
  }
  

	// --------------------------------------------------------------------
	
	/**
	 * Prep the publish data
	 *
	 * @access	public
	 */
	function pre_process($data)
	{

        $map = array ();
		// Parse out the file info $point
        if ($data != "") {
		    list($map["latitude"], $map["longitude"], $map["zoom"]) = explode('|', $data);
        };
		return $map;
		//, $map["entry_id"]
	} 

	// --------------------------------------------------------------------
		
	/**
	 * Replace tag
	 *
	 * @access	public
	 * @param	field contents
	 * @return	replacement text
	 *
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		$r = "";
		if ($tagdata !== FALSE AND !empty($data)){
			$mapTypeControl =  ( ! isset($params['mapTypeControl'])) ? "\n,mapTypeControl: true" : "\n,mapTypeControl:".$params['mapTypeControl'];
			
			$data_q = array('entry_id' =>  $this->row['entry_id'], 'field_id' => $this->field_id);
		
    		if (isset($this->row['point_id']))  {
				$data_q['point_id'] = $this->row['point_id'];
			}
			
			$query = $this->EE->db->get_where('exp_mx_google_map', $data_q);
		//	, 'point_id' => $this->row['point_id']
			$r = ''; 
			
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$pass = true;	
					
					if (isset($this->EE->TMPL->tagparams['points'])) {
						if (!isset($this->EE->TMPL->tagparams['points'][$row['point_id']])){
							$pass = false;
						}
						else{
							$row['distance'] = $this->EE->TMPL->tagparams['points'][$row['point_id']];
						}
					}
					
					if ($pass) {
						$out = $this->EE->functions->prep_conditionals($tagdata, $row);
						$r .= $this->EE->functions->var_swap($out, $row);
					}
					
				}
			}
			return $r;
		}

		
	}
	/**
	 * Replace tag
	 *
	 * @access	public
	 * @param	field contents
	 * @return	replacement text
	 *
	 */
	function replace_isempty($data, $params = array(), $tagdata = FALSE)
	{
       return ($data == "") ? true : false;   
    }

	function replace_map($data, $params = array(), $tagdata = FALSE)
	{

		$ret = '';
		if ($data != "") {
		$mt_control_style =  ( ! isset($params['mt_control_style'])) ? '' : "\n,mapTypeControlOptions: {\nstyle: google.maps.MapTypeControlStyle.".$params['mt_control_style']."\n}";
		$n_control_style =  ( ! isset($params['n_control_style'])) ? '' : "\n,navigationControlOptions: {\nstyle: google.maps.NavigationControlStyle.".$params['n_control_style']."\n}";		
		$maptype =  ( ! isset($params['maptype'])) ? null :  ",mapTypeId: google.maps.MapTypeId.".$params['maptype'];				
		$draggable =  ( ! isset($params['draggable'])) ? null : "\n,draggable:".$params['draggable'];		
	
		$scrollwheel =  ( ! isset($params['scrollwheel'])) ? null : "\n,scrollwheel:".$params['scrollwheel'];
		$doubleclickzoom =  ( ! isset($params['doubleclickzoomoff'])) ? null : "\n,disableDoubleClickZoom:".$params['doubleclickzoomoff'];
		
		$height =  ( ! isset($params['height'])) ? "500px" : $params['height'];
		$width =  ( ! isset($params['width'])) ? "100%" : $params['width'];

		$icon =  ( !isset($params['icon'])) ? ',icon: "default"' :  "\n,icon: \"".$params['icon'].'"';		
		$marker_draggable =  ( ! isset($params['marker_draggable'])) ? null : "\n,draggable:".$params['marker_draggable'];		
		
		$icon =  ( !isset($params['icon'])) ? ',icon: "default"' :  "\n,icon: \"".$params['icon'].'"';		
		
		$navigationControl =  ( ! isset($params['navigationControl'])) ? "\n,navigationControl: true" : "\n,navigationControl:".$params['navigationControl'];		
		$scaleControl =  ( ! isset($params['scaleControl'])) ? "\n,scaleControl: true" : "\n,scaleControl:".$params['scaleControl'];		
		$mapTypeControl =  ( ! isset($params['mapTypeControl'])) ? "\n,mapTypeControl: true" : "\n,mapTypeControl:".$params['mapTypeControl'];		
		$url_markers_icons	= (!empty($this->settings['url_markers_icons'])) ? $this->settings['url_markers_icons'] : $this->EE->functions->remove_double_slashes($this->EE->config->item('theme_folder_url').'/third_party/mx_google_map/maps-icons/');

		$randid = rand();
	
		$custom_fields = $this->EE->db->get_where('exp_mx_google_map_fields', array('site_id' => SITE_ID))->result_array();
		
		$marker_template = "";
		
		foreach ($custom_fields as $row)
		{
			$marker_template  .= ','.$row['field_name'].' : "{'.$row['field_name'].'}"
			';
		}
	
		$query = $this->EE->db->get_where('exp_mx_google_map', array('entry_id' => $this->row['entry_id'], 'field_id' => $this->field_id))->result_array();
		$markers = ''; 
		
		foreach ($query as $row)
		{
			if (isset($this->EE->TMPL->tagparams['points'])) {
				if (!in_Array ($row['point_id'], $this->EE->TMPL->tagparams['points']))
				return false;
			}
			
			$markers .= '{'.'marker_id : '.$row['point_id'].'

						'. $this->EE->functions->var_swap($marker_template, $row).'
						
						,latitude: 	'.$row['latitude'].',
						longitude: '.$row['longitude'].',
						draggable: true

                        '.(($row['icon'] != "") ? ',icon: "'.$row['icon'].'"' :$icon) .'},';
		}

		$markers = rtrim($markers, ',');

		$ret .= '<script type="text/javascript">
					marker_icons_path = "'.$url_markers_icons.'";

					jQuery(document).ready(function() { 
			jQuery("#'.$randid.'_map").mxgoogleMaps({
					latitude: '.$data["latitude"].',
					longitude: '.$data["longitude"].',
					zoom:  '.$data["zoom"].',
					markers: ['.$markers.'],
					field_id : "'.$randid.'"
					'.$maptype
					.$navigationControl
					.$scaleControl
					.$mapTypeControl
					.$mt_control_style
					.$n_control_style 
					.$scrollwheel
					.$doubleclickzoom
					.$draggable
					.'
				}
			); 
			
		}); 
		</script>';
      

		
		return $ret.'<div style="height: '.$height.';width:'.$width.'"><div id="'.$randid.'_map" style="width: 100%; height: 100%"></div></div>';
        }
        
        return "";
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Display Global Settings
	 *
	 * @access	public
	 * @return	form contents
	 *
	 */
	function display_global_settings()
	{
		$settings = array_merge($this->settings, $_POST);

		$this->EE->lang->loadfile('mx_google_map');
		$this->_cp_js();
		$this->EE->javascript->output('$(window).load(gmaps);');
		
		$vars = array(
			'addon_name' => $this->addon_name,
			'error' => FALSE,
			'input_prefix' => __CLASS__,
			'message' => FALSE,
			'settings_form' =>FALSE,
			'channel_data' => $this->EE->channel_model->get_channels()->result(),
			'language_packs' => ''
		);
		
		$url_markers_icons	= (!empty($settings['url_markers_icons'])) ? $settings['url_markers_icons'] : $this->EE->functions->remove_double_slashes($this->EE->config->item('theme_folder_url').'/third_party/mx_google_map/maps-icons/');
		$path_markers_icons	= (!empty($settings['path_markers_icons'])) ? $settings['path_markers_icons'] : $this->EE->functions->remove_double_slashes($this->EE->config->item('theme_folder_path').'/third_party/mx_google_map/maps-icons/');
		
		$vars['settings']['url_markers_icons'] = $url_markers_icons;
		$vars['settings']['url_markers_icons'] = $path_markers_icons;
		$vars['img_path'] = $this->EE->config->item('theme_folder_url');
		$vars['settings'] = $settings;
		$vars['settings_form'] = TRUE;		

		
		//return "<div id=\"\">$r</div>";
		return $this->EE->load->view('form_settings', $vars, TRUE);
		
	// Add script tags
	
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Save Global Settings
	 *
	 * @access	public
	 * @return	global settings
	 *
	 */
	function save_global_settings()
	{
		return array_merge($this->settings, $_POST);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Display Settings Screen
	 *
	 * @access	public
	 * @return	default global settings
	 *
	 */
	function display_settings($data)
	{
	
		$this->EE->lang->loadfile('mx_google_map');
		
		$latitude		= isset($data['latitude']) ? $data['latitude'] : $this->settings['latitude'];
		$longitude	= isset($data['longitude']) ? $data['longitude'] : $this->settings['longitude'];
		$zoom		= isset($data['zoom']) ? $data['zoom'] : $this->settings['zoom'];
		$max_points	= isset($data['max_points']) ? $data['max_points'] : $this->settings['max_points'];
		$icon	= isset($data['icon']) ? $data['icon'] : $this->settings['icon'];
		$slide_bar = isset($data['slide_bar']) ? $data['slide_bar'] : $this->settings['slide_bar'];
		$path_markers_icons =  (!empty($this->settings['path_markers_icons'])) ? $this->settings['path_markers_icons'] : $this->EE->functions->remove_double_slashes($this->EE->config->item('theme_folder_path').'/third_party/mx_google_map/maps-icons/');

		
		$this->EE->table->add_row(
			lang('latitude', 'latitude'),
			form_input('latitude', $latitude)
		);
		
		$this->EE->table->add_row(
			lang('longitude', 'longitude'),
			form_input('longitude', $longitude)
		);
		
		$this->EE->table->add_row(
			lang('zoom', 'zoom'),
			form_dropdown('zoom', range(1, 20), $zoom)
		);
		
		
		$this->EE->table->add_row(
			lang('max_points', 'max_points'),
			form_input('max_points', $max_points)
		);

		$this->EE->table->add_row(
			lang('icon', 'icon'),
			$this->_get_dir_list($path_markers_icons, '', $icon)
		);
		
		$this->EE->table->add_row(
			lang('slide_bar', 'slide_bar'),
			form_dropdown('slide_bar', array('y' => lang('yes_close'),  'o' => lang('yes_open'),  'n' => lang('no')), $slide_bar)
			
		);			

		
		if (!isset($this->cache[$this->addon_name]['header_map']))
		{
			// Map preview
			$this->_cp_js();
			$this->EE->javascript->output('$(window).load(gmaps);');
			$this->cache[$this->addon_name]['header_map'] = TRUE;
		}
		


		$this->EE->table->add_row(
			lang('preview', 'preview'),
			'<div style="height: 300px;"><div id="map_canvas" style="width: 100%; height: 100%"></div></div>'
		);
	}

	/**
	 * Save Settings
	 *
	 * @access	public
	 * @return	field settings
	 *
	 */
	function save_settings($data)
	{
		return array(
			'latitude'	=> $this->EE->input->post('latitude'),
			'longitude'	=> $this->EE->input->post('longitude'),
			'zoom'		=> $this->EE->input->post('zoom'),
			'max_points'		=> $this->EE->input->post('max_points'),
			'icon'		=> $this->EE->input->post('gmap-icon'),
			'slide_bar'		=> $this->EE->input->post('slide_bar')
		);
	}
	
	
	function save($data)
	{
	
		$r = array();
     
        if($this->EE->input->post('ACT') != "" AND empty($this->safecracker)){
      
            if ($data != "") {
                $GetLatLong_result = $this->GetLatLong($data, 2);
                if ($GetLatLong_result != false)
                {
                    list ($zipLongitude, $zipLatitude) = $GetLatLong_result;
                }
                else
                {
                    $zipLongitude = 1;
                    $zipLatitude =  1;
                }

                $randid = rand();

                $address = $data;

                $data= array();

                $data['order'][0] = $randid;

                $data[$randid]= array(
                        'city' => "",
                        'address' =>"",
                        'zipcode' => "",
                        'state' => "",
                        'lat' => $zipLatitude,
                        'long' => $zipLongitude,
                        'icon' => "default"
                    );
                $data['field_data'] = $zipLatitude."|".$zipLongitude."|13";
            }
         
        }

		if (isset($data['order'])) {
			$this->cache[$this->addon_name]['custom_fields'] = $this->EE->db->get_where('exp_mx_google_map_fields', array('site_id' => SITE_ID))->result_array();
		

			
			foreach ($data['order'] as $row_order => $marker_id)
			{
				$row = $data[$marker_id];
				
				$custom_fields_tmp = array ();
				
				foreach ($this->cache[$this->addon_name]['custom_fields'] as $custom_field)
				{
					$custom_fields_tmp[$custom_field['field_name']] = (!isset($row[$custom_field['field_name']])) ? '' : $row[$custom_field['field_name']];
				}
				
				$this->cache[$this->addon_name][$this->field_id][$marker_id] = array_merge (array(
					'point_id' => $marker_id,
					'latitude' => $row['lat'],
					'longitude' => $row['long'],
					'icon' => $row['icon']
				), $custom_fields_tmp);

			
			}
					
		}
        else {$data['field_data'] = "";};
      
        return $data['field_data'];
	}

	// --------------------------------------------------------------------

	/**
	 * 
	 *
	 * @access	public
	 * @return
	 *
	 */

	function GetLatLong($query, $mode){
		$xml_url = "http://maps.google.com/maps/geo?output=xml&q=$query&ie=utf-8&oe=utf-8";

		if (ini_get('allow_url_fopen')) {
			$xml = @simplexml_load_file($xml_url);
		}
		else {
		   $ch = curl_init($xml_url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$xml_raw = curl_exec($ch);
			$xml = simplexml_load_string($xml_raw);
		  }

		if (is_object($xml) AND ($xml instanceof SimpleXMLElement) AND (int) $xml->Response->Status->code === 200)
		{
			$out = ($mode == 1) ?  $xml->Response->Placemark->address :  explode(',', $xml->Response->Placemark->Point->coordinates);
			return $out ;
		}
		else
		{
		return false;
		}

	}
	// --------------------------------------------------------------------
	
	/**
	 * Handles any custom logic after an entry is saved.
	 *
	 * @access	public
	 * @return	
	 *
	 */
	 
	function post_save($data)
	{

		$id = $this->settings['entry_id'];	
		$this->EE->db->where('entry_id', $id);
        if ($data != ""){  
		    $this->EE->db->update('exp_channel_data', array('field_id_'.$this->settings['field_id'] => $data));
        }

		if  (!isset($this->cache[$this->addon_name]['entry_id']))  {
			
			if (!isset($this->cache[$this->addon_name]['sql_request'])) {
			
				$query = $this->EE->db->get_where('exp_mx_google_map', array('entry_id' => $this->settings['entry_id']))->result_array();
				
				$this->cache[$this->addon_name]['sql_request']	=	array();
				
				foreach ($query as $row) {
					$this->cache[$this->addon_name]['sql_request'][] = $row['point_id'];
				}
				
				$this->EE->db->query('DELETE FROM  exp_mx_google_map WHERE entry_id = '.$id.'');
				
			}
	
			$this->cache[$this->addon_name]['entry_id'] = true;
		}
		

		if (isset($this->cache[$this->addon_name][$this->field_id])) {
			foreach ($this->cache[$this->addon_name][$this->field_id] as $row)
			{
			
				$point  = $row;
				$point ['point_id'] = (in_array($row['point_id'],$this->cache[$this->addon_name]['sql_request'])) ? $row['point_id'] : '';
				$point ['entry_id'] = $this->settings['entry_id'];
				$point ['field_id'] = $this->field_id;
			
				$this->EE->db->query($this->EE->db->insert_string('exp_mx_google_map', $point));

		}

	}
	
	}
	



	// --------------------------------------------------------------------
	
	/**
	 * Install Fieldtype
	 *
	 * @access	public
	 * @return	default global settings
	 *
	 */
	function install()
	{

			$this->EE->db->query("CREATE TABLE  IF NOT EXISTS  exp_mx_google_map (
							  `point_id` int(10) unsigned NOT NULL auto_increment,
							  `entry_id`     varchar(10)             NOT NULL default '',
							  `latitude`        varchar(50)      NOT NULL default '',
							  `longitude`      varchar(50)      NOT NULL default '',
							  `address`      varchar(50)      NOT NULL default '',
							  `city`      varchar(50)      NOT NULL default '',
							  `zipcode`      varchar(50)      NOT NULL default '',
							  `state`      varchar(50)      NOT NULL default '',
							  `field_id`      varchar(10)        NOT NULL default '',
							  `icon`      varchar(128)        NOT NULL default '',
							  PRIMARY KEY (`point_id`)
							)");
	


	

		return array(
			'latitude'	=> '44.06193297865348',
			'longitude'	=> '-121.27584457397461',
			'zoom'		=> 13,
			'max_points' => '3',
			'icon' => '',
			'slide_bar' => 'y',
			'path_markers_icons' => $this->EE->functions->remove_double_slashes($this->EE->config->item('theme_folder_path').'/third_party/mx_google_map/maps-icons/'),
			'url_markers_icons' => $this->EE->functions->remove_double_slashes($this->EE->config->item('theme_folder_url').'/third_party/mx_google_map/maps-icons/')
		);
	}
	// --------------------------------------------------------------------
	
	/**
	 * Uninstall Fieldtype
	 * 
	 */
	function uninstall()
	{
		$this->EE->db->query("DROP TABLE exp_mx_google_map");

		return TRUE;
	}

	function delete($entry_ids)
	{
		$this->EE->db->where_in('entry_id', $entry_ids);
		$this->EE->db->delete('exp_mx_google_map');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Control Panel Javascript
	 *
	 * @access	public
	 * @return	void
	 *
	 */
	function _cp_js()
	{
		// This js is used on the global and regular settings
		// pages, but on the global screen the map takes up almost
		// the entire screen. So scroll wheel zooming becomes a hindrance.
		$this->EE->javascript->set_global('gmaps.scroll', ($_GET['C'] == 'content_admin'));
		$this->EE->cp->add_to_head('<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>');
		$this->EE->cp->load_package_js('cp');
	}
}

/* End of file ft.mx_google_map.php */
/* Location: ./system/expressionengine/third_party/mx_google_map/ft.mx_google_map.php */