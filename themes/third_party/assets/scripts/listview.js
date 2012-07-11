/**
 * Assets List View
 *
 * This powers List Views in Assets, by providing the following:
 *
 *  • Keeps the table headers' widths and x scroll position in sync with their data columns
 *  • Accounts for whether the table body has a scrollbar or not
 *  • Handles column sorting click events
 *
 * Accepted Settings:
 *
 *  • orderby:      the current column that the data is being ordered by, if any
 *  • sort:         the direction the data is being sorted ('asc' or 'desc'), if any
 *  • onSortChange: a function to be called when the user intends to change the sorting
 *
 * Public Methods:
 *
 *  • getItems(): returns the list items
 *  • addItems( items ): adds additional items to the list
 *  • destroy(): removes all event listeners created by this List View instance
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */


(function($) {


// define the namespace
var NS = 'assets-listview';


/**
 * List View
 */
Assets.ListView = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function($container, settings) {

		this.$container = $container;
		this.settings = (settings || {});

		this.$theadContainer = $('> .assets-lv-thead', this.$container);
		this.$tbodyContainer = $('> .assets-lv-tbody', this.$container);
		this.$ths = $('> table > thead > tr > th', this.$theadContainer);
		this.$table = $('> table', this.$tbodyContainer);
		this.$tbody = $('> tbody', this.$table);
		this.$tds = $('> tbody > tr:first > td', this.$table);
		this.$items;

		this.scrollbarWidth;
		this.scrollLeft = 0;

		this.orderby = this.settings.orderby;
		this.sort = this.settings.sort;

		this._getItems();

		this._setScrollbarPadding();

		this._setColHeaderWidths();
		$(window).bind('resize.'+NS, $.proxy(this, '_setColHeaderWidths'));

		// -------------------------------------------
		//  Column Sorting
		// -------------------------------------------

		if (typeof this.settings.onSortChange == 'function') {
			this.$ths.bind('click.'+NS, $.proxy(function(event) {
				var orderby = $(event.currentTarget).attr('data-orderby');

				if (orderby != this.orderby) {
					// ordering by something new
					this.orderby = orderby;
					this.sort = 'asc';
				} else {
					// just reverse the sort
					this.sort = (this.sort == 'asc' ? 'desc' : 'asc');
				}

				this.settings.onSortChange(this.orderby, this.sort);
			}, this));
		}

		// -------------------------------------------
		//  Keep header x-position in sync with data
		// -------------------------------------------

		this.$tbodyContainer.bind('scroll.'+NS, $.proxy(function() {
			// has the scrollLeft position changed?
			if ((this.scrollLeft !== (this.scrollLeft = this.$tbodyContainer.scrollLeft()))) {
				this.$theadContainer.css('margin-left', -this.scrollLeft+'px');
			}
		}, this));
	},

	/**
	 * Get Items
	 */
	_getItems: function(second) {
		this.$items = $('> tr', this.$tbody);
	},

	/**
	 * Reset
	 */
	_reset: function() {
		this._getItems();

		// give the browser a chance to orient itself
		setTimeout($.proxy(function() {
			this._setScrollbarPadding();
			this._setColHeaderWidths();
		}, this), 0);
	},

	/**
	 * Set Scrollbar Padding
	 */
	_setScrollbarPadding: function() {
		// get this field's current scrollbar width (could be 0)
		this.scrollbarWidth = this.$container.width() - this.$tbodyContainer[0].clientWidth

		// add padding to the last col header to compensate for the tbody's scrollbar
		this.$ths.last().css('padding-right', 8 + this.scrollbarWidth);
	},

	/**
	 * Set Column Header Widths
	 *
	 * Keeps <th> widths in sync with <td> widths
	 */
	_setColHeaderWidths: function() {
		// set the thead width
		this.$theadContainer.width(this.$tbodyContainer[0].scrollWidth + this.scrollbarWidth);

		for (var i = 0; i < this.$tds.length; i++) {
			var tdWidth = $(this.$tds[i]).width();
			$(this.$ths[i]).width(tdWidth);
		}
	},

	// -------------------------------------------
	//  Public methods
	// -------------------------------------------

	/**
	 * Get Items
	 */
	getItems: function() {
		return this.$items;
	},

	/**
	 * Add Items
	 */
	addItems: function($add) {
		this.$tbody.append($add);
		this._reset();
	},

	/**
	 * Remove Items 
	 */
	removeItems: function($remove) {
		$remove.remove();
		this._reset();
	},

	/**
	 * Reset Items
	 */
	resetItems: function() {
		this._getItems();
	},

	/**
	 * Destroy
	 */
	destroy: function() {
		// unbind all events
		$(window).unbind('.'+NS);
		this.$ths.unbind('.'+NS);
		this.$tbodyContainer.unbind('.'+NS);
	},

	/**
	 * Get Container
	 */
	getContainer: function() {
		return this.$tbody;
	},

	/**
	 * Get Scrollpane
	 */
	getScrollpane: function() {
		return this.$tbodyContainer;
	},

	/**
	 * Set Drag Wrapper
	 */
	setDragWrapper: function(file) {
		return $('<table class="assets-listview assets-lv-drag" cellpadding="0" cellspacing="0" border="0" />').width(this.$table.width()).append($('<tbody />').append(file));
	},

	/**
	 * Get Drag Caboose
	 */
	getDragCaboose: function() {
		return $('<tr class="assets-lv-file assets-lv-dragcaboose" />');
	},

	/**
	 * Get Drag Insertion Placeholder
	 */
	getDragInsertion: function() {
		return $('<tr class="assets-lv-draginsertion"><td colspan="'+this.$ths.length+'">&nbsp;</td></tr>');
	}

});


})(jQuery);
