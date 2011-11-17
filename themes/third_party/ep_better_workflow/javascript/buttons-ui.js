(function () {
'use strict';

  var $ = jQuery;

 /*
  * Public:
  *
  * Constructors a set of buttons from a Workflow stateTransition
  *
  * transition - an instance of EditorTransition or PublisherTransition
  *
  *
  * returns nothing.
  */

  var ButtonsUI = function (transition) {
        this.transition = transition;
      };

      ButtonsUI.prototype = {

       /*
        * Public:
        *
        * Renders the buttons into the the page dom.
        *
        *
        * returns nothing.
        */
        render: function () {
          this._prepareDom();
          this._appendButtons();

          if (! this.transition.formIsEditable  ) {
            $('#holder').prepend("<div id='bwf_locked_publish'>&nbsp;</div>");
          }
        },

        /*
         * Appends the buttons to the Better Workflow button containers.
         *
         * returns nothing.
         *
         */

         _appendButtons: function () {
           var optionTypes = ['entryOptions', 'draftOptions'],
               transition = this.transition,
               self = this;


           $.each(optionTypes, function (typeIndex, optionType) {
             var options = transition[optionType].reverse();

             $.each(options, function (optionIndex, option) {
               var btnValue = self._buttonValue(option.status, option.dbOperation),
                   btnClass = self._buttonClass(btnValue, option.dbOperation),
                   btnName =  (option.dbOperation ?  'epBwfDraft' : 'epBwfEntry') + '_' + option.btnName ,
                   btnContent = option.btnName.replace(/_/g,' '),
                   btnLabel = btnContent.charAt(0).toUpperCase() + btnContent.substr(1);
                   
                   // UX update - change label on button which saves draft
                   if(btnLabel == 'Save as draft') btnLabel = 'Save and close';
                   if(btnLabel == 'Update draft') btnLabel = 'Save and close';
               
               $(".bwf_control_unit ul").append(
                 "<li><button type='submit' class='" + btnClass + "' name='" + btnName + "' value='" + btnValue +"'>" + btnLabel + "</button></li>" 
               );

               //bind click event to the buttons outside the form
               $('button[name="' + btnName + '"]').eq(0).click( function() {
                 $('button[name="' + $(this).attr('name') + '"]').get(1).click();
               });

               //bind confirm dialog to delete buttons

               if (option.dbOperation == 'delete' ) {
                 $("#publishForm button[name='" + btnName + "']").click( function(event) {
                   self.confirmDelete(event);
                 });
               }


             });
           });

         },

         /*
          * Prepares the EE entry edit DOM for injecting Better Workflow UI elements.
          *
          *
          * returns nothing.
          *
          */
          _prepareDom: function () {
            this._hideStatusSelectInput();
            this._removeDefaultSubmit();
            this._insertButtonContainer();
          },

         /*
          * Hides EE's default select input for changing an entry state.
          *
          * returns nothing.
          *
          */

          _hideStatusSelectInput: function () {
            $('select[name=status]').attr('disabled','disabled');
            $('#hold_field_status').hide();
          },

         /*
          * Removes the default submit button from the entry editing form.
          *
          *
          * returns nothing
          */
          _removeDefaultSubmit: function () {
            $("#publish_submit_buttons").remove();
          },

         /*
          * Adds to the DOM the two containers elements wherein the Workflow buttons will be appended.
          *
          *
          * returns nothing.
          */
          _insertButtonContainer: function () {
            var container = $("<div class='bwf_control_unit " + this._statusClassName() + 
                              "' ><ul></ul><h3>" + this._statusFullName() + "</h3></div>"
            );

            $($(container).clone()).insertAfter('.heading');
            //jQuery('#holder').append(container);
            $(container).insertAfter('#holder');

          },


        /*
         * Gets a button's value on the basis of the transition data.
         *
         * status      - the current entry status.
         * dbOperation - the value of this.transition.options[{draft|entry}].dbOperation.
         *
         * A string to be used as the button value.
         *
         */
         _buttonValue: function (status, dbOperation) {

           var btnValue = status;

           if (typeof dbOperation !== 'undefined') {
             btnValue += "|" + dbOperation;
           }

           return btnValue;
         },

        /*
         * Gets a button classname.
         *
         * btnValue    - The value attribute of the button.
         * dbOperation - The value of this.transition.options[{draft|entry}].dbOperation.
         *
         *
         */

         _buttonClass: function (btnValue, dbOperation) {
           var btnClass = dbOperation ?  'epBwfDraft' : 'epBwfEntry';

           if( btnValue == 'draft|create' || btnValue == 'draft' || btnValue == 'draft|update' ) {
             btnClass = 'bwf_red_button';
           }
           else if (btnValue == 'submitted' || btnValue == 'submitted|create' || btnValue == 'submitted|update' || btnValue == 'open|replace' || btnValue == 'open' ) {
             btnClass = 'submit';
           }
           else if (btnValue == 'null|delete' || btnValue === 'closed') { 
             btnClass = 'bwf_blue_button';
           }

           return btnClass;
         },


         /*
          * Gets a classname for this entry's status.
          *
          * returns a string.
          *
          */

          _statusClassName: function () {
            return this.transition.draftStatus || this.transition.entryStatus || 'draft';
          },


         /*
          * Gets a human readable string for this entry's status.
          *
          * returns a string suitable to be used in button labels.
          *
          */

          _statusFullName : function () {
            var workflowStatus = this._statusClassName();
            var statusFullName = "";

           if (workflowStatus === 'submitted') {
             statusFullName = 'SUBMITTED FOR APPROVAL';
           }
           else if (workflowStatus == 'open'){
             statusFullName = 'LIVE';
           }
           else {
             statusFullName = workflowStatus.toUpperCase() ;
           }
           
           if(this.transition.draftStatus){
           	statusFullName = statusFullName + " (HAS LIVE VERSION)";
           }
           
           return statusFullName
          },

         /*
          *
          * Event handler for adding a confirm dialog to delete buttons.
          *
          * event - a blur event
          *
          */
          confirmDelete: function (event) {
            var go_ahead = confirm("Are you sure you want to delete this draft? This action can not be undone.");

            if (go_ahead) {
              return;
            }

            else {
             event.preventDefault();
            }
          }
        };


      this.ButtonsUI = ButtonsUI;

}).call(Bwf);
