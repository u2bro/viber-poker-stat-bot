<?php

namespace ViberPokerBot\Lib\Storage;

require_once 'Storage.php';

class ResultStorage extends Storage
{
    protected function getFilePath(): string
    {
        return static::STORAGE_PATH . 'result.json';
    }

    public function addResult(object $newResult): bool
    {
        $results = $this->getResults();
        $results[] = $newResult;
        $this->storeResultsToFile($results);

        return true;
    }

    protected function storeResultsToFile(array $results): void
    {
        file_put_contents($this->getFilePath(), json_encode($results, JSON_THROW_ON_ERROR));
    }

    public function getResults(): array
    {
        return array_values((array)@json_decode(file_get_contents($this->getFilePath(), true)));
    }
}