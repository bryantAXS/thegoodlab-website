/**
 * Assets Field
 *
 * @package Assets
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */


(function($) {


// define the Assets global
if (typeof window.Assets == 'undefined') window.Assets = {};


/**
 * Field
 */
Assets.Field = Assets.Class({

	/**
	 * Constructor
	 */
	__construct: function($field, fieldName, settings) {
		this.$field = $field;
		this.fieldName = fieldName;
		this.settings = settings;

		var $btns = this.$field.next();
		this.$addBtn = $('.assets-add', $btns);
		this.$removeBtn = $('.assets-remove', $btns);

		this.$container = $('#mainContent');
		this.originalContainerWidth = this.$container.width();
		this.originalFieldWidth = this.$field.width();

		this.filesView;
		this.fileSelect;
		this.filesSort;
		this.sheet;

		this.orderby;
		this.sort;

		this.orderFilesRequestId = 0;
		this.selectFilesRequestId = 0;

		this.$addBtn.click($.proxy(this, '_showSheet'));

		this.$removeBtn.click($.proxy(function() {
			// ignore if disabled
			if (this.$removeBtn.hasClass('assets-disabled')) return;

			var $files = this.settings.multi ? this.fileSelect.getSelectedItems() : this.filesView.getItems();
			this._removeFiles($files);
		}, this));

		if (this.settings.view == 'list') {
			// keep the field width in sync with its container width
			$(window).resize($.proxy(function() {
				var containerWidth = this.$container.width(),
					fieldWidth = this.originalFieldWidth + (containerWidth - this.originalContainerWidth);

				this.$field.width(fieldWidth);
			}, this));
		}

		this._initFilesView();
	},

	/**
	 * Initialize Files View
	 */
	_initFilesView: function() {
		// Initialize the Files View
		if (this.settings.view == 'thumbs') {
			this.filesView = new Assets.ThumbView($('> .assets-thumbview', this.$field));
		} else {
			this.filesView = new Assets.ListView($('> .assets-listview', this.$field), {
				orderby: this.orderby,
				sort: this.sort,
				onSortChange: $.proxy(function(orderby, sort) {
					this.orderby = orderby;
					this.sort = sort;

					this.orderFilesRequestId++;

					data = {
						requestId:  this.orderFilesRequestId,
						view:       this.settings.view,
						field_name: this.fieldName,
						orderby:    this.orderby,
						sort:       this.sort
					};

					for (var i = 0; i < this.settings.show_cols.length; i++) {
						data['show_cols['+i+']'] = this.settings.show_cols[i];
					}

					this.filesView.getItems().each(function(i) {
						data['files['+i+']'] = $(this).attr('data-file-path');
					});

					this.fileSelect.destroy();
					this.filesView.destroy();

					$.post(Assets.actions.get_ordered_files_view, data, $.proxy(function(data, textStatus) {
						if (textStatus == 'success') {
							// ignore if this isn't the current request
							if (data.requestId != this.orderFilesRequestId) return;

							// update the HTML
							this.$field.html(data.html);

							this._initFilesView();
						}
					}, this), 'json');
				}, this)
			});
		}

		// initialize the multiselect
		this.fileSelect = new Assets.Select(this.$field, {
			multi: true,
			onSelectionChange: $.proxy(function() {
				if (this.settings.multi) {
					// enable/disable buttons based on selection
					if (this.fileSelect.getTotalSelected()) {
						this.$removeBtn.removeClass('assets-disabled');
					} else {
						this.$removeBtn.addClass('assets-disabled');
					}
				}
			}, this)
		});

		// initialize the dragger
		this.filesSort = new Assets.Sort(this.filesView.getContainer(), {
			vertical: this.settings.view == 'list' ? true : false,
			filter: '.assets-selected',
			helper: $.proxy(this.filesView, 'setDragWrapper'),
			caboose: $.proxy(this.filesView, 'getDragCaboose'),
			insertion: $.proxy(this.filesView, 'getDragInsertion'),
			onSortChange: $.proxy(function() {
				this.fileSelect.reset();
				this.filesSort.reset();
				this.filesView.resetItems();
				this.fileSelect.addItems(this.filesView.getItems());
				this.filesSort.addItems(this.filesView.getItems());
			}, this)
		});

		// initialize the files
		var $files = this.filesView.getItems();
		this._initFiles($files);
	},

	/**
	 * Initialize Files
	 */
	_initFiles: function($files) {
		// ignore if no files
		if (! $files.length) return;

		// add them to the multi-select
		this.fileSelect.addItems($files);

		if (this.settings.multi) {
			// make them draggable
			this.filesSort.addItems($files);
		} else {
			this.$addBtn.addClass('assets-disabled');
			this.$removeBtn.removeClass('assets-disabled');
		}

		// show properties on double-click
		$files.dblclick($.proxy(this, '_showProperties'));

		// add the context menus
		new Assets.ContextMenu($files, [
			{ label: Assets.lang.view_file, onClick: $.proxy(this, '_viewFile') },
			{ label: Assets.lang.edit_file, onClick: $.proxy(this, '_showProperties') },
			'-',
			{ label: Assets.lang.remove_file, onClick: $.proxy(this, '_removeFiles') }
		]);
	},

	/**
	 * Show Sheet
	 */
	_showSheet: function() {
		if (! this.sheet) {
			this.sheet = new Assets.Sheet({
				multiSelect: this.settings.multi,
				filedirs:    this.settings.filedirs,
				onSelect:    $.proxy(this, '_selectFiles')
			});
		}

		// get currently selected files
		var selectedFiles = [],
			$selectedFiles = this.filesView.getItems();

		for (var i = 0; i < $selectedFiles.length; i++) {
			var filePath = $selectedFiles[i].getAttribute('data-file-path');
			selectedFiles.push(filePath);
		};

		this.sheet.show({
			disabledFiles: selectedFiles
		});
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
		this.propertiesHud = new Assets.Properties($(event.currentTarget));
	},

	/**
	 * Remove Files 
	 */
	_removeFiles: function($files) {
		if ($files.currentTarget) $files = $($files.currentTarget);

		this.fileSelect.removeItems($files);
		this.filesSort.removeItems($files);
		this.filesView.removeItems($files);

		if (! this.settings.multi) {
			this.$addBtn.removeClass('assets-disabled');
			this.$removeBtn.addClass('assets-disabled');
		}
	},

	/**
	 * Select Files
	 */
	_selectFiles: function(files) {
		this.selectFilesRequestId++;

		var data = {
			requestId:  this.selectFilesRequestId,
			view:       this.settings.view,
			prev_total: this.filesView.totalFiles,
			field_name: this.fieldName
		};

		if (this.settings.view == 'list') {
			// pass the show_cols setting
			for (var i = 0; i < this.settings.show_cols.length; i++) {
				data['show_cols['+i+']'] = this.settings.show_cols[i];
			}
		}

		for (var i = 0; i < files.length; i++) {
			data['files['+i+']'] = files[i].path;
		}

		$.post(Assets.actions.get_selected_files, data, $.proxy(function(data, textStatus) {
			if (textStatus == 'success') {
				// ignore if this isn't the current request
				if (data.requestId != this.selectFilesRequestId) return;

				// initialize the files
				var $files = $(data.html);

				if (this.settings.view == 'list') {
					$files = $files.filter('tr');
				} else {
					$files = $files.filter('li');
				}

				this.filesView.addItems($files);
				this._initFiles($files);
			}
		}, this), 'json');
	}
});


})(jQuery);
