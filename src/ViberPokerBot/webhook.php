<?php

namespace ViberPokerBot;

use ViberPokerBot\Lib\DotEnv;
use ViberPokerBot\Lib\Logger;
use ViberPokerBot\Lib\Storage\ResultStorage;
use ViberPokerBot\Lib\Storage\UserStorage;

require_once __DIR__ . '/Lib/DotEnv.php';
require_once __DIR__ . '/Lib/Storage/ResultStorage.php';
require_once __DIR__ . '/Lib/Storage/UserStorage.php';
require_once __DIR__ . '/Lib/Logger.php';

const TRACK_SUBSCRIBE = 'subscribe';
const TRACK_SET = 'set';
const TRACK_BROADCAST = 'broadcast';

const COMMAND_REFRESH_MEMBERS = 'refresh-members';
const COMMAND_BROADCAST = 'broadcast';
const COMMAND_SET = 'set';
const COMMAND_IDS = 'ids';
const COMMAND_ADMINS = 'admins';
const COMMAND_ADMIN_ADD = 'admin-add';
const COMMAND_ADMIN_REMOVE = 'admin-remove';
const COMMAND_COMMANDS = 'commands';
const COMMAND_STAT = 'stat';
const COMMAND_USERS = 'users';
const COMMAND_USERS_SUB = 'users-sub';
const COMMANDS_ADMIN = [
    COMMAND_SET,
    COMMAND_IDS,
    COMMAND_REFRESH_MEMBERS,
    COMMAND_ADMIN_ADD,
    COMMAND_ADMIN_REMOVE,
    COMMAND_USERS_SUB
];
const COMMANDS_REGULAR = [
    COMMAND_COMMANDS,
    COMMAND_ADMINS,
    COMMAND_USERS,
    COMMAND_STAT,
];


DotEnv::load(__DIR__ . '/../../config/.env');

if (strpos($_SERVER['HTTP_USER_AGENT'], 'akka-http') !== 0) {
    $userStorage = new UserStorage();
    $r = $userStorage->setSubscribe('tmDjqEPjuuBVmaKm9+hr\/A==', false);
    var_dump($r);
    var_dump('die');
    die();
}

$request = file_get_contents("php://input");
$input = json_decode($request, true);

Logger::log(($_SERVER ? 'Server: ' . json_encode($_SERVER) . PHP_EOL : '')
    . ($_REQUEST ? 'Request: ' . json_encode($_REQUEST) . PHP_EOL : '')
    . ($_POST ? 'Post: ' . json_encode($_POST) . PHP_EOL : '')
    . ($_GET ? 'Get: ' . json_encode($_GET) . PHP_EOL : '')
    . ($request ? 'Input: ' . $request . PHP_EOL : ''));

$userStorage = new UserStorage();
$resultStorage = new ResultStorage();
if ($input['event'] === 'webhook') {
    $webhook_response['status'] = 0;
    $webhook_response['status_message'] = "ok";
    $webhook_response['event_types'] = 'delivered';
    echo json_encode($webhook_response);
    die;
} elseif ($input['event'] === "subscribed") {
    $newUse = new \stdClass();
    $newUse->id = $input['user']['id'] ?? null;
    $newUse->name = $input['user']['name'] ?? null;
    $newUse->avatar = $input['user']['avatar'] ?? null;
    $newUse->role = UserStorage::ROLE_ADMIN;
    $newUse->isSubscribed = true;
    $userStorage->updateUser($newUse);
} elseif ($input['event'] === "unsubscribed") {
    $userStorage->setSubscribe($input['user_id'], false);
} elseif ($input['event'] === "conversation_started") {
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
        if ($text === COMMAND_SET) {
            $data['text'] = "Set 1 place.";
            $data['type'] = 'text';
            $data['keyboard']['Type'] = 'keyboard';
            $data['keyboard']['InputFieldState'] = 'hidden';
            $buttons = getSetButtons($userStorage);
            $data['keyboard']['Buttons'] = $buttons;
            $data['tracking_data'] = TRACK_SET;
            callApi('https://chatapi.viber.com/pa/send_message', $data);
            die();
        }
        if (strpos($track, TRACK_SET) === 0) {
            $setId = $input['message']['text'] ?? '';
            $excludeIds = explode(':', $track);
            unset($excludeIds[0]);
            $nextStep = count($excludeIds) + 2;
            if ($nextStep > 4 && ($setId === 'none' || strpos($setId, '==') !== false)) {
                $data['text'] = "Done!";
                $data['type'] = 'text';
                callApi('https://chatapi.viber.com/pa/send_message', $data);
                die();
            }
            if ($nextStep < 5 && ($setId === 'none' || strpos($setId, '==') !== false)) {
                $user = $userStorage->getUser($setId);
                if (!$user && $setId !== 'none') {
                    $data['text'] = "*Error* : user with id {$setId} not found";
                    callApi('https://chatapi.viber.com/pa/send_message', $data);
                    die();
                }
                $step = $nextStep - 1;

                if ($user) {
                    $result = new \stdClass();
                    $result->userId = $user->id;
                    $result->userName = $user->name;
                    $result->place = $step;
                    $result->adminId = $senderId;
                    $result->adminName = $input['sender']['name'] ?? '';
                    $result->date = time();
                    $resultStorage->addResult($result);
                }
                $userIds = $userStorage->getUserIds();
                if (($key = array_search($senderId, $userIds)) !== false) {
                    unset($userIds[$key]);
                }
                $userIds = array_values($userIds);

                if ($step === 1) {
                    $dataB['type'] = 'sticker';
                    $dataB['sticker_id'] = 99610;
                    $dataB['broadcast_list'] = $userIds;
                    $dataB['sender']['name'] = 'bot';
                    callApi('https://chatapi.viber.com/pa/broadcast_message', $dataB);
                    $dataF['type'] = 'text';
                    $dataF['broadcast_list'] = $userIds;
                    $dataF['text'] = "Game over, congratulations champions!";
                    $dataF['sender']['name'] = 'bot';
                    callApi('https://chatapi.viber.com/pa/broadcast_message', $dataF);
                }


                $dataC['type'] = 'text';
                $dataC['broadcast_list'] = $userIds;
                $dataC['text'] = "{$step} place: " . ($user->name ?? 'none');
                $dataC['sender']['name'] = 'bot';
                if ($user) {
                    $dataC['sender']['avatar'] = $user->avatar;
                }
                callApi('https://chatapi.viber.com/pa/broadcast_message', $dataC);


                if ($step === 3) {
                    $data['text'] = "Done!";
                    $data['type'] = 'text';
                    callApi('https://chatapi.viber.com/pa/send_message', $data);
                    die();
                }

                $data['text'] = "Set {$nextStep} place.";
                $data['keyboard']['Type'] = 'keyboard';
                $data['keyboard']['InputFieldState'] = 'hidden';
                $excludeIds[] = $setId;
                $buttons = getSetButtons($userStorage, $excludeIds);
                $data['keyboard']['Buttons'] = $buttons;
                $data['tracking_data'] = TRACK_SET . ':' . implode(':', $excludeIds);
                callApi('https://chatapi.viber.com/pa/send_message', $data);
                die();
            }
        }
        if ($text === COMMAND_REFRESH_MEMBERS) {
            $dataApp = callApi('https://chatapi.viber.com/pa/get_account_info');

            $userStorage->updateUsers($dataApp->members);
            $text = 'Current members: ';
            foreach ($dataApp->members as $key => $user) {
                $text .= $user->name . (!empty($dataApp->members[$key + 1]) ? ', ' : '.');
            }
        }
        if ($text === COMMAND_IDS) {
            if (!isSupperAdmin($senderId)) {
                $data['text'] = '*Error* : this command allowed only for superadmins';
                callApi('https://chatapi.viber.com/pa/send_message', $data);
                die();
            }
            $data['text'] = 'User ids: ';
            callApi('https://chatapi.viber.com/pa/send_message', $data);
            foreach ($userStorage->getUsers() as $key => $user) {
                $data['text'] = $user->name . ' ' . $user->id;
                callApi('https://chatapi.viber.com/pa/send_message', $data);
            }
            die();
        }
        if (strpos($text, COMMAND_ADMIN_ADD) === 0 || strpos($text, COMMAND_ADMIN_REMOVE) === 0) {
            if (!isSupperAdmin($senderId)) {
                $data['text'] = '*Error* : this command allowed only for superadmins';
                callApi('https://chatapi.viber.com/pa/send_message', $data);
                die();
            }
            $id = explode(':', $text)[1] ?? null;
            if (!$id) {
                $data['text'] = 'User ids: ';
                callApi('https://chatapi.viber.com/pa/send_message', $data);
                foreach ($userStorage->getUsers() as $key => $user) {
                    $data['text'] = $user->name . ' admin-' . (strpos($text, COMMAND_ADMIN_ADD) === 0 ? 'add:' : 'remove:') . $user->id;
                    callApi('https://chatapi.viber.com/pa/send_message', $data);
                }
                die();
            }
            $res = $userStorage->setRole($id, strpos($text, COMMAND_ADMIN_ADD) === 0 ? UserStorage::ROLE_ADMIN : UserStorage::ROLE_USER);
            if ($res) {
                $text = 'Done. Current admins: ';
                $admins = [];
                foreach ($userStorage->getUsers() as $user) {
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
        }
        if ($text === COMMAND_USERS_SUB) {
            $text = 'Users: ';
            $users = $userStorage->getSubscribedUsers();
            foreach ($users as $key => $user) {
                $text .= $user->name . (!empty($users[$key + 1]) ? ', ' : '.');
            }
        }
        if ($text === COMMAND_BROADCAST) {
            if (!isSupperAdmin($senderId)) {
                $data['text'] = '*Error* : this command allowed only for superadmins';
                callApi('https://chatapi.viber.com/pa/send_message', $data);
                die();
            }
            $data['text'] = "Send broadcast message";
            $data['type'] = 'text';
            $data['tracking_data'] = TRACK_BROADCAST;
            callApi('https://chatapi.viber.com/pa/send_message', $data);
            die();
        }
        if ($track === TRACK_BROADCAST) {
            $data['text'] = $input['message']['text'];
            $data['type'] = 'text';
            $data['broadcast_list'] = $userStorage->getUserIds();
            callApi('https://chatapi.viber.com/pa/broadcast_message', $data);
            die();
        }
    }
    if ($text === COMMAND_ADMINS) {
        $text = 'Admins: ';
        $admins = [];
        foreach ($userStorage->getUsers() as $user) {
            if ($user->role === UserStorage::ROLE_ADMIN) {
                $admins[] = $user;
            }
        }
        foreach ($admins as $key => $admin) {
            $text .= $admin->name . (!empty($admins[$key + 1]) ? ', ' : '.');
        }
    }
    if ($text === COMMAND_USERS) {
        $text = 'Users: ';
        $users = $userStorage->getUsers();
        foreach ($users as $key => $user) {
            $text .= $user->name . (!empty($users[$key + 1]) ? ', ' : '.');
        }
    }
    if ($text === COMMAND_STAT) {
        $data['text'] = 'Full stat: ';
        callApi('https://chatapi.viber.com/pa/send_message', $data);
        $results = [];
        foreach ($resultStorage->getResults() as $result) {
            if (!empty($results[$result->userId]['score'])) {
                $results[$result->userId]['score'] += 4 - (int)$result->place;
                continue;
            }
            $results[$result->userId]['score'] = 4 - (int)$result->place;
        }

        foreach ($results as $key => $result) {
            $user = $userStorage->getUser($key);
            if (!$user) {
                continue;
            }
            $data['sender']['avatar'] = $user->avatar;
            $data['text'] = $user->name . ' - ' . $result['score'] . ' points.';
            callApi('https://chatapi.viber.com/pa/send_message', $data);
        }
        die();
    }
    if ($track === TRACK_SUBSCRIBE) {
        $newUse = new \stdClass();
        $newUse->id = $input['sender']['id'] ?? null;
        $newUse->name = $input['sender']['name'] ?? null;
        $newUse->avatar = $input['sender']['avatar'] ?? null;
        $newUse->role = UserStorage::ROLE_USER;
        $newUse->isSubscribed = true;
        $userStorage->updateUser($newUse);
    }

    if ($text === lcfirst($input['message']['text'] ?? '')) {
        $commands = COMMANDS_REGULAR;
        if ($isAdmin) {
            $commands = array_merge($commands, COMMANDS_ADMIN);
        }
        $text = 'Available commands: ';
        foreach ($commands as $key => $command) {
            $text .= $command . (!empty($commands[$key + 1]) ? ', ' : '.');
        }
    }

    $data['text'] = $text;

    callApi('https://chatapi.viber.com/pa/send_message', $data);
}

function jsonResponse(array $data)
{
    header('Content-Type: application/json');
    header('X-Viber-Auth-Token: ' . getenv('VIBER_AUTH_TOKEN'));
    echo json_encode($data);
}

function callApi(string $url, array $data = null)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'X-Viber-Auth-Token: ' . getenv('VIBER_AUTH_TOKEN')]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        Logger::log(': Error Resp: ' . json_encode(curl_getinfo($ch)));
    }

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    return json_decode($response);
}

function isSupperAdmin(string $id = null): bool
{
    $dataApp = callApi('https://chatapi.viber.com/pa/get_account_info');
    foreach ($dataApp->members as $key => $user) {
        if ($user->id === $id) {
            return true;
        }
    }
    return false;
}

function getSetButtons(UserStorage $userStorage, array $excludeIds = []): array
{
    $buttons = [];
    $users = $userStorage->getUsers();
    if ($excludeIds) {
        $users = array_filter($users, function ($element) use ($excludeIds) {
            return !in_array($element->id, $excludeIds, true);
        });
    }
    $count = count($users) + 1;
    usort($users, function ($a, $b) {
        return $a->name <=> $b->name;
    });

    $buttonNone = [
        "Text" => "<font color='#FFFFFF' size='32'>None</font>",
        "TextHAlign" => "center",
        "TextVAlign" => "middle",
        "ActionType" => "reply",
        "TextSize" => "large",
        "ActionBody" => 'none',
        "BgColor" => "#665CAC",
        "Columns" => 6
    ];
    if ($count > 24) {
        $buttonNone['Columns'] = 3;
    } elseif ($count > 48) {
        $buttonNone['Columns'] = 2;
    } elseif ($count > 72) {
        $buttonNone['Columns'] = 1;
    }
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
            "Image" => $user->avatar,
            "Columns" => 1
        ];
        $button = [
            "Text" => "<font color='#FFFFFF' size='32'>{$user->name}</font>",
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
