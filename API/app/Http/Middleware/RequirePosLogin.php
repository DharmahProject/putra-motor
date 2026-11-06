<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePosLogin
{
    /**
     * Reads the native PHP session written by includes/auth.php (the POS front end),
     * so the API only answers requests from a logged-in, active user.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $sessionId = (string) $request->cookies->get(session_name(), '');
        $userId = $sessionId !== '' ? $this->sessionUserId($sessionId) : null;
        $user = $userId ? User::query()->find($userId) : null;

        if (! $user || ! $user->active) {
            return response()->json(['message' => 'Sesi login berakhir, silakan login kembali'], 401);
        }

        if ($permission && ! in_array($permission, (array) $user->permissions, true)) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke modul ini'], 403);
        }

        return $next($request);
    }

    private function sessionUserId(string $sessionId): ?int
    {
        if (! preg_match('/^[A-Za-z0-9,-]{22,256}$/', $sessionId)) {
            return null;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_save_path(storage_path('framework/auth_sessions'));
            session_id($sessionId);
            session_start(['read_and_close' => true]);
        }

        $userId = $_SESSION['auth_user']['id'] ?? null;

        return is_numeric($userId) ? (int) $userId : null;
    }
}
