/**
 * Assets Context Menu
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */


(function($) {


/**
 * Context Menu
 */
Assets.ContextMenu = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function(target, options) {
		this.$target = $(target);
		this.options = options;

		Assets.ContextMenu.counter++;
		this.NS = 'assets-contextmenu'+Assets.ContextMenu.counter;

		this.showing = false;

		this.$target.unbind('.'+this.NS);

		this.enable();
	},

	/**
	 * Build Menu
	 */
	_buildMenu: function() {
		this.$menu = $('<ul class="assets-contextmenu" style="display: none" />');

		for (var i in this.options) {
			var option = this.options[i];

			if (option == '-') {
				$('<li class="hr"></li>').appendTo(this.$menu);
			} else {
				var $li = $('<li></li>').appendTo(this.$menu),
					$a = $('<a>'+option.label+'</a>').appendTo($li);

				if (typeof option.onClick == 'function') {
					// maintain the current $a and options.onClick variables
					(function($a, onClick) {
						setTimeout($.proxy(function(){
							$a.mousedown($.proxy(function(event) {
								this._hideMenu();
								// call the onClick callback, with the scope set to the item,
								// and pass it the event with currentTarget set to the item as well
								onClick.call(this.currentTarget, $.extend(event, { currentTarget: this.currentTarget }));
							}, this));
						}, this), 1);
					}).call(this, $a, option.onClick);
				}
			}
		}
	},

	/**
	 * Show Menu
	 */
	_showMenu: function(event) {
		// ignore left mouse clicks
		if (event.type == 'mousedown' && event.button != 2) return;

		if (event.type == 'contextmenu') {
			// prevent the real context menu from showing
			event.preventDefault();
		}

		// ignore if already showing
		if (this.showing && event.currentTarget == this.currentTarget) return;

		this.currentTarget = event.currentTarget;

		if (! this.$menu) {
			this._buildMenu();
		}

		this.$menu.appendTo(document.body);
		this.$menu.show();
		this.$menu.css({ left: event.pageX+1, top: event.pageY-4 });

		this.showing = true;

		setTimeout($.proxy(function() {
			$(document).bind('mousedown.'+this.NS, $.proxy(this, '_hideMenu'));
		}, this), 0);
	},

	/**
	 * Hide Menu
	 */
	_hideMenu: function() {
		$(document).unbind('.'+this.NS);
		this.$menu.hide();
		this.showing = false;
	},

	/**
	 * Enable
	 */
	enable: function() {
		this.$target.bind('contextmenu.'+this.NS+' mousedown.'+this.NS, $.proxy(this, '_showMenu'));
	},

	/**
	 * Disable
	 */
	disable: function() {
		this.$target.unbind('.'+this.NS);
	}

});

Assets.ContextMenu.counter = 0;


})(jQuery);
