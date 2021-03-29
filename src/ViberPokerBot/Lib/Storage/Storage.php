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

    public function getAll(): array
    {
        return array_values((array)@json_decode(file_get_contents($this->getFilePath(), true)));
    }
}