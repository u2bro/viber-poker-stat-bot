<?php

namespace ViberPokerBot\Lib;

class Logger
{
    protected const LOG_PATH = __DIR__ . '/../../../log/';

    public static function log(string $data, string $scope = 'webhook'): void
    {
        $path = static::LOG_PATH . $scope . '.log';
        $fp = fopen($path, 'ab');
        fwrite($fp, date('y.m.d H:m:s:') . $data . PHP_EOL);
        fclose($fp);
    }
}