var editing;
function editingDoc(user){

    editing = setInterval(function() {checkEditingDoc('user')}, 500);

}
//load applet in a modal
function loadApplet(url, value)
{
    if (value != '') {
        //console.log('value : '+value);
        displayModal(url, 'CMApplet', 300, 300);
    }
}

//applet send a message (error) to Maarch
function sendAppletMsg(theMsg)
{
    //theMsg = 'erreur!';
    //console.log(window.opener.$('divErrorAttachment'));

    var error = /^ERREUR/.test(theMsg);
    if (window.opener.$('divErrorAttachment')) {
        if(error){
            window.opener.$('divErrorAttachment').innerHTML = theMsg;
            window.opener.$('divInfoAttachment').setStyle({display: 'none'});
            window.opener.$('divErrorAttachment').setStyle({display: 'inline'});
            window.close();
        }else{
            window.opener.$('divInfoAttachment').innerHTML = theMsg;
            window.opener.$('divInfoAttachment').setStyle({display: 'inline'});
            window.opener.$('divErrorAttachment').setStyle({display: 'none'});
        }
    }else{
        if(error){
            window.opener.$('main_info').setStyle({display: 'none'});
            window.opener.$('main_error').setStyle({display: 'inline'});
            window.opener.$('main_error').innerHTML = theMsg;
            window.close();
        }else{
            window.opener.$('main_info').setStyle({display: 'inline'});
            window.opener.$('main_error').setStyle({display: 'none'});
            window.opener.$('main_info').innerHTML = theMsg;
        }
    }
    //$('divError').innerHTML = theMsg;
    //$('divError').setStyle({display: 'inline'});
    //window.close();
    /*if (theMsg != '' && theMsg != ' ') {
        if (window.opener.$('divError')) {
            window.opener.$('divError').innerHTML = theMsg;
        } else if ($('divError')) {
            $('divError').innerHTML = theMsg;
        }
    }*/
}

//destroy the modal of the applet and launch an ajax script
function endOfApplet(objectType, theMsg)
{
    console.log('endOfAppletendOfAppletendOfApplet');
    if (window.opener.$('add')) {
        window.opener.$('add').setStyle({display: 'inline'});
    } else if (window.opener.$('edit')) {
        window.opener.$('edit').setStyle({display: 'inline'});
    }

    //if (window.opener.$('edit') && objectType != 'transmission') {
    //    window.opener.$('edit').setStyle({display: 'inline'});
    //}

    $('divError').innerHTML = theMsg;
    if (objectType == 'template' || objectType == 'templateStyle' || objectType == 'attachmentVersion' || objectType == 'attachmentUpVersion' || objectType == 'transmission') {
        endTemplate();
    } else if (objectType == 'resource') {
        endResource();
    } else if (objectType == 'attachmentFromTemplate') {
        endAttachmentFromTemplate();
    } else if (objectType == 'attachment') {
        endAttachment();
    } else if (objectType == 'outgoingMail') {
        endAttachmentOutgoing();
    } 
    //destroyModal('CMApplet');
}

function endAttachmentFromTemplate()
{
    //window.alert('template ?');
    if(window.opener.$('list_attach')) {
        window.opener.$('list_attach').src = window.opener.$('list_attach').src;
    }
    window.close();
}

function endAttachment()
{
    window.close();
}

function endTemplate()
{
    //window.alert('template ?');
    window.close();
}

function endAttachmentOutgoing()
{
    console.log('Fin nouveau spontane');
	
	
    window.close();
	
}

//reload the list div and the document if necessary
function endResource()
{
    //window.alert('resource ?');
    showDivEnd(
        'loadVersions',
        'nbVersions',
        'createVersion',
        'index.php?display=false&module=content_management&page=list_versions'
    );
    if (window.opener.$('viewframe')) {
        window.opener.$('viewframe').src = window.opener.$('viewframe').src;
    } else if (window.opener.$('viewframevalid')) {
        window.opener.$('viewframevalid').src = window.opener.$('viewframevalid').src;
    }
    
    //window.close();
}

function showDivEnd(divName, spanNb, divCreate, path_manage_script)
{
    new Ajax.Request(path_manage_script,
    {
        method:'post',
        parameters: {res_id : 'test'},
            onSuccess: function(answer){
            eval("response = "+answer.responseText);
            if(response.status == 0 || response.status == 1) {
                if(response.status == 0) {
                    window.opener.$(divName).innerHTML = response.list;
                    window.opener.$(spanNb).innerHTML = response.nb;
                    window.opener.$(divCreate).innerHTML = response.create;
                    window.close();
                } else {
                    window.opener.$(divName).innerHTML = 'error = 1 : ' . response.error_txt;
                }
            } else {
                window.opener.$(divName).innerHTML = 'error > 1 : ' . response.error_txt;
                try {
                    //window.opener.$(divName).innerHTML = response.error_txt;
                }
                catch(e){}
            }
        }
    });
}

function showDiv(divName, spanNb, divCreate, path_manage_script)
{
    new Ajax.Request(path_manage_script,
    {
        method:'post',
        parameters: {res_id : 'test'},
            onSuccess: function(answer){
            eval("response = "+answer.responseText);
            if(response.status == 0 || response.status == 1) {
                if(response.status == 0) {
                    if ($(divName)) {
                        $(divName).innerHTML = response.list;
                        $(spanNb).innerHTML = response.nb;
                        $(divCreate).innerHTML = response.create;
                    } else {
                        window.opener.$(divName).innerHTML = response.list;
                        window.opener.$(spanNb).innerHTML = response.nb;
                        window.opener.$(divCreate).innerHTML = response.create;
                    }
                } else {
                    //
                }
            } else {
                try {
                    //$(divName).innerHTML = response.error_txt;
                }
                catch(e){}
            }
        }
    });
}

function checkEditingDoc(userId) {
    if($j('#add').length){
        var target = $j('#add');
    }else{
        var target = $j('#edit');
    }
    //LOCK VALIDATE BUTTON
    target.prop('disabled', true);
    target.css({"opacity":"0.5"});
    target.val('Edition en cours ...');

    //LOCK EDIT BUTTON TRANSMISSION
    $j(".transmissionEdit").css({"opacity":"0.5"});
    $j(".transmissionEdit").prop('disabled', true);

    $j.ajax({
       url : 'index.php?display=true&page=checkEditingDoc&module=content_management',
       type : 'POST',
       dataType : 'JSON',
       success : function(response){
            if (response.status == 0) {
                console.log('no lck found!');

                //UNLOCK VALIDATE BUTTON
                target.prop('disabled', false);
                target.css({"opacity":"1"});
                target.val('Valider');

                //UNLOCK EDIT BUTTON TRANSMISSION
                $j(".transmissionEdit, #edit").css({"opacity":"1"});
                $j(".transmissionEdit, #edit").prop('disabled', false);

                //END OF CHECKING APPLET
                console.log('clearInterval');
                window.top.document.getElementById('liAttachement').click();
                document.getElementById('viewframevalid_attachment').src='index.php?display=true&dir=indexing_searching&page=file_iframe&#navpanes=0'+response.pdf_version;
                clearInterval(editing);
            } else {
                console.log('lck found! Editing in progress !');

                //LOCK VALIDATE BUTTON
                target.prop('disabled', true);
                target.css({"opacity":"0.5"});
                target.val('Edition en cours ...');

                //LOCK EDIT BUTTON TRANSMISSION
                $j(".transmissionEdit, #edit").css({"opacity":"0.5"});
                $j(".transmissionEdit, #edit").prop('disabled', true);

            }
       },
       error : function(error){
           console.log(error);
           //alert(error);
       }

    });

}

function showAppletLauncher(path, width, height) {

    new Ajax.Request(path,
    {
        method:'post',
        parameters: { url : path
                    },  
        onSuccess: function(answer) {
            
            eval("response = "+answer.responseText);
            
            if(response.status == 0){
                var modal_content = convertToTextVisibleNewLine(response.content);
                createModal(modal_content, 'CMApplet', 300, 300); 
            } else {
                window.top.$('main_error').innerHTML = response.error;
            }
        }
    });
}
