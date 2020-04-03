<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   merged_jsAbstract
*
* @author  dev <dev@maarch.org>
* @ingroup apps
*/
class MergedJsAbstract
{
    public function header()
    {
        if (empty($_GET['debug'])) {
            $date = mktime(0, 0, 0, date('m') + 2, date('d'), date('Y'));
            $date = date('D, d M Y H:i:s', $date);
            $time = 30 * 12 * 60 * 60;
            header('Pragma: public');
            header('Expires: '.$date.' GMT');
            header('Cache-Control: max-age='.$time.', must-revalidate');
            header('Content-type: text/javascript');
        }
    }

    public function start()
    {
        ob_start();
    }

    public function end()
    {
        ob_end_flush();
    }

    public function merge_lib()
    {
        readfile('apps/maarch_entreprise/js/prototype.js');

        //scriptaculous (prototype)
        readfile('apps/maarch_entreprise/js/scriptaculous.js');
        readfile('apps/maarch_entreprise/js/effects.js');
        readfile('apps/maarch_entreprise/js/controls.js');

        //Dependencies
        readfile('node_modules/jquery/dist/jquery.min.js');
        readfile('node_modules/zone.js/dist/zone.min.js'); //V2 - Angular
        readfile('node_modules/bootstrap/dist/js/bootstrap.min.js'); //V2
        readfile('node_modules/tinymce/tinymce.min.js');
        readfile('node_modules/jquery.nicescroll/dist/jquery.nicescroll.min.js'); //V2
        readfile('node_modules/tooltipster/dist/js/tooltipster.bundle.min.js'); //V2
        readfile('node_modules/jquery-typeahead/dist/jquery.typeahead.min.js'); //V2
        readfile('node_modules/chosen-js/chosen.jquery.min.js');
        readfile('node_modules/jstree-bootstrap-theme/dist/jstree.js'); //V2
        readfile('apps/maarch_entreprise/js/bootstrap-tree.js'); //DEPRECATED use jstree instead

        //Mobile
        readfile('node_modules/photoswipe/dist/photoswipe.min.js');
        readfile('node_modules/photoswipe/dist/photoswipe-ui-default.min.js');

        //Maarch
        include 'apps/maarch_entreprise/js/functions.js';
        include 'apps/maarch_entreprise/js/indexing.js';
        readfile('apps/maarch_entreprise/js/angularFunctions.js');

        echo "\n";
    }

    public function merge_module()
    {
        if (!empty($_SESSION['modules_loaded'])) {
            foreach (array_keys($_SESSION['modules_loaded']) as $value) {
                if (file_exists($_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$_SESSION['modules_loaded'][$value]['name'].DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'functions.js')
                    || file_exists($_SESSION['config']['corepath'].'modules'.DIRECTORY_SEPARATOR.$_SESSION['modules_loaded'][$value]['name'].DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'functions.js')
                ) {
                    include 'modules/'.$_SESSION['modules_loaded'][$value]['name'].'/js/functions.js';
                }
            }
        }
    }

    public function merge()
    {
        if (empty($_GET['html'])) {
            $this->header();
            $this->start();
            $this->merge_lib();
            $this->merge_module();
            $this->end();
        } else {
            echo '<html><body><script>';
            $this->merge_lib();
            $this->merge_module();
            echo '</script></body></html>';
            exit;
        }
    }
}
