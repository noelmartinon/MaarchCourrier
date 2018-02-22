<?php

/* View */
if ($viewResourceArr['status'] <> 'ko') {
    if (strtolower($viewResourceArr['mime_type']) == 'application/maarch') {
        
        ?>
        <head><meta content="text/html; charset=UTF-8" http-equiv="Content-Type"></head>
        <?php echo $content;?>
        <?php
    } else {
        if(strtolower($viewResourceArr['ext']) == 'txt'){
            $viewResourceArr['mime_type'] = 'text/plain';
        }
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: public');
        header('Content-Description: File Transfer');
        header('Content-Type: ' . strtolower($viewResourceArr['mime_type']));
        header('Content-Disposition: inline; filename=' . basename(
                    'maarch.' . strtolower($viewResourceArr['ext'])
               )
               . ';');
        header('Content-Transfer-Encoding: binary');
        readfile($filePathOnTmp);
        exit();
    }
} else {
    $core_tools->load_html();
    $core_tools->load_header('', true, false);
    echo '<body>';
    echo '<div style="border: dashed;font-weight: bold;opacity: 0.5;font-size: 30px;height: 96%;text-align: center">';
    echo '<div style="padding-top: 25%;">'.str_replace('||',' ',$viewResourceArr['error']).'<br><sub></sub></div>';
    echo '</div>';
    echo '</body></html>';
    exit();
}
