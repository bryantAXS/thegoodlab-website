PublisherTransition=require('../ep_statusTransition').EpBwf_PublisherTransition;

exports.publisherCreatesNewEntry = function(test){
    pt=new PublisherTransition();
    pt.run();
    test.expect(3);
    test.equal(pt.entryOptions[0]["status"],'closed');
    test.equal(pt.entryOptions[1]["status"],'open');
    test.equal(pt.draftOptions.length,0);
    test.done();
};
exports.publisherEditsEntrySubmittedForApproval = function(test){
    pt=new PublisherTransition(true,'submitted');
    pt.run();
    test.expect(3);
    test.equal(pt.entryOptions[0]["status"],'closed');
    test.equal(pt.entryOptions[1]["status"],'open');
    test.equal(pt.draftOptions.length,0);
    test.done();
};
exports.publisherEditsExistingWithStatusOpen = function(test){
    pt=new PublisherTransition(true,'open');
    pt.run();
    test.expect(5);
    test.equal(pt.draftOptions[0]["status"],'closed');
    test.equal(pt.draftOptions[0]["dbOperation"],'create');
    test.equal(pt.entryOptions[0]["status"],'open');
    test.equal(pt.draftOptions.length,1);
    test.equal(pt.entryOptions.length,1);
    test.done();
};
exports.publisherEditsExistingEntryWithStatusOpenHavingAnAssociatedDraft = function(test){
    pt=new PublisherTransition(true,'open',true,'closed');
    pt.run();
    test.expect(8);
    test.equal(pt.draftOptions[0]["status"],null);
    test.equal(pt.draftOptions[0]["dbOperation"],'delete');
    test.equal(pt.draftOptions[1]["status"],'closed');
    test.equal(pt.draftOptions[1]["dbOperation"],'update');
    test.equal(pt.draftOptions[2]["status"],'open');
    test.equal(pt.draftOptions[2]["dbOperation"],'replace');
    test.equal(pt.draftOptions.length,3);
    test.equal(pt.entryOptions.length,0);
    test.done();
};
