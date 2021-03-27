<?php

namespace ViberPokerBot\Lib\Storage;

use ViberPokerBot\Lib\Trait\Singleton;

require_once  __DIR__  . '/../Trait/Singleton.php';

abstract class Storage
{
    use Singleton;

    protected function __construct() { }

    protected const STORAGE_PATH = __DIR__ . '/../../../../data/';

    abstract protected function getFilePath():string;
}