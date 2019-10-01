var addEmailAdress = function (idField, idList, theUrlToListScript, paramNameSrv, minCharsSrv) {
     new Ajax.Autocompleter(
         idField,
         idList,
         theUrlToListScript,
         {
             paramName: paramNameSrv,
             minChars: minCharsSrv,
             tokens: ',',
             afterUpdateElement:extractEmailAdress
         });
 };

 var addDestUser = function (idField, idList, theUrlToListScript, paramNameSrv, minCharsSrv) {
     new Ajax.Autocompleter(
         idField,
         idList,
         theUrlToListScript,
         {
             paramName: paramNameSrv,
             minChars: minCharsSrv,
             tokens: ',',
             afterUpdateElement:extractDestUser
         });
 };

function addTemplateToEmail(templateMails, path){

    new Ajax.Request(path,
    {
        method      :'post',
        parameters  :{
                        templateId : templateMails,
                        mode : mode
                     },
        onSuccess   :function(answer){
            eval("response = " + answer.responseText);
            if (response.status == 0) {
                var strContent = response.content;
                if(mode == 'html'){
                    var strContentReplace = strContent.replace(/\\n/g, '');
                    tinyMCE.execCommand('mceSetContent', false, strContentReplace);
                } else {
                    var strContentReplace = strContent.replace(/\\n/g, '\n');
                    $j("textarea#body_from_raw").html(strContentReplace);
                }
            } 
        }
    });
}

function changeSignature(selected, mailSignaturesJS){
    var nb = selected.getAttribute('data-nb');
    var body = $('body_from_html_ifr').contentWindow.document.getElementById("tinymce");
    var customTag = body.getElementsByTagName("mailSignature");

    if (mode == 'html' && customTag.length == 0) {
        body.innerHTML += "<mailSignature>t</mailSignature>";
        customTag = body.getElementsByTagName("mailSignature");
    }

    if (nb >= 0) {
        var strContent = mailSignaturesJS[nb].signature;
        if(mode == 'html'){
            customTag[0].innerHTML = strContent;
        } else {
            var text = $j(strContent).text();
            $j("textarea#body_from_raw").append(text);
        }
        
    } else {
        customTag[0].innerHTML = "";
    }
}

function showEmailForm(path, width, height, iframe_container_id) {
    
    if(typeof(width)==='undefined'){
        width = '820px';
    }
    
    if(typeof(height)==='undefined'){
        height = '545px';
    }  
	
    if(typeof(iframe_container_id)==='undefined'){
        iframe_container_id = '';
    }  
    new Ajax.Request(path,
    {
        method:'post',
        parameters: { url : path
                    },  
        onSuccess: function(answer) {
            eval("response = "+answer.responseText);
           
            if(response.status == 0){
				console.log('Height = '+height);
				console.log('Width = '+width);
                var modal_content = convertToTextVisibleNewLine(response.content);
                createModal(modal_content, 'form_email', height, width,'',iframe_container_id); 
            } else {
                window.top.$('main_error').innerHTML = response.error;
            }
        }
    });
}

function updateAdress(path, action, adress, target, array_index, email_format_text_error) {
    
    if (validateEmail(adress) === true) {
        
        new Ajax.Request(path,
        {
            method:'post',
            parameters: { url : path,
                          'for': action,
                          email: adress,
                          field: target,
                          index: array_index
                        },
            onLoading: function(answer) {
                $('loading_' + target).style.display='inline';
            },
            onSuccess: function(answer) {
                eval("response = "+answer.responseText);
                if(response.status == 0){
                    $(target).innerHTML = response.content;
                    if (action == 'add') {$('email').value = '';}
                } else {
                    alert(response.error);
                    eval(response.exec_js);
                }
            }
        });
    } else {
        if(typeof(email_format_text_error) == 'undefined'){
            email_format_text_error = 'Email format is not available!';
        }
        alert(email_format_text_error);
    }
}

function updateDestUser(path, action, adress, target, array_index) {
     
    new Ajax.Request(path,
    {
        method:'post',
        parameters: { url : path,
                      'for': action,
                      contactAddress: adress,
                      field: target,
                      index: array_index
                    },
        onLoading: function(answer) {
            $('loading_' + target).style.display='inline';
        },
        onSuccess: function(answer) {
            eval("response = "+answer.responseText);
            if(response.status == 0){
                $(target).innerHTML = response.content;
                if (action == 'add') {$('user').value = '';}
            } else {
                alert(response.error);
                eval(response.exec_js);
            }
        }
    });
}

function validEmailForm(path, form_id) {

    var attachments = $j("#joined_files input.check");

    if (attachments.length > 0 && (path.includes("for=send") || path.includes("formContent=messageExchange"))) {
        var hasOneChecked = false;
        for (var i = 0; i < attachments.length; i++) {
            if (attachments[i].checked == true) {
                hasOneChecked = true;
                break;
            }
        }

        if (!hasOneChecked) {
            if(path.includes("formContent=messageExchange")){
                alert('Aucune pièce jointe sélectionnée');
                return;
            } else if(path.includes("for=send")){
                var cfm = confirm('Aucune pièce jointe sélectionnée. Voulez-vous quand même envoyer le mail ?');
                if (!cfm) {
                    return;
                }
            }
        }
    }

    if (typeof($j('input[name=main_exchange_doc]:checked', '#formEmail').val()) === 'undefined' && path.includes("formContent=messageExchange")) {
        alert('Aucun document principal sélectionné');
        return;
    }

    $j("input#valid.button").prop("disabled", true).css("opacity", "0.5");

    tinyMCE.triggerSave();
    new Ajax.Request(path,
    {
        asynchronous:false,
        method:'post',
        parameters: Form.serialize(form_id),
        encoding: 'UTF-8',
        onSuccess: function(answer){
            eval("response = "+answer.responseText);
            if(response.status == 0){
                eval(response.exec_js);
               window.parent.destroyModal('form_email');
            } else {
                $j("input#valid.button").prop("disabled", false).css("opacity", "1");
                alert(response.error);
                eval(response.exec_js);
            }
        }
    });
}

function validEmailFormForSendToContact(path, form_id, path2, status) {
    tinyMCE.triggerSave();
    new Ajax.Request(path,
    {
        asynchronous:false,
        method:'post',
        parameters: Form.serialize(form_id),
        encoding: 'UTF-8',                       
        onSuccess: function(answer){
            eval("response = "+answer.responseText);
            if(response.status == 0){
                eval(response.exec_js);
                changeStatusForActionSendToContact(path2, status);
                window.parent.destroyModal('form_email');
            } else {
                alert(response.error);
                eval(response.exec_js);
            }
        }
    });
}

function changeStatusForActionSendToContact(path, status){
    console.log(path);
    new Ajax.Request(path,
    {
        asynchronous:false,
        method:'post',
        parameters: {status : status},   
        encoding: 'UTF-8',                       
        onSuccess : function(){
          parent.document.getElementById('storage').click();
        }
    });
}

function extractEmailAdress(field, item) {
    var fullAdress = item.innerHTML;
    field.value = fullAdress.match(/\(([^)]+)\)/)[1];
}

function extractDestUser(field, item) {
    $j('#user').val(item.id);
    $j('#valid').click();
}

function validateEmail(email) { 
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}
 
function switchMode(action) {
    var div = document.getElementById(mode+"_mode");
    div.style.display = "block";
    if(action == "show") {
        div.style.display = "none"; // Hide the current div.
        mode = (mode === 'html')? 'raw' : 'html';      // switch the mode
        document.getElementById("is_html").value = (mode === 'html')? 'Y' : 'N'; //Update the hidden field
        document.getElementById(mode+"_mode").style.display = "block"; // Show the other div.
    }
}

var MyAjax = { };
MyAjax.Autocompleter = Class.create(Ajax.Autocompleter, {
    updateChoices: function(choices) {
        if(!this.changed && this.hasFocus) {
            this.update.innerHTML = choices;
            Element.cleanWhitespace(this.update);
            Element.cleanWhitespace(this.update.down());
            if(this.update.firstChild && this.update.down().childNodes) {
                this.entryCount = this.update.down().childNodes.length;
                for (var i = 0; i < this.entryCount; i++) {
                    var entry = this.getEntry(i);
                    entry.autocompleteIndex = i;
                    this.addObservers(entry);
                }
            } else {
                this.entryCount = 0;
            }
            this.stopIndicator();
            this.index = -1;

            if(this.entryCount==1 && this.options.autoSelect) {
                this.selectEntry();
                this.hide();
            } else {
                this.render();
            }
        }
    }
});

function clickAttachments(id){
    $("join_file_"+id).click();
}
function clickAttachmentsInput(id){
    $("join_attachment_"+id).click();
}
function clickAttachmentsNotes(id){
    $("note_"+id).click();
}
