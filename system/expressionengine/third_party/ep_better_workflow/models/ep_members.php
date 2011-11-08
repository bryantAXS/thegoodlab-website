<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Ep_members Model
 *
 * ----------------------------------------------------------------------------------------------
 * @package	EE2 
 * @subpackage	ThirdParty
 * @author	Andrea Fiore / Malcolm Elsworth 
 * @link	http://electricputty.co.uk 
 * @copyright	Copyright (c) 2011 Electric Putty Ltd.
 *
 */

class Ep_members extends CI_Model {

	function Ep_members()
	{
		parent::__construct();
	}



	function get_all_members_from_group($group_id)
	{
		$this->db->where('group_id', $group_id);
		$members = $this->db->get('members');
		if ($members->num_rows() == 0)
		{
			return FALSE;
		}
		else
		{
			return $members;
		}
	}


}