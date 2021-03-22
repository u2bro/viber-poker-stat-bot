<?php

namespace ViberPokerBot;

use ViberPokerBot\Lib\DotEnv;
use ViberPokerBot\Lib\Logger;
use ViberPokerBot\Lib\Storage\UserStorage;

require_once __DIR__ . '/Lib/DotEnv.php';
require_once __DIR__ . '/Lib/Storage/UserStorage.php';
require_once __DIR__ . '/Lib/Logger.php';

const TRACK_SUBSCRIBE = 'subscribe';

const COMMAND_REFRESH_USERS = '/refresh_users';
const COMMAND_COMMANDS = '/commands';
const COMMANDS_ADMIN = [
    COMMAND_REFRESH_USERS,
];
const COMMANDS_REGULAR = [
    COMMAND_COMMANDS,
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
if ($input['event'] == 'webhook') {
    $webhook_response['status'] = 0;
    $webhook_response['status_message'] = "ok";
    $webhook_response['event_types'] = 'delivered';
    echo json_encode($webhook_response);
    die;
} elseif ($input['event'] == "subscribed") {
    $newUse = new \stdClass();
    $newUse->id = $input['user']['id'] ?? null;
    $newUse->name = $input['user']['name'] ?? null;
    $newUse->avatar = $input['user']['avatar'] ?? null;
    $newUse->role = 'user';
    $newUse->isSubscribed = true;
    $userStorage->updateUser($newUse);
} elseif ($input['event'] == "unsubscribed") {
    $userStorage->setSubscribe($input['user_id'], false);
} elseif ($input['event'] == "conversation_started") {
    $newUse = new \stdClass();
    $newUse->id = $input['user']['id'] ?? null;
    $newUse->name = $input['user']['name'] ?? null;
    $newUse->avatar = $input['user']['avatar'] ?? null;
    $newUse->role = 'user';
    $newUse->isSubscribed = false;
    $userStorage->updateUser($newUse);
    $data['receiver'] = $newUse->id;
    $data['sender']['name'] = 'bot';
    $data['text'] = "Welcome to the Poker Uzh bot!\n\n*Send any message* to start conversation and see list of available commands.";
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
} elseif ($input['event'] == "message") {
    $text = $input['message']['text'] ?? '';
    $track = $input['message']['tracking_data'] ?? '';
    $senderId = $input['sender']['id'] ?? null;
    $isAdmin = $userStorage->isUserAdmin($senderId);
    if ($isAdmin) {
        if ($text === COMMAND_REFRESH_USERS) {
            $dataApp = callApi('https://chatapi.viber.com/pa/get_account_info');

            $userStorage->updateUsers($dataApp->members);
            $text = 'Current users: ';
            $users = $userStorage->getUsers();
            foreach ($users as $key => $user) {
                $text .= $user->name . (!empty($users[$key + 1]) ? ', ' : '.');
            }
        }
    }
    if ($track === TRACK_SUBSCRIBE) {
        $newUse = new \stdClass();
        $newUse->id = $input['sender']['id'] ?? null;
        $newUse->name = $input['sender']['name'] ?? null;
        $newUse->avatar = $input['sender']['avatar'] ?? null;
        $newUse->role = 'user';
        $newUse->isSubscribed = true;
        $userStorage->updateUser($newUse);
    }

    if ($text === $input['message']['text'] ?? '') {
        $commands = COMMANDS_REGULAR;
        if ($isAdmin) {
            $commands = array_merge($commands, COMMANDS_ADMIN);
        }
        $text = 'Available commands: ';
        foreach ($commands as $key => $command) {
            $text .= $command . (!empty($commands[$key + 1]) ? ', ' : '.');
        }
    }

    /* when a user message is received */
    $type = $input['message']['type'];
    $senderName = $input['sender']['name'];

    $data['receiver'] = $senderId;
    $data['sender']['name'] = 'bot';
    $data['text'] = $text;
    $data['type'] = 'text';
    $data['tracking_data'] = 'tracking_data';
    $data['min_api_version'] = 1;

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
