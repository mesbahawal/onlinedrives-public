<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
 */

namespace humhub\modules\onlinedrives\models;

/**
 *
 * @author luke
 */
interface ItemInterface
{

    public function getItemId();

    public function getTitle();

    public function getSize();

    public function getCreator();

    public function getUrl();

    public function getFullUrl();

    public function getEditUrl();
}
