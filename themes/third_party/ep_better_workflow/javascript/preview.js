(function ($, undefined) {
'use strict';


  /**
   * The preview box for previewing entries directly from within the EE entry edit form
   *
   * transition - an instance of Bwf.EditorTransition or Bwf.PublisherTransition
   *
   */
  function PreviewBox (transition) {
    this.transition = transition;
    this.height = $(window).height() - 100;
    this.width = $(window).width() - 100;
    this.iframeHeight = this.height - 60;
    this.iframeWidth = this.width - 30;
    this.authTokenId;
  }

  PreviewBox.prototype = {
    render: function () {
      this._prepareDOM();
      this._appendButton();
    },

    _prepareDOM: function () {
      this._appendPreviewDialog();
    },

   /*
    * Gets the value of the 'entry_id' query parameters from the document URL
    *
    *
    * returns a URL string.
    *
    */
    _getEntryId: function () {
      var match = document.location.search.match(/entry_id=([0-9]+)/); 
      if (match && match[1]) {
        return match[1];
      }
    },


   /*
    * Perform UI updates when preview is closed
    *
    * Clear the HTML from the preview holder
    * Update DOM elements in the publish form so previewing again doesn't break things
    *
    * returns nothing
    */
    _previewDialogClose: function () {
      $("#EpBwf_preview_dialog").empty();
      this._updatePublishView();
    },


    _appendPreviewDialog: function () {
      var previewWindow = $("<div id='EpBwf_preview_dialog'></div>"),
          self = this;

      jQuery(previewWindow).insertAfter('#footer');

      //Prepare the preview DOM element for preview
      $("#EpBwf_preview_dialog").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        position: "center",
        title: "Better Workflow Preview",
        top: 100,
        height: this.height,
        width: this.width,
        close: function() {
          self._previewDialogClose();
        }
      });
    },


    // Serialize the Form data appending the correct status action
    // There can only be one selector at any time, 
    _serializeForm: function () {
      var formData = $('#publishForm').serialize(),
          selectors = ['epBwfEntry_save_as_draft','epBwfDraft_save_as_draft','epBwfDraft_update_draft','epBwfEntry_revert_to_draft','epBwfDraft_revert_to_draft'],
          selector,
          buttonName,
          buttonValue,
          updateButtonValue;

      $.each(selectors, function(index, item) {
         selector = 'button[name="'+item+'"]';
         if ($(selector).length) {

           // Save the current button value
           buttonValue = $(selector).val();
           buttonName = item;
          
           // Are we previewing a 'submitted' entry / draft we need some special behaviour
           if(item == 'epBwfEntry_revert_to_draft' || item == 'epBwfDraft_revert_to_draft')
           {
             // Are we previewing a draft 'entry'
             if(buttonValue == 'draft') {
               buttonName = 'epBwfEntry_submit_for_approval';
               buttonValue = 'submitted';
             } else {
               buttonName = 'epBwfDraft_submit_for_approval';
               buttonValue = 'submitted|update';
             }
           }
           else
           {
             // Whatever the button label was before, once you have previewed it will say 'update draft'
             //$(selector).text('Update draft');
             // UX update - change label on button which saves draft
             $(selector).text('Save and close');

             // If the old value was 'draft' keep this the same
             // in every other case, change the value to draft|update
             if(buttonValue == 'draft') {
               updateButtonValue = 'draft';
             } else {
               updateButtonValue = 'draft|update';
             }

             // Update the button value
             $(selector).val(updateButtonValue);
           }

           // Append the selected action to the form data
           formData += '&'+buttonName+'='+ buttonValue;

           //break the each loop
           return false;

         }

      });

      return formData;
    },

    /*
    *
    * Issues an ajax request to save the entry data when previewing an unsaved entry.
    *
    * isNewEntry - whether or not this is a new entry.
    * callback   - A callback function to be executed after.
    *
    * returns nothing.
    */
    _saveEntryForPreview: function (isNewEntry, callback) {

      var self = this,
      
      isNewEntry = isNewEntry || false;

      // Call all compatibility methods
      // These have to be called always before _serializeForm();
      this._cmpEpEditor();
      this._cmpwygWam();
      this._cmpMatrix();

      // Serialise the form data
      var formAction = $('#publishForm').attr('action'),
          formData = this._serializeForm(),
          newEntryId;

      if (isNewEntry) {
        formData += '&bwf_ajax_new_entry=t';
      }

      // Fire off the AJAX to save the entry
      $.ajax({
        url: formAction,
        type: 'post',
        data: formData,
        context: $('#EpBwf_preview_dialog'),
        beforeSend: function() {
          $(this).append('<div id="epBwf_preview_spinner">&nbsp;</div>');
        },

        success: function(data) {
          // Check we have received valid JSON
          // If an error occured while trying to save the entry we're get a whole bunch of HTML
          if(self._isValidJSON(data))
          {
            $('#epBwf_preview_spinner').remove();
            data = data.length > 0 ? $.parseJSON(data) : null;
            newEntryId = data ? data.new_entry_id : null;

            if (newEntryId) {
              // append the entry_id to the form action url
              $('input[name="entry_id"]').val(newEntryId);
            }

            callback(newEntryId);
          }
          else
          {
            // We have an error in the entry we're trying to save
            // We need to unbind the close behaviour so we do not update the publish page's DOM
            // We then need to rebind the event so they can preview properly once the problem has been resolved
            // Close the preview window and report a problem
            $("#EpBwf_preview_dialog").empty();
            $("#EpBwf_preview_dialog").dialog('option', 'close', null)
            $("#EpBwf_preview_dialog").dialog("close");
            $("#EpBwf_preview_dialog").dialog('option', 'close', function() { self._previewDialogClose(); })
            alert("Whoops! It looks like there's a problem.\n\nBefore you can 'Save and preview' an entry all required fields must be completed.\n\nPlease check the form and try again.");
          }
        }
      });

    },


   /*
    * Replaces the default preview dialog's iframe with another one containing
    * the page to be previewed.
    *
    * entryId     - The id of entry to be previewed.
    * buttonValue - A reference to the preview button value.
    *
    * returns nothing.
    */
    _appendIframe: function (newEntryId, structurePreviewUrl, buttonValue) {
      var isStructureURL = false;

      if (newEntryId) {
        buttonValue = buttonValue.replace(/undefined/g, newEntryId);
      }

      if (structurePreviewUrl) {
        buttonValue = buttonValue.replace(/\/index[^?]*/,'/index.php' + structurePreviewUrl);
        isStructureURL = true;
      }

      $('#EpBwf_preview_iframe').remove();

      // Now set an auth token, append it to the URL and launch the preview
      this._setAuthToken(buttonValue, isStructureURL, this.iframeHeight, this.iframeWidth, function (previewHeight, previewWidth, previewURL) {$("#EpBwf_preview_dialog")
        .append('<iframe id="EpBwf_preview_iframe" height="' + previewHeight + '" width="' + previewWidth + '" frameborder="0" src="' + previewURL + '"></iframe>'
      )});

    },


   /*
    * Opens the preview window.
    *
    * buttonValue - The value of the preview button.
    *
    * returns nothing.
    */
    _openPreviewWindow: function (buttonValue) {
      var self = this,
          isNewEntry;

      //check that there is an entry title, don't do anything without one
      if($("input#title").val() == "") {
        alert("Please enter a title before you preview");
      }

      else {
        //open modal dialog
        $("#EpBwf_preview_dialog").dialog("open");

        // check whether we need to preview this entry using Structure or using the normal method.
        this._cmpStructure( function (structurePreviewUrl) {
          isNewEntry = /undefined$/.test(buttonValue);
          self._saveEntryForPreview(isNewEntry, function (newEntryId) {
            self._appendIframe(newEntryId, structurePreviewUrl, buttonValue);
          });
        });
      }
    },


   /*
    * Sets an auth token in the database.
    *
    * returns ID of auth token.
    */
    _setAuthToken: function(buttonValue, isStructureURL, previewHeight, previewWidth, launchPreviewFunc) {
      var self = this,
      ajaxUrl = $('#publishForm').attr('action') + '&ajax_set_auth_token',
      humanReadableUrl;
      
      
      $.getJSON(ajaxUrl, function(data)
      {
        //Set the object's property so we can delete the token
        self.authTokenId = data.token_id;
        
        buttonValue = buttonValue + "&ep_bwf_token_id=" + data.token_id + "&ep_bwf_token=" + data.token;
        
        // Strip away the querystring to give a human readable preview URL and append this to the dialog title
        humanReadableUrl = buttonValue.split('?')[0];
        $("#EpBwf_preview_dialog").dialog('option', 'title', 'Better Workflow Preview [URL: ' +humanReadableUrl  + ']');
        
        launchPreviewFunc(previewHeight, previewWidth, buttonValue);
        
      });
    },


   /*
    * Deleted the auth token from the database.
    *
    * returns nothing.
    */
    _deleteAuthToken: function() {
      var ajaxUrl = $('#publishForm').attr('action') + '&ajax_delete_auth_token',
      formData = 'token_id=' + this.authTokenId;
      $.ajax({
        url: ajaxUrl,
        type: 'post',
        data: formData,
        success: function(data) {
          //console.log(data);
        }
      });
    },


   /*
    *
    * Makes the necessary changes to the publish form DOM so that
    * that the form will reflect the state of the update entry record in the DB.
    *
    * returns nothing.
    *
    */
    _updatePublishView: function () {
      //console.log("UI Update");
      this._deleteAuthToken();
      this._updateWorkflowUI();
      this._updateMatrixFields();
    },


    /*
    * Makes the necessary changes so that the matrix input fields relect the state 
    * of the matrix field in the DB.
    *
    * returns nothing.
    */
    _updateMatrixFields: function () {
      var url = document.location.href;

      if (! /&entry_id/.test(url)) {
        url = url + "&entry_id=" + $('input[name="entry_id"]').val();
      }

      jQuery.ajax({
        url: url,
        type: 'get',
        success: function (theHtml) {
          var fieldIds = [],
              dom,
              scriptTags,
              loadEvent;

          $(theHtml).find('.publish_matrix').each(function(index, matrixInput) {
            fieldIds.push(
              $(matrixInput).find('div.matrix').attr('id')
            );
            $('#'+ $(matrixInput).attr('id')).html($(matrixInput).html());

          });

          if(fieldIds.length) {
            dom = document.createElement('div');
            dom.innerHTML = theHtml;

            // Search the DOM obj for script elements
            scriptTags = dom.getElementsByTagName('script');

            for ( var i=0; i < scriptTags.length; i++) {
              if (scriptTags[i].innerHTML !== '' ) {
                // If we can find one of our field_ids in the innerHTML of this script block; we need to re-fire it
                for(var j=0; j < fieldIds.length; j++) {

                  if(scriptTags[i].innerHTML.indexOf(fieldIds[j]) != -1) {

                    loadEvent = scriptTags[i].innerHTML.substring(34, scriptTags[i].innerHTML.length-3);
                     
                    // I know, I know, it's horrible and kittens scream. I just couldn't figure out another way of re-invoking these
                    eval(loadEvent);
                  }
                }
              }
            }
          }

        }
      });

    },

   /*
    * Makes sure that preview button value is updated and refers to the newly created entry id rather than to undefinde.
    *
    *
    */
    _updateWorkflowUI: function () {
      $('.bwf_control_unit').each(function() {
        // Find the preview button and make sure this has the entry if
        $(this).find('.bwf_grey_button').each(function() {
          var thisEntryId = $('input[name="entry_id"]').val();
          var newPreviewUrl = $(this).val().replace(/undefined/g, thisEntryId);
          $(this).val(newPreviewUrl);
        });
        
        // After *every* preview the entry or draft is put into 'draft' status, so make sure the UI is correctly styled
        // This also applies to a 'closed' (archived) entry
        if($(this).hasClass('closed'))
        {
          var controlUnit = $(this);
          controlUnit.removeClass('closed').addClass('draft');
          controlUnit.children('h3').html('DRAFT');
        }
        if($(this).hasClass('open'))
        {
          var controlUnit = $(this),
              previewButton = $(this).find('ul li:last'),
              publishButton = $(this).find('button.submit'),
              discardDraftButton = $('<button></button>'),
              listItem = $('<li></li>');
          
          controlUnit.removeClass('open').addClass('draft');
          controlUnit.children('h3').html('DRAFT (HAS LIVE VERSION)');
          
          // Remove the 'Archive' button
          controlUnit.find('.bwf_blue_button').each(function() {
            $(this).parent().remove();
          });
          
          // Check and update the publish button
          if (publishButton.attr('name') == 'epBwfEntry_publish') {
            publishButton.attr('name','epBwfDraft_publish');
          }
          if (publishButton.val() == 'open') {
            publishButton.val('open|replace');
          }
          
          // Append the discard draft button (Needs to have the confirmation event attached)
          //discardDraftButton.attr({value:"null|delete", class:"bwf_blue_button", type:"submit"});
          discardDraftButton.val("null|delete");
          discardDraftButton.addClass("bwf_blue_button");
          discardDraftButton.attr('type', "submit");
          discardDraftButton.html('Discard draft');
          discardDraftButton.click( function(event) {
            if(confirm("Are you sure you want to delete this draft? This action can not be undone.")) {
              $('#publishForm').prepend('<input type="hidden" name="epBwfDraft_discard_draft" id="epBwfDraft_discard_draft" value="true" />');
              $('#publishForm').submit();
            }
          });
          listItem.append(discardDraftButton)
          previewButton.before(listItem);

        }
      });
    },


    /*
    * Appends the preview button to the Workflow widget container
    *
    * returns nothing.
    */
    _appendButton: function () {
      var transition = this.transition,
          workflowStatus = transition.draftStatus || transition.entryStatus || 'draft',
          previewEntryId = this._getEntryId(),
          draftTemplate = transition.draftTemplate,
          self = this;

      //preview template settings
      if (draftTemplate.substring(draftTemplate.length-1) == "/"){
        draftTemplate = draftTemplate + "undefined";
      }

      // We want the preview button in almost all cases, however if this is an editor and the draft is submitted, don't display it.
      if (workflowStatus === 'submitted' && transition.userRole === 'editor') {
        return; //this exits from the function
      }


      //draft template should either end with a numeric id or with undefined
      if (!/(\d+)$/.test(transition.draftTemplate)) {
        transition.draftTemplate += 'undefined';
      }

      $('.bwf_control_unit ul').
          append('<li><button class="bwf_grey_button" value="' + transition.draftTemplate +
                 '?ep_bwf_draftpreview=t&ep_bwf_entry_id=' + previewEntryId + '">Save and preview</button>&nbsp;&nbsp;|</li>'
          );

      // This is where we append the click event to the preview button **Super Important**
      $('.bwf_grey_button').click(function (event) {
        self._openPreviewWindow($(this).val(), self.previewIframeHeight, self.previewIframeWidth);
        event.preventDefault();
      });
     },

    /*
     * Compatibility method for Structure.
     *
     * Checks if an entry has a structure url and and if so passes that to the
     * continueCallback function.
     *
     */
     _cmpStructure: function (continueCallback) {
		
         var channelId = $('input[name="channel_id"]').val(),
             entryId = $('input[name="entry_id"]').val(),
             structurePreviewUrl;
         
         if (!entryId) {
           continueCallback();
         }
         else {
           structurePreviewUrl = $('#publishForm').attr('action') + '&ajax_structure_get_entry_url&channel_id='+channelId+'&entry_id='+entryId;
      	   
           $.getJSON(structurePreviewUrl, function(data) {
             continueCallback(data.structure_url);
           });
         }
     },


     _cmpwygWam: function () {
       // Get all regular, full Wygwam fields
       $('.cke_editor iframe').each(function(i,item) {
         var fieldId = $(item).parent().attr('id').replace(/cke_contents_/,'');
         var fieldValue = $(item).get(0).contentDocument.body.innerHTML;
         $('#'+fieldId).val(fieldValue);
       });

       // Get all wygwam fields placed inside a Matrix row
       $('td.matrix td[id^="cke_contents_"] iframe').each(function(i,item) {
         var matrixParentCell = $(this).parents('td.matrix');
         var fieldId = matrixParentCell.children('textarea').attr('id').replace(/cke_contents_/,'');
         var fieldValue = $(item).get(0).contentDocument.body.innerHTML;
         $('#'+fieldId).val(fieldValue);
       });
     },

     _cmpMatrix: function () {	
       $('.publish_matrix').each(function(i,item) {
         // Count the inputs, if we don't have any add a blank input input, so we trigger our matrix model
         var mtxInputs = jQuery(item).find(":input");
         if (mtxInputs.length == 0) {
           var fieldId = jQuery(item).find("div.matrix").attr("id");
           jQuery(item).append('<input type="hidden" name="'+ fieldId + '" id="' + fieldId + '" value="" />');
         }
       });
     },

     _cmpEpEditor: function () {
       $('.epEditorContent').each(function(i,item) {
        var editorId = jQuery(item).attr('id'),
            fieldId,
            fieldValue;

        // Check for the different version of the epEditor - older ones don't use the underscore
        if (editorId.indexOf("_epEditorIFrame") != -1) {
          fieldId = editorId.replace(/_epEditorIFrame/,'');
        } else {
          fieldId = editorId.replace(/epEditorIFrame/,'');
        }

        fieldValue = jQuery(item).get(0).contentWindow.document.body.innerHTML;
        $('#'+fieldId).val(fieldValue);
      });
     },
     
     
     _isValidJSON: function(value) {
       try {
         jQuery.parseJSON(value);
         return true;
       } catch(e) {
         return false;
       }
     }


  };



  this.PreviewBox = PreviewBox;

}).call(Bwf, jQuery, undefined);
