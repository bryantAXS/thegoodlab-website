/**
 * Assets File Manager Folder
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */


(function($) {


/**
 * File Manager Folder
 */
Assets.FileManager.Folder = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function(fm, li, depth, parent) {

		this.fm = fm;
		this.li = li;
		this.depth = depth;
		this.parent = parent;

		this.$li = $(this.li);
		this.$a = $('> a', this.$li);
		this.$toggle;
		this.$ul;

		this.path = this.$a.attr('data-path');

		this.visible = false;
		this.visibleBefore = false;
		this.expanded = false;
		this.subfolders = [];

		this.fm.folders[this.path] = this;

		// -------------------------------------------
		//  Make top-level folders visible
		// -------------------------------------------

		if (this.depth == 1) {
			this.onShow();
		}

		// -------------------------------------------
		//  Create the context menu
		// -------------------------------------------

		var menuOptions = [];

		if (this.fm.options.mode == 'full' && this.depth > 1) {
			menuOptions.push({ label: Assets.lang.rename, onClick: $.proxy(this, '_rename') });
			menuOptions.push('-');
		}

		menuOptions.push({ label: Assets.lang.new_subfolder, onClick: $.proxy(this, '_createSubfolder') });

		if (this.fm.options.mode == 'full' && this.depth > 1) {
			menuOptions.push('-');
			menuOptions.push({ label: Assets.lang._delete, onClick: $.proxy(this, '_delete') });
		}

		new Assets.ContextMenu(this.$a, menuOptions);
	},

	// -------------------------------------------
	//  Subfolders and the toggle button
	// -------------------------------------------

	/**
	 * Has Subfolders
	 */
	hasSubfolders: function() {
		return !! this.subfolders.length;
	},

	/**
	 * Prep for Subfolders
	 */
	_prepForSubfolders: function() {
		// add the toggle
		if (! this.$toggle) {
			this.$toggle = $('<span class="assets-fm-toggle"></span>');
		}

		this.$toggle.prependTo(this.$a);

		// prevent toggle button clicks from triggering multi select functions
		this.$toggle.bind('mouseup.assets, mousedown.assets, click.assets', function(event) {
			event.stopPropagation();
		});

		// toggle click handling
		this.$toggle.click($.proxy(this, '_toggle'));

		// add the $ul
		if (! this.$ul) {
			this.$ul = $('<ul />');
		}

		this.$ul.appendTo(this.$li);
	},

	/**
	 * Unprep for Subfolders
	 */
	_unprepForSubfolders: function() {
		this.$toggle.remove();
		this.$ul.remove();
		this.collapse();
	},

	/**
	 * Add Subfolder
	 */
	addSubfolder: function(subfolder) {
		// is this our first subfolder?
		if (! this.hasSubfolders()) {
			this._prepForSubfolders();

			var pos = 0;
		} else {
			var paths = [ subfolder.path ];

			for (var i = 0; i < this.subfolders.length; i++) {
				var path = this.subfolders[i].path;
				paths.push(path);
			}

			paths.sort(Assets.caseInsensativeSort);
			var pos = $.inArray(subfolder.path, paths);
		}

		if (pos == 0) {
			subfolder.$li.prependTo(this.$ul);
			this.$ul.prepend(subfolder.$li);
		} else {
			var prevSibling = this.fm.folders[paths[pos-1]];
			subfolder.$li.insertAfter(prevSibling.$li);
		}

		this.subfolders.push(subfolder);
	},

	/**
	 * Remove Subfolder
	 */
	removeSubfolder: function(subfolder) {
		this.subfolders.splice($.inArray(subfolder, this.subfolders), 1);

		// was this the only subfolder?
		if (! this.hasSubfolders()) {
			this._unprepForSubfolders();
		}
	},

	/**
	 * Toggle
	 */
	_toggle: function() {
		if (this.expanded) {
			this.collapse();
		} else {
			this.expand();
		}
	},
	
	/**
	 * Expand
	 */
	expand: function() {
		if (this.expanded) return;

		this.expanded = true;
		this.$a.addClass('assets-fm-expanded');
		this.$ul.show();
		this._onShowSubfolders();
		this.fm.setFoldersWidth();
	},

	/**
	 * Collapse
	 */
	collapse: function() {
		if (! this.expanded) return;

		this.expanded = false;
		this.$a.removeClass('assets-fm-expanded');
		this.$ul.hide();
		this._onHideSubfolders();
		this.fm.setFoldersWidth();
	},

	// -------------------------------------------
	//  Showing and hiding
	// -------------------------------------------

	/**
	 * On Show
	 */
	onShow: function() {
		this.visible = true;

		this.fm.folderSelect.addItems(this.$a);

		if (this.depth > 1) {
			if (this.fm.options.mode == 'full') {
				this.fm.folderDrag.addItems(this.$li);
			}
		}

		if (! this.visibleBefore) {
			this.visibleBefore = true;

			// check to see if there are any subfolders
			var data = {
				folder: this.path,
				depth: this.depth
			};

			$.post(Assets.actions.get_subfolders, data, $.proxy(function(data, textStatus) {
				if (textStatus == 'success' && data) {
					// prep this folder for subfolders
					this._prepForSubfolders();

					// add the LIs to the UL
					this.$ul.append(data);

					// initialize sub folders
					var $lis = $('> li', this.$ul);

					for (var i = 0; i < $lis.length; i++) {
						var subfolder = new Assets.FileManager.Folder(this.fm, $lis[i], this.depth + 1, this);
						this.subfolders.push(subfolder);
					};
				}
			}, this));
		}

		if (this.expanded) {
			this._onShowSubfolders();
		}
	},

	/**
	 * On Hide
	 */
	onHide: function() {
		this.visible = false;
		this.fm.folderSelect.removeItems(this.$a);

		if (this.expanded) {
			this._onHideSubfolders();
		}
	},

	/**
	 * On Show Subfolders
	 */
	_onShowSubfolders: function() {
		for (var i in this.subfolders) {
			this.subfolders[i].onShow();
		}
	},

	/**
	 * On Hide Subfolders
	 */
	_onHideSubfolders: function() {
		for (var i in this.subfolders) {
			this.subfolders[i].onHide();
		}
	},

	/**
	 * On Delete
	 */
	onDelete: function(topDeletedFolder) {
		// remove the master record of this folder
		delete this.fm.folders[this.path];

		if (topDeletedFolder) {
			// remove the parent folder's record of this folder
			this.parent.removeSubfolder(this);

			// remove the LI
			this.$li.remove();
		}

		for (var i = 0; i < this.subfolders.length; i++) {
			this.subfolders[i].onDelete();
		}
	},

	// -------------------------------------------
	//  Operations
	// -------------------------------------------

	/**
	 * Move to...
	 */
	moveTo: function(newPath) {
		// find the parent folder
		var parts = Assets.getPathParts(newPath),
			newParentPath = parts[0];

		for (var i = 1; i < parts.length-1; i++) {
			newParentPath += parts[i]+'/';
		}

		var newParent = this.fm.folders[newParentPath];

		// is the old boss the same as the new boss?
		if (newParent == this.parent) return;

		// add this to the new parent
		// (we need to do this first so that the <li> is always in the DOM, and keeps its events)
		newParent.addSubfolder(this);

		// remove this from the old parent
		this.parent.removeSubfolder(this);

		// set the new depth
		this.updateDepth(newParent.depth + 1);

		// make sure the new parent is expanded
		newParent.expand();

		this.parent = newParent;

		this.updatePath(newPath);
		this.updateName(parts[parts.length-1]);
	},

	/**
	 * Update Path
	 */
	updatePath: function(path) {
		delete this.fm.folders[this.path];

		this.path = path;
		this.$a.attr('data-path', this.path);
		this.fm.folders[this.path] = this;

		// update subfolders
		for (var i = 0; i < this.subfolders.length; i++) {
			var subfolder = this.subfolders[i],
			 	parts = Assets.getPathParts(subfolder.path),
				newPath = this.path + parts[parts.length-1]+'/';

			subfolder.updatePath(newPath);
		}
	},

	/**
	 * Update Name
	 */
	updateName: function(name) {
		$('span.assets-fm-icon', this.$a)[0].nextSibling.nodeValue = name;

		// -------------------------------------------
		//  Re-sort this folder among its siblings
		// -------------------------------------------

		var paths = [];

		for (var i = 0; i < this.parent.subfolders.length; i++) {
			var path = this.parent.subfolders[i].path;
			paths.push(path);
		}

		paths.sort(Assets.caseInsensativeSort);
		var pos = $.inArray(this.path, paths);

		if (pos == 0) {
			this.$li.prependTo(this.parent.$ul);
		} else {
			var prevSibling = this.fm.folders[paths[pos-1]];
			this.$li.insertAfter(prevSibling.$li);
		}
	},

	/**
	 * Update Depth
	 */
	updateDepth: function(depth) {
		if (depth == this.depth) return;

		this.depth = depth;

		var padding = 20 + (18 * this.depth);
		this.$a.css('padding-left', padding);

		for (var i = 0; i < this.subfolders.length; i++) {
			this.subfolders[i].updateDepth(this.depth + 1);
		}
	},

	/**
	 * Rename
	 */
	_rename: function() {
		var parts = Assets.getPathParts(this.path),
			oldName = parts[parts.length-1],
			newName = prompt(Assets.lang.rename, oldName);

		if (newName && newName != oldName) {
			this.fm.$spinner.show();

			// assemble the complete new folder ID
			var newPath = parts[0];

			for (var i = 1; i < parts.length-1; i++) {
				newPath += parts[i]+'/';
			}

			newPath += newName+'/';

			var data = {
				old_folder: this.path,
				new_folder: newPath
			};

			$.post(Assets.actions.move_folder, data, $.proxy(function(data, textStatus) {
				if (textStatus == 'success') {
					if (data[0][1] == 'success') {
						// get the new name (might have changed if there was a conflict)
						var newPath = data[0][2],
							parts = Assets.getPathParts(newPath),
							newName = parts[parts.length-1];

						this.updatePath(newPath);
						this.updateName(newName);
					}
				}

				this.fm.$spinner.hide();
			}, this), 'json');
		}
	},

	/**
	 * Create Subfolder
	 */
	_createSubfolder: function() {
		var subfolderName = prompt(Assets.lang.new_subfolder);

		if (subfolderName) {
			this.fm.$spinner.show();

			var subfolderPath = this.path+subfolderName+'/';

			$.post(Assets.actions.create_folder, { folder: subfolderPath }, $.proxy(function(data, textStatus) {
				if (textStatus == 'success') {
					if (data.error) {
						alert(data.error);

						// try again?
						this._createSubfolder();
					} else {
						var subfolderDepth = this.depth + 1,
							padding = 20 + (18 * subfolderDepth),
							$li = $('<li class="assets-fm-folder">'
						          +   '<a data-path="'+subfolderPath+'" style="padding-left: '+padding+'px;">'
						          +     '<span class="assets-fm-icon" />' + subfolderName
						          +   '</a>'
						          + '</li>'),
							subfolder = new Assets.FileManager.Folder(this.fm, $li[0], subfolderDepth, this);

						this.addSubfolder(subfolder);

						subfolder.onShow();
					}
				}
			}, this), 'json')
		}
	},

	/**
	 * Delete
	 */
	_delete: function() {
		var parts = Assets.getPathParts(this.path);

		if (confirm(Assets.parseTag(Assets.lang.confirm_delete_folder, 'folder', parts[parts.length-1]))) {
			this.fm.$spinner.show();

			$.post(Assets.actions.delete_folder, { folder: this.path }, $.proxy(function(data, textStatus) {
				if (textStatus == 'success') {
					if (data.error) {
						alert(data.error);
					} else {
						this.onDelete(true);
					}
				}
			}, this), 'json');
		}
	}
});


})(jQuery);
