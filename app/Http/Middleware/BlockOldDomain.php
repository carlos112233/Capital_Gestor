<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockOldDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === 'elbajon.duckdns.org') {
            return redirect()->to('https://elbajon.store' . $request->getRequestUri(), 301);
        }
        
        return $next($request);
    }
}
