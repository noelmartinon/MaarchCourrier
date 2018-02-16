var angularGlobals = {};
function triggerAngular(prodmode, locationToGo) {
    var views = [
        //'header',
        'administration',
        'users-administration',
        'users-administration-redirect-modal',
        'user-administration',
        'groups-administration',
        'groups-administration-redirect-modal',
        'group-administration',
        'baskets-administration',
        'baskets-order-administration',
        'basket-administration',
        'basket-administration-settings-modal',
        'basket-administration-groupList-modal',
        'entities-administration',
        'entity-administration',
        'status-administration',
        'statuses-administration',
        'actions-administration',
        'action-administration',
        'history-administration',
        'historyBatch-administration',
        'update-status-administration',
        'profile',
        'signature-book',
        'parameter-administration',
        'parameters-administration',
        'priorities-administration',
        'priority-administration',
        'reports-administration',
        'notifications-administration',
        'notifications-schedule-administration',
        'notification-administration'
    ];

    $j.ajax({
        url      : '../../rest/initialize',
        type     : 'POST',
        dataType : 'json',
        data: {
            views  : views
        },
        success: function(answer) {

            angularGlobals = answer;
            $j('#inner_content').html('<i class="fa fa-spinner fa-spin fa-5x" style="margin-left: 50%;margin-top: 16%;font-size: 8em"></i>');
            
            if (prodmode) {

                var alreadyLoaded = false;
                $j('script').each(function(i, element) {
                    if (element.src == (answer.coreUrl + "apps/maarch_entreprise/js/angular/main.bundle.min.js")) {
                        alreadyLoaded = true;
                    }
                });
                if (!alreadyLoaded) {
                    var head = document.getElementsByTagName('head')[0];
                    var script = document.createElement('script');
                    script.type = 'text/javascript';
                    script.src = "js/angular/main.bundle.min.js";

                    script.onreadystatechange = changeLocationToAngular(locationToGo);
                    script.onload = changeLocationToAngular(locationToGo);

                    // Fire the loading
                    head.appendChild(script);
                } else {
                    location.href = locationToGo;
                }
            } else {
                System.import('js/angular/main.js').catch(function(err){ console.error(err); });
                location.href = locationToGo;
            }
        }
    });
}

function changeLocationToAngular(locationToGo) {
    location.href = locationToGo;
}

function successNotification(message) {
    $j('#resultInfo').html(message).removeClass().addClass('alert alert-success alert-dismissible');
    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function() {
        $j("#resultInfo").slideUp(500);
    });
}

function errorNotification(message) {
    $j('#resultInfo').html(message).removeClass().addClass('alert alert-danger alert-dismissible');
    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function() {
        $j("#resultInfo").slideUp(500);
    });
}

function lockDocument(resId) {
    $j.ajax({
        url: 'index.php?display=true&dir=actions&page=docLocker',
        type : 'POST',
        data: {
            AJAX_CALL  : true,
            lock       : true,
            res_id     : resId
        },
        success: function(result){
        }
    });
}

function unlockDocument(resId) {
    $j.ajax({
        url: 'index.php?display=true&dir=actions&page=docLocker',
        type : 'POST',
        data: {
            AJAX_CALL  : true,
            unlock     : true,
            res_id     : resId
        },
        success: function(result) {
        }
    });
}

function islockForSignatureBook(resId, basketId, groupId, prodmode) {
    $j.ajax({
        url: 'index.php?display=true&dir=actions&page=docLocker',
        type : 'POST',
        data: {
            AJAX_CALL  : true,
            isLock     : true,
            res_id     : resId
        },
        success: function(result) {
            var response = JSON.parse(result);

            if (response.lock) {
                alert("Courrier verrouillé par " + response.lockBy);
            } else {
                if (prodmode) {
                    triggerAngular(true, "#/groups/" + groupId + "/baskets/" + basketId + "/signatureBook/" + resId);
                } else {
                    triggerAngular(false, "#/groups/" + groupId + "/baskets/" + basketId + "/signatureBook/" + resId);
                }
            }
        }
    });
}

var disablePrototypeJS = function (method, pluginsToDisable) {
    var handler = function (event) {
        event.target[method] = undefined;
        setTimeout(function () {
            delete event.target[method];
        }, 0);
    };
    pluginsToDisable.each(function (plugin) {
        $j(window).on(method + '.bs.' + plugin, handler);
    });
};

if (Prototype.BrowserFeatures.ElementExtensions) {
    //FIX PROTOTYPE CONFLICT
    var pluginsToDisable = ['collapse', 'dropdown', 'modal', 'tooltip', 'popover','tab'];
    disablePrototypeJS('show', pluginsToDisable);
    disablePrototypeJS('hide', pluginsToDisable);
}

function duplicateTemplate(id) {
    var r = confirm("Voulez-vous vraiment dupliquer le modèle ?");

    if (r) {
        $j.ajax({
            url      : '../../rest/templates/' + id + '/duplicate',
            type     : 'POST',
            dataType : 'json',
            data: {},
            success: function(answer) {
                location.href = "index.php?page=templates_management_controler&mode=up&module=templates&id=" + answer.id + "&start=0&order=asc&order_field=&what=";
            }, error: function(err) {
                alert("Une erreur s'est produite");
            }
        });
    }
}

function setAttachmentInSignatureBook(id, isVersion) {
    $j.ajax({
        url      : '../../rest/attachments/' + id + '/inSignatureBook',
        type     : 'PUT',
        dataType : 'json',
        data: {
            isVersion   : isVersion
        },
        success: function(answer) {
            if (typeof window.parent['angularSignatureBookComponent'] !== "undefined") {
                window.parent.angularSignatureBookComponent.componentAfterAttach("left");
            }
        }, error: function(err) {
            alert("Une erreur s'est produite");
        }
    });
}
