<?php

namespace ViberPokerBot\Lib\Storage;
use ViberPokerBot\Lib\Storage\UserStorage;

require_once 'Storage.php';
require_once 'UserStorage.php';

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

    public function getGameByGameId(int $gameId)
    {
        foreach ($this->getAll() as $result) {
            if ((int)$result->gameId === $gameId) {
                return $result;
            }
        }
        return null;
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

    public function getUsersByGameId(int $gameId = null): array
    {
        /** @var UserStorage $userStorage */
        $userStorage = UserStorage::getInstance();
        $allUsers = $userStorage->getAll();
        if ($gameId === null) {
            return $allUsers;
        }
        $game = $this->getGameByGameId($gameId);
        if (!$game) {
            return [];
        }

        $userIds = $game->userIds;
        return array_filter($allUsers, function ($element) use ($userIds) {
            return in_array($element->id, $userIds, true);
        });
    }
}