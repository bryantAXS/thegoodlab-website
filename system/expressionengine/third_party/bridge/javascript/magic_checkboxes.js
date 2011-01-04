<script type="text/javascript"> 

var lastCheckedBox = '';

function create_magic_checkboxes(table_id)
{
	if (typeof(table_id) == 'undefined')
	{
		var pageTables = document.getElementsByTagName("table");
		
		for (var j = 0; j < pageTables.length; j++)
		{
			if (pageTables[j].className.indexOf('magic_checkbox_table') > -1 ||
				pageTables[j].className.indexOf('magicCheckboxTable') > -1)
			{
				create_magic_checkboxes(pageTables[j]);
			}
		}		
		
		return;
	}
	else if (typeof(table_id) == 'object')
	{
		var listTable = table_id;
	}
	else if (typeof(table_id) == 'string')
	{
		if ( ! document.getElementById(table_id)) return;
		
		var listTable = document.getElementById(table_id);
	}
	else
	{
		return;
	}
	
	var listTRs = listTable.getElementsByTagName("tr");
	
	for (var j = 0; j < listTRs.length; j++)
	{
		for (var m = 0; m < 2; m++)
		{
			var theTag = (m == 1) ? 'th' : 'td';
			
			var elements = listTRs[j].getElementsByTagName(theTag);
			
			for ( var i = 0; i < elements.length; i++ )
			{
				elements[i].onclick = function (e)
				{
					e = (e) ? e : ((window.event) ? window.event : "")
					var element = e.target || e.srcElement;
					var tag = element.tagName ? element.tagName.toLowerCase() : null;
					
					// Last chance
					if (tag == null)
					{
						element = element.parentNode;
						tag = element.tagName ? element.tagName.toLowerCase() : null;
					}

					if (tag != 'a' && tag != null)
					{
						while (element.tagName.toLowerCase() != 'tr')
						{
							element = element.parentNode;
							if (element.tagName.toLowerCase() == 'a') return;
						}
						
						var theTDs = element.getElementsByTagName(theTag);
						var theInputs = element.getElementsByTagName("input");
						var entryID = false;
						var toggleFlag = false;
						
						for ( var k = 0; k < theInputs.length; k++ )
						{
							if (theInputs[k].type == "checkbox")
							{
								if (theInputs[k].name == 'toggle_all_checkboxes')
								{
									toggleFlag = true;
								}
								else
								{
									entryID = theInputs[k].id;
								}
								
								break;
							}
						}
						
						if (entryID == false && toggleFlag == false) return;
						
						// Select All Checkbox
						if (toggleFlag == true)
						{
							if (tag != 'input')
							{
								return;
							}
							
							if (theInputs[k].checked) 
							{
							   selectAllVal = true;
							}
							else
							{
							   selectAllVal = false;
							}
							
							var listTRs	  = listTable.getElementsByTagName("tr");
							var theInputs = listTable.getElementsByTagName("input");
										
							for ( var k = 0; k < theInputs.length; k++ )
							{
								if (theInputs[k].type == "checkbox")
								{
									theInputs[k].checked = selectAllVal;
								}
							}

							for (var j = 1; j < listTRs.length; j++)
							{
								var elements = listTRs[j].getElementsByTagName(theTag);

								for ( var t = 0; t < elements.length; t++ )
								{
									if (selectAllVal == true)
									{
										elements[t].className = (elements[t].className.indexOf('tableCellOne') > -1) ? 'tableCellOneHover' : 'tableCellTwoHover';
									}
									else
									{
										elements[t].className = (elements[t].className.indexOf('tableCellTwo') > -1) ? 'tableCellTwo' : 'tableCellOne';
									}
								}
							}
						}
						else
						{
							if (tag != 'input')
							{
								document.getElementById(entryID).checked = (document.getElementById(entryID).checked ? false : true);
							}
							
							// Unselect any empty selections on the screen
							
							if (window.getSelection || (document.selection && document.selection.createRange))
							{
								var txt = window.getSelection ? window.getSelection().toString() : document.selection.createRange().text;
								
								if (txt != '' && txt.replace(/<\/?[^>]+(>|$)/g, '').replace(/\s*/g,'') == '')
								{
									if (document.getSelection) { window.getSelection().removeAllRanges(); }
									else if (document.selection) { document.selection.empty(); }
									else { document.getElementById(entryID).focus(); }
								}
							}
							
							for ( var t = 0; t < theTDs.length; t++ )
							{
								if (document.getElementById(entryID).checked == true)
								{
									theTDs[t].className = (theTDs[t].className.indexOf('tableCellTwo') > -1) ? 'tableCellTwoHover' : 'tableCellOneHover';
								}
								else
								{
									theTDs[t].className = (theTDs[t].className.indexOf('tableCellOne') > -1) ? 'tableCellOne' : 'tableCellTwo';
								}
							}
						
							if (e.shiftKey && lastCheckedBox != '')
							{
								shift_magic_checkbox(document.getElementById(entryID).checked, lastCheckedBox, element);
							}
						
							lastCheckedBox = element;
						}
					}
				}
			}
		}
	}
}	


function shift_magic_checkbox(whatSet, lastCheckedBox, current)
{
	var outerElement = current.parentNode;
	var outerTag = outerElement.tagName ? outerElement.tagName.toLowerCase() : null;
	
	if (outerTag == null)
	{
		outerElement = outerElement.parentNode;
		outerTag = outerElement.tagName ? outerElement.tagName.toLowerCase() : null;
	}
	
	if (outerTag != null)
	{
		while (outerElement.tagName.toLowerCase() != 'table')
		{
			outerElement = outerElement.parentNode;
		}
		
		var listTRs = outerElement.getElementsByTagName("tr");
	
		var start = false;
	
		for (var j = 1; j < listTRs.length; j++)
		{
			if (start == false && listTRs[j] != lastCheckedBox && listTRs[j] != current)
			{
				continue;
			}
			
			for (var m = 0; m < 2; m++)
			{
				var theTag = (m == 1) ? 'th' : 'td';
				
				var listTDs = listTRs[j].getElementsByTagName(theTag);
				var listInputs = listTRs[j].getElementsByTagName("input");
				var entryID = false;
				
				for ( var k = 0; k < listInputs.length; k++ )
				{
					if (listInputs[k].type == "checkbox")
					{
						entryID = listInputs[k].id;
					}
				}
											
				if (entryID == false || entryID == '') return;
				
				document.getElementById(entryID).checked = whatSet;
				
				for ( var t = 0; t < listTDs.length; t++ )
				{
					if (whatSet == true)
					{
						listTDs[t].className = (listTDs[t].className.indexOf('tableCellTwo') > -1) ? 'tableCellTwoHover' : 'tableCellOneHover';
					}
					else
					{
						listTDs[t].className = (listTDs[t].className.indexOf('tableCellOne') > -1) ? 'tableCellOne' : 'tableCellTwo';
					}
				}
				
				if (listTRs[j] == lastCheckedBox || listTRs[j] == current)
				{
					if (start == true) break;
					if (start == false) start = true;
				}
			}
		}
	}
}
   
</script>
