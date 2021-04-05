<?php

namespace ViberPokerBot\Lib\Storage;

require_once 'Storage.php';

class BeerStorage extends Storage
{

    //Beer fields
    //{
    //"userId": data,
    //"userName": data,
    //"date": data,
    //"gameId": data
    //}

    protected function getFilePath(): string
    {
        return static::STORAGE_PATH . 'beer.json';
    }

    public function addBeer(object $newBeer): bool
    {
        $beers = $this->getAll();
        $beers[] = $newBeer;
        $this->storeDataToFile($beers);

        return true;
    }

    public function removeBeer($userId)
    {
        $dateLimit = strtotime("-12 hours");
        $beers = $this->getAll();
        foreach ($beers as $key => $beer) {
            if ($beer->userId === $userId && $beer->date > $dateLimit) {
                unset($beers[$key]);
                $this->storeDataToFile($beers);
                return true;
            }
        }
        return false;
    }

    public function getBeerStatus($userId): int
    {
        $currentStatus = 0;
        $dateLimit = strtotime("-12 hours");
        foreach ($this->getAll() as $beer) {
            if ($beer->userId === $userId && $beer->date > $dateLimit) {
                $currentStatus++;
            }
        }
        return $currentStatus;
    }
}