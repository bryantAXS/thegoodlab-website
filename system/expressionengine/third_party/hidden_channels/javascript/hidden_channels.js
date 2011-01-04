/**
 * Hide channels from the EE navigation menu
 */
function hiddenChannelsInit(lang, hiddenChannels) {
	// remove the accessory tab
	$('#accessoryTabs > ul > li > a.hidden_channels').parent('li').remove()
	
	// remove the channel publish links from menu
	$('#navigationTabs > li.parent > a:contains("'+lang.nav_content+'") + ul')
		.find('> li.parent > a:contains("'+lang.nav_publish+'") + ul')
		.find('> li > a').each(function() { hiddenChannelsHideItem(this, hiddenChannels); });
}

/**
 * Hide channels from the EE homepage
 */
function hiddenChannelsHomepage(lang, hiddenChannels) {
	// remove the channel publish links from menu
	$('#mainContent > div.create > ul.homeBlocks > li > a:contains("'+lang.entry+'") + ul')
		.find('> li > a').each(function() { hiddenChannelsHideItem(this, hiddenChannels); });
}

/**
 * Hide the parent element of a link if 
 */
function hiddenChannelsHideItem(elem, hiddenChannels) {
	// find the channel id of current menu item
	var menuLink = $(elem).attr('href');
	var channelId = menuLink.match(/\&channel_id=(\d+)/i);
	channelId = parseInt(channelId[1]);
	
	// check if it is a hidden channel
	if ($.inArray(channelId, hiddenChannels) >= 0) {
		$(elem).parent().remove();
	}
}

/**
 * Restructure the Channel Management page
 */
function hiddenChannelsManagement(lang, hiddenChannels) {
	var mainTable = $('#mainContent table.mainTable');
	mainTable.after('<p id="hidden_channels_note" style="display: none;"><em>'+lang.hidden_channels_note+'</em></p>');
	
	// add the Hidden column to channels table
	mainTable.find('> thead > tr > th:contains("'+lang.delete+'")')
		.before('<th>'+lang.hidden+'</th>');
	
	// add icons to the Hidden column for each channel
	mainTable.find('> tbody > tr').each(function() {
		// find the channel id & initial icon state
		var channelId = parseInt($(this).children('td:first').text());
		var iconHtml = '<a href="' + lang.ICON_CHANNEL_LINK + channelId + '">';
		if ($.inArray(channelId, hiddenChannels) >= 0) {
			iconHtml += lang.ICON_CHANNEL_SHOW + '</a>';
		} else {
			iconHtml += lang.ICON_CHANNEL_HIDE + '</a>';
		}
		
		// add the channel show/hide icon
		$(this).children('td:last')
			.before('<td>'+iconHtml+'</td>')
			// add click handler to show/hide icon
			.prev().children('a').click(function() {
				// show progress icon, then request new icon with ajax
				$(this).html(lang.ICON_CHANNEL_PROGRESS).load($(this).attr('href'));
				$('#hidden_channels_note').show();
				return false;
			});
	});
}