/**
 * Assets Properties HUD
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */


(function($) {


// define the namespace
var NS = 'assets-properties';


/**
 * Properties
 */
Assets.Properties = function($file) {
	var obj = this;

	// is there already an active props HUD?
	if (Assets.Properties.active) {
		Assets.Properties.active.close();
	}

	// register this one
	Assets.Properties.active = obj;

	// create the HUD
	var hud = new Assets.HUD($file, 'assets-props'),
		$cancelBtn = $('<a class="assets-btn">'+Assets.lang.cancel+'</a>').appendTo(hud.$buttons),
		$saveBtn = $('<a class="assets-btn assets-submit assets-disabled">'+Assets.lang.save_changes+'</a>').appendTo(hud.$buttons);

	Assets.Properties.requestId++;

	var filePath = $file.attr('data-file-path');

	var data = {
		requestId: Assets.Properties.requestId,
		file_path: filePath
	};

	// run the ajax post request
	$.post(Assets.actions.get_props, data, function(data, textStatus) {
		if (textStatus == 'success') {
			// ignore if this isn't the current request
			if (data.requestId != Assets.Properties.requestId) return;

			// update the HTML
			hud.addContents(data.html);

			// set the filedata height
			var filenameHeight = $('> .assets-filename', hud.$contents).outerHeight(),
				buttonsHeight = $('> .assets-btns', hud.$hud).outerHeight(),
				filedataHeight = hud.height - filenameHeight - buttonsHeight - 20,
				$filedata = $('> .assets-filedata', hud.$contents).height(filedataHeight);

			var inputs = {};

			/**
			 * Check if input has changed
			 */
			var checkInput = function(prop) {
				inputs[prop].changed = (inputs[prop].$input.val() != inputs[prop].startVal);
				updateSaveBtnState();
			};

			/**
			 * Update Save button state
			 */
			var updateSaveBtnState = function() {
				for (var prop in inputs) {
					if (inputs[prop].changed) {
						$saveBtn.removeClass('assets-disabled');
						return;
					}
				}
				$saveBtn.addClass('assets-disabled');
			};

			/**
			 * Submit
			 */
			var submit = function() {
				// ignore if disabled
				if ($saveBtn.hasClass('assets-disabled')) return;

				var saveData = {};

				for (var prop in inputs) {
					// has this value changed?
					var val = inputs[prop].$input.val();
					if (val !== inputs[prop].startVal) {
						saveData['data['+prop+']'] = val;
					}
				}

				if (saveData) {
					saveData['data[file_path]'] = data.file_path;

					$.post(Assets.actions.save_props, saveData);

					// close the HUD
					obj.close();
				}
			};

			// -------------------------------------------
			//  Initialize fields
			// -------------------------------------------

			// get the property rows
			var $trs = $('> table > tbody > tr', $filedata);

			/**
			 * Initialize Text Property
			 */
			var initTextProp = function(prop, settings) {
				var $tr = $trs.filter('.assets-'+prop);
				if ($tr.length) {
					inputs[prop] = {};
					inputs[prop].$input = $('> td > textarea, > td > input', $tr);
					new Assets.Properties.Text($tr, inputs[prop].$input, settings, function() {
						checkInput(prop);
					});

					// save the starter value
					inputs[prop].startVal = inputs[prop].$input.val();

					// submit on return
					inputs[prop].$input.keydown(function(event) {
						if (event.keyCode == 13 && (! settings.multiline || event.altKey)) {
							event.preventDefault();
							checkInput(prop);
							submit();
						}
					});
				}
			};

			initTextProp('title', { maxl: 100 });
			initTextProp('date');
			initTextProp('alt_text', { maxl: 255 });
			initTextProp('caption', { maxl: 255 });
			initTextProp('desc', { maxl: 65535, multiline: true });
			initTextProp('author', { maxl: 255 });
			initTextProp('location', { maxl: 255 });
			initTextProp('keywords', { maxl: 65535, multiline: true });

			// -------------------------------------------
			//  Initialize date field
			// -------------------------------------------

			var date = new Date(),
				hours = date.getHours(),
				minutes = date.getMinutes();

			if (minutes < 10) minutes = '0'+minutes;

			if (hours >= 12) {
				hours = hours - 12;
				var meridiem = ' PM';
			} else {
				var meridiem = ' AM';
			}

			var time = " \'"+hours+':'+minutes+meridiem+"\'";

			inputs.date.$input.datepicker({
				dateFormat: $.datepicker.W3C + time,
				defaultDate: new Date(data.defaultDate)
			});

			// -------------------------------------------
			//  Save button
			// -------------------------------------------

			$saveBtn.click(submit);
		}
	}, 'json');

	/**
	 * Close Properties
	 */
	obj.close = function() {
		hud.$hud.fadeOut('fast', function() {
			delete obj;
		});
	};

	$cancelBtn.click(obj.close);

};

Assets.Properties.requestId = 0;


// ====================================================================


var hudInnerPadding = 10,
	hudOuterPadding = 15

var $window = $(window);


/**
 * Heads-up Display
 */
Assets.HUD = function($target, hudClass) {
	var hud = this;

	hud.loadingContents = true;

	hud.$hud = $('<div class="assets-hud '+hudClass+'" />').appendTo(document.body);

	var $tip = $('<div class="assets-tip" />').appendTo(hud.$hud);

	hud.$contents = $('<div class="assets-contents" />').appendTo(hud.$hud);
	hud.$buttons = $('<div class="assets-btns" />').appendTo(hud.$hud);

	// -------------------------------------------
	//  Where are we putting it?
	//   - Ideally, we'll be able to find a place to put this where it's not overlapping the target at all.
	//     If we can't find that, either put it to the right or below the target, depending on which has the most room.
	// -------------------------------------------

	var windowWidth = $window.width(),
		windowHeight = $window.height(),

		windowScrollLeft = $window.scrollLeft(),
		windowScrollTop = $window.scrollTop(),

		// get the target element's dimensions
		targetWidth = $target.width(),
		targetHeight = $target.height(),

		// get the offsets for each side of the target element
		targetOffset = $target.offset(),
		targetOffsetRight = targetOffset.left + targetWidth,
		targetOffsetBottom = targetOffset.top + targetHeight,
		targetOffsetLeft = targetOffset.left,
		targetOffsetTop = targetOffset.top;

	// get the HUD dimensions
	hud.width = hud.$hud.width();
	hud.height = hud.$hud.height();

		// get the minumum horizontal/vertical clearance needed to fit the HUD
	var minHorizontalClearance = hud.width + hudInnerPadding + hudOuterPadding,
		minVerticalClearance = hud.height + hudInnerPadding + hudOuterPadding,

		// find the actual available right/bottom/left/top clearances
		rightClearance = windowWidth + windowScrollLeft - targetOffsetRight,
		bottomClearance = windowHeight + windowScrollTop - targetOffsetBottom,
		leftClearance = targetOffsetLeft - windowScrollLeft,
		topClearance = targetOffsetTop - windowScrollTop;

	/**
	 * Set Top
	 */
	var setTopPos = function() {
		var maxTop = (windowHeight + windowScrollTop) - (hud.height + hudOuterPadding),
			minTop = (windowScrollTop + hudOuterPadding),

			targetCenter = targetOffsetTop + Math.round(targetHeight / 2),
			top = targetCenter - Math.round(hud.height / 2);

		// adjust top position as needed
		if (top > maxTop) top = maxTop;
		if (top < minTop) top = minTop;

		hud.$hud.css('top', top);

		// set the tip's top position
		var tipTop = (targetCenter - top) - 15;
		$tip.css('top', tipTop);
	};

	/**
	 * Set Left
	 */
	var setLeftPos = function() {
		var maxLeft = (windowWidth + windowScrollLeft) - (hud.width + hudOuterPadding),
			minLeft = (windowScrollLeft + hudOuterPadding),

			targetCenter = targetOffsetLeft + Math.round(targetWidth / 2),
			left = targetCenter - Math.round(hud.width / 2);

		// adjust left position as needed
		if (left > maxLeft) left = maxLeft;
		if (left < minLeft) left = minLeft;

		hud.$hud.css('left', left);

		// set the tip's left position
		var tipLeft = (targetCenter - left) - 15;
		$tip.css('left', tipLeft);
	};

	// to the right?
	if (rightClearance >= minHorizontalClearance) {
		var left = targetOffsetRight + hudInnerPadding;
		hud.$hud.css('left', left);
		setTopPos();
		$tip.addClass('assets-tip-left');
	}
	// below?
	else if (bottomClearance >= minVerticalClearance) {
		var top = targetOffsetBottom + hudInnerPadding;
		hud.$hud.css('top', top);
		setLeftPos();
		$tip.addClass('assets-tip-top');
	}
	// to the left?
	else if (leftClearance >= minHorizontalClearance) {
		var left = targetOffsetLeft - (hud.width + hudInnerPadding);
		hud.$hud.css('left', left);
		setTopPos();
		$tip.addClass('assets-tip-right');
	}
	// above?
	else if (topClearance >= minVerticalClearance) {
		var top = targetOffsetTop - (hud.height + hudInnerPadding);
		hud.$hud.css('top', top);
		setLeftPos();
		$tip.addClass('assets-tip-bottom');
	}
	// ok, which one comes the closest -- right or bottom?
	else {
		var rightClearanceDiff = minHorizontalClearance - rightClearance,
			bottomCleananceDiff = minVerticalClearance - bottomClearance;

		if (rightClearanceDiff >= bottomCleananceDiff) {
			var left = windowWidth - (hud.width + hudOuterPadding),
				minLeft = targetOffsetLeft + hudInnerPadding;
			if (left < minLeft) left = minLeft;
			hud.$hud.css('left', left);
			setTopPos();
			$tip.addClass('assets-tip-left');
		}
		else {
			var top = windowHeight - (hud.height + hudOuterPadding),
				minTop = targetOffsetTop + hudInnerPadding;
			if (top < minTop) top = minTop;
			hud.$hud.css('top', top);
			setLeftPos();
			$tip.addClass('assets-tip-top');
		}
	}

	// -------------------------------------------
	//  Fade it in
	// -------------------------------------------

	hud.$contents.addClass('assets-loading');

	hud.addContents = function(html) {
		hud.loadingContents = false;
		hud.$contents.removeClass('assets-loading').html(html);
	};

};



// ====================================================================


var integerKeyCodes = [8 /* (delete) */ , 37,38,39,40 /* (arrows) */ , 45,91 /* (minus) */ , 48,49,50,51,52,53,54,55,56,57 /* (0-9) */ ],
	numericKeyCodes = [8 /* (delete) */ , 37,38,39,40 /* (arrows) */ , 45,91 /* (minus) */ , 46,190 /* period */ , 48,49,50,51,52,53,54,55,56,57 /* (0-9) */ ],
	traversingKeyCodes = [8 /* delete */ , 37,38,39,40 /* (arrows) */];


/**
 * Property Text
 */
Assets.Properties.Text = function($tr, $input, settings, onChange) {

	var settings = $.extend({ maxl: '', multiline: false, spaces: true, content: 'any' }, settings),
		isTextarea = ($input[0].nodeName == 'TEXTAREA'),
		val = $input.val()
		clicked = false,
		clickedDirectly = false,
		focussed = false;


	// -------------------------------------------
	//  Keep textarea height updated to match contents
	// -------------------------------------------

	if (isTextarea) {

		// create the stage
		var $stage = $('<stage />').insertAfter($input),
			textHeight;

		// replicate the textarea's text styles
		$stage.css({
			position: 'absolute',
			top: -9999,
			left: -9999,
			width: $input.width(),
			lineHeight: $input.css('lineHeight'),
			fontSize: $input.css('fontSize'),
			fontFamily: $input.css('fontFamily'),
			fontWeight: $input.css('fontWeight'),
			letterSpacing: $input.css('letterSpacing'),
			wordWrap: 'break-word'
		});

		/**
		 * Update Input Height
		 */
		var updateHeight = function() {
			if (! val) {
				var html = '&nbsp;';
			} else {
				// html entities
				var html = val.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/[\n\r]$/g, '<br/>&nbsp;').replace(/[\n\r]/g, '<br/>');
			}

			if (focussed) html += 'm';
			$stage.html(html);

			// has the height changed?
			if ((textHeight !== (textHeight = $stage.height())) && textHeight) {
				// update the textarea height
				$input.height(textHeight);
			}
		};

		updateHeight();
	}

	/**
	 * Check Input Value
	 */
	var checkVal = function(){
		// has the input value changed?
		if (val !== (val = $input.val())) {
			// update the height if this is a textarea
			if (isTextarea) updateHeight();

			// callback?
			if (typeof onChange == 'function') onChange();
		}
	};

	// -------------------------------------------
	//  Focus and Blur
	// -------------------------------------------

	$tr.click(function(){
		clicked = true;

		if (! focussed) $input.focus();
	});

	$input.mousedown(function(){
		clickedDirectly = true;
	});

	/**
	 * Focus
	 */
	$input.focus(function(){
		focussed = true;

		interval = setInterval(checkVal, 1);

		if (isTextarea) {
			setTimeout(function(){
				if (! clickedDirectly) {
					// focus was *given* to the textarea, so we'll do our best
					// to make it seem like the entire $td is a normal text input

					var val = $input.val();

					if ($input[0].setSelectionRange) {
						var length = val.length * 2;

						if (! clicked) {
							// tabbed into, so select the entire value
							$input[0].setSelectionRange(0, length);
						} else {
							// just place the cursor at the end
							$input[0].setSelectionRange(length, length);
						}
					} else {
						// browser doesn't support setSelectionRange so try refreshing
						// the value as a way to place the cursor at the end
						$input.val(val);
					}
				}

				clicked = clickedDirectly = false;
			}, 0);
		}
	});

	/**
	 * Blur
	 */
	$input.blur(function(){
		focussed = false;

		clearInterval(interval);
		checkVal();
	});

	$input.change(checkVal);

	// -------------------------------------------
	//  Input validation
	// -------------------------------------------

	if (! settings.multiline || ! settings.spaces || settings.content != 'any' || settings.maxl) {
		$input.keydown(function(event){
			if (! event.metaKey
				&& $.inArray(event.keyCode, traversingKeyCodes) == -1 && (
				(! settings.multiline && event.keyCode == 13)
				|| (! settings.spaces && event.keyCode == 32)
				|| (settings.content == 'integer' && $.inArray(event.keyCode, integerKeyCodes) == -1)
				|| (settings.content == 'numeric' && $.inArray(event.keyCode, numericKeyCodes) == -1)
				|| (settings.maxl && $input.val().length >= settings.maxl))) {
				event.preventDefault();
			}
		});
	}

};


})(jQuery);
