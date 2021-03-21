<?php

namespace PokerBot;

use PokerBot\Lib\Storage\UserStorage;

require_once __DIR__ . '/Lib/Storage/UserStorage.php';


$html = '<table>';
$html .= '<tr>';
$html .= '<th>Name</th>';
$html .= '<th>Role</th>';
$html .= '</tr>';
$userStorage = new UserStorage();
foreach ($userStorage->getUsers() as $user) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($user->name) . '</td>';
    $html .= '<td>' . htmlspecialchars($user->role) . '</td>';
    $html .= '</tr>';
}
$html .= '</table>';
echo $html;
die();