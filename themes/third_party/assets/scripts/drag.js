/**
 * Assets Drag
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */


(function($) {


var $window = $(window),
	$document = $(document);

var NS = 'assets-drag';


/**
 * Drag
 */
Assets.Drag = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function(settings) {

		this.settings = settings;

		this.$items = $();

		this.mousedownX;
		this.mousedownY;
		this.targetMouseDiffX;
		this.targetMouseDiffY;
		this.mouseX;
		this.mouseY;
		this.lastMouseX;
		this.lastMouseY;

		this.dragging = false;
		this.target;
		this.$draggees;
		this.otherItems;

		this.helpers;
		this.helperTargets;
		this.helperPositions;
		this.helperLagIncrement;
		this.updateHelperPosInterval;

		this.activeDropTarget;
	},

	/**
	 * On Mouse Down
	 */
	_onMouseDown: function(event) {
		// ignore if we already have a target
		if (this.target) return;

		event.preventDefault();

		// capture the target
		this.target = event.currentTarget;

		// capture the current mouse position
		this.mousedownX = this.mouseX = event.pageX;
		this.mousedownY = this.mouseY = event.pageY;

		// capture the difference between the mouse position and the target item's offset
		var offset = $(this.target).offset();
		this.targetMouseDiffX = event.pageX - offset.left;
		this.targetMouseDiffY = event.pageY - offset.top;

		// listen for mousemove, mouseup
		$document.bind('mousemove.'+NS, $.proxy(this, '_onMouseMove'));
		$document.bind('mouseup.'+NS, $.proxy(this, '_onMouseUp'));
	},

	/**
	 * On Mouse Move
	 */
	_onMouseMove: function(event) {
		event.preventDefault();

		if (! this.settings.vertical) {
			this.mouseX = event.pageX;
		}

		this.mouseY = event.pageY;

		if (! this.dragging) {
			// has the mouse moved far enough to initiate dragging yet?
			var mouseDist = Assets.getDist(this.mousedownX, this.mousedownY, this.mouseX, this.mouseY);

			if (mouseDist >= 1) {
				this._startDragging();
			} else {
				// we're done here
				return;
			}
		}

		// -------------------------------------------
		//  Drop Targets
		// -------------------------------------------

		if (this.dropTargets) {
			var _activeDropTarget;

			// is the cursor over any of the drop target?
			for (var i = 0; i < this.dropTargets.length; i++) {
				var elem = this.dropTargets[i];

				if (Assets.isCursorOver(event, elem)) {
					_activeDropTarget = elem;
					break;
				}
			}

			// has the drop target changed?
			if (_activeDropTarget != this.activeDropTarget) {
				// was there a previous one?
				if (this.activeDropTarget) {
					this.activeDropTarget.removeClass(this.settings.activeDropTargetClass);
				}

				// remember the new drop target
				this.activeDropTarget = _activeDropTarget;

				// is there a new one?
				if (this.activeDropTarget) {
					this.activeDropTarget.addClass(this.settings.activeDropTargetClass);
				}

				// -------------------------------------------
				//  onDropTargetChange callback
				//
					if (typeof this.settings.onDropTargetChange == 'function') {
						this.settings.onDropTargetChange(this.activeDropTarget);
					}
				//
				// -------------------------------------------
			}
		}

		// -------------------------------------------
		//  onMouseMove callback
		//
			if (typeof this.settings.onMouseMove == 'function') {
				this.settings.onMouseMove(event);
			}
		//
		// -------------------------------------------
	},

	/**
	 * On Moues Up
	 */
	_onMouseUp: function(event) {
		// unbind the document events
		$document.unbind('.'+NS);

		if (this.dragging) {
			this._stopDragging();
		}

		this.target = null;
	},

	/**
	 * Start Dragging
	 */
	_startDragging: function() {
		this.dragging = true;

		this.helpers = [];
		this.helperTargets = [];
		this.helperPositions = [];

		// get the draggees, based on the filter setting
		switch (typeof this.settings.filter) {
			case 'function':
				this.$draggees = this.settings.filter();
				break;

			case 'string':
				this.$draggees = this.$items.filter(this.settings.filter);
				break;

			default:
				this.$draggees = $(this.target);
		}

		// put the target item in the front of the list
		this.$draggees = $([ this.target ].concat(this.$draggees.not(this.target).toArray()));

		this.draggeeDisplay = this.$draggees.css('display');

		for (var i = 0; i < this.$draggees.length; i++) {
			// create the helper
			var $draggee = $(this.$draggees[i]),
				draggeeOffset = $draggee.offset(),
				$helper = this.settings.helper($draggee.clone()).appendTo(document.body);

			$helper.css({
				position: 'absolute',
				top: draggeeOffset.top,
				left: draggeeOffset.left,
				zIndex: 1000 + this.$draggees.length - i,
				opacity: (typeof this.settings.helperOpacity != 'undefined' ? this.settings.helperOpacity : 1)
			});

			this.helperPositions[i] = {
				top:  draggeeOffset.top,
				left: draggeeOffset.left
			};

			this.helpers.push($helper);

			if (this.settings.draggeePlaceholders) {
				$draggee.css('visibility', 'hidden')
			} else {
				$draggee.hide();
			}
		};

		this.lastMouseX = this.lastMouseY = null;

		this.helperLagIncrement = this.helpers.length == 1 ? 0 : 1.5 / (this.helpers.length-1);
		this.updateHelperPosInterval = setInterval($.proxy(this, '_updateHelperPos'), 20);

		// -------------------------------------------
		//  Deal with the remaining items
		// -------------------------------------------

		// create an array of all the other items
		this.otherItems = [];

		for (var i = 0; i < this.$items.length; i++) {
			var item = this.$items[i];

			if ($.inArray(item, this.$draggees) == -1) {
				this.otherItems.push(item);
			}
		};

		// -------------------------------------------
		//  Drop Targets
		// -------------------------------------------

		if (this.settings.dropTargets) {
			if (typeof this.settings.dropTargets == 'function') {
				this.dropTargets = this.settings.dropTargets();
			} else {
				this.dropTargets = this.settings.dropTargets;
			}

			// ignore if an empty array
			if (! this.dropTargets.length) {
				this.dropTargets = false;
			}
		} else {
			this.dropTargets = false;
		}

		this.activeDropTarget = null;

		// -------------------------------------------
		//  onDragStart callback
		//
			if (typeof this.settings.onDragStart == 'function') {
				this.settings.onDragStart();
			}
		//
		// -------------------------------------------
	},

	/**
	 * Stop Dragging
	 */
	_stopDragging: function() {
		this.dragging = false;

		// clear the helper interval
		clearInterval(this.updateHelperPosInterval);

		// -------------------------------------------
		//  Drop Targets
		// -------------------------------------------

		if (this.settings.dropTargets && this.activeDropTarget) {
			this.activeDropTarget.removeClass(this.settings.activeDropTargetClass);
		}

		// -------------------------------------------
		//  onDragStop callback
		//
			if (typeof this.settings.onDragStop == 'function') {
				this.settings.onDragStop();
			}
		//
		// -------------------------------------------
	},

	/**
	 * Update Helper Position
	 */
	_updateHelperPos: function() {
		// has the mouse moved?
		if (this.mouseX !== this.lastMouseX || this.mouseY !== this.lastMouseY) {
			this.lastMouseX = this.mouseX;
			this.lastMouseY = this.mouseY;

			// figure out what each of the helpers' target positions are
			// (they will gravitate toward their targets in _updateHelperPos())
			for (var i = 0; i < this.helpers.length; i++) {
				this.helperTargets[i] = {
					left: this.mouseX - this.targetMouseDiffX + (i * 5),
					top:  this.mouseY - this.targetMouseDiffY + (i * 5)
				};
			};
		}

		for (var i = 0; i < this.helpers.length; i++) {
			var lag = 1 + (this.helperLagIncrement * i);

			this.helperPositions[i] = {
				left: this.helperPositions[i].left + ((this.helperTargets[i].left - this.helperPositions[i].left) / lag),
				top:  this.helperPositions[i].top  + ((this.helperTargets[i].top  - this.helperPositions[i].top) / lag)
			};

			this.helpers[i].css(this.helperPositions[i]);
		};
	},

	/**
	 * Return Helpers to Draggees
	 */
	returnHelpersToDraggees: function() {
		for (var i = 0; i < this.$draggees.length; i++) {
			var $draggee = $(this.$draggees[i]),
				$helper = this.helpers[i],
				draggeeOffset = $draggee.offset();

			// preserve $draggee and $helper for the end of the animation
			(function($draggee, $helper) {
				$helper.animate({
					left: draggeeOffset.left,
					top: draggeeOffset.top
				}, 'fast', function() {
					$draggee.css('visibility', 'visible');
					$helper.remove();
				});
			})($draggee, $helper);
		};
	},

	/**
	 * Fade Out Helpers
	 */
	fadeOutHelpers: function() {
		for (var i = 0; i < this.helpers.length; i++) {
			(function($helper) {
				$helper.fadeOut('fast', function() {
					$helper.remove();
				});
			})(this.helpers[i]);
		};
	},

	/**
	 * Add Items
	 */
	addItems: function($_items) {
		// make a record of it
		this.$items = this.$items.add($_items);

		// bind mousedown listener
		$_items.bind('mousedown.'+NS, $.proxy(this, '_onMouseDown'));
	},

	/**
	 * Remove Items
	 */
	removeItems: function($_items) {
		// unbind all events
		$_items.unbind('.'+NS);

		// remove the record of it
		this.$items = this.$items.not($_items);
	},

	/**
	 * Reset
	 */
	reset: function() {
		// unbind the events
		this.$items.unbind('.'+NS);

		// reset local vars
		this.$items = $();
	}
});


/**
 * Sort
 */
Assets.Sort = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function($container, settings) {

		this.$container = $container;
		this.settings = settings;

		this.$heightedContainer;

		this.drag;
		this.otherItemMidpoints;
		this.closestItem;

		// -------------------------------------------
		//  Get the closest container that has a height
		// -------------------------------------------

		this.$heightedContainer = this.$container;

		while (! this.$heightedContainer.height()) {
			this.$heightedContainer = this.$heightedContainer.parent();
		}

		// -------------------------------------------
		//  Initialize the Drag instance
		// -------------------------------------------

		// combine the passed-in settings with our own
		var dragSettings = $.extend({}, this.settings, {
			onDragStart: $.proxy(this, '_onDragStart'),
			onMouseMove: $.proxy(this, '_onMouseMove'),
			onDragStop:  $.proxy(this, '_onDragStop')
		});

		this.drag = new Assets.Drag(dragSettings);
	},

	/**
	 * On Drag Start 
	 */
	_onDragStart: function() {

		this.closestItem = null;

		// add the caboose?
		if (this.settings.caboose) {
			// is it a function?
			if (typeof this.settings.caboose == 'function') {
				this.settings.caboose = this.settings.caboose();
			}

			this.settings.caboose.appendTo(this.$container);
			this.drag.otherItems.push(this.settings.caboose);
		}

		// find the midpoints of the other items
		this.otherItemMidpoints = [];

		for (var i = 0; i < this.drag.otherItems.length; i++) {
			var $item = $(this.drag.otherItems[i]),
				offset = $item.offset();

			this.otherItemMidpoints.push({
				left: offset.left + $item.width() / 2,
				top:  offset.top + $item.height() / 2
			});
		};
	},

	/**
	 * On Mouse Move
	 */
	_onMouseMove: function(event) {

		// -------------------------------------------
		//  Find the closest item
		// -------------------------------------------

		if (this.settings.insertion) {
			// is it a function?
			if (typeof this.settings.insertion == 'function') {
				this.settings.insertion = this.settings.insertion();
			}

			if (Assets.isCursorOver(event, this.$heightedContainer)) {
				// are there any other items?
				var _closestItem,
					_closestItemMouseDiff;

				for (var i = 0; i < this.drag.otherItems.length; i++) {
					var mouseDiff = Assets.getDist(this.otherItemMidpoints[i].left, this.otherItemMidpoints[i].top, this.drag.mouseX, this.drag.mouseY);

					if (! _closestItem || mouseDiff < _closestItemMouseDiff) {
						_closestItem = this.drag.otherItems[i],
						_closestItemMouseDiff = mouseDiff;
					}
				};

				// new closest item?
				if (_closestItem != this.closestItem) {
					this.closestItem = _closestItem;
					this.settings.insertion.insertBefore(this.closestItem);
				}
			} else {
				this.settings.insertion.remove();
				this.closestItem = null;
			}
		}
	},

	/**
	 * On Drag Stop
	 */
	_onDragStop: function() {

		if (this.closestItem) {
			// swap the insertion with the draggees
			this.settings.insertion.replaceWith(this.drag.$draggees);

			// -------------------------------------------
			//  onSortChange callback
			//
				if (typeof this.settings.onSortChange == 'function') {
					this.settings.onSortChange();
				}
			//
			// -------------------------------------------
		}

		// "show" the drag items, but make them invisible
		this.drag.$draggees.css({
			display:    this.drag.draggeeDisplay,
			visibility: 'hidden'
		});

		// return the helpers to the draggees
		this.drag.returnHelpersToDraggees();

		// remove the caboose
		this.settings.caboose.remove();
	},

	/**
	 * Add Items
	 */
	addItems: function($_items) {
		return this.drag.addItems($_items);
	},

	/**
	 * Remove Items
	 */
	removeItems: function($_items) {
		return this.drag.removeItems($_items);
	},

	/**
	 * Reset
	 */
	reset: function() {
		return this.drag.reset();
	}
});


})(jQuery);
