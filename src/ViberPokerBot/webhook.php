<?php

namespace ViberPokerBot;

use ViberPokerBot\Lib\DotEnv;
use ViberPokerBot\Lib\Logger;
use ViberPokerBot\Lib\Storage\UserStorage;

require_once __DIR__ . '/Lib/DotEnv.php';
require_once __DIR__ . '/Lib/Storage/UserStorage.php';
require_once __DIR__ . '/Lib/Logger.php';

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
    $data = callApi('https://chatapi.viber.com/pa/get_account_info');
    $userStorage = new UserStorage();
    $userStorage->updateUsers($data->members);
    $text = 'Current users: ';
    $users = $userStorage->getUsers();
    foreach (array_values($users) as $key => $user) {
        $text .= $user->name . (!empty($users[$key + 1]) ? ', ' : '.');
    }
    var_dump($text);
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


if ($input['event'] == 'webhook') {
    $webhook_response['status'] = 0;
    $webhook_response['status_message'] = "ok";
    $webhook_response['event_types'] = 'delivered';
    echo json_encode($webhook_response);
    die;
} elseif ($input['event'] == "subscribed") {
    // when a user subscribes to the public account
} elseif ($input['event'] == "conversation_started") {
    // when a conversation is started
} elseif ($input['event'] == "message") {
    $text = $input['message']['text'] ?? '';
    $senderId = $input['sender']['id'] ?? null;
    $userStorage = new UserStorage();
    $isUserAdmin = $userStorage->isUserAdmin($senderId);
    if ($isUserAdmin) {
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
    if ($text === COMMAND_COMMANDS) {
        $commands = COMMANDS_REGULAR;
        if ($isUserAdmin) {
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

function callApi(string $url, array $data = null)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "X-Viber-Auth-Token: " . getenv('VIBER_AUTH_TOKEN')]);
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
