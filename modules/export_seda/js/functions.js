function actionSeda($path,$type) {
    if ($type == 'zip') {
        window.open($path);
        $("validSend").style.display = 'block';
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
            });
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