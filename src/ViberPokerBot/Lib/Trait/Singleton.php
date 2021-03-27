<?php

namespace ViberPokerBot\Lib\Trait;

trait Singleton
{
    private static array $instances = [];

    public static function getInstance(): object
    {
        $clasName = static::class;
        if (!isset(self::$instances[$clasName])) {
            self::$instances[$clasName] = new static();
        }

        return self::$instances[$clasName];
    }

}