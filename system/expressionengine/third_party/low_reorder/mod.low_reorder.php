<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include base class
if ( ! class_exists('Low_reorder_base'))
{
	require_once(PATH_THIRD.'low_reorder/base.low_reorder.php');
}

/**
 * Low Reorder Module class
 *
 * @package        low_reorder
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-reorder
 * @copyright      Copyright (c) 2009-2012, Low
 */
class Low_reorder extends Low_reorder_base {

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	* Return data
	*
	* @access      public
	* @var         string
	*/
	public $return_data = '';

	// --------------------------------------------------------------------

	/**
	* Set ID
	*
	* @access      public
	* @var         int
	*/
	private $set_id;

	/**
	* Category ID
	*
	* @access      public
	* @var         int
	*/
	private $cat_id;

	/**
	* Set instance
	*
	* @access      public
	* @var         array
	*/
	private $set;

	/**
	* Entry ids for set/cat combo
	*
	* @access      public
	* @var         array
	*/
	private $entry_ids = array();

	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	/**
	* Display entries in order
	*
	* @access      public
	* @return      string
	*/
	public function entries()
	{
		// --------------------------------------
		// Initiate set to get set_id, cat_id and entry_ids
		// --------------------------------------

		$this->_init_set();

		// --------------------------------------
		// Check if that results into entry_ids
		// --------------------------------------

		if (empty($this->entry_ids))
		{
			return $this->_empty_set();
		}

		// --------------------------------------
		// Check existing entry_id parameter
		// --------------------------------------

		if (isset($this->EE->TMPL->tagparams['entry_id']) && strlen($this->EE->TMPL->tagparams['entry_id']))
		{
			$this->EE->TMPL->log_item('Low Reorder: entry_id parameter found, filtering ordered entries accordingly');

			// Get the parameter value
			list($ids, $in) = low_explode_param($this->EE->TMPL->tagparams['entry_id']);

			// Either remove $ids from $entry_ids OR limit $entry_ids to $ids
			$method = $in ? 'array_intersect' : 'array_diff';

			// Get list of entry ids that should be listed
			$this->entry_ids = $method($this->entry_ids, $ids);
		}

		// If that results in empty ids, bail out again
		if (empty($this->entry_ids))
		{
			return $this->_empty_set();
		}

		// --------------------------------------
		// Add fixed_order to parameters
		// --------------------------------------

		$this->set['parameters']['fixed_order'] = implode('|', $this->entry_ids);

		// --------------------------------------
		// Set template parameters
		// --------------------------------------

		// Check whether to force template params or not
		$force = ($this->EE->TMPL->fetch_param('force_set_params', 'no') == 'yes');

		// Set the params
		$this->_set_template_parameters($force);

		// --------------------------------------
		// Set dynamic="no" as default
		// --------------------------------------

		if ($this->EE->TMPL->fetch_param('dynamic') != 'yes')
		{
			$this->EE->TMPL->tagparams['dynamic'] = 'no';
		}

		// --------------------------------------
		// Use channel module to generate entries
		// --------------------------------------

		// Set low_reorder param so extension kicks in
		$this->EE->TMPL->tagparams['low_reorder'] = 'yes';

		return $this->_channel_entries();
	}

	/**
	* Return pipe-delimited list of ordered entry_ids
	*
	* @access      public
	* @return      string
	*/
	public function entry_ids()
	{
		// --------------------------------------
		// Initiate set
		// --------------------------------------

		$this->_init_set();

		// --------------------------------------
		// Get some parameters and check pair tag
		// --------------------------------------

		$pair       = ($tagdata = $this->EE->TMPL->tagdata) ? TRUE : FALSE;
		$no_results = $this->EE->TMPL->fetch_param('no_results', $this->EE->TMPL->no_results());
		$separator  = $this->EE->TMPL->fetch_param('separator', '|');

		// --------------------------------------
		// Bail out if no results...
		// --------------------------------------

		if ( ! $this->entry_ids && ! $pair)
		{
			return $this->empty_set($no_results);
		}
		else
		{
			// --------------------------------------
			// We need to get the actual entry ids.
			// Some entries might not be applicable,
			// due to the filters defined in the Set.
			// --------------------------------------

			// Set/Cat key
			$key = $this->set_id.'-'.$this->cat_id;

			// Get entries from cache
			$entries = array_filter((array) low_get_cache(LOW_REORDER_PACKAGE, 'entry_ids'));

			if ( ! isset($entries[$key]))
			{
				// Log to template
				$this->EE->TMPL->log_item('Low Reorder: getting filtered entry_ids from database');

				// Add channel_id and entry_id as parameter
				$params = $this->set['parameters'];
				$params['channel_id'] = implode('|', $this->set['channels']);
				$params['entry_id']   = implode('|', $this->entry_ids);

				// Fetch from DB
				$filtered = low_flatten_results($this->get_entries($params, FALSE), 'entry_id');

				// Intersect to preserve the order
				$filtered = array_intersect($this->entry_ids, $filtered);

				// Add to cache
				$entries[$key] = $filtered;
				low_set_cache(LOW_REORDER_PACKAGE, 'entry_ids', $entries);

				// Clean up
				unset($filtered);
			}
			else
			{
				// Log to template
				$this->EE->TMPL->log_item('Low Reorder: getting filtered entry_ids from cache');
			}
		}


		// --------------------------------------
		// Final filtered entries;
		// bail out if it results in an empty set
		// --------------------------------------

		if ( ! ($entry_ids = $entries[$key]))
		{
			if ($pair)
			{
				$entry_ids = $no_results;
			}
			else
			{
				return $this->empty_set($no_results);
			}
		}
		else
		{
			// Change entry_ids to single string
			$entry_ids = implode($separator, $entry_ids);
		}

		// --------------------------------------
		// Either return them for single tag,
		// or parse row for tag pair
		// --------------------------------------

		if ($tagdata = $this->EE->TMPL->tagdata)
		{
			return $this->EE->TMPL->parse_variables_row($tagdata, array(
				'low_reorder:entry_ids' => $entry_ids
			));
		}
		else
		{
			return $entry_ids;
		}
	}

	// --------------------------------------------------------------------

	/**
	* Add leading 0's to count
	*
	* @access      public
	* @return      string
	*/
	public function pad()
	{
		// Get parameters
		$input  = (string) $this->EE->TMPL->fetch_param('input', '');
		$length = (int) $this->EE->TMPL->fetch_param('length', 1);
		$string = (string) $this->EE->TMPL->fetch_param('string', '0');
		$type   = ($this->EE->TMPL->fetch_param('type', 'left') != 'right') ?  STR_PAD_LEFT : STR_PAD_RIGHT;

		// Add padding if necessary
		if (strlen($input) < $length)
		{
			$input = str_pad($input, $length, $string, $type);
		}

		// return the formatted number
		return $input;
	}

	// --------------------------------------------------------------------

	/**
	* Get next entry in custom order
	*
	* @access      public
	* @return      string
	*/
	public function next_entry()
	{
		return $this->_prev_next('next');
	}

	/**
	* Get previous entry in custom order
	*
	* @access      public
	* @return      string
	*/
	public function prev_entry()
	{
		return $this->_prev_next('prev');
	}

	/**
	* Get next or previous entry in custom order
	*
	* @access      private
	* @param       string
	* @return      string
	*/
	private function _prev_next($which)
	{
		// --------------------------------------
		// Initiate set
		// --------------------------------------

		$this->_init_set();

		// --------------------------------------
		// Get other parameters
		// --------------------------------------

		$params = array('entry_id', 'url_title', 'prefix', 'no_results', 'loop');

		foreach ($params AS $param)
		{
			$$param = $this->EE->TMPL->fetch_param($param);
		}

		// --------------------------------------
		// Get set entries
		// --------------------------------------

		if ( ! ($entries = $this->entry_ids))
		{
			return $this->_empty_set();
		}

		// --------------------------------------
		// We need a $entry_id or $url_title to go on
		// --------------------------------------

		if ( ! $entry_id && ! $url_title)
		{
			$this->EE->TMPL->log_item('Low Reorder: no entry_id or url_title given, returning empty string');
			return;
		}

		// --------------------------------------
		// Make sure we've got an entry id
		// --------------------------------------

		if ( ! $entry_id && strlen($url_title))
		{
			// Get entry id by url_title
			$entry_id = $this->_get_entry_id($url_title, $entries);
		}

		// Initiate row
		$row = array();

		// --------------------------------------
		// Get the current order and filter out current
		// --------------------------------------

		if ($entries && $entry_id)
		{
			// Reverse it for previous entries
			if ($which == 'prev')
			{
				$entries = array_reverse($entries);
			}

			// Get current entry's index
			$index = array_search($entry_id, $entries);

			// Get entries above current, if any, and if we're looping
			$top = ($loop == 'yes') ? array_slice($entries, 0, $index) : array();

			// Get entries below current
			$bottom = array_slice($entries, $index + 1);

			// Combine bottom and top to get stack of entries that could be the prev/next entry
			$entries = array_merge($bottom, $top);

			// --------------------------------------
			// If we still have entries, go and get them from the DB
			// --------------------------------------

			if ($entries)
			{
				// Log the entries for debugging purposes
				$this->EE->TMPL->log_item("Low Reorder: Getting {$which} entry from stack ".implode('|', $entries));

				/*
					// Using the Channel:entries tag for displaying
					// Strip optional prefix from template data
					$this->_strip_prefix($prefix);

					// Parameters to set
					$tagparams = array(
						'fixed_order' => implode('|', $entries),
						'limit'       => '1',
						'sort'        => 'asc',
						'dynamic'     => 'no'
					);

					// Check if the disable param is set
					if ( ! $this->EE->TMPL->fetch_param('disable'))
					{
						$tagparams['disable'] = 'categories|member_data|pagination|channel_fields';
					}

					// Add the params to the set
					$this->set['parameters'] = array_merge($this->set['parameters'], $tagparams);

					// Force params
					$this->_set_template_parameters(TRUE);

					// Unset before calling channel entries
					foreach ($params AS $param)
					{
						unset($this->EE->TMPL->tagparams[$param]);
					}

					$this->return_data = $this->_channel_entries();
				*/

				$params = $this->EE->low_reorder_set_model->get_params($this->set['parameters']);
				$params['category']   = $this->cat_id;
				$params['channel_id'] = implode('|', $this->set['channels']);
				$params['entry_id']   = implode('|', $entries);

				// Get site pages
				if ($pages = $this->EE->config->item('site_pages'))
				{
					$pages = $pages[$this->site_id];
				}

				// Get the entry and focus on the single row
				foreach ($this->get_entries($params, $entries, 1) AS $entry)
				{
					// Account for Pages uri / url
					$entry['page_uri'] = (isset($pages['uris'][$entry['entry_id']]))
					                   ? $pages['uris'][$entry['entry_id']]
					                   : '';

					$entry['page_url'] = (isset($pages['url']) && strlen($entry['page_uri']))
					                   ? $this->EE->functions->create_page_url($pages['url'], $entry['page_uri'])
					                   : '';

					foreach ($entry AS $key => $val)
					{
						$row[$prefix.$key] = $val;
					}
				}

				// Parse the single row
				$this->return_data = $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $row);
			}
		}

		// --------------------------------------
		// Nothing to return? Trigger no_results
		// --------------------------------------

		if (empty($row))
		{
			$this->return_data = ($no_results === FALSE) ? $this->EE->TMPL->no_results() :  $no_results;
		}

		return $this->return_data;
	}

	// --------------------------------------------------------------------

	/**
	 * Initiate tag, set $this->set_id, $this->cat_id, $this->set and $this->entry_ids
	 *
	 * @access      private
	 * @param       int      set id
	 * @return      array
	 */
	private function _init_set()
	{
		// --------------------------------------
		// Get set_id and set details
		// --------------------------------------

		if ($this->set_id = $this->EE->TMPL->fetch_param('set'))
		{
			$this->set = $this->_get_set($this->set_id);
		}

		// --------------------------------------
		// Check category param if cat_option = one, default to 0
		// --------------------------------------

		$this->cat_id
			= (@$this->set['cat_option'] == 'one')
			? $this->_get_cat_id($this->set['cat_groups'])
			: 0;

		// --------------------------------------
		// Get entry ids for this set/cat
		// --------------------------------------

		if (isset($this->set[$this->cat_id]))
		{
			$this->entry_ids = $this->set[$this->cat_id];
		}
	}

	/**
	 * Get Set details from Cache or DB
	 *
	 * @access      private
	 * @param       int      set id
	 * @return      array
	 */
	private function _get_set($set_id)
	{
		// Get sets from cache
		$sets = array_filter((array) low_get_cache(LOW_REORDER_PACKAGE, 'sets'));

		// If it's not set, get it from DB
		if ( ! isset($sets[$set_id]))
		{
			$this->EE->TMPL->log_item("Low Reorder: Retrieving set from database");

			// Get set and its orders
			$query = $this->EE->db->select('s.set_id, s.channels, s.cat_option, s.cat_groups, s.parameters, o.cat_id, o.sort_order')
			       ->from($this->EE->low_reorder_set_model->table() . ' s')
			       ->join($this->EE->low_reorder_order_model->table() . ' o', 's.set_id = o.set_id')
			       ->where('s.set_id', $set_id)
			       ->get();

			// Loop through results and add add to sets array
			foreach ($query->result() AS $row)
			{
				if ( ! isset($sets[$set_id]))
				{
					$sets[$set_id] = array(
						'channels'   => low_delinearize($row->channels),
						'cat_option' => $row->cat_option,
						'cat_groups' => low_delinearize($row->cat_groups),
						'parameters' => $this->EE->low_reorder_set_model->get_params($row->parameters)
					);
				}
				$sets[$set_id][$row->cat_id] = low_delinearize($row->sort_order);
			}

			// Register new sets array to cache
			low_set_cache(LOW_REORDER_PACKAGE, 'sets', $sets);
		}
		else
		{
			$this->EE->TMPL->log_item("Low Reorder: Retrieving set from cache");
		}

		// Return the requested set
		return (array) @$sets[$set_id];
	}

	/**
	 * Get entry id from Cache or DB
	 *
	 * @access      private
	 * @param       string   url title
	 * @param       array    limited by these entry ids
	 * @return      int
	 */
	private function _get_entry_id($url_title, $entry_ids = array())
	{
		// Get entries from cache
		$entries = array_filter((array) low_get_cache(LOW_REORDER_PACKAGE, 'entries'));

		// If it's not set, get it from DB
		if ( ! isset($entries[$url_title]))
		{
			$this->EE->TMPL->log_item("Low Reorder: Retrieving entry_id from database");

			// Get get the entry id
			$query = $this->EE->db->select('entry_id')
			       ->from('channel_titles')
			       ->where('url_title', $url_title)
			       ->where_in('entry_id', $entry_ids)
			       ->where('site_id', $this->site_id)
			       ->limit(1)
			       ->get();

			// Add it to the entries array
			if ($query->num_rows())
			{
				$entries[$url_title] = $query->row('entry_id');
			}

			// Register new sets array to cache
			low_set_cache(LOW_REORDER_PACKAGE, 'entries', $entries);
		}
		else
		{
			$this->EE->TMPL->log_item("Low Reorder: Retrieving entry_id from cache");
		}

		// Return the requested set
		return (int) @$entries[$url_title];
	}

	/**
	 * Get category id from param, URI, DB or Cache
	 *
	 * @access      private
	 * @param       array    limited by these category groups
	 * @return      int
	 */
	private function _get_cat_id($cat_groups = array())
	{
		// --------------------------------------
		// Check category parameter first
		// --------------------------------------

		if ($cat_id = $this->EE->TMPL->fetch_param('category'))
		{
			$this->EE->TMPL->log_item("Low Reorder: Retrieving cat_id from parameter");
			return $cat_id;
		}

		// --------------------------------------
		// Check URI for C123
		// --------------------------------------

		if (preg_match('#/?C(\d+)(/|$)#', $this->EE->uri->uri_string(), $match))
		{
			$this->EE->TMPL->log_item("Low Reorder: Retrieving cat_id from URI");
			return $match[1];
		}

		// --------------------------------------
		// Check URI for category keyword
		// --------------------------------------

		// Check if cat group is not empty and reserved category word is valid
		if ($cat_groups &&
			($this->EE->config->item('use_category_name') == 'y') &&
			($cat_word = $this->EE->config->item('reserved_category_word')) != '')
		{
			// Check if reserved cat word is in URI and if there's a segment behind it
			if (($key = array_search($cat_word, $this->EE->uri->segment_array())) &&
				($cat_url_title = $this->EE->uri->segment($key + 1)))
			{
				// Get category cache
				$categories = (array) low_get_cache(LOW_REORDER_PACKAGE, 'categories');

				// Fetch cat_id from DB if not in cache
				if ( ! ($cat_id = (int) array_search($cat_url_title, $categories)))
				{
					$this->EE->TMPL->log_item("Low Reorder: Retrieving cat_id from database");

					$query = $this->EE->db->select('cat_id, cat_url_title')
					       ->from('categories')
					       ->where('cat_url_title', $cat_url_title)
					       ->where_in('group_id', $cat_groups)
					       ->get();

					$cat_id = $query->row('cat_id');
					$categories[$cat_id] = $query->row('cat_url_title');
					low_set_cache(LOW_REORDER_PACKAGE, 'categories', $categories);
				}
				else
				{
					$this->EE->TMPL->log_item("Low Reorder: Retrieving cat_id from cache");
				}

				// Return the cat id
				return $cat_id;
			}
		}

		// Return 0 by default if all else fails
		return 0;
	}

	/**
	 * Log empty set message to template debugger, return empty string
	 *
	 * @access      private
	 * @param       bool     use no_results() or not
	 * @return      string
	 */
	private function _empty_set($no_results = NULL)
	{
		$this->EE->TMPL->log_item("Low Reorder: Empty set for set_id {$this->set_id} / cat_id {$this->cat_id}");
		return is_string($no_results) ? $no_results : $this->EE->TMPL->no_results();
	}

	// --------------------------------------------------------------------

	/**
	 * Set template parameters based on given params
	 *
	 * @access      private
	 * @param       bool     Force overwrite or not
	 * @return      void
	 */
	private function _set_template_parameters($force = FALSE)
	{
		// For logging
		$params = array();

		foreach ($this->set['parameters'] AS $key => $val)
		{
			// Keep track of param
			$params[] = sprintf('%s="%s"', $key, $val);

			// Search parameter
			if (substr($key, 0, 7) == 'search:')
			{
				// Strip off the 'search:' prefix
				$key = substr($key, 7);

				if ($force || ! isset($this->EE->TMPL->search_fields[$key]))
				{
					$this->EE->TMPL->search_fields[$key] = $val;
				}
			}
			else
			{
				if ($force || ! isset($this->EE->TMPL->tagparams[$key]))
				{
					$this->EE->TMPL->tagparams[$key] = $val;
				}
			}
		}

		// Log this
		$this->EE->TMPL->log_item('Low Reorder: Setting parameters '.implode(' ', $params));
	}

	/**
	 * Reset current template vars
	 *
	 * @access      private
	 * @return      void
	 */
	private function _strip_prefix($prefix)
	{
		// Do nothing if no prefix is given
		if ( ! $prefix) return;

		// Shortcut to tagdata
		$td =& $this->EE->TMPL->tagdata;

		// Simple replace for prefixed vars
		$td = str_replace(LD.$prefix, LD, $td);

		// Check if there are conditionals
		if ($conds = $this->EE->functions->assign_conditional_variables($td))
		{
			foreach ($conds AS $cond)
			{
				$td = str_replace(
					$cond[0],
					preg_replace('#(\s)'.preg_quote($prefix).'([\w_])#', '$1$2', $cond[0]),
					$td
				);
			}
		}

		// Reset template vars
		$vars = $this->EE->functions->assign_variables($td);
		$this->EE->TMPL->var_single = $vars['var_single'];
		$this->EE->TMPL->var_pair   = $vars['var_pair'];
	}

	/**
	 * Call the native channel:entries method
	 *
	 * @access      private
	 * @return      string
	 */
	private function _channel_entries()
	{
		$this->EE->TMPL->log_item('Low Reorder: Calling the channel module');

		// --------------------------------------
		// Take care of related entries
		// --------------------------------------

		// We must do this, 'cause the template engine only does it for
		// channel:entries or search:search_results. The bastard.
		$this->EE->TMPL->tagdata = $this->EE->TMPL->assign_relationship_data($this->EE->TMPL->tagdata);

		// Add related markers to single vars to trigger replacement
		foreach ($this->EE->TMPL->related_markers AS $var)
		{
			$this->EE->TMPL->var_single[$var] = $var;
		}

		// --------------------------------------
		// Include channel module
		// --------------------------------------

		if ( ! class_exists('channel'))
		{
			require_once PATH_MOD.'channel/mod.channel'.EXT;
		}

		// --------------------------------------
		// Create new Channel instance
		// --------------------------------------

		$channel = new Channel();

		// --------------------------------------
		// Let the Channel module do all the heavy lifting
		// --------------------------------------

		return $channel->entries();
	}

	// --------------------------------------------------------------------

}
// END Low_reorder class