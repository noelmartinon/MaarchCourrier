function actionSeda(path,type) {
    var resPath = path.split('|');

    if (type == 'zip' || type == 'sendMessage') {
        resPath[0] += '&messageTitle=' + $('messageTitle').value;
    }

    new Ajax.Request(resPath[0],
        {
            method: 'post',
            parameters: {
                url: resPath[0]
            },
            onSuccess: function (answer) {
                eval("response = " + answer.responseText);
                if (response.status == 0) {
                    if (type == "validateMessage") {
                        $("cancel").click();
                        location.reload();
                    } else {
                        $('messageTitle').disabled = true;

                        if (type == 'zip') {
                            window.open(resPath[1]);

                            if ($("cancel").style.display != 'none') {
                                $("validSend").style.display = 'block';
                            }
                        } else if (type == 'sendMessage') {
                            new Ajax.Request(resPath[1],
                                {
                                    method:'post',
                                    parameters: { url : resPath[1]
                                    },
                                    onSuccess: function(answer) {
                                        eval("response = "+answer.responseText);
                                        if(response.status == 0){
                                            $("valid").style.display = 'block';
                                            $("validSend").style.display = 'none';
                                            $("cancel").style.display = 'none';
                                            $("sendMessage").style.display = 'none';

                                            alert(response.content);
                                        } else {
                                            alert(response.error);
                                        }
                                    }
                                }
                            );
                        }
                    }
                } else {
                    alert(response.error);
                }
            }
        }
    );
}

function actionValidation(path, userId, groupIdSer, basketId) {
    new Ajax.Request(path,
        {
            method:'post',
            parameters: { url : path},
            onSuccess: function(answer) {
                eval("response = "+answer.responseText);
                if(response.status == 0){
                    //alert(response.content);
                } else {
                    alert(response.error);
                }
                triggerAngular('#/basketList/users/' + userId + '/groups/' + groupIdSer + '/baskets/' + basketId);
            }
        });
}
