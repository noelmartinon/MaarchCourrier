<?php

/*
*    Copyright 2008-2018 Maarch
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
* Export Class
*
* Contains all the specific functions to export documents
*
* @package  Maarch LetterBox 3.0
* @version 3.0
* @since 06/2007
* @license GPL
* @author  Nathan Cheval <nathan.cheval@edissyum.com>
*
*/

use SrcCore\models\DatabaseModel;

abstract class export_Abstract
{

    public function get_config_xml(){
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'apps'
            . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
            . 'file_export.xml'
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom'
                . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR
                . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR . 'xml'
                . DIRECTORY_SEPARATOR . 'file_export.xml';
        } else {
            $path = 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
                . 'file_export.xml';
        }
        $config = simplexml_load_file($path);
        return $config;
    }

    public function get_path(){
        $config = self::get_config_xml();
        if($config){
            foreach ($config -> DESTINATION as $arrayConfig){
                $path = (string) $arrayConfig -> path;
            }
            return $path;
        }else{
            echo "export pesv2::get_path error";
        }
    }

    public function get_structure(){
        $globality      = array();
        $extensionArr   = array();
        $nameArr        = array();

        $nameConfig = self::get_config_xml();
        if ($nameConfig) {
            foreach ($nameConfig -> NAME as $nameTag) {
                foreach ($nameTag -> ELEMENT as $item) {
                    $type   = (string) $item -> type;
                    $value  = (string) $item -> value;
                    $filter = (string) $item -> filter;
                    array_push(
                        $nameArr,
                        array(
                            'type'      => $type,
                            'value'     => $value,
                            'filter'    => $filter
                        )
                    );
                }
            }
            foreach ($nameConfig->EXTENSION as $extensionTag) {
                foreach ($extensionTag -> ELEMENT as $item) {
                    $type   = (string) $item -> type;
                    $value  = (string) $item -> value;
                    $filter = (string) $item -> filter;
                    array_push(
                        $extensionArr,
                        array(
                            'type'      => $type,
                            'value'     => $value,
                            'filter'    => $filter
                        )
                    );
                }
            }
            foreach($nameConfig -> SEPARATOR as $separatorTag){
                $separatorArr['value'] = (string) $separatorTag -> value;
            }
            array_push(
                $globality,
                array(
                    'ELEMENTS'  => $nameArr,
                    'EXTENSION' => $extensionArr,
                    'SEPARATOR' => $separatorArr
                )
            );

            return $globality;
        } else {
            echo "export pesv2::get_structure error";
        }
    }

    public function get_authorized_attachment_type(){
        $config = self::get_config_xml();
        if($config){
            foreach ($config -> ATTACHMENTS as $attachmentType){
                $attachmentTypes = (string) $attachmentType -> TYPES;
            }
            return $attachmentTypes;
        }else{
            echo "export pesv2::get authorized attachment error";
        }
    }

    public function get_status(){
        $statusArr = array();
        $statusConfig = $this -> get_config_xml();
        if ($statusConfig) {
            foreach ($statusConfig->STATUS as $statusTag) {
                $statusArr['in'] = (string) $statusTag -> IN;
                $statusArr['out'] = (string) $statusTag -> OUT_ID;
            }
            return $statusArr;
        }else {
            echo "export pesv2::get_status error";
        }
    }

    public function get_suffix_attachment(){
        $config = self::get_config_xml();
        if($config){
            foreach ($config -> ATTACHMENTS as $attachmentSuffix){
                $attachmentSuffix = (string) $attachmentSuffix -> SUFFIX;
            }
            return $attachmentSuffix;
        }else{
            echo "export pesv2::get suffix attachment error";
        }
    }

    public function convert_database_field($nameArray, $resId){
        for($i = 0; $i < count($nameArray); $i++){
            $type   = $nameArray[$i]['type'];
            $value  = $nameArray[$i]['value'];
            $filter = $nameArray[$i]['filter'];

            if($type == 'column'){
                $dateFilter = array(
                    'year'      => 'YYYY',
                    'month'     => 'MM',
                    'day'       => 'Day',
                    'full_date' => 'DDMMYYYY'
                );
                if(isset($filter) && array_key_exists($filter, $dateFilter)){
                    $field = "to_char(doc_date,'" . $dateFilter[$filter] . "') as date";
                }else{
                    $field = $value;
                }

                $aField         = DatabaseModel::select([
                    'select'    => [$field],
                    'table'     => ['res_view_letterbox'],
                    'where'     => ['res_id = ?'],
                    'data'      => [$resId]
                ]);
                $nameArray[$i]['value'] = $this -> filter_filename($aField[0]);
            }
        }
        return $nameArray;
    }

    public function convert_format($extensionArray, $resId, $table){
        if($extensionArray[0]['type'] == 'column'){
            $field          = $extensionArray[0]['value'];
            $aField         = DatabaseModel::select([
                'select'    => [$field],
                'table'     => [$table],
                'where'     => ['res_id = ?'],
                'data'      => [$resId]
            ]);
            $extensionArray[0]['value'] = $aField[0][$field];
        }
        return $extensionArray;
    }

    public function generate_name($resId, $suffixAttachment = NULL, $attachmentNumber = '', $isAttachment = NULL){
        $tmp        = $this->get_structure();
        $elements   = $tmp[0]['ELEMENTS'];
        $extension  = $tmp[0]['EXTENSION'];
        $separator  = $tmp[0]['SEPARATOR'];

        $elements   = $this -> convert_database_field($elements, $resId);

        if(isset($isAttachment) && $isAttachment){
            $extension = $this->convert_format($extension, $resId, 'res_attachments');
        }else {
            $extension = $this->convert_format($extension, $resId, 'res_letterbox');
        }

        $string = $this->convert_in_string($elements, $extension, $separator, $suffixAttachment, $attachmentNumber);

        return $string;
    }

    public function convert_in_string($elements, $extension, $separator, $suffixAttachment, $attachmentNumber){
        $tmpArray = array();
        foreach ($elements as $array) {
            array_push(
                $tmpArray,
                $array['value']
            );
        }
        $thisString = join($separator['value'], $tmpArray);
        if(isset($suffixAttachment)){
            $thisString .= $suffixAttachment . $attachmentNumber;
        }
        if(isset($extension)){
            $thisString .= '.' . $extension[0]['value'];
        }
        return $thisString;
    }

    private function filter_filename($name){
        foreach($name as $key => $value){
            $name   = str_replace(array_merge(
                array_map('chr', range(0, 31)),
                array('<', '>', ':', '"', '/', '\\', '|', '?', '*', "'")
            ), '', $value);
            $ext    = pathinfo($name, PATHINFO_EXTENSION);
            $name   = mb_strcut(pathinfo($name, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($name)) . ($ext ? '.' . $ext : '');
        }
        return $name;
    }

    public function check_status($resId){
        $status     = $this -> get_status();
        $statusList = explode(',', $status['in']);

        $aStatus        = DatabaseModel::select([
            'select'    => ['status'],
            'table'     => ['res_letterbox'],
            'where'     => ['res_id = ?'],
            'data'      => [$resId]
        ]);

        if(in_array($aStatus[0]['status'], $statusList) || $status['in'] == ''){
            return true;
        }else return false;
    }

    public function retrieve_creation_date($resId, $table){
        $aCreationDate          = DatabaseModel::select([
            'select'            => ['creation_date'],
            'table'             => [$table],
            'where'             => ['res_id = ?'],
            'data'              => [$resId]
        ]);

        return $aCreationDate[0]['creation_date'];
    }

    public function retrieve_attachments($resIdMaster, $authorizedAttachmentType){
        $whereClause  = "res_id_master = ? AND status NOT IN(?) ";
        if(!empty($authorizedAttachmentType)){
            $authorizedAttachmentType = str_replace(",","','", $authorizedAttachmentType);
            $whereClause .= "AND attachment_type IN ('" . preg_replace('/\s+/', '', $authorizedAttachmentType) . "')";
        }

        $aAttachments           = DatabaseModel::select([
            'select'            => ['res_id'],
            'table'             => ['res_attachments'],
            'where'             => [$whereClause],
            'data'              => [$resIdMaster, array('DEL','OBS')]
        ]);

        return $aAttachments;
    }

    public function retrieve_out_status(){
        $listStatus = $this->get_status();
        $sStatus                = DatabaseModel::select([
            'select'            => ['id_status'],
            'table'             => ['actions'],
            'where'             => ['id = ?'],
            'data'              => [$listStatus['out']]
        ]);

        return $sStatus[0]['id_status'];
    }

    public function change_status($resId){
        DatabaseModel::update([
            'table'     => 'res_letterbox',
            'set'       => [
                'status'    => $this -> retrieve_out_status()
            ],
            'where'     => ['res_id = ?'],
            'data'      => [$resId]
        ]);
    }
}
