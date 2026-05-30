<?php

namespace App\Http\Middleware;

use App\Admin\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAllowedViewerEmail
{
    public function __construct(private readonly AdminAccess $adminAccess)
    {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->adminAccess->isAllowedEmail($request->user()?->email), 403);

        return $next($request);
    }
}
