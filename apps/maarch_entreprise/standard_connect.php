<?php
function getHeaders() 
{

    foreach ($_SERVER as $h => $v ) 
    {      
      if( preg_match( '/HTTP_(.+)/', $h, $hp ) )
        $headers[$hp[1]] = $v ;
    }
    return $headers;
}

$userId = '';
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
                echo '<p>';
                    echo '<br/><label for="login">'._ID.'</label>';
                    echo '<input name="login" id="login" value="'.functions::xssafe($userId)
                        .'" type="text"  />';
                echo '</p>';
                echo '<p>';
                    echo '<label for="pass">'._PASSWORD.'</label>';
                    echo '<input name="pass" id="pass" value="" type="password"  />';
                echo '</p>';
                $loggingMethod = \SrcCore\models\CoreConfigModel::getLoggingMethod();
                if ($loggingMethod['id'] == 'standard') {
                    echo '<p style="cursor: pointer;font-size: 11px;">';
                    echo '<label>&nbsp;</label>';
                    echo '<span onclick="triggerAngular(\'#/forgot-password\')">'._FORGOT_PASSWORD.'</span>';
                    echo '</p>';
                }
                echo '<br><p>';
                echo '<label>&nbsp;</label>';
                    echo '<input type="submit" class="button" name="submit" value="'._CONNECT.'" />';
                echo '</p>';
            echo '<div class="error">';
            if(isset($_SESSION['error']))
            {
                echo functions::xssafe($_SESSION['error']);
            }
            $_SESSION['error'] = '';
            echo '</div>';
          echo '</div>';
        echo '</form>';
