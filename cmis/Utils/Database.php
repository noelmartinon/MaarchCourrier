<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace CMIS\Utils;


class Database
{
    private $_connection;
    /** @var  \PDO */
    private static $_instance;

    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    private function __construct()
    {

        switch ($_SESSION['cmis_databasetype']) {
            case 'POSTGRESQL';
                $driver = 'pgsql';
                break;
            case 'ORACLE';
                $driver = 'oci';
                break;
            case 'MYSQL';
                $driver = 'mysql';
                break;
            default:
                $driver = 'pgsql';
        }

        try {
            $this->_connection = new \PDO($driver . ':dbname=' . $_SESSION['cmis_databasename'] . ';host=' . $_SESSION['cmis_databaseserver'],
                $_SESSION['cmis_databaseuser'],
                $_SESSION['cmis_databasepassword'], [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ]);

            $this->_connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
        }
    }

    private function __clone()
    {
    }

    public function getConnection()
    {
        return $this->_connection;
    }

    public function query($req, $data = [])
    {
        $stmt = $this->_connection->prepare($req);
        $stmt->execute($data);
        return $stmt;
    }

    public function exec($req, $data = [])
    {
        $stmt = $this->_connection->prepare($req);
        $stmt->execute($data);
    }

    public static function getOtherPropertiesArray($result)
    {
        $otherProperties = [];

        foreach ($result as $key => $val) {


            if (preg_match('/_id/', $key)) {
                $type = 'Id';
            } else if (preg_match('/date/', $key)) {
                $type = 'DateTime';
            } else if (preg_match('/custom_d/', $key)) {
                $type = 'DateTime';
            } else if (preg_match('/custom_n/', $key)) {
                $type = 'Id';
            } else if (preg_match('/custom_f/', $key)) {
                $type = 'Id';
            } else if (preg_match('/_level/', $key)) {
                $type = 'Id';
            } else {
                $type = 'String';
            }

            $otherProperties[$key] = [
                "type" => $type,
                "value" => $val
            ];
        }

        return $otherProperties;

    }

    public function lastInsertId($sequenceName)
    {

        switch ($_SESSION['cmis_databasetype']) {
            case 'POSTGRESQL';
                return self::$_instance->query('SELECT lastval();')->fetch()[0];
                break;
            case 'ORACLE';
                return self::$_instance->query('SELECT  ' . $sequenceName . '.currval as lastinsertid FROM dual')->fetch()[0];
                break;
            case 'MYSQL';
                return self::$_instance->lastInsertId();
                break;
            default:
                return self::$_instance->lastInsertId();
        }

    }

}