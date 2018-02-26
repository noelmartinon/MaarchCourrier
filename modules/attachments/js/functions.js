// Date + 60 jours, utile pour les transmissions
function defineBackDate(delay) {
	var date1 = new Date();
	date1.setDate(date1.getDate() + Number(delay));
	var str_date = date1.toLocaleDateString();
    var t = str_date.split('/');
    if(t[1].length == 1){
        t[1] = '0'+t[1];
    }
    if(t[0].length == 1){
        t[0] = '0'+t[0];
    }
    str_date = t.join('-');
	return str_date;
}

function showOrButtonForAttachment() {
  if ($("edit").style.display != "none" && $("newTransmissionButton0").style.display != "none") {
    $("divOr0").style.display = "";
  } else {
    $("divOr0").style.display = "none";
  }
}


function checkBackDate(inputDate) {

  var dataCreationDate;
  var dateToCheck = inputDate.value;

  if($('dataCreationDate')) {
    dataCreationDate = $('dataCreationDate').value;
    var t = dataCreationDate.split(/[- :.]/);
    var tmpDate = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
    var d1_dataCreationDate = tmpDate.getTime();
  }

  if (dateToCheck != "") {
    var tmpDate = new Date();
    tmpDate.setFullYear(dateToCheck.substr(6,4));
    tmpDate.setMonth(dateToCheck.substr(3,2) - 1);
    tmpDate.setDate(dateToCheck.substr(0,2));
    tmpDate.setHours(0);
    tmpDate.setMinutes(0);
    var d2_dateToCheck = tmpDate.getTime();
  }
  if ((d1_dataCreationDate > d2_dateToCheck) && !isNaN(d2_dateToCheck)) {
    alert("La date de retour doit être supérieure à la date du courrier");
    inputDate.value = "";
  }else if(isNaN(d2_dateToCheck) && dateToCheck != ""){
    alert("Le format de la date de retour est incorrect");
    inputDate.value = "";
  }

}

function setRturnForEffectiveDate() {
  $('effectiveDateStatus').selectedIndex = 1;
}

function saveContactToSession(target) {

  setTimeout(function() {
    var transmissionContactidAttach = [];
    var transmissionAddressidAttach = [];
    
    $j('#formAttachment #addAttach1').each(function (index) {
        if ($j('#formAttachment #addAttach1').find('[name=attachment_types\\[\\]]').eq(index).val() == 'transmission') {
            var item1 = {
                "index": index,
                "val": $j('#formAttachment #addAttach1').find('[name=contactidAttach\\[\\]]').eq(index).val(),
            };
            transmissionContactidAttach.push(item1);
            var item2 = {
                "index": index,
                "val": $j('#formAttachment #addAttach1').find('[name=addressidAttach\\[\\]]').eq(index).val(),
            };
            transmissionAddressidAttach.push(item2);
        }        
    });

    $j.ajax({
        type: "POST",
        data: {
            transmissionContactidAttach : transmissionContactidAttach,
            transmissionAddressidAttach : transmissionAddressidAttach
        },
        url: "index.php?display=true&module=attachments&page=saveTransmissionContact",
        success: function(msg){
            
        }
    });

  }, 500);
}


function hideEditAndAddButton(editParagraph) {
  $(editParagraph).style.display = "none";
}


function hideInput(target) {
	if ($j('#'+target.id).val() == "NO_RTURN") {
        $j('#'+target.id).parent().parent().find('[name=back_date\\[\\]]').val("");
	} else {
        var delay = $j('#'+target.id).parent().parent().find('[name=attachment_types\\[\\]] option:selected').attr("width_delay");
        var delay_date = defineBackDate(delay);
        $j('#'+target.id).parent().parent().find('[name=back_date\\[\\]]').val(delay_date);
	}
}

function getTemplatesForSelect(path_to_script, attachment_type, selectToChange)
{
  new Ajax.Request(path_to_script,
    {
      method:'post',
      parameters: {attachment_type: attachment_type},
      onSuccess: function(answer) {
        $(selectToChange).innerHTML = answer.responseText;
      }
    });
}

function showAttachmentsForm(path, width, height) {
    if(typeof(width)==='undefined'){
        width = '800';
    }

    if(typeof(height)==='undefined'){
        height = '480';
    }
    new Ajax.Request(path,
    {
        method:'post',
        parameters: {
            url : path
        },
        onSuccess: function(answer) {
            eval("response = "+answer.responseText);

            if(response.status == 0){
                var modal_content = convertToTextVisibleNewLine(response.content);
                createModalinAttachmentList(modal_content, 'form_attachments', height, width, 'fullscreen');
                eval(response.exec_js);
            } else {
                window.top.$('main_error').innerHTML = response.error;
            }
        }
    });
}

function ValidAttachmentsForm(path, form_id, fromAngular) {

    new Ajax.Request(path,
    {
        asynchronous: false,
        method: 'post',
        parameters: Form.serialize(form_id),
        encoding: 'UTF-8',
        onSuccess: function (answer) {
            eval("response = " + answer.responseText);

            if (response.status == 0) {

                destroyModal('form_attachments');

                if (typeof fromAngular != "undefined") {
                    if (typeof response.type != "undefined" && response.type == "incoming_mail_attachment") {
                        window.angularSignatureBookComponent.componentAfterAttach("rightContent");
                    } else {
                        window.angularSignatureBookComponent.componentAfterAttach(fromAngular);
                    }
                } else {
                    if ($('viewframe') != undefined) {
                        var srcViewFrame = $('viewframe').src;
                        $('viewframe').src = srcViewFrame;
                    }

                    if (parent.document.getElementById('nb_attach') != undefined) {
                        var nb_attach = parseInt(parent.document.getElementById('nb_attach').innerHTML);
                        nb_attach = nb_attach+1;
                        parent.document.getElementById('nb_attach').innerHTML = nb_attach;
                    }
                    if(document.getElementById('iframe_tab')){
                        if (document.getElementById('iframe_tab').contentWindow.document.getElementById('list_attach') != undefined) {
                            iframe_attach = document.getElementById('iframe_tab').contentWindow.document.getElementById('list_attach');
                            iframe_attach.src = iframe_attach.src;
                        }
                    }

                    $j("#main_info").html(response.content).show().delay(5000).fadeOut();

                    eval(response.exec_js);
                }
            } else {
                $j("#main_error").css("opacity","1");
                $j("#main_error").html(response.error).show().delay(5000).fadeOut();
            }
        }
    });
}

function modifyAttachmentsForm(path, width, height) {
    if(typeof(width)==='undefined'){
        width = '800';
    }

    if(typeof(height)==='undefined'){
        height = '480';
    }

    new Ajax.Request(path,
    {
        method:'post',
        parameters: { url : path
                    },  
        onSuccess: function(answer) {
            eval("response = "+answer.responseText);
            if(response.status == 0){
                var modal_content = convertToTextVisibleNewLine(response.content);
                createModalinAttachmentList(modal_content, 'form_attachments', height, width, 'fullscreen'); 
                eval(response.exec_js);
            } else {
                alert(response.error);
            }
        }
    });
}

function setFinalVersion(path) {  

    new Ajax.Request(path,
    {
        asynchronous:false,
        method:'post',
        encoding: 'UTF-8',                       
        onSuccess: function(answer){
            eval("response = "+answer.responseText);
            if(response.status == 0 || response.status == 1){
                eval(response.exec_js);
            } else {
                alert(response.error);
            }
        }
    });
}

function loadSelectedContact() {
    var ContactAndAddress = $('selectContactIdRes').value;
    var value = ContactAndAddress.split("#");
    $('contactidAttach').value=value[0];
    $('addressidAttach').value=value[1];
    $('contact_attach').value=value[2];
    $('contact_attach').focus();
}

function createModalinAttachmentList(txt, id_mod, height, width, mode_frm){
    // FIX IE 11
    if($j('#leftPanelShowDocumentIframe')){
       $j('#leftPanelShowDocumentIframe').hide(); 
    }
    if($j('#rightPanelShowDocumentIframe')){
       $j('#rightPanelShowDocumentIframe').hide(); 
    }
    if(height == undefined || height=='') {
        height = '100px';
    }

    if(width == undefined || width=='') {
        width = '400px';
    }

    if( mode_frm == 'fullscreen') {
        width = (screen.availWidth)+'px';
        height = (screen.availHeight)+'px';
    }

    if(id_mod && id_mod!='') {
        id_layer = id_mod+'_layer';
    } else {
        id_mod = 'modal';
        id_layer = 'lb1-layer';
    }
    var tmp_width = width;
    var tmp_height = height;
    
    var layer_height = document.body.clientHeight;
    var layer_width = window.top.window.document.getElementsByTagName('html')[0].offsetWidth - 5;

    
    var layer = new Element('div', {'id':id_layer, 'class' : 'lb1-layer', 'style' : "display:block;filter:alpha(opacity=70);opacity:.70;z-index:"+get_z_indexes()['layer']+';width :'+ (layer_width)+"px;height:"+layer_height+'px;'});

    if( mode_frm == 'fullscreen') {
        var fenetre = new Element('div', {'id' :id_mod,'class' : 'modal', 'style' :'top:0px;left:0px;width:'+width+';height:'+height+";z-index:"+get_z_indexes()['modal']+";position:absolute;" });
    } else {
        var fenetre = new Element('div', {'id' :id_mod,'class' : 'modal', 'style' :'top:0px;left:0px;'+'width:'+width+';height:'+height+";z-index:"+get_z_indexes()['modal']+";margin-top:0px;margin-left:0px;position:absolute;" });
    }

    Element.insert(window.top.window.document.body,layer);
    Element.insert(window.top.window.document.body,fenetre);

    if( mode_frm == 'fullscreen') {
        navName = BrowserDetect.browser;
        if (navName == 'Explorer') {
            if (width == '1080px') {
                fenetre.style.width = (window.top.window.document.getElementsByTagName('html')[0].offsetWidth - 55)+"px";
            }
        } else {
            //fenetre.style.width = (window.top.window.document.getElementsByTagName('html')[0].offsetWidth - 30)+"px";
            fenetre.style.width = "98%";
        }
        //fenetre.style.height = (window.top.window.document.getElementsByTagName('body')[0].offsetHeight - 20)+"px";
        fenetre.style.height = "95%";
    }

    Element.update(fenetre,txt);
    
    
    Event.observe(layer, 'mousewheel', function(event){Event.stop(event);}.bindAsEventListener(), true);
    Event.observe(layer, 'DOMMouseScroll', function(event){Event.stop(event);}.bindAsEventListener(), false);
    
    $j('html',window.top.window.document).scrollTop(0);
    $j('body',window.top.window.document).scrollTop(0);
    
    $j('body',window.top.window.document).css("overflow","hidden");
    window.top.window.$(id_mod).focus();
}

function cleanTitle(str) {
    //permet de supprimer les # dans le titre qui bloque l'ouverture de l'applet java
    var res = str.replace(/#/g, " ");
    return(res);
}

function activeOngletAttachement() {
  $j('#viewframevalid_attachment').css('display', 'inline');
  $j('#viewframevalid_main').css('display', 'none');
  $j('#viewframevalid_attachment').addClass('activeOngletAttachement');
  $j('#viewframevalid_main').removeClass('activeOngletAttachement');
  $j('#liAttachement').css('background-color', 'white');
  $j('#liAttachement').css('height', '23px');
  $j('#liAttachement').css('display', 'inline');
  if(typeof($j('#liMainDocument')) != 'undefined'){
    $j('#liMainDocument').css('background-color', 'rgb(197, 197, 197)');
    $j('#liMainDocument').css('height', '21px');
  }
}

function activeOngletMainDocument() {
  $j('#viewframevalid_attachment').css('display', 'none');
  $j('#viewframevalid_main').css('display', 'inline');
  $j('#viewframevalid_attachment').removeClass('activeOngletAttachement');
  $j('#viewframevalid_main').addClass('activeOngletAttachement');
  $j('#liAttachement').css('background-color', 'rgb(197, 197, 197)');
  $j('#liAttachement').css('height', '21px');
  if(typeof($j('#liMainDocument')) != 'undefined'){
    $j('#liMainDocument').css('background-color', 'white');
    $j('#liMainDocument').css('height', '23px');
  }
}

function activePjTab(target) {

    $j('[id^=PjDocument_],#MainDocument').css('background-color', 'rgb(197, 197, 197)');
    $j('[id^=PjDocument_],#MainDocument').css('height', '21px');
    $j('[id^=iframePjDocument_],#iframeMainDocument').hide();

    $j('#'+target.id).css('background-color', 'white');
    $j('#'+target.id).css('height', '23px');
    $j('#iframe'+target.id).show();
}

function addNewAttach() {

    //STEP 1 : add content
    $j("#formAttachment #addAttach1").last().after($j("#formAttachment #addAttach1").first().clone().find("#choose_file").remove().end().clone());
    
    //STEP 2 : remove templateOfficeTool
    $j("#formAttachment .transmissionDiv #templateOfficeTool").hide();

    //STEP 3 : change ids
    $j("[name=attachment_types\\[\\]]").each(function( index ) {
        this.id = this.id.replace(/\d+/,'')+index;
    });
    $j("[name=chrono_display\\[\\]]").each(function( index ) {
        this.id = this.id.replace(/\d+/,'')+index;
    });
    $j("[name=chrono\\[\\]]").each(function( index ) {
        this.id = this.id.replace(/\d+/,'')+index;
    });
    $j("[name=attachNum\\[\\]]").each(function( index ) {
        this.value = index;
        this.id = this.id.replace(/\d+/,'')+index;    
    });
    $j("[name=back_date\\[\\]]").each(function( index ) {
        this.id = this.id.replace(/\d+/,'')+index;
    });
    $j("[name=effectiveDateStatus\\[\\]]").each(function( index ) {
        this.id = this.id.replace(/\d+/,'')+index;
        $j("#"+this.id).attr("onchange","checkEffectiveDateStatus(this);");
    });
    $j("[name=contact_card_attach]").each(function( index ) {
        this.id = this.id.replace(/\d+/,'')+index;
        $j('#'+this.id).attr("onclick","showContactInfo(this,$j('[name=contactidAttach\\\\[\\\\]]')["+index+"],$j('[name=addressidAttach\\\\[\\\\]]')["+index+"]);");
    });
    $j("[name=contactidAttach\\[\\]]").each(function( index ) {
        if ($j("#selectContactIdRes option:eq("+index+")").length) {
            var contact = $j("#selectContactIdRes option:eq("+index+")").val();
            contactId = contact.split('#');
            this.value = contactId[0];
        }
        this.id = this.id.replace(/\d+/,'')+index;
    });
    $j("[name=addressidAttach\\[\\]]").each(function( index ) {
        if($j("#selectContactIdRes option:eq("+index+")").length) {
            var contact = $j("#selectContactIdRes option:eq("+index+")").val();
            contactId = contact.split('#');
            this.value = contactId[1];
        }
        this.id = this.id.replace(/\d+/,'')+index;
    });
    $j("[name=contact_attach\\[\\]]").each(function( index ) {
        if ($j("#selectContactIdRes option:eq("+index+")").length) {
            $j("#"+this.id).val($j("#selectContactIdRes option:eq("+index+")").html());
        }
        $j("#"+this.id).attr("onchange","saveContactToSession(this);");
        $j("#"+this.id).change();
        this.id = this.id+index;
        
    });
    $j("[name=templateOffice\\[\\]]").each(function( index ) {
        this.id = this.id.replace(/\d+/,'')+index;
        $j("#"+this.id).attr("onchange","showEditButton(this);");
    });

    $j("[name=templateOffice_edit\\[\\]]").each(function( index ) {
        var id_split = this.id.replace(/\d+/,'').split('_');
        this.id = id_split[0]+index+'_'+id_split[1];
    });

    $j("[name=delAttachButton\\[\\]]").each(function( index ) {
        this.id = this.id.replace(/\d+/,'')+index;
        $j("#"+this.id).attr("onclick","delAttach("+index+");");

        if ($j("[name=delAttachButton\\[\\]]").length == 1 || index == 0) {
            $j("#"+this.id).css("display","none");
            $j("#"+this.id).prop("disabled",true);
        } else {
            $j("#"+this.id).css("display","inline-block");
            $j("#"+this.id).prop("disabled",false);
        }
    });

    //STEP 4 : reset new element
    $j("#formAttachment .transmissionDiv [name=attachment_types\\[\\]]").last().val('');
    $j("#formAttachment .transmissionDiv [name=attachment_types\\[\\]]").last().change();
    $j("#formAttachment .transmissionDiv [name=templateOffice\\[\\]]").last().val('');
    $j("#formAttachment .transmissionDiv [name=templateOffice\\[\\]]").last().css('display','inline-block');
    $j("#formAttachment .transmissionDiv [name=templateOffice\\[\\]]").last().prop('disabled',false);
    $j("#formAttachment .transmissionDiv [name=templateOffice\\[\\]]").last().change();
    $j("#formAttachment .transmissionDiv [name=chrono_display\\[\\]]").last().val('');
    $j("#formAttachment .transmissionDiv [name=chrono\\[\\]]").last().val('');
    $j("#formAttachment .transmissionDiv [name=back_date\\[\\]]").last().val('');
    $j("#formAttachment .transmissionDiv #newAttachButton").css("visibility","hidden");
    $j("#formAttachment .transmissionDiv #newAttachButton").prop("disabled",true);
    $j("#formAttachment .transmissionDiv #newAttachButton").last().css("visibility","visible");
    $j("#formAttachment .transmissionDiv #newAttachButton").last().addClass("readonly");

    $j("#formAttachment .transmissionDiv #templateOfficeTool #attachment_type_icon").first().attr("onclick","$j('#'+this.id).css('color','#009DC5');$j('#'+this.id).parent().parent().parent().parent().find('#attachment_type_icon2').first().css('color','#666');$j('#'+this.id).parent().parent().parent().parent().find('#templateOffice0').css('display','none');$j('#'+this.id).parent().parent().parent().parent().find('#templateOffice0').prop('disabled',true);$j('#'+this.id).parent().parent().parent().parent().find('#templateOffice0').css('display','none');$j('#'+this.id).parent().parent().parent().parent().find('#choose_file').css('display','inline-block');$j('#'+this.id).parent().parent().parent().parent().find('#choose_file').contents().find('#file').click()");
    $j("#formAttachment .transmissionDiv #templateOfficeTool #attachment_type_icon2").first().attr("onclick","$j('#'+this.id).css('color','#009DC5');$j('#'+this.id).parent().parent().parent().parent().find('#attachment_type_icon').first().css('color','#666');$j('#'+this.id).parent().parent().parent().parent().find('#templateOffice0').css('display','inline-block');$j('#'+this.id).parent().parent().parent().parent().find('#templateOffice0').prop('disabled',false);$j('#'+this.id).parent().parent().parent().parent().find('#choose_file').css('display','none');");


    $j("#formAttachment .transmissionDiv #templateOfficeTool").first().show();

    launch_autocompleter2_contacts_v2("index.php?display=true&dir=indexing_searching&page=autocomplete_contacts", $j("#formAttachment .transmissionDiv [name=contact_attach\\[\\]]").last().attr("id"), "show_contacts_attach", "", $j("#formAttachment .transmissionDiv [name=contactidAttach\\[\\]]").last().attr("id"), $j("#formAttachment .transmissionDiv [name=addressidAttach\\[\\]]").last().attr("id"));
    launch_autocompleter2_contacts_v2("index.php?display=true&dir=indexing_searching&page=autocomplete_contacts", $j("#formAttachment .transmissionDiv [name=contact_attach\\[\\]]").first().attr("id"), "show_contacts_attach", "", $j("#formAttachment .transmissionDiv [name=contactidAttach\\[\\]]").first().attr("id"), $j("#formAttachment .transmissionDiv [name=addressidAttach\\[\\]]").first().attr("id"));
}

function delAttach(index) {

    //DELETE DATA IN SESSION
    $j.ajax({
        url: 'index.php?display=true&module=attachments&page=ajaxDelAttachment',
        type: 'POST',
        dataType: 'JSON',
        data: {
            index: index,
        },
        success: function (response) {
            if (response.status == 0) {
                //alert('ok');
                $j("#MainDocument").click();
                $j("#delAttachButton"+index).parent().parent().remove();
                $j("#PjDocument_"+index).remove();
                $j("#iframePjDocument_"+index).remove();

                //reset ids
                $j("[id^=PjDocument_]").each(function( index ) {
                    this.id = this.id.replace(/\d+/,'')+index;
                    $j("#"+this.id).html("<span>PJ n°"+(parseInt(index)+1)+"</span>");
                });
                $j("[id^=iframePjDocument_]").each(function( index ) {
                    this.id = this.id.replace(/\d+/,'')+index;
                });
                $j("[name=attachment_types\\[\\]]").each(function( index ) {
                    this.id = this.id.replace(/\d+/,'')+index;
                });
                $j("[name=chrono_display\\[\\]]").each(function( index ) {
                    this.id = this.id.replace(/\d+/,'')+index;
                });
                $j("[name=chrono\\[\\]]").each(function( index ) {
                    this.id = this.id.replace(/\d+/,'')+index;
                });
                $j("[name=attachNum\\[\\]]").each(function( index ) {
                    this.value = index;
                    this.id = this.id.replace(/\d+/,'')+index;    
                });
                $j("[name=back_date\\[\\]]").each(function( index ) {
                    this.id = this.id.replace(/\d+/,'')+index;
                });
                $j("[name=backDateStatus\\[\\]]").each(function( index ) {
                    this.id = this.id.replace(/\d+/,'')+index;
                });
                $j("[name=contact_card_attach]").each(function( index ) {
                    this.id = this.id.replace(/\d+/,'')+index;
                    $j('#'+this.id).attr("onclick","showContactInfo(this,$j('[name=contactidAttach\\\\[\\\\]]')["+index+"],$j('[name=addressidAttach\\\\[\\\\]]')["+index+"]);");
                });
                $j("[name=contactidAttach\\[\\]]").each(function( index ) {
                    if ($j("#selectContactIdRes option:eq("+index+")").length) {
                        var contact = $j("#selectContactIdRes option:eq("+index+")").val();
                        contactId = contact.split('#');
                        this.value = contactId[0];
                    }
                    this.id = this.id.replace(/\d+/,'')+index;
                });
                $j("[name=addressidAttach\\[\\]]").each(function( index ) {
                    if($j("#selectContactIdRes option:eq("+index+")").length) {
                        var contact = $j("#selectContactIdRes option:eq("+index+")").val();
                        contactId = contact.split('#');
                        this.value = contactId[1];
                    }
                    this.id = this.id.replace(/\d+/,'')+index;
                });
                $j("[name=contact_attach\\[\\]]").each(function( index ) {
                    if ($j("#selectContactIdRes option:eq("+index+")").length) {
                        $j("#"+this.id).val($j("#selectContactIdRes option:eq("+index+")").html());
                    }
                    $j("#"+this.id).attr("onchange","saveContactToSession(this);");
                    $j("#"+this.id).change();
                    this.id = this.id+index;
                    
                });
                $j("[name=templateOffice\\[\\]]").each(function( index ) {
                    this.id = this.id.replace(/\d+/,'')+index;
                    $j("#"+this.id).attr("onchange","showEditButton(this);");
                });

                $j("[name=templateOffice_edit\\[\\]]").each(function( index ) {
                    var id_split = this.id.replace(/\d+/,'').split('_');
                    this.id = id_split[0]+index+'_'+id_split[1];
                });

                $j("[name=delAttachButton\\[\\]]").each(function( index ) {
                    this.id = this.id.replace(/\d+/,'')+index;
                    $j("#"+this.id).attr("onclick","delAttach("+index+");");

                    if ($j("[name=delAttachButton\\[\\]]").length == 1 || index == 0) {
                        $j("#"+this.id).css("display","none");
                        $j("#"+this.id).prop("disabled",true);
                    } else {
                        $j("#"+this.id).css("display","inline-block");
                        $j("#"+this.id).prop("disabled",false);
                    }
                });

                $j("#formAttachment .transmissionDiv #templateOfficeTool").first().show();
                $j("#formAttachment .transmissionDiv #newAttachButton").last().prop("disabled",false);
                $j("#formAttachment .transmissionDiv #newAttachButton").last().css("visibility","visible");
                $j("#formAttachment .transmissionDiv #newAttachButton").last().removeClass("readonly");
                
            }
        },
        error: function (error) {
            alert(error);
        }

    });

    
}

function showEditButton(target) {

    var modele_id = $j('#'+target.id).val();

    if (modele_id != '') {

        $j('#'+target.id+'_edit').css("display","inline");
        $j('#'+target.id).css("width","166px");
        
        if ($('not_enabled')) {
            $('not_enabled').setStyle({display: 'inline'});
        }

        $j('#'+target.id).parent().parent().find('#choose_file').css("display","none");

        if ($('file_loaded')) {
            $('file_loaded').setStyle({display: 'none'});
        }
    } else {
        $j('#'+target.id+'_edit').css("display","none");
        $j('#'+target.id).css("width","206px");

        if ($('not_enabled')) {
            $('not_enabled').setStyle({display: 'none'});
        }
        if ($('file_loaded')) {
            $('file_loaded').setStyle({display: 'none'});
        }
    }
}

function affiche_chrono(target){
    var type_id = document.getElementById(target.id).options[document.getElementById(target.id).selectedIndex];
    var chrono_display = $j(target).parent().parent().find("[name=chrono_display\\[\\]]");
    var chrono_label = $j(target).parent().parent().find("[name=chrono_label\\[\\]]");
    var get_chrono_display = $j(target).parent().parent().find("[name=get_chrono_display\\[\\]]");
    var chrono = $j(target).parent().parent().find("[name=chrono\\[\\]]");

    //FOR MULTI ATTACHMENT
    var index = '0';
    var regexIndex = chrono.attr("id").match(/\d+/);
    if (regexIndex != null) {
        index = regexIndex[0];
    }

    //GENERATE CHRONO
    if (type_id.value == 'transmission' && index != '0') {
        get_chrono_display.css("display","none"); 
        chrono_label.css("display","inline"); 
        chrono_display.css("display","inline"); 
        var num = $j('[value=transmission]:selected').length;
        var chr = String.fromCharCode(64 + num);
        chrono_display.val($j("#chrono0").val()+'.'+chr);
        chrono.val($j("#chrono0").val()+'.'+chr);

    } else if (type_id.getAttribute('with_chrono') == 'true') {
        get_chrono_display.css("display","none"); 
        chrono_label.css("display","inline"); 
        chrono_display.css("display","inline"); 
        
        new Ajax.Request('index.php?display=true&module=attachments&page=get_chrono_attachment',
        {
            method:'post',
            parameters:
            {
                index : index,
                type_id : type_id
            },
                onSuccess: function(answer){
                eval("response = "+answer.responseText);
                chrono_display.val(response.chronoNB);
                chrono.val(response.chronoNB);
            }
        });
    } else if (type_id.getAttribute('get_chrono') != '' && type_id.getAttribute('get_chrono') != null) {
        chrono_display.css("display","none");
        chrono_display.val('');
        chrono_label.css("display","inline");
        get_chrono_display.css("display","inline");
        chrono.val('');
        new Ajax.Request('index.php?display=true&module=attachments&page=get_other_chrono_attachment',
        {
            method:'post',
            parameters:
            {
                type_id : type_id.value
            },
                onSuccess: function(answer){
                eval("response = "+answer.responseText);
                get_chrono_display.html(response.chronoList);
            }
        });
    } else {
        chrono_label.css("display","none");
        get_chrono_display.css("display","none");
        chrono_display.css("display","none");
        chrono_display.val('');
        chrono.val('');
    }
}

function showContactInfo(target,contactTarget,addressTarget) {
    $j('#info_contact_div_attach').slideToggle("slow",function(){
        $j('#'+target.name+'_iframe').attr('src','index.php?display=false&dir=my_contacts&page=info_contact_iframe&fromAttachmentContact=Y&seeAllAddresses&contactid='+contactTarget.value+'&addressid='+addressTarget.value);
    });
}

function checkEffectiveDateStatus(effectiveDateStatus) {
    console.log($j('#'+effectiveDateStatus.id).val());
    if ($j('#'+effectiveDateStatus.id).val() == 'NO_RTURN') {
        $j('#'+effectiveDateStatus.id).parent().find('[name=back_date\\[\\]]').val('');
        $j('#'+effectiveDateStatus.id).parent().find('[name=back_date\\[\\]]').prop('disabled',true);
        $j('#'+effectiveDateStatus.id).parent().find('[name=back_date\\[\\]]').addClass('readonly');
    } else {
        $j('#'+effectiveDateStatus.id).parent().find('[name=back_date\\[\\]]').prop('disabled',false);
        $j('#'+effectiveDateStatus.id).parent().find('[name=back_date\\[\\]]').removeClass('readonly');
    }    
}