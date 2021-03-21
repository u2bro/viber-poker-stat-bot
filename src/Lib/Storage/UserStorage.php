<?php

namespace PokerBot\Lib\Storage;

class UserStorage extends Storage
{
    protected function getFilePath(): string
    {
        return static::STORAGE_PATH . 'users.csv';
    }

    public function getUsers()
    {
        $users = [];
        if (($handle = fopen($this->getFilePath(), 'rb')) !== FALSE) {
            while (($user = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $users[] = $user;
            }
            fclose($handle);
        }

        return $users;
    }

    public function addUser($newUser)
    {
        $users = [];
        $exist = false;
        if (($handle = fopen($this->getFilePath(), 'rab')) !== FALSE) {
            while (($user = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($newUser[0] === $user[0]) {
                    $exist = true;
                    break;
                }
                $users[] = $user;
            }
            if (!$exist) {
                $users[] = $newUser;
                fputcsv($handle, $users);
            }
            fclose($handle);
        }

        return $users;
    }


    public function updateUsers($newUsers)
    {
        $users = [];
        if (($handle = fopen($this->getFilePath(), 'rab')) !== FALSE) {
            while (($user = fgetcsv($handle, 1000, ",")) !== FALSE) {
                foreach ($newUsers as $key => $newUser) {
                    if ($newUser[0] === $user[0]) {
                        unset($newUsers[$key]);
                        break;
                    }
                }

                $users[] = $user;
            }
            if ($newUsers) {
                $users[] = array_merge($users, $newUsers);
                fputcsv($handle, $users);
            }
            fclose($handle);
        }

        return $users;
    }

}