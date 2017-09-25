function addAvisUser(users) {
    if (!users) {
        nb_avis = $j(".droptarget").length;
        next_avis = nb_avis + 1;
        if (nb_avis == 0) {
            $j("#emptyAvis").hide();
        }
        $j("#avis_content").append('<div class="droptarget" id="avis_' + next_avis + '" draggable="true">'
                + '<span class="avisUserStatus">'
                + '<i class="fa fa-hourglass-half" aria-hidden="true"></i>'
                + '</span>'
                + '<span class="avisUserInfo">'
                +'<sup class="avisUserPos nbResZero">'+next_avis+'</sup>&nbsp;&nbsp;'
                + '<i class="fa fa-user fa-2x" aria-hidden="true"></i> ' + $j("select#avisUserList option:selected").text() + ' <sup class="nbRes">' + $j("select#avisUserList option:selected").parent().get(0).label + '</sup>'
                + '<input class="userId" type="hidden" value="' + $j("select#avisUserList option:selected").val() + '"/><input class="avisDate" type="hidden" value=""/>'
                + '</span>'
                + '<span class="avisUserAction">'
                + '<i class="fa fa-trash" aria-hidden="true" onclick="delAvisUser(this.parentElement.parentElement);"></i>'
                + '</span>'
                + '<span class="avisUserConsigne">'
                + '<input type="text" class="consigne" value=""/>'
                + '</span>'
                + '<span id="dropZone" title="Glisser/déposer pour modifier l\'ordre du circuit" style="cursor: pointer">'
                + '<i class="fa fa-exchange fa-2x fa-rotate-90" aria-hidden="true"></i>'
                + '</span>'
                + '</div>');

        //prototype
        document.getElementById("avisUserList").selectedIndex = 0;
        Event.fire($("avisUserList"), "chosen:updated");
    } else {
        nb_avis = $j(".droptarget").length;
        next_avis = nb_avis + 1;
        if (nb_avis == 0) {
            $j("#emptyAvis").hide();
        }
        $j("#avis_content").append('<div class="droptarget" id="avis_' + next_avis + '" draggable="true">'
                + '<span class="avisUserStatus">'
                + '<i class="fa fa-hourglass-half" aria-hidden="true"></i>'
                + '</span>'
                + '<span class="avisUserInfo">'
                +'<sup class="avisUserPos nbResZero">'+next_avis+'</sup>&nbsp;&nbsp;'
                + '<i class="fa fa-user fa-2x" aria-hidden="true"></i> ' + users.lastname + ' ' + users.firstname + ' <sup class="nbRes">' + users.entity_id + '</sup>'
                + '<input class="userId" type="hidden" value="' + users.user_id + '"/><input class="avisDate" type="hidden" value=""/>'
                + '</span>'
                + '<span class="avisUserAction">'
                + '<i class="fa fa-trash" aria-hidden="true" onclick="delAvisUser(this.parentElement.parentElement);"></i>'
                + '</span>'
                + '<span class="avisUserConsigne">'
                + '<input type="text" class="consigne" value="' + users.process_comment + '"/>'
                + '</span>'
                + '<span id="dropZone" title="Glisser/déposer pour modifier l\'ordre du circuit" style="cursor: pointer">'
                + '<i class="fa fa-exchange fa-2x fa-rotate-90" aria-hidden="true"></i>'
                + '</span>'
                + '</div>');

    }
    resetPosAvis();
}
function delAvisUser(target) {
    console.log(target);
    var id = '#' + target.id;

    if ($j(".droptarget").length == 1) {
        $j("#emptyAvis").show();
    }
    $j(id).remove();

    resetPosAvis();

}
function resetPosAvis() {
    i = 1;
    $j(".droptarget").each(function () {
        this.id = 'avis_' + i;
        $j("#" + this.id).find(".avisUserPos").text(i);
        i++;
    });
}
function updateAvisWorkflow(resId) {
    var $i = 0;
    var userList = [];
    if ($j(".droptarget").length) {
        $j(".droptarget").each(function () {
            //console.log('viseur : '+$j("#"+this.id+" .userdId").val());
            userId = $j("#" + this.id).find(".userId").val();
            userConsigne = $j("#" + this.id).find(".consigne").val();
            userAvisDate = $j("#" + this.id).find(".avisDate").val();
            userPos = $i;
            userList.push({userId: userId, userPos: userPos, userConsigne: userConsigne, userAvisDate: userAvisDate});
            $i++;
        });
    }
    $j.ajax({
        url: 'index.php?display=true&module=avis&page=updateAvisWF',
        type: 'POST',
        dataType: 'JSON',
        data: {
            resId: resId,
            userList: JSON.stringify(userList)
        },
        success: function (response) {
            if (response.status == 0) {
                $('divInfoAvis').innerHTML = 'Mise à jour du circuit effectuée';
                $('divInfoAvis').style.display = 'table-cell';
                Element.hide.delay(5, 'divInfoAvis');
                eval(response.exec_js);
            } else if (response.status != 1) {
                alert(response.error_txt)
            }
        },
        error: function (error) {
            alert(error);
        }

    });
}
function saveAvisWorkflowAsModel() {
    var $i = 0;
    var userList = [];
    var title = $j("#titleModel").val();

    if ($j(".droptarget").length) {
        $j(".droptarget").each(function () {
            //console.log('viseur : '+$j("#"+this.id+" .userdId").val());
            userId = $j("#" + this.id).find(".userId").val();
            userConsigne = $j("#" + this.id).find(".consigne").val();
            userAvisDate = $j("#" + this.id).find(".avisDate").val();
            userPos = $i;
            userList.push({userId: userId, userPos: userPos, userConsigne: userConsigne, userAvisDate: userAvisDate});
            $i++;
        });
        $j.ajax({
            url: 'index.php?display=true&module=avis&page=saveAvisModel',
            type: 'POST',
            dataType: 'JSON',
            data: {
                title: title,
                userList: JSON.stringify(userList)
            },
            success: function (response) {
                if (response.status == 0) {
                    $('divInfoAvis').innerHTML = 'Modèle enregistré';
                    $('divInfoAvis').style.display = 'table-cell';
                    Element.hide.delay(5, 'divInfoAvis');
                    $j('#modalSaveAvisModel').hide();
                    eval(response.exec_js);
                } else {
                    alert(response.error_txt)
                }
            },
            error: function (error) {
                alert(error);
            }

        });

    } else {
        alert('Aucun utilisateur dans le circuit !');
    }

}
function loadAvisModelUsers() {

    var objectId = $j("select#modelList option:selected").val();
    var objectType = 'AVIS_CIRCUIT';
    $j.ajax({
        url: 'index.php?display=true&module=avis&page=load_listmodel_avis_users',
        type: 'POST',
        dataType: 'JSON',
        data: {
            objectType: objectType,
            objectId: objectId
        },
        success: function (response) {
            if (response.status == 0) {

                var userList = response.result;
                if (userList) {
                    userList.each(function (user, key) {
                        addAvisUser(user);
                    });
                }


            } else {
                alert(response.error_txt);
            }
        },
        error: function (error) {
            alert(error);
        }

    });

    //prototype
    document.getElementById("modelList").selectedIndex = 0;
    Event.fire($("modelList"), "chosen:updated");
}

function initDragNDropAvis() {
    document.getElementById("avis_content").addEventListener("dragstart", function (event) {
        $j(".droptarget").not(".vised,.currentVis").css("border", "dashed 2px #93D1E4");
        // The dataTransfer.setData() method sets the data type and the value of the dragged data
        event.dataTransfer.setData("Text", event.target.id);

        // Output some text when starting to drag the p element
        //document.getElementById("demo").innerHTML = "Started to drag the p element.";

        // Change the opacity of the draggable element
        event.target.style.opacity = "0.4";
    });

    // While dragging the p element, change the color of the output text
    document.getElementById("avis_content").addEventListener("drag", function (event) {
        //document.getElementById("demo").style.color = "red";
    });

    // Output some text when finished dragging the p element and reset the opacity
    document.getElementById("avis_content").addEventListener("dragend", function (event) {
        //document.getElementById("demo").innerHTML = "Finished dragging the p element.";
        $j(".droptarget").not(".vised,.currentVis").css("border", "dashed 2px #93D1E4");
        event.target.style.opacity = "1";
    });


    /* Events fired on the drop target */

    // When the draggable p element enters the droptarget, change the DIVS's border style
    document.getElementById("avis_content").addEventListener("dragenter", function (event) {
        if (event.target.className == "droptarget") {
            event.target.style.border = "dashed 2px green";
        }
    });

    // By default, data/elements cannot be dropped in other elements. To allow a drop, we must prevent the default handling of the element
    document.getElementById("avis_content").addEventListener("dragover", function (event) {
        event.preventDefault();
    });

    // When the draggable p element leaves the droptarget, reset the DIVS's border style
    document.getElementById("avis_content").addEventListener("dragleave", function (event) {
        if (event.target.className == "droptarget") {
            event.target.style.border = "dashed 2px #ccc";
        }
    });

    /* On drop - Prevent the browser default handling of the data (default is open as link on drop)
     Reset the color of the output text and DIV's border color
     Get the dragged data with the dataTransfer.getData() method
     The dragged data is the id of the dragged element ("drag1")
     Append the dragged element into the drop element
     */
    document.getElementById("avis_content").addEventListener("drop", function (event) {
        event.preventDefault();
        if (event.target.className == "droptarget") {
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
            var target = event.target.id;
            posData = data.split("_");
            posTarget = target.split("_");
            if (posData[1] > posTarget[1]) {
                $j('#' + target).before($j('#' + data));
            } else {
                $j('#' + target).after($j('#' + data));
            }
            resetPosAvis();


        }
    });
    $j('#avis_content')
        .on('focus', '.consigne', function(e) {
            $j(this).closest('.droptarget').attr("draggable", false);
            console.log($j(this).closest('.droptarget'));
        })
        .on('blur', '.consigne', function(e) {
            $j(this).closest('.droptarget').attr("draggable", true);
        });
}

function checkRealDateAvis() {

    var docDate;
    var processLimitDate;
    var avisLimitDate;

    var nowDate = new Date();
    var date3 = new Date();

    var current_date = Date.now();



    /* if($('doc_date')) {
     docDate = $('doc_date').value;
     var date2 = new Date();
     date2.setFullYear(docDate.substr(6,4));
     date2.setMonth(docDate.substr(3,2));
     date2.setDate(docDate.substr(0,2));
     date2.setHours(0);
     date2.setMinutes(0);
     var d2_docDate=date2.getTime();
     } */

    if ($('process_limit_date')) {
        processLimitDate = $('process_limit_date').value;
        var date4 = new Date();
        date4.setFullYear(processLimitDate.substr(6, 4));
        date4.setMonth(processLimitDate.substr(3, 2) - 1);
        date4.setDate(processLimitDate.substr(0, 2));
        date4.setHours(0);
        date4.setMinutes(0);
        date4.setSeconds(0);
        var d4_processLimitDate = date4.getTime();
    }


    if ($('recommendation_limit_date')) {
        avisLimitDate = $('recommendation_limit_date_tr').value;
        var date5 = new Date();
        date5.setFullYear(avisLimitDate.substr(6, 4));
        date5.setMonth(avisLimitDate.substr(3, 2) - 1);
        date5.setDate(avisLimitDate.substr(0, 2));
        date5.setHours(0);
        date5.setMinutes(0);
        date5.setSeconds(0);
        var d5_avisLimitDate;
        var d5_avisLimitDate = date5.getTime();
    }

    if (d4_processLimitDate != "" && avisLimitDate != "" && (d5_avisLimitDate > d4_processLimitDate && d4_processLimitDate > current_date)) {
        alert("La date limite d'avis doit être antérieure à la date limite du courrier ");
        $('recommendation_limit_date').value = "";
        $('recommendation_limit_date_tr').value = "";

    }

    if (current_date > d5_avisLimitDate && avisLimitDate != "") {
        alert("La date d'avis doit être supérieure à la date du jour ");
        $('recommendation_limit_date').value = "";
        $('recommendation_limit_date_tr').value = "";

    }
}
