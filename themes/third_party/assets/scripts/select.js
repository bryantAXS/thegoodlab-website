/**
 * Assets Select
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */


(function($) {


// define the namespace
var NS = 'assets-select';

// define namespace-based class names
var scrollpaneClass = 'assets-scrollpane',
	selectedClass = 'assets-selected';


/**
 * Get distance between two coordinates
 */
var getDist = function(x1, y1, x2, y2) {
	return Math.sqrt(Math.pow(x1-x2, 2) + Math.pow(y1-y2, 2));
};


/**
 * Select
 */
Assets.Select = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function($container, settings) {

		this._$container = $container;
		this._settings = (settings || {});

		this._$items = $();
		this._$scrollpane = $('.'+scrollpaneClass+':first', this._$container);
		this._mouseUpTimeout;
		this._mouseUpTimeoutDuration = (this._settings.multiDblClick ? 300 : 0);
		this._callbackTimeout;

		this._totalSelected = 0;

		this._$first = null;
		this._$last = null;
		this._first = null;
		this._last = null;

		this._ignoreClick;

		this._mousedownX;
		this._mousedownY;

		// --------------------------------------------------------------------

		this._$container.bind('click.'+NS, $.proxy(function(event) {
			if (this._ignoreClick) {
				this._ignoreClick = false;
			} else {
				// deselect all items on container click
				this.deselectAll(true);
			}
		}, this));

		// --------------------------------------------------------------------

		this._$container.attr('tabindex', '0');

		this._$container.bind('mousedown.'+NS, $.proxy(function() {
			// since they're using the mouse, no need to show the outline
			this._$container.addClass('assets-no-outline');
		}, this));

		this._$container.bind('blur.'+NS, $.proxy(function() {
			this._$container.removeClass('assets-no-outline');
		}, this));

		this._$container.bind('keydown.'+NS, $.proxy(this, '_onKeyDown'));

	},

	// --------------------------------------------------------------------

	/**
	 * Get Item Index
	 */
	getItemIndex: function($item) {
		return this._$items.index($item[0]);
	},

	/**
	 * Is Selected?
	 */
	isSelected: function($item) {
		return $item.hasClass(selectedClass);
	},

	/**
	 * Select Item
	 */
	selectItem: function($item) {
		if (! this._settings.multi) {
			this.deselectAll();
		}

		$item.addClass(selectedClass);

		this._$first = this._$last = $item;
		this._first = this._last = this.getItemIndex($item);

		this._totalSelected++;

		this.setCallbackTimeout();
	},

	/**
	 * Select Range
	 */
	selectRange: function($item) {
		if (! this._settings.multi) {
			return this.selectItem($item);
		}

		this.deselectAll();

		this._$last = $item;
		this._last = this.getItemIndex($item);

		// prepare params for $.slice()
		if (this._first < this._last) {
			var sliceFrom = this._first,
				sliceTo = this._last + 1;
		} else { 
			var sliceFrom = this._last,
				sliceTo = this._first + 1;
		}

		this._$items.slice(sliceFrom, sliceTo).addClass(selectedClass);

		this._totalSelected = sliceTo - sliceFrom;

		this.setCallbackTimeout();
	},

	/**
	 * Deselect Item
	 */
	deselectItem: function($item) {
		$item.removeClass(selectedClass);

		var index = this.getItemIndex($item);
		if (this._first === index) this._$first = this._first = null;
		if (this._last === index) this._$last = this._last = null;

		this._totalSelected--;

		this.setCallbackTimeout();
	},

	/**
	 * Deselect All
	 */
	deselectAll: function(clearFirst) {
		this._$items.removeClass(selectedClass);

		if (clearFirst) {
			this._$first = this._first = this._$last = this._last = null;
		}

		this._totalSelected = 0;

		this.setCallbackTimeout();
	},

	/**
	 * Deselect Others
	 */
	deselectOthers: function($item) {
		this.deselectAll();
		this.selectItem($item);
	},

	/**
	 * Toggle Item
	 */
	toggleItem: function($item) {
		if (! this.isSelected($item)) {
			this.selectItem($item);
		} else {
			this.deselectItem($item);
		}
	},

	// --------------------------------------------------------------------

	clearMouseUpTimeout: function() {
		clearTimeout(this._mouseUpTimeout);
	},

	/**
	 * On Mouse Down
	 */
	_onMouseDown: function(event) {
		// ignore right clicks
		if (event.button == 2) return;

		this._mousedownX = event.pageX;
		this._mousedownY = event.pageY;

		var $item = $(event.currentTarget);

		if (event.metaKey || event.ctrlKey) {
			this.toggleItem($item);
		}
		else if (this._first !== null && event.shiftKey) {
			this.selectRange($item);
		}
		else if (! this.isSelected($item)) {
			this.deselectAll();
			this.selectItem($item);
		}

		this._$container.focus();
	},

	/**
	 * On Mouse Up
	 */
	_onMouseUp: function(event) {
		// ignore right clicks
		if (event.button == 2) return;

		var $item = $(event.currentTarget);

		// was this a click?
		if (! event.metaKey && ! event.ctrlKey && ! event.shiftKey && getDist(this._mousedownX, this._mousedownY, event.pageX, event.pageY) < 1) {
			this.selectItem($item);

			// wait a moment before deselecting others
			// to give the user a chance to double-click
			this.clearMouseUpTimeout()
			this._mouseUpTimeout = setTimeout($.proxy(function() {
				this.deselectOthers($item);
			}, this), this._mouseUpTimeoutDuration);
		}
	},

	// --------------------------------------------------------------------

	/**
	 * On Key Down
	 */
	_onKeyDown: function(event) {
		// ignore if meta/ctrl key is down
		if (event.metaKey || event.ctrlKey) return;

		// ignore if this pane doesn't have focus
		if (event.target != this._$container[0]) return;

		// ignore if there are no items
		if (! this._$items.length) return;

		var anchor = event.shiftKey ? this._last : this._first;

		switch (event.keyCode) {
			case 40: // Down
				event.preventDefault();

				if (this._first === null) {
					// select the first item
					$item = $(this._$items[0]);
				}
				else if (this._$items.length >= anchor + 2) {
					// select the item after the last selected item
					$item = $(this._$items[anchor+1]);
				}

				break;

			case 38: // up
				event.preventDefault();

				if (this._first === null) {
					// select the last item
					$item = $(this._$items[this._$items.length-1]);
				}
				else if (anchor > 0) {
					$item = $(this._$items[anchor-1]);
				}

				break;

			case 27: // esc
				this.deselectAll(true);

			default: return;
		};

		if (! $item || ! $item.length) return;

		// -------------------------------------------
		//  Scroll to the item
		// -------------------------------------------

		Assets.scrollContainerToElement(this._$scrollpane, $item);

		// -------------------------------------------
		//  Select the item
		// -------------------------------------------

		if (this._first !== null && event.shiftKey) {
			this.selectRange($item);
		}
		else {
			this.deselectAll();
			this.selectItem($item);
		}
	},

	// --------------------------------------------------------------------

	/**
	 * Get Total Selected
	 */
	getTotalSelected: function() {
		return this._totalSelected;
	},

	/**
	 * Add Items
	 */
	addItems: function($items) {
		// make a record of it
		this._$items = this._$items.add($items);

		// bind listeners
		$items.bind('mousedown.'+NS, $.proxy(this, '_onMouseDown'));
		$items.bind('mouseup.'+NS, $.proxy(this, '_onMouseUp'));

		$items.bind('click.'+NS, $.proxy(function(event) {
			this._ignoreClick = true;
		}, this));

		this._totalSelected += $items.filter('.'+selectedClass).length;

		this.updateIndexes();
	},

	/**
	 * Remove Items
	 */
	removeItems: function($items) {
		for (var i = 0; i < $items.length; i++) {
			var $item = $($items[i]);

			// deselect it first
			if (this.isSelected($item)) {
				this.deselectItem($item);
			}
		};

		// unbind all events
		$items.unbind('.'+NS);

		// remove the record of it
		this._$items = this._$items.not($items);

		this.updateIndexes();
	},

	/**
	 * Reset
	 */
	reset: function() {
		// unbind the events
		this._$items.unbind('.'+NS);

		// reset local vars
		this._$items = $();
		this._totalSelected = 0;
		this._$first = this._first = this._$last = this._last = null;

		// clear timeout
		this.clearCallbackTimeout();
	},

	/**
	 * Destroy
	 */
	destroy: function() {
		// unbind events
		this._$container.unbind('.'+NS);
		this._$items.unbind('.'+NS);

		// clear timeout
		this.clearCallbackTimeout();
	},

	/**
	 * Update First/Last indexes
	 */
	updateIndexes: function() {
		if (this._first !== null) {
			this._first = this.getItemIndex(this._$first);
			this._last = this.getItemIndex(this._$last);
		}
	},

	// --------------------------------------------------------------------

	/**
	 * Clear Callback Timeout
	 */
	clearCallbackTimeout: function() {
		if (this._settings.onSelectionChange) {
			clearTimeout(this._callbackTimeout);
		}
	},

	/**
	 * Set Callback Timeout
	 */
	setCallbackTimeout: function() {
		if (this._settings.onSelectionChange) {
			// clear the last one
			this.clearCallbackTimeout();

			this._callbackTimeout = setTimeout($.proxy(function() {
				this._callbackTimeout = null;
				this._settings.onSelectionChange.call();
			}, this), 300);
		}
	},

	/**
	 * Get Selected Items
	 */
	getSelectedItems: function() {
		return this._$items.filter('.'+selectedClass);
	}

});


})(jQuery);
