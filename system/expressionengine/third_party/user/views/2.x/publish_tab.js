jQuery(function($)
{
	//----------------------------------------------------------------------------------
	// preliminary actions and starting vars
	//----------------------------------------------------------------------------------
	
	// Change Tab Name to Our Custom One in Preferences
	$("#menu_user a:first").html("<?php echo $tag_name;?>");
	// Hide the WriteMode image for our Tag Field 
	$('#id_user__solspace_user_browse_authors').hide(); 

	var primaryAuthor 	= 0,
		userAuthors		= [];

	//----------------------------------------------------------------------------------
	// jQuery cached lookups
	//----------------------------------------------------------------------------------

	var $primaryAuthorBox 	= $('#hold_field_user__solspace_user_primary_author').hide(),
	 	$userAuthorsField	= $('#user__solspace_user_browse_authors').hide(),
		$primaryAuthorField = $('#user__solspace_user_primary_author');

	//----------------------------------------------------------------------------------
	// views  - I really dont like having these here - gf
	//----------------------------------------------------------------------------------

	var views = {};
	
	views.choice = [
		'<div id="solspace_uid_{id}" class="solspace_user_choice ui-state-default ui-corner-all">',
			'{screen_name}',
			'<div class="solspace_chose ui-state-default ui-corner-all">',
				'<span class="ui-icon ui-icon-circle-triangle-e"><\/span>',
			'<\/div>',
		'<\/div>'
	].join('\n');

	views.chosen = [
		'<div id="solspace_uid_{id}" class="solspace_user_choice ui-state-default ui-corner-all">',
			'{radio}',
			'{screen_name}',	
			'<div class="solspace_chose ui-state-default ui-corner-all">',
				'<span class="ui-icon ui-icon-circle-close"><\/span>',
			'<\/div>',
		'<\/div>'
	].join('\n');

	views.chosenPrimary = [
		'<div class="solspace_primary ui-state-default ui-corner-all">',
			'<span class="ui-icon ui-icon-bullet"><\/span>',
		'<\/div>'	
	].join('\n');
	
	views.chosenNonPrimary = [
		'<div class="solspace_primary ui-state-default ui-corner-all">',
			'<span class="ui-icon ui-icon-radio-on"><\/span>',
		'<\/div>'
	].join('\n');
	
	views.loading = [
		'<div id="solspace_loader_{id}">', 
			'<img src="<?=$loading_img_uri?>" alt="loading"/>', 
			'<?=$lang_loading_users?>',
		'</div>'
	].join('\n');

	//----------------------------------------------------------------------------------
	// utils
	//----------------------------------------------------------------------------------

	//parses out brackets from view files a bit like EE
	function view(template, data)
	{
		for(var item in data)
		{
			if (data.hasOwnProperty(item))
			{
				template = template.replace('{' + item + '}', data[item]);
			}
		}
		
		return template;
	}

	//removes the id from the list
	function removeUser(id)
	{
		var tempArray = [];
		
		for (var i = 0, l = userAuthors.length; i < l; i++) 
		{
			if ( userAuthors[i] !== id)
			{
				tempArray.push(userAuthors[i]);
			}
		}
		
		//reset array with new array, missing removed id
		userAuthors = tempArray;
		
		//change primaryAuthor 
		primaryAuthor = ( primaryAuthor !== id ) ? primaryAuthor : 0;
		
		//send
		parseFieldData('out');
	}

	function parseFieldData(direction)
	{
		if (direction == 'in')
		{
			//check primary author isnan first
			var tempNum		= parseInt($primaryAuthorField.val(), 10);
			primaryAuthor  	= ( ! isNaN(tempNum) ) ? tempNum : 0;
			
			
			//array needs a bit of checking
			var tempArray	= $userAuthorsField.val().split(',');
			var cleanArray	= [];
			
			//bail if not clean			
			if ( tempArray.length == 1 && 
				 ( $.trim(tempArray[0]) === '' || isNaN(parseInt(tempArray[0], 10)) )
			   )
			{
				return;
			}
			
			//make sure everything is a proper number, no funny business
			//no 0's either. that person doens't exist!
			for(var i = 0, l = tempArray.length; i < l; i++)
			{
				tempNum			= parseInt(tempArray[i], 10); 
				
				if (! isNaN(tempNum) && tempNum !== 0) 
				{
					cleanArray.push(tempNum);
				}
			}
			
			//clean? sweet
			userAuthors = cleanArray;
		}
		else if (direction == 'out')
		{
			$primaryAuthorField.val(primaryAuthor);
			$userAuthorsField.val(userAuthors.join(','))
		}
	}
	
	//----------------------------------------------------------------------------------
	// work
	//----------------------------------------------------------------------------------

	//do we have left over, or incoming data from the fields?
	parseFieldData('in');
	
	//makes #solspace_loader_1
	$userAuthorsField.after(view(views.loading, {id:1}));
	
	//get template and start binding of functions
	$.get(
		'<?=$template_uri?>&no_cache=' + (new Date()).getTime(), 
		{
			primary_author 	: primaryAuthor, 
			user_authors 	: userAuthors.join(',')
		}, 
		function(templateData) 
		{
			$('#solspace_loader_1').remove();
			
			//must happen before everything else!
			$userAuthorsField.after(templateData); 
		
			var $users 			= $('#solspace_user_authors_results'),
				$authors		= $('#solspace_user_authors .holder:first'),
				$search			= $("#solspace_user_search"),
				$searchButton	= $("#solspace_user_search_button"),
				$notFound		= $('#solspace_user_not_found');
		
			function choseClick () {
				var $that = $(this),
					$parent = $that.parent(),
					$id = $parent.attr('id'),
					id = parseInt($id.substring($id.lastIndexOf('_') + 1, $id.length), 10);
					
				userAuthors.push(id);
				parseFieldData('out');
				
				$that.removeClass('solspace_chose').addClass('solspace_chosen');
				$('span:first', $that).removeClass('ui-icon-circle-triangle-e').addClass('ui-icon-circle-close');
				$that.unbind('click', choseClick).bind('click', chosenClick);
			
				$parent.prepend(views.chosenNonPrimary);
				$authors.append($parent);
			}

			function chosenClick () {
				var $that = $(this),
					$parent = $that.parent(),
					$id = $parent.attr('id'),
					id = parseInt($id.substring($id.lastIndexOf('_') + 1, $id.length), 10);				
				
				//kill parent item
				$parent.remove();
				
				//pull name from list
				removeUser(id);
			}
						
			$('.solspace_user_choice .solspace_chose').live('click', choseClick);

			$('.solspace_user_choice .solspace_chosen').live('click', chosenClick);		
		
			$('.solspace_primary').live('click', function() {
				var $span = $('span', this),
					toggle = $span.hasClass('ui-icon-radio-on') ? 'on' : 'off';
				
				//all off
				$('.solspace_primary span').removeClass('ui-icon-bullet').addClass('ui-icon-radio-on');
				
				primaryAuthor = 0;
				
				//if we are turning this ON, do the work, else stay
				if (toggle == 'on')
				{
					$span.addClass('ui-icon-bullet').removeClass('ui-icon-radio-on');
					var $id = $(this).parent().attr('id');
					
					//get our own id for sending
					primaryAuthor = parseInt($id.substring($id.lastIndexOf('_') + 1, $id.length), 10); 
				}
				
				//send the data properly
				parseFieldData('out');
			});
			
			$searchButton.click(function(event)
			{
				return user_authors_search(event);
			});

			$search.keypress(function(event)
			{
				if ( event.which == 13 )
				{
					return user_authors_search(event);
				}
			});

			function user_authors_search(event)
			{
				$notFound.hide();
				
				//remove current search items
				$users.find('.solspace_user_choice').remove();
				
				$('solspace_user_authors_results').before(view(views.loading, {id:2}));

				$.ajax({
					type		: 'POST', 
					url 		: "<?=$user_search_uri?>",
					data		: {
						author		: $.trim($search.val()),
						existing	: userAuthors.join('||') 
					},
					success 	: function(data)
					{
						$('#solspace_loader_1').remove();
						
						if ( data.found === false )
						{
							$notFound.show();
						}
						else
						{
							for(var item in data.users)
							{
								if (typeof data.users[item].name !== "undefined")
								{
									$users.append(view(views.choice, {
										'id' 			: data.users[item].id,
										'screen_name'	: data.users[item].name
									}));
								}
							}
						}
					},
					dataType	: 'json'	
				});

				return false;
			}
		}
	);
	//end get template ajax
}); 