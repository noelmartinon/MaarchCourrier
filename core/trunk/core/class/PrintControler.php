<?php
/*------------------------------------------------------------------------------
- Require needed classes
------------------------------------------------------------------------------*/
require_once('core/class/class_functions.php');
require_once('core/class/class_core_tools.php');
require_once('core/class/class_db.php');
require_once('core/class/class_history.php');
require_once('apps/maarch_entreprise/class/class_business_app_tools.php');
require_once('core/class/class_security.php');

require_once('apps/maarch_entreprise/tools/PDFMerger/fpdf/fpdf.php');
require_once('apps/maarch_entreprise/tools/PDFMerger/fpdi/fpdi.php');

class EmptyObject {
    function __construct()
    {
        $test = '';
    }
}

/*------------------------------------------------------------------------------
- PrintControler
------------------------------------------------------------------------------*/
class PrintControler extends PrintFunctions
{
    /*--------------------------------------------------------------------------
    - Attributes
    --------------------------------------------------------------------------*/
        // Public
        // Private
        public $collection    = false;
        public $configuration = false;
        
        public $object_print = false;
        public $array_print  = false;
    
    /*--------------------------------------------------------------------------
    - Methods
    --------------------------------------------------------------------------*/
        // Public
        function __construct($resId = '')
        {
			$core = new core_tools();
			$core->load_lang();
            $this->collection = $_SESSION['collection_id_choice'];
            $this->load_configuration();
			if ($resId <> '') {
				$this->retrieve_datas($resId);
			} else {
				$this->retrieve_datas($resId);
			}
            $this->process_functions();
            $_SESSION['print']['filename'] = $this->make_pdf();
        }
        
        // Private
        private function load_configuration()
        {
            // Retrieve id to create paths (app & custom)
            $id_app = $_SESSION['config']['app_id'];
            $id_custom = false;
            if (!empty($_SESSION['custom_override_id']))
                $id_custom = $_SESSION['custom_override_id'];
            $collection = $this->collection;
            
            // Retrieve name for print configuration file
            $fileName = 'print.xml';
            
            // Make paths to xml dir
            $pathToDir_app = 'apps/' . $id_app . '/xml/';
            $pathToDir_custom = 'custom/' . $id_custom . '/' . $pathToDir_app;
            
            $pathToFile_app = $pathToDir_app . $fileName;
            $pathToFile_custom = $pathToDir_custom . $fileName;
            
            // Load the configuration file
            if ($id_custom && file_exists($pathToFile_custom))
                $configuration = simplexml_load_file($pathToFile_custom);
            else
                $configuration = simplexml_load_file($pathToFile_app);
            
            // Store interesting part of the configuration
            $this->configuration = $configuration->$collection;
        }
        
        private function retrieve_datas($resId = '')
        {
            // Retrieve the query
			if ($resId <> '') {
				$query = $this->make_query($resId);
			} else {
				$query = $this->make_query();
			}
            // Retrieve datas
            $db = new dbquery();
            $db->connect();
            $result = $db->query($query);
            $i = 0;
            $this->object_print = new EmptyObject();
            while($line = $db->fetch_object()) {
                $this->object_print->$i = $line;
                $i++;
            }
			//var_dump($this->object_print);exit;
        }
        
        private function make_query($resId = '')
        {
            // Retrieve the end of last select query on the list
            $endLastQuery = substr(
                $_SESSION['last_select_query'], 
                strpos(
                    $_SESSION['last_select_query'], 
                    'FROM'
                )
            );
            
			if ($resId <> '') {
				$security = new security();
				// retrieve view name
				$view = $security->retrieve_view_from_coll_id($this->collection);
				$endLastQuery = 'FROM ' . $view . ' where res_id = ' . $resId;
				// TEST SECURITY ACCESS !!!!
				$right = $security->test_right_doc($this->collection, $resId);
				if (!$right) {
					$endLastQuery .= ' and 1=-1';
				}
			}
            // Create template for the new query
            $query_template = 'SELECT ';
                $query_template .= '##DATABASE_FIELDS## ';
            $query_template .= $endLastQuery;
            
            // Retrieve ##DATABASE_FIELDS##
            $fields = $this->configuration->FIELD;
            $i_max = count($fields);
            $database_fields = false;
            for($i=0; $i<$i_max; $i++) {
                $field = $fields[$i];
                $database_fields .= $field->DATABASE_FIELD;
                if ($i != ($i_max-1))
                    $database_fields .= ', ';
            }
            
            // Return query
            return str_replace(
                '##DATABASE_FIELDS##', 
                $database_fields, 
                $query_template
            );
        }
        
        private function retrieve_encoding($string)
        {
            return mb_detect_encoding($string, 'UTF-8', true);
        }
        
        private function unprotect_string($string)
        {
            return str_replace("\'", "'", $string);
        }
        
        private function process_functions()
        {
            $functions = $this->configuration->FUNCTIONS->FUNCTION;
            $functions_max = count($functions);
            for($i=0; $i<$functions_max; $i++) {
                $function = $functions[$i];
                $call = $function->CALL;
                if (method_exists($this, $call))
                    eval('$this->' . $call . '(\'' . $function->LIBELLE . '\');');
            }
        }
        
		private function make_pdf()
        {
            $functions = new functions();
			$db = new dbquery();
			$db->connect();
			
            $this->array_print = $functions->object2array($this->object_print);
			//var_dump($this->array_print);exit;
			
			if (count((array)$this->array_print) == 0) {
				return false;
			}
			
			$pdf= new fpdi();//create a new document PDF
			$cptResToPrint = count($this->array_print);
			
			for ($cpt=0;$cpt<$cptResToPrint;$cpt++) {
				$y = 0;
				
				$pdf->addPage(); //Add a blank page
			
				/**********************************************************************/
				
				//THE FONT
				$pdf->SetFont('Arial','B',11);
				
				//APPLICATION NAME
				$pdf->Cell(140,5,$_SESSION['config']['applicationname'],0,0, 'L', false);
				
				$pdf->SetFont('Arial','',10);
				
				//PRINT DATE
				$pdf->Cell(40,5,utf8_decode(_PRINT_DATE . ' : ') . date('d-m-Y'),0,1, 'L', false);
				
				$pdf->SetFont('Arial','B',10);
				
				//INITIATOR
				
                if ($this->array_print[$cpt]['initiator'] <> '') {
                    $db->query("select entity_label from entities where entity_id = '" . $this->array_print[$cpt]['initiator'] . "'");
                    $resultEntity = $db->fetch_object();
                    $pdf->Cell(36,5,utf8_decode(_INITIATOR . ' : ' 
                        . $resultEntity->entity_label . " (" . $this->array_print[$cpt]['initiator'] . ")"),0,1, 'L', false);
				} elseif($this->array_print[$cpt]['typist'] <> '') {
                    require_once "modules/entities/class/class_manage_entities.php";
                    $entity = new entity();
                    $initiator = $entity->get_primary_entity($this->array_print[$cpt]['typist']);
                    $db->query("select entity_label from entities where entity_id = '" . $initiator['ID'] . "'");
                    $resultEntity = $db->fetch_object();
                    $pdf->Cell(36,5,utf8_decode(_INITIATOR . ' : ' 
                        . $resultEntity->entity_label . " (" . $initiator['ID'] . ")"),0,1, 'L', false);
                }
				$pdf->SetFont('Arial','B',14);
				
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
                
				//TITLE
				$pdf->Cell(182,5,utf8_decode(_PRINTED_FILE_NUMBER . ' : ') . $this->array_print[$cpt]['res_id'],1,1, 'C', false);
				
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				
				$pdf->SetFont('Arial','B',10);
				
				/**********************************************************************/
				
				//TITLE DOCUMENT
				$pdf->Cell(36,5,utf8_decode(_INFORMATIONS_OF_THE_DOCUMENT),0,1, 'L', false);
				
				$pdf->SetFont('Arial','',9);
				
				//LINE 1
				if ($this->array_print[$cpt]['category_id'] <> '' || $this->array_print[$cpt]['priority'] <> '') {
                    if (count($_SESSION['coll_categories']) == 0) {
                        $categoryLabel = $_SESSION['mail_categories'][$this->array_print[$cpt]['category_id']];
                    } else {
                        $categoryLabel = $_SESSION['coll_categories']['letterbox_coll'][$this->array_print[$cpt]['category_id']];
                    }
					//CATEGORY
					$pdf->Cell(91,5,utf8_decode(_PRINT_CATEGORY . ' : ' 
						. html_entity_decode($categoryLabel)),1,0, 'L', false);
					//PRIORITY
					$pdf->Cell(91,5,utf8_decode(_PRINT_PRIORITY . ' : ' . $_SESSION['mail_priorities'][$this->array_print[$cpt]['priority']]),1,1, 'L', false);
				}
				
				//LINE 2
				if ($this->array_print[$cpt]['admission_date'] <> '' || $this->array_print[$cpt]['process_limit_date'] <> '') {
					//ADMISSION DATE
					$pdf->Cell(91,5,utf8_decode(_PRINT_ADMISSION_DATE . ' : ') 
						. $functions->format_date_db($this->array_print[$cpt]['admission_date'], false),1,0, 'L', false);
					//PROCESS_LIMIT_DATE
					$pdf->Cell(91,5,utf8_decode(_PRINT_PROCESS_LIMIT_DATE . ' : ') 
						. $functions->format_date_db($this->array_print[$cpt]['process_limit_date'], false),1,1, 'L', false);
				}
				
				//LINE 3, 4
				if ($this->array_print[$cpt]['nature_id'] <> '' || $this->array_print[$cpt]['doc_date'] <> '') {
					//CREATION DATE
					$pdf->Cell(91,5,utf8_decode(_CREATED_ON . ' : ') 
						. $functions->format_date_db($this->array_print[$cpt]['creation_date'], false),1,0, 'L', false);
					
					//NATURE
					foreach (array_keys($_SESSION['mail_natures']) as $nature) {
						if ($this->array_print[$cpt]['nature_id'] == $nature) {
							$this->array_print[$cpt]['nature_id'] = $_SESSION['mail_natures'][$nature];
						}
					}
					$pdf->Cell(91,5,utf8_decode(_NATURE . ' : ' . $this->array_print[$cpt]['nature_id']),1,1, 'L', false);
					
					//DOC DATE
					$pdf->Cell(91,5,utf8_decode(_PRINT_DOC_DATE . ' : ') 
						. $functions->format_date_db($this->array_print[$cpt]['doc_date'], false),1,0, 'L', false);
					
					//DOCTYPE
					$pdf->Cell(91,5,utf8_decode(_DOCTYPE . ' : ' . $this->array_print[$cpt]['type_label']),1,1, 'L', false);
				} else {
					//CREATION DATE
					$pdf->Cell(91,5,utf8_decode(_CREATED_ON . ' : ') 
						. $functions->format_date_db($this->array_print[$cpt]['creation_date'], false),1,0, 'L', false);
					
					//DOCTYPE
					$pdf->Cell(91,5,utf8_decode(_DOCTYPE . ' : ' . $this->array_print[$cpt]['type_label']),1,1, 'L', false);
				}
				
				//LINE 5
				if ($this->array_print[$cpt]['folder_name'] <> '' || $this->array_print[$cpt]['arbox_id'] <> '') {
					//FOLDER
					$pdf->Cell(91,5,utf8_decode(_PRINT_FOLDER . ' : ' . $this->array_print[$cpt]['folder_name']),1,0, 'L', false);
					//ARBOX
					$pdf->Cell(91,5,utf8_decode(_PRINT_ARBOX . ' : ' . $this->array_print[$cpt]['arbox_id']),1,1, 'L', false);
				}
				
				//LINE 6
				if ($this->array_print[$cpt]['status'] <> '' || $this->array_print[$cpt]['alt_identifier'] <> '') {
                    require_once('core/class/class_manage_status.php');
                    $status_obj = new manage_status();
                    $res_status = $status_obj->get_status_data($this->array_print[$cpt]['status'], '');
					//STATUS
					$pdf->Cell(91,5,utf8_decode(_PRINT_STATUS . ' : ' . $res_status['LABEL']),1,0, 'L', false);
					//ALT IDENTIFIER
					$pdf->Cell(91,5,utf8_decode(_PRINT_ALT_IDENTIFIER . ' : ' . $this->array_print[$cpt]['alt_identifier']),1,1, 'L', false);
				}
				
				/**********************************************************************/
				
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				
				$pdf->SetFont('Arial','B',11);
				
                if ($this->array_print[$cpt]['contact_id'] <> '') {
                    $contactInfos = $this->getContactInfos($this->array_print[$cpt]['contact_id']);
                }
				
				//CONTACT
				$pdf->Cell(182,5,utf8_decode(_PRINT_CONTACT),0,1, 'C', false);
				
				$pdf->SetFont('Arial','',11);
				
				$pdf->MultiCell(182,5,utf8_decode($contactInfos),1, 'C', false);
				
				/**********************************************************************/
				
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				
				$pdf->SetFont('Arial','B',11);
				
				//SUBJECT
				$pdf->Cell(182,5,utf8_decode(_PRINT_SUBJECT),0,1, 'C', false);
				
				$pdf->SetFont('Arial','',11);
				
				$pdf->MultiCell(182,5,utf8_decode($this->unprotect_string($this->array_print[$cpt]['subject'])),1, 'L', false);
				
				/**********************************************************************/
				
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				
				$pdf->SetFont('Arial','B',11);
				
				$userInfos = $this->getUserInfo($this->array_print[$cpt]['dest_user']);
				
				//DESTINATION
				$pdf->Cell(182,5,utf8_decode(_PRINT_PROCESS_ENTITY),0,1, 'C', false);
				
				$pdf->SetFont('Arial','B',11);
				
				$pdf->MultiCell(182,5,utf8_decode($this->array_print[$cpt]['entity_label']. "\r\n" . $userInfos),1, 'C', false);
				
				/**********************************************************************/
				
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				
				$pdf->SetFont('Arial','B',11);
				
				//COPIES
				if ($this->array_print[$cpt]['retrieve_copies'] <> '') {
					$pdf->Cell(182,5,utf8_decode(_PRINT_COPIES),0,1, 'C', false);
					
					$pdf->SetFont('Arial','',11);
					
					$pdf->MultiCell(182,5,utf8_decode($this->array_print[$cpt]['retrieve_copies']),1, 'L', false);
				}
				
				/**********************************************************************/
				
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				
				$pdf->SetFont('Arial','B',11);
				
				//NOTES
				if ($this->array_print[$cpt]['retrieve_notes'] <> '') {
					$pdf->Cell(182,5,utf8_decode(_PRINT_NOTES),0,1, 'C', false);
					
					$pdf->SetFont('Arial','',11);
					
					$pdf->MultiCell(182,5,utf8_decode($this->array_print[$cpt]['retrieve_notes']),1, 'L', false);
				}
				
				/**********************************************************************/
                
                //BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				
				$pdf->SetFont('Arial','B',11);
				
				//FREE NOTES
				if ($this->array_print[$cpt]['free_notes'] <> '') {
					$pdf->Cell(182,5,utf8_decode(_PRINT_FREE_NOTES),0,1, 'C', false);
					
					$pdf->SetFont('Arial','',11);
					
					$pdf->MultiCell(182,5,utf8_decode($this->array_print[$cpt]['free_notes']),1, 'L', false);
				}
				
				/**********************************************************************/
				
				$pdf->SetFont('Arial','B',11);
				
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				//BREAK A LINE
				$pdf->SetY($pdf->GetY()+4);
				
				//TYPIST
				$userInfos = $this->getUserInfo($this->array_print[$cpt]['typist']);
				$pdf->Cell(150,5,utf8_decode(_PRINT_TYPIST . " : " . $userInfos),0,0, 'L', false);
				
				/**********************************************************************/
			}
			
			//$pdf->AutoPrint(true);
        
			$printNameInTmp = "print_" 
				. $_SESSION['user']['UserId'] . '_' . rand() . ".pdf";
			$_SESSION['printNameInTmp'] = $printNameInTmp;
			$pdfPath = $_SESSION['config']['tmppath'] . $printNameInTmp;
			
			$pdf->Output($pdfPath);
			
			return $printNameInTmp;
		}
}

class PrintFunctions
{
    /* -------------------------------------------------------------------------
    - Functions
    -
    - All the functions must have only one argument
    - This argument is the name of the column for the header of the print
    -
    - Toutes les fonctions doivent avoir un argument et un seul
    - Cette argument est le libelle de la colonne à afficher dans l'en-tête du
    - fichier d'print
    ------------------------------------------------------------------------- */
	function getContactInfos($contactId)
	{
		$business = new business_app_tools();
		$tmp = $business->get_titles();
		$titles = $tmp['titles'];
		
		$contactInfos = '';
	
		$db = new dbquery();
        $db->connect();
		
		$query = "select * from contacts where contact_id = " . $contactId;
		$db->query($query);
        while($result = $db->fetch_object()) {
			if ($result->society <> '') {
				$contactInfos = $result->society . "\r\n";
			}
			if ($result->title <> '') {
				foreach(array_keys($titles) as $key) {
					if($result->title == $key) {
						$result->title = $titles[$key];
					}
				}
				$contactInfos .= $result->title . ' ';
			}
			if ($result->firstname <> '') {
				$contactInfos .= $result->firstname . ' ';
			}
			if ($result->lastname <> '') {
				$contactInfos .= $result->lastname ."\r\n";
			}
			if ($result->address_num <> '') {
				$contactInfos .= $result->address_num . ' ';
			}
			if ($result->address_street <> '') {
				$contactInfos .= $result->address_street . ' ';
			}
			if ($result->address_complement <> '') {
				$contactInfos .= $result->address_complement;
			}
			if ($result->address_postal_code <> '') {
				$contactInfos .= "\r\n" . $result->address_postal_code . ' ';
			}
			if ($result->address_town <> '') {
				$contactInfos .= $result->address_town . ' ';
			}
			if ($result->address_country <> '') {
				$contactInfos .= $result->address_country;
			}
		}
		return $contactInfos;
	}
	
	function getUserInfo($userId)
    {
		if ($userId <> '') {
			$db = new dbquery();
			$db->connect();
			$db->query("select firstname, lastname from users where user_id = '" . $userId . "'");
			$resultUsers = $db->fetch_object();
			if ($resultUsers->firstname <> '' && $resultUsers->lastname <> '') {
				return $resultUsers->firstname . ' ' . $resultUsers->lastname;
				//return $resultUsers->firstname . ' ' . $resultUsers->lastname . ' (' . $userId . ')';
			}
		} else {
			return false;
		}
	}
	
    function retrieve_copies($libelle)
    {
        $db = new dbquery();
        $db->connect();
        $db2 = new dbquery();
        $db2->connect();
		$db3 = new dbquery();
        $db3->connect();
        
        $collection = $this->collection;
        
        $query_template = 'SELECT ';
            $query_template .= 'item_id, ';
            $query_template .= 'item_type ';
        $query_template .= 'FROM ';
            $query_template .= 'listinstance ';
        $query_template .= 'WHERE ';
                $query_template .= "res_id = ##res_id## ";
            $query_template .= "AND ";
                $query_template .= "coll_id = '" . $collection . "' ";
            $query_template .= "AND ";
                $query_template .= "item_mode = 'cc'";
        
        $query_template2 = 'SELECT ';
            $query_template2 .= 'entity_id ';
        $query_template2 .= 'FROM ';
            $query_template2 .= 'users_entities ';
        $query_template2 .= 'WHERE ';
                $query_template2 .= "user_id = '##item_id##' ";
            $query_template2 .= "AND ";
                $query_template2 .= "primary_entity = 'Y'";
        
        $i = 0;
        foreach($this->object_print as $line_name => $line_value) {
            $return = false;
            $res_id = $line_value->res_id;
            $query = str_replace('##res_id##', $res_id, $query_template);
            $db->query($query);
            while($result = $db->fetch_object()) {
                if ($result->item_type == 'user_id') {
                    $query = str_replace('##item_id##', $result->item_id, $query_template2);
                    $db2->query($query);
                    while ($result2 = $db2->fetch_object()) {
						$db3->query("select entity_label from entities where entity_id = '" . $result2->entity_id . "'");
						$resultEntity = $db3->fetch_object();
						$userInfos = $this->getUserInfo($result->item_id);
						$usersEntities = "- " . $resultEntity->entity_label . ' : ' . $userInfos;
                    }
                } else {
					$db3->query("select entity_label from entities where entity_id = '" . $result->item_id . "'");
					$resultEntity = $db3->fetch_object();
                    $usersEntities = "- " . $resultEntity->entity_label . ' (' . $result->item_id . ") ";
                }
                $return .= $usersEntities . "\r\n";
            }
            if (strlen($return) > 3)
                $return = substr($return, 0, -2);
            
            $line_value->retrieve_copies = $return;
            $i++;
        }
    }
	
	function free_notes($libelle)
	{
		foreach($this->object_print as $line_name => $line_value) {
			$return = "";
			$return = "NOTE 1\r\n"
				. "__________________________________"
				. "__________________________________"
				. "_______________\r\n";
			$return .= "NOTE 2\r\n"
				. "__________________________________"
				. "__________________________________"
				. "_______________\r\n";
			$return .= "NOTE 3\r\n\r\n";
			
			$line_value->free_notes = $return;
		}
	}
	
	function retrieve_notes($libelle)
	{
		$db = new dbquery();
        $db->connect();
		$db2 = new dbquery();
        $db2->connect();
		
		$collection = $this->collection;
        
        $query_template = 'SELECT ';
            $query_template .= 'id, ';
            $query_template .= 'user_id, ';
            $query_template .= 'date_note, ';
            $query_template .= 'note_text ';
        $query_template .= 'FROM ';
            $query_template .= 'notes ';
        $query_template .= 'WHERE ';
                $query_template .= "identifier = ##res_id## ";
            $query_template .= "AND ";
                $query_template .= "coll_id = '" . $collection . "' ";
			//EXCLUDE PRIVATE NOTES
			$query_template .= "AND id not in (select note_id from note_entities) ";
			$query_template .= "order by id";
		
		$i = 0;
        foreach($this->object_print as $line_name => $line_value) {
			$return = false;
            $res_id = $line_value->res_id;
            $query = str_replace('##res_id##', $res_id, $query_template);
            $db->query($query);
			while($result = $db->fetch_object()) {
				/*$db2->query("select count(id) as cptprivatenote from note_entities where note_id = " . $result->id);
				$resultPrivate = $db2->fetch_object();
				if ($resultPrivate->cptprivatenote > 0) {
					$result->note_text = _PRINT_PRIVATE_NOTE;
				}*/
				$userInfos = $this->getUserInfo($result->user_id);
				$return .= "- " 
                    //. $result->id . " " 
                    . _PRINT_THE . " " 
                    . $db->format_date_db($result->date_note, false) 
                    . " " . _BY . " " . $userInfos . " : " . $result->note_text . "\r\n"
				. "__________________________________"
				. "__________________________________"
				. "_______________\r\n";
			}
			if (strlen($return) > 3)
                $return = substr($return, 0, -85);
			
			$line_value->retrieve_notes = $return;
            $i++;
		}
	}
}
