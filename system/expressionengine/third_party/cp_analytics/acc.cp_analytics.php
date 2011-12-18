<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
    This file is part of CP Analytics add-on for ExpressionEngine.

    CP Analytics is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CP Analytics is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Read the terms of the GNU General Public License
    at <http://www.gnu.org/licenses/>.
    
    Copyright 2011 Derek Hogue
*/

class Cp_analytics_acc {

	var $name			= 'CP Analytics';
	var $id				= 'cp_analytics_acc';
	var $version		= '1.2.2';
	var $description	= 'Display your Google Analytics stats in the EE control panel.';
	var $sections		= array();
	var $slug			= 'cp_analytics';
	var $extension		= 'Cp_analytics_ext';
	var $token			= '';
	var $profile		= '';
	var $theme			= 'default';
	var $settings		= '';


	function Cp_analytics_acc()
	{
		$this->EE =& get_instance();
		$this->EE->lang->loadfile('cp_analytics');
		$this->theme = $this->EE->session->userdata['cp_theme'];
		$this->settings = $this->get_settings();
		
		// Always load the default CSS
		$this->EE->cp->load_package_css('default');
				
		switch($this->theme)
		{
			// Don't need to add anything if we're using the default theme
			case 'default':
				break;
			// Add tweaks for Corporate theme
			case 'corporate':
				$this->EE->cp->load_package_css('corporate');
				break;
			// Allow overrides from custom themes as well
			default:
				if(file_exists(PATH_THIRD.'cp_analytics/css/'.$this->theme.'.css'))
				{
					$this->EE->cp->load_package_css($this->theme);
				}
		}	

	}


	function set_sections()
	{
		$this->name = "Google Analytics";

		if(empty($this->settings['profile']) || empty($this->settings['token']))
		{
			$this->sections[$this->EE->lang->line('analytics_not_configured')] = 
				'<p><a href="'.
				BASE.AMP.'C=addons_extensions'.
				AMP.'M=extension_settings'.
				AMP.'file='.$this->slug.
				'">'.$this->EE->lang->line('analytics_not_configured_message').
				'</a></p>';
		}
		else
		{
			$this->token = $this->settings['token'];
			$this->profile = $this->settings['profile'];
			
			// Check to see if we have a hourly cache, and if it's still valid
			if(isset($this->settings['hourly_cache']['cache_time']) && 
				$this->EE->localize->set_localized_time() < strtotime('+60 minutes', $this->settings['hourly_cache']['cache_time']))
			{
				$today = $this->settings['hourly_cache'];
				$today['hourly_updated'] = $this->EE->localize->decode_date('%g:%i%a', $this->settings['hourly_cache']['cache_time'], FALSE);
			}
			else
			{
				$today = $this->fetch_hourly_stats();
				$today['hourly_updated'] = $this->EE->localize->decode_date('%g:%i%a', $this->EE->localize->set_localized_time(), FALSE);
			}
				
			// Check to see if we have a daily cache, and if it's still valid
			if(isset($this->settings['daily_cache']['cache_date']) && 
				$this->settings['daily_cache']['cache_date'] == $this->EE->localize->decode_date('%Y-%m-%d', $this->EE->localize->set_localized_time(), FALSE))
			{
				$daily = $this->settings['daily_cache'];
				$daily['daily_updated'] = $this->settings['daily_cache']['cache_date'];
			}
			else
			{
				$daily = $this->fetch_daily_stats();
				$daily['daily_updated'] = $this->EE->localize->decode_date('%Y-%m-%d', $this->EE->localize->set_localized_time(), FALSE);
			}
			
			$recently = array_merge($today, $daily);
			
			if(isset($today) && isset($daily))
			{				
				if($this->EE->input->get('D') == 'cp' && $this->EE->input->get('C') == 'homepage' && isset($this->settings['homepage_chart']) && $this->settings['homepage_chart'] == 'y')
				{
					$this->homepage($daily['lastmonth_chart'], $this->settings['homepage_chart_labels']);
				}
				
				$this->sections[$this->EE->lang->line('analytics_recently')] = 
					$this->EE->load->view('recent', $recently, TRUE);
					
				$this->sections[$this->EE->lang->line('analytics_lastmonth')] = 
					$this->EE->load->view('lastmonth', $daily['lastmonth'], TRUE);
					
				$this->sections[$this->EE->lang->line('analytics_top_content')] = 
					$this->EE->load->view('content', $daily['lastmonth'], TRUE);
				
				$this->sections[$this->EE->lang->line('analytics_top_referrers')] = 
					$this->EE->load->view('referrers', $daily['lastmonth'], TRUE);
			}
			else
			{
				// We couldn't fetch our account data for some reason
				$this->sections[$this->EE->lang->line('analytics_trouble_connecting')] = 
					$this->EE->lang->line('analytics_trouble_connecting_message');
			}	
		}		
	}
	
	
	function get_settings($all_sites = FALSE)
	{
		$get_settings = $this->EE->db->query("SELECT settings 
			FROM exp_extensions 
			WHERE class = '".$this->extension."' 
			LIMIT 1");
		
		$this->EE->load->helper('string');
		
		if ($get_settings->num_rows() > 0 && $get_settings->row('settings') != '')
        {
        	$settings = strip_slashes(unserialize($get_settings->row('settings')));
        	$settings = ($all_sites == FALSE && isset($settings[$this->EE->config->item('site_id')])) ? 
        		$settings[$this->EE->config->item('site_id')] : 
        		$settings;
        }
        else
        {
        	$settings = array();
        }
        return $settings;
	}	
	
	
	function fetch_hourly_stats()
	{
		$data = array();
		$data['cache_time'] = $this->EE->localize->set_localized_time();					

		require_once(PATH_THIRD.'cp_analytics/libraries/gapi.class.php');
		
		$today = new gapi($this->token);
		$today->requestReportData(
			$this->profile,
			array('date'),
			array('pageviews','visits', 'timeOnSite'),
			'','',
			$this->EE->localize->decode_date('%Y-%m-%d', $this->EE->localize->set_localized_time(), FALSE),
			$this->EE->localize->decode_date('%Y-%m-%d', $this->EE->localize->set_localized_time(), FALSE)
		);
		
		$data['visits'] = 
		number_format($today->getVisits());
		
		$data['pageviews'] = 
		number_format($today->getPageviews());
		
		$data['pages_per_visit'] = 
		$this->analytics_avg_pages($today->getPageviews(), $today->getVisits());
		
		$data['avg_visit'] = 
		$this->analytics_avg_visit($today->getTimeOnSite(), $today->getVisits());
		
		// Now cache it
		$settings = $this->get_settings(TRUE);
		$settings[$this->EE->config->item('site_id')]['hourly_cache'] = $data;
		
		$this->EE->db->where('class', $this->extension);
		$this->EE->db->update('extensions', array('settings' => serialize($settings)));				

		return $data;
	}


	function fetch_daily_stats()
	{
		$data = array();
		$data['cache_date'] = $this->EE->localize->decode_date('%Y-%m-%d', $this->EE->localize->set_localized_time(), FALSE);					

		require_once(PATH_THIRD.'cp_analytics/libraries/gapi.class.php');
		
		// Compile yesterday's stats
		$yesterday = new gapi($this->token);
		$yesterday->requestReportData(
			$this->profile,
			array('date'),
			array('pageviews','visits', 'timeOnSite'),
			'','',
			$this->EE->localize->decode_date('%Y-%m-%d', strtotime('yesterday', $this->EE->localize->set_localized_time()), FALSE),
			$this->EE->localize->decode_date('%Y-%m-%d', strtotime('yesterday', $this->EE->localize->set_localized_time()), FALSE)
		);
		
		// Get account data so we can store the profile info
		$data['profile'] = array();
		$yesterday->requestAccountData(1,1000);
		foreach($yesterday->getResults() as $result)
		{
			if($result->getProfileId() == $this->profile)
			{
				$data['profile']['id'] = $result->getProfileId();
				$data['profile']['title'] = $result->getTitle();
			}
		}
		
		$data['yesterday']['visits'] = 
		number_format($yesterday->getVisits());
		
		$data['yesterday']['pageviews'] = 
		number_format($yesterday->getPageviews());
		
		$data['yesterday']['pages_per_visit'] = 
		$this->analytics_avg_pages($yesterday->getPageviews(), $yesterday->getVisits());
		
		$data['yesterday']['avg_visit'] = 
		$this->analytics_avg_visit($yesterday->getTimeOnSite(), $yesterday->getVisits());
		
		// Compile last month's stats
		$lastmonth = new gapi($this->token);
		$lastmonth->requestReportData(
			$this->profile,
			array('date'),
			array('pageviews','visits', 'newVisits', 'timeOnSite', 'bounces', 'entrances'),
			'date', '',
			$this->EE->localize->decode_date('%Y-%m-%d', strtotime('30 days ago', $this->EE->localize->set_localized_time()), FALSE),
			$this->EE->localize->decode_date('%Y-%m-%d', strtotime('yesterday', $this->EE->localize->set_localized_time()), FALSE)
		);
		
		$data['lastmonth']['date_span'] = 
		$this->EE->localize->decode_date('%F %j%S %Y', strtotime('30 days ago', $this->EE->localize->set_localized_time()), FALSE).' &ndash; '.$this->EE->localize->decode_date('%F %j%S %Y', strtotime('yesterday', $this->EE->localize->set_localized_time()), FALSE);
		
		$data['lastmonth']['visits'] = 
		number_format($lastmonth->getVisits());
		$data['lastmonth']['visits_sparkline'] = 
		$this->analytics_sparkline($lastmonth->getResults(), 'visits');
		
		$data['lastmonth']['pageviews'] = 
		number_format($lastmonth->getPageviews());
		$data['lastmonth']['pageviews_sparkline'] = 
		$this->analytics_sparkline($lastmonth->getResults(), 'pageviews');
		
		$data['lastmonth']['pages_per_visit'] = 
		$this->analytics_avg_pages($lastmonth->getPageviews(), $lastmonth->getVisits());
		$data['lastmonth']['pages_per_visit_sparkline'] = 
		$this->analytics_sparkline($lastmonth->getResults(), 'avgpages');
		
		$data['lastmonth']['avg_visit'] = 
		$this->analytics_avg_visit($lastmonth->getTimeOnSite(), $lastmonth->getVisits());
		$data['lastmonth']['avg_visit_sparkline'] = 
		$this->analytics_sparkline($lastmonth->getResults(), 'time');
		
		$data['lastmonth']['bounce_rate'] = 
		($lastmonth->getBounces() > 0 && $lastmonth->getBounces() > 0) ? 
		round( ($lastmonth->getBounces() / $lastmonth->getEntrances()) * 100, 2 ).'%' : '0%';
		$data['lastmonth']['bounce_rate_sparkline'] = 
		$this->analytics_sparkline($lastmonth->getResults(), 'bouncerate');
		
		$data['lastmonth']['new_visits'] = 
		($lastmonth->getNewVisits() > 0 && $lastmonth->getVisits() > 0) ? 
		round( ($lastmonth->getNewVisits() / $lastmonth->getVisits()) * 100, 2).'%' : '0%';					
		$data['lastmonth']['new_visits_sparkline'] = 
		$this->analytics_sparkline($lastmonth->getResults(), 'newvisits');
		
		// Compile stats for the homepage chart
		$data['lastmonth_chart'] = $this->analytics_chart_data($lastmonth->getResults());

		// Compile last month's top content
		$topcontent = new gapi($this->token);
		$topcontent->requestReportData(
			$this->profile,
			array('hostname', 'pagePath'),
			array('pageviews'),
			'-pageviews', '',
			$this->EE->localize->decode_date('%Y-%m-%d', strtotime('30 days ago', $this->EE->localize->set_localized_time()), FALSE),
			$this->EE->localize->decode_date('%Y-%m-%d', strtotime('yesterday', $this->EE->localize->set_localized_time()), FALSE),
			null, 16
		);
		
		$data['lastmonth']['content'] = array();
		$i = 0;
		
		// Make a temporary array to hold page paths
		// (for checking dupes resulting from www vs non-www hostnames)
		$paths = array();
		
		foreach($topcontent->getResults() as $result)
		{
			// Do we already have this page path?
			$dupe_key = array_search($result->getPagePath(), $paths);
			if($dupe_key !== FALSE)
			{
				// Combine the pageviews of the dupes
				$data['lastmonth']['content'][$dupe_key]['count'] = 
				($result->getPageviews() + $data['lastmonth']['content'][$dupe_key]['count']);
			}
			else
			{
				$url = (strlen($result->getPagePath()) > 20) ? 
					substr($result->getPagePath(), 0, 20).'&hellip;' : 
					$result->getPagePath();
				$data['lastmonth']['content'][$i]['title'] = 
				'<a href="'.$this->EE->cp->masked_url('http://'.$result->getHostname().$result->getPagePath()).'" target="_blank">'.
				$url.'</a>';
				$data['lastmonth']['content'][$i]['count'] = $result->getPageviews();

				// Store the page path at the same position so we can check for dupes
				$paths[$i] = $result->getPagePath();

				$i++;
			}
		}
		
		// Slice down to 8 results
		$data['lastmonth']['content'] = array_slice($data['lastmonth']['content'], 0, 8);
		
		// Compile last month's top referrers
		$referrers = new gapi($this->token);
		$referrers->requestReportData(
			$this->profile,
			array('source', 'referralPath', 'medium'),
			array('visits'),
			'-visits', '',
			$this->EE->localize->decode_date('%Y-%m-%d', strtotime('30 days ago', $this->EE->localize->set_localized_time()), FALSE),
			$this->EE->localize->decode_date('%Y-%m-%d', strtotime('yesterday', $this->EE->localize->set_localized_time()), FALSE),
			null, 8
		);
		
		$data['lastmonth']['referrers'] = array();
		$i = 0;
		foreach($referrers->getResults() as $result)
		{
			$data['lastmonth']['referrers'][$i]['title'] = 
			($result->getMedium() == 'referral') ?
			'<a href="'.$this->EE->cp->masked_url('http://'.$result->getSource() . $result->getReferralPath()).'" target="_blank">'.$result->getSource().'</a>' : $result->getSource();
			$data['lastmonth']['referrers'][$i]['count'] = number_format($result->getVisits());
			$i++;
		}
		
		// Now cache it
		$settings = $this->get_settings(TRUE);
		$settings[$this->EE->config->item('site_id')]['daily_cache'] = $data;
		
		$this->EE->db->where('class', $this->extension);
		$this->EE->db->update('extensions', array('settings' => serialize($settings)));			

		return $data;
	}
	
		
	function analytics_avg_pages($pageviews, $visits)
	{
		return ($pageviews > 0 && $visits > 0) ? round($pageviews / $visits, 2) : 0;
	}
	

	function analytics_avg_visit($seconds, $visits)
	{
		if($seconds > 0 && $visits > 0)
		{
			$avg_secs = $seconds / $visits;
			// This little snippet by Carson McDonald, from his Analytics Dashboard WP plugin
			$hours = floor($avg_secs / (60 * 60));
			$minutes = floor(($avg_secs - ($hours * 60 * 60)) / 60);
			$seconds = $avg_secs - ($minutes * 60) - ($hours * 60 * 60);
			return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
		}
		else
		{
			return '00:00:00';
		}
	}
	
	
	function analytics_chart_data($data_array)
	{
		$stats = array();
		$i = 0;
		foreach($data_array as $result)
		{
			$stats[$i] = "['".$this->EE->localize->decode_date('%M %j%S', strtotime($result->getDate(), $this->EE->localize->set_localized_time()), FALSE)."', ";
			$stats[$i] .= $result->getVisits().', ';
			$stats[$i] .= $result->getPageviews().']';
			$i++;
		}
		return implode(',', $stats);
	}
	
	
	function analytics_sparkline($data_array, $metric)
	{
		$max = 0; $stats = array();
		
		foreach($data_array as $result)
		{
			switch($metric) {
				case "pageviews":
					$datapoint = $result->getPageviews();
					break;
				case "visits":	
					$datapoint = $result->getVisits();
					break;
				case "time":
					$datapoint = $result->getTimeOnSite();
					break;
				case "avgpages":
					$datapoint = ($result->getVisits() > 0 && $result->getPageViews() > 0) ? $result->getPageviews() / $result->getVisits() : 0;
					break;
				case "bouncerate":
					$datapoint = ($result->getEntrances() > 0 && $result->getBounces() > 0) ? $result->getBounces() / $result->getEntrances() : 0;
					break;
				case "newvisits":
					$datapoint =  ($result->getNewVisits() > 0 && $result->getVisits() > 0) ? $result->getNewVisits() / $result->getVisits() : 0;
					break;
			}
			$max = ($max < $datapoint) ? $datapoint : $max;
			$stats[] = $datapoint;
		}
		
		// Build Google Chart url
		$base = 'http://chart.apis.google.com/chart?';
		$args = array(
			'cht=ls',
			'chs=120x20',
			'chm=B,FFFFFF66,0,0,0',
			'chco=FFFFFFEE',
			'chf=c,s,FFFFFF00|bg,s,FFFFFF00',
			'chd=t:'.implode(',', $stats),
			'chds=0,'.$max
		);
		
		$curl_url = $base.implode('&', $args);
		$src_url = $base.implode('&amp;', $args);
					
	   	// Are we caching locally?
	   	if(isset($this->settings['cache_sparklines']) && $this->settings['cache_sparklines'] == 'y')
	   	{
			// Determine cache and file destination
			if($path = $this->EE->config->item('cp_analytics_cache_path'))
			{
				$path = rtrim($path, '/');
			}
			elseif(isset($this->settings['cache_path']) && !empty($this->settings['cache_path']))
			{
				$path = rtrim($this->settings['cache_path'], '/');
			}
			else
			{
				$path = rtrim(PATH_THEMES, '/').'/third_party/cp_analytics';
			}

			// Determine cache URL
			if($url = $this->EE->config->item('cp_analytics_cache_url'))
			{
				$url = rtrim($url, '/').'/';
			}
			elseif(isset($this->settings['cache_url']) && !empty($this->settings['cache_url']))
			{
				$url = rtrim($this->settings['cache_url'], '/').'/';
			}
			else
			{
				$url = rtrim($this->EE->config->item('theme_folder_url'), '/').'/third_party/cp_analytics/';
			}
			
			$filename = sha1(implode(',',$stats).','.$max).'.png';
			$fullpath = $path.'/'.$filename;
				    
			// Check for cache directory
			if(!is_dir($path))
			{
				@mkdir($path);
			}
			
			// If the directory was there, or we were able to create it, fetch the sparklines and save them	   
			if(is_dir($path))
			{	    
			    // Check for an identical file first
			    if(!file_exists($fullpath))
			    {
					// Fetch it
					$ch = curl_init($curl_url);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					if($image = curl_exec($ch))
					{
						$this->EE->load->helper('file');
						write_file($fullpath, $image);
						$sparkline = $url.$filename;
					}
					curl_close($ch);	          
			    }
			    else
			    {
					$sparkline = $url.$filename;
			    }
			}
		}
				
		// Otherwise just call the image externally
		if(!isset($sparkline)) $sparkline = $src_url;
		
		return '<img src="'.$sparkline.'" alt="" width="120" height="20" />';
	}
	
	
	function homepage($data, $labels)
	{
		$vars = array(
			'chart' => $data,
			'text_pos' => ($labels == 'y') ? 'in' : 'none'
		);
		switch($this->theme)
		{
			case 'corporate' :
				$vars['accent'] = 'corporate_blue';
				break;
			case 'nerdery' : 
				$vars['accent'] = 'nerdery_blue';
				break;
			default :
				$vars['accent'] = 'pink';
		}
		$this->EE->cp->add_to_foot($this->EE->load->view('homepage', $vars, TRUE));
	}


}