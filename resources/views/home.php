<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $version */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= htmlspecialchars($title) ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            background: #0b0d10;
            color: #f4f5f7;
        }

        main {
            width: min(680px, calc(100% - 40px));
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border: 1px solid #30343b;
            border-radius: 999px;
            font-size: 12px;
            color: #aeb4bd;
        }

        h1 {
            margin: 22px 0 8px;
            font-size: clamp(42px, 8vw, 72px);
            letter-spacing: -0.055em;
        }

        p {
            color: #9da4ae;
            font-size: 18px;
            line-height: 1.6;
        }

        code {
            color: #ffffff;
        }
    </style>
</head>

<body>

<main>
    <span class="badge">
        Core <?= htmlspecialchars($version) ?>
    </span>

    <h1><?= htmlspecialchars($title) ?></h1>

    <p>
        AfiuCMS core application is running.
        Router, environment configuration,
        sessions, views and error handling
        have been initialized successfully.
    </p>

    <p>
        Health endpoint:
        <code>/health</code>
    </p>
</main>

</body>
</html>