<?php
/*
*   Copyright 2008-2017 Maarch
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

class Zip
{
    
    protected $executable;

    public function __construct($zipExecutable=false)
    {
        if (!$zipExecutable) {
            switch (DIRECTORY_SEPARATOR) {
                // Windows installation
                case '\\':
                    $this->executable = 'C:\Program Files (x86)\7-Zip\7z.exe';
                    break;

                case "/":
                default:
                    $this->executable = "7z";
            }
        } else {
            $this->executable = $zipExecutable;
        }
    }

    
    public function add($archive, $filename, array $options=null)
    {
        $tokens = array('"' . $this->executable . '"');
        $tokens[] = "a";
        $tokens[] = '"' . $archive . '"';
        $tokens[] = '"' . $filename . '"';
        //$tokens[] = '-scsUTF-8';
        if ($options) {
            foreach ($options as $option) {
                $tokens[] = $option;
            }
        }

        $command = implode(' ', $tokens);

        $output = array();
        $return = null;
        $this->errors = array();

        exec($command, $output, $return);

        if ($return === 0) {
            return true;
        } else {
            $message = $this->handleError($return);

            return $message;
        }
    }
    
    protected function handleError($return)
    {       
        switch ($return) {
            case 1 :
                return "Warning: Some files could not be processed. See output for more informations.";

            case 2 : 
                return "Error: Unable to process the command. See output for more informations.";
 
            case 7 : 
                return "Command line error.";

            case 8 :
                return "Not enough memory for operation.";

            case 255 :
                return "User stopped the process.";

            default:
                return "Unknown error.";

        }
    }

}