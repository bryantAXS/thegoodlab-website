// Fix for IE6/7
if(!Array.indexOf) {
  Array.prototype.indexOf = function(obj){
    for(var i=0; i<this.length; i++){
      if(this[i]==obj){
        return i;
      }
    }
    return -1;
  };
}


(function ($, undefined) {
'use strict';

 /*
  * Observes the EE control panel's entry list table and adds workflow related information
  * to its rows.
  *
  * ajaxEndPoint - the URL of the ajax Endpoint from which Workflow entry data will be retrieved.
  *
  * returns nothing.
  */

  function EntryListObserver (ajaxEndPoint) {
    this.ajaxEndPoint = ajaxEndPoint;
    this.entryIds = [];
    this.entryIdsBefore = [];
  }

  EntryListObserver.prototype = {

   /*
    * Iterates over the table's rows and extract entry ids.
    *
    * returns an Array of entry ids.
    */

    _getEntryIds: function () {
      var entryIds = [];
      $('table.mainTable tr td:first-child').each(function () {
        entryIds.push($(this).text());
      });
      return entryIds;
    },


   /*
    * Checks whether or not the table has changed (this may happen as a result of the UI filters and search being used).
    *
    * returns a boolean.
    *
    */
    _tableHasChanged: function () {
      return this.entryIds.join(',') !== this.entryIdsBefore.join(',');
    },


    /*
     * Listens to all the ajax calls made through jQuery and injects Better Workflow metadata to the table whenever
     * whenever the EE control panel filters are triggered.
     *
     * (this is needed, otherwise the control panel will overrite our changes to the dom).
     *
     * returns nothing.
     *
     */
    observeFilters: function () {
      var self = this;
      $('body').ajaxComplete(function(event,request, settings){
        if (/D=cp&C=content_edit&M=edit_ajax_filter/.test(settings.url)) {
          self.entryIdsBefore = [];
          self._refreshWorkflowData();
        }
      });
      $('.paginate_button').click(function(){
        self._refreshWorkflowData();
      });
    },


   /*
    * The main program loop: whenever the table's content changes, refresh workflow releated
    * information from the server and adds that to the table rows.
    *
    *
    * returns nothing.
    */

    _refreshWorkflowData: function () {
	var self = this;
	var trElement;
	this.entryIds = this._getEntryIds();
	if (this._tableHasChanged()) {

		// Fetch from the ajax endpoint those entryIds that do have an associated workflow draft.
		$.post(this.ajaxEndPoint, {entryIds: this.entryIds}, function (data) {

			//add draft information to their corresponding rows in the table
			$.each(data.bwf_draft_data, function (index, entry) {
				self._addDraftInfo(entry);
			});

			// Add workflow information to all the other rows that correspond to non-draft entries.
			$.each(data.bwf_entry_ids, function (index, entry_id)
			{
				$("table.mainTable tbody tr").each(function(){
					trElement = $(this);
					if(trElement.find("td:first-child").html() == entry_id && !trElement.hasClass('bwfhasDraft'))
					{
						self._addNonDraftInfo(trElement);
					}
				});
			});
		});
	}
	this.entryIdsBefore = this.entryIds;
    },


   /*
    * Enriches an EE control panel's entry list table row with workflow related information.
    *
    * entryData - an entry record, as returned from the ajaxEndPoint.
    *
    *
    */
    _addDraftInfo: function (entryData) {

	//check the index of a given entry in the table rows
	var rowIndex = this.entryIds.indexOf(entryData.entry_id) + 1,
	statusColIndex,
	displayStatus,
	tr;

	if (rowIndex >= 1) {

		//get a table row by index
		tr = $('table.mainTable tbody tr:nth-child('+rowIndex+')');

		// Check we haven't already updated this row
		if(!$(tr).hasClass('bwfhasDraft'))
		{
			//disable checkboxes
			$(":checkbox",tr).attr('disabled','disabled');

			//find the index of the entry status column
			statusColIndex = $(tr).children('td').length - 1;

			//add classes to mark this entry has having a draft
			$(tr).addClass('bwfhasDraft');
			$(tr).children('td:nth-child(2)').addClass('bwf_edit_list_cell bwf_'+entryData.status+'_open_cell');

			//display a human readable string in the status column
			if (entryData.status == 'draft') {
				displayStatus = "Draft";
			}
			else if (entryData.status == 'submitted') {
				displayStatus = "Submitted for approval";
			}
			else {
				displayStatus = entryData.status;
			}

			$('<span>, </span><span class="status_'+entryData.status+'">' + displayStatus + '</span>').appendTo($(tr).children('td:nth-child(' + statusColIndex + ')'));
		}
	}
    },


   /*
    * Enriches an EE control panel's entry list table row with workflow related information.
    *
    * trElement - A table's row DOM element.
    *
    * returns nothing.
    */
    _addNonDraftInfo: function (trElement) {
      var statusColIndex = $(trElement).children('td').length - 1,
          displayStatus,
          status = $(trElement).children('td:nth-child(' + statusColIndex + ')')
                      .children('span').attr('class').replace(/status_/,'');

      if (status !== 'open' && status !== 'closed' ) {
        $(trElement).children('td:nth-child(2)').addClass('bwf_edit_list_cell bwf_'+status+'_cell'); 

        if(status == 'draft') {
          displayStatus = "Draft";
        } else if (status == 'submitted') {
          displayStatus = "Submitted for approval";
        } else {
          displayStatus = status;
        }

        $(trElement).children('td:nth-child(' + statusColIndex + ')')
          .html('<span class="status_' + status + '">' + displayStatus + '</span>');
      }

    }
  };

  this.EntryListObserver = EntryListObserver;

}).call(Bwf, jQuery, undefined);
