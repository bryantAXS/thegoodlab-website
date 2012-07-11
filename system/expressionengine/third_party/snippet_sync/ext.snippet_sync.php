<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (! defined('SNIPPET_SYNC_VERSION'))
{
    // get the version from config.php
    require PATH_THIRD.'snippet_sync/config.php';
    define('SNIPPET_SYNC_VERSION', $config['version']);
    define('SNIPPET_SYNC_NAME', $config['name']);
    define('SNIPPET_SYNC_DESCRIPTION', $config['description']);
}

/**
 * ExpressionEngine Snippets Sync Extension Class
 *
 * @package     ExpressionEngine
 * @subpackage  Extensions
 * @category    Snippets Sync
 * @author      Isaac Raway & Brian Litzinger
 * @copyright   Copyright 2010 - Isaac Raway & Brian Litzinger
 * @link        http://metasushi.com/ee & http://boldminded.com
 */

class Snippet_sync_ext {
    public $name           = SNIPPET_SYNC_NAME;
    public $version        = SNIPPET_SYNC_VERSION;
    public $description    = SNIPPET_SYNC_DESCRIPTION;
    public $settings_exist = 'y';
    public $docs_url       = '';
    public $settings       = array();
    
    private $site_id = 1;
    private $site_name = 'default_site';
    private $sites;
    private $path;
    private $cache;
    
    private $default_settings = array(
        'hashes' => array(),
        'prefix' => 'snippet:',
        'subfolder_suffix' => '_',
        'msm_shared_folder' => 'shared',
        'hide_menu' => 'n',
        'enable_cleanup' => 'n'
    );
    
    public function Snippet_sync_ext($settings = '')
    {
        $this->EE =& get_instance();
        
        $this->settings = is_array($settings) ? array_merge($this->default_settings, $settings) : $settings;
        
        // Override our settings if a config value is added to config.php
        $this->_override_settings(array(
            'snippet_sync_prefix'   => 'prefix',
            'snippets_sync_prefix'  => 'prefix', // legacy, due to me spelling it wrong.
            'snippet_sync_suffix'   => 'subfolder_suffix',
            'snippets_sync_suffix'  => 'subfolder_suffix', // legacy too
            'msm_shared_folder'     => 'msm_shared_folder'
        ));
        
        $this->sites = $this->EE->db->select('site_name')
                              ->select('site_id')
                              ->get('sites')
                              ->result_array();
                              
        $this->path = $this->EE->config->slash_item('snippet_file_basepath') ? $this->EE->config->slash_item('snippet_file_basepath') : APPPATH . 'snippets/';
        $this->path = $this->EE->functions->remove_double_slashes($this->path);
        
        // Create cache
        if (! isset($this->EE->session->cache[__CLASS__]))
        {
            $this->EE->session->cache[__CLASS__] = array();
        }
        $this->cache =& $this->EE->session->cache[__CLASS__];
        
        // $this->debug($this->settings, true);
    }
    
    private function _override_settings($items = array())
    {
        foreach($items as $item => $setting)
        {
            if($value = $this->EE->config->item($item))
            {
                $this->settings[$setting] = $value;
            }
        }
    }
    
    public function settings()
    {
        $settings = array();

        $settings['prefix'] = isset($this->settings['prefix']) ? $this->settings['prefix'] : $this->default_settings['prefix'];
        $settings['msm_shared_folder'] = isset($this->settings['msm_shared_folder']) ? $this->settings['msm_shared_folder'] : $this->default_settings['msm_shared_folder'];
        $settings['subfolder_suffix'] = isset($this->settings['subfolder_suffix']) ? $this->settings['subfolder_suffix'] : $this->default_settings['subfolder_suffix'];
        $settings['hide_menu'] = array('s', array('y' => 'Yes', 'n' => 'No'));
        $settings['enable_cleanup'] = array('s', array('n' => 'No', 'y' => 'Yes'));
        
        return $settings;
    }
    
    public function activate_extension()
    {
        // Delete old hooks
        $this->EE->db->query("DELETE FROM exp_extensions WHERE class = '". __CLASS__ ."'");
    
        // Add new hooks
        $ext_template = array(
            'class'    => __CLASS__,
            'settings' => serialize($this->default_settings),
            'priority' => 8,
            'version'  => $this->version,
            'enabled'  => 'y'
        );
    
        $extensions = array(
            array('hook' => 'sessions_end', 'method' => 'sessions_end'),
            array('hook' => 'show_full_control_panel_end', 'method' => 'show_full_control_panel_end')
        );
    
        foreach($extensions as $extension)
        {
            $ext = array_merge($ext_template, $extension);
            $this->EE->db->insert('extensions', $ext);
        }
    }
    
    public function update_extension($current = '')
    {
        if ($current == '' OR $current == $this->version)
        {
            return FALSE;
        }

        // if ($current < '1.0') { }

        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->update('extensions', array('version' => $this->version));
    }
    
    public function disable_extension()
    {
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->delete('extensions');
    }
    
    public function sessions_end($session)
    {
        // Only sync snippets if logged in as Super Admin (usually a developer) or if debug is explicitly turned on.
        if($session->userdata['group_id'] == 1 OR $this->EE->config->item('debug') != 0) 
        {
            $this->_do_sync();
        }
        
        return $session;
    }
    
    /**
     * Requiring Wallace ext to do this instead of creating an Accessory and adding 
     * even more JS to the already JS heavy CP pages
     */
    public function show_full_control_panel_end($out)
    {
        if(isset($this->settings['hide_menu']) AND $this->settings['hide_menu'] == 'y')
        {
            // Find all <li> on the page, will be a lot
            if(preg_match_all("/(<li.*?<\/li>)/", $out, $matches))
            {
                // Each returns as an array
                foreach($matches as $match)
                {
                    // Loop over each <li> in the group
                   foreach($match as $li)
                   {
                       // Replace/remove if it's the Snippets link
                        if(strstr($li, 'C=design&amp;M=snippets'))
                        {
                            $out = str_replace($li, '', $out);
                        }
                   }
                }
            }
        }
        
        return $out;
    }
    
    private function _do_sync()
    {
        $this->EE->load->helper('directory');
        
        $dir = directory_map($this->path, 3);
        clearstatcache();
        
        $updated = FALSE;
        
        if(!isset($this->cache['site_names']))
        {
            $this->cache['site_names'] = array();
        }

        if($dir)
        {
            foreach($dir as $k => $v)
            {
                if(is_numeric($k)) // it's a file
                { 
                    $updated = $updated OR $this->_sync_snippet($k, $this->path, $v);
                }
                else // it's a site-directory
                {
                    $sub_prefix = '';

                    // If the root folder is named "shared", it is a globally shared folder
                    // across MSM sites, so the site_id is 0, and prefix it with shared.
                    
                    //                 folder group  file.html
                    // usage: {snippet:shared_global_header}
                    if($this->settings['msm_shared_folder'] != '' AND $k == $this->settings['msm_shared_folder'])
                    {
                        $this->site_id = 0;
                        $sub_prefix = $this->settings['msm_shared_folder'] . $this->settings['subfolder_suffix'];
                    }
                    else
                    {
                        // Find out what the site_id is for the current directory
                        foreach($this->sites as $sk => $sv)
                        {
                            $this->cache['site_names'][] = $this->sites[$sk]['site_name'];
                        
                            if($site_key = array_search($k, $sv))
                            {
                                $this->site_id = $this->sites[$sk]['site_id'];
                                continue;
                            }
                        }
                    }
                    
                    // Go through each file found in the directory array
                    foreach($v as $dk => $dv)
                    {
                        if(is_numeric($dk)) // it's a file
                        {
                            $updated = $updated OR $this->_sync_snippet($dk, $this->path . $k .'/', $dv, $sub_prefix);
                        }
                        else // it's a sub-directory
                        {
                            foreach($v[$dk] as $sub_dv)
                            {
                                $updated = $updated OR $this->_sync_snippet($dk, $this->path . $k.'/'.$dk .'/', $sub_dv, $sub_prefix.$dk);
                            }
                        }
                    }
                }
            }
        }

        // Only if cleanup is enabled, and we have hashes, meaning this isn't the first time this has been run.
        if(
            isset($this->settings['enable_cleanup']) AND 
            $this->settings['enable_cleanup'] == 'y' AND 
            isset($this->settings['hashes']) AND 
            count($this->settings['hashes']) > 0)
        {
            $this->_cleanup();
        }
        
        if($updated)
        {
            if($this->EE->extensions->active_hook('snippet_sync_updated'))
            {
                $this->EE->extensions->call('snippet_sync_updated');
            }
        }
        
        $this->_save_settings();
    }
    
    private function _generate_prefix($sub_folder)
    {
        // Remove the .group if that is part of the folder name.
        if ($sub_folder AND isset($this->settings['subfolder_suffix']) AND $this->settings['subfolder_suffix'] != '')
        {
            // If this is true, then it's a file in the msm shared folder, but not in a group sub-folder
            if($sub_folder == $this->settings['msm_shared_folder'] . $this->settings['subfolder_suffix'])
            {
                $prefix = $this->settings['prefix'] . str_replace('.group', '', $sub_folder);
            }
            else
            {
                $prefix = $this->settings['prefix'] . str_replace('.group', '', $sub_folder) . $this->settings['subfolder_suffix'];
            }
        }
        else
        {
            $prefix = $this->settings['prefix'];
        }
        
        return $prefix;
    }
    
    private function _cleanup()
    {
        $valid_snippets = array();
        $snippet_names = array();
        
        if(isset($this->cache['found']))
        {
            foreach($this->cache['found'] as $site_id => $snippets)
            {
                foreach($snippets as $data)
                {
                    $valid_snippets[$site_id][$data['name']] = $data['file'];
                }
            }
            
            $snippets = $this->EE->db->get('snippets')->result_array();
            
            foreach($snippets as $snippet)
            {
                // If the snippet in the db is not found in the array, then it
                // does not exist on the filesystem anymore, so delete it.
                if(!array_key_exists($snippet['snippet_name'], $valid_snippets[$snippet['site_id']]))
                {
                    unset($this->settings['hashes'][$this->site_id][$snippet['snippet_name']]);
                    
                    $this->EE->db->where('snippet_name', $snippet['snippet_name'])
                                 ->where('site_id', $snippet['site_id'])
                                 ->delete('snippets');
                }
            }
        }
    }
    
    private function _sync_snippet($key, $path, $file, $sub_folder = false)
    {
        $result = FALSE;
        $filemtime = filemtime($path . $file);
        $snippet_name = $this->_generate_prefix($sub_folder) . basename($file, '.html');
        
        // Check if we have a recorded hash for the file, and update if hash does not match and datetime is different
        if(
            !isset($this->settings['hashes'][$this->site_id][$snippet_name])
            OR ( ($check_hash = md5_file($path . $file)) != $this->settings['hashes'][$this->site_id][$snippet_name][0]
            AND ($this->settings['hashes'][$this->site_id][$snippet_name][1] < $filemtime) )
        ) 
        {
            $result = TRUE;
            
            $snippet_contents = file_get_contents($path . $file);
            
            $data = array(
                'site_id'           => $this->site_id,
                'snippet_name'      => $snippet_name,
                'snippet_contents'  => $snippet_contents
            );
            
            $where = array(
                'site_id'           => $this->site_id,
                'snippet_name'      => $snippet_name
            );
            
            $snippet_exists = $this->EE->db->where($where)->count_all_results('snippets');
            
            // Update/Create snippet in exp_snippets based on the file contents
            if($snippet_exists) 
            {
                $this->EE->db->where($where)->update('snippets', $data);
            } 
            else 
            {
                $this->EE->db->insert('snippets', $data);
            }
    
            if(!isset($check_hash)) $check_hash = md5_file($path . $file);
            $this->settings['hashes'][$this->site_id][$snippet_name] = array($check_hash, $filemtime);
        }
        
        // Add all our found files to cache for cleanup
        $this->cache['found'][$this->site_id][] = array('name' => $snippet_name, 'file' => $path . $file);
        
        return $result;
    }
    
    private function _save_settings() 
    {
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->update('extensions', array('settings' => serialize($this->settings)));
    }
    
    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
        
        if($die) die('debug terminated');
    }
}