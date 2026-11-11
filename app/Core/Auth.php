<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = (int) Session::get('user_id', 0);
        if ($id > 0) {
            try {
                $user = Database::fetch(
                    'SELECT id, nombre, email, rol, activo FROM users WHERE id = ? LIMIT 1',
                    [$id]
                );
                if ($user && (int) $user['activo'] === 1) {
                    self::$user = $user;
                } else {
                    Session::forget('user_id');
                }
            } catch (\Throwable) {
                // Si la base no está disponible, el usuario queda como invitado.
                self::$user = null;
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        return self::user()['rol'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        $role = self::role();
        return $role !== null && in_array($role, $roles, true);
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::fetch(
            'SELECT id, nombre, email, password_hash, rol, activo FROM users WHERE email = ? LIMIT 1',
            [strtolower(trim($email))]
        );
        if (!$user || (int) $user['activo'] !== 1) {
            return false;
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], ['id' => $user['id']]);
        }
        self::login((int) $user['id']);
        return true;
    }

    public static function login(int $userId): void
    {
        Session::regenerate();
        Session::set('user_id', $userId);
        self::$user = null;
        self::$resolved = false;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
        self::$resolved = false;
    }

    /** @return array<string,mixed> */
    public static function requireUser(): array
    {
        $user = self::user();
        if ($user === null) {
            if (Request::wantsJson()) {
                Response::json(['ok' => false, 'error' => 'No autenticado', 'codigo' => 'AUTH'], 401);
            }
            Response::redirect('/admin/login?next=' . urlencode(Request::path()));
        }
        return $user;
    }

    /** @param array<int,string> $roles */
    public static function requireRole(array $roles): array
    {
        $user = self::requireUser();
        if (!in_array((string) $user['rol'], $roles, true)) {
            if (Request::wantsJson()) {
                Response::json(['ok' => false, 'error' => 'No tienes permisos para esta acción', 'codigo' => 'FORBIDDEN'], 403);
            }
            http_response_code(403);
            View::render('pages/403', ['titulo' => 'Acceso restringido'], 'layouts/main');
            exit;
        }
        return $user;
    }
}
