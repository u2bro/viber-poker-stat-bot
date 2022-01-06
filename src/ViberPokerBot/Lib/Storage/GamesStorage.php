<?php

namespace ViberPokerBot\Lib\Storage;

require_once 'Storage.php';

class GamesStorage extends Storage
{

    //Game fields
    //{
    //"userIds": data,
    //"userNames": data,
    //"adminId": data,
    //"adminName": data,
    //"date": data,
    //"gameId": data
    //}

    protected function getFilePath(): string
    {
        return static::STORAGE_PATH . 'games.json';
    }

    public function addGame(object $newResult): bool
    {
        if (!$newResult->gameId) {
            return false;
        }
        $results = $this->getAll();
        $lastResults = end($results);
        if ($lastResults->gameId === $newResult->gameId) {
            array_pop($results);
            $newResult->userIds = array_merge($lastResults->userIds, $newResult->userIds);
            $newResult->userNames = array_merge($lastResults->userNames, $newResult->userNames);
        }
        $results[] = $newResult;

        $this->storeDataToFile($results);

        return true;
    }

    protected function storeDataToFile(array $results): void
    {
        file_put_contents($this->getFilePath(), json_encode($results, JSON_THROW_ON_ERROR));
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

    public function removeByGameId(int $gameId)
    {
        $results = [];
        foreach ($this->getAll() as $result) {
            if ((int)$result->gameId !== $gameId) {
                $results[] = $result;
            }
        }

        $this->storeDataToFile($results);
    }
}