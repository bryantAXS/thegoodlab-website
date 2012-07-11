(function($) {


Assets.Field.matrixConfs = {};


Matrix.bind('assets', 'display', function(cell){
	var $field = $('.assets-field', this);

	// ignore if we can't find that field
	if (! $field.length) return;

	var fieldName = cell.field.id+'['+cell.row.id+']['+cell.col.id+']';

	cell.assetsField = new Assets.Field($field, fieldName, Assets.Field.matrixConfs[cell.col.id]);
});


})(jQuery);
