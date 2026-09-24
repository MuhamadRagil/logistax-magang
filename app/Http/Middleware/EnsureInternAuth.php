<?php

namespace App\Http\Middleware;

use App\Models\InternAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof InternAccount) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthorized. Intern access required.',
            ], 403);
        }

        return $next($request);
    }
}
