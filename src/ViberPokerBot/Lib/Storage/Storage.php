<?php

namespace ViberPokerBot\Lib\Storage;

abstract class Storage
{
    protected const STORAGE_PATH = __DIR__ . '/../../../data/';

    abstract protected function getFilePath():string;
}