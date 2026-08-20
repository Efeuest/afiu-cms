<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers;

use AfiuCMS\Core\Config;
use AfiuCMS\Core\Database;
use AfiuCMS\Core\EnvWriter;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\MigrationRunner;
use AfiuCMS\Core\View;
use RuntimeException;
use Throwable;

final class InstallController
{
    public function __construct(
        private readonly Config $config,
        private readonly View $view,
        private readonly EnvWriter $envWriter
    ) {}

    public function show(): Response
    {
        return Response::html(
            $this->view->render('install.index', [
                'error' => null,
                'values' => [],
            ])
        );
    }

    public function install(Request $request): Response
    {
        $values = $request->all();
        $siteName = trim((string) $request->input('site_name'));
        $adminName = trim((string) $request->input('admin_name'));
        $email = strtolower(trim((string) $request->input('admin_email')));
        $password = (string) $request->input('admin_password');

        $db = [
            'host' => trim((string) $request->input('db_host', '127.0.0.1')),
            'port' => (int) $request->input('db_port', 3306),
            'database' => trim((string) $request->input('db_database')),
            'username' => trim((string) $request->input('db_username')),
            'password' => (string) $request->input('db_password'),
        ];

        $errors = [];
        if ($siteName === '') $errors[] = 'Site name is required.';
        if ($adminName === '') $errors[] = 'Administrator name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid administrator email is required.';
        if (strlen($password) < 10) $errors[] = 'Administrator password must contain at least 10 characters.';
        if ($db['host'] === '' || $db['database'] === '' || $db['username'] === '') $errors[] = 'Database host, name and username are required.';
        if ($db['port'] < 1 || $db['port'] > 65535) $errors[] = 'Database port is invalid.';

        if ($errors !== []) {
            return Response::html(
                $this->view->render('install.index', [
                    'error' => implode(' ', $errors),
                    'values' => $values,
                ]),
                422
            );
        }

        try {
            Database::testAndCreate($db);

            // Build an in-memory installation config. We deliberately do not
            // write APP_INSTALLED=false to .env before migrations complete.
            // Failed installations therefore cannot corrupt a previously
            // usable environment file.
            $installConfig = new Config();
            $installConfig->loadDirectory(AFIU_ROOT . '/config');
            $installConfig->set('database.host', $db['host']);
            $installConfig->set('database.port', $db['port']);
            $installConfig->set('database.database', $db['database']);
            $installConfig->set('database.username', $db['username']);
            $installConfig->set('database.password', $db['password']);

            $database = new Database($installConfig);
            $runner = new MigrationRunner($database, $installConfig);
            $runner->run();

            // Seed the first administrator and baseline settings without an
            // outer transaction. The installer must remain recoverable after
            // a partially completed installation, including MySQL environments
            // that implicitly end transactions around schema operations.
            $userCountRow = $database->one('SELECT COUNT(*) AS total FROM users');
            $userCount = (int) ($userCountRow['total'] ?? 0);

            if ($userCount === 0) {
                $database->execute(
                    'INSERT INTO users '
                    . '(name, email, password_hash, role, status) '
                    . 'VALUES (?, ?, ?, ?, ?)',
                    [
                        $adminName,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        'administrator',
                        'active',
                    ]
                );
            } else {
                $existingAdmin = $database->one(
                    'SELECT id, email FROM users WHERE email = ? LIMIT 1',
                    [$email]
                );

                // Recovery path for an installation that previously failed
                // after the administrator row had already been written.
                if ($userCount === 1 && $existingAdmin !== null) {
                    $database->execute(
                        'UPDATE users SET name = ?, password_hash = ?, '
                        . 'role = ?, status = ? WHERE id = ?',
                        [
                            $adminName,
                            password_hash($password, PASSWORD_DEFAULT),
                            'administrator',
                            'active',
                            (int) $existingAdmin['id'],
                        ]
                    );
                } else {
                    throw new RuntimeException(
                        'Installation stopped because the users table already contains data. '
                        . 'Use an empty AfiuCMS database for a fresh installation.'
                    );
                }
            }

            $settings = [
                'site_name' => $siteName,
                'site_tagline' => 'Powered by AfiuCMS',
                'active_theme' => 'afiu-default',
                'site_description' => '',
                'homepage_title' => $siteName,
                'footer_text' => 'Powered by AfiuCMS',
                'posts_per_page' => '10',
                'search_engine_visibility' => '0',
            ];

            foreach ($settings as $key => $value) {
                $database->execute(
                    'INSERT INTO settings (setting_key, setting_value) '
                    . 'VALUES (?, ?) '
                    . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                    [$key, $value]
                );
            }


            $appKey = 'base64:' . base64_encode(random_bytes(32));

            $this->envWriter->write([
                'APP_NAME' => $siteName,
                'APP_ENV' => 'local',
                'APP_DEBUG' => true,
                'APP_URL' => rtrim(
                    (string) $this->config->get(
                        'app.url',
                        'http://127.0.0.1:8000'
                    ),
                    '/'
                ),
                'APP_TIMEZONE' => 'Europe/Istanbul',
                'APP_KEY' => $appKey,
                'APP_INSTALLED' => true,
                'DB_HOST' => $db['host'],
                'DB_PORT' => $db['port'],
                'DB_DATABASE' => $db['database'],
                'DB_USERNAME' => $db['username'],
                'DB_PASSWORD' => $db['password'],
                'SESSION_SECURE' => false,
                'SESSION_SAME_SITE' => 'Lax',
                'SESSION_IDLE_TIMEOUT' => 3600,
                'UPLOAD_MAX_MB' => 10,
            ]);

            return Response::redirect('/admin/login');
        } catch (Throwable $e) {
            $this->logInstallFailure($e);

            $message = $e->getMessage();

            if ((bool) $this->config->get('app.debug', false)) {
                $message .= sprintf(
                    ' [AfiuCMS 0.2.0-alpha | %s:%d]',
                    basename($e->getFile()),
                    $e->getLine()
                );
            }

            return Response::html(
                $this->view->render('install.index', [
                    'error' => $message,
                    'values' => $values,
                ]),
                500
            );
        }
    }

    private function logInstallFailure(Throwable $e): void
    {
        $logDirectory = (string) $this->config->get(
            'paths.logs',
            AFIU_ROOT . '/storage/logs'
        );

        if (!is_dir($logDirectory)) {
            @mkdir($logDirectory, 0775, true);
        }

        $entry = sprintf(
            "[%s] AfiuCMS 0.2.0-alpha INSTALL FAILED: %s in %s:%d\n%s\n\n",
            date('c'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        @file_put_contents(
            $logDirectory . '/install.log',
            $entry,
            FILE_APPEND | LOCK_EX
        );
    }
}
