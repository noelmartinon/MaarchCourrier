<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

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
     * @param $id
     * @param [CMISObject] $objects
     * @param $succinct
     * @param $selector
     * @return mixed
     *
     */
    public function id($id, $objects, $succinct, $selector);

    /**
     * @return mixed
     */
    public function query($objects);

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
     * @param $objects
     * @param int $maxItems
     * @param int $skipCount
     * @return mixed
     */
    public function descendants($objects, $maxItems, $skipCount);

    /**
     * @param $objects
     * @param int $maxItems
     * @param int $skipCount
     * @return mixed
     */
    public function children($objects, $maxItems, $skipCount);

    /**
     * @param $type
     * @return mixed
     */
    public function renderType($type);

}