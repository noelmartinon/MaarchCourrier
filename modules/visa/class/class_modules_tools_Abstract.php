<?php
/*
*   Copyright 2008-2016 Maarch and Document Image Solutions
*
*   This file is part of Maarch Framework.
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
*   along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * @brief Contains the functions to manage visa and notice workflow.
 *
 * @file
 *
 * @author Nicolas Couture <couture@docimsol.com>
 * @date $date$
 *
 * @version $Revision$
 * @ingroup visa
 */
define('FPDF_FONTPATH', $core_path.'apps/maarch_entreprise/tools/pdfb/fpdf_1_7/font/');
require $core_path.'apps/maarch_entreprise/tools/pdfb/fpdf_1_7/fpdf.php';
require $core_path.'apps/maarch_entreprise/tools/pdfb/fpdf_1_7/fpdi.php';

abstract class visa_Abstract extends Database
{
    public $errorMessageVisa;

    /***
    * Build Maarch module tables into sessions vars with a xml configuration file
    *
    *
    */
    public function build_modules_tables()
    {
        if (file_exists(
            $_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR
            .$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'
            .DIRECTORY_SEPARATOR.'visa'.DIRECTORY_SEPARATOR.'xml'
            .DIRECTORY_SEPARATOR.'config.xml'
        )
        ) {
            $configPath = $_SESSION['config']['corepath'].'custom'
                        .DIRECTORY_SEPARATOR.$_SESSION['custom_override_id']
                        .DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR
                        .'visa'.DIRECTORY_SEPARATOR.'xml'
                        .DIRECTORY_SEPARATOR.'config.xml';
        } else {
            $configPath = 'modules'.DIRECTORY_SEPARATOR.'visa'
                        .DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR
                        .'config.xml';
        }

        $xmlconfig = simplexml_load_file($configPath);
        $conf = $xmlconfig->CONFIG;
        $_SESSION['modules_loaded']['visa']['exeSign'] = (string) $conf->exeSign;
        $_SESSION['modules_loaded']['visa']['showAppletSign'] = (string) $conf->showAppletSign;
        $_SESSION['modules_loaded']['visa']['reason'] = (string) $conf->reason;
        $_SESSION['modules_loaded']['visa']['location'] = (string) $conf->location;
        $_SESSION['modules_loaded']['visa']['licence_number'] = (string) $conf->licence_number;

        $_SESSION['modules_loaded']['visa']['width_blocsign'] = (string) $conf->width_blocsign;
        $_SESSION['modules_loaded']['visa']['height_blocsign'] = (string) $conf->height_blocsign;

        $_SESSION['modules_loaded']['visa']['confirm_sign_by_email'] = (string) $conf->confirm_sign_by_email;

        $routing_template = (string) $conf->routing_template;

        if (file_exists(
            $_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR
            .$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'
            .DIRECTORY_SEPARATOR.'visa'.DIRECTORY_SEPARATOR.'Bordereau_visa_modele.pdf'
        )
        ) {
            $routing_template = $_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR
            .$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'
            .DIRECTORY_SEPARATOR.'visa'.DIRECTORY_SEPARATOR.'Bordereau_visa_modele.pdf';
        }

        $_SESSION['modules_loaded']['visa']['routing_template'] = $routing_template;
    }
}

abstract class PdfNotes_Abstract extends FPDI
{
    public function LoadData($tab, $collId)
    {
        require_once 'core/class/class_request.php';
        $request = new request();
        // Lecture des lignes du fichier
        $data = array();

        $db2 = new Database();
        foreach ($tab as $id) {
            //Check if ID exists
            $stmt2 = $db2->query(
                'SELECT n.identifier, n.creation_date, n.user_id, n.note_text, u.lastname, '
                .'u.firstname FROM notes n inner join '.USERS_TABLE
                .' u on n.user_id  = u.user_id WHERE n.id = :Id ',
                [':Id' => $id]
            );

            if ($stmt2->rowCount() > 0) {
                $line = $stmt2->fetchObject();
                $user = $request->show_string($line->lastname.' '.$line->firstname);
                $notes = str_replace('←', '<=', $line->note_text);
                $date = explode('-', date('d-m-Y', strtotime($line->creation_date)));
                $date = $date[0].'/'.$date[1].'/'.$date[2].' '.date('H:i', strtotime($line->creation_date));
            }
            $data[] = array(utf8_decode($user), $date, utf8_decode($notes));
        }

        return $data;
    }

    public $widths;
    public $aligns;

    public function SetWidths($w)
    {
        $this->widths = $w;
    }

    public function SetAligns($a)
    {
        $this->aligns = $a;
    }

    public function Row($data)
    {
        //Calcule la hauteur de la ligne
        $nb = 0;
        for ($i = 0; $i < count($data); ++$i) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = 5 * $nb;
        $this->CheckPageBreak($h);
        for ($i = 0; $i < count($data); ++$i) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    public function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    public function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n") {
            --$nb;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                ++$i;
                $sep = -1;
                $j = $i;
                $l = 0;
                ++$nl;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        ++$i;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                ++$nl;
            } else {
                ++$i;
            }
        }

        return $nl;
    }
}

abstract class ConcatPdf_Abstract extends FPDI
{
    public $files = array();

    public function setFiles($files)
    {
        $this->files = $files;
    }

    public function concat()
    {
        foreach ($this->files as $file) {
            $pageCount = $this->setSourceFile($file);
            for ($pageNo = 1; $pageNo <= $pageCount; ++$pageNo) {
                $tplIdx = $this->ImportPage($pageNo);
                $s = $this->getTemplatesize($tplIdx);
                $this->AddPage($s['w'] > $s['h'] ? 'L' : 'P', array($s['w'], $s['h']));
                $this->useTemplate($tplIdx);
            }
        }
    }
}
