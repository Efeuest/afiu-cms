<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$config = require AFIU_ROOT . '/config/app.php';

echo '<h1>' . htmlspecialchars($config['name']) . '</h1>';
echo '<p>Version: ' . htmlspecialchars($config['version']) . '</p>';
echo '<p>AfiuCMS core is running.</p>';