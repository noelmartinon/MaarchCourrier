<?php

namespace CMIS\Models;

interface OutputStrategyInterface
{
    /**
     * @param $webroot
     * @return mixed
     */
    public function webroot($webroot);

    /**
     * @return mixed
     */
    public function repository();

    /**
     * @param [CMISObject] $objects
     * @param $succinct
     * @return mixed
     */
    public function id($objects, $succinct, $selector);

    /**
     * @return mixed
     */
    public function createFolder();

    /**
     * @return mixed
     */
    public function createDocument();

    /**
     * @return mixed
     */
    public function query();

    /**
     * @return mixed
     */
    public function capabilities();

    /**
     * @return mixed
     */
    public function generate();

    /**
     * @return mixed
     */
    public function render();

    /**
     * @return mixed
     */
    public function validate();

    /**
     * @param $conf
     * @return mixed
     */
    public function loadConfiguration($conf);

    /**
     * @return mixed
     */
    public function getObjects();

    /**
     * @param $id
     * @return mixed
     */
    public function descendants($id);

    /**
     * @param $type
     * @return mixed
     */
    public function renderType($type);

}