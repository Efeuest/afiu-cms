<?php

declare(strict_types=1);

namespace AfiuCMS\Core\Contracts;

use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;

interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}
