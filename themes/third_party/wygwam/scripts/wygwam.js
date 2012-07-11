(function($) {


window.Wygwam;


/**
 * Wygwam
 */
Wygwam = function(id, config, defer) {

	this.id = id;
	this.config = (Wygwam.configs[config] || Wygwam.configs['default']);
	this.defer = defer;

	if (this.defer) {
		this.showIframe();
	} else {
		this.initCKEditor();
	}
};


Wygwam.prototype = {

	/**
	 * Show Iframe
	 */
	showIframe: function() {
		var width = (this.config.width ? this.config.width.toString() : '100%'),
			height = (this.config.height ? this.config.height.toString() : '200'),
			css = (this.config.contentsCss ? this.config.contentsCss : Wygwam.themeUrl+'lib/ckeditor/contents.css'),
			$textarea = $('#'+this.id).hide();

		if (width.match(/\d$/)) width += 'px';
		if (height.match(/\d$/)) height += 'px';

		this.$iframe = $('<iframe class="wygwam" style="width:'+width+'; height:'+height+';" frameborder="0" />').insertAfter($textarea);

		var iDoc = this.$iframe[0].contentWindow.document,
			html = '<html>'
			     +   '<head>'
			     +     '<link rel="stylesheet" type="text/css" href="'+css+'" />'
			     +     '<style type="text/css">* { cursor: pointer !important; }</style>'
			     +   '</head>'
			     +   '<body>'
			     +     $textarea.val()
			     +   '</body>'
			     + '</html>';

		iDoc.open();
		iDoc.write(html);
		iDoc.close();

		$(iDoc).click($.proxy(this, 'initCKEditor'));
	},

	/**
	 * Init CKEditor
	 */
	initCKEditor: function() {
		if (this.$iframe) {
			this.$iframe.remove();
		}

		CKEDITOR.replace(this.id, this.config);
	}
}


Wygwam.configs = {};


/**
 * Load Assets Sheet
 */
Wygwam.loadAssetsSheet = function(params, filedir, kind) {
	var sheet = new Assets.Sheet({
		filedirs: (filedir == 'all' ? filedir : [filedir]),
		kinds: (kind == 'any' ? kind : [kind]),

		onSelect: function(files) {
			CKEDITOR.tools.callFunction(params.CKEditorFuncNum, files[0].url);
		}
	});

	sheet.show();
};


/**
 * Load EE File Browser
 */
Wygwam.loadEEFileBrowser = function(params, directory, content_type) {
	var $trigger = $('<trigger />');

	if (Wygwam.ee2plus) {

		$.ee_filebrowser.add_trigger($trigger, 'userfile', {
			directory: directory,
			content_type: content_type
		}, function(file) {
			var url = Wygwam.filedirUrls[file.upload_location_id] + file.file_name;
			CKEDITOR.tools.callFunction(params.CKEditorFuncNum, url);
		});

	} else {

		$.ee_filebrowser.add_trigger($trigger, 'userfile', function(file) {
			var url = Wygwam.filedirUrls[file.directory] + file.name;
			CKEDITOR.tools.callFunction(params.CKEditorFuncNum, url);
		});

	}

	$trigger.click();
};


})(jQuery);
