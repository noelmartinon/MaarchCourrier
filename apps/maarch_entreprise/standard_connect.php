<?php
function getHeaders()
{
    foreach ($_SERVER as $h => $v) {
        if (preg_match('/HTTP_(.+)/', $h, $hp)) {
            $headers[$hp[1]] = $v ;
        }
    }
    return $headers;
}

echo '<form id="formlogin" method="post" action="'
    . $_SESSION['config']['businessappurl']
    . 'index.php?display=true&page=log';
    if (isset($_SESSION['config']['debug'])
        && $_SESSION['config']['debug'] == 'true'
    ) {
        echo '&XDEBUG_PROFILE';
    }
    echo '" class="forms">';
            echo '<div>';
                echo '<input type="hidden" name="display" id="display" value="true" />';
                echo '<input type="hidden" name="page" id="page" value="log" />';
                echo '<p style="margin-bottom: 3px">';
                    echo '<br/>';
                    echo '<input placeholder="'._ID.'" name="login" id="login" type="text" class="standardConnectInput" />';
                echo '</p>';
                echo '<p>';
                    echo '<input placeholder="'._PASSWORD.'" name="pass" id="pass" type="password" class="standardConnectInput" />';
                echo '</p>';
                $loggingMethod = \SrcCore\models\CoreConfigModel::getLoggingMethod();
                if ($loggingMethod['id'] == 'standard') {
                    echo '<p style="cursor: pointer;font-size: 12px; text-align: right;">';
                    echo '<span onclick="triggerAngular(\'#/forgot-password\')">'._FORGOT_PASSWORD.'</span>';
                    echo '</p>';
                }
                echo '<br><p>';
                    echo '<input type="submit" class="button submitButton" name="submit" value="'._CONNECT.'" />';
                echo '</p>';
            echo '<div class="error">';
            if (isset($_SESSION['error'])) {
                echo functions::xssafe($_SESSION['error']);
            }
            $_SESSION['error'] = '';
            echo '</div>';
          echo '</div>';
        echo '</form>';
