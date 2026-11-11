<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Session;

final class AuthController extends AdminController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirigir('/admin');
        }
        $this->view('admin/login', [
            'titulo' => 'Acceso al panel',
            'meta'   => ['titulo' => 'Acceso · Arcillas MX', 'noindex' => true],
            'error'  => Session::pullFlash('error'),
        ], 'layouts/blank');
    }

    public function login(): void
    {
        if (!Csrf::verifyRequest()) {
            Session::flash('error', 'La sesión expiró. Intenta de nuevo.');
            $this->redirigir('/admin/login');
        }
        if (!RateLimit::check('login', 8, 900)) {
            Session::flash('error', 'Demasiados intentos. Espera 15 minutos.');
            $this->redirigir('/admin/login');
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '' || !Auth::attempt($email, $password)) {
            usleep(400000);
            Session::flash('error', 'Correo o contraseña incorrectos.');
            $this->redirigir('/admin/login');
        }

        Database::run('UPDATE users SET ultimo_acceso = NOW() WHERE id = ?', [Auth::id()]);
        Database::run('INSERT INTO logs (user_id, accion, ip) VALUES (?, ?, ?)', [Auth::id(), 'login', Request::ip()]);
        Session::regenerate();
        $this->redirigir('/admin');
    }

    public function logout(): void
    {
        $this->bitacora('logout');
        Auth::logout();
        $this->redirigir('/admin/login');
    }
}
