/**
 * Assets File Manager
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */


(function($) {


// define the Assets global
if (typeof window.Assets == 'undefined') window.Assets = {};


// -------------------------------------------
//  Utility functions
// -------------------------------------------

/**
 * Format Number
 */
Assets.fmtNum = function(num) {
	num = num.toString();

	var regex = /(\d+)(\d{3})/;
	while (regex.test(num)) {
		num = num.replace(regex, '$1'+','+'$2');
	}

	return num;
};

/**
 * Get the distance between two coordinates
 */
Assets.getDist = function(x1, y1, x2, y2) {
	return Math.sqrt(Math.pow(x1-x2, 2) + Math.pow(y1-y2, 2));
};

/**
 * Hit Test
 */
Assets.hitTest = function(x0, y0, element) {
	var $element = $(element),
		offset = $element.offset(),
		x1 = offset.left,
		y1 = offset.top,
		x2 = x1 + $element.width(),
		y2 = y1 + $element.height();

	return (x0 >= x1 && x0 < x2 && y0 >= y1 && y0 < y2);
};

/**
 * Check if cursor is over an element
 */
Assets.isCursorOver = function(event, element) {
	return Assets.hitTest(event.pageX, event.pageY, element);
};

/**
 * Case Insensative Sort
 */
Assets.caseInsensativeSort = function(a, b) {
	a = a.toLowerCase();
	b = b.toLowerCase();
	return a < b ? -1 : (a > b ? 1 : 0);
}

/**
 * Really basic class constructor
 */
Assets.Class = function(p) {

	var c = function() {
		if (typeof this.__construct == 'function') {
			this.__construct.apply(this, arguments);
		}
	};

	c.prototype = p;

	return c;
};


// -------------------------------------------
//  File Manager classes
// -------------------------------------------

/**
 * File Manager
 */
Assets.FileManager = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function($fm, options) {

		this.$fm = $fm;

		this.options = $.extend({}, Assets.FileManager.defaultOptions, options);

		this.$toolbar = $('> .assets-toolbar', this.$fm);

		this.$viewAsThumbsBtn = $('> .assets-fm-view a.assets-fm-thumbs', this.$toolbar);
		this.$viewInListBtn   = $('> .assets-fm-view a.assets-fm-list', this.$toolbar);

		this.$upload = $('> .assets-fm-upload', this.$toolbar);

		this.$search = $('> .assets-fm-search', this.$toolbar);
		this.$searchInput = $('> label > input', this.$search);
		this.$searchErase = $('> a.assets-fm-erase', this.$search),

		this.$spinner = $('> .assets-fm-spinner', this.$toolbar);

		this.$left = $('> .assets-fm-left', this.$fm);
		this.$right = $('> .assets-fm-right', this.$fm);

		this.$folders = $('> .assets-fm-folders', this.$left);
		this.$files = $('> .assets-fm-files', this.$right);

		this.$rightFooter = $('> .assets-footer', this.$right);
		this.$totalFiles = $('> .assets-fm-total', this.$rightFooter);
		this.$buttons = $('> .assets-fm-btns a', this.$rightFooter);

		this.folders = {};

		this.selectedFolderIds = [];
		this.view = 'thumbs';
		this.searchTimeout;
		this.searchVal = '';
		this.totalFiles = 0;
		this.orderby = 'name';
		this.sort = 'asc';

		this.filesRequestId = 0;
		this.propsRequestId = 0;

		this.fileSelect;
		this.filesView;

		// -------------------------------------------
		//  Switch Views
		// -------------------------------------------

		this.$viewAsThumbsBtn.click($.proxy(function() {
			// ignore if it's already in thumbs view
			if (this.view == 'thumbs') return;

			// swap the active button
			this.$viewAsThumbsBtn.addClass('assets-active');
			this.$viewInListBtn.removeClass('assets-active');

			// set the view and update the files
			this.view = 'thumbs';
			this._updateFiles();
		}, this));

		this.$viewInListBtn.click($.proxy(function() {
			// ignore if it's already in thumbs view
			if (this.view == 'list') return;

			// swap the active button
			this.$viewAsThumbsBtn.removeClass('assets-active');
			this.$viewInListBtn.addClass('assets-active');

			// set the view and update the files
			this.view = 'list';
			this._updateFiles();
		}, this));

		// -------------------------------------------
		//  File Uploads
		// -------------------------------------------

		this.uploader = new Assets.qq.FileUploader({
			element: this.$upload[0],
			action:  Assets.actions.upload_file,
			onSubmit: $.proxy(function(id, fileName) {
				this.$spinner.show();
			}, this),
			onComplete: $.proxy(function(id, fileName, responseJSON) {
				if (! this.uploader.getInProgress()) {
					this._updateFiles();
				}
			}, this)
		});

		// -------------------------------------------
		//  Folders
		// -------------------------------------------

		// initialize the folder multiselect
		this.folderSelect = new Assets.Select(this.$folders, {
			multi: true,
			multiDblClick: false,
			onSelectionChange: $.proxy(this, '_updateSelectedFolders')
		});

		// initialize top-level folders
		this.$topFolderUl = $('> ul', this.$folders),
		this.$topFolderLis = $('> li', this.$topFolderUl);

		for (var i = 0; i < this.$topFolderLis.length; i++) {
			var folder = new Assets.FileManager.Folder(this, this.$topFolderLis[i], 1);

			// select the first one
			if (i == 0) {
				folder.$a.addClass('assets-selected');
			}
		};

		// set it right off the bat in case there are any really long upload directory names
		this.setFoldersWidth();

		if (this.options.mode == 'full') {

			this.expandDropTargetFolderTimeout = null;

			// -------------------------------------------
			//  Folder dragging
			// -------------------------------------------

			this.folderDrag = new Assets.Drag({
				draggeePlaceholders: true,
				helperOpacity: 0.5,
				activeDropTargetClass: 'assets-selected assets-fm-dragtarget',

				filter: $.proxy(function() {
					// return each of the selected <a>'s parent <li>s
					var $selected = this.folderSelect.getSelectedItems(),
						draggees = [];

					for (var i = 0; i < $selected.length; i++) {
						var li = $($selected[i]).parent()[0];

						// ignore top-level folders
						if ($.inArray(li, this.$topFolderLis) != -1) {
							this.folderSelect.deselectItem($($selected[i]));
							continue;
						}

						draggees.push(li);
					};

					return $(draggees);
				}, this),

				helper: $.proxy(function($folder) {
					var $helper = $('<ul class="assets-fm-folderdrag" />').append($folder);

					// collapse this folder
					$('> a', $folder).removeClass('assets-fm-expanded');
					$('> ul', $folder).hide();

					// set the helper width to the folders container width
					$helper.width(this.$folders[0].scrollWidth);

					return $helper;
				}, this),

				dropTargets: $.proxy(function() {
					var targets = [];

					for (var folderId in this.folders) {
						var folder = this.folders[folderId];

						if (folder.visible && $.inArray(folder.$li[0], this.folderDrag.$draggees) == -1) {
							targets.push(folder.$a);
						}
					}

					return targets;
				}, this),

				onDragStart: $.proxy(function() {
					this.tempExpandedFolders = [];

					// hide the expanded draggees' subfolders
					$('> a.assets-fm-expanded', this.folderDrag.$draggees).each(function() {
						$(this).next('ul').hide();
					});
				}, this),

				onDropTargetChange: $.proxy(this, '_onDropTargetChange'),

				onDragStop: $.proxy(function() {
					// show the expanded draggees' subfolders
					$('> a.assets-fm-expanded', this.folderDrag.$draggees).each(function() {
						$(this).next('ul').show();
					});

					if (this.folderDrag.activeDropTarget) {
						var targetFolderId = this.folderDrag.activeDropTarget.attr('data-id');

						this._collapseExtraExpandedFolders(targetFolderId);

						this.$spinner.show();

						// get the old folder IDs, and sort them so that we're moving the most-nested folders first
						var folderIds = [];

						for (var i = 0; i < this.folderDrag.$draggees.length; i++) {
							var $a = $('> a', this.folderDrag.$draggees[i]),
								folderId = $a.attr('data-id'),
								folder = this.folders[folderId];

							// make sure it's not already in the target folder
							if (folder.parent.id != targetFolderId) {
								folderIds.push(folderId);
							}
						}

						if (folderIds.length) {
							folderIds.sort();
							folderIds.reverse();

							// now prep the data for move_folder()
							var data = {};

							for (var i = 0; i < folderIds.length; i++) {
								var parts = folderIds[i].split(/[\}\/]/),
									newFolderId = targetFolderId + parts[parts.length-2]+'/';

								data['old_folder['+i+']'] = folderIds[i];
								data['new_folder['+i+']'] = newFolderId;
							}

							$.post(Assets.actions.move_folder, data, $.proxy(function(data, textStatus) {
								if (textStatus == 'success') {
									for (var i = 0; i < data.length; i++) {
										if (data[i][1] == 'success') {
											var folder = this.folders[data[i][0]];
											folder.moveTo(data[i][2]);
										} else {
											alert('Could not move '+data[i][0]+':\n\n'+data[i][2]);
										}
									}

									this.folderDrag.returnHelpersToDraggees();
								}
							}, this), 'json');

							// skip returning dragees until we get the Ajax response
							return;
						}
					} else {
						this._collapseExtraExpandedFolders();
					}

					this.folderDrag.returnHelpersToDraggees();
				}, this)
			});

			// -------------------------------------------
			//  File dragging
			// -------------------------------------------

			this.fileDrag = new Assets.Drag({
				draggeePlaceholders: true,
				helperOpacity: 0.5,
				activeDropTargetClass: 'assets-selected assets-fm-dragtarget',

				filter: $.proxy(function() {
					return this.fileSelect.getSelectedItems();
				}, this),

				helper: $.proxy(function($file) {
					return $('<ul class="assets-fm-filedrag" />').append($file);
				}, this),

				dropTargets: $.proxy(function() {
					var targets = [];

					for (var folderId in this.folders) {
						var folder = this.folders[folderId];

						if (folder.visible) {
							targets.push(folder.$a);
						}
					}

					return targets;
				}, this),

				onDragStart: $.proxy(function() {
					this.tempExpandedFolders = [];

					$selectedFolders = this.folderSelect.getSelectedItems();
					$selectedFolders.removeClass('assets-selected');
				}, this),

				onDropTargetChange: $.proxy(this, '_onDropTargetChange'),

				onDragStop: $.proxy(function() {
					if (this.fileDrag.activeDropTarget) {
						// keep it selected
						this.fileDrag.activeDropTarget.addClass('assets-selected');

						var targetFolderId = this.fileDrag.activeDropTarget.attr('data-id');

						this._collapseExtraExpandedFolders(targetFolderId);

						var oldFilePaths = [],
							newFilePaths = [];

						for (var i = 0; i < this.fileDrag.$draggees.length; i++) {
							var oldFilePath = this.fileDrag.$draggees[i].getAttribute('data-file-path'),
								parts = oldFilePath.split(/[\}\/]/),
								fileName = parts[parts.length-1],
								newFilePath = targetFolderId + fileName;

							// make sure it's not already in the target folder
							if (newFilePath != oldFilePath) {
								oldFilePaths.push(oldFilePath);
								newFilePaths.push(newFilePath);
							}
						}

						// are any files actually getting moved?
						if (oldFilePaths.length) {
							this.$spinner.show();

							// prep the data for move_file()
							var data = {};

							for (var i = 0; i < oldFilePaths.length; i++) {
								data['old_file['+i+']'] = oldFilePaths[i];
								data['new_file['+i+']'] = newFilePaths[i];
							}

							$.post(Assets.actions.move_file, data, $.proxy(function(data, textStatus) {
								if (textStatus == 'success') {
									this.fileDrag.fadeOutHelpers();
									this._updateSelectedFolders();
									this._updateFiles();
								}
							}, this), 'json');

							// skip returning dragees
							return;
						}
					} else {
						this._collapseExtraExpandedFolders();
					}

					// re-select the previously selected folders
					$selectedFolders.addClass('assets-selected');

					this.fileDrag.returnHelpersToDraggees();
				}, this)
			});

		}

		// -------------------------------------------
		//  Keyword Search
		// -------------------------------------------

		this.$searchInput.keydown($.proxy(this, '_onSearchKeyDown'));

		this.$searchErase.click($.proxy(function() {
			this._eraseSearch();
			this._updateFiles();
			this.$searchInput.focus();
		}, this));

		// -------------------------------------------
		//  Bottom buttons
		// -------------------------------------------

		if (this.options.mode == 'full') {
			// Edit File button
			$(this.$buttons[0]).click($.proxy(function() {
				// ignore if there's more than one file selected
				if (this.fileSelect.getTotalSelected() != 1) return;

				var $file = this.fileSelect.getSelectedItems();
				new Assets.Properties($file);
			}, this));
		}

		// -------------------------------------------
		//  Initialize the files view
		// -------------------------------------------

		this._updateSelectedFolders();

	},

	_updateSelectedFolders: function() {
		// get the new list of selected folder IDs
		this.selectedFolderIds = [];

		var $selected = this.folderSelect.getSelectedItems();

		for (var i = 0; i < $selected.length; i++) {
			this.selectedFolderIds.push($($selected[i]).attr('data-id'));
		};

		// clear the keyword search
		this._eraseSearch();

		this._updateFiles();

		if (this.selectedFolderIds.length == 1) {
			this.uploader.enable();

			this.uploader.setParams({
				folder: this.selectedFolderIds[0]
			});
		} else {
			this.uploader.disable();
		}
	},

	/**
	 * Set Folders Width
	 *
	 * This is called by Assets.FileManager.Folder instances when their toggle button has been clicked.
	 * It makes sure that the folders content width is equal to the pane's scroll width,
	 * which prevents inner elements from spanning the full width if there's horizontal scrolling
	 */
	setFoldersWidth: function() {
		// clear the ul's current width
		this.$topFolderUl.width('auto');

		// now we have an accurate scrollWidth
		this.$topFolderUl.width(this.$folders[0].scrollWidth);
	},

	/**
	 * On Drop Target Change
	 */
	_onDropTargetChange: function(dropTarget) {
		clearTimeout(this.expandDropTargetFolderTimeout);

		if (dropTarget) {
			var folderId = dropTarget.attr('data-id');
			this.dropTargetFolder = this.folders[folderId];

			if (this.dropTargetFolder.hasSubfolders() && ! this.dropTargetFolder.expanded) {
				this.expandDropTargetFolderTimeout = setTimeout($.proxy(this, '_expandDropTargetFolder'), 750);
			}
		}
	},

	/**
	 * Expand Drop Target Folder
	 */
	_expandDropTargetFolder: function() {
		// collapse any temp-expanded drop targets that aren't parents of this one
		this._collapseExtraExpandedFolders(this.dropTargetFolder.id);

		this.dropTargetFolder.expand();

		// keep a record of that
		this.tempExpandedFolders.push(this.dropTargetFolder);

		// what's currently being dragged -- folders or files?
		var dragger = this.folderDrag.dragging ? this.folderDrag : this.fileDrag;

		// add the subfolders to the drop targets
		for (var i = 0; i < this.dropTargetFolder.subfolders.length; i++) {
			var subfolder = this.dropTargetFolder.subfolders[i];
			dragger.dropTargets.push(subfolder.$a);
		}
	},

	/**
	 * Collapse Extra Expanded Folders
	 */
	_collapseExtraExpandedFolders: function(dropTargetFolderId) {
		clearTimeout(this.expandDropTargetFolderTimeout);

		for (var i = this.tempExpandedFolders.length-1; i >= 0; i--) {
			var folder = this.tempExpandedFolders[i];

			if (! dropTargetFolderId || dropTargetFolderId == folder.id || dropTargetFolderId.substr(0, folder.id.length) != folder.id) {
				folder.collapse();
				this.tempExpandedFolders.splice(i, 1);
			}
		}
	},

	// -------------------------------------------
	//  Files
	// -------------------------------------------

	/**
	 * Update Total Files 
	 */
	_updateTotalFiles: function() {
		if (this.fileSelect && this.fileSelect.getTotalSelected()) {
			html = Assets.fmtNum(this.fileSelect.getTotalSelected())+' '+Assets.lang.of+' '+Assets.fmtNum(this.totalFiles)+' '+Assets.lang.selected;
		} else if (this.showingFiles) {
			html = Assets.lang.showing+' '+Assets.fmtNum(this.showingFiles)+' '+Assets.lang.of+' '+Assets.fmtNum(this.totalFiles)+' '+Assets.lang.files;
		} else {
			html = Assets.fmtNum(this.totalFiles)+' '+(this.totalFiles == 1 ? Assets.lang.file : Assets.lang.files);
		}

		this.$totalFiles.html(html);
	},

	/**
	 * Rename File
	 */
	_renameFile: function(event) {
		var filePath = event.currentTarget.getAttribute('data-file-path'),
			parts = filePath.split(/[\}\/]/),
			oldName = parts[parts.length-1],
			newName = prompt(Assets.lang.rename, oldName);

		if (newName !== null && newName != oldName) {
			this.$spinner.show();

			// assemble the complete new file ID
			var newPath = parts[0]+'}';

			for (var i = 1; i < parts.length-1; i++) {
				newPath += parts[i]+'/';
			}

			newPath += newName;

			var data = {
				old_file: filePath,
				new_file: newPath
			};

			$.post(Assets.actions.move_file, data, $.proxy(function(data, textStatus) {
				if (textStatus == 'success') {
					if (data[0][1] == 'success') {
						this._updateFiles();
					} else if (data[0][1] == 'error') {
						alert(data[0][2]);
						this._renameFile(event);
					}
				}
			}, this), 'json');
		}
	},

	/**
	 * Delete File
	 */
	_deleteFile: function(event) {
		var filePath = event.currentTarget.getAttribute('data-file-path');

		if (confirm(Assets.lang.confirm_delete_file.replace('{file}', filePath))) {
			this.$spinner.show();

			$.post(Assets.actions.delete_file, { file: filePath }, $.proxy(function(data, textStatus) {
				if (textStatus == 'success') {
					if (data.error) {
						alert(data.error);
					} else {
						this._updateFiles();
					}
				}
			}, this), 'json');
		}
	},

	/**
	 * View File
	 */
	_viewFile: function(event) {
		var filePath = event.currentTarget.getAttribute('data-file-path'),
			url = Assets.actions.view_file+'&file='+encodeURIComponent(filePath);

		window.open(url);
	},

	/**
	 * Show Properties
	 */
	_showProperties: function(event) {
		new Assets.Properties($(event.currentTarget));
	},

	// -------------------------------------------
	//  Keyword Search
	// -------------------------------------------

	/**
	 * On Search Key Down
	 */
	_onSearchKeyDown: function(event) {
		// ignore if meta key is down
		if (event.metaKey) return;

		event.stopPropagation();

		// clear the last timeout
		clearTimeout(this.searchTimeout);

		setTimeout($.proxy(function() {
			switch (event.keyCode) {
				case 13: // return
					event.preventDefault();
					this._checkKeywordVal();
					break;

				case 27: // esc
					event.preventDefault();
					this.$searchInput.val('');
					this._checkKeywordVal();
					break;

				default:
					this.searchTimeout = setTimeout($.proxy(this, '_checkKeywordVal'), 500);
			}

			// show/hide the escape button
			if (this.$searchInput.val()) {
				this.$searchErase.css('display', 'block');
			} else {
				this.$searchErase.css('display', 'none');
			}
		}, this), 0);
	},

	_checkKeywordVal: function() {
		// has the value changed?
		if (this.searchVal !== (this.searchVal = this.$searchInput.val())) {
			this._updateFiles();
		}
	},

	_eraseSearch: function(event) {
		this.$searchInput.val('');
		this.searchVal = '';
		this.$searchErase.css('display', 'none');
	},

	// -------------------------------------------
	//  Update Files
	// -------------------------------------------

	/**
	 * Update Files
	 */
	_updateFiles: function() {
		this.filesRequestId++;

		// show the spinner
		this.$spinner.show();

		// destroy previous select & view
		if (this.fileSelect) this.fileSelect.destroy();
		if (this.filesView) this.filesView.destroy();
		this.fileSelect = this.filesView = null;

		if (this.options.mode == 'full') {
			this.fileDrag.reset();
			$(this.$buttons[0]).addClass('assets-disabled');
		}

		// -------------------------------------------
		//  onBeforeUpdateFiles callback
		//
			if (typeof this.options.onBeforeUpdateFiles == 'function') {
				this.options.onBeforeUpdateFiles();
			}
		//
		// -------------------------------------------

		var data = {
			requestId: this.filesRequestId,
			view:      this.view,
			keywords:  this.searchVal,
			orderby:   this.orderby,
			sort:      this.sort
		};

		// pass the selected folder IDs
		for (var i in this.selectedFolderIds) {
			data['folders['+i+']'] = this.selectedFolderIds[i];
		}

		// pass the file kinds
		if (! this.options.kinds || this.options.kinds == 'any') {
			data.kinds = 'any';
		} else {
			for (var i = 0; i < this.options.kinds.length; i++) {
				data['kinds['+i+']'] = this.options.kinds[i];
			}
		}

		// pass the disabled files
		for (var i = 0; i < this.options.disabledFiles.length; i++) {
			data['disabled_files['+i+']'] = this.options.disabledFiles[i];
		}

		// run the ajax post request
		$.post(Assets.actions.get_files_view_by_folders, data, $.proxy(function(data, textStatus) {
			if (textStatus == 'success') {
				// ignore if this isn't the current request
				if (data.requestId != this.filesRequestId) return;

				// update the HTML
				this.$files.html(data.html);

				// update the total files record
				this.totalFiles = data.total;
				this.showingFiles = data.showing;
				this._updateTotalFiles();

				// initialize the files view
				if (this.view == 'list') {
					this.filesView = new Assets.ListView($('> .assets-listview', this.$files), {
						orderby: this.orderby,
						sort:    this.sort,
						onSortChange: $.proxy(function(orderby, sort) {
							this.orderby = orderby;
							this.sort = sort;
							this._updateFiles();
						}, this)
					});
				} else {
					this.filesView = new Assets.ThumbView($('> .assets-thumbview', this.$files));
				}

				// get the files
				var $files = this.filesView.getItems().not('.assets-disabled');

				// initialize the files multiselect
				this.fileSelect = new Assets.Select(this.$files, {
					multi:         this.options.multiSelect,
					multiDblClick: (this.options.multiSelect && this.options.mode == 'select'),
					onSelectionChange: $.proxy(function() {
						this._updateTotalFiles();

						if (this.options.mode == 'full') {
							if (this.fileSelect.getTotalSelected() == 1) {
								$(this.$buttons[0]).removeClass('assets-disabled');
							} else {
								$(this.$buttons[0]).addClass('assets-disabled');
							}
						}

						// -------------------------------------------
						//  onSelectionChange callback
						//
							if (typeof this.options.onSelectionChange == 'function') {
								this.options.onSelectionChange();
							}
						//
						// -------------------------------------------

					}, this)
				});

				this.fileSelect.addItems($files);

				if (this.options.mode == 'full') {
					// file dragging
					this.fileDrag.addItems($files);
				}

				// double-click handling
				$files.bind('dblclick.asset-fm', $.proxy(function(event) {
					switch (this.options.mode) {
						case 'select':
							clearTimeout(this.fileSelect.clearMouseUpTimeout());
							this.options.onSelect();
							break;

						case 'full':
							this._showProperties(event);
							break;
					}
				}, this));

				// add the context menus
				var menuOptions = [{ label: Assets.lang.view_file, onClick: $.proxy(this, '_viewFile') }];

				if (this.options.mode == 'full') {
					menuOptions.push({ label: Assets.lang.edit_file, onClick: $.proxy(this, '_showProperties') });
					menuOptions.push({ label: Assets.lang.rename, onClick: $.proxy(this, '_renameFile') });
					menuOptions.push('-');
					menuOptions.push({ label: Assets.lang._delete, onClick: $.proxy(this, '_deleteFile') });
				}

				new Assets.ContextMenu($files, menuOptions);

				// hide the spinner
				this.$spinner.hide();

				// -------------------------------------------
				//  onAfterUpdateFiles callback
				//
					if (typeof this.options.onAfterUpdateFiles == 'function') {
						this.options.onAfterUpdateFiles();
					}
				//
				// -------------------------------------------

			}
		}, this), 'json');
	}
});


Assets.FileManager.defaultOptions = {
	mode:          'full',
	multiSelect:   true,
	kinds:         'any',
	disabledFiles: []
};


/**
 * Sheet
 */
Assets.Sheet = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function(options) {
		this.options = $.extend({}, Assets.Sheet.defaultOptions, options);
	},

	/**
	 * Load
	 */
	_load: function() {
		this.$sheet = $('<div class="assets-sheet" />').appendTo(document.body);

		var data = {
			multi: this.options.multiSelect ? 'y' : 'n'
		};

		if (this.options.filedirs == 'all') {
			data.filedirs = 'all';
		} else {
			for (var i = 0; i < this.options.filedirs.length; i++) {
				data['filedirs['+i+']'] = this.options.filedirs[i];
			}
		}

		this.$sheet.load(Assets.actions.build_sheet, data, $.proxy(function(responseText, textStatus, XMLHttpRequest) {

			// find dom nodes
			var $field = $('> .assets-fm', this.$sheet),
				$buttons = $('> .assets-fm-right > .assets-footer > .assets-fm-btns a', $field);

			this.$cancelBtn = $($buttons[0]);
			this.$selectBtn = $($buttons[1]);

			this.fileManager = new Assets.FileManager($field, {
				mode:                'select',
				multiSelect:         this.options.multiSelect,
				onBeforeUpdateFiles: $.proxy(this, '_onBeforeUpdateFiles'),
				onSelectionChange:   $.proxy(this, '_onSelectionChange'),
				onSelect:            $.proxy(this, '_onSelect'),
				kinds:               this.options.kinds,
				disabledFiles:       this.options.disabledFiles
			});

			// button events
			this.$cancelBtn.click($.proxy(this, 'hide'));
			this.$selectBtn.click($.proxy(this, '_onSelect'));

			// now show it
			this.show();

		}, this));
	},

	/**
	 * On Before Update Files
	 */
	_onBeforeUpdateFiles: function() {
		this.$selectBtn.addClass('assets-disabled');
	},

	/**
	 * On Selection Change
	 */
	_onSelectionChange: function() {
		if (this.fileManager.fileSelect.getTotalSelected()) {
			this.$selectBtn.removeClass('assets-disabled');
		} else {
			this.$selectBtn.addClass('assets-disabled');
		}
	},

	/**
	 * On Select
	 */
	_onSelect: function() {
		// ignore if nothing is selected
		if (! this.fileManager.fileSelect.getTotalSelected()) return;

		var $files = this.fileManager.fileSelect.getSelectedItems(),
			files = [];

		for (var i = 0; i < $files.length; i++) {
			var $file = $($files[i]);
			files.push({
				path: $file.attr('data-file-path'),
				url:  $file.attr('data-file-url')
			});
		}

		this.options.onSelect(files);

		this.hide();
	},

	/**
	 * Show
	 */
	show: function(options) {
		$.extend(this.options, options);

		// showing for the first time?
		if (! this.$sheet) {
			this._load();

			// _load() calls show() once everything is ready to go
			return;
		}

		// update the list of disabled files
		var updateFiles = false;

		if (this.fileManager.options.disabledFiles.length != this.options.disabledFiles.length) {
			updateFiles = true;
		} else {
			for (var i = 0; i < this.fileManager.options.disabledFiles.length; i++) {
				if (this.fileManager.options.disabledFiles[i] != this.options.disabledFiles[i]) {
					updateFiles = true;
					break;
				}
			}
		}

		if (updateFiles) {
			this.fileManager.options.disabledFiles = this.options.disabledFiles;
			this.fileManager._updateFiles();
		}

		this.$sheet.show().animate({ top: 0 }, 300);
	},

	/**
	 * Hide
	 */
	hide: function() {
		this.$sheet.animate({ top: -358 }, 300, $.proxy(function() {
			this.$sheet.hide();
		}, this));
	}

});


Assets.Sheet.defaultOptions = {
	multiSelect:   false,
	filedirs:      'all',
	kinds:         'any',
	onSelect:      function(){},
	disabledFiles: []
};


})(jQuery);
