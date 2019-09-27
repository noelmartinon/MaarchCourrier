
var $j = jQuery.noConflict();

/**
 * When you choose a doctype, you must calculate the process date limit (Ajax) and execute module services
 *
 * @param doctype_id String Document type identifier
 * @param path_manage_script String Path to the php file called in the Ajax object
 * @param error_empty_type String Error label displayed when the document type is empty
 * @param action_id String Action identifier
 **/
function change_doctype(doctype_id, path_manage_script, error_empty_type, action_id, get_js_script,display_value_tr, id_res, id_coll, from_valid_qualif)
{
    var admissionDate;
    if ($('admission_date')) {
        admissionDate = $('admission_date').value;
    }
    var priorityId;
    if ($('priority')) {
        priorityId = $('priority').value;
    }
    var theCollId = path_manage_script.split('coll_id=');
    var tmp_res_id = id_res || null;
    if (theCollId[1] != '') {
        tmp_coll_id = theCollId[1];
    } else {
        var tmp_coll_id = id_coll || null;
    }
    if(doctype_id != null && doctype_id != '' && doctype_id != NaN)
    {
        new Ajax.Request(path_manage_script,
        {
            method:'post',
            parameters: { type_id : doctype_id,
                          id_action : action_id,
                          res_id : tmp_res_id,
                          coll_id : tmp_coll_id,
                          admission_date : admissionDate,
                          priority_id : priorityId                          
                        },
                onSuccess: function(answer){
                eval("response = "+answer.responseText);
                //alert(answer.responseText);
                if(response.status == 0  || response.status == 1)
                {
                    if($('process_limit_date_tr').style.display != 'none'){
                        if(response.status == 0)
                        {
                            var limit_use = $('process_limit_date_use_yes');
                            if(limit_use)
                            {
                                limit_use.checked = true;
                            }
                            var process_date_tr = $('process_limit_date_tr');
                            if(process_date_tr != null)
                            {
                                Element.setStyle(process_date_tr, {display : display_value_tr})
                            }
                            var process_date = $('process_limit_date');
                            if(process_date != null && !from_valid_qualif)
                            {
                                process_date.value = response.process_date;
                            }
                            if (process_date.value == '') {
                                process_date.value = response.process_date;
                            }
                        }
                        else
                        {

                            var limit_use = $('process_limit_date_use_no');
                            if(limit_use)
                            {
                                limit_use.checked = true;
                            }
                            var process_date_tr = $('process_limit_date_tr');
                            if(process_date_tr != null)
                            {
                                Element.setStyle(process_date_tr, {display : 'none'})
                            }
                        }
                    }
                    
                    var indexes = response.opt_indexes;
                    var div_indexes = $('comp_indexes');
                    if(div_indexes )
                    {
                        div_indexes.update(indexes);
                    }
                    var services_to_exec = response.services;
                    var path_scripts = '';
                    var call_func = '';
                    for(var ind=0; ind < services_to_exec.length;ind++)
                    {
                        path_scripts += services_to_exec[ind]['script'] + '$$';
                        call_func += services_to_exec[ind]['function_to_execute']+'('+services_to_exec[ind]['arguments']+');';
                    }
                    if(call_func != '' && path_scripts != '' && get_js_script != '')
                    {
                        path_scripts = path_scripts.replace("modules/templates/js/", "");
                        new Ajax.Request(get_js_script,
                        {
                            method:'post',
                            parameters:
                            {
                                scripts : path_scripts
                            },
                             onSuccess: function(answer){
                                eval(answer.responseText+call_func);
                            }
                        });
                    }
                }
                else
                {
                    try{
                        $('frm_error').innerHTML = response.error_txt;
                        }
                    catch(e){}
                }
            }
        });
    }
    else
    {
        try{
            $('frm_error_'+action_id).innerHTML = error_empty_type;
            }
        catch(e){}
    }
}

function changePriorityForSve(doctype_id, path_script){
    new Ajax.Request(path_script,
    {
        method:'post',
        parameters: { type_id : doctype_id,
                      address_id : path_script
                    },
            onSuccess: function(answer){
                eval("response = "+answer.responseText);
                if(response.status == 0) {
                    $j("#priority").val(response.value);
                    $j("#priority").trigger("chosen:updated");

                } else if(response.status == 1) {
                    $j("#priority").val(response.value);
                    $j("#priority").trigger("chosen:updated");
                } else {
                    console.log('Erreur Ajax');
                }
            },
            onFailure: function(){ alert('Something went wrong...'); }
    });
}

/**
 * Compute process limit date from the admission date
 *
 **/
function updateProcessDate(path_manage_script)
{
    var admissionDate;
    
    if ($('admission_date')) {
        admissionDate = $('admission_date').value;
    }
    var typeId;
    if ($('type_id')) {
        typeId = $('type_id').value;
    }
    var priorityId;
    if ($('priority')) {
        priorityId = $('priority').value;
    }
    if (admissionDate != null && admissionDate != '' && admissionDate != NaN) {
        new Ajax.Request(path_manage_script,
        {
            method:'post',
            parameters: {
                    type_id: typeId,
                    admission_date : admissionDate,
                    priority_id : priorityId
                },
                onSuccess: function(answer){
                eval("response = "+answer.responseText);
                //alert(answer.responseText);
                if (response.status == 0  || response.status == 1) {
                    //console.log(response.process_date);
                    var process_date = $('process_limit_date');
                    if (response.process_date != null) {
                        process_date.value = response.process_date;
                    }
                }
            }
        });
    }
}

function displayFatherFolder(select)
{
    if ($(select) && $j('#parentFolderSpan') && $j('#parentFolderTr')) {
        var tmpParSpan = $j('#parentFolderSpan');
        var tmpParTr = $j('#parentFolderTr');
        var selectFolders = $(select);
    } else {
        return ;
    }

    for (var i = 0; i < selectFolders.options.length; i++) {
        if (selectFolders.options[i].getAttribute('value') == selectFolders.options[selectFolders.selectedIndex].getAttribute('parent')) {
            tmpParTr.css("display","block");

            tmpParSpan.html( "Dossier Parent : " + selectFolders.options[i].label);
            return;
        }
    }
    tmpParTr.css("display","none");
}

function checkRealDate(arg) {

    var cat = $('category_id').options[$('category_id').selectedIndex].value

    if (cat == 'incoming') {

        var admissionDate;
        var docDate;

        if ($('admission_date')) {
            admissionDate = $('admission_date').value;
			var date1 = new Date();
			date1.setFullYear(admissionDate.substr(6,4));
			date1.setMonth(admissionDate.substr(3,2) - 1, admissionDate.substr(0,2));
			date1.setHours(0, 0, 0, 0);

			var d1_admissionDate=date1.getTime();
        }

        if($('doc_date')) {
            docDate = $('doc_date').value;
			var date2 = new Date();
			date2.setFullYear(docDate.substr(6,4));
			date2.setMonth(docDate.substr(3,2) - 1, docDate.substr(0,2));
			date2.setHours(0, 0, 0, 0);
			var d2_docDate=date2.getTime();
        }

        if(admissionDate != "" && docDate != "" && d2_docDate > d1_admissionDate) {          
            alert("La date du courrier doit être antérieure à la date d'arrivée du courrier ");
            if(arg == 'admissionDate'){
                $('admission_date').value = "";
            }
            if(arg == 'docDate'){
                $('doc_date').value = "";
            }    
        }

        var date = new Date();
        var date3 = new Date();
        date.setFullYear(date3.getFullYear());
        date.setMonth(date3.getMonth()+1);
        date.setDate(date3.getDate()); 
        current_date = date.getTime();

        if (d1_admissionDate > current_date) {
            alert("La date d'arrivée doit être antérieure à la date du jour ");
            var current_month = date3.getMonth()+1;
            $('admission_date').value = (("0" + date3.getDate()).slice(-2)) + "-" + ("0" + current_month).slice(-2) + "-" + date3.getFullYear();
        }
    } else if (cat == 'outgoing') {
        if($('departure_date')) {
            departureDate = $('departure_date').value;
            var date2 = new Date();
            date2.setFullYear(departureDate.substr(6,4));
            date2.setMonth(departureDate.substr(3,2));
            date2.setDate(departureDate.substr(0,2));
            date2.setHours(0);
            date2.setMinutes(0);
            var d2_departureDate=date2.getTime();
        }

        if(departureDate != "" && docDate != "" && d2_docDate > d2_departureDate) {          
            alert("La date du courrier doit être antérieure à la date de départ du courrier ");
            if(arg == 'custom_d1'){
                $('custom_d1').value = "";
            }
            if(arg == 'docDate'){
                $('doc_date').value = "";
            }    
        }
    }
}

function checkRealLimitDate(arg) {

    var process_limit_date;
    var admission_date;
    if ($('process_limit_date')) {
        process_limit_date = $('process_limit_date').value;
        var date1 = new Date();
        date1.setFullYear(process_limit_date.substr(6,4),process_limit_date.substr(3,2)-1,process_limit_date.substr(0,2));
        //date1.setMonth(process_limit_date.substr(3,2)-1,process_limit_date.substr(0,2));
        // date1.setDate(process_limit_date.substr(0,2));
        // date1.setHours(0);
        // date1.setMinutes(0);

        var d1_process_limit_date=date1.getTime();
    }

    if($('admission_date')) {
        admission_date = $('admission_date').value;
        var date2 = new Date();
        date2.setFullYear(admission_date.substr(6,4),admission_date.substr(3,2)-1,admission_date.substr(0,2));
		//date2.setMonth(admission_date.substr(3,2)-1,admission_date.substr(0,2));
		// date2.setDate(admission_date.substr(0,2));
		// date2.setHours(0);
		// date2.setMinutes(0);
        var d2_admission_date=date2.getTime();
    }

    if(process_limit_date != "" && admission_date != "" && d2_admission_date > d1_process_limit_date) {          
        alert("La date limite de traitement doit être supérieure à la date d'arrivée du courrier ");
        if(arg == 'process_limit_date'){
            $('process_limit_date').value = "";
        }
        if(arg == 'admission_date'){
        	$('admission_date').value = "";
        }    
    }

/*    var date = new Date();
    var date3 = new Date();
    date.setFullYear(date3.getFullYear());
    date.setMonth(date3.getMonth()+1);
    date.setDate(date3.getDate()); 
    current_date = date.getTime();

    if (d1_admissionDate > current_date) {
        alert("La date d'arrivée doit être antérieure à la date du jour ");
        var current_month = date3.getMonth()+1;
        $('admission_date').value = date3.getDate() + "-" + current_month + "-" + date3.getFullYear();
    }*/

}

/**
 * Activates / Desactivates the process date limit
 *
 * @param activate Bool Activate mode = true, desactivate mode = false
 **/
function activate_process_date(activate, display_value_tr)
{
    var process_date = $('process_limit_date_tr');
    var tr_display_value = display_value_tr || 'table-row';
    if(process_date != null)
    {
        if(activate == true)
        {
            Element.setStyle(process_date, {display : tr_display_value});
            //process_date.style.display = tr_display_value;
        }
        else
        {
            //process_date.style.display = 'none';
            Element.setStyle(process_date, {display : 'none'});
        }
    }
}

/**
 * Adapts the indexing form with the category : loads the fields to display in a JSON object
 *
 * @param cat_id String Category identifier
 **/
function change_category(cat_id, display_value_tr, path_manage_script,get_js_script, params_cat, origin)
{
    var typeContactInternal = 'not_checked';
    var typeContactExternal = 'not_checked';
    var typeMultiContactExternal = 'not_checked';

    if (typeof (origin) == "undefined") {
        origin = '';
    }
    // if (cat_id == 'internal' || cat_id == 'incoming') {
    //     $('type_contact_external').checked = true;
    // };
    if ($('type_contact_internal')) {
        if ($('type_contact_internal').checked == true) {
            var typeContactInternal = 'checked';
        }
    }
    if ($('type_contact_external')) {
        if ($('type_contact_external').checked == true) {
            typeContactExternal = 'checked';
        }
    }    
	  if ($('type_multi_contact_external')) {
        if ($('type_multi_contact_external').checked == true) {
            typeMultiContactExternal = 'checked';
        }
    }
    if (typeContactInternal == 'not_checked' && typeContactExternal == 'not_checked' && typeMultiContactExternal == 'not_checked') {
        typeContactExternal = 'checked';
    }
    //Category = INCOMING
    if(cat_id == 'incoming')
    {
        var category = [
            {id:'doctype_mail', type:'label', state:'display'},
            {id:'doctype_res', type:'label', state:'hide'},
            {id:'priority_tr', type:'tr', state:'display'},
            {id:'external_reference_tr', type:'tr', state:'display'},
            {id:'external_id_mandatory', type:'tr', state:'display'},
            {id:'doc_date_label', type:'label', state:'hide'},
            {id:'mail_date_label', type:'label', state:'display'},
            {id:'author_tr', type:'tr', state:'hide'},
            {id:'admission_date_tr', type:'tr', state:'display'},
            {id:'contact_check', type:'tr', state:'hide'},
            {id:'nature_id_tr', type:'tr', state:'display'},
            {id:'label_dep_dest', type:'label', state:'display'},
            {id:'label_dep_exp', type:'label', state:'hide'},
            {id:'label_dep_owner', type:'label', state:'hide'},
            {id:'process_limit_date_use_tr', type:'tr', state:'display'},
            {id:'process_limit_date_tr', type:'tr', state:'display'},
            {id:'department_tr', type:'tr', state:'display'},
            {id:'difflist_tr', type:'tr', state:'display'},
            {id:'box_id_tr', type:'tr', state:'display'},
            {id:'contact_choose_tr', type:'tr', state:'hide'},
            {id:'contact_choose_2_tr', type:'tr', state:'hide'},
            {id:'contact_choose_3_tr', type:'tr', state:'hide'},
            {id:'dest_contact_choose_label', type:'label', state:'hide'},
            {id:'exp_contact_choose_label', type:'label', state:'display'},
            {id:'contact_id_tr', type:'tr', state:'display'},
            {id:'dest_contact', type:'label', state:'hide'},
            {id:'exp_contact', type:'label', state:'display'},
            {id:'dest_multi_contact', type:'label', state:'hide'},
            {id:'exp_multi_contact', type:'label', state:'display'},
            {id:'author_contact', type:'label', state:'hide'},
            {id:'type_multi_contact_external_icon', type:'label', state:'display'},
            {id:'type_contact_internal', type:'radiobutton', state:typeContactInternal},
            {id:'type_contact_external', type:'radiobutton', state:typeContactExternal},
            {id:'type_multi_contact_external', type:'radiobutton', state:typeMultiContactExternal},
            {id:'folder_tr', type:'tr', state:'display'},
            {id:'category_id_mandatory', type:'label', state:'display'},
            {id:'type_id_mandatory', type:'label', state:'display'},
            {id:'priority_mandatory', type:'label', state:'display'},
            {id:'doc_date_mandatory', type:'label', state:'display'},
            {id:'departure_date_mandatory', type:'label', state:'hide'},
            {id:'departure_date_tr', type:'tr', state:'hide'},
            {id:'author_mandatory', type:'label', state:'hide'},
            {id:'admission_date_mandatory', type:'label', state:'display'},
            {id:'type_contact_mandatory', type:'label', state:'display'},
            {id:'contact_mandatory', type:'label', state:'display'},
            {id:'nature_id_mandatory', type:'label', state:'display'},
            {id:'subject_mandatory', type:'label', state:'display'},
            {id:'destination_mandatory', type:'label', state:'display'},
            {id:'process_limit_date_use_mandatory', type:'label', state:'display'},
            {id:'process_limit_date_mandatory', type:'label', state:'display'},
            {id:'chrono_number', type:'label', state:'hide'},
            {id:'chrono_number_mandatory', type:'label', state:'hide'},
            {id:'chrono_number_tr', type:'tr', state:'hide'},
            {id:'folder_mandatory', type:'label', state:'hide'},
            {id:'sr_sender_span', type:'label', state:'hide'},
            {id:'sr_recipient_span', type:'label', state:'display'},
            // NCH01
            {id:'type_id_tr', type:'tr', state:'display'},
            {id:'confidentiality_tr', type:'tr', state:'display'},
            {id:'diff_list_tr', type:'tr', state:'display'},
            {id:'priority_tr', type:'tr', state:'display'},
            {id:'subject_tr', type:'tr', state:'display'},
            {id:'initiator_tr', type:'tr', state:'display'},
            {id:'sender_recipient_tr', type:'tr', state:'display'},
            {id:'doc_date_tr', type:'tr', state:'display'}
            // END NCH01
        ];
    }
    //Category = OUTGOING
    else if(cat_id == 'outgoing')
    {
        var category = [
            {id:'doctype_mail', type:'label', state:'display'},
            {id:'doctype_res', type:'label', state:'hide'},
            {id:'priority_tr', type:'tr', state:'display'},
            {id:'external_reference_tr', type:'tr', state:'hide'},
            {id:'external_id_mandatory', type:'tr', state:'hide'},
            {id:'doc_date_label', type:'label', state:'hide'},
            {id:'mail_date_label', type:'label', state:'display'},
            {id:'author_tr', type:'tr', state:'hide'},
            {id:'admission_date_tr', type:'tr', state:'hide'},
            {id:'contact_check', type:'tr', state:'hide'},
            {id:'nature_id_tr', type:'tr', state:'display'},
            {id:'department_tr', type:'tr', state:'display'},
            {id:'label_dep_dest', type:'label', state:'hide'},
            {id:'label_dep_exp', type:'label', state:'display'},
            {id:'label_dep_owner', type:'label', state:'hide'},
            {id:'difflist_tr', type:'tr', state:'display'},
            {id:'process_limit_date_use_tr', type:'tr', state:'display'},
            {id:'process_limit_date_tr', type:'tr', state:'display'},
            {id:'box_id_tr', type:'tr', state:'display'},
            {id:'contact_choose_tr', type:'tr', state:'hide'},
            {id:'contact_choose_2_tr', type:'tr', state:'hide'},
            {id:'contact_choose_3_tr', type:'tr', state:'hide'},
            {id:'dest_contact_choose_label', type:'label', state:'display'},
            {id:'exp_contact_choose_label', type:'label', state:'hide'},
            {id:'contact_id_tr', type:'tr', state:'display'},
            {id:'dest_contact', type:'label', state:'display'},
            {id:'exp_contact', type:'label', state:'hide'},
            {id:'dest_multi_contact', type:'label', state:'display'},
            {id:'exp_multi_contact', type:'label', state:'hide'},
            {id:'author_contact', type:'label', state:'hide'},
            {id:'type_multi_contact_external_icon', type:'label', state:'display'},
            {id:'type_contact_internal', type:'radiobutton', state:typeContactInternal},
            {id:'type_contact_external', type:'radiobutton', state:typeContactExternal},
            {id:'type_multi_contact_external', type:'radiobutton', state:typeMultiContactExternal},
            {id:'folder_tr', type:'tr', state:'display'},
            {id:'category_id_mandatory', type:'label', state:'display'},
            {id:'type_id_mandatory', type:'label', state:'display'},
            {id:'priority_mandatory', type:'label', state:'display'},
            {id:'doc_date_mandatory', type:'label', state:'display'},
            {id:'departure_date_mandatory', type:'label', state:'hide'},
            {id:'departure_date_tr', type:'tr', state:'hide'},
            {id:'author_mandatory', type:'label', state:'hide'},
            {id:'admission_date_mandatory', type:'label', state:'hide'},
            {id:'type_contact_mandatory', type:'label', state:'display'},
            {id:'contact_mandatory', type:'label', state:'display'},
            {id:'nature_id_mandatory', type:'label', state:'display'},
            {id:'subject_mandatory', type:'label', state:'display'},
            {id:'destination_mandatory', type:'label', state:'display'},
            {id:'process_limit_date_use_mandatory', type:'label', state:'display'},
            {id:'process_limit_date_mandatory', type:'label', state:'display'},
            {id:'chrono_number', type:'label', state:'hide'},
            {id:'chrono_number_tr', type:'tr', state:'hide'},
            {id:'chrono_number_mandatory', type:'label', state:'hide'},
            {id:'folder_mandatory', type:'label', state:'hide'},
            {id:'sr_sender_span', type:'label', state:'display'},
            {id:'sr_recipient_span', type:'label', state:'hide'},
            //NCH01
            {id:'type_id_tr', type:'tr', state:'display'},
            {id:'confidentiality_tr', type:'tr', state:'display'},
            {id:'diff_list_tr', type:'tr', state:'display'},
            {id:'priority_tr', type:'tr', state:'display'},
            {id:'subject_tr', type:'tr', state:'display'},
            {id:'initiator_tr', type:'tr', state:'display'},
            {id:'sender_recipient_tr', type:'tr', state:'display'},
            {id:'doc_date_tr', type:'tr', state:'display'}
            // END NCH01
        ];
    }
    //Category = INTERNAL
    else if(cat_id == 'internal')
    {
        var category = [
        {id:'doctype_mail', type:'label', state:'display'},
        {id:'doctype_res', type:'label', state:'hide'},
        {id:'priority_tr', type:'tr', state:'display'},
        {id:'doc_date_label', type:'label', state:'hide'},
        {id:'mail_date_label', type:'label', state:'display'},
        {id:'author_tr', type:'tr', state:'hide'},
        {id:'admission_date_tr', type:'tr', state:'hide'},
        {id:'contact_check', type:'tr', state:'hide'},
        {id:'nature_id_tr', type:'tr', state:'display'},
        {id:'department_tr', type:'tr', state:'display'},
        {id:'label_dep_dest', type:'label', state:'display'},
        {id:'label_dep_exp', type:'label', state:'hide'},
        {id:'label_dep_owner', type:'label', state:'hide'},
        {id:'difflist_tr', type:'tr', state:'display'},
        {id:'process_limit_date_use_tr', type:'tr', state:'display'},
        {id:'process_limit_date_tr', type:'tr', state:'display'},
        {id:'box_id_tr', type:'tr', state:'display'},
        {id:'contact_choose_tr', type:'tr', state:'hide'},
        {id:'contact_choose_2_tr', type:'tr', state:'hide'},
        {id:'contact_choose_3_tr', type:'tr', state:'hide'},
        {id:'dest_contact_choose_label', type:'label', state:'hide'},
        {id:'exp_contact_choose_label', type:'label', state:'display'},
        {id:'contact_id_tr', type:'tr', state:'display'},
        {id:'dest_contact', type:'label', state:'hide'},
        {id:'exp_contact', type:'label', state:'display'},
        {id:'dest_multi_contact', type:'label', state:'hide'},
        {id:'exp_multi_contact', type:'label', state:'display'},
        {id:'author_contact', type:'label', state:'hide'},
        {id:'type_multi_contact_external_icon', type:'label', state:'hide'},
        {id:'type_contact_internal', type:'radiobutton', state:typeContactInternal},
        {id:'type_contact_external', type:'radiobutton', state:typeContactExternal},
        {id:'type_multi_contact_external', type:'radiobutton', state:typeMultiContactExternal},
        {id:'folder_tr', type:'tr', state:'display'},
        {id:'category_id_mandatory', type:'label', state:'display'},
        {id:'type_id_mandatory', type:'label', state:'display'},
        {id:'priority_mandatory', type:'label', state:'display'},
        {id:'doc_date_mandatory', type:'label', state:'display'},
        {id:'author_mandatory', type:'label', state:'hide'},
        {id:'admission_date_mandatory', type:'label', state:'hide'},
        {id:'type_contact_mandatory', type:'label', state:'display'},
        {id:'contact_mandatory', type:'label', state:'display'},
        {id:'nature_id_mandatory', type:'label', state:'display'},
        {id:'subject_mandatory', type:'label', state:'display'},
        {id:'destination_mandatory', type:'label', state:'display'},
        {id:'process_limit_date_use_mandatory', type:'label', state:'display'},
        {id:'process_limit_date_mandatory', type:'label', state:'display'},
        {id:'chrono_number', type:'label', state:'hide'},
        {id:'chrono_number_tr', type:'tr', state:'hide'},
        {id:'chrono_number_mandatory', type:'label', state:'hide'},
        {id:'folder_mandatory', type:'label', state:'hide'},
            {id:'sr_sender_span', type:'label', state:'hide'},
            {id:'sr_recipient_span', type:'label', state:'display'},
            //NCH01
            {id:'type_id_tr', type:'tr', state:'display'},
            {id:'confidentiality_tr', type:'tr', state:'display'},
            {id:'diff_list_tr', type:'tr', state:'display'},
            {id:'priority_tr', type:'tr', state:'display'},
            {id:'subject_tr', type:'tr', state:'display'},
            {id:'initiator_tr', type:'tr', state:'display'},
            {id:'sender_recipient_tr', type:'tr', state:'display'},
            {id:'doc_date_tr', type:'tr', state:'display'}
            // END NCH01
        ];
    }
    //Category = GED_DOC
    if(cat_id == 'ged_doc')
    {
        var category = [
        {id:'doctype_mail', type:'label', state:'hide'},
        {id:'doctype_res', type:'label', state:'display'},
        {id:'priority_tr', type:'tr', state:'hide'},
        {id:'doc_date_label', type:'label', state:'display'},
        {id:'mail_date_label', type:'label', state:'hide'},
        {id:'author_tr', type:'tr', state:'hide'},
        {id:'admission_date_tr', type:'tr', state:'hide'},
        {id:'contact_check', type:'tr', state:'hide'},
        {id:'nature_id_tr', type:'tr', state:'hide'},
        {id:'label_dep_dest', type:'label', state:'hide'},
        {id:'label_dep_exp', type:'label', state:'hide'},
        {id:'label_dep_owner', type:'label', state:'display'},
        {id:'process_limit_date_use_tr', type:'tr', state:'hide'},
        {id:'process_limit_date_tr', type:'tr', state:'hide'},
        {id:'department_tr', type:'tr', state:'display'},
        {id:'box_id_tr', type:'tr', state:'hide'},
        {id:'contact_choose_tr', type:'tr', state:'hide'},
        {id:'contact_choose_2_tr', type:'tr', state:'hide'},
        {id:'contact_choose_3_tr', type:'tr', state:'hide'},
        {id:'dest_contact_choose_label', type:'label', state:'hide'},
        {id:'exp_contact_choose_label', type:'label', state:'display'},
        {id:'contact_id_tr', type:'tr', state:'display'},
        {id:'dest_contact', type:'label', state:'hide'},
        {id:'exp_contact', type:'label', state:'hide'},
        {id:'dest_multi_contact', type:'label', state:'hide'},
        {id:'exp_multi_contact', type:'label', state:'hide'},
        {id:'author_contact', type:'label', state:'display'},
        {id:'type_multi_contact_external_icon', type:'label', state:'hide'},
        {id:'type_contact_internal', type:'radiobutton', state:typeContactInternal},
        {id:'type_contact_external', type:'radiobutton', state:typeContactExternal},
        {id:'type_multi_contact_external', type:'radiobutton', state:typeMultiContactExternal},
        {id:'folder_tr', type:'tr', state:'display'},
        {id:'category_id_mandatory', type:'label', state:'display'},
        {id:'type_id_mandatory', type:'label', state:'display'},
        {id:'priority_mandatory', type:'label', state:'display'},
        {id:'doc_date_mandatory', type:'label', state:'display'},
        {id:'author_mandatory', type:'label', state:'hide'},
        {id:'admission_date_mandatory', type:'label', state:'display'},
        {id:'type_contact_mandatory', type:'label', state:'display'},
        {id:'contact_mandatory', type:'label', state:'display'},
        {id:'nature_id_mandatory', type:'label', state:'display'},
        {id:'subject_mandatory', type:'label', state:'display'},
        {id:'destination_mandatory', type:'label', state:'display'},
        {id:'process_limit_date_use_mandatory', type:'label', state:'display'},
        {id:'process_limit_date_mandatory', type:'label', state:'display'},
        {id:'chrono_number', type:'label', state:'hide'},
        {id:'chrono_number_mandatory', type:'label', state:'hide'},
        {id:'chrono_number_tr', type:'tr', state:'hide'},
        {id:'folder_mandatory', type:'label', state:'hide'},
        {id:'external_reference_tr', type:'tr', state:'hide'},
        {id:'external_id_mandatory', type:'tr', state:'hide'},
            {id:'sr_sender_span', type:'label', state:'hide'},
            {id:'sr_recipient_span', type:'label', state:'display'},
            //NCH01
            {id:'type_id_tr', type:'tr', state:'display'},
            {id:'confidentiality_tr', type:'tr', state:'display'},
            {id:'subject_tr', type:'tr', state:'display'},
            {id:'initiator_tr', type:'tr', state:'display'},
            {id:'sender_recipient_tr', type:'tr', state:'display'},
            {id:'doc_date_tr', type:'tr', state:'display'}
            // END NCH01
        ];
    } else if(cat_id == 'attachment'){    // NCH01
        var category = [
            {id:'doctype_mail', type:'label', state:'display'},
            {id:'doctype_res', type:'label', state:'hide'},
            {id:'priority_tr', type:'tr', state:'hide'},
            {id:'external_reference_tr', type:'tr', state:'hide'},
            {id:'external_id_mandatory', type:'tr', state:'hide'},
            {id:'doc_date_label', type:'label', state:'hide'},
            {id:'doc_date_tr', type:'tr', state:'hide'},
            {id:'mail_date_label', type:'label', state:'hide'},
            {id:'author_tr', type:'tr', state:'hide'},
            {id:'admission_date_tr', type:'tr', state:'hide'},
            {id:'contact_check', type:'tr', state:'hide'},
            {id:'nature_id_tr', type:'tr', state:'hide'},
            {id:'department_tr', type:'tr', state:'hide'},
            {id:'label_dep_dest', type:'label', state:'hide'},
            {id:'label_dep_exp', type:'label', state:'hide'},
            {id:'process_limit_date_use_tr', type:'tr', state:'hide'},
            {id:'process_limit_date_tr', type:'tr', state:'hide'},
            {id:'box_id_tr', type:'tr', state:'hide'},
            {id:'confidentiality_tr', type:'tr', state:'hide'},
            {id:'contact_choose_tr', type:'tr', state:'hide'},
            {id:'contact_choose_2_tr', type:'tr', state:'hide'},
            {id:'contact_choose_3_tr', type:'tr', state:'hide'},
            {id:'dest_contact_choose_label', type:'label', state:'hide'},
            {id:'exp_contact_choose_label', type:'label', state:'hide'},
            {id:'contact_id_tr', type:'tr', state:'hide'},
            {id:'dest_contact', type:'label', state:'display'},
            {id:'exp_contact', type:'label', state:'hide'},
            {id:'author_contact', type:'label', state:'hide'},
            {id:'type_multi_contact_external_icon', type:'label', state:'hide'},
            {id:'type_contact_internal', type:'radiobutton', state:typeContactInternal},
            {id:'type_contact_external', type:'radiobutton', state:typeContactExternal},
            {id:'type_multi_contact_external', type:'radiobutton', state:typeMultiContactExternal},
            {id:'folder_tr', type:'tr', state:'hide'},
            {id:'category_id_mandatory', type:'label', state:'hide'},
            {id:'type_id_mandatory', type:'label', state:'hide'},
            {id:'type_id_tr', type:'tr', state:'hide'},
            {id:'diff_list_tr', type:'tr', state:'hide'},
            {id:'priority_mandatory', type:'label', state:'hide'},
            {id:'doc_date_mandatory', type:'label', state:'hide'},
            {id:'author_mandatory', type:'label', state:'hide'},
            {id:'admission_date_mandatory', type:'label', state:'hide'},
            {id:'type_contact_mandatory', type:'label', state:'hide'},
            {id:'contact_mandatory', type:'label', state:'hide'},
            {id:'nature_id_mandatory', type:'label', state:'hide'},
            {id:'subject_mandatory', type:'label', state:'hide'},
            {id:'destination_mandatory', type:'label', state:'hide'},
            {id:'process_limit_date_use_mandatory', type:'label', state:'hide'},
            {id:'process_limit_date_mandatory', type:'label', state:'hide'},
            {id:'chrono_number', type:'label', state:'hide'},
            {id:'chrono_number_tr', type:'tr', state:'hide'},
            {id:'chrono_number_mandatory', type:'label', state:'hide'},
            {id:'folder_mandatory', type:'label', state:'hide'},
            {id:'res_id_link', type:'label', state:'hide'},
            {id:'status', type:'tr', state:'hide'},
            {id:'departure_date_mandatory', type:'label', state:'hide'},
            {id:'departure_date_tr', type:'tr', state:'hide'},
            {id:'add_multi_contact_tr', type:'tr', state:'hide'},
            {id:'show_multi_contact_tr', type:'tr', state:'hide'},
            {id:'sr_sender_span', type:'label', state:'hide'},
            {id:'sr_recipient_span', type:'label', state:'hide'},
            {id:'initiator_tr', type:'tr', state:'hide'},
            {id:'sender_recipient_tr', type:'tr', state:'hide'}
        ];
    }

    if(cat_id == 'attachment'){ // only qualification page
        document.getElementById("attachment_tr").style.display='table-row';
        document.getElementById("attach_show").style.display='display';
        $j('#attach_reconciliation').click();

        document.getElementById("subject_tr").style.display = 'none';
        document.getElementById("diff_list_tr").style.display = 'none';
    }else{
        if($j('#attach_reconciliation').length > 0){ // qualification page
            document.getElementById("attachment_tr").style.display='none';
        } else { // indexing page
            document.getElementById("attachment_tr").style.display='table-row';
        }
        $j('#no_attach').click();
        
        document.getElementById("diff_list_tr").style.display = 'display';
        document.getElementById("subject_tr").style.display = 'display';
    }

    if(params_cat)
    {
        process_category(category, display_value_tr, params_cat);
    }
    else
    {
        process_category(category, display_value_tr);
    }

    if(cat_id != null && cat_id != '' && cat_id != NaN)
    {
        //Read the actual box for this category
        //change_box(cat_id);
        new Ajax.Request(path_manage_script,
        {
            method:'post',
            parameters: { category_id : cat_id
                        },
                onSuccess: function(answer){
                eval("response = "+answer.responseText);
                // console.log('1 '+answer.responseText);
                // console.log('2 '+response);
                if(response.status == 0 )
                {
                    var services_to_exec = response.services;
                    if (
                        cat_id == 'outgoing'
                        && (response.doc_date != undefined 
                        && ($('doc_date') != undefined && $('doc_date').value == ''))
                    ) {
                        var doc_date = response.doc_date;
                        $('doc_date').value = doc_date;

                        var destination = response.destination;
                        $('destination').value = destination;
                    }

                    if(cat_id == 'ged_doc'){	// NCH01
                        document.getElementById("diff_list_tr").style.display = 'none';
                    }else if(cat_id == 'attachment'){
                        document.getElementById("subject_tr").style.display = 'none';
                        document.getElementById("diff_list_tr").style.display = 'none';
                    }else{
                        document.getElementById("diff_list_tr").style.display = 'table-row';
                        document.getElementById("subject_tr").style.display = 'table-row';
                    }
                    
                    if(cat_id != 'outgoing' && origin != 'scan'){
                        $j("#choose_file").on("load", function () {
                            $j("#choose_file").off();
                            $j('#choose_file_div').show();
                            $j('#choose_file').contents().find('#with_file').click();
                        });                        
                    } else if (origin != 'scan') {
                        $j("#choose_file").on("load", function () {
                            $j("#choose_file").off();
                            $j('#choose_file').contents().find('#with_file2').click();
                            $j('#choose_file_div').hide();
                        });
                    }

                    if(origin != 'init'){
                        document.getElementById("diff_list_tr").style.display='table-row';
                        document.getElementById("destination").onchange();
                        $j("#destination").trigger("chosen:updated");
                    }

                    var path_scripts = '';
                    var call_func = '';

                    for(var ind=0; ind < services_to_exec.length;ind++)
                    {
                        path_scripts += services_to_exec[ind]['script'] + '$$';
                        call_func += services_to_exec[ind]['function_to_execute']+'('+services_to_exec[ind]['arguments']+');';
                    }
                    //console.log(get_js_script);
                    if(call_func != '' && path_scripts != '' && get_js_script != '')
                    {
                        //console.log('OK');
                        new Ajax.Request(get_js_script,
                        {
                            method:'post',
                            parameters:
                            {
                                scripts : path_scripts
                            },
                             onSuccess: function(answer){
                                //console.log(answer.responseText+call_func);
                                eval(answer.responseText+call_func);
                            }
                        });
                    }
                }
                else
                {
                    try{
                        $('frm_error').innerHTML = response.error_txt;
                        }
                    catch(e){}
                }
            }
        });
    }

    // NCH01
    if(cat_id == 'attachment'){
        if(document.getElementById("attachment_tr")){
            document.getElementById("attachment_tr").style.display='table-row';
            document.getElementById("attach_show").style.display='table-row';
        }
    }else{
        if(document.getElementById("attachment_tr")){
            document.getElementById("attachment_tr").style.diplay = 'none';
            document.getElementById("attach_show").style.display='none';
        }
    }

    var process_date_not_used = $('process_limit_date_use_no').checked;
    if(process_date_not_used){
        activate_process_date(false, 'none');
    }

    var div_indexes = $('comp_indexes');
    if(div_indexes && cat_id == 'attachment')
    {
        document.getElementById("comp_indexes").style.display='none';
    }else if(div_indexes && cat_id !== 'attachment'){
        document.getElementById("comp_indexes").style.display='table-row';
    }
    // END NCH01
}

/**
 * Shows all the required fields and labels for the category and hides all the others
 *
 * @param category String JSON Object of the fields to display
 **/
function process_category(category, display_value_tr, params_cat)
{
    var tr_display_val = display_value_tr || 'table-row';
    var no_param = true;
    if(params_cat)
    {
        no_param = false;
    }
    if(category != null && category.length > 0)
    {
        for(var i=0; i < category.length; i++)
        {
            var item = $(category[i]['id']);
            if(item != null)
            {
                if(category[i]['state'] == 'hide' )
                {
                    Element.setStyle(item, {display : 'none'});
                    //item.style.display = 'none';
                }
                else if(category[i]['state'] == 'display')
                {
                    if(category[i]['type'] == 'label')
                    {
                        Element.setStyle(item, {display : 'inline'});
                        //item.style.display = 'inline';
                    }
                    else if(category[i]['type'] == 'tr')
                    {
                        Element.setStyle(item, {display : tr_display_val});
                        //item.style.display = tr_display_val;
                    }
                }
                else if(category[i]['state'] == 'checked')
                {
                    item.checked = true;
                    //~ if(  no_param || typeof(params_cat[category[i]['id']]) == undefined ||  typeof(params_cat[category[i]['id']]['onchange']) == undefined || params_cat[category[i]['id']]['onchange'] == true )
                    //~ {
                        //~ item.onchange();
                    //~ }
                    if(  no_param || typeof(params_cat[category[i]['id']]) == undefined ||  typeof(params_cat[category[i]['id']]['onclick']) == undefined || params_cat[category[i]['id']]['onclick'] == true )
                    {
                        item.onclick();
                    }
                }
                else if(category[i]['state'] == 'not_checked')
                {
                    item.checked = false;
                }
            }
        }
    }
}

/** Declaration of the autocompleter object used for the contacts*/
var contact_autocompleter;

/**
 * Launch the Ajax autocomplete object to activate autocompletion on contacts
 *
 * @param path_script String Path to the Ajax script
 **/
function launch_autocompleter_contacts(path_script, id_text, id_div, cat_id)
{
    var input  = id_text || 'contact';
    var div    = id_div  || 'show_contacts';
    
    var params = get_contacts_params();
    
    if (contact_autocompleter && contact_autocompleter.element == $$('#' + input)[0])
        contact_autocompleter.options.defaultParams = params;
    else if(path_script)
        contact_autocompleter = new Ajax.Autocompleter(input, div, path_script, {
            method:'get',
            paramName:'Input',
            parameters: params,
            minChars: 2
        });
    else return false;
}

/**
 * Launch the Ajax autocomplete object to activate autocompletion on contacts en put address_id and contact_id in an hidden input
 *
 * @param path_script String Path to the Ajax script
 **/
function launch_autocompleter_contacts_v2(path_script, id_text, id_div, cat_id, contact_id, address_id, path_script2)
{
    var input  = id_text || 'contact';
    var div    = id_div  || 'show_contacts';
    
    var params = get_contacts_params();

    if (contact_autocompleter && contact_autocompleter.element == $j('#' + input)[0]) {
        contact_autocompleter.options.defaultParams = params;
    } else if(path_script) {
        contact_autocompleter = new Ajax.Autocompleter(input, div, path_script, {
            method:'get',
            paramName:'Input',
            parameters: params,
            minChars: 2,
            //loading
            frequency: 0.5, // NOTICE THIS
             indicator: 'searching_autocomplete', // AND THIS
             onShow : function(element, update) {
                Effect.Appear(update,{duration:0});
            },
            afterUpdateElement: function (text, li){
                var all_li = li.id;
                var res = all_li.split(",");
                parent.$(contact_id).value = res[0];
                if (typeof (parent.$(contact_id).onchange) == 'function')
                    parent.$(contact_id).onchange();
                parent.$(address_id).value = res[1];
                if (path_script2 && res[1]) {
                    getDepartment(path_script2, res[1]);
                }
                $j("#" + input).css('background-color', li.getStyle('background-color'));
            }
        });
    } else {
        return false;
    }
}

function launch_autocompleter_contacts_search(path_script, id_text, id_div, cat_id, contact_id, address_id)
{
    var input  = id_text || 'contact';
    var div    = id_div  || 'show_contacts';
    
    var params = 'table=contacts';

    if (contact_autocompleter && contact_autocompleter.element == $j('#' + input)[0]) {
        contact_autocompleter.options.defaultParams = params;
    } else if(path_script) {
        contact_autocompleter = new Ajax.Autocompleter(input, div, path_script, {
            method:'get',
            paramName:'Input',
            parameters: params,
            minChars: 2,
            //loading
            frequency: 0.5, // NOTICE THIS
             indicator: 'searching_autocomplete', // AND THIS
             onShow : function(element, update) {
                Effect.Appear(update,{duration:0});
            },
            afterUpdateElement: function (text, li){
                var all_li = li.id;
                var res = all_li.split(",");
                $(contact_id).value = res[0];
                if (typeof ($(contact_id).onchange) == 'function')
                    $(contact_id).onchange();
                    $(address_id).value = res[1];
            }
        });
    } else {
        return false;
    }
}

function launch_autocompleter2_contacts_v2(path_script, id_text, id_div, cat_id, contact_id, address_id, path_script2)
{
    var input  = id_text || 'contact';
    var div    = id_div  || 'show_contacts';
    
    if (contact_autocompleter && contact_autocompleter.element == $j('#' + input)[0])
        contact_autocompleter.options.defaultParams = params;
    else if(path_script)
        contact_autocompleter = new Ajax.Autocompleter2(input, div, path_script, {
            method:'get',
            paramName:'Input',
            parameters: 'table=contacts&contact_type=letter',
            minChars: 2,
            afterUpdateElement: function (text, li){
                var all_li = li.id;
                var res = all_li.split(",");
                if (parent.$(contact_id) == null) {
                    top.$(contact_id).value = res[0];
                    top.$(address_id).value = res[1];
                } else {
                    parent.$(contact_id).value = res[0];
                    parent.$(address_id).value = res[1];
                }
                
                
            }
        });
    else {
        return false;
    }
}

function getDepartment(path_script, address_id) {
    new Ajax.Request(path_script,
    {
        method:'post',
        parameters: { address_id : address_id
                    },
            onSuccess: function(answer){
            eval("response = "+answer.responseText);
            if(response.status == 0 ) {
                parent.$("department_number").value = response.departement_name;
                parent.$("department_number_id").value = response.departement_id;
            }
        }
    });
}

function launch_autocompleter_choose_contact(path_script, id_text, id_div, cat_id, contact_id){
    var input  = id_text || 'contact';
    var div    = id_div  || 'show_contacts';
    
    // var params = get_contacts_params();

    contact_autocompleter = new Ajax.Autocompleter(input, div, path_script, {
        method:'get',
        paramName:'what',
        // parameters: params,
        minChars: 2,
        afterUpdateElement: function (text, li){
            $(contact_id).value = li.id;
        }
    });

}

function putInSessionContact(path_script){
    var contactSelected = $('contactSelect').options[$('contactSelect').selectedIndex].value;
    if (contactSelected == "") {
        alert("Choisissez un contact");
    } else {
        new Ajax.Request(path_script,
        {
            method:'post',
            parameters: {
                contactid : contactSelected
            },
            onSuccess: function(answer){
                    document.location = 'index.php?display=false&dir=my_contacts&page=create_address_iframe';
            }
        });
    }
}

function getContacts(path_script, id, mode){
    new Ajax.Request(path_script,
    {
        method:'post',
        parameters: {
            type_id: id,
            mode: mode
        },
        onSuccess: function(answer){
            if(mode=="view"){
                if (id != "") {
                    $('contacts_created_tr').setStyle({display : 'table-row'});
                    $('contacts_created').innerHTML = answer.responseText;
                } else {
                    $('contacts_created_tr').setStyle({display : 'none'});
                }
            } else if(mode="set"){
                //$('contactSelect').innerHTML = answer.responseText;
            }
        }
    });
}

/**
 * Gets the parameters for the contacts : the table which must be use in the ajax script
 *
 * @return String parameters
 **/
function get_contacts_params(name_radio)
{
    var check = name_radio || 'type_contact';
    var arr = get_checked_values(check);
    var params = '';
    var cat = '';
    
    if (parent.document.getElementById('category_id').value != undefined) {
        cat = parent.document.getElementById('category_id').value;
    }else if (parent.parent.document.getElementById('category_id').value != undefined) {
        cat = parent.parent.document.getElementById('category_id').value;
    }else{
        cat = document.getElementById('category_id').value;
    }
    if (arr.length == 0) {
        var contact_type = 'letter';
        params = 'table=contacts&contact_type=' + contact_type;
    } else {
        if (arr[0] == 'internal') {
            params = 'table=users';
        } else if(arr[0] == 'external') {
            params = 'table=contacts';
        } else {
            params = 'table=contacts';
        }
    }
    
    return params;
}

/**
 * Update the parameters of the contact_autocompleter object
 *
 **/
function update_contact_autocompleter()
{
    var params = get_contacts_params();
    if( contact_autocompleter  )
    {
        if(contact_autocompleter.options.defaultParams == null)
        {
            contact_autocompleter.options.defaultParams = 'table=contacts';
        }
        var old_param = contact_autocompleter.options.defaultParams;
        var new_param =old_param.substring(0, old_param.indexOf('table=') -1) ;
        if(new_param && new_param !='')
        {
            new_param += '&'+params;
        }
        else
        {
            new_param =  params;
        }
        contact_autocompleter.options.defaultParams = new_param;
    }
}

/**
 * When you change the contact type, the table used for the autocompletion change
 *
 * @param path_autocomplete String Path to the Ajax script
 **/
function change_contact_type(path_autocomplete, empty_contact_div, id_internal, id_external, id_contact, id_multi_external)
{
    var contact_id = id_contact || 'contact';
    var external_type = id_external || 'type_contact_external';
    var internal_type = id_internal || 'type_contact_internal';
    var multi_external_type = id_multi_external || 'type_multi_contact_external';
    var create_contact = $('create_contact');
    var contact_card = $('contact_card');
    var valid_multi_contact = $('valid_multi_contact');
    var email = $('email');
    var to_multi_contact = $('to_multi_contact');
    var to = $('to');
    var show_multi_contact = $('show_multi_contact_tr');
    var add_multi_contact = $('add_multi_contact_tr');
    var contact_id_tr = $('contact_id_tr');
    if(typeof(empty_contact_div) == 'undefined' || empty_contact_div == null)
    {
        var empty_contact = true ;
    }
    else
    {
        var empty_contact = empty_contact_div ;
    }
    if(create_contact)
    {
        if($(internal_type).checked == true)
        {
            Element.setStyle(create_contact, {display : 'none'});
            //create_contact.style.display = 'none';
			Element.setStyle(email, {display : 'none'});
			Element.setStyle(valid_multi_contact, {display : 'none'});
			Element.setStyle(to_multi_contact, {display : 'none'});
			Element.setStyle(to, {display : 'none'});
			Element.setStyle(show_multi_contact, {display : 'none'});
			Element.setStyle(add_multi_contact, {display : 'none'});
            Element.setStyle(contact_id_tr, {display : 'table-row'});
            if($j('#create_contact_div').length > 0)
            {
                Element.setStyle('create_contact_div', {display : 'none'});
            }
            if($j('#info_contact_div').length > 0) {
                Element.setStyle('info_contact_div', {display : 'none'});
            }
        }
        else if($(external_type ).checked == true || $(multi_external_type ).checked == true )
        {
            var cat_id = $(category_id).options[$(category_id).selectedIndex].value; // NCH01
            Element.setStyle(create_contact, {display : 'inline'});
            //create_contact.style.display = 'inline';
			if($(multi_external_type ).checked == true){
				Element.setStyle(email, {display : 'inline'});
				Element.setStyle(valid_multi_contact, {display : 'inline'});
				Element.setStyle(to_multi_contact, {display : 'table-cell'});
				Element.setStyle(to, {display : 'inline'});
                // NCH01
				if(cat_id !== 'attachment'){
                    Element.setStyle(show_multi_contact, {display : 'table-row'});
                }
				Element.setStyle(add_multi_contact, {display : 'table-row'});
				Element.setStyle(contact_id_tr, {display : 'none'});
			}else if ($(external_type ).checked == true){
				Element.setStyle(email, {display : 'none'});
				Element.setStyle(valid_multi_contact, {display : 'none'});
				Element.setStyle(to_multi_contact, {display : 'none'});
				Element.setStyle(to, {display : 'none'});
				Element.setStyle(show_multi_contact, {display : 'none'});
				Element.setStyle(add_multi_contact, {display : 'none'});
				// NCH01
                if(cat_id !== 'attachment') {
                    Element.setStyle(contact_id_tr, {display : 'table-row'});
                }
			}
        }
    }
    if(empty_contact)
    {
        $(contact_id).value='';
        $("contactid").value='';
        $("contactcheck").value='success';
        $("contact_check").innerHTML = "";
        $("contact").style.backgroundColor='#ffffff';
    }
    display_contact_card('hidden');
    update_contact_autocompleter();
}

function init_validation(path_autocomplete_contact, display_value_tr, path_manage_script, get_js_script)
{
    var param_cat = {'type_contact_internal' : {'onclick' : false} , 'type_contact_external' : {'onclick' : false}, 'type_multi_contact_external' : {'onclick' : false}};
    change_category($('category_id').value, display_value_tr,path_manage_script,get_js_script, param_cat, 'init');
    change_contact_type(path_autocomplete_contact, false);
    $('contact').onchange();
    //$('destination').onchange();
}

function clear_error(id_error)
{
    //console.log("'"+id_error+"'");
    var error_div = $(id_error);
    //console.log(error_div);
    if(error_div)
    {
        error_div.update();
    }
}

function display_contact_card(mode, id)
{
	if(id){
		var contact_card = $(id);

	}else{
		var contact_card = $('contact_card');
	}

    if ($('contactidAttach')) {
        var contactid = $('contactidAttach').value;
    } else {
        if ($('contactid')) {
            var contactid = $('contactid').value;
        }        
    }
    

    if(contact_card && (mode == 'hidden' || mode == 'visible') && contactid != '')
    {
        Element.setStyle(contact_card, {visibility : mode});
    } else if (contactid == '') {
        Element.setStyle(contact_card, {visibility : 'hidden'});
    }
}

function changeCycle(path_manage_script)
{
    var policy_id = $('policy_id');
    if(policy_id.value != '') {
        new Ajax.Request(path_manage_script,
        {
            method:'post',
            parameters: { policy_id : policy_id.value
                        },
                onSuccess: function(answer){
                eval("response = "+answer.responseText);
                if(response.status == 0 || response.status == 1) {
                    if(response.status == 0) {
                        //response.selectClient;
                        $('cycle_div').innerHTML = response.selectCycle;
                    } else {
                        //
                    }
                } else {
                    try {
                        $('frm_error').innerHTML = response.error_txt;
                    }
                    catch(e){}
                }
            }
        });
    } //else {
        //if($('policy_id')) {
            //Element.setStyle($('policy_id'), {display : 'none'})
        //}
    //}
}

function getIframeContent(path_manage_script)
{
    userAgent = navigator.userAgent;
    var isIE = userAgent.indexOf("MSIE ") > -1 || userAgent.indexOf("Trident/") > -1 || userAgent.indexOf("Edge/") > -1;
    if(isIE){
        var iframe = document.frames['file_iframe'];
        doc_content = iframe.document
    } else {
        var iframe = document.getElementById("file_iframe");
        doc_content = iframe.contentDocument;
    }

    if ($j('#choose_file_div') && doc_content) {
        var choose_file_div = $('choose_file_div');
        if (choose_file_div.style.display == 'none') {            
            var iframeContent = doc_content;
            var templateContent2 = iframeContent.getElementById("template_content_ifr");
            if (templateContent2) {
                var templateContent = templateContent2.contentDocument;
                if (templateContent) {
                    var templateContentBody = templateContent.getElementById("tinymce");
                    //window.alert(templateContentBody.innerHTML);
                    new Ajax.Request(path_manage_script,
                    {
                        method:'post', asynchronous:false,
                        parameters: { template_content : templateContentBody.innerHTML
                        },
                        onSuccess: function(answer){
                            eval("response = "+answer.responseText);
                            if(response.status == 0) {
                                //
                            } else {
                                //alert(answer.responseText);
                            }
                        }
                    });
                }
            }
        }
    }
}

var addMultiContacts = function (idField, idList, theUrlToListScript, paramNameSrv, minCharsSrv, contact_id, address_id) {
     new Ajax.Autocompleter(
         idField,
         idList,
         theUrlToListScript,
         {
             paramName : paramNameSrv,
             minChars : minCharsSrv,
             tokens : ',',
             //afterUpdateElement : extractEmailAdress,
			parameters : 'table=contacts',
            //loading
            frequency: 0.5, // NOTICE THIS
             indicator: 'searching_autocomplete_multi', // AND THIS
             onShow : function(element, update) {
                Effect.Appear(update,{duration:0});
            },
            afterUpdateElement: function (text, li){
                var all_li = li.id;
                var res = all_li.split(",");
                parent.$(contact_id).value = res[0];
                if(res[1]==' ') {
                    res[1] = 0;
                }
                parent.$(address_id).value = res[1];
            }
         });
 };
 
function updateMultiContacts(path, action, contact, target, array_index, addressid, contactid) {

	new Ajax.Request(path,
	{
		method:'post',
		parameters: { url : path,
					  'for': action,
					  contact: contact,
					  field: target,
					  index: array_index,
                      addressid: addressid,
                      contactid: contactid
					},
		onLoading: function(answer) {
			$('loading_' + target).style.display='inline';
		},
		onSuccess: function(answer) {
			eval("response = "+answer.responseText);
			if(response.status == 0){
				$(target).innerHTML = response.content;
				if (action == 'add') {
                    $j('#contactid').val('');
                    $j('#is_multicontacts').val('');
                    $j('#addressid').val('');
                    $j('#email').val('');
                }
			} else {
				alert(response.error);
				eval(response.exec_js);
			}
			// $('loading_' + target).style.display='none';
		}
	});
}

function update_contact_type_session(path)
{
    var check = 'type_contact';
    // var arr = get_checked_values(check);
    var arr = ["multi_external"];
    var params = '';
    
    if (arr.length == 0) {
        var contact_type = 'letter';
        params = 'table=contacts&contact_type=' + contact_type;
    } else {
        if (arr[0] == 'internal') {
            params = 'users';
        } else if(arr[0] == 'external') {
            params = 'contacts';
        } else if(arr[0] == 'multi_external') {
            params = 'multi';
        } else {
            params = 'contacts';
        }
    }
    new Ajax.Request(path,
    {
        method:'post',
        parameters:
        {
            paramsRequest : params
        },
        onSuccess: function(answer){
            eval(answer.responseText);
        }
    });
}

function change_category_actions(path_manage_script, resId, collId,category_id)
{  
    
    if(category_id != '') {
        if (resId === undefined && collId === undefined) {
            new Ajax.Request(path_manage_script,
            {
                method:'post',
                parameters: { category_id : category_id
                            },
                    onSuccess: function(answer){
                    eval("response = "+answer.responseText);
                    if(response.status == 0 || response.status == 1) {
                        if(response.status == 0) {
                            $('actionSpan').innerHTML = response.selectAction;
                        } else {
                            //
                        }
                    } else {
                        try {
                            $('actionSpan').innerHTML = response.error_txt;
                        }
                        catch(e){}
                    }
                }
            });
        } else {
            new Ajax.Request(path_manage_script,
            {
                method:'post',
                parameters: { category_id : category_id, resId : resId, collId : collId
                            },
                    onSuccess: function(answer){
                    eval("response = "+answer.responseText);
                    if(response.status == 0 || response.status == 1) {
                        if(response.status == 0) {
                            $('actionSpan').innerHTML = response.selectAction;
                        } else {
                            //
                        }
                    } else {
                        try {
                            $('actionSpan').innerHTML = response.error_txt;
                        }
                        catch(e){}
                    }
                }
            });
        }
    }
}

function set_new_contact_address(path_manage_script, id_div, close,transmission){
    if (close == "true") {
        new Effect.toggle(parent.document.getElementById(id_div), 'blind', {delay:0.2});  
    }
    
    if(transmission != '' & transmission != '0' && transmission != undefined){
        new Ajax.Request(path_manage_script,
    {
        method:'post',
        parameters: {},
        onSuccess: function(answer){
            eval("response = "+answer.responseText);
            if (parent.$('transmissionContact_attach'+transmission)) {
                parent.$('transmissionContact_attach'+transmission).value = response.contactName;
            } else if (parent.$('contact')) {
                parent.$('contact').value = response.contactName;
            }
            if (parent.$('transmissionContactidAttach'+transmission)) {
                parent.$('transmissionContactidAttach'+transmission).value = response.contactId;
            } else if (parent.$('contactid')){
                parent.$('contactid').value = response.contactId;
            }
            if (parent.$('addressidAttach'+transmission)) {
                parent.$('addressidAttach'+transmission).value = response.addressId;
            } else if (parent.$('transmissionAddressidAttach'+transmission)){
                parent.$('transmissionAddressidAttach'+transmission).value = response.addressId;
            }
            getDepartment('index.php?display=true&page=getDepartment', response.addressId);
        }       
    });
    }else{
        new Ajax.Request(path_manage_script,
        {
        method:'post',
        parameters: {},
        onSuccess: function(answer){
            eval("response = "+answer.responseText);
            if (parent.$('contact_attach')) {
                parent.$('contact_attach').value = response.contactName;
            } else if (parent.parent.$('contact_attach')) {
                parent.parent.$('contact_attach').value = response.contactName;
            } else if (parent.$('contact') && parent.$('add_multi_contact_tr').style.display == 'none') {
                parent.$('contact').value = response.contactName;
            } else if (parent.parent.$('contact')  && parent.parent.$('add_multi_contact_tr').style.display == 'none') {
                parent.parent.$('contact').value = response.contactName;
            }
            if (response.rateColor != "") {
                parent.$j('#contact').css('background-color', response.rateColor);
            }
            if (parent.$('email') && parent.$('add_multi_contact_tr').style.display != 'none') {
                parent.$('email').value = response.contactName;
            } else if (parent.parent.$('email') && parent.parent.$('add_multi_contact_tr').style.display != 'none') {
                parent.parent.$('email').value = response.contactName;
            }
            if (parent.$('contactidAttach')) {
                parent.$('contactidAttach').value = response.contactId;
            } else if (parent.parent.$('contactidAttach')){
                parent.parent.$('contactidAttach').value = response.contactId;
            } else if (parent.$('contactid')){
                parent.$('contactid').value = response.contactId;
            } else if (parent.parent.$('contactid')){
                parent.parent.$('contactid').value = response.contactId;
            }
            if (parent.$('addressidAttach')) {
                parent.$('addressidAttach').value = response.addressId;
            } else if (parent.parent.$('addressidAttach')){
                parent.parent.$('addressidAttach').value = response.addressId;
            } else if (parent.$('addressid')){
                parent.$('addressid').value = response.addressId;
            } else if (parent.parent.$('addressid')){
                parent.parent.$('addressid').value = response.addressId;
            }
            getDepartment('index.php?display=true&page=getDepartment', response.addressId);
        }       
    });
    }
}

function check_date_exp(path_manage_script, path_link){
    var check_days_before = $('check_days_before').value;

    if (check_days_before > 0) {
        var contact_id = $('contactid').value;
        var address_id = $('addressid').value;
        var res_id = $('values').value;
        var e = $("category_id");
        var category = e.options[e.selectedIndex].value;
        var scriptAddons = '';
        new Ajax.Request(path_manage_script,
        {
            method:'post',
            parameters: {
                contact_id : contact_id,
                address_id : address_id,
                res_id : res_id,
                category : category
            },
            onSuccess: function(answer){
                if(answer.responseText == "success"){
                    // document.getElementById('contact').style.backgroundColor='#ffffff';
                    document.getElementById('contact_check').style.display='none';
                    document.getElementById('contactcheck').value = answer.responseText;
                } else {
                    if (document.getElementById('to_link') != null) {
                        scriptAddons = "document.getElementById('to_link').click();";
                    }
                    document.getElementById('contact_check').style.display='table-row';
                    document.getElementById("contact_check").innerHTML = "<td colspan=\"3\" style=\"font-size: 9px;text-align: center;color:#ea0000;\">Au moins un courrier enregistré dans les "+check_days_before+" derniers jours est affecté au même contact. "+
                                                                            "<input type='button' class='button' value='Voir' onclick=\""+scriptAddons+"window.open('"+path_link+"',"+
                                                                            " 'search_doc_for_attachment', 'scrollbars=yes,menubar=no,toolbar=no,resizable=yes,status=no,width=1100,height=775');\"/></td>";
                    document.getElementById('contactcheck').value = answer.responseText;
                }
            }
        });
    }
}

function reset_check_date_exp(){
	document.getElementById('contact').style.backgroundColor='#ffffff';
	document.getElementById('contact_check').style.display='none';
	document.getElementById('contactcheck').value = 'success';
}

function hideSelectFile(){
    var e = $("category_id");
    var category = e.options[e.selectedIndex].value;
    if (category == "outgoing") {
        // $('choose_file_div').style.display='none';
        window.frames["choose_file"].$('with_file2').click();       
    } else {
        // $('choose_file_div').style.display='block';
        window.frames["choose_file"].$('with_file').click(); 
    }
}

function affiche_chrono(){
    
    var type_id = document.getElementById('attachment_types').options[document.getElementById('attachment_types').selectedIndex];

    if (type_id.getAttribute('with_chrono') == 'true') { 
        $('get_chrono_display').setStyle({display: 'none'});     
        $('chrono_label').setStyle({display: 'inline'});
        $('chrono_display').setStyle({display: 'inline'});
            new Ajax.Request('index.php?display=true&module=attachments&page=get_chrono_attachment',
                {
                    method:'post',
                    parameters:
                    {
                        type_id : type_id
                    },
                     onSuccess: function(answer){
                        eval("response = "+answer.responseText);
                        $('chrono_display').value=response.chronoNB;
                        $('chrono').value=response.chronoNB;
                    }
                });
    } else if (type_id.getAttribute('get_chrono') != '') {
        $('chrono_display').setStyle({display: 'none'});
        $('chrono_display').value='';     
        $('chrono_label').setStyle({display: 'inline'});
        $('get_chrono_display').setStyle({display: 'inline'});
        $('chrono').value='';
            new Ajax.Request('index.php?display=true&module=attachments&page=get_other_chrono_attachment',
                {
                    method:'post',
                    parameters:
                    {
                        type_id : type_id.value
                    },
                     onSuccess: function(answer){
                        eval("response = "+answer.responseText);
                        $('get_chrono_display').innerHTML=response.chronoList;
                    }
                });
    } else {
        $('chrono_label').setStyle({display: 'none'});
        $('get_chrono_display').setStyle({display: 'none'});
        $('chrono_display').setStyle({display: 'none'});
        $('chrono_display').value='';
        $('chrono').value='';
    }
}

function showEditButton(){

    var modele_id = document.getElementById('templateOffice').options[document.getElementById('templateOffice').selectedIndex];

    if (modele_id.value != '') {
        $('edit').setStyle({display: 'inline'});
        if ($('not_enabled')) {
            $('not_enabled').setStyle({display: 'inline'});
		}
        if ($('choose_file')){
            $('choose_file').setStyle({display: 'none'});
		}
        if ($('file_loaded')) {
            $('file_loaded').setStyle({display: 'none'});
        }
    } else {
        $('edit').setStyle({display: 'none'});
        if ($('not_enabled')) {
            $('not_enabled').setStyle({display: 'none'});
        }
        if ($('file_loaded')) {
            $('file_loaded').setStyle({display: 'none'});
        }
    }
}

function loadInfoContact(){
    var reg = /^\d+$/;
    var pathScript = '';

    if(!reg.test(document.getElementById('contactid').value)){
        pathScript = 'index.php?display=false&page=user_info&id='+document.getElementById('contactid').value;
    }else{
        pathScript = 'index.php?display=false&dir=my_contacts&page=info_contact_iframe&seeAllAddresses&contactid='+document.getElementById('contactid').value+'&addressid='+document.getElementById('addressid').value;
    }
    
    return pathScript;
}

function loadInfoContactSenderRecipient(){

    var pathScript = '';

    pathScript = 'index.php?display=false&dir=my_contacts&page=info_contact_iframe&mode=editDetailSender&editDetailSender&popup&sender_recipient_id='+document.getElementById('sender_recipient_id').value+'&sender_recipient_type='+document.getElementById('sender_recipient_type').value;

    return pathScript;
}

function loadIndexingModel(actionId) {
    if ($j('#complementary_fields').css('display') == 'none') {
        new Effect.toggle('complementary_fields', 'blind', {delay:0.2});
        whatIsTheDivStatus('complementary_fields', 'divStatus_complementary_fields');
    }
    if ($j('#indexing_models_select').val() != 'none') {
        $j('#action2_indexingmodels').css('visibility','visible');
        $j('#action1_indexingmodels').removeClass('fa-plus');
        $j('#action1_indexingmodels').addClass('fa-edit');

        $j.ajax({
            url : 'index.php?display=true&page=ajaxIndexingModel',
            type : 'POST',
            dataType : 'JSON',
            data: {
                mode : 'get',
                id: $j('#indexing_models_select').val()
            },
            success : function(response){
                if (response.status == 0) {
                    var content = JSON.parse(response.result_txt);

                    $j.each(content, function( index, value ) {
                        if ($j('#'+index).length) {
                            if (index == 'destination' && $j('#'+index+' option[value="'+value+'"]').is(':disabled')) {
                                //do nothing
                            } else {
                                $j('#'+index).val(value);
                            }
                            $j('#category_id').change();

                            if ($j('#'+index).is('select') && index != 'thesaurus') {
                                if (index == 'type_id') {
                                    change_doctype(value, 'index.php?display=true&dir=indexing_searching&page=change_doctype', 'Type de document non valide',
                                        actionId, 'index.php?display=true&page=get_content_js', 'table-row');
                                } else {
                                    $j('#'+index).change();
                                }
                                $j('#'+index).trigger("chosen:updated");
                            } else if (index == 'thesaurus') {
                                $j.each(value, function( ind, thes ) {
                                    add_thes(thes.id,thes.label);
                                });
                            }
                        }
                    });
                }
            },
            error : function(error){
                alert('ERROR!');
            }
        
        });
    } else {
        $j('#action2_indexingmodels').css('visibility','hidden');
        $j('#action1_indexingmodels').removeClass('fa-edit');
        $j('#action1_indexingmodels').addClass('fa-plus');
        $j('#category_id').val('');
        $j('#category_id').change();
        $j('#category_id').trigger("chosen:updated");
        $j('#type_id').val('');
        $j('#priority').val('');
        $j('#priority').change();
        $j('#priority').trigger("chosen:updated");
        $j('#nature_id').val('');
        $j('#nature_id').change();
        $j('#nature_id').trigger("chosen:updated");
        $j('#subject').val('');
        $j('#destination').val('');
        $j('#destination').change();
        $j('#destination').trigger("chosen:updated");
        $j('#folder').val('');
        $j('#folder').change();
        $j('#folder').trigger("chosen:updated");
        $j('#thesaurus').val('');
        $j('#thesaurus').trigger("chosen:updated");
    }
}

function saveIndexingModel() {

    if ($j('#indexing_models_select').val() == 'none') {
        var label_fields =[]
        if ($j('[for=category_id]').length) {
            label_fields.push($j('[for=category_id]').filter(function() { return $j(this).css("display") != "none" }).text());   
        }
        if ($j('[for=type_id]').length) {
            label_fields.push($j('[for=type_id]').filter(function() { return $j(this).css("display") != "none" }).text());
            
        }
        if ($j('[for=priority]').length) {
            label_fields.push($j('[for=priority]').filter(function() { return $j(this).css("display") != "none" }).text());
            
        }
        if ($j('[for=nature_id]').length) {
            label_fields.push($j('[for=nature_id]').filter(function() { return $j(this).css("display") != "none" }).text());
            
        }
        if ($j('[for=subject]').length) {
            label_fields.push($j('[for=subject]').filter(function() { return $j(this).css("display") != "none" }).text());
            
        }
        if ($j('[for=destination]').length) {
            label_fields.push($j('[for=destination]').filter(function() { return $j(this).css("display") != "none" }).text());
            
        }
        if ($j('[for=folder]').length) {
            label_fields.push($j('[for=folder]').filter(function() { return $j(this).css("display") != "none" }).text());
            
        }
        if ($j('[for=thesaurus]').length) {
            label_fields.push($j('[for=thesaurus]').filter(function() { return $j(this).css("display") != "none" }).text());            
        }
        
        var label = prompt("Nom du modèle:\nChamps concernés : "+label_fields.join(', '), "");
        mode = 'add';
    } else {
        mode = 'up';
    }
    

    if (label || $j('#indexing_models_select').val() != 'none') {
        var obj = {};
        if ($j('#category_id').val() != '') {
            obj['category_id'] = $j('#category_id').val();
        }
        if ($j('#type_id').val() != '') {
            obj['type_id'] = $j('#type_id').val();
        }
        if ($j('#priority').val() != '') {
            obj['priority'] = $j('#priority').val();
        }
        if ($j('#nature_id').val() != '') {
            obj['nature_id'] = $j('#nature_id').val();
        }
        if ($j('#subject').val() != '') {
            obj['subject'] = $j('#subject').val();
        }
        if ($j('#destination').val() != '') {
            obj['destination'] = $j('#destination').val();
        }
        if ($j('#folder').val() != '') {
            obj['folder'] = $j('#folder').val();
        }
        if ($j('#thesaurus').length) {
            obj['thesaurus'] = [];
                $j("#thesaurus > option").each(function() {
                $tmp = {
                    "id" : this.value,
                    "label" : this.text
                }
                obj['thesaurus'].push($tmp);
            });
        }
        $j.ajax({
            url : 'index.php?display=true&page=ajaxIndexingModel',
            type : 'POST',
            dataType : 'JSON',
            data: {
                id : $j('#indexing_models_select').val(),
                mode : mode,
                label: label,
                content: JSON.stringify(obj)
            },
            success : function(response){
                if (response.status == 0) {
                    //alert(response.result_txt);
                    if (mode == 'add') {
                        var res_content = JSON.parse(response.result);
                        $j('#indexing_models_select').append('<option value="'+res_content.id+'">'+res_content.label+'</option>');
                        $j('#indexing_models_select').val(res_content.id);
                        $j('#indexing_models_select').trigger("chosen:updated");
                    }
                }
            },
            error : function(error){
                alert('ERROR!');
            }
     
        });
    }

}

function delIndexingModel() {

    if (confirm("Supprimer le modèle ?")) {
        $j.ajax({
            url : 'index.php?display=true&page=ajaxIndexingModel',
            type : 'POST',
            dataType : 'JSON',
            data: {
                mode : 'del',
                id: $j('#indexing_models_select').val(),
            },
            success : function(response){
                if (response.status == 0) {
                    //alert(response.result_txt);
                    $j('#indexing_models_select option:selected').remove();
                    $j('#indexing_models_select option:selected').change();
                    $j('#indexing_models_select').trigger("chosen:updated");
                }
            },
            error : function(error){
                alert('ERROR!');
            }
        
        });    
    }
}

function initSenderRecipientAutocomplete(inputId, mode, alternateVersion, cardId) {
    var route = '';
    if (mode == 'contactsUsers') {
        route = '../../rest/autocomplete/contactsUsers';
    } else {
        route = '../../rest/autocomplete/entities';
    }

    $j("#" + inputId).typeahead({
        // order: "asc",
        display: "idToDisplay",
        templateValue: "{{otherInfo}}",
        emptyTemplate: "Aucune donnée pour <b>{{query}}</b>",
        minLength: 3,
        dynamic: true,
        filter: false,
        source: {
            ajax: function (query) {
                return {
                    type: "GET",
                    url: route,
                    data: {
                        search          : query,
                        onlyContacts    : alternateVersion,
                        color           : !alternateVersion
                    }
                }
            }
        },
        callback: {
            onClickAfter: function (node, li, item) {
                if (item.type == "entity") {
                    $j("#" + inputId + "_id").val(item.serialId);
                } else {
                    $j("#" + inputId + "_id").val(item.id);
                }
                $j("#" + inputId + "_type").val(item.type);

                if (!alternateVersion) {
                    if (li[0].getStyle('background-color') == 'rgba(0, 0, 0, 0)') {
                        $j("#" + inputId).css('background-color', 'white');
                    } else {
                        $j("#" + inputId).css('background-color', li[0].getStyle('background-color'));
                    }

                }
                if(typeof cardId != 'undefined'){
                    $j("#" + cardId).css('visibility', 'visible');
                }
            },
            onCancel: function () {
                $j("#" + inputId + "_id").val('');
                $j("#" + inputId + "_type").val('');
                $j("#" + inputId).css('background-color', "");
                if(typeof cardId != 'undefined'){
                    $j("#" + cardId).css('visibility', 'hidden');
                }
            },
            onLayoutBuiltBefore: function (node, query, result, resultHtmlList) {
                if (typeof resultHtmlList != "undefined" && result.length > 0) {
                    $j.each(resultHtmlList.find('li'), function (i, target) {
                        if (result[i]['type'] == "contact" && result[i]["rateColor"] != "") {
                            $j(target).css({"background-color" : result[i]["rateColor"]});
                        }
                        if (result[i]['type'] == "contact") {
                            $j(target).find('span').before("<i class='fa fa-building fa-1x'></i>&nbsp;&nbsp;");
                        } else if (result[i]['type'] == "user") {
                            $j(target).find('span').before("<i class='fa fa-user fa-1x'></i>&nbsp;&nbsp;");
                        } else if (result[i]['type'] == "onlyContact") {
                            $j(target).find('span').before("<i class='fa fa-address-card fa-1x'></i>&nbsp;&nbsp;");
                        }
                    });
                }
                return resultHtmlList;
            }
        }
    });
}
