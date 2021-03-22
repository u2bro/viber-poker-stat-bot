<?php

use ViberPokerBot\Lib\DotEnv;

require_once __DIR__ . '/Lib/DotEnv.php';

DotEnv::load(__DIR__ . '/../../config/.env');

$fp = fopen('webhook.log', 'ab');
fwrite($fp, date('y.m.d H:m:s:')
    . ($_SERVER ? 'Server: ' . json_encode($_SERVER) . PHP_EOL : '')
    . ($_REQUEST ? 'Request: ' . json_encode($_REQUEST) . PHP_EOL : '')
    . ($_POST ? 'Post: ' . json_encode($_POST) . PHP_EOL : '')
    . ($_GET ? 'Get: ' . json_encode($_GET) . PHP_EOL : '')
    . PHP_EOL
);
fclose($fp);


$url = 'https://chatapi.viber.com/pa/set_webhook';
$jsonData = '{ "auth_token": "' . getenv('VIBER_AUTH_TOKEN') . '", "url": "https://coronamatter.com/ViberPokerBot/webhook.php" }';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$result = curl_exec($ch);
curl_close($ch);