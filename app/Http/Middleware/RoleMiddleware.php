<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     * @param  Role|Role[]  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $userRole = $user->role;

        $allowedRoles = array_map(function (string $role): Role {
            return Role::from($role);
        }, $roles);

        if (! in_array($userRole, $allowedRoles, true)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
