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
Assets.Properties = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function($file) {

		this.$file = $file;
		this.filePath = $file.attr('data-path');

		this.saveEnabled = false;

		// -------------------------------------------
		//  Only one active HUD at a time
		// -------------------------------------------

		// is there already an active props HUD?
		if (Assets.Properties.active) {
			Assets.Properties.active.close();
		}

		// register this one
		Assets.Properties.active = this;

		// -------------------------------------------
		//  Set up the HUD
		// -------------------------------------------

		this.hud = new Assets.HUD(this.$file, 'assets-props');
		this.$cancelBtn = $('<a class="assets-btn">'+Assets.lang.cancel+'</a>').appendTo(this.hud.$buttons);
		this.$saveBtn = $('<a class="assets-btn assets-submit assets-disabled">'+Assets.lang.save_changes+'</a>').appendTo(this.hud.$buttons);

		this.$cancelBtn.click($.proxy(this, 'close'));
		this.$saveBtn.click($.proxy(this, 'submit'));

		// -------------------------------------------
		//  Load the contents
		// -------------------------------------------

		// get the next request ID
		Assets.Properties.requestId++;

		var data = {
			requestId: Assets.Properties.requestId,
			file_path: this.filePath
		};

		// run the ajax post request
		$.post(Assets.actions.get_props, data, $.proxy(this, '_init'), 'json');
	},

	/**
	 * Initialize
	 */
	_init: function(data, textStatus) {
		// ignore if this is a bad response
		if (textStatus != 'success' || data.requestId != Assets.Properties.requestId) return;

		// update the HTML
		this.hud.addContents(data.html);

		// set the filedata height
		var filenameHeight = $('> .assets-filename', this.hud.$contents).outerHeight(),
			buttonsHeight = $('> .assets-btns', this.hud.$hud).outerHeight(),
			filedataHeight = this.hud.height - filenameHeight - buttonsHeight - 20,
			$filedata = $('> .assets-filedata', this.hud.$contents).height(filedataHeight);

		// -------------------------------------------
		//  Get the meta rows
		// -------------------------------------------

		// get the metadata rows
		this.$trs = $('> table > tbody > tr:not(.assets-fileinfo):not(.assets-spacer)', $filedata);

		// mark the odd rows (not sure why we need to use :even here instead of :odd)
		this.$trs.filter(':even').addClass('assets-odd');

		// get all the actual form inputs
		this.$inputs = $('input,textarea,select', this.$trs);

		// treat the entire TR like a <label>
		this.$trs.click($.proxy(this, '_onRowClick'));

		// -------------------------------------------
		//  Enable Save button when any inputs have changed
		// -------------------------------------------

		// get the initial values
		for (var i = 0; i < this.$inputs.length; i++) {
			var input = this.$inputs[i];
			$.data(input, 'initialVal', $(input).val());
		};

		// check their state each time an input has changed
		this.$inputs.bind('keydown keypress change', $.proxy(this, '_onInputChange'));

		// -------------------------------------------
		//  Initialize text fields
		// -------------------------------------------

		var $textareas = $('textarea', this.$trs);

		for (var i = 0; i < $textareas.length; i++) {
			var $textarea = $($textareas[i]);

			new Assets.Properties.Text($textarea);

			// submit on return
			$textarea.keydown($.proxy(this, '_onTextKeydown'));
		};

		// -------------------------------------------
		//  Initialize date fields
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

		var $dates = this.$inputs.filter('[data-type=date]');

		for (var i = 0; i < $dates.length; i++) {
			var $input = $($dates[i]);
			$input.datepicker({
				dateFormat: $.datepicker.W3C + time,
				defaultDate: new Date(parseInt($input.attr('data-default-date')))
			});
		}
	},

	/**
	 * On Row Click
	 */
	_onRowClick: function(event) {
		// ignore if they clicked on an actual input
		if (event.target.nodeName == 'INPUT' || event.target.nodeName == 'TEXTAREA' || event.target.nodeName == 'SELECT') return;

		var $firstInput = $('input,textarea,select', event.currentTarget).first();
		$firstInput.focus();
	},

	/**
	 * On Input Change
	 */
	_onInputChange: function(event) {
		setTimeout($.proxy(this, '_checkAllInputs'), 0);
	},

	/**
	 * Check All Inputs
	 */
	_checkAllInputs: function() {
		this.saveEnabled = false;

		for (var i = 0; i < this.$inputs.length; i++) {
			var input = this.$inputs[i];
			if (this._inputChanged(input)) {
				this.saveEnabled = true;
				break;
			}
		};

		if (this.saveEnabled) {
			this.$saveBtn.removeClass('assets-disabled');
		} else {
			this.$saveBtn.addClass('assets-disabled');
		}
	},

	/**
	 * Input Changed?
	 */
	_inputChanged: function(input) {
		return ($(input).val() != $.data(input, 'initialVal'))
	},

	/**
	 * On Text Keydown
	 */
	_onTextKeydown: function(event) {
		var $textarea = $(event.currentTarget);
		if (event.keyCode == 13 && (! $textarea.attr('data-multiline') || event.altKey)) {
			event.preventDefault();
			setTimeout($.proxy(this, 'submit'), 1);
		}
	},

	/**
	 * Submit
	 */
	submit: function() {
		// ignore if the save button is disabled
		if (! this.saveEnabled) return;

		var saveData = {};

		for (var i = 0; i < this.$inputs.length; i++) {
			var input = this.$inputs[i];
			if (this._inputChanged(input)) {
				saveData['data['+$(input).attr('name')+']'] = $(input).val();
			}
		};

		if (saveData) {
			saveData['data[file_path]'] = this.filePath;

			$.post(Assets.actions.save_props, saveData);

			// close the HUD
			this.close();
		}
	},

	/**
	 * Close
	 */
	close: function() {
		this.hud.$hud.fadeOut('fast');
	}

});

Assets.Properties.requestId = 0;


// ====================================================================


var hudInnerPadding = 10,
	hudOuterPadding = 15

var $window = $(window);


/**
 * Heads-up Display
 */
Assets.HUD = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function($target, hudClass) {

		this.$target = $target;

		this.loadingContents = true;

		this.$hud = $('<div class="assets-hud '+hudClass+'" />').appendTo(document.body);
		this.$tip = $('<div class="assets-tip" />').appendTo(this.$hud);

		this.$contents = $('<div class="assets-contents" />').appendTo(this.$hud);
		this.$buttons = $('<div class="assets-btns" />').appendTo(this.$hud);

		// -------------------------------------------
		//  Get all relevant dimensions, lengths, etc
		// -------------------------------------------

		this.windowWidth = $window.width();
		this.windowHeight = $window.height();

		this.windowScrollLeft = $window.scrollLeft();
		this.windowScrollTop = $window.scrollTop();

			// get the target element's dimensions
		this.targetWidth = this.$target.width();
		this.targetHeight = this.$target.height();

		// get the offsets for each side of the target element
		this.targetOffset = this.$target.offset();
		this.targetOffsetRight = this.targetOffset.left + this.targetWidth;
		this.targetOffsetBottom = this.targetOffset.top + this.targetHeight;
		this.targetOffsetLeft = this.targetOffset.left;
		this.targetOffsetTop = this.targetOffset.top;

		// get the HUD dimensions
		this.width = this.$hud.width();
		this.height = this.$hud.height();

		// get the minumum horizontal/vertical clearance needed to fit the HUD
		this.minHorizontalClearance = this.width + hudInnerPadding + hudOuterPadding;
		this.minVerticalClearance = this.height + hudInnerPadding + hudOuterPadding;

		// find the actual available right/bottom/left/top clearances
		this.rightClearance = this.windowWidth + this.windowScrollLeft - this.targetOffsetRight;
		this.bottomClearance = this.windowHeight + this.windowScrollTop - this.targetOffsetBottom;
		this.leftClearance = this.targetOffsetLeft - this.windowScrollLeft;
		this.topClearance = this.targetOffsetTop - this.windowScrollTop;

		// -------------------------------------------
		//  Where are we putting it?
		//   - Ideally, we'll be able to find a place to put this where it's not overlapping the target at all.
		//     If we can't find that, either put it to the right or below the target, depending on which has the most room.
		// -------------------------------------------

		// to the right?
		if (this.rightClearance >= this.minHorizontalClearance) {
			var left = this.targetOffsetRight + hudInnerPadding;
			this.$hud.css('left', left);
			this._setTopPos();
			this.$tip.addClass('assets-tip-left');
		}
		// below?
		else if (this.bottomClearance >= this.minVerticalClearance) {
			var top = this.targetOffsetBottom + hudInnerPadding;
			this.$hud.css('top', top);
			this._setLeftPos();
			this.$tip.addClass('assets-tip-top');
		}
		// to the left?
		else if (this.leftClearance >= this.minHorizontalClearance) {
			var left = this.targetOffsetLeft - (this.width + hudInnerPadding);
			this.$hud.css('left', left);
			this._setTopPos();
			this.$tip.addClass('assets-tip-right');
		}
		// above?
		else if (this.topClearance >= this.minVerticalClearance) {
			var top = this.targetOffsetTop - (this.height + hudInnerPadding);
			this.$hud.css('top', top);
			this._setLeftPos();
			this.$tip.addClass('assets-tip-bottom');
		}
		// ok, which one comes the closest -- right or bottom?
		else {
			var rightClearanceDiff = this.minHorizontalClearance - this.rightClearance,
				bottomCleananceDiff = this.minVerticalClearance - this.bottomClearance;

			if (rightClearanceDiff >= bottomCleananceDiff) {
				var left = this.windowWidth - (this.width + hudOuterPadding),
					minLeft = this.targetOffsetLeft + hudInnerPadding;
				if (left < minLeft) left = minLeft;
				this.$hud.css('left', left);
				this._setTopPos();
				this.$tip.addClass('assets-tip-left');
			}
			else {
				var top = this.windowHeight - (this.height + hudOuterPadding),
					minTop = this.targetOffsetTop + hudInnerPadding;
				if (top < minTop) top = minTop;
				this.$hud.css('top', top);
				this._setLeftPos();
				this.$tip.addClass('assets-tip-top');
			}
		}

		// -------------------------------------------
		//  Fade it in
		// -------------------------------------------

		this.$contents.addClass('assets-loading');

		this.addContents = function(html) {
			this.loadingContents = false;
			this.$contents.removeClass('assets-loading').html(html);
		};
	},

	/**
	 * Set Top
	 */
	_setTopPos: function() {
		var maxTop = (this.windowHeight + this.windowScrollTop) - (this.height + hudOuterPadding),
			minTop = (this.windowScrollTop + hudOuterPadding),

			targetCenter = this.targetOffsetTop + Math.round(this.targetHeight / 2),
			top = targetCenter - Math.round(this.height / 2);

		// adjust top position as needed
		if (top > maxTop) top = maxTop;
		if (top < minTop) top = minTop;

		this.$hud.css('top', top);

		// set the tip's top position
		var tipTop = (targetCenter - top) - 15;
		this.$tip.css('top', tipTop);
	},

	/**
	 * Set Left
	 */
	_setLeftPos: function() {
		var maxLeft = (this.windowWidth + this.windowScrollLeft) - (this.width + hudOuterPadding),
			minLeft = (this.windowScrollLeft + hudOuterPadding),

			targetCenter = this.targetOffsetLeft + Math.round(this.targetWidth / 2),
			left = targetCenter - Math.round(this.width / 2);

		// adjust left position as needed
		if (left > maxLeft) left = maxLeft;
		if (left < minLeft) left = minLeft;

		this.$hud.css('left', left);

		// set the tip's left position
		var tipLeft = (targetCenter - left) - 15;
		this.$tip.css('left', tipLeft);
	}

});



// ====================================================================


var traversingKeyCodes = [8 /* delete */ , 37,38,39,40 /* (arrows) */];


/**
 * Property Text
 */
Assets.Properties.Text = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function($input) {

		this.$input = $input;

		// ignore if not a textarea
		if (this.$input[0].nodeName != 'TEXTAREA') return;

		this.settings = {
			maxl:      (parseInt(this.$input.attr('data-maxl')) || false),
			multiline: (!! this.$input.attr('data-multiline') || false)
		};

		this.val = this.$input.val();
		this.clicked = false,
		this.focussed = false;

		// -------------------------------------------
		//  Keep textarea height updated to match contents
		// -------------------------------------------

		// create the stage
		this.$stage = $('<stage />').insertAfter(this.$input);
		this.textHeight;

		// replicate the textarea's text styles
		this.$stage.css({
			position: 'absolute',
			top: -9999,
			left: -9999,
			width: this.$input.width(),
			lineHeight: this.$input.css('lineHeight'),
			fontSize: this.$input.css('fontSize'),
			fontFamily: this.$input.css('fontFamily'),
			fontWeight: this.$input.css('fontWeight'),
			letterSpacing: this.$input.css('letterSpacing'),
			wordWrap: 'break-word'
		});

		this._updateInputHeight();

		// -------------------------------------------
		//  Bind events
		// -------------------------------------------

		this.$input.mousedown($.proxy(this, '_onInputMousedown'));
		this.$input.focus($.proxy(this, '_onInputFocus'));
		this.$input.blur($.proxy(this, '_onInputBlur'));
		this.$input.change($.proxy(this, '_checkInputVal'));

		if (! this.settings.multiline || this.settings.maxl) {
			this.$input.keydown($.proxy(this, '_onInputKeydown'));
		}
	},

	/**
	 * On Input Mousedown
	 */
	_onInputMousedown: function(){
		this.clicked = true;
	},

	/**
	 * On Input Focus
	 */
	_onInputFocus: function(){
		this.focussed = true;

		// make the textarea behave like a text input
		setTimeout($.proxy(this, '_fakeTextInputOnFocus'), 0);

		// start checking the input value
		this.interval = setInterval($.proxy(this, '_checkInputVal'), 100);
	},

	/**
	 * Fake Text Input On Focus
	 */
	_fakeTextInputOnFocus: function(){
		if (! this.clicked) {
			// focus was *given* to the textarea, so we'll do our best
			// to make it seem like the entire $td is a normal text input

			this.val = this.$input.val();

			if (this.$input[0].setSelectionRange) {
				var length = this.val.length * 2;
				this.$input[0].setSelectionRange(0, length);
			} else {
				// browser doesn't support setSelectionRange so try refreshing
				// the value as a way to place the cursor at the end
				this.$input.val(this.val);
			}
		} else {
			this.clicked = false;
		}
	},

	/**
	 * On Input Blur
	 */
	_onInputBlur: function(){
		this.focussed = false;

		clearInterval(this.interval);
		this._checkInputVal();
	},

	/**
	 * On Input Keydown
	 */
	_onInputKeydown: function(event){
		if (! event.metaKey && ! event.ctrlKey
				&& $.inArray(event.keyCode, traversingKeyCodes) == -1 && (
				(! this.settings.multiline && event.keyCode == 13)
				|| (this.settings.maxl && this.$input.val().length >= this.settings.maxl))) {
			event.preventDefault();
		}
	},

	/**
	 * Check Input Value
	 */
	_checkInputVal: function(){
		// has the input value changed?
		if (this.val !== (this.val = this.$input.val())) {
			this._updateInputHeight();
		}
	},

	/**
	 * Update Input Height
	 */
	_updateInputHeight: function() {
		if (! this.val) {
			var html = '&nbsp;';
		} else {
			// html entities
			var html = this.val.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/[\n\r]$/g, '<br/>&nbsp;').replace(/[\n\r]/g, '<br/>');
		}

		if (this.focussed) html += 'm';
		this.$stage.html(html);

		// has the height changed?
		if ((this.textHeight !== (this.textHeight = this.$stage.height())) && this.textHeight) {
			// update the textarea height
			this.$input.height(this.textHeight);
		}
	}

});


})(jQuery);
