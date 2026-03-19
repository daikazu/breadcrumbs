<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs\Middleware;

use Closure;
use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetBreadcrumbs
{
    public function __construct(
        protected BreadcrumbsManager $manager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        View::share('breadcrumbs', $this->manager->current());

        return $response;
    }
}
