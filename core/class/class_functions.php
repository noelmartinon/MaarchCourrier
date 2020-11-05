<?php
/*
*    Copyright 2008-2015 Maarch
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

/**
* @brief   Contains all the various functions of this application.
*
* @file
* @author Claire Figueras <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup core
*/

/**
* @brief   Contains all the various functions of this application.
*
* <ul>
*  <li>The toolkit of the Maarch framework</li>
*  <li>Management of variables format</li>
*  <li>Management of date format</li>
* </ul>
* @ingroup core
*/
class functions
{
    /**
    *
    * @deprecated
         */
    private $f_page;

    public function normalize($string)
    {
        $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ'
            . 'ßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
        $b = 'aaaaaaaceeeeiiiidnoooooouuuuy'
            . 'bsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
        $string = utf8_decode($string);
        $string = strtr($string, utf8_decode($a), $b);
        $string = strtolower($string);

        return utf8_encode($string);
    }

    /**
    * Cuts a string at the maximum number of char to displayed
    *
    * @param     $string string String value
    * @param     $max integer Maximum character number
    */
    public function cut_string($string, $max)
    {
        if (strlen($string) >= $max) {
            $string = substr($string, 0, $max);
            $espace = strrpos($string, " ");
            $string = substr($string, 0, $espace)."...";
            return $string;
        } else {
            return $string;
        }
    }

    /**
    * Adds en error to the errors log
    *
    * @param     $msg  string Message to add
    * @param  $var  string Language dependant message
    */
    public function add_error($msg, $var)
    {
        $msg = trim($msg);
        if (!empty($msg)) {
            $_SESSION['error'] .= $msg." ".$var . ' ';
            if (strlen(str_replace(array("<br />","<br />"), "", $_SESSION['error'])) < 6) {
                $_SESSION['error'] = "";
            }
        }
    }

    /**
    * Cleans a variable with multiple possibility
    *
    * @param     $what  string Variable to clean
    * @param  $mask  string Mask, "no" by default
    * @param     $msg_error string Error message, empty by default
    * @param     $empty  string "yes" by default
    * @param     $min_limit integer Empty by default
    * @param     $max_limit integer Empty by default
    * @return   string Cleaned variable or empty string
    */
    public function wash($what, $mask = "no", $msg_error = "", $empty = "yes", $min_limit = "", $max_limit = "", $custom_pattern = '', $custom_error_msg = '')
    {

        //$w_var = addslashes(trim(strip_tags($what)));

        $w_var = trim(strip_tags($what));
        $test_empty = "ok";

        if ($empty == "yes") {
            // We use strlen instead of the php's empty function because for a var containing 0 return by a form (in string format)
            // the empty function return that the var is empty but it contains à 0
            if (strlen($w_var) == 0) {
                $test_empty = "no";
            } else {
                $test_empty = "ok";
            }
        }
        if ($test_empty == "no") {
            $this->add_error($msg_error, _IS_EMPTY);
            return "";
        } else {
            if ($msg_error <> '') {
                if ($min_limit <> "") {
                    if (strlen($w_var) < $min_limit) {
                        if ($min_limit > 1) {
                            $this->add_error($msg_error, _MUST_MAKE_AT_LEAST." ".$min_limit." "._CHARACTERS);
                        } else {
                            $this->add_error($msg_error, _MUST_MAKE_AT_LEAST." ".$min_limit." "._CHARACTERS);
                        }
                        return "";
                    }
                }
            }

            if ($max_limit <> "") {
                if (strlen($w_var) > $max_limit) {
                    if ($min_limit > 1) {
                        $this->add_error($msg_error, MUST_BE_LESS_THAN." ".$max_limit." "._CHARACTERS);
                    } else {
                        $this->add_error($msg_error, MUST_BE_LESS_THAN." ".$max_limit." "._CHARACTERS);
                    }

                    return "";
                }
            }

            switch ($mask) {
                case "no":
                    return $w_var;

                case "num":
                    if (preg_match("/^[0-9]+$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT." :<br/>"._WAITING_INTEGER);
                        return "";
                    }

                    // no break
                case "float":
                    if (preg_match("/^[0-9.,]+$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT." "._WAITING_FLOAT);
                        return "";
                    }

                    // no break
                case "letter":
                    if (preg_match("/^[a-zA-Z]+$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT);
                        $this->add_error(_ONLY_ALPHABETIC, '');
                        return "";
                    }

                    // no break
                case "alphanum":
                    if (preg_match("/^[a-zA-Z0-9]+$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT);
                        $this->add_error(_ONLY_ALPHANUM, '');
                        return "";
                    }

                    // no break
                case "alphanumunderscore":
                    if (preg_match("/^[a-zA-Z0-9_]+$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT);
                        return "";
                    }

                    // no break
                case "nick":
                    if (preg_match("/^[_a-zA-Z0-9.-]+$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT);
                        return "";
                    }

                    // no break
                case "mail":
                    if (preg_match("/^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]{2,10}$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT);
                        return "";
                    }

                    // no break
                case "url":
                    if (preg_match("/^[www.]+[_a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT);
                        return "";
                    }

                    // no break
                case "file":
                    if (preg_match("/^[_a-zA-Z0-9.-? é&\/]+$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT);
                        return "";
                    }

                    // no break
                case "name":
                    if (preg_match("/^[_a-zA-Z0-9.-? \'\/&éea]+$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT);
                        return "";
                    }
                    // no break
                case "phone":
                    if (preg_match("/^[\+0-9\(\)\s\.]*$/", $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT);
                        return "";
                    }
                    // no break
                case "date":
                    $date_pattern = "/^[0-3][0-9]-[0-1][0-9]-[1-2][0-9][0-9][0-9]$/";
                    if (preg_match($date_pattern, $w_var)) {
                        return $w_var;
                    } else {
                        $this->add_error($msg_error, _WRONG_FORMAT." "._WAITING_DATE);
                        return "";
                    }
                    // no break
                case "custom":
                    if (preg_match($custom_pattern, $w_var) == 0) {
                        $this->add_error($msg_error, $custom_error_msg.' '.$custom_pattern.' '.$w_var);
                        return "";
                    } else {
                        return $w_var;
                    }
            }
        }
    }

    /**
    * Returns a variable with personnal formating. It allows you to add formating action when you displays the variable the var
    *
    * @param     $what string Variable to format
    * @return string  Formated variable
    */
    public static function show_str($what)
    {
        return stripslashes($what);
    }

    /**
    * Manages the location bar in session (4 levels max), then calls the where_am_i() function.
    *
    * @param     $path  string Url (empty by default)
    * @param   $label string Label to show in the location bar (empty by default)
    * @param   $id_pagestring  Page identifier (empty by default)
    * @param   $init bool If true reinits the location bar (true by default)
    * @param   $level string Level in the location bar (empty by default)
    */
    public function manage_location_bar($path = '', $label = '', $id_page = '', $init = true, $level = '')
    {
        //INIT LOCATION BAR
        if (empty($_SESSION['location_bar_label'])) {
            $_SESSION['location_bar_label'][0] = _WELCOME_TITLE;
            $_SESSION['location_bar_path'][0] = 'index.php?reinit=true';
        }
        if (!empty($level)) {
            //IF USER CLICKED ON LOCATION BAR
            $arrLocationLabel = [];
            $arrLocationPath = [];
            foreach ($_SESSION['location_bar_label'] as $key => $value) {
                $arrLocationLabel[] = $_SESSION['location_bar_label'][$key];
                $arrLocationPath[] = $_SESSION['location_bar_path'][$key];
                if ($key == $level) {
                    break;
                }
            }
            $_SESSION['location_bar_label'] = $arrLocationLabel;
            $_SESSION['location_bar_path'] = $arrLocationPath;
        } elseif (count($_SESSION['location_bar_label'])==4 && $_SESSION['location_bar_label'][count($_SESSION['location_bar_label'])-1] != $label) {
            //ERASE BEGIN OF LOCATION BAR IF TOO MUCH ITEMS
            array_shift($_SESSION['location_bar_label']);
            array_shift($_SESSION['location_bar_path']);

            $_SESSION['location_bar_label'][0] = _WELCOME_TITLE;
            $_SESSION['location_bar_path'][0] = 'index.php?reinit=true';
        }
        
        //ADD NEW LOCATION
        if ($_SESSION['location_bar_label'][count($_SESSION['location_bar_label'])-1] != $label) {
            $_SESSION['location_bar_label'][] = $label;
            $_SESSION['location_bar_path'][] = $path;
        }

        //WRITE LOCATION BAR
        foreach ($_SESSION['location_bar_label'] as $key => $value) {
            ?>
<script type="text/javascript">
    writeLocationBar('<?php echo $_SESSION['location_bar_path'][$key]; ?>', '<?php echo $value; ?>', '<?php echo $key; ?>');
</script><?php
        }
    }


    /**
    * For debug, displays an array in a more readable way
    *
    * @param   $arr array Array to display
    */
    public function show_array($arr)
    {
        echo "<table width=\"550\"><tr><td align=\"left\">";
        echo "<pre>";
        print_r($arr);
        echo "</pre>";
        echo "</td></tr></table>";
    }

    /**
    * Formats a datetime to a dd/mm/yyyy format (date)
    *
    * @param   $date datetime The date to format
    * @return   datetime  The formated date
    */
    public function format_date($date)
    {
        $last_date = '';
        if ($date <> "") {
            if (strpos($date, " ")) {
                $date_ex = explode(" ", $date);
                $the_date = explode("-", $date_ex[0]);
                $last_date = $the_date[2]."-".$the_date[1]."-".$the_date[0];
            } else {
                $the_date = explode("-", $date);
                $last_date = $the_date[2]."-".$the_date[1]."-".$the_date[0];
            }
        }
        return $last_date;
    }

    /**
    * Formats a datetime to a dd/mm/yyyy hh:ii:ss format (timestamp)
    *
    * @param   $date  datetime The date to format
    * @return   datetime  The formatted date
    */
    public function dateformat($realDate, $sep='/')
    {
        if ($realDate <> '') {
            if (preg_match('/ /', $realDate)) {
                $hasTime = true;
                $tmpArr = explode(" ", $realDate);
                $date = $tmpArr[0];
                $time = $tmpArr[1];
                if (preg_match('/\./', $time)) {  // POSTGRES date
                    $tmp = explode('.', $time);
                    $time = $tmp[0];
                } elseif (preg_match('/,/', $time)) { // ORACLE date
                    $tmp = explode(',', $time);
                    $time = $tmp[0];
                }
            } else {
                $hasTime = false;
                $date = $realDate;
            }
            if (preg_match('/-/', $date)) {
                $dateArr = explode("-", $date);
            } elseif (preg_match('@\/@', $date)) {
                $dateArr = explode("/", $date);
            }
            if (! $hasTime || substr($tmpArr[1], 0, 2) == "00") {
                return $dateArr[2] . $sep . $dateArr[1] . $sep . $dateArr[0];
            } else {
                return $dateArr[2] . $sep . $dateArr[1] . $sep . $dateArr[0]
                    . " " . $time;
            }
        }
        return '';
    }

    /**
    * Returns a formated date for SQL queries
    *
    * @param  $date date Date to format
    * @param  $insert bool If true format the date to insert in the database (true by default)
    * @return Formated date or empty string if any error
    */
    public static function format_date_db($date, $insert=true, $databasetype= '', $withTimeZone=false)
    {
        if (isset($_SESSION['config']['databasetype'])
            && ! empty($_SESSION['config']['databasetype'])) {
            $databasetype = $_SESSION['config']['databasetype'];
        }

       

        if ($date <> "") {
            $var = explode('-', $date) ;

            if (preg_match('/\s/', $var[2])) {
                $tmp = explode(' ', $var[2]);
                $var[2] = $tmp[0];
                $var[3] = substr($tmp[1], 0, 8);
            }

            if (preg_match('/^[0-3][0-9]$/', $var[0])) {
                $day = $var[0];
                $month = $var[1];
                $year = $var[2];
                $hours = $var[3];
            } else {
                $year = $var[0];
                $month = $var[1];
                $day = substr($var[2], 0, 2);
                $hours = $var[3];
            }
            if ($year <= "1900") {
                return '';
            } else {
                if ($databasetype == "SQLSERVER") {
                    if ($withTimeZone) {
                        return  $day . "-" . $month . "-" . $year . " " . $hours;
                    } else {
                        return  $day . "-" . $month . "-" . $year;
                    }
                } elseif ($databasetype == "POSTGRESQL") {
                    if ($_SESSION['config']['lang'] == "fr") {
                        if ($withTimeZone) {
                            return $day . "-" . $month . "-" . $year . " " . $hours;
                        } else {
                            return $day . "-" . $month . "-" . $year;
                        }
                    } else {
                        if ($withTimeZone) {
                            return $year . "-" . $month . "-" . $day . " " . $hours;
                        } else {
                            return $year . "-" . $month . "-" . $day;
                        }
                    }
                } elseif ($databasetype == "ORACLE") {
                    return  $day . "-" . $month . "-" . $year;
                } elseif ($databasetype == "MYSQL" && $insert) {
                    return $year . "-" . $month . "-" . $day;
                } elseif ($databasetype == "MYSQL" && !$insert) {
                    return  $day . "-" . $month . "-" . $year;
                }
            }
        } else {
            return '';
        }
    }

    /**
    * Protects string to insert in the database
    *
    * @param  $string string String to format
    * @return Formated date
    */
    public function protect_string_db($string, $databasetype = '', $full='yes')
    {
        if (isset($_SESSION['config']['databasetype']) && !empty($_SESSION['config']['databasetype'])) {
            $databasetype = $_SESSION['config']['databasetype'];
        }
        if ($databasetype  == "SQLSERVER") {
            $string = str_replace("'", "''", $string);
            $string = str_replace("\\", "", $string);
        } elseif ($databasetype  == "ORACLE") {
            $string = str_replace("'", "''", $string);
            $string = str_replace("\\", "", $string);
        } elseif (($databasetype  == "MYSQL")) {
            $string = addslashes($string);
        } elseif (($databasetype  == "POSTGRESQL")) {
            $string = str_replace("&#039;", "'", $string);
            $string = pg_escape_string($string);
        }

        if ($full == 'yes') {
            $string=str_replace(';', ' ', $string);
            $string=str_replace('--', '-', $string);
        }
        
        return $string;
    }

    /**
    * Returns a string without the escaping characters
    *
    * @param  $string string String to format
    * @return string
    */
    public static function show_string($string, $replace_CR = false, $chars_to_escape = array(), $databasetype = '', $escape_quote = true)
    {
        if (isset($string) && !empty($string) && is_string($string)) {
            if (isset($_SESSION['config']['databasetype']) && !empty($_SESSION['config']['databasetype'])) {
                $databasetype = $_SESSION['config']['databasetype'];
            }
            if ($databasetype == "SQLSERVER") {
                $string = str_replace("''", "'", $string);
                $string = str_replace("\\", "", $string);
            } elseif ($databasetype == "MYSQL" || $databasetype == "POSTGRESQL" && (ini_get('magic_quotes_gpc') <> true || phpversion() >= 6)) {
                $string = stripslashes($string);
                $string = str_replace("\\'", "'", $string);
                $string = str_replace('\\"', '"', $string);
            } elseif ($databasetype == "ORACLE") {
                $string = str_replace("''", "'", $string);
                $string = str_replace("\\", "", $string);
            }
            if ($replace_CR) {
                $to_del = array("\t", "\n", "&#0A;", "&#0D;", "\r");
                $string = str_replace($to_del, ' ', $string);
            }
            if (!empty($chars_to_escape) && is_array($chars_to_escape)) {
                for ($i=0;$i<count($chars_to_escape);$i++) {
                    $string = str_replace($chars_to_escape[$i], '\\'.$chars_to_escape, $string);
                }
            }

            if ($escape_quote) {
                $string = str_replace('"', "'", $string);
            }
            
            $string = trim($string);
        }
        return $string;
    }

    /**
    * Cleans html string, replacing entities by utf-8 code
    *
    * @param  $var string  String to clean
    * @return Cleaned string
    */
    public function wash_html($var, $mode="UNICODE")
    {
        if ($mode == "UNICODE") {
            $var = str_replace("<br/>", "\\n", $var);
            $var = str_replace("<br />", "\\n", $var);
            $var = str_replace("<br/>", "\\n", $var);
            $var = str_replace("&nbsp;", " ", $var);
            $var = str_replace("&eacute;", "\u00e9", $var);
            $var = str_replace("&egrave;", "\u00e8", $var);
            $var = str_replace("&ecirc;", "\00ea", $var);
            $var = str_replace("&agrave;", "\u00e0", $var);
            $var = str_replace("&acirc;", "\u00e2", $var);
            $var = str_replace("&icirc;", "\u00ee", $var);
            $var = str_replace("&ocirc;", "\u00f4", $var);
            $var = str_replace("&ucirc;", "\u00fb", $var);
            $var = str_replace("&acute;", "\u0027", $var);
            $var = str_replace("&deg;", "\u00b0", $var);
            $var = str_replace("&rsquo;", "\u2019", $var);
        } elseif ($mode == 'NO_ACCENT') {
            $var = str_replace("<br/>", "\\n", $var);
            $var = str_replace("<br />", "\\n", $var);
            $var = str_replace("<br/>", "\\n", $var);
            $var = str_replace("&nbsp;", " ", $var);
            $var = str_replace("&eacute;", "e", $var);
            $var = str_replace("&egrave;", "e", $var);
            $var = str_replace("&ecirc;", "e", $var);
            $var = str_replace("&agrave;", "a", $var);
            $var = str_replace("&acirc;", "a", $var);
            $var = str_replace("&icirc;", "i", $var);
            $var = str_replace("&ocirc;", "o", $var);
            $var = str_replace("&ucirc;", "u", $var);
            $var = str_replace("&acute;", "", $var);
            $var = str_replace("&deg;", "o", $var);
            $var = str_replace("&rsquo;", "'", $var);

            // AT LAST
            $var = str_replace("&", " et ", $var);
        } else {
            $var = str_replace("<br/>", "\\n", $var);
            $var = str_replace("<br />", "\\n", $var);
            $var = str_replace("<br/>", "\\n", $var);
            $var = str_replace("&nbsp;", " ", $var);
            $var = str_replace("&eacute;", "é", $var);
            $var = str_replace("&egrave;", "è", $var);
            $var = str_replace("&ecirc;", "ê", $var);
            $var = str_replace("&agrave;", "à", $var);
            $var = str_replace("&acirc;", "â", $var);
            $var = str_replace("&icirc;", "î", $var);
            $var = str_replace("&ocirc;", "ô", $var);
            $var = str_replace("&ucirc;", "û", $var);
            $var = str_replace("&acute;", "", $var);
            $var = str_replace("&deg;", "°", $var);
            $var = str_replace("&rsquo;", "'", $var);
        }
        return $var;
    }

    /**
    *  Compares to date
    *
    * @param  $date1 date First date
    * @param  $date2 date Second date
    * @return date1 if the first date is the greater, date2 if the second date or "equal" otherwise
    */
    public function compare_date($date1, $date2)
    {
        $date1 = strtotime($date1);
        $date2 = strtotime($date2);
        if ($date1 > $date2) {
            $result = "date1";
        } elseif ($date1 < $date2) {
            $result = "date2";
        } elseif ($date1 = $date2) {
            $result = "equal";
        }
        return $result;
    }

    /**
    *  Compares to date and return dif between 2 dates
    *
    * @param  $date1 date First date
    * @param  $date2 date Second date
    * @return dif between 2 dates in days
    */
    public function nbDaysBetween2Dates($date1, $date2)
    {
        $date1 = strtotime($date1);
        $date2 = strtotime($date2);
        if ($date2 > $date1) {
            $result = round((($date2 - $date1) / (3600)) / 24, 0);
        } elseif ($date2 < $date1) {
            $result = round((($date1 - $date2) / (3600)) / 24, 0);
        } else {
            $result = 0;
        }
        return $result;
    }

    /**
    *  Checks if a directory is empty
    *
    * @param  $dir string The directory to check
    * @return bool True if empty, False otherwise
    */
    public function isDirEmpty($dir)
    {
        $dir = opendir($dir);
        $isEmpty = true;
        while (($entry = readdir($dir)) !== false) {
            if ($entry !== '.' && $entry !== '..'  && $entry !== '.svn') {
                $isEmpty = false;
                break;
            }
        }
        closedir($dir);
        return $isEmpty;
    }
    
    /**
    * Convert an object to an array
    * @param  $object object to convert
    */
    public function object2array($object)
    {
        $return = null;
        if (is_array($object)) {
            foreach ($object as $key => $value) {
                $return[$key] = $this->object2array($value);
            }
        } else {
            if (is_object($object)) {
                $var = get_object_vars($object);
                if ($var) {
                    foreach ($var as $key => $value) {
                        $return[$key] = ($key && !$value) ? null : $this->object2array($value);
                    }
                } else {
                    return $object;
                }
            } else {
                return $object;
            }
        }
        return $return;
    }

    /**
    * Function to encode an url in base64
    */
    public function base64UrlEncode($data)
    {
        return strtr(base64_encode($data), '+/', '-_,');
    }

    /**
    * Function to decode an url encoded in base64
    */
    public function base64UrlDecode($base64)
    {
        return base64_decode(strtr($base64, '-_,', '+/'));
    }

    /**
    * Encrypt a text
    * @param $text string to encrypt
    */
    public function encrypt($sensitiveData)
    {
        $publicKeyPath = $this->getPublicKeyPath();
        if (file_exists($publicKeyPath)) {
            $pubKey = openssl_pkey_get_public('file://'.$publicKeyPath);
            if (!$pubKey) {
                return false;
            } else {
                $encryptedData = "";
                openssl_public_encrypt($sensitiveData, $encryptedData, $pubKey);
                //base 64 encode to use it in url
                return $this->base64UrlEncode($encryptedData);
            }
        } else {
            return false;
        }
    }

    /**
    * Decrypt a text
    * @param $text string to decrypt
    */
    public function decrypt($encryptedData)
    {
        $privateKeyPath = $this->getPrivateKeyPath();
        if (file_exists($privateKeyPath)) {
            $passphrase = "";
            $privateKey = openssl_pkey_get_private('file://'.$privateKeyPath, $passphrase);
            if (!$privateKey) {
                return false;
            } else {
                $decryptedData = "";
                openssl_private_decrypt($this->base64UrlDecode($encryptedData), $decryptedData, $privateKey);
                return $decryptedData;
            }
        } else {
            return false;
        }
    }

    /**
    * return the path of the private key path
    */
    public function getPrivateKeyPath()
    {
        if (file_exists($_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'config.xml')) {
            $path = $_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'config.xml';
        } else {
            $path = 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'config.xml';
        }
        $xmlconfig = simplexml_load_file($path);
        $CRYPT = $xmlconfig->CRYPT;
        return (string) $CRYPT->pathtoprivatekey;
    }

    /**
    * return the path of the public key path
    */
    public function getPublicKeyPath()
    {
        if (file_exists($_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'config.xml')) {
            $path = $_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'config.xml';
        } else {
            $path = 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'config.xml';
        }
        $xmlconfig = simplexml_load_file($path);
        $CRYPT = $xmlconfig->CRYPT;
        return $CRYPT->pathtopublickey;
    }

    /**
    * Return the file's extention of a file
    * @param  $sFullPath string path of the file
    */
    public function extractFileExt($sFullPath)
    {
        $sName = $sFullPath;
        if (strpos($sName, ".") == 0) {
            $extractFileExt = "";
        } else {
            $extractFileExt = explode(".", $sName);
        }
        if ($extractFileExt <> '') {
            return $extractFileExt[count($extractFileExt) - 1];
        }
        return '';
    }

    /**
    * Browse each file and folder in the folder and return true if the folder is not empty
    * @param  $folder path string of the folder
    */
    public function isDirNotEmpty($folder)
    {
        $foundDoc = false;
        $classScan = dir($folder);
        while (($fileScan = $classScan->read()) != false) {
            if ($fileScan == '.' || $fileScan == '..' || $fileScan == '.svn') {
                continue;
            } else {
                $foundDoc = true;
                break;
            }
        }
        return $foundDoc;
    }

    /**
    * xss mitigation functions
    * Return protected chars
    * @param  $data to encode
    * @param  $encoding ut8 by default
    */
    public static function xssafe($data, $encoding='UTF-8')
    {
        if (!is_array($data)) {
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML401, $encoding);
        } else {
            return $data;
        }
    }

    /**
    * xss mitigation functions
    * Return protected chars
    * @param  $data to encode
    */
    public static function xecho($data)
    {
        echo functions::xssafe($data);
    }
}
