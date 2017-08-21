function actionSeda($path,$type) {
    if ($type == 'zip') {
        window.open($path);
        $("validSend").style.display = 'block';
    } else if ($type == 'generateMessage') {
        $path += '&messageTitle=' + $('messageTitle').value;
        new Ajax.Request($path,
            {
                method:'post',
                parameters: { url : $path,
                },
                onSuccess: function(answer) {
                    eval("response = "+answer.responseText);

                    if(response.status == 0){
                            $("zip").style.display = 'inline';
                            $("sendMessage").style.display = 'inline';
                            $('generateMessage').style.display = 'none';
                            $('messageTitle').disabled = true;
                    } else {
                        alert(response.error);
                    }
                }
            }
        );
    } else {
    	new Ajax.Request($path,
            {
                method:'post',
                parameters: { url : $path,
                            },
                onSuccess: function(answer) {
                    eval("response = "+answer.responseText);
                    if(response.status == 0){
                        if ($type != "validateMessage") {
                            $("valid").style.display = 'block';
                            $("validSend").style.display = 'none';
                            $("cancel").style.display = 'none';
                            $("sendMessage").style.display = 'none';

                            alert(response.content);
                        } else {
                            $("cancel").click();
                            location.reload();
                        }
                    } else {
                        alert(response.error);
                    }
                }
            }
        );
    }
}

function actionValidation($path,$type) {
    new Ajax.Request($path,
        {
            method:'post',
            parameters: { url : $path},
            onSuccess: function(answer) {
                eval("response = "+answer.responseText);
                if(response.status == 0){
                    //alert(response.content);
                } else {
                    alert(response.error);
                }
                location.reload();
            }
        });
}