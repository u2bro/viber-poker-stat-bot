<?php

use ViberPokerBot\Lib\Storage\ResultStorage;
use ViberPokerBot\Lib\Storage\UserStorage;
use ViberPokerBot\Lib\DotEnv;

require_once __DIR__ . '/ViberPokerBot/Lib/Storage/ResultStorage.php';
require_once __DIR__ . '/ViberPokerBot/Lib/Storage/UserStorage.php';
require_once __DIR__ . '/ViberPokerBot/Lib/DotEnv.php';

//const EMPTY_AVATAR_URL = 'https://www.viber.com/app/uploads/s3.jpg';
const EMPTY_AVATAR_URL = 'https://invite.viber.com/assets/g2-chat/images/generic-avatar.jpg';

const POINTS = [
    1 => 5,
    2 => 3,
    3 => 2
];

DotEnv::load(__DIR__ . '/../config/.env');

$resultStorage = ResultStorage::getInstance();
$userStorage = UserStorage::getInstance();
$stat = $personResults = [];
foreach ($resultStorage->getAll() as $result) {
    if (!empty($stat[$result->userId]['score'])) {
        $stat[$result->userId]['score'] += POINTS[(int)$result->place];
    } else {
        $stat[$result->userId]['score'] = POINTS[(int)$result->place];
        $stat[$result->userId]['user'] = $userStorage->getUser($result->userId);
    }
    if (!empty($personResults[$result->userId][$result->place])) {
        $personResults[$result->userId][$result->place] += 1;
    } else {
        $personResults[$result->userId][$result->place] = 1;
        $personResults[$result->userId]['user'] = $userStorage->getUser($result->userId);
    }
}

usort($stat, function ($a, $b) {
    return $b['score'] <=> $a['score'];
});
$stat = array_values($stat);

usort($personResults, function ($a, $b) {
    return ($b[1] ?? 0) <=> ($a[1] ?? 0);
});
$personResults = array_values($personResults);

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

usort($games, function ($a, $b) {
    return $b['gameId'] <=> $a['gameId'];
});

header("X-Robots-Tag: noindex, nofollow", true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Poker Uzh</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.2/css/bulma.min.css">
    <meta name="robots" content="noindex">
    <meta name="googlebot" content="noindex">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Poker Uzh">
    <meta property="og:title" content="Poker Uzh">
    <meta property="og:description" content="Poker Uzh statistics">
    <meta property="og:url" content="https://<?php echo getenv('WEB_PAGE_URL') ?>">
    <meta property="og:locale" content="en_US">
    <meta property="og:image" content="https://dl-media.viber.com/1/share/2/long/vibes/icon/image/0x0/a925/a9836e54625056f9616c448b66fb75a919fcae6fc9011e02d1b3ee607bf1a925.jpg">
    <meta property="og:image:width" content="500">
    <meta property="og:image:height" content="500">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Poker Uzh">
    <meta name="twitter:description" content="Poker Uzh statistics">
    <meta name="twitter:image:src" content="https://dl-media.viber.com/1/share/2/long/vibes/icon/image/0x0/a925/a9836e54625056f9616c448b66fb75a919fcae6fc9011e02d1b3ee607bf1a925.jpg">
    <meta name="twitter:url" content="https://<?php echo getenv('WEB_PAGE_URL') ?>">
    <meta name="twitter:domain" content="<?php echo getenv('WEB_PAGE_URL') ?>">
</head>
<body>

<!--<nav class="navbar is-spaced is-light" role="navigation" aria-label="main navigation">-->
<!--    <div class="navbar-menu is-active">-->
<!--        <div class="navbar-start">-->
<!--            <a class="navbar-item" href="/">-->
<!--                Home-->
<!--            </a>-->
<!--        </div>-->
<!--    </div>-->
<!--</nav>-->
<section class="section">
    <div class="container is-fullhd">
        <div class="tile is-ancestor">
            <div class="tile is-parent is-4">
                <div class="tile is-child box">
                    <div class="content">
                        <p class="title">Statistics</p>
                        <div class="content">
                            <table class="table is-striped">
                                <thead>
                                <tr>
                                    <th><abbr title="Position">Pos</abbr></th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th><abbr title="Points">Pts</abbr></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($stat as $key => $result) { ?>
                                    <tr>
                                        <th><?php echo $key + 1 ?></th>
                                        <td>
                                            <figure class="image is-32x32">
                                                <img class="is-rounded" src="<?php echo ($result['user']->avatar ?? EMPTY_AVATAR_URL) ?>" alt="<?php echo  $result['user']->name ?>">
                                            </figure>
                                        </td>
                                        <td><?php echo  $result['user']->name ?></td>
                                        <td><?php echo  $result['score'] ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tile is-8 is-parent is-flex-wrap-wrap">
                <div class="tile is-12 is-parent">
                    <p class="title">Personal results</p>
                </div>
                <?php foreach ($personResults as $personResult) { ?>
                    <div class="tile is-3 is-parent">
                        <div class="card">
                            <div class="card-content">
                                <div class="media" style="align-items: center;">
                                    <div class="media-left">
                                        <figure class="image is-48x48">
                                            <img class="is-rounded" src="<?php echo  ($personResult['user']->avatar ?? EMPTY_AVATAR_URL) ?>" alt="<?php echo  $personResult['user']->name ?>">
                                        </figure>
                                    </div>
                                    <div class="media-content">
                                        <p class="title is-5"><?php echo  $personResult['user']->name ?></p>
                                    </div>
                                </div>

                                <div class="content">
                                    <ul>
                                        <li>First place: <?php echo $personResult[1] ?? 0?></li>
                                        <li>Second place: <?php echo $personResult[2] ?? 0?></li>
                                        <li>Third place: <?php echo $personResult[3] ?? 0?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="tile is-ancestor">
            <div class="tile is-12 is-parent is-flex-wrap-wrap">
                <div class="tile is-12 is-parent">
                    <p class="title">Games</p>
                </div>
                <?php foreach ($games as $game) { ?>
                    <div class="tile is-3 is-parent">
                        <div class="card">
                            <div class="card-content">
                                <div class="media" style="align-items: center;">
                                    <div class="media-content">
                                        <p class="title is-5"><?php echo  'Game ' . $game['gameId'] . ' ' . date("Y-m-d H:i", (int)$game['date']) ?></p>
                                    </div>
                                </div>

                                <div class="content">
                                    <ul>
                                        <li>First place: <?php echo ($game[1] ?? '') ?></li>
                                        <li>Second place: <?php echo ($game[2] ?? '') ?></li>
                                        <li>Third place: <?php echo ($game[3] ?? '') ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

</body>
</html>
