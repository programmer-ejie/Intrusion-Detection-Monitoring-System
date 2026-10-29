<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user session exists
        if (!session()->has('user')) {
            // No session, redirect to login
            return redirect()->route('login')->with('message', 'Session expired. Please login again.');
        }

        // Reload permissions so role changes also apply to existing sessions.
        $user = \App\Models\UsersAccount::find(session('user')->id);
        if (! $user) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }
        session(['user' => $user]);

        abort_if(! $request->isMethodSafe() && ! $user->canManageThreats(), 403, 'This account has view-only access.');

        return $next($request);
    }
}
