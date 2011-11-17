<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
/*
====================================================================================================
 Author: Aaron Waldon (Causing Effect)
 http://www.causingeffect.com
====================================================================================================
 This file must be placed in the /system/expressionengine/third_party/ce_gravatar folder in your ExpressionEngine installation.
 package 		CE Gravatar
 version 		Version 0.1
 copyright 		Copyright (c) 2010 Causing Effect, Aaron Waldon <aaron@causingeffect.com>
 Last Update	12 January 2011
----------------------------------------------------------------------------------------------------
 Purpose: Facilitate the use of Gravatar images in ExpressionEngine templates.
====================================================================================================

License: CE Gravatar by Aaron Waldon (Causing Effect) is licensed under a Creative Commons Attribution-NoDerivs 3.0 Unported License (http://creativecommons.org/licenses/by-nd/3.0/). Permissions beyond the scope of this license may be available by contacting us at software@causingeffect.com.

CE Gravatar is provided "as is", without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.

You assume all risk associated with the installation and use of CE Gravatar.
*/

$plugin_info = array(
						'pi_name'			=> 'CE Gravatar',
						'pi_version'		=> '0.1',
						'pi_author'			=> 'Aaron Waldon (Causing Effect)',
						'pi_author_url'		=> 'http://www.causingeffect.com/software/ee/ce_gravatar',
						'pi_description'	=> 'Facilitates the use of Gravatar images in ExpressionEngine templates.',
						'pi_usage'			=> Ce_gravatar::usage()
					);

class Ce_gravatar 
{
	private $ratings = array( 'g', 'pg', 'r', 'x' );
	private $defaults = array( '404', 'mm', 'identicon', 'monsterid', 'wavatar', 'retro');
	private $check_404 = FALSE;
	
	function Ce_gravatar()
	{
		//EE super global
		$this->EE =& get_instance();
	}
	
	private function gravatar()
	{
		//---------- get the URL essentials ----------
		//email
		$email = trim($this->EE->TMPL->fetch_param('email'));
		//Use secure protocol?
		$use_https = $this->EE->TMPL->fetch_param('use_https') == 'yes';
		//Add a .jpg extension?
		$add_extension = $this->EE->TMPL->fetch_param('add_extension') == 'yes';
		
		//---------- get the params ----------
		$params = array();
		//default
		$default = trim( $this->EE->TMPL->fetch_param('default') );
		if ( $default != '' )
		{
			//A default was passed in. Add it or encode it as neccessary.
			$params[] = ( in_array( strtolower( $default ), $this->defaults ) ) ? 'd=' . strtolower( $default ) : 'd=' . urlencode( $default );
			if ( strtolower( $default ) == '404' )
			{
				$this->check_404 = TRUE;
			}
		}
		//force default
		$force_default = $this->EE->TMPL->fetch_param('force_default') == 'yes';
		if ( $force_default )
		{
			$params[] = 'f=y';
		}
		//rating
		$rating = strtolower( trim( $this->EE->TMPL->fetch_param('rating') ) );
		if ( $rating != '' && in_array( $rating, $this->ratings ) )
		{
			$params[] = 'r=' . $rating;
		}
		//size
		$size = $this->EE->TMPL->fetch_param('size');
		if ( $size != '' && is_numeric( $size ) )
		{
			$params[] = 's=' . $size;
		}
		
		//---------- Create the URL ----------
		$url = ( ! $use_https ) ? 'http://www' : 'https://secure';
		$url .= '.gravatar.com/avatar/';
		
		//prep the email and add - http://en.gravatar.com/site/implement/hash/
		if ( $email != '' )
		{
			$url .= md5( strtolower( $email ) );
			
			if ( $add_extension )
			{
				$url .= '.jpg';	
			}
		}
		
		//add the parameters
		if ( count( $params ) > 0 )
		{
			$url .= '?' . implode( '&amp;', $params );
		}
		
		if ( $this->check_404 )
		{
			$headers =  get_headers( $url );
			if ( $headers[0] != 'HTTP/1.1 200 OK' )
			{
				$url = FALSE;
			}
		}
		
		$tagdata = $this->EE->TMPL->tagdata;
		if ( trim($tagdata) == '' ) //there is no tagdata
		{
			return $url;
		}
		else //there is tagdata
		{
			//conditionals
			$conditionals = array();
			$conditionals['gravatar'] = ( $url != FALSE );
			
			//variables
			$tagdata = $this->EE->functions->prep_conditionals( $tagdata, $conditionals );
			
			$parsed = $this->EE->TMPL->parse_variables_row( $tagdata, array( 
					'gravatar' => $url
					) );
			return $parsed;
		}
	}
	
	public function single()
	{
		return $this->gravatar();
	}
	
	public function pair()
	{
		return $this->gravatar();
	}

	// This function describes how the plugin is used.
	public function usage() 
	{
		ob_start();
?>
Full documentation and examples can be found at: http://www.causingeffect.com/software/ee/ce_gravatar

Single Tag:
<img src="{exp:ce_gravatar:single email="aaron@causingeffect.com"}" alt="" />

Pair Tag:
{exp:ce_gravatar:pair email="aaron@causingeffect.com" default="404"}
        {if gravatar}
                {! -- a Gravatar image exists for the provided email address --}
                <img src="{gravatar}" alt="" />
        {if:else}
                {! -- no Gravatar, so let's create our own default image --}
                {exp:ce_img:single src="/images/example/cow_square.jpg" max="80"}
        {/if}
{/exp:ce_gravatar:pair}

<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	} /* End of usage() function */
	
} /* End of class */
/* End of file pi.ce_gravatar.php */ 
/* Location: ./system/expressionengine/third_party/ce_gravatar/pi.ce_gravatar.php */ 