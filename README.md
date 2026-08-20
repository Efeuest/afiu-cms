# AfiuCMS

AfiuCMS is a modern, open-source PHP content management system with a modular theme architecture.

> **Current release:** `0.2.1-alpha` — development software, not yet recommended for production websites.

## Highlights

- Web installer and automatic additive database upgrades
- Modern responsive administration console
- Pages and posts with drafts, publishing, SEO metadata and revision snapshots
- Categories and tags
- Media library with protected file storage and alt text
- Administrator, Editor and Author roles with backend authorization
- Menu locations and menu item management
- Global site/SEO settings
- Audit/activity log
- Secure sessions, CSRF protection, security headers and login throttling
- ZIP theme installer with path traversal and symlink checks
- Three bundled themes: **Afiu Default**, **Afiu Studio**, **Afiu Journal**
- Public blog pagination, taxonomy archives and search
- Small CLI (`bin/afiu`) for migrations/version checks

## Requirements

- PHP 8.4+
- MySQL 8+ / compatible MySQL server
- Composer
- PHP extensions: PDO MySQL, mbstring, fileinfo, zip (zip is needed for theme installation)

## Development

```bash
composer install
composer serve
```

Open `http://127.0.0.1:8000`.

For an existing v0.1.x installation, replace application files while keeping `.env`, then run `composer install`. Pending migrations are applied automatically on boot; they can also be applied explicitly with:

```bash
php bin/afiu migrate
```

## Theme structure

```text
themes/my-theme/
├── theme.json
├── assets/
│   ├── style.css
│   └── preview.svg
└── views/
    ├── layout.php
    ├── home.php
    ├── blog.php
    ├── post.php
    ├── page.php
    ├── search.php
    └── 404.php
```

Theme PHP files execute server-side code. Install third-party themes only from sources you trust.

## Roadmap

Next major areas: block editor, plugin API, update channels, backups/import-export, scheduled publishing, richer menu builder, theme customizer, REST API and automated test coverage.

## License

MIT.
