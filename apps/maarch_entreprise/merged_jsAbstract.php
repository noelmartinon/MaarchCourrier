<?php

/*
*    Copyright 2017 Maarch
*
*  This file is part of Maarch Framework.
*
*   Maarch Framework is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   Maarch Framework is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

class MergedJsAbstract {
	public function header() {
		if ( empty($_GET['debug']) ) {
			$date = mktime(0,0,0,date("m" ) + 2  ,date("d" ) ,date("Y" )  );
			$date = date("D, d M Y H:i:s", $date);
			$time = 30*12*60*60;
			header("Pragma: public");
			header("Expires: ".$date." GMT");
			header("Cache-Control: max-age=".$time.", must-revalidate");
			header('Content-type: text/javascript');
		}
	}
	public function start() {
		ob_start();
	}
	public function end() {
		ob_end_flush();
	}

	public function merge_lib() {
		readfile('apps/maarch_entreprise/js/prototype.js');

		//scriptaculous (prototype)
		readfile('apps/maarch_entreprise/js/scriptaculous.js');
		readfile('apps/maarch_entreprise/js/effects.js');
		readfile('apps/maarch_entreprise/js/controls.js');

		//TODO clean
		readfile('apps/maarch_entreprise/js/chosen.proto.min.js');
		readfile('apps/maarch_entreprise/js/scrollbox.js');
		readfile('apps/maarch_entreprise/js/event.simulate.js'); // Works with chosen proto
		readfile('apps/maarch_entreprise/js/tabricator.js');

		//Dependencies
		readfile('node_modules/jquery/dist/jquery.min.js');
		readfile('node_modules/core-js/client/shim.js');
		readfile('node_modules/zone.js/dist/zone.min.js');
		readfile('node_modules/bootstrap/dist/js/bootstrap.min.js');
		readfile('node_modules/chart.js/Chart.min.js');
		readfile('node_modules/tinymce/tinymce.min.js');
		readfile('node_modules/jquery.nicescroll/jquery.nicescroll.min.js');
		readfile('node_modules/jquery-typeahead/dist/jquery.typeahead.min.js');
		readfile('node_modules/tooltipster/dist/js/tooltipster.bundle.min.js');

		//Mobile
		readfile('node_modules/photoswipe/dist/photoswipe.min.js');
		readfile('node_modules/photoswipe/dist/photoswipe-ui-default.min.js');

		//Maarch
		include('apps/maarch_entreprise/js/functions.js');
		include('apps/maarch_entreprise/js/indexing.js');
                
                //INPI
		include('apps/maarch_entreprise/js/keypress.js');

//		include('apps/maarch_entreprise/js/RSVP.js');
//		include('apps/maarch_entreprise/js/render.js');
//		include('apps/maarch_entreprise/js/jio.js');

        echo "\n";
	}

	public function merge_module() {
		if ( !empty($_SESSION['modules_loaded'])) {
			foreach(array_keys($_SESSION['modules_loaded']) as $value)
			{
			    if(file_exists($_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$_SESSION['modules_loaded'][$value]['name'].DIRECTORY_SEPARATOR."js".DIRECTORY_SEPARATOR."functions.js")
					|| file_exists($_SESSION['config']['corepath'].'modules'.DIRECTORY_SEPARATOR.$_SESSION['modules_loaded'][$value]['name'].DIRECTORY_SEPARATOR."js".DIRECTORY_SEPARATOR."functions.js"))
			    {
			        include('modules/'.$_SESSION['modules_loaded'][$value]['name'].'/js/functions.js');
			    }
			}
		}
	}

	public function merge() {
		if ( empty($_GET['html'] ) ) {
			$this->header();
			$this->start();
			$this->merge_lib();
			$this->merge_module();
			$this->end();
		} else {
			echo '<html><body><script>';
			$this->merge_lib();
			$this->merge_module();
			echo '</script></body></html>';exit;
		}
	}
}
