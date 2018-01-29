function addVisaUser(users) {
    if (!users) {
        nb_visa = $j(".droptarget").length;
        next_visa = nb_visa + 1;
        if(nb_visa == 0){
            $j("#emptyVisa").hide();      
        }
        if ($j("select[id^=signRequest_] option:selected[value=true]").length >= 2 || 
            ($j("select[id^=signRequest_] option:selected[value=true]").length == 1 && $j("select[id^=signRequest_] option:last:selected[value=true]").length == 0)) {
            selected = '';
        } else {
            if (!$j('#signRequest_'+nb_visa).is(':disabled')) {
                $j('#signRequest_'+nb_visa).val("false");
            }
            selected = ' selected="selected" ';        
        }
        

        if ($j("#isAllAttachementSigned").val() == 'false') {
            signRequest = '<br/><sub><select id="signRequest_'+next_visa+'"><option value="false">VISEUR</option><option value="true" '+selected+'>SIGNATAIRE</option></select></sub>';
        } else if($j("#isAllAttachementSigned").val() == 'allsigned'){
            signRequest = '<br/><sub><select id="signRequest_'+next_visa+'"><option value="false" '+selected+'>VISEUR</option><option value="true">SIGNATAIRE</option></select></sub>';
        } else {
            signRequest = '<br/><sub><select id="signRequest_'+next_visa+'" disabled="disabled"><option value="false" '+selected+'>VISEUR</option><option value="true">SIGNATAIRE</option></select></sub>';
        }

        $j("#visa_content").append('<div class="droptarget" id="visa_' + next_visa + '" draggable="true">'
            +'<span class="visaUserStatus">'
                +'<i class="fa fa-hourglass-half" aria-hidden="true"></i>'
            +'</span>'
            +'<span class="visaUserInfo">'
                +'<sup class="visaUserPos nbResZero">'+next_visa+'</sup>&nbsp;&nbsp;'
                +'<i class="fa fa-user fa-2x" aria-hidden="true"></i> '+ $j("select#visaUserList option:selected").text() +' <sup class="nbRes">'+$j("select#visaUserList option:selected").parent().get( 0 ).label+'</sup>'
                +'<input class="userId" type="hidden" value="' + $j("select#visaUserList option:selected").val() + '"/><input class="visaDate" type="hidden" value=""/>'
                +'&nbsp;&nbsp; <i id="signedUser_'+next_visa+'" title="Personne signataire" class="visaUserSign fa fa-certificate" aria-hidden="true" style="color:#FDD16C;visibility:hidden;"></i>'
                + signRequest      
            +'</span>'
            +'<span class="visaUserAction">'
                +'<i class="fa fa-trash" aria-hidden="true" onclick="delVisaUser(this.parentElement.parentElement);"></i>'
            +'</span>'
            +'<span class="visaUserConsigne">'
                +'<input type="text" class="consigne" value=""/>'
            +'</span>'
            +'<span id="dropZone" title="Glisser/déposer pour modifier l\'ordre du circuit" style="cursor: pointer">'
                +'<i class="fa fa-exchange fa-2x fa-rotate-90" aria-hidden="true"></i>'
            +'</span>'
        +'</div>');
        
        //prototype
        document.getElementById("visaUserList").selectedIndex = 0;
        Event.fire($("visaUserList"), "chosen:updated");
    } else {
        nb_visa = $j(".droptarget").length;
        next_visa = nb_visa + 1;
        if(nb_visa == 0){
            $j("#emptyVisa").hide();      
        }
        if ($j("select[id^=signRequest_] option:selected[value=true]").length <= 2) {
            if (!$j('#signRequest_'+nb_visa).is(':disabled')) {
                $j('#signRequest_'+nb_visa).val("false");
            }
            selected = ' selected="selected" ';
        } else {   
            selected = '';
        }

        if ($j("#isAllAttachementSigned").val() == 'false') {
            signRequest = '<br/><sub><select id="signRequest_'+next_visa+'"><option value="false">VISEUR</option><option value="true" '+selected+'>SIGNATAIRE</option></select></sub>';
        } else if($j("#isAllAttachementSigned").val() == 'allsigned'){
            signRequest = '<br/><sub><select id="signRequest_'+next_visa+'"><option value="false" '+selected+'>VISEUR</option><option value="true">SIGNATAIRE</option></select></sub>';
        } else {
            signRequest = '<br/><sub><select id="signRequest_'+next_visa+'" disabled="disabled"><option value="false" '+selected+'>VISEUR</option><option value="true">SIGNATAIRE</option></select></sub>';            
        }
        $j("#visa_content").append('<div class="droptarget" id="visa_' + next_visa + '" draggable="true">'
            +'<span class="visaUserStatus">'
                +'<i class="fa fa-hourglass-half" aria-hidden="true"></i>'
            +'</span>'
            +'<span class="visaUserInfo">'
                +'<sup class="visaUserPos nbResZero">'+next_visa+'</sup>&nbsp;&nbsp;'
                +'<i class="fa fa-user fa-2x" aria-hidden="true"></i> ' + users.lastname + ' ' + users.firstname + ' <sup class="nbRes">'+users.entity_id+'</sup>'
                +'<input class="userId" type="hidden" value="' + users.user_id + '"/><input class="visaDate" type="hidden" value=""/>'
                +'&nbsp;&nbsp; <i id="signedUser_'+next_visa+'" title="Personne signataire" class="visaUserSign fa fa-certificate" aria-hidden="true" style="color:#FDD16C;visibility:hidden;"></i>'
                + signRequest 
                +'</span>'
            +'<span class="visaUserAction">'
                +'<i class="fa fa-trash" aria-hidden="true" onclick="delVisaUser(this.parentElement.parentElement);"></i>'
            +'</span>'
            +'<span class="visaUserConsigne">'
                +'<input type="text" class="consigne" value="' + users.process_comment + '"/>'
            +'</span>'
            +'<span id="dropZone" title="Glisser/déposer pour modifier l\'ordre du circuit" style="cursor: pointer">'
                +'<i class="fa fa-exchange fa-2x fa-rotate-90" aria-hidden="true"></i>'
            +'</span>'
        +'</div>');
        
    }
    resetPosVisa();
}
function delVisaUser(target) {
    var id = '#' + target.id;

    if ($j(".droptarget").length == 1) {
        $j("#emptyVisa").show();
    }
    $j(id).remove();

    resetPosVisa();

}
function resetPosVisa () {
    i = 1;
    $j(".droptarget").each(function() {
        this.id = 'visa_' + i;
        $j("#" + this.id).find("select[id^=signRequest_]")[0].id='signRequest_'+i;
        $j("#" + this.id).find("[id^=signedUser_]")[0].id='signedUser_'+i;
        $j("#" + this.id).find(".visaUserPos").text(i);
        i++;
    });

    i = 1;
    var hasSignatory = false;
    $j(".droptarget").each(function() {
        if ($j("#signRequest_"+(i)+" option:selected[value=true]").length) {
            userRequestSign=true;
        } else {
            userRequestSign=false;
        }
        if ($j("#signedUser_"+(i)).css('visibility') == 'visible') {
            userSignatory=true;
        } else {
            userSignatory=false;
        }

        if(userRequestSign || userSignatory){
            hasSignatory = true;
        }
        if ($j("#signRequest_"+(i+1)).length == 0 && !hasSignatory) {
            $j('#signRequest_'+(i)).val("true");
        }
        i++;

    });
    i--;            

}
function updateVisaWorkflow(resId) {
    var i = 0;
    var userList = [];
    var hasSignatory = false;
    if (($j("select[id^=signRequest_] option:selected[value=true]").length == 0) && $j(".droptarget").length != 0) {
        $j('#signRequest_'+i).val("true");
    }
    if ($j(".droptarget").length) {
        $j(".droptarget").each(function () {
            if ($j("#signRequest_"+(i+1)+" option:selected[value=true]").length) {
                userRequestSign=true;
            } else {
                userRequestSign=false;
            }
            if ($j("#signedUser_"+(i+1)).css('visibility') == 'visible') {
                userSignatory=true;
            } else {
                userSignatory=false;
            }

            userId = $j("#" + this.id).find(".userId").val();
            userConsigne = $j("#" + this.id).find(".consigne").val();
            userVisaDate = $j("#" + this.id).find(".visaDate").val();
            userPos = i;
            // last one is signatory if no one selected
            if(userRequestSign || userSignatory){
                hasSignatory = true;
            }
            if ($j("#signRequest_"+(i+2)).length == 0 && !hasSignatory) {
                userRequestSign=true;
                $j('#signRequest_'+(i+1)).val("true");
            }
            userList.push({userId: userId, userPos: userPos, userConsigne: userConsigne, userVisaDate: userVisaDate, userRequestSign: userRequestSign, userSignatory: userSignatory});
            i++;
        });
    }
    $j.ajax({
       url : 'index.php?display=true&module=visa&page=updateVisaWF',
       type : 'POST',
       dataType : 'JSON',
       data: {
           resId: resId,
            userList: JSON.stringify(userList)
       },
       success : function(response){
            if (response.status == 0) {
                parent.$('main_info').innerHTML = 'Mise à jour du circuit effectuée';
                parent.$('main_info').style.display = 'table-cell';
                parent.Element.hide.delay(5, 'main_info');
                eval(response.exec_js);
                if(parent.$j('.contentShow iframe').length){
                    parent.$j('.contentShow iframe')[0].contentWindow.location.reload(true);
                }
            } else if (response.status != 1) {
                alert(response.error_txt)
            }
       },
       error : function(error){
           alert(error);
       }

    });
    
}
function saveVisaWorkflowAsModel () {
    var $i = 0;
    var userList = [];
    var title = $j("#titleModel").val();
    
    if($j(".droptarget").length){
        $j(".droptarget").each(function() {
            //console.log('viseur : '+$j("#"+this.id+" .userdId").val());
            userId = $j("#"+this.id).find(".userId").val();
            userConsigne = $j("#"+this.id).find(".consigne").val();
            userVisaDate = $j("#"+this.id).find(".visaDate").val();
            userPos = $i;
            userList.push({userId:userId, userPos:userPos, userConsigne:userConsigne, userVisaDate:userVisaDate});        
            $i++;
        });
        $j.ajax({
            url : 'index.php?display=true&module=visa&page=saveVisaModel',
            type : 'POST',
            dataType : 'JSON',
            data: {
                title: title,
                userList: JSON.stringify(userList)
            },
            success : function(response){
                if (response.status == 0) {
                    $('divInfoVisa').innerHTML = 'Modèle enregistré';
                    $('divInfoVisa').style.display = 'table-cell';
                    Element.hide.delay(5, 'divInfoVisa');
                    $j('#modalSaveVisaModel').hide();
                    eval(response.exec_js);
                } else {
                    alert(response.error_txt)
                }
            },
            error : function(error){
                alert(error);
            }

         });
   
    }else{
        alert('Aucun utilisateur dans le circuit !');
    }
    
}
function loadVisaModelUsers() {
    
    var objectId = $j("select#modelList option:selected").val();
    var objectType = 'VISA_CIRCUIT';
    $j.ajax({
            url : 'index.php?display=true&module=visa&page=load_listmodel_visa_users',
            type : 'POST',
            dataType : 'JSON',
            data: {
                objectType: objectType,
                objectId: objectId
            },
            success : function(response){
                if (response.status == 0) {
                    
                    var userList = response.result;
                    if(userList){
                        userList.each(function(user, key) {
                            addVisaUser(user);
                         });  
                    }
                    

                } else {
                    alert(response.error_txt);
                }
            },
            error : function(error){
                alert(error);
            }

         });
         
    //prototype
    document.getElementById("modelList").selectedIndex = 0;
    Event.fire($("modelList"), "chosen:updated");
}

function initDragNDropVisa() {
    document.getElementById("visa_content").addEventListener("dragstart", function(event) {
        $j(".droptarget").not(".vised,.currentVis").css("border","dashed 2px #93D1E4");
        // The dataTransfer.setData() method sets the data type and the value of the dragged data
        event.dataTransfer.setData("Text", event.target.id);

        // Output some text when starting to drag the p element
        //document.getElementById("demo").innerHTML = "Started to drag the p element.";

        // Change the opacity of the draggable element
        event.target.style.opacity = "0.4";
    });

    // While dragging the p element, change the color of the output text
    document.getElementById("visa_content").addEventListener("drag", function(event) {
        //document.getElementById("demo").style.color = "red";
    });

    // Output some text when finished dragging the p element and reset the opacity
    document.getElementById("visa_content").addEventListener("dragend", function(event) {
        //document.getElementById("demo").innerHTML = "Finished dragging the p element.";
        $j(".droptarget").not(".vised,.currentVis").css("border","dashed 2px #93D1E4");
        event.target.style.opacity = "1";
    });


    /* Events fired on the drop target */

    // When the draggable p element enters the droptarget, change the DIVS's border style
    document.getElementById("visa_content").addEventListener("dragenter", function(event) {
        if ( event.target.className == "droptarget") {
            event.target.style.border = "dashed 2px green";
        }
    });

    // By default, data/elements cannot be dropped in other elements. To allow a drop, we must prevent the default handling of the element
    document.getElementById("visa_content").addEventListener("dragover", function(event) {
        event.preventDefault();
    });

    // When the draggable p element leaves the droptarget, reset the DIVS's border style
    document.getElementById("visa_content").addEventListener("dragleave", function(event) {
        if ( event.target.className == "droptarget" ) {
            event.target.style.border = "dashed 2px #ccc";
        }
    });

    /* On drop - Prevent the browser default handling of the data (default is open as link on drop)
       Reset the color of the output text and DIV's border color
       Get the dragged data with the dataTransfer.getData() method
       The dragged data is the id of the dragged element ("drag1")
       Append the dragged element into the drop element
    */
    document.getElementById("visa_content").addEventListener("drop", function(event) {
        event.preventDefault();
        if ( event.target.className == "droptarget" ) {
            /*event.target.style.border = "";
            var data = event.dataTransfer.getData("Text");
            var oldContent = event.target.innerHTML;
            var draggedConsigne = $j('#'+data+' .consigne').val();
            var replaceConsigne = $j('#'+event.target.id+' .consigne').val();
            event.target.innerHTML = document.getElementById(data).innerHTML;
            $j('#'+event.target.id+' .consigne').val(draggedConsigne);
            document.getElementById(data).innerHTML = oldContent;
            $j('#'+data+' .consigne').val(replaceConsigne);*/
            var data = event.dataTransfer.getData("Text");
            var target =event.target.id;
            posData = data.split("_");
            posTarget = target.split("_");
            if(posData[1] > posTarget[1]){
                $j('#'+target).before($j('#'+data));
            }else{
                $j('#'+target).after($j('#'+data));
            }
            resetPosVisa();
            

        }
    });
    $j('#visa_content')
        .on('focus', '.consigne', function(e) {
            $j(this).closest('.droptarget').attr("draggable", false);
            console.log($j(this).closest('.droptarget'));
        })
        .on('blur', '.consigne', function(e) {
            $j(this).closest('.droptarget').attr("draggable", true);
        });
}

function setTitle(input) {
	input.title = input.value;
}

//load applet in a modal
function loadAppletSign(url){
    displayModal(url, 'VisaApplet', 300, 300);
}

function printFolder(res_id, coll_id, form_id, path) {
    //console.log("printFolder");
    new Ajax.Request(path,
            {
                asynchronous: false,
                method: 'post',
                parameters: Form.serialize(form_id),
                encoding: 'UTF-8',
                onSuccess: function (answer) {
                    eval("response = " + answer.responseText);
                    if (response.status == 0) {
                        var id_folder = response.id_folder;
                        var winPrint = window.open('index.php?display=true&module=attachments&page=view_attachment&res_id_master=' + res_id + '&id=' + id_folder, '', 'height=800, width=700,scrollbars=yes,resizable=yes');
                        /*winPrint.focus();
                         winPrint.print();*/
                    }
                    else if (response.status == 1 || response.status == -1) {
                        $('divErrorPrint').innerHTML = response.error_txt;
                        $('divErrorPrint').style.display = 'table-cell';
                        Element.hide.delay(5, 'divErrorPrint');
                    }
                }
            });

}

function selectAllPrintFolder() {
    console.log($j('#allPrintFolder')[0].checked);
    if($j('#allPrintFolder')[0].checked == true){
        $j('.checkPrintFolder').prop('checked', true);
    }else{
        $j('.checkPrintFolder').prop('checked', false);
    }
}
