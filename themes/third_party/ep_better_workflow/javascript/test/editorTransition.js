EditorTransition=require('../ep_statusTransition').EpBwf_EditorTransition;

exports.editorCreatesNewEntry = function(test){
    et=new EditorTransition();
    et.run();
    test.expect(2);
    test.equal(et.entryOptions[0]["status"],'closed');
    test.equal(et.entryOptions[1]["status"],'submitted');
    test.done();
};

exports.editorUpdatesAnExistingEntryWithStatusSubmitted = function(test){
    et=new EditorTransition(true,'submitted');
    et.run();


    test.expect(3);
    test.equal(et.entryOptions[0]["status"],'closed');
    test.equal(typeof(et.entryOptions[1]),'undefined');
    test.equal(et.formIsEditable,false);
    test.done();
};

exports.editorUpdatesAnExistingEntryWithStatusOpen=function(test){
    
    et=new EditorTransition(true,'open');
    et.run();

    test.expect(6);
    test.equal(et.entryOptions.length,0,'should be empty');
    test.equal(et.draftOptions.length,2);
    test.equal(et.draftOptions[0]['dbOperation'],'create');
    test.equal(et.draftOptions[0]['status'],'closed');
    test.equal(et.draftOptions[1]['dbOperation'],'create');
    test.equal(et.draftOptions[1]['status'],'submitted');
    test.done();
}

exports.editorUpdatesAnExistingEntryHavingAnAssociatedDraft=function(test){
    
    et=new EditorTransition(true,'open',true,'closed');
    et.run();

    test.expect(7);
    test.equal(et.entryOptions.length,0,'should be empty');
    test.equal(et.draftOptions.length,3);
    test.equal(et.draftOptions[0]['dbOperation'],'delete');
    test.equal(et.draftOptions[1]['dbOperation'],'update');
    test.equal(et.draftOptions[1]['status'],'closed');
    test.equal(et.draftOptions[2]['dbOperation'],'update');
    test.equal(et.draftOptions[2]['status'],'submitted');
    test.done();
}

exports.editorUpdatesAnExistingEntryWithADraftSubmittedForApproval=function(test){
    
    et=new EditorTransition(true,'open',true,'submitted');
    et.run();
    test.equal(et.formIsEditable,false); 
    test.equal(et.entryOptions.length,0,'should be empty');
    test.equal(et.draftOptions.length,1);
    test.equal(et.draftOptions[0]['dbOperation'],'update');
    test.equal(et.draftOptions[0]['status'],'closed');
    test.done();
}
