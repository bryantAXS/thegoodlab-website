(function($) {


// define the Assets global
if (typeof window.Assets == 'undefined') window.Assets = {};


Assets.onAllFiledirsChange = function(all) {
	var $all = $(all),
		$others = $('input', $all.parent().parent().next());

	$others.attr({
		checked:  true,
		disabled: !!$all.attr('checked')
	});
};


$('.assets-view').each(function() {
	var $thumbs = $('.assets-view-thumbs', this),
		$list = $('.assets-view-list', this),
		$showColsTr = $(this).parent().parent().next().hide();

	$thumbs.change(function() {
		$showColsTr.hide();
	});

	$list.change(function() {
		$showColsTr.show();
	});
});


})(jQuery);
