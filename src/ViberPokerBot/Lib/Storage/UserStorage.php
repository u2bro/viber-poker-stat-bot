<?php

namespace ViberPokerBot\Lib\Storage;

require_once 'Storage.php';

class UserStorage extends Storage
{
    protected function getFilePath(): string
    {
        return static::STORAGE_PATH . 'users.json';
    }

    protected function storeUsersToFile(array $users): void
    {
        file_put_contents($this->getFilePath(), json_encode($users, JSON_THROW_ON_ERROR));
    }

    public function getUsers()
    {
        return (array)@json_decode(file_get_contents($this->getFilePath(), true));
    }

    public function addUser($newUser)
    {
        if (empty($newUser->id)) {
            return false;
        }
        $users = $this->getUsers();
        foreach ($users as $key => $user) {
            if ($newUser->id === $user->id) {
                //user already exist and isSubscribed is equal
                if (($user->isSubscribed ?? null) === $newUser->isSubscribed || ($user->isSubscribed ?? null)) {
                    return false;
                }

                unset($users[$key]);
                break;
            }
        }
        $users[] = $newUser;
        $this->storeUsersToFile($users);

        return true;
    }


    public function updateUsers($newUsers, bool $isSubscribed = true)
    {
        $users = $this->getUsers();
        foreach ($users as $user) {
            foreach ($newUsers as $key => $newUser) {
                $newUser->isSubscribed = $isSubscribed;
                if ($newUser->id === $user->id) {
                    unset($users[$key]);
                    if (($user->isSubscribed ?? null) === $newUser->isSubscribed) {
                        unset($newUsers[$key]);
                    } else {
                        unset($users[$key]);
                    }
                }
            }
            if (!$newUsers) {
                break;
            }
        }
        if (!$newUsers) {
            return false;
        }

        $users = array_merge($users, $newUsers);
        $this->storeUsersToFile($users);

        return true;
    }

    public function isUserAdmin($id)
    {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user->id === $id) {
                return $user->role === 'admin';
            }
        }

        return false;
    }
}