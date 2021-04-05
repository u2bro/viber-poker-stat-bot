<?php

namespace ViberPokerBot\Lib\Storage;

require_once 'Storage.php';

class ResultStorage extends Storage
{

    //Result fields
    //{
    //"userId": data,
    //"userName": data,
    //"place": data,
    //"adminId": data,
    //"adminName": data,
    //"date": data,
    //"gameId": data
    //}

    protected function getFilePath(): string
    {
        return static::STORAGE_PATH . 'result.json';
    }

    public function addResult(object $newResult): bool
    {
        $results = $this->getAll();
        $results[] = $newResult;
        $this->storeDataToFile($results);

        return true;
    }

    protected function storeDataToFile(array $results): void
    {
        file_put_contents($this->getFilePath(), json_encode($results, JSON_THROW_ON_ERROR));
    }

    public function getNextGameId(): int
    {
        $nextGameId = 0;
        foreach ($this->getAll() as $result) {
            if ((int)$result->gameId > $nextGameId) {
                $nextGameId = (int)$result->gameId;
            }
        }

        return ++$nextGameId;
    }

    public function getResultsByGameId(int $gameId): array
    {
        $results = [];
        foreach ($this->getAll() as $result) {
            if ((int)$result->gameId === $gameId) {
                $results[] = $result;
            }
        }
        return $results;
    }
}