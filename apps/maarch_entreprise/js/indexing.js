
var $j = jQuery.noConflict();

/** Declaration of the autocompleter object used for the contacts*/
var contact_autocompleter;

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

function initSenderRecipientAutocomplete(inputId, mode, alternateVersion, cardId) {
    var route = '../../rest/autocomplete/correspondents';

    $j("#" + inputId).typeahead({
        // order: "asc",
        display: ["company", "firstname", "lastname"],
        templateValue: "{{company}} {{firstname}} {{lastname}}",
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
                        search              : query,
                        noContactsGroups    : true,
                        color               : !alternateVersion
                    }
                }
            }
        },
        callback: {
            onClickAfter: function (node, li, item) {
                $j("#" + inputId + "_id").val(item.id);
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
            onSearch: function () {
                $j("#" + inputId + "_id").val('');
                $j("#" + inputId + "_type").val('');
                $j("#" + inputId).css('background-color', "");
                if(typeof cardId != 'undefined'){
                    $j("#" + cardId).css('visibility', 'hidden');
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
