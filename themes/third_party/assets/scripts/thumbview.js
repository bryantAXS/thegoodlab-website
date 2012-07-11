/**
 * Assets Thumb View
 *
 * This powers Thumb Views in Assets, by providing the following:
 *
 * Public Methods:
 *
 *  • getItems(): returns the items
 *  • addItems( items ): adds additional items
 *  • destroy(): removes all event listeners created by this Thumb View instance
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */


(function($) {


// define the namespace
var NS = 'assets-thumbview';


/**
 * Thumb View
 */
Assets.ThumbView = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function($container) {

		this.$container = $container;

		this.$scrollpane = $('> .assets-scrollpane', this.$container);
		this.$ul = $('> ul', this.$scrollpane);
		this.$items;

		this._getItems();
	},

	/**
	 * Get Items
	 */
	_getItems: function(second) {
		this.$items = $('> li', this.$ul);
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
		this.$ul.append($add);
		this._getItems()
	},

	/**
	 * Remove Items 
	 */
	removeItems: function($remove) {
		$remove.remove();
		this._getItems();
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
		// delete this ThumbView instance
		delete obj;
	},

	/**
	 * Get Container
	 */
	getContainer: function() {
		return this.$ul;
	},

	/**
	 * Get Scrollpane
	 */
	getScrollpane: function() {
		return this.$scrollpane;
	},

	/**
	 * Set Drag Wrapper
	 */
	setDragWrapper: function(file) {
		return $('<ul class="assets-tv-drag" />').append(file);
	},

	/**
	 * Get Drag Caboose
	 */
	getDragCaboose: function() {
		return $('<li class="assets-tv-file assets-tv-dragcaboose" />');
	},

	/**
	 * Get Drag Insertion Placeholder
	 */
	getDragInsertion: function() {
		return $('<li class="assets-tv-draginsertion" />');
	}

});


})(jQuery);
