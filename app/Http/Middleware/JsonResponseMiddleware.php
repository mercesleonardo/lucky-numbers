<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Força Accept: application/json para rotas da API
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        // Adiciona headers específicos para frontend
        if ($response instanceof JsonResponse) {
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            
            // Headers para debugging frontend
            $response->headers->set('X-API-Version', '1.0');
            $response->headers->set('X-Lottery-System', 'Lucky Numbers');
            
            // Cache headers para endpoints específicos
            if ($request->is('api/games/info')) {
                $response->headers->set('Cache-Control', 'public, max-age=3600'); // 1 hora
            } elseif ($request->is('api/contests/latest*')) {
                $response->headers->set('Cache-Control', 'public, max-age=300'); // 5 minutos
            } else {
                $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            }
        }

        return $response;
    }
}
