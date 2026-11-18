<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasRole(...$roles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akses Dilarang / Unauthorized.',
                ], 403);
            }

            abort(403, 'Akses Dilarang / Unauthorized. Anda tidak memiliki hak akses untuk membuka halaman ini.');
        }

        return $next($request);
    }
}

