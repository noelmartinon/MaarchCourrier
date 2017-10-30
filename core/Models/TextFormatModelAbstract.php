<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Text Format Model Abstract
 * @author dev@maarch.org
 * @ingroup core
 */

namespace Core\Models;

class TextFormatModelAbstract
{
    public static function normalize(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['string']);
        ValidatorModel::stringType($aArgs, ['string']);

        $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
        $b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr';

        $string = utf8_decode($aArgs['string']);
        $string = strtr($string, utf8_decode($a), $b);
        $string = strtolower($string);

        return utf8_encode($string);
    }

    public static function format_date($date)
    {
        $last_date = '';

        if($date != '') {
            if(strpos($date, " ")) {
                $date_ex = explode(" ", $date);
                $the_date = explode("-", $date_ex[0]);
                $last_date = $the_date[2] . "-" . $the_date[1] . "-" .$the_date[0];
            } else {
                $the_date = explode("-", $date);
                $last_date = $the_date[2] ."-" . $the_date[1] . "-" . $the_date[0];
            }
        }

        return $last_date;
    }

    public static function htmlWasher($html)
    {
        $html = str_replace("<br/>", "\\n", $html);
        $html = str_replace("<br />", "\\n", $html);
        $html = str_replace("<br/>", "\\n", $html);
        $html = str_replace("&nbsp;", " ", $html);
        $html = str_replace("&eacute;", "\u00e9", $html);
        $html = str_replace("&egrave;", "\u00e8", $html);
        $html = str_replace("&ecirc;", "\00ea", $html);
        $html = str_replace("&agrave;", "\u00e0", $html);
        $html = str_replace("&acirc;", "\u00e2", $html);
        $html = str_replace("&icirc;", "\u00ee", $html);
        $html = str_replace("&ocirc;", "\u00f4", $html);
        $html = str_replace("&ucirc;", "\u00fb", $html);
        $html = str_replace("&acute;", "\u0027", $html);
        $html = str_replace("&deg;", "\u00b0", $html);
        $html = str_replace("&rsquo;", "\u2019", $html);

        return $html;
    }
}
