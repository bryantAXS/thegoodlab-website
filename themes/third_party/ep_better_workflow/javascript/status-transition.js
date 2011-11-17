(function () {
'use strict';

  function StatusTransition (userRole, entryExists, entryStatus, draftExists, draftStatus,draftTemplate) {
    entryExists =  (typeof(entryExists) === 'undefined' ) ? false : entryExists;
    draftExists =  (typeof(draftExists) === 'undefined' ) ? false : draftExists;
    entryStatus =  (typeof(entryStatus) === 'undefined' ) ? null : entryStatus;
    draftStatus =  (typeof(draftStatus) === 'undefined' ) ? null : draftStatus;
    draftTemplate =  (typeof(draftTemplate) === 'undefined' ) ? null : draftTemplate;

    this.userRole=userRole;
    this.entryExists=entryExists;
    this.entryStatus=entryStatus;
    this.draftExists=draftExists;
    this.draftStatus=draftStatus;
    this.draftTemplate = draftTemplate;

    this.entryOptions= [];
    this.draftOptions= [];
    this.formIsEditable= true;
  }

  StatusTransition.prototype ={
    userRole: null,
    entryStatus: null,
    draftExists: null,
    draftStatus: null,

    _addEntryOption: function(btnName,entryStatus){
      this.entryOptions.push({
        'btnName':btnName,
        'status':entryStatus
      });
    },

    _addDraftOption: function(btnName,draftStatus,dbOperation){
      dbOperation =  (typeof(dbOperation) === 'undefined' ) ? null : dbOperation;
      this.draftOptions.push({
        'btnName':btnName,
        'status':draftStatus,
        'dbOperation':dbOperation
      });
    },

    render: function() {
      var uiButtons, uiPreview;
      this.run();
      uiButtons = new Bwf.ButtonsUI(this);
      uiPreview = new Bwf.PreviewBox(this);

      uiButtons.render();
      uiPreview.render();
    }
  };


  function EditorTransition (entryExists, entryStatus, draftExists, draftStatus, draftTemplate) {
    StatusTransition.call(this,'editor',entryExists,entryStatus,draftExists,draftStatus,draftTemplate);
  }

  EditorTransition.prototype={
    run: function () {

      if (!this.entryExists ) {
        this._addEntryOption('save_as_draft','draft');
        this._addEntryOption('submit_for_approval','submitted');
        return ;
      }
      if (this.entryExists && this.entryStatus == 'submitted' && !this.draftExists ) {
        this._addEntryOption('revert_to_draft','draft');
        this.formIsEditable=false;
        return;
      }
      //entry exists and is closed
      if (this.entryExists && this.entryStatus == 'draft' && !this.draftExists) {
        this._addEntryOption('save_as_draft','draft');
        this._addEntryOption('submit_for_approval','submitted');
        return;
      }
      if (this.entryExists && this.entryStatus == 'open' && !this.draftExists ){
        this._addDraftOption('save_as_draft','draft','create'); //create new record..
        this._addDraftOption('submit_for_approval','submitted','create');
        return;
      }
      if (this.entryExists && this.entryStatus == 'open' && this.draftExists && this.draftStatus == 'submitted') {
        this._addDraftOption('revert_to_draft','draft','update');
        this.formIsEditable=false;
        return;
      }
      if (this.entryExists && this.entryStatus == 'open' && this.draftExists && this.draftStatus == 'draft') {
        this._addDraftOption('discard_draft',null,'delete');
        this._addDraftOption('update_draft','draft','update');
        this._addDraftOption('submit_for_approval','submitted','update');
        return;
      }
    }
  };

  function PublisherTransition (entryExists,entryStatus,draftExists,draftStatus,draftTemplate) {
    StatusTransition.call(this,'publisher',entryExists,entryStatus,draftExists,draftStatus,draftTemplate);
  }

  PublisherTransition.prototype={
    run: function () {
      // This is a brand new entry or an existing one which is closed
      if (!this.entryExists || (this.entryExists && this.entryStatus == 'closed')) {
        this._addEntryOption('save_as_draft','draft');
        this._addEntryOption('publish','open');
        return ;
      }

      if (this.entryExists && this.entryStatus == 'submitted' && !this.draftExists) {
        this._addEntryOption('revert_to_draft','draft');
        this._addEntryOption('publish','open');
        return;
      }

      if (this.entryExists && this.entryStatus == 'draft' && !this.draftExists) {
        this._addEntryOption('save_as_draft','draft');
        this._addEntryOption('publish','open');
        return;
      }

      if (this.entryExists && this.entryStatus == 'open' && this.draftExists && this.draftStatus == 'draft') {
        this._addDraftOption('discard_draft',null,'delete');
        this._addDraftOption('update_draft','draft','update');
        this._addDraftOption('publish','open','replace');
        return;
      }

      if (this.entryExists && this.entryStatus == 'open' && this.draftExists && this.draftStatus == 'submitted') {
        this._addDraftOption('revert_to_draft','draft','update');
        this._addDraftOption('publish','open','replace');
        return;
      }

      if (this.entryExists && this.entryStatus == 'open' && !this.draftExists) {
        this._addDraftOption('save_as_draft','draft','create');
        this._addEntryOption('publish','open');
        this._addEntryOption('archive','closed'); 
        return;
      }

    }
  };

  function extend(child, parent) {
    for (var property in parent.prototype) {
      if (typeof child.prototype[property] == "undefined") {
        child.prototype[property] = parent.prototype[property];
      }
    }
    return child;
  }
  extend(EditorTransition, StatusTransition);
  extend(PublisherTransition, StatusTransition);


  this.EditorTransition = EditorTransition;
  this.PublisherTransition = PublisherTransition;

}).call(Bwf);
