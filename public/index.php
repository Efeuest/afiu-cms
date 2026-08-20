<?php

declare(strict_types=1);

use AfiuCMS\Core\Application;

/** @var Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->run();
