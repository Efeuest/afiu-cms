<?php

declare(strict_types=1);

use AfiuCMS\Controllers\Admin\ContentController;
use AfiuCMS\Controllers\Admin\DashboardController;
use AfiuCMS\Controllers\Admin\MediaController;
use AfiuCMS\Controllers\Admin\SettingsController;
use AfiuCMS\Controllers\Admin\ThemeController;
use AfiuCMS\Controllers\AssetController;
use AfiuCMS\Controllers\AuthController;
use AfiuCMS\Controllers\InstallController;
use AfiuCMS\Controllers\SiteController;
use AfiuCMS\Core\Config;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Middleware\Authenticate;
use AfiuCMS\Core\Middleware\GuestOnly;
use AfiuCMS\Core\Routing\Router;

/** @var Router $router */
/** @var Config $config */

$router->get('/health', static fn (): Response => Response::json([
    'status' => 'ok',
    'application' => $config->get('app.name'),
    'version' => $config->get('app.version'),
    'installed' => $config->get('app.installed'),
    'timestamp' => date(DATE_ATOM),
]));

$router->get('/install', [InstallController::class, 'show']);
$router->post('/install', [InstallController::class, 'install']);

$router->get('/admin/login', [AuthController::class, 'showLogin'], [GuestOnly::class]);
$router->post('/admin/login', [AuthController::class, 'login'], [GuestOnly::class]);
$router->post('/admin/logout', [AuthController::class, 'logout'], [Authenticate::class]);

$auth = [Authenticate::class];
$router->get('/admin', [DashboardController::class, 'index'], $auth);
$router->get('/admin/pages', [ContentController::class, 'pages'], $auth);
$router->get('/admin/pages/create', [ContentController::class, 'createPage'], $auth);
$router->post('/admin/pages', [ContentController::class, 'storePage'], $auth);
$router->get('/admin/pages/{id}/edit', [ContentController::class, 'editPage'], $auth);
$router->post('/admin/pages/{id}', [ContentController::class, 'updatePage'], $auth);
$router->post('/admin/pages/{id}/delete', [ContentController::class, 'deletePage'], $auth);
$router->get('/admin/posts', [ContentController::class, 'posts'], $auth);
$router->get('/admin/posts/create', [ContentController::class, 'createPost'], $auth);
$router->post('/admin/posts', [ContentController::class, 'storePost'], $auth);
$router->get('/admin/posts/{id}/edit', [ContentController::class, 'editPost'], $auth);
$router->post('/admin/posts/{id}', [ContentController::class, 'updatePost'], $auth);
$router->post('/admin/posts/{id}/delete', [ContentController::class, 'deletePost'], $auth);
$router->get('/admin/media', [MediaController::class, 'index'], $auth);
$router->post('/admin/media', [MediaController::class, 'upload'], $auth);
$router->post('/admin/media/{id}/delete', [MediaController::class, 'delete'], $auth);
$router->get('/admin/themes', [ThemeController::class, 'index'], $auth);
$router->post('/admin/themes/upload', [ThemeController::class, 'upload'], $auth);
$router->post('/admin/themes/{slug}/activate', [ThemeController::class, 'activate'], $auth);
$router->get('/admin/settings', [SettingsController::class, 'index'], $auth);
$router->post('/admin/settings', [SettingsController::class, 'update'], $auth);

$router->get('/theme-assets/{theme}/{path*}', [AssetController::class, 'theme']);
$router->get('/media/{id}', [AssetController::class, 'media']);
$router->get('/', [SiteController::class, 'home']);
$router->get('/blog', [SiteController::class, 'blog']);
$router->get('/blog/{slug}', [SiteController::class, 'post']);
$router->get('/{slug}', [SiteController::class, 'page']);
