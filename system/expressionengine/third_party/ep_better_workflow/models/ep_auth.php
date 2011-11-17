<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_auth Model
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_auth extends CI_Model {



	function Ep_auth()
	{
		parent::__construct();
	}



	function set_token()
	{
		$token = $this->_generate_token(25);
		$data = array('token' => $token);
		$this->db->insert('ep_entry_drafts_auth', $data);
		$token_id = $this->db->insert_id();
		return $token_id.'|'.$token;
	}



	function is_valid_request($token_id, $token)
	{
		$theReturn = false;
		if($token == $this->_get_token_value($token_id))
		{
			$theReturn = true;
		}
		return $theReturn;
	}



	function delete_token($token_id)
	{
		$this->db->from('ep_entry_drafts_auth');
		$this->db->where('ep_auth_id', $token_id);
		$this->db->delete();
	}



	private function _get_token_value($token_id)
	{
		$this->db->where('ep_auth_id', $token_id);
		$data = $this->db->get('ep_entry_drafts_auth');
		if ($data->num_rows() == 0)
		{
			return "";
		}
		else
		{
			foreach($data->result_array() as $row)
			{
				return $row['token'];
			}
		}
	}



	private function _generate_token($length)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$size = strlen( $chars );
		$token = '';
		for( $i = 0; $i < $length; $i++ )
		{
			$token .= $chars[ rand( 0, $size - 1 ) ];
		}
		return $token;
	}

}