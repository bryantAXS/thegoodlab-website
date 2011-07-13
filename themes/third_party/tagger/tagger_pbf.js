// ********************************************************************************* //
var Tagger = Tagger ? Tagger : new Object();
Tagger.prototype = {}; // Get Outline Going
//********************************************************************************* //

jQuery(document).ready(function(){
	
	Tagger.TaggerField = jQuery('.TaggerField');
	Tagger.FieldID = Tagger.TaggerField.attr('rel');
	
	// Single Input Mode?
	if (Tagger.TaggerField.find('.SingleTagsInput').length > 0){
		
		Tagger.TaggerField.find('#SingleTagsInput_'+Tagger.FieldID).tagsInput({
			width:'98%',
			height:'',
			delimiter: '||',
			autocomplete_url: Tagger.AJAX_URL + '&ajax_method=tag_search',
			autocomplete: {autoFocus:true, open:function(){ Tagger.TaggerField.find('#SingleTagsInput_'+Tagger.FieldID+'_tag').autocomplete("widget").width(300).addClass('DDTaggerAC') }},			
			unique:true
		});
		
	}
	else {		
		Tagger.TaggerField.find('.InstantInsert').keypress(Tagger.InstantSearch);
		
		Tagger.TaggerField.find('.InstantInsert').autocomplete({
			source: Tagger.AJAX_URL + '&ajax_method=tag_search',
			autoFocus:true,
			open:function(){
				Tagger.TaggerField.find('.InstantInsert').autocomplete("widget").width(300).addClass('DDTaggerAC')
			},
			select: function(e, ui){
				Tagger.SaveTag(ui.item.value);
				setTimeout(function(){Tagger.TaggerField.find('.InstantInsert').val('')}, 100);
			}
		});
	}
	
	Tagger.TaggerField.find('.MostUsedTags .tag').click(Tagger.MostUsedTagger);
	Tagger.TaggerField.find('.AssignedTags .tag a').live('click', Tagger.DelTag);
	Tagger.TaggerField.find('.AssignedTags').sortable();
	
});


//********************************************************************************* //

Tagger.InstantSearch = function(event){
	if (event.which == 13)	{
		Tagger.SaveTag(event.target.value);
		jQuery(this).val('');
		return false;
	}
};

//********************************************************************************* //

Tagger.MostUsedTagger = function(Event){
	Event.preventDefault();
	Tagger.SaveTag( jQuery(this).find('span').html() );
}

//********************************************************************************* //

Tagger.SaveTag = function(tag){

	if (Tagger.TaggerField.find('.SingleTagsInput').length > 0){
		Tagger.TaggerField.find('#SingleTagsInput_'+Tagger.FieldID).addTag(tag);		
		return;
	}
	
	Tagger.TaggerField.find('.AssignedTags .NoTagsAssigned').hide();
	
	var dupe = false;
	Tagger.TaggerField.find('.AssignedTags .tag input').each(function(){ if (jQuery(this).val() == tag) dupe = true; });
	
	if (dupe == false)
	{
		var Tag = jQuery('<div class="tag">'+tag+'<input type="hidden" name="field_id_'+Tagger.FieldID+'[tags][]" value="'+tag+'"> <a href="#"></a>	</div>');
		Tagger.TaggerField.find('.AssignedTags br').before(Tag);
		//console.log(Tagger.TaggerField.find('.AssignedTags br'));
	}
	
	return;
};

//********************************************************************************* //

Tagger.DelTag = function()
{
	jQuery(this).closest('.tag').fadeOut('slow', function(){ jQuery(this).remove(); });
	return false;
};

//********************************************************************************* //
