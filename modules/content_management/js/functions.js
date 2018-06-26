var editing;
function editingDoc(elem,user){

    editing = setInterval(function() {checkEditingDoc(elem,'user')}, 500);

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

function checkEditingDoc(elem, userId) {

    if ($j('#'+elem.id).parent().parent().find('[name=attachNum\\[\\]]').length) {
        var attachNum = $j('#'+elem.id).parent().parent().find('[name=attachNum\\[\\]]').val();
    } else {
        var attachNum = '0';
    }
    
    if ($j('#add').length) {
        var target = $j('#add');
    } else {
        var target = $j('#edit');
    }
    //LOCK VALIDATE BUTTON
    target.prop('disabled', true);
    target.css({"opacity":"0.5"});
    target.val('Edition en cours ...');

    //LOCK EDIT BUTTON (IF MULTI ATTACHMENT)
    $j("[name=templateOffice_edit\\[\\]]").css({"opacity":"0.5"});
    $j("[name=templateOffice_edit\\[\\]]").prop('disabled', true);

    $j.ajax({
       url : 'index.php?display=true&page=checkEditingDoc&module=content_management',
       type : 'POST',
       dataType : 'JSON',
       data: {attachNum : attachNum},
       success : function(response){
            if (response.status == 0) {
                console.log('no lck found!');

                //UNLOCK VALIDATE BUTTON
                target.prop('disabled', false);
                target.css({"opacity":"1"});
                target.val('Valider');

                //UNLOCK EDIT BUTTON (IF MULTI ATTACHMENT)
                $j("[name=templateOffice_edit\\[\\]], #edit").css({"opacity":"1"});
                $j("[name=templateOffice_edit\\[\\]], #edit").prop('disabled', false);

                if($j('#cancelpj').length){
                    $j('#cancelpj').prop('disabled', false);
                    $j('#cancelpj').css({'opacity':'1'});
                }

                //END OF CHECKING APPLET
                console.log('clearInterval');
                clearInterval(editing);

                //CONSTRUCT TAB (IF PDF)
                if ($j("#ongletAttachement li",window.parent.document).eq(attachNum).length) {
                    $j("#ongletAttachement li",window.parent.document).eq(attachNum).after($j("#MainDocument",window.parent.document).clone())
                } else {
                    $j("#MainDocument",window.parent.document).after($j("#MainDocument",window.parent.document).clone());
                }
                
                //$j("#MainDocument",window.parent.document).after($j("#MainDocument",window.parent.document).clone());
                $j("#iframeMainDocument",window.parent.document).after($j("#iframeMainDocument",window.parent.document).clone());
                if ($j("#ongletAttachement #PjDocument_"+attachNum,window.parent.document).length) {
                    $j("#ongletAttachement #PjDocument_"+attachNum,window.parent.document).remove();
                    $j("div #iframePjDocument_"+attachNum,window.parent.document).remove();
                }
                $j("div #iframeMainDocument",window.parent.document).eq(1).attr("id","iframePjDocument_"+attachNum);
                $j("div #iframePjDocument_"+attachNum,window.parent.document).attr("src","index.php?display=true&dir=indexing_searching&page=file_iframe&num="+attachNum+"&#navpanes=0"+response.pdf_version);              

                $j("#ongletAttachement #MainDocument",window.parent.document).eq(1).attr("id","PjDocument_"+attachNum);
                $j("#ongletAttachement #PjDocument_"+attachNum,window.parent.document).html("<span>PJ n°"+(parseInt(attachNum)+1)+"</span>");
                $j("#ongletAttachement #PjDocument_"+attachNum,window.parent.document).click();
                $j("#ongletAttachement [id^=PjDocument_]",window.parent.document).each(function( index ) {
                    $j("#"+this.id,window.parent.document).attr("onclick","activePjTab(this);");
                });
                
            } else {
                console.log('lck found! Editing in progress !');

                //LOCK VALIDATE BUTTON
                target.prop('disabled', true);
                target.css({"opacity":"0.5"});
                target.val('Edition en cours ...');

                //LOCK EDIT BUTTON (IF MULTI ATTACHMENT)
                $j("[name=templateOffice_edit\\[\\]]").css({"opacity":"0.5"});
                $j("[name=templateOffice_edit\\[\\]]").prop('disabled', true);

                if($j('#cancelpj').length){
                    $j('#cancelpj').prop('disabled', true);
                    $j('#cancelpj').css({'opacity':'0.5'});
                }

            }
       },
       error : function(error){
           console.log(error);
           //alert(error);
       }

    });

}

function showAppletLauncher(target, resId, objectTable, objectType, mode) {

    if (mode == 'template') {
        var path = 'index.php?display=true&module=content_management&page=applet_modal_launcher&uniqueId=0&objectType=' + objectType + '&objectId=' + resId + '&objectTable=' + objectTable;
    
    } else {
        //Num of Attachment
        var attachNum = $j('#'+target.id).parent().parent().find('[name=attachNum\\[\\]]').val();
        //Only add mode
        if (objectType == 'attachmentVersion') {
            var templateOffice = $j('#'+target.id).parent().parent().find('[name=templateOffice\\[\\]]').val();
        } else {
            var templateOffice = $j('#'+target.id).parent().parent().find('#res_id').val();
        }
        var attachment_types = $j('#'+target.id).parent().parent().find('[name=attachment_types\\[\\]]').val();

        if (attachment_types == 'transmission') {
            var contactidAttach = $j('#formAttachment [name=contactidAttach\\[\\]]').first().val();
            var addressidAttach = $j('#formAttachment [name=addressidAttach\\[\\]]').first().val();
        } else {
            var contactidAttach = $j('#'+target.id).parent().parent().find('[name=contactidAttach\\[\\]]').val();
            var addressidAttach = $j('#'+target.id).parent().parent().find('[name=addressidAttach\\[\\]]').val();
        }
        
        var chrono = $j('#'+target.id).parent().parent().find('[name=chrono\\[\\]]').val();
        var title = cleanTitle($j('#'+target.id).parent().parent().find('[name=title\\[\\]]').val());
        var back_date = $j('#'+target.id).parent().parent().find('[name=back_date\\[\\]]').val();
        if (typeof back_date === "undefined") {
            back_date = '';
        }
        var backDateStatus = $j('#'+target.id).parent().parent().find('[name=backDateStatus\\[\\]]').val();
        var path = 'index.php?display=true&module=content_management&page=applet_modal_launcher&uniqueId='+attachNum+'&objectType='+objectType+'&objectId='+templateOffice+'&attachType='+attachment_types+'&objectTable=' + objectTable + '&contactId='+contactidAttach+'&addressId='+addressidAttach+'&chronoAttachment='+chrono+'&titleAttachment='+title+'&backDateStatus='+backDateStatus+'&back_date='+back_date+'&resMaster=' + resId
    }
    

    /*console.log('attach number : '+attachNum);
    console.log('template_id : '+templateOffice);
    console.log('attachment type : '+attachment_types);
    console.log('contact_id : '+contactidAttach);
    console.log('address_id : '+addressidAttach);
    console.log('chrono : '+chrono);
    console.log('title : '+title);
    console.log('back date : '+back_date);
    console.log('path : '+path);*/
    
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
