<?php

namespace ViberPokerBot\Lib\Trait;

trait Singleton
{
    private static ?object $instance = null;

    public static function getInstance(): object
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

}