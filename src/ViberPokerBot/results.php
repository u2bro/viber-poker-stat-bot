<?php

namespace PokerBot;

use ViberPokerBot\Lib\Storage\ResultStorage;

require_once __DIR__ . '/Lib/Storage/ResultStorage.php';


$html = '<table>';
$html .= '<tr>';
$html .= '<th>Name</th>';
$html .= '<th>Place</th>';
$html .= '<th>Time</th>';
$html .= '<th>Who added</th>';
$html .= '<th>Game №</th>';
$html .= '</tr>';
$resultStorage = ResultStorage::getInstance();
foreach ($resultStorage->getResults() as $result) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($result->userName) . '</td>';
    $html .= '<td>' . htmlspecialchars($result->place) . '</td>';
    $html .= '<td>' . htmlspecialchars(date("Y-m-d H:i", (int)$result->date)) . '</td>';
    $html .= '<td>' . htmlspecialchars($result->adminName) . '</td>';
    $html .= '<td>' . htmlspecialchars($result->gameId) . '</td>';
    $html .= '</tr>';
}
$html .= '</table>';
echo $html;
die();