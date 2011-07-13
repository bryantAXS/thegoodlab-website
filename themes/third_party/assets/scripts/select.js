/**
 * Assets Select
 *
 * Makes a set of items (multi-)selectable
 *
 * Accepted Settings:
 *
 *  • multi:             whether the user can select multiple items
 *  • multiDblClick:     whether the user should be able to double-click a set of multiple selected items
 *  • onSelectionChange: a function to be called when the selection has changed
 *
 * Public Methods:
 *
 *  • addItems( item ): makes additional items selectable
 *  • removeItems( item ): makes an item unselectable
 *  • getSelectedItems(): returns all currently selected items
 *  • destroy(): removes all event listeners created by this Select instance
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
Assets.Select = function($container, settings) {

	var obj = this;

	obj.settings = (settings || {});

	var $items = $(),
		$scrollpane = $('.'+scrollpaneClass+':first', $container),
		mouseUpTimeout,
		mouseUpTimeoutDuration = (obj.settings.multiDblClick ? 300 : 0),
		callbackTimeout,

		totalSelected = 0,

		$first = null,
		$last = null,
		first = null,
		last = null,

		ignoreClick;

	// --------------------------------------------------------------------

	/**
	 * Get Item Index
	 */
	obj.getItemIndex = function($item) {
		return $items.index($item[0]);
	};

	/**
	 * Is Selected?
	 */
	obj.isSelected = function($item) {
		return $item.hasClass(selectedClass);
	};

	/**
	 * Select Item
	 */
	obj.selectItem = function($item) {
		if (! obj.settings.multi) {
			obj.deselectAll();
		}

		$item.addClass(selectedClass);

		$first = $last = $item;
		first = last = obj.getItemIndex($item);

		totalSelected++;

		obj.setCallbackTimeout();
	};

	/**
	 * Select Range
	 */
	obj.selectRange = function($item) {
		if (! obj.settings.multi) {
			return obj.selectItem($item);
		}

		obj.deselectAll();

		$last = $item;
		last = obj.getItemIndex($item);

		// prepare params for $.slice()
		if (first < last) {
			var sliceFrom = first,
				sliceTo = last + 1;
		} else { 
			var sliceFrom = last,
				sliceTo = first + 1;
		}

		$items.slice(sliceFrom, sliceTo).addClass(selectedClass);

		totalSelected = sliceTo - sliceFrom;

		obj.setCallbackTimeout();
	};

	/**
	 * Deselect Item
	 */
	obj.deselectItem = function($item) {
		$item.removeClass(selectedClass);

		var index = obj.getItemIndex($item);
		if (first === index) $first = first = null;
		if (last === index) $last = last = null;

		totalSelected--;

		obj.setCallbackTimeout();
	};

	/**
	 * Deselect All
	 */
	obj.deselectAll = function(clearFirst) {
		$items.removeClass(selectedClass);

		if (clearFirst) {
			$first = first = $last = last = null;
		}

		totalSelected = 0;

		obj.setCallbackTimeout();
	};

	/**
	 * Deselect Others
	 */
	obj.deselectOthers = function($item) {
		obj.deselectAll();
		obj.selectItem($item);
	};

	/**
	 * Toggle Item
	 */
	obj.toggleItem = function($item) {
		if (! obj.isSelected($item)) {
			obj.selectItem($item);
		} else {
			obj.deselectItem($item);
		}
	}

	// --------------------------------------------------------------------

	obj.clearMouseUpTimeout = function() {
		clearTimeout(mouseUpTimeout);
	};

	var mousedown_x, mousedown_y;

	/**
	 * On Mouse Down
	 */
	var onMouseDown = function(event) {
		mousedown_x = event.pageX;
		mousedown_y = event.pageY;

		var $item = $(this);

		if (event.metaKey) {
			obj.toggleItem($item);
		}
		else if (first !== null && event.shiftKey) {
			obj.selectRange($item);
		}
		else if (! obj.isSelected($item)) {
			obj.deselectAll();
			obj.selectItem($item);
		}

		$container.focus();
	};

	/**
	 * On Mouse Up
	 */
	var onMouseUp = function(event) {
		var $item = $(this);

		// was this a click?
		if (! event.metaKey && ! event.shiftKey && getDist(mousedown_x, mousedown_y, event.pageX, event.pageY) < 1) {
			obj.selectItem($item);

			// wait a moment before deselecting others
			// to give the user a chance to double-click
			obj.clearMouseUpTimeout()
			mouseUpTimeout = setTimeout(function() {
				obj.deselectOthers($item);
			}, mouseUpTimeoutDuration);
		}
	};

	// --------------------------------------------------------------------

	var clickedInto = null;

	$container.bind('click.'+NS, function(event) {
		if (ignoreClick) {
			ignoreClick = false;
		} else {
			// deselect all items on container click
			obj.deselectAll(true);
		}
	});

	// --------------------------------------------------------------------

	$container.attr('tabindex', '0');

	$container.bind('mousedown.'+NS, function() {
		// since they're using the mouse, no need to show the outline
		$container.addClass('assets-no-outline');
	});

	$container.bind('blur.'+NS, function() {
		$container.removeClass('assets-no-outline');
	});

	// --------------------------------------------------------------------

	/**
	 * On Key Down
	 */
	$container.bind('keydown.'+NS, function(event) {
		// ignore if meta key is down
		if (event.metaKey) return;

		// ignore if this pane doesn't have focus
		if (event.target != $container[0]) return;

		// ignore if there are no items
		if (! $items.length) return;

		var anchor = event.shiftKey ? last : first;

		switch (event.keyCode) {
			case 40: // Down
				event.preventDefault();

				if (first === null) {
					// select the first item
					$item = $($items[0]);
				}
				else if ($items.length >= anchor + 2) {
					// select the item after the last selected item
					$item = $($items[anchor+1]);
				}

				break;

			case 38: // up
				event.preventDefault();

				if (first === null) {
					// select the last item
					$item = $($items[$items.length-1]);
				}
				else if (anchor > 0) {
					$item = $($items[anchor-1]);
				}

				break;

			case 27: // esc
				obj.deselectAll(true);

			default: return;
		};

		if (! $item || ! $item.length) return;

		// -------------------------------------------
		//  Scroll to the item
		// -------------------------------------------

		var scrollTop = $scrollpane.scrollTop(),
			itemOffset = $item.offset().top,
			scrollpaneOffset = $scrollpane.offset().top,
			offsetDiff = itemOffset - scrollpaneOffset;

		if (offsetDiff < 0) {
			$scrollpane.scrollTop(scrollTop + offsetDiff);
		}
		else {
			var itemHeight = $item.outerHeight(),
				scrollpaneHeight = $scrollpane[0].clientHeight;

			if (offsetDiff + itemHeight > scrollpaneHeight) {
				$scrollpane.scrollTop(scrollTop + (offsetDiff - (scrollpaneHeight - itemHeight)));
			}
		}

		// -------------------------------------------
		//  Select the item
		// -------------------------------------------

		if (first !== null && event.shiftKey) {
			obj.selectRange($item);
		}
		else {
			obj.deselectAll();
			obj.selectItem($item);
		}
	});

	// --------------------------------------------------------------------

	/**
	 * Get Total Selected
	 */
	obj.getTotalSelected = function() {
		return totalSelected;
	};

	/**
	 * Add Items
	 */
	obj.addItems = function($_items) {
		// make a record of it
		$items = $items.add($_items);

		// bind listeners
		$_items.bind('mousedown.'+NS, onMouseDown);
		$_items.bind('mouseup.'+NS, onMouseUp);

		$_items.bind('click.'+NS, function(event) {
			ignoreClick = true;
		});

		totalSelected += $_items.filter('.'+selectedClass).length;

		obj.updateIndexes();
	};

	/**
	 * Remove Items
	 */
	obj.removeItems = function($_items) {
		$_items.each(function() {
			var $item = $(this);

			// deselect it first
			if (obj.isSelected($item)) {
				obj.deselectItem($item);
			}
		});

		// unbind all events
		$_items.unbind('.'+NS);

		// remove the record of it
		$items = $items.not($_items);

		obj.updateIndexes();
	};

	/**
	 * Reset
	 */
	obj.reset = function() {
		// unbind the events
		$items.unbind('.'+NS);

		// reset local vars
		$items = $();
		totalSelected = 0;
		$first = first = $last = last = null;

		// clear timeout
		obj.clearCallbackTimeout();
	};

	/**
	 * Destroy
	 */
	obj.destroy = function() {
		// unbind events
		$container.unbind('.'+NS);
		$items.unbind('.'+NS);

		// clear timeout
		obj.clearCallbackTimeout();

		// delete this object
		delete obj;
	};

	/**
	 * Update First/Last indexes
	 */
	obj.updateIndexes = function() {
		if (first !== null) {
			first = obj.getItemIndex($first);
			last = obj.getItemIndex($last);
		}
	};

	// --------------------------------------------------------------------

	/**
	 * Clear Callback Timeout
	 */
	obj.clearCallbackTimeout = function() {
		if (obj.settings.onSelectionChange) {
			clearTimeout(callbackTimeout);
		}
	};

	/**
	 * Set Callback Timeout
	 */
	obj.setCallbackTimeout = function() {
		if (obj.settings.onSelectionChange) {
			// clear the last one
			obj.clearCallbackTimeout();

			callbackTimeout = setTimeout(function() {
				callbackTimeout = null;
				obj.settings.onSelectionChange.call();
			}, 300);
		}
	};

	/**
	 * Get Selected Items
	 */
	obj.getSelectedItems = function() {
		return $items.filter('.'+selectedClass);
	};

};


})(jQuery);
