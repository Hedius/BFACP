<?php

namespace BFACP\Http\Middleware;

use Closure;
use Lavary\Menu\Facade as Menu;

/**
 * Class MenuMiddleware
 * @package BFACP\Http\Middleware
 */
class MenuMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Menu::make('SideNav', function ($m) {
            $m->add('Home', ['route' => 'home']);
            $m->add('Players', ['route' => 'player.listing']);
        });

        return $next($request);
    }
}
