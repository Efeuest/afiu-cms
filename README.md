# AfiuCMS

AfiuCMS is an experimental, open-source CMS written in PHP 8.4. It is being built as a small, understandable core with a web installer, administration panel, content management, media library, settings and an installable theme system.

> **Alpha software:** this repository is under active development and is not production-ready yet.

## Current alpha features

- Web-based first-run installer
- MySQL + PDO database layer and migration runner
- Admin account creation and session authentication
- CSRF protection for state-changing requests
- Admin dashboard
- Pages and blog posts CRUD
- Draft/published content states
- Basic media library with MIME validation and private storage
- General site settings
- Theme discovery, activation and ZIP installation
- Bundled `Afiu Default` theme
- Public homepage, pages, blog index and post routes
- Environment configuration via `.env`
- Error logging and development error screen
- Apache rewrite file and PHP built-in-server router

## Requirements

- PHP 8.4+
- Composer
- MySQL 8+ or compatible MariaDB
- PDO MySQL
- Fileinfo
- Zip extension for theme ZIP installation

## Local setup

```bash
composer install
composer serve
```

Then open `http://127.0.0.1:8000`. AfiuCMS will redirect to `/install` until installation is complete.

The installer asks for database credentials, site details and the first administrator account. It writes the local `.env`, runs migrations and creates the initial data.

## Project structure

```text
afiu-cms/
├── app/                 # Core, controllers and middleware
├── bootstrap/           # Application bootstrapping
├── config/              # Runtime configuration
├── database/migrations/ # Database migrations
├── public/              # Web root
├── resources/views/     # Core/admin views
├── routes/              # Route definitions
├── storage/             # Logs, sessions, uploads and temporary files
└── themes/              # Installable themes
```

## Theme packages

A theme is a directory containing a `theme.json`, a `views/` directory and optional `assets/`. ZIP packages must place `theme.json` at the archive root. Themes are trusted code because PHP view files execute on the server; only install themes from sources you trust.

## Security status

The alpha includes password hashing, session regeneration, CSRF protection, prepared SQL statements, MIME-checked media uploads, private upload storage and ZIP path validation. It still needs a broader security review, automated tests, permissions/roles beyond administrator, hardened deployment documentation and release signing before production use.

## License

MIT
