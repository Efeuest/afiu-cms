<?php

declare(strict_types=1);

use AfiuCMS\Core\Application;
use AfiuCMS\Core\Config;
use AfiuCMS\Core\Container;
use AfiuCMS\Core\Database;
use AfiuCMS\Core\ErrorHandler;
use AfiuCMS\Core\MigrationRunner;
use AfiuCMS\Core\Routing\Router;
use AfiuCMS\Core\Session;
use AfiuCMS\Core\ThemeManager;
use AfiuCMS\Core\Middleware\EnsureInstalled;
use AfiuCMS\Core\Middleware\SecurityHeaders;
use AfiuCMS\Core\Middleware\VerifyCsrfToken;
use Dotenv\Dotenv;

define('AFIU_START', microtime(true));
define('AFIU_ROOT', dirname(__DIR__));

require AFIU_ROOT . '/vendor/autoload.php';
Dotenv::createImmutable(AFIU_ROOT)->safeLoad();

$config = new Config();
$config->loadDirectory(AFIU_ROOT . '/config');
date_default_timezone_set((string) $config->get('app.timezone', 'UTC'));

ErrorHandler::register((bool) $config->get('app.debug', false), (string) $config->get('paths.logs') . '/afiu.log');
Session::start($config, (string) $config->get('paths.sessions'));

$container = new Container();
$container->instance(Config::class, $config);
$container->singleton(Database::class, fn (Container $c) => new Database($c->make(Config::class)));

// Alpha updater: installed sites automatically apply pending additive migrations.
// This keeps v0.1.x installations upgradeable without wiping user data.
if ((bool) $config->get('app.installed', false)) {
    (new MigrationRunner($container->make(Database::class), $config))->run();

    // Theme assets are published as static public files for reliable, cacheable delivery.
    $themeManager = $container->make(ThemeManager::class);
    $themeManager->ensurePublished($themeManager->activeSlug());
}

$router = new Router($container);
$container->instance(Router::class, $router);
require AFIU_ROOT . '/routes/web.php';

$app = new Application($container, $router);
$app->middleware(SecurityHeaders::class);
$app->middleware(EnsureInstalled::class);
$app->middleware(VerifyCsrfToken::class);

return $app;
