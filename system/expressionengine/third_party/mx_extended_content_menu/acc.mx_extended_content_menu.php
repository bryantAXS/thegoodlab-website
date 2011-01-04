<?php
/**
 * MX Extended Content Menu  Accessory
 *
 * @package		ExpressionEngine
 * @category	Accessory
 * @author    Max Lazar <max@eec.ms>
 * @copyright Copyright (c) 2010 Max Lazar (http://eec.ms)
 * @license   http://creativecommons.org/licenses/MIT/  MIT License
 * @version 1.0.0
 */

class mx_extended_content_menu_acc
{
    var $name = 'MX Extended Content Menu';
    var $id = 'mx_extended_content_menu';
    var $version = '1.0.3';
    var $description = '';
    var $sections = array();
    var $settings = array();
    
    /**
     * Set Sections
     *
     * Set content for the accessory
     *
     * @access	public
     * @return	void
     */
    function set_sections()
    {
        $this->EE =& get_instance();
        
        if (defined('SITE_ID') == FALSE)
            define('SITE_ID', $this->EE->config->item('site_id'));
        
        
        $out = '<script type="text/javascript" charset="utf-8">$("#accessoryTabs a.mx_extended_content_menu").parent().remove();';
        $out .= 'last_element = $("#navigationTabs li:eq(2)");';
        
        $channel_data = $this->EE->channel_model->get_channels();
        
        $channel_menu = '';
        
        foreach ($channel_data->result() as $channel) {
            $channel_menu .= '<li><a tabindex="-1" href="' . BASE . AMP . 'C=content_edit&channel_id=' . $channel->channel_id . '" class="">' . addslashes($channel->channel_title) . '</a></li>';
        }
        
        $out .= 'last_element = last_element.next();';
        $out .= 'last_element.addClass("parent").append(\'<ul>' . $channel_menu . '<li class="bubble_footer"></li></ul>\') ;';
        
        
        
        $pages_menu = "";
        
        $id = "";
        
        if ($this->EE->config->item('site_pages') !== FALSE) {
            $this->EE->lang->loadfile('mx_extended_content_menu');
            
            foreach ($this->EE->config->item('site_pages') as $page => $data) {
			
				if (!empty($data['uris'])) {
					foreach ($data['uris'] as $entry_id => $url) {
						$id .= $entry_id . ",";
					}
                };
            }
            ;
            if ($id != "") {
            $out .= 'last_element.after(\'<li class="parent"><a tabindex="-1" href="#" class="">' . lang("page_title") . '</a><ul class="">';
            
            $query = $this->EE->db->query("SELECT entry_id, title 
								   FROM exp_channel_titles					  
								   WHERE site_id = " . SITE_ID . " AND entry_id IN (" . rtrim($id, ",") . ") ORDER by title");
            
            
            
            foreach ($query->result() as $var => $data) {
                $pages_menu .= '<li><a tabindex="-1" href="' . BASE . AMP . 'C=content_publish&M=entry_form&channel_id=2&entry_id=' . $data->entry_id . '" class="">' . addslashes($data->title) . '</a></li>';
            }
            
            
            $out .= $pages_menu . '<li class="bubble_footer"></li></ul></li>\');';
            };
        }
        ;
        
        $out .= '
		</script>';
        $this->sections[] = $out;
    }
    
    
}

/* End of file acc.mx_extended_content_menu.php */
/* Location: ./system/expressionengine/third_party/mx_extended_content_menu/acc.mx_extended_content_menu.php */