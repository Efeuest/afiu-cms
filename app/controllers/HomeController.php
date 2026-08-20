<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers;

use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\View;

final class HomeController
{
    public function index(Request $request): Response
    {
        return Response::html(
            View::render(
                'home',
                [
                    'title' => 'AfiuCMS',
                    'version' => '0.1.0-dev',
                ]
            )
        );
    }
}