<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(
	'ce_cache_module_name' => 'CE Cache',
	'ce_cache_module_description' => 'Fragment caching via db, files, APC, Redis, Memcache, and/or Memcached',
	'module_home' => 'CE Cache Home',
	'ce_cache_channel_cache_breaking' => 'Cache Breaking',
	'ce_cache_channel_breaking_settings' => '&ldquo;%s&rdquo; Settings',
	'ce_cache_break_settings' => 'Cache Break Settings',
	'ce_cache_driver' => 'Driver',
	'ce_cache_channel' => 'Channel',
	'ce_cache_is_supported' => 'Supported',
	'ce_cache_yes' => 'Yes',
	'ce_cache_no' => 'No',
	'ce_cache_bytes' => 'Bytes',
	'ce_cache_size' => 'Size',
	'ce_cache_driver_file' => 'File',
	'ce_cache_driver_all' => 'All',
	'ce_cache_driver_apc' => 'APC',
	'ce_cache_driver_memcached' => 'Memcached',
	'ce_cache_driver_memcache' => 'Memcache',
	'ce_cache_driver_dummy' => 'Dummy',
	'ce_cache_driver_db' => 'Database',
	'ce_cache_driver_redis' => 'Redis',
	'ce_cache_clear_cache_question_site' => 'Clear Driver Site Cache?',
	'ce_cache_clear_cache_question_driver' => 'Clear Entire Driver Cache?',
	'ce_cache_clear_cache_site' => 'Clear Driver Site Cache',
	'ce_cache_clear_cache_driver' => 'Clear Entire Driver Cache',
	'ce_cache_clear_cache_all_drivers' => 'Clear Entire Cache For All Drivers',
	'ce_cache_clear_cache_site_all' => 'Clear Site Cache For All Drivers',
	'ce_cache_clear_cache' => 'Clear Cache',
	'ce_cache_view_items' => 'View Items',
	'ce_cache_view_item' => 'View Item',
	'ce_cache_view' => 'View',
	'ce_cache_id' => 'Cache Item Id',
	'ce_cache_seconds' => 'seconds',
	'ce_cache_seconds_from_now' => 'seconds from now',
	'ce_cache_created' => 'Created',
	'ce_cache_expires' => 'Expires',
	'ce_cache_ttl' => 'Time To Live',
	'ce_cache_content' => 'Content',
	'ce_cache_delete_children' => 'Delete Children',
	'ce_cache_delete_item' => 'Delete Item',
	'ce_cache_delete' => 'Delete',
	'ce_cache_back_to' => 'Back To',
	'ce_cache_viewing_item_meta' => 'You are viewing the &ldquo;%s&rdquo; cache item.',
	'ce_cache_clear_cache_success' => 'The cache has been cleared successfully.',
	'ce_cache_clear_all_cache_success' => 'The caches have been cleared successfully.',
	'ce_cache_clear_site_cache_success' => 'The site caches for all drivers have been cleared successfully.',
	'ce_cache_delete_item_success' => 'The item has been deleted successfully.',
	'ce_cache_delete_children_success' => 'The child items of the path have been deleted successfully.',
	'ce_cache_confirm_clear' => 'Are you sure you want to clear the entire cache? The cache will be cleared for all sites if you are using Multiple Site Manager.',
	'ce_cache_confirm_clear_all' => 'Are you sure you want to clear the entire cache for all drivers?',
	'ce_cache_confirm_clear_sites' => 'Are you sure you want to clear the cache for all drivers for this site?',
	'ce_cache_confirm_clear_site' => 'You are about to clear the %s driver cache for the current site.',
	'ce_cache_confirm_delete' => 'You are about to delete the &ldquo;%s&rdquo; cache item.',
	'ce_cache_confirm_delete_children' => 'Are you sure you want to delete the children of &ldquo;%s&rdquo;?',
	'ce_cache_confirm_clear_button' => "Yes I'm Sure, Clear the Cache",
	'ce_cache_confirm_clear_all_button' => "Yes I'm Sure, Clear All Driver Caches",
	'ce_cache_confirm_clear_sites_button' => "Yes I'm Sure, Clear The Site Cache For All Drivers",
	'ce_cache_confirm_delete_button' => "Delete the Item",
	'ce_cache_confirm_delete_children_button' => "Yes I'm Sure, Delete the Child Items",
	'ce_cache_error_no_driver' => 'No driver was specified.',
	'ce_cache_no_items' => 'No items were found.',
	'ce_cache_no_more_items' => 'All found items were expired. Please refresh the page.',
	'ce_cache_error_no_path' => 'No path was specified.',
	'ce_cache_error_no_item' => 'No item was specified.',
	'ce_cache_error_invalid_driver' => 'The specified driver is not valid.',
	'ce_cache_error_invalid_path' => 'An item path was not received.',
	'ce_cache_error_getting_items' => 'No cache items were found.',
	'ce_cache_error_getting_meta' => 'No information could be found for the specified item.',
	'ce_cache_error_cleaning_cache' => 'Something went wrong and the cache was *not* cleaned successfully.',
	'ce_cache_error_cleaning_driver_cache' => 'The cache may have *not* been cleaned successfully for the %s driver.',
	'ce_cache_error_deleting_item' => 'Something went wrong and the item "%s" was *not* deleted successfully.',
	'ce_cache_error_no_channel' => 'No channel was specified.',
	'ce_cache_error_channel_not_found' => 'Channel not found.',
	'ce_cache_save_settings' => 'Save Settings',
	'ce_cache_save_settings_success' => 'Your cache break settings have been saved successfully.',
	'ce_cache_any_channel' => 'Any Channel',
	'ce_cache_add' => 'Add',
	'ce_cache_remove' => 'Remove',
	'ce_cache_tags' => 'Tags',
	'ce_cache_tag' => 'Tag',
	'ce_cache_items' => 'Items',
	'ce_cache_variables' => 'Variables',
	'ce_cache_error_module_not_installed' => 'The correct version of the module is not installed, so cache breaking cannot be implemented.',
	'ce_cache_error_invalid_refresh_time' => 'The refresh time must be a number between 0 and 5 inclusively.',
	'ce_cache_error_invalid_item_start' => 'This item must begin with <code>local/</code> or <code>global/</code>.',
	'ce_cache_error_invalid_item_length' => 'This item must be less than or equal to 250 characters in length',
	'ce_cache_error_invalid_tag_character' => 'This tag contains one or more disallowed characters.',
	'ce_cache_error_invalid_tag_length' => 'This tag must be less than or equal to 100 characters in length.',
	'ce_cache_break_intro_html' => '<h3>Cache Breaking</h3>
		<p>This page allows you to remove certain cache items whenever one or more entries from the &ldquo;%s&rdquo; channel are added, updated, or deleted for the current site.</p>
		<p>You can choose to have cache items recreate themselves after they are removed. This will only work for local (non-global) items, as they contain a relative path to a specific page. However, any removed global items that happen to be on a refreshed page will also be recreated.</p>',
	'ce_cache_break_intro_any' => '<h3>Cache Breaking</h3>
			<p>This page allows you to remove certain cache items whenever one or more entries from any channel are added, updated, or deleted for the current site. Individual channel cache break settings will also be applied in addition to these settings.</p>
			<p>You can choose to have cache items recreate themselves after they are removed. This will only work for local (non-global) items, as they contain a relative path to a specific page. However, any removed global items that happen to be on a refreshed page will also be recreated.</p>',
	'ce_cache_refresh_cached_items_question' => 'Refresh cached items after deleting them?',
	'ce_cache_refresh_cached_items_instructions_html' => '<p>Please choose the number of seconds to wait between refreshing cached items. This can be really helpful if you are refreshing a large number of pages, and you don&rsquo;t want to bog down your server all at one time. However, keep in mind that this will take more time; if you have 200 pages with items to be refreshed, and you are delaying 2 seconds between each one, it will take at least 400 seconds (almost 7 minutes) for all of the cache items to be recreated. You will not need to stay on the page while the cache is being recreated.</p>',
	'ce_cache_breaking_tags_instructions_html' => '<p>In your templates, you can assign tags to the It and Save methods using the tag= parameter. You can specify one or more tags below, and any items that have those tags will be removed or refreshed when an entry in this channel changes.</p>
			<p>Click on the &ldquo;add&rdquo; icon below to add a tag.</p>',
	'ce_cache_breaking_tags_examples_html' => '<p>Examples:</p>
			<ul class="ce_cache_break_item_examples">
				<li>To clear all items with a tag of &ldquo;apple&rdquo;, you would add <code>apple</code></li>
				<li>To clear all items with a tag of the current channel name, you could add <code>{channel_name}</code></li>
			</ul>
			<p>Note: Tags are not case sensitive, so <code>apple</code> is considered the same as <code>Apple</code>. Although discouraged, spaces in your tags are allowed, so <code>bricks in the wall</code> is technically a valid tag. Tags may not contain any pipe (<code>|</code>) characters.</p>',
	'ce_cache_breaking_items_instructions_html' => '<p>You can add items or item parent paths to remove or refresh when an entry in this channel changes. All global items should begin with <code>global/</code> and non-global items should begin with <code>local/</code>. If you are specifying a parent path (as opposed to an item id), then be sure to give it a trailing slash (<code>/</code>).</p>
				<p>Click on the &ldquo;add&rdquo; icon below to add an item.</p>',
	'ce_cache_breaking_items_examples_html' => '<p>Here are some examples:</p>
			<ul class="ce_cache_break_item_examples">
				<li>To clear all global items for the entire site, you would add: <code>global/</code></li>
				<li>If you had a &ldquo;blog&rdquo; section of your site, and wanted to remove all cached content under that section, you would add: <code>local/blog/</code></li>
				<li>If you wanted to clear a specific item, like your home page, you could add: <code>local/item</code> (assuming your home page has a cache item with the id &ldquo;item&rdquo;)</li>
				<li>To clear a global item with an id of &ldquo;footer&rdquo;, you could add: <code>global/footer</code></li>
				<li>To clear all local caches where {segment_1} matched the current {channel_name} and {segment_2} matched the {url_title}, use <code>local/{channel_name}/{url_title}/</code></li>
			</ul>',
	'ce_cache_breaking_variables_html' => '<p>The following variables can be used in your tag and item cache breaking settings below: <code>{entry_id}</code>, <code>{url_title}</code>, <code>{channel_id}</code>, <code>{channel_name}</code>, <code>{entry_date format=""}</code>, and <code>{edit_date format=""}</code>. The variables will be replaced with the corresponding values of the currently breaking entry. The two date variables can use <a href="http://expressionengine.com/user_guide/templates/date_variable_formatting.html" target="_blank">date variable formatting</a>.</p>',
	'ce_cache_clear_tagged_items' => 'Clear Tagged Items',
	'ce_cache_clear_tags_instructions' => '<p>The following tags represent cached tag items. Please select which tags you wish to clear.</p>',
	'ce_cache_no_tags' => 'No tags were found for the current site.',
	'ce_cache_confirm_delete_tags_button' => 'Clear The Selected Tags',
	'ce_cache_delete_tags_success' => 'The tags have been cleared successfully.',

	//misc ajax errors
	'ce_cache_ajax_unknown_error' => 'An unknown error occurred.',
	'ce_cache_ajax_no_items_found' => 'No items were found.',
	'ce_cache_ajax_error' => 'An unexpected response was received:',
	'ce_cache_ajax_error_title' => 'Unexpected Response',
	'ce_cache_ajax_install_error' => 'An error has occurred! Please ensure the CE Cache module is installed correctly.',

	//delete child items
	'ce_cache_ajax_delete_child_items_confirmation' => 'Are you sure you want to delete all of the \\\"%s\\\" child items?',
	'ce_cache_ajax_delete_child_items_button' => 'Delete Child Items',
	'ce_cache_ajax_delete_child_items_refresh' => 'Refresh items after deleteing them?',
	'ce_cache_ajax_delete_child_items_refresh_time' => 'How many seconds would you like to wait between refreshing items?',
	//delete item
	'ce_cache_ajax_delete_child_item_confirmation' => 'Are you sure you want to delete the \\\"%s\\\" item?',
	'ce_cache_ajax_delete_child_item_refresh' => 'Refresh the item after it is deleted?',
	'ce_cache_ajax_delete_child_item_button' => 'Delete Item',
	//cancel button
	'ce_cache_ajax_cancel' => 'Cancel'
);

/* End of file lang.ce_cache.php */
/* Location: /system/expressionengine/third_party/ce_cache/language/english/lang.ce_cache.php */
