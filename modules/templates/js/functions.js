function show_templates(show)
{
    var div = $('templates_div');
    if (div != null) {
        if (show == true) {
            div.style.display = 'block';
        } else {
            div.style.display = 'none';
            var list = $('templates');
            list.selectedIndex = 0;
        }
    }
}

function show_special_form(elem_to_view, elem_to_hide1)
{
    var elem_1 = window.document.getElementById(elem_to_view);
    var elem_2 = window.document.getElementById(elem_to_hide1);
    if (elem_1 != null) {
        elem_1.style.display = "block";
    }
    if (elem_2 != null) {
        elem_2.style.display = "none";
    }
}

function show_special_form_3_elements(elem_to_view, elem_to_hide1, elem_to_hide2)
{
    var elem_0 = window.document.getElementById(elem_to_view);
    var elem_1 = window.document.getElementById(elem_to_hide1);
    var elem_2 = window.document.getElementById(elem_to_hide2);
    if (elem_0 != null) {
        elem_0.style.display = "block";
    }
    if (elem_1 != null) {
        elem_1.style.display = "none";
    }
    if (elem_2 != null) {
        elem_2.style.display = "none";
    }
}

function changeStyle(style_id, path_to_script) 
{
    //window.alert(path_to_script);
    new Ajax.Request(path_to_script,
    {
        method:'post',
        parameters: {template_style: style_id.value},
        onSuccess: function(answer){
            eval("response = "+answer.responseText)
        },
        onFailure: function() {
            $('modal').innerHTML = '<div class="error">Server error</div>'+form_txt;
            form.query_name.value = this.name;
        }
    });
}

function setradiobutton(target)
{
    $j("#html,#office,#txt,#span_html,#span_office,#span_txt").css({"display":"inline"});
    $j("#template_attachment_type_tr").hide();

    if(target=="notes") {
        $j("#html,#span_html,#office,#span_office").hide();
        $j("#txt").click();
    }else if(target=="sendmail") {
        $j("#office,#span_office").hide();
        $j("#html").click();
    } else if(target=="notifications") {
        $j("#txt,#span_txt,#office,#span_office").hide();
        $j("#html").click();
    } else if (target=="attachments"){
        $j("#txt,#span_txt,#html,#span_html").hide();
        $j("#office").click();
        $j("#template_attachment_type_tr").css({"display":"inline"});
    } else if(target=="doctypes") {
        $j("#txt,#span_txt,#office,#span_office").hide();
        $j("#html").click();
    }

    if (target != "attachments") {
    	$j("#template_attachment_type").selectedIndex="0";
    }
}

function select_template(path_to_script, attachment_type)
{
    
    if ($j('#'+attachment_type.id).val() != '') {
        $j('#formAttachment .transmissionDiv #newAttachButton').prop("disabled",false);
        $j('#formAttachment .transmissionDiv #newAttachButton').removeClass("readonly");
        
    } else {
        $j('#formAttachment .transmissionDiv #newAttachButton').prop("disabled",true);
        $j('#formAttachment .transmissionDiv #newAttachButton').addClass("readonly");
    }
    if ($j('#'+attachment_type.id+' option:selected').attr("width_delay") != '' && $j('#'+attachment_type.id+' option:selected').attr("width_delay") != undefined) {
        var delay = $j('#'+attachment_type.id+' option:selected').attr("width_delay");
        var delay_date = defineBackDate(delay);
        $j('#'+attachment_type.id).parent().parent().find('[name=back_date\\[\\]]').val(delay_date);

    } else {
        $j('#'+attachment_type.id).parent().parent().find('[name=back_date\\[\\]]').val("");
    }

    if ($j('#'+attachment_type.id+' option:selected').val() == "transmission") {
        $j('#'+attachment_type.id).parent().parent().find('[name=effectiveDateStatus\\[\\]]').css('display','inline-block');
        $j('#'+attachment_type.id).parent().parent().find('[name=effectiveDateStatus\\[\\]] option').remove();
        $j('#'+attachment_type.id).parent().parent().find('[name=effectiveDateStatus\\[\\]]').append('<option value="EXP_RTURN">Attente retour</option>');
        $j('#'+attachment_type.id).parent().parent().find('[name=effectiveDateStatus\\[\\]]').append('<option value="NO_RTURN">Pas de retour</option>');
        $j('#'+attachment_type.id).parent().parent().find('[name=back_date\\[\\]]').css('width','75px');
    } else {
        $j('#'+attachment_type.id).parent().parent().find('[name=effectiveDateStatus\\[\\]]').css('display','none');
        $j('#'+attachment_type.id).parent().parent().find('[name=effectiveDateStatus\\[\\]] option').remove();
        $j('#'+attachment_type.id).parent().parent().find('[name=effectiveDateStatus\\[\\]]').append('<option value="A_TRA">A traiter</option>');
        $j('#'+attachment_type.id).parent().parent().find('[name=back_date\\[\\]]').css('width','inherite');
    }

    new Ajax.Request(path_to_script,
    {
        method:'post',
        parameters: {attachment_type: $j('#'+attachment_type.id).val()},
        onSuccess: function(answer){
            $j('#'+attachment_type.id).parent().parent().find('[name=templateOffice\\[\\]]').html(answer.responseText);
            $j('#'+attachment_type.id).parent().parent().find('[name=templateOffice\\[\\]]').change();    
            $j('#'+attachment_type.id).parent().parent().find('[name=contact_attach\\[\\]]').change();
        }
    });
}

function addTemplateBase(file)
{
    // if (confirm('En cliquant sur ok, le modèle sera ajouté dans la liste des natures de modèle.')) {
    //     saveTemplateBase = "yes";
    // }else{
        saveTemplateBase = "no";
    // }
    var reader = new FileReader();
    reader.readAsDataURL(file.files[0]);
    reader.onload = function () {
      base64File = reader.result.replace(/^[^,]*,/, '');
      $j.ajax({
        url: 'index.php?display=true&module=templates&page=addTemplateBase',
        type: 'POST',
        dataType: 'JSON',
        data: {
            fileName: file.files[0].name,
            fileMimeType : file.files[0].type,
            fileContent: base64File,
            saveTemplateBase : saveTemplateBase,
        },
        success: function (response) {
            if (response.status == 0) {
                $j('#template_style').hide();
                $j('#addTemplate').val(file.files[0].name);
                $j('#addTemplate').show();
                $j('#templateEditTr').hide();
            } else {
                alert(response.error_txt);
            }
        },
        error: function (error) {
            console.log(error);
            alert(error);
        }

    });
    };
}