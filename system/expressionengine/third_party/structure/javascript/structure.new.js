/// jquery.dataset v0.1.0 -- HTML5 dataset jQuery plugin
(function($){var PREFIX='data-',PATTERN=/^data\-(.*)$/;function dataset(name,value){if(value!==undefined){return this.attr(PREFIX+name,value)}switch(typeof name){case'string':return this.attr(PREFIX+name);case'object':return set_items.call(this,name);case'undefined':return get_items.call(this);default:throw'dataset: invalid argument '+name;}}function get_items(){return this.foldAttr(function(index,attr,result){var match=PATTERN.exec(this.name);if(match)result[match[1]]=this.value})}function set_items(items){for(var key in items){this.attr(PREFIX+key,items[key])}return this}function remove(name){if(typeof name=='string'){return this.removeAttr(PREFIX+name)}return remove_names(name)}function remove_names(obj){var idx,length=obj&&obj.length;if(length===undefined){for(idx in obj){this.removeAttr(PREFIX+idx)}}else{for(idx=0;idx<length;idx++){this.removeAttr(PREFIX+obj[idx])}}return this}$.fn.dataset=dataset;$.fn.removeDataset=remove_names})(jQuery);(function($){function each_attr(proc){if(this.length>0){$.each(this[0].attributes,proc)}return this}function fold_attr(proc,acc){return fold((this.length>0)&&this[0].attributes,proc,acc)}function fold(object,proc,acc){var length=object&&object.length;if(acc===undefined)acc={};if(!object)return acc;if(length!==undefined){for(var i=0,value=object[i];(i<length)&&(proc.call(value,i,value,acc)!==false);value=object[++i]){}}else{for(var name in object){if(proc.call(object[name],name,object[name],acc)===false)break}}return acc}function fold_jquery(proc,acc){if(acc===undefined)acc=[];return fold(this,proc,acc)}$.fn.eachAttr=each_attr;$.fn.foldAttr=fold_attr;$.fn.fold=fold_jquery;$.fold=fold})(jQuery);

$("#structure-ui .round").corner("3px");

$('.action-delete').click(function() {
	return confirm('Do you want to delete this page from Structure? The entry status will be set to "Closed" and removed from navigation.');
});

// Part of hidden config value for Add New Page link
$(".rightNav span.button a.submit:contains('Add Page')").addClass("action-add").attr("data-parent_id", "0");

$(".action-add").overlay({
	target: '.overlay',
	expose: {
		color: '#777',
		loadSpeed: 100,
		opacity: .5,
		closeSpeed: 0
	},
	speed: 100
});

$(".action-add").click(function(){
	// alert($(this).dataset('parent_id'));
	var parent_id = $(this).dataset('parent_id');
	
	$('#overlay_listing li a').each(function(){
		var string = $(this).attr('href');
		newstring = string +'&parent_id='+parent_id;
		
		$(this).attr('href', newstring);		
	});
});