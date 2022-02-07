<?php

namespace ViberPokerBot;

use ViberPokerBot\Lib\DotEnv;
use ViberPokerBot\Lib\Logger;
use ViberPokerBot\Lib\Storage\BeerStorage;
use ViberPokerBot\Lib\Storage\GamesStorage;
use ViberPokerBot\Lib\Storage\ResultStorage;
use ViberPokerBot\Lib\Storage\UserStorage;
use ViberPokerBot\Lib\ViberAPI;

require_once __DIR__ . '/Lib/DotEnv.php';
require_once __DIR__ . '/Lib/Storage/BeerStorage.php';
require_once __DIR__ . '/Lib/Storage/ResultStorage.php';
require_once __DIR__ . '/Lib/Storage/UserStorage.php';
require_once __DIR__ . '/Lib/Storage/GamesStorage.php';
require_once __DIR__ . '/Lib/Logger.php';
require_once __DIR__ . '/Lib/ViberAPI.php';

const EMPTY_AVATAR_URL = 'https://invite.viber.com/assets/g2-chat/images/generic-avatar.jpg';

const TRACK_SUBSCRIBE = 'subscribe';
const TRACK_SET = 'set';
const TRACK_PARTICIPANTS = 'set-participants';
const TRACK_BROADCAST = 'broadcast';
const TRACK_SEPARATOR_GAME_ID = '--';
const TRACK_SEPARATOR_USER_ID = '::';

const COMMAND_REFRESH_MEMBERS = 'refresh-members';
const COMMAND_BROADCAST = 'broadcast';
const COMMAND_WIN = 'win';
const COMMAND_RESULTS = 'results';
const COMMAND_RESULT = 'result';
const COMMAND_GAMES = 'games';
const COMMAND_SET = 'set';
const COMMAND_IDS = 'ids';
const COMMAND_ADMINS = 'admins';
const COMMAND_ADMIN_ADD = 'admin-add';
const COMMAND_ADMIN_REMOVE = 'admin-remove';
const COMMAND_COMMANDS = 'commands';
const COMMAND_STAT = 'stat';
const COMMAND_USERS = 'users';
const COMMAND_USERS_SUB = 'users-sub';
const COMMAND_BEER_ADD = 'beer-add';
const COMMAND_BEER_REMOVE = 'beer-remove';
const COMMAND_BEER_STATUS = 'beer-status';
const COMMAND_PARTICIPANTS_DONE = 'participants-done';
const COMMAND_ATTENDANCE = 'attendance';

const COMMANDS_ADMIN = [
    COMMAND_SET,
//    COMMAND_IDS,
//    COMMAND_REFRESH_MEMBERS,
//    COMMAND_ADMIN_ADD,
//    COMMAND_ADMIN_REMOVE,
    COMMAND_USERS_SUB,
];
const COMMANDS_REGULAR = [
//    COMMAND_COMMANDS,
    COMMAND_BEER_ADD,
    COMMAND_BEER_REMOVE,
    COMMAND_BEER_STATUS,
    COMMAND_STAT,
    COMMAND_WIN,
    COMMAND_RESULTS,
    COMMAND_GAMES,
    COMMAND_ATTENDANCE,
//    COMMAND_USERS,
//    COMMAND_ADMINS,
];

const DEFAULT_STAT_LIMIT = 5;

const POINTS = [
    1 => 5,
    2 => 3,
    3 => 2
];

const STICKER_IDS_WIN = [
    99610, 88023, 40127, 87609, 36917, 13918, 105812, 36913, 13906, 21617, 87614, 87602
];


DotEnv::load(__DIR__ . '/../../config/.env');

if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    die();
}

$request = file_get_contents("php://input");
$input = json_decode($request, true);

Logger::log(($_SERVER ? 'Server: ' . json_encode($_SERVER) . PHP_EOL : '')
    . ($_REQUEST ? 'Request: ' . json_encode($_REQUEST) . PHP_EOL : '')
    . ($_POST ? 'Post: ' . json_encode($_POST) . PHP_EOL : '')
    . ($_GET ? 'Get: ' . json_encode($_GET) . PHP_EOL : '')
    . ($request ? 'Input: ' . $request . PHP_EOL : ''));

/** @var UserStorage $userStorage */
$userStorage = UserStorage::getInstance();
/** @var ResultStorage $resultStorage */
$resultStorage = ResultStorage::getInstance();
/** @var GamesStorage $gamesStorage */
$gamesStorage = GamesStorage::getInstance();
/** @var BeerStorage $beerStorage */
$beerStorage = BeerStorage::getInstance();
$api = new ViberAPI();
if ($input['event'] === 'webhook') {
    $webhook_response['status'] = 0;
    $webhook_response['status_message'] = "ok";
    $webhook_response['event_types'] = 'delivered';
    echo json_encode($webhook_response);
    die;
} elseif ($input['event'] === "subscribed") {
    if (($user = $userStorage->getUser($input['user']['id'] ?? null)) && $user->isSubscribed) {
        die();
    }
    $newUse = new \stdClass();
    $newUse->id = $input['user']['id'] ?? null;
    $newUse->name = $input['user']['name'] ?? null;
    $newUse->avatar = $input['user']['avatar'] ?? null;
    $newUse->role = UserStorage::ROLE_USER;
    $newUse->isSubscribed = true;
    $userStorage->updateUser($newUse);
    $dataS['receiver'] = getSupperAdminId();
    $dataS['type'] = 'text';
    $dataS['text'] = "New user subscribed -  {$newUse->name}";
    $api->sendMessage($dataS);
} elseif ($input['event'] === "unsubscribed") {
    $userStorage->setSubscribe($input['user_id'], false);
    $user = $userStorage->getUser($input['user_id']);
    $dataS['receiver'] = getSupperAdminId();
    $dataS['type'] = 'text';
    $dataS['text'] = 'User unsubscribed - ' . ($user->name ?? 'no name');
    $api->sendMessage($dataS);
} elseif ($input['event'] === "conversation_started") {
    if ($user = $userStorage->getUser($input['user']['id'] ?? null)) {
        $data['receiver'] = $user->id;
        $data['sender']['name'] = 'bot';
        sendAvailableCommands($userStorage->isUserAdmin($user->id), $data);
        die();
    }
    $newUse = new \stdClass();
    $newUse->id = $input['user']['id'] ?? null;
    $newUse->name = $input['user']['name'] ?? null;
    $newUse->avatar = $input['user']['avatar'] ?? null;
    $newUse->role = UserStorage::ROLE_USER;
    $newUse->isSubscribed = false;
    $userStorage->updateUser($newUse);
    $data['receiver'] = $newUse->id;
    $data['sender']['name'] = 'bot';
    $data['text'] = "Welcome to the *Poker Uzh bot!*\n\n♠️♣️♥️♦️\n\n*Send any message* to start conversation and see list of available commands.";
    $data['type'] = 'text';
    $data['keyboard']['Type'] = 'keyboard';
//    $data['keyboard']['DefaultHeight'] = true;
    $data['keyboard']['BgColor'] = '#665CAC';
    $data['keyboard']['InputFieldState'] = 'hidden';
    $data['keyboard']['Buttons'] = [[
        "Text" => '<font color="#FFFFFF" size=”32”><b>Press</b> to start conversation!</font>',
        "TextHAlign" => "center",
        "TextVAlign" => "middle",
        "ActionType" => "reply",
        "TextSize" => "large",
        "ActionBody" => "conversation_started",
        "BgColor" => "#665CAC",
        "Rows" => 2
    ]];
    $data['tracking_data'] = TRACK_SUBSCRIBE;
    $data['min_api_version'] = 1;
    jsonResponse($data);
    die();
} elseif ($input['event'] === "message") {
    closeConnection();
    $text = lcfirst($input['message']['text'] ?? '');
    $track = $input['message']['tracking_data'] ?? '';
    $senderId = $input['sender']['id'] ?? null;
    $isAdmin = $userStorage->isUserAdmin($senderId);
    $data = [];
    $data['receiver'] = $senderId;
    $data['sender']['name'] = 'bot';
    $data['type'] = 'text';
    $data['tracking_data'] = 'tracking_data';
    $data['min_api_version'] = 1;
    if ($isAdmin) {
        if ($text === COMMAND_PARTICIPANTS_DONE) {
            $nextGameId = $resultStorage->getNextGameId();
            $gameParticipants = $gamesStorage->getUsersByGameId($nextGameId);
            if (!$gameParticipants || count($gameParticipants) < 5) {
                $resultStorage->removeResultsByGameId($nextGameId);
                $gamesStorage->removeByGameId($nextGameId);
                $data['text'] = "*Error* : minimum participants for game = 5";
                $api->sendMessage($data);
                sendAvailableCommands($isAdmin, $data);
                die();
            }
            $data['text'] = "Set 1 place.";
            $data['keyboard']['Type'] = 'keyboard';
            $data['keyboard']['InputFieldState'] = 'hidden';
            $data['keyboard']['Buttons'] = getSetButtons([], false, $nextGameId);
            $data['tracking_data'] = TRACK_SET . TRACK_SEPARATOR_GAME_ID . $nextGameId;
            $api->sendMessage($data);
            die();
        }
        if ($text === COMMAND_SET) {
            $data['text'] = "Add participants.";
            $data['keyboard']['Type'] = 'keyboard';
            $data['keyboard']['InputFieldState'] = 'hidden';
            $data['keyboard']['Buttons'] = getSetButtons([], true);
            $data['tracking_data'] = TRACK_PARTICIPANTS . TRACK_SEPARATOR_GAME_ID . $resultStorage->getNextGameId();
            $api->sendMessage($data);
            die();
        }
        if ($text === 'skip' && (str_starts_with($track, TRACK_SET) || str_starts_with($track, TRACK_PARTICIPANTS))) {
            $excludeIds = explode(TRACK_SEPARATOR_USER_ID, $track);
            $gameId = (int)(explode(TRACK_SEPARATOR_GAME_ID, $track)[1] ?? 0);
            unset($excludeIds[0]);

            if ($gameId) {
                $resultStorage->removeResultsByGameId($gameId);
                $gamesStorage->removeByGameId($gameId);
            }
            $data['text'] = 'Setting is skiped.';
            $api->sendMessage($data);
            sendAvailableCommands($isAdmin, $data);
            die();
        }
        if ($text !== 'skip' && str_starts_with($track, TRACK_PARTICIPANTS)) {
            $setId = $input['message']['text'] ?? '';
            $excludeIds = explode(TRACK_SEPARATOR_USER_ID, $track);
            $gameId = (int)(explode(TRACK_SEPARATOR_GAME_ID, $track)[1] ?? 0);
            unset($excludeIds[0]);
            $user = $userStorage->getUser($setId);
            if (!$user && $setId !== 'none') {
                $data['text'] = "*Error* : user with id {$setId} not found";
                $api->sendMessage($data);
                sendAvailableCommands($isAdmin, $data);
                die();
            }

            if ($user) {
                $result = new \stdClass();
                $result->userIds = [$user->id];
                $result->userNames = [$user->name];
                $result->adminId = $senderId;
                $result->adminName = $input['sender']['name'] ?? '';
                $result->date = time();
                $result->gameId = $gameId;
                $gamesStorage->addGame($result);
            }
            $excludeIds[] = $setId;

            $data['text'] = "Add participants.";
            $data['keyboard']['Type'] = 'keyboard';
            $data['keyboard']['InputFieldState'] = 'hidden';
            $data['keyboard']['Buttons'] = getSetButtons($excludeIds, true);
            $data['tracking_data'] = TRACK_PARTICIPANTS . TRACK_SEPARATOR_GAME_ID . $gameId . TRACK_SEPARATOR_USER_ID . implode(TRACK_SEPARATOR_USER_ID, $excludeIds);
            $api->sendMessage($data);
            die();
        }
        if ($text !== 'skip' && str_starts_with($track, TRACK_SET)) {
            $setId = $input['message']['text'] ?? '';
            $excludeIds = explode(TRACK_SEPARATOR_USER_ID, $track);
            $gameId = (int)(explode(TRACK_SEPARATOR_GAME_ID, $track)[1] ?? 0);
            unset($excludeIds[0]);
            $nextStep = count($excludeIds) + 2;
            $place = $nextStep - 1;

            if ($nextStep > 4 && ($setId === 'none' || str_contains($setId, '=='))) {
                $data['text'] = "Done!";
                $api->sendMessage($data);
                sendAvailableCommands($isAdmin, $data);
                die();
            }
            foreach ($resultStorage->getResultsByGameId($gameId) as $result) {
                if ((int)$result->place === $place) {
                    die(); //accident double click, ignore it
                }
            }
            if ($nextStep < 5 && ($setId === 'none' || str_contains($setId, '=='))) {
                $user = $userStorage->getUser($setId);
                if (!$user && $setId !== 'none') {
                    $data['text'] = "*Error* : user with id {$setId} not found";
                    $api->sendMessage($data);
                    sendAvailableCommands($isAdmin, $data);
                    die();
                }

                if ($user) {
                    $result = new \stdClass();
                    $result->userId = $user->id;
                    $result->userName = $user->name;
                    $result->place = $place;
                    $result->adminId = $senderId;
                    $result->adminName = $input['sender']['name'] ?? '';
                    $result->date = time();
                    $result->gameId = $gameId;
                    $resultStorage->addResult($result);
                }

//                if ($place === 1) {
//                    $dataB['type'] = 'sticker';
//                    $dataB['sticker_id'] = STICKER_IDS_WIN[array_rand(STICKER_IDS_WIN)];
//                    $dataB['broadcast_list'] = $userIds;
//                    $dataB['sender']['name'] = 'bot';
//                    $api->broadcastMessage($dataB);
//                    $dataF['type'] = 'text';
//                    $dataF['broadcast_list'] = $userIds;
//                    $dataF['text'] = "Game over. Congratulations to the winners!";
//                    $dataF['sender']['name'] = 'bot';
//                    $api->broadcastMessage($dataF);
//                }


//                $dataC['type'] = 'text';
//                $dataC['broadcast_list'] = $userIds;
//                $dataC['text'] = "{$place} place: " . ($user->name ?? 'none');
//                $dataC['sender']['name'] = 'bot';
//                if ($user) {
//                    $dataC['sender']['avatar'] = $user->avatar ?? EMPTY_AVATAR_URL;
//                }
//                $api->broadcastMessage($dataC);

                $excludeIds[] = $setId;
                if ($place === 3) {
                    $winners = [];
                    foreach ($excludeIds as $winnerId) {
                        $winners[] = $userStorage->getUser($winnerId);
                    }
                    $dataF['type'] = 'text';
                    $dataF['broadcast_list'] = $userStorage->getUserIds();
                    $dataF['sender']['name'] = 'bot';
                    $dataF['text'] = "Game over. Congratulations to the winners!" . PHP_EOL;
                    foreach ($winners as $key => $winner) {
                        $dataF['text'] .= ($key + 1) . ' place: ' . ($winner->name ?? 'none') . PHP_EOL;
                    }
                    $api->broadcastMessage($dataF);
                    sendAvailableCommands($isAdmin, $data);
                    die();
                }

                $data['text'] = "Set {$nextStep} place.";
                $data['keyboard']['Type'] = 'keyboard';
                $data['keyboard']['InputFieldState'] = 'hidden';
                $data['keyboard']['Buttons'] = getSetButtons($excludeIds, false, $gameId);
                $data['tracking_data'] = TRACK_SET . TRACK_SEPARATOR_GAME_ID . $gameId . TRACK_SEPARATOR_USER_ID . implode(TRACK_SEPARATOR_USER_ID, $excludeIds);
                $api->sendMessage($data);
                die();
            }
        }
        if ($text === COMMAND_REFRESH_MEMBERS) {
            $dataApp = $api->getAccountInfo();

            $userStorage->updateUsers($dataApp->members);
            $text = 'Current members: ';
            foreach ($dataApp->members as $key => $user) {
                $text .= $user->name . (!empty($dataApp->members[$key + 1]) ? ', ' : '.');
            }
            $data['text'] = $text;
            $api->sendMessage($data);
            sendAvailableCommands($isAdmin, $data);
            die();
        }
        if ($text === COMMAND_IDS) {
            if (!isSupperAdmin($senderId)) {
                $data['text'] = '*Error* : this command allowed only for superadmins';
                $api->sendMessage($data);
                die();
            }
            $data['text'] = 'User ids: ';
            $api->sendMessage($data);
            foreach ($userStorage->getAll() as $key => $user) {
                $data['text'] = $user->name . ' ' . $user->id;
                $api->sendMessage($data);
            }
            sendAvailableCommands($isAdmin, $data);
            die();
        }
        if (str_starts_with($text, COMMAND_ADMIN_ADD) || str_starts_with($text, COMMAND_ADMIN_REMOVE)) {
            if (!isSupperAdmin($senderId)) {
                $data['text'] = '*Error* : this command allowed only for superadmins';
                $api->sendMessage($data);
                die();
            }
            $id = explode(':', $text)[1] ?? null;
            if (!$id) {
                $data['text'] = 'User ids: ';
                $api->sendMessage($data);
                foreach ($userStorage->getAll() as $key => $user) {
                    $data['text'] = $user->name . ' admin-' . (str_starts_with($text, COMMAND_ADMIN_ADD) ? 'add:' : 'remove:') . $user->id;
                    $api->sendMessage($data);
                }
                die();
            }
            $res = $userStorage->setRole($id, str_starts_with($text, COMMAND_ADMIN_ADD) ? UserStorage::ROLE_ADMIN : UserStorage::ROLE_USER);
            if ($res) {
                $adminName = $input['sender']['name'] ?? 'Admin';
                $data['text'] = $adminName . (str_starts_with($text, COMMAND_ADMIN_ADD) ? ' give you admin permissions.' : ' remove your admin permissions.');
                $data['receiver'] = $id;
                $api->sendMessage($data);
                $data['receiver'] = $senderId;
                $text = 'Done. Current admins: ';
                $admins = [];
                foreach ($userStorage->getAll() as $user) {
                    if ($user->role === UserStorage::ROLE_ADMIN) {
                        $admins[] = $user;
                    }
                }
                foreach ($admins as $key => $admin) {
                    $text .= $admin->name . (!empty($admins[$key + 1]) ? ', ' : '.');
                }
            } else {
                $text = 'Not found user with id: ' . $id;
            }
            $data['text'] = $text;
            $api->sendMessage($data);
            die();
        }
        if ($text === COMMAND_USERS_SUB) {
            $text = 'Users: ';
            $users = $userStorage->getSubscribedUsers();
            foreach ($users as $key => $user) {
                $text .= $user->name . (!empty($users[$key + 1]) ? ', ' : '.');
            }
            $data['text'] = $text;
            $api->sendMessage($data);
            sendAvailableCommands($isAdmin, $data);
            die();
        }
        if ($text === COMMAND_BROADCAST) {
            if (!isSupperAdmin($senderId)) {
                $data['text'] = '*Error* : this command allowed only for superadmins';
                $api->sendMessage($data);
                die();
            }
            $data['text'] = "Send broadcast message";
            $data['tracking_data'] = TRACK_BROADCAST;
            $api->sendMessage($data);
            die();
        }
        if ($track === TRACK_BROADCAST) {
            $data['text'] = $input['message']['text'];
            $data['broadcast_list'] = $userStorage->getUserIds();
            $api->broadcastMessage($data);
            sendAvailableCommands($isAdmin, $data);
            die();
        }
    }
    if ($track === TRACK_SUBSCRIBE) {
        if (($user = $userStorage->getUser($input['sender']['id'] ?? null)) && $user->isSubscribed) {
            $data['receiver'] = $user->id;
            $data['sender']['name'] = 'bot';
            sendAvailableCommands($userStorage->isUserAdmin($user->id), $data);
            die();
        }
        $newUse = new \stdClass();
        $newUse->id = $input['sender']['id'] ?? null;
        $newUse->name = $input['sender']['name'] ?? null;
        $newUse->avatar = $input['sender']['avatar'] ?? null;
        $newUse->role = UserStorage::ROLE_USER;
        $newUse->isSubscribed = true;
        $userStorage->updateUser($newUse);
        $dataS['receiver'] = getSupperAdminId();
        $dataS['type'] = 'text';
        $dataS['text'] = 'New user subscribed - ' . $newUse->name;
        $api->sendMessage($dataS);
    }
    if ($text === COMMAND_BEER_ADD) {
        $user = $userStorage->getUser($senderId);
        if (!$user) {
            die();
        }
        $newBeer = new \stdClass();
        $newBeer->userId = $user->id;
        $newBeer->userName = $user->name;
        $newBeer->date = time();
        $newBeer->gameId = $resultStorage->getNextGameId();
        $beerStorage->addBeer($newBeer);
        $data['text'] = 'Your current beers status is - ' . $beerStorage->getBeerStatus($user->id);
        $api->sendMessage($data);
        sendAvailableCommands($isAdmin, $data);
        die();
    }
    if ($text === COMMAND_BEER_REMOVE) {
        $beerStorage->removeBeer($senderId);
        $data['text'] = 'Your current beers status is - ' . $beerStorage->getBeerStatus($senderId);
        $api->sendMessage($data);
        sendAvailableCommands($isAdmin, $data);
        die();
    }
    if ($text === COMMAND_BEER_STATUS) {
        $data['text'] = 'Your current beers status is - ' . $beerStorage->getBeerStatus($senderId);
        $api->sendMessage($data);
        sendAvailableCommands($isAdmin, $data);
        die();
    }
    if ($text === COMMAND_ADMINS) {
        $text = 'Admins: ';
        $admins = [];
        foreach ($userStorage->getAll() as $user) {
            if ($user->role === UserStorage::ROLE_ADMIN) {
                $admins[] = $user;
            }
        }
        foreach ($admins as $key => $admin) {
            $text .= $admin->name . (!empty($admins[$key + 1]) ? ', ' : '.');
        }
        $data['text'] = $text;
        $api->sendMessage($data);
        sendAvailableCommands($isAdmin, $data);
        die();
    }
    if ($text === COMMAND_USERS) {
        $text = 'Users: ';
        $users = $userStorage->getAll();
        foreach ($users as $key => $user) {
            $text .= $user->name . (!empty($users[$key + 1]) ? ', ' : '.');
        }
        $data['text'] = $text;
        $api->sendMessage($data);
        sendAvailableCommands($isAdmin, $data);
        die();
    }
    if ($text === COMMAND_STAT) {
        $results = [];
        foreach ($resultStorage->getAll() as $result) {
            if (!empty($results[$result->userId]['score'])) {
                $results[$result->userId]['score'] += POINTS[(int)$result->place];
                continue;
            }
            $results[$result->userId]['score'] = POINTS[(int)$result->place];
            $results[$result->userId]['userId'] = $result->userId;
        }

        if (!$results) {
            $data['text'] = 'Stat is empty yet.';
            $api->sendMessage($data);
        }

        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        foreach ($results as $result) {
            $user = $userStorage->getUser($result['userId']);
            if (!$user) {
                continue;
            }
            $data['sender']['avatar'] = $user->avatar ?? EMPTY_AVATAR_URL;
            $data['text'] = $user->name . ' - ' . $result['score'] . ' points.';
            $api->sendMessage($data);
        }
        sendAvailableCommands($isAdmin, $data);
        die();
    }
    if ($text === COMMAND_WIN) {
        $results = [];
        foreach ($resultStorage->getAll() as $result) {
            if ($result->place !== 1) {
                continue;
            }
            if (!empty($results[$result->userId]['score'])) {
                $results[$result->userId]['score'] += 1;
                continue;
            }
            $results[$result->userId]['score'] = 1;
            $results[$result->userId]['userId'] = $result->userId;
        }

        if (!$results) {
            $data['text'] = 'Stat is empty yet.';
            $api->sendMessage($data);
        }

        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        foreach ($results as $result) {
            $user = $userStorage->getUser($result['userId']);
            if (!$user) {
                continue;
            }
            $data['sender']['avatar'] = $user->avatar ?? EMPTY_AVATAR_URL;
            $data['text'] = $user->name . ' won - ' . $result['score'] . ($result['score'] === 1 ? ' time.' : ' times.');
            $api->sendMessage($data);
        }
        sendAvailableCommands($isAdmin, $data);
        die();
    }
    if ($text === COMMAND_RESULTS || $text === COMMAND_RESULT) {
        $results = [];
        foreach ($resultStorage->getAll() as $result) {
            if (!empty($results[$result->userId][$result->place])) {
                $results[$result->userId][$result->place] += 1;
                continue;
            }
            $results[$result->userId][$result->place] = 1;
            $results[$result->userId]['userId'] = $result->userId;
        }

        if (!$results) {
            $data['text'] = 'Stat is empty yet.';
            $api->sendMessage($data);
        }

        usort($results, function ($a, $b) {
            return ($b[1] ?? 0) <=> ($a[1] ?? 0);
        });

        foreach ($results as $result) {
            $user = $userStorage->getUser($result['userId']);
            if (!$user) {
                continue;
            }
            $data['sender']['avatar'] = $user->avatar ?? EMPTY_AVATAR_URL;
            $data['text'] = $user->name . "\n 1 place: " . ($result[1] ?? 0) . "\n 2 place: " . ($result[2] ?? 0) . "\n 3 place: " . ($result[3] ?? 0);
            $api->sendMessage($data);
        }
        sendAvailableCommands($isAdmin, $data);
        die();
    }
    if ($text === COMMAND_GAMES) {
        $games = [];
        foreach ($resultStorage->getAll() as $result) {
            if (!empty($games[$result->gameId])) {
                $games[$result->gameId][$result->place] = $result->userName;
                continue;
            }
            $games[$result->gameId][$result->place] = $result->userName;
            $games[$result->gameId]['gameId'] = $result->gameId;
            $games[$result->gameId]['date'] = $result->date;
        }
        if (!$games) {
            $data['text'] = 'Stat is empty yet.';
            $api->sendMessage($data);
        }

        usort($games, function ($a, $b) {
            return $b['gameId'] <=> $a['gameId'];
        });

        $games = array_slice($games,0, DEFAULT_STAT_LIMIT);
        foreach ($games as $game) {
            $data['text'] = 'Game ' . $game['gameId'] . ' ' . date("Y-m-d H:i", (int)$game['date']) . "\n 1 place: " . ($game[1] ?? '') . "\n 2 place: " . ($game[2] ?? '') . "\n 3 place: " . ($game[3] ?? '');
            $api->sendMessage($data);
        }
        sendAvailableCommands($isAdmin, $data);
        die();
    }

    if ($text === COMMAND_ATTENDANCE) {
        $games = $gamesStorage->getAll();
        $stat = [];
        $totalGamesCount = count($games);
        foreach ($games as $game) {
            foreach ($userStorage->getAll() as $key => $user) {
                if (!array_key_exists($user->id, $stat)) {
                    $stat[$user->id]['games'] = 0;
                    $stat[$user->id]['avatar'] = $user->avatar;
                    $stat[$user->id]['name'] = $user->name;
                }

                if (in_array($user->id, $game->userIds)) {
                    $stat[$user->id]['games']++;
                }
            }
        }
        if (!$stat) {
            $data['text'] = 'Stat is empty yet.';
            $api->sendMessage($data);
        }
        usort($stat, function ($a, $b) {
            return $b['games'] <=> $a['games'];
        });

        foreach ($stat as $user) {
            $data['sender']['avatar'] = $user['avatar'] ?? EMPTY_AVATAR_URL;
            $data['text'] = $user['name'] . ' - ' . round($user['games']/$totalGamesCount*100, 2) . '% (' . $user['games'] . ' / ' . $totalGamesCount . ' total)';
            $api->sendMessage($data);
        }

        sendAvailableCommands($isAdmin, $data);
        die();
    }

    sendAvailableCommands($isAdmin, $data);

    die();
}

function sendAvailableCommands($isAdmin, $data)
{
    $commands = COMMANDS_REGULAR;
    if ($isAdmin) {
        $commands = array_merge($commands, COMMANDS_ADMIN);
    }

    $data['text'] = 'Full stat see in ' . getenv('WEB_PAGE_URL');
    $data['keyboard']['Type'] = 'keyboard';
    $data['keyboard']['InputFieldState'] = 'hidden';
    $data['keyboard']['Buttons'] = getCommandButtons($commands);
    $api = new ViberAPI();
    $api->sendMessage($data);
}

function jsonResponse(array $data)
{
    header('Content-Type: application/json');
    header('X-Viber-Auth-Token: ' . getenv('VIBER_AUTH_TOKEN'));
    echo json_encode($data);
}

function isSupperAdmin(string $id = null): bool
{
    $api = new ViberAPI();
    $dataApp = $api->getAccountInfo();
    foreach ($dataApp->members as $key => $user) {
        if ($user->id === $id) {
            return true;
        }
    }
    return false;
}

function getSupperAdminId(): ?string
{
    $api = new ViberAPI();
    $dataApp = $api->getAccountInfo();

    return $dataApp->members[0]->id ?? null;
}

function getSetButtons(array $excludeIds = [], bool $isParticipants = false, $gameId = null): array
{
    $buttons = [];
    $gamesStorage = GamesStorage::getInstance();
    $users = $gamesStorage->getUsersByGameId($gameId);
    if ($excludeIds) {
        $users = array_filter($users, function ($element) use ($excludeIds) {
            return !in_array($element->id, $excludeIds, true);
        });
    }
    $count = count($users) + 2;
    usort($users, function ($a, $b) {
        return $a->name <=> $b->name;
    });

    $buttonSkip = [
        "Text" => "<font color='#FFFFFF' size='22'>Skip setting</font>",
        "TextHAlign" => "center",
        "TextVAlign" => "middle",
        "ActionType" => "reply",
        "TextSize" => "large",
        "ActionBody" => 'skip',
        "BgColor" => "#665CAC",
        "Columns" => 6
    ];
    $buttonName = $isParticipants ? "That's all" : "Don't remember";
    $buttonNone = [
        "Text" => "<font color='#FFFFFF' size='22'>" . $buttonName . "</font>",
        "TextHAlign" => "center",
        "TextVAlign" => "middle",
        "ActionType" => "reply",
        "TextSize" => "large",
        "ActionBody" => $isParticipants ? COMMAND_PARTICIPANTS_DONE : 'none',
        "BgColor" => "#665CAC",
        "Columns" => 6
    ];
    if ($count > 24) {
        $buttonNone['Columns'] = $buttonSkip['Columns'] = 3;
    } elseif ($count > 48) {
        $buttonNone['Columns'] = $buttonSkip['Columns'] = 2;
    } elseif ($count > 72) {
        $buttonNone['Columns'] = $buttonSkip['Columns'] = 1;
    }

    $buttons[] = $buttonSkip;
    $buttons[] = $buttonNone;

    foreach ($users as $user) {
        $buttonImage = [
            "Text" => "",
            "TextHAlign" => "center",
            "TextVAlign" => "middle",
            "ActionType" => "reply",
            "TextSize" => "large",
            "ActionBody" => $user->id,
            "BgColor" => "#665CAC",
            "Image" => $user->avatar ?? EMPTY_AVATAR_URL,
            "Columns" => 1
        ];
        $button = [
            "Text" => "<font color='#FFFFFF' size='22'>{$user->name}</font>",
            "TextHAlign" => "center",
            "TextVAlign" => "middle",
            "ActionType" => "reply",
            "TextSize" => "large",
            "ActionBody" => $user->id,
            "BgColor" => "#665CAC",
            "Columns" => 5
        ];
        if ($count > 24) {
            $button['Columns'] = 2;
        }
        if ($count > 48) {
            $button['Columns'] = 1;
        }
        if ($count > 72) {
            $buttons[] = $buttonImage;
            continue;
        }

        $buttons[] = $buttonImage;
        $buttons[] = $button;
    }

    return $buttons;
}

function getCommandButtons(array $commands): array
{
    $buttons = [];
    $count = count($commands);

    foreach ($commands as $command) {
        $color = "#665CAC";
        if ($command === COMMAND_BEER_ADD) {
            $color = "#54C0D4";
        }
        if ($command === COMMAND_BEER_REMOVE) {
            $color = "#EF6062";
        }
        if ($command === COMMAND_BEER_STATUS) {
            $color = "#F4EF7B";
        }
        $button = [
            "Text" => "<font color='#FFFFFF' size='22'>{$command}</font>",
            "TextHAlign" => "center",
            "TextVAlign" => "middle",
            "ActionType" => "reply",
            "TextSize" => "large",
            "ActionBody" => $command,
            "BgColor" => $color,
            "Columns" => $command === COMMAND_BEER_STATUS ? 6 : 3
        ];
//        if ($count > 24) {
//            $button['Columns'] = 3;
//        }
        if ($count > 48) {
            $button['Columns'] = 2;
        }
        if ($count > 72) {
            $button['Columns'] = 1;
        }
        $buttons[] = $button;
    }

    return $buttons;
}

function closeConnection()
{
    set_time_limit(0);
    ob_start();
    echo ' ';
    header('Connection: close');
    header('Content-Length: '.ob_get_length());
    ob_end_flush();
    flush();
}

//"subscribed",
//"unsubscribed",
//"webhook",
//"conversation_started",
//"client_status",
//"action",
//"delivered",
//"failed",
//"message",
//"seen"
