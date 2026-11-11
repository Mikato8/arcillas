<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;

final class UsuarioController extends AdminController
{
    public function index(): void
    {
        $usuarios = Database::fetchAll('SELECT id, nombre, email, rol, activo, ultimo_acceso, creado_en FROM users ORDER BY id ASC');
        $editar = null;
        $editarId = (int) ($_GET['editar'] ?? 0);
        if ($editarId > 0) {
            foreach ($usuarios as $usuario) {
                if ((int) $usuario['id'] === $editarId) {
                    $editar = $usuario;
                    break;
                }
            }
        }
        $this->admin('admin/usuarios/index', [
            'titulo'   => 'Usuarios',
            'usuarios' => $usuarios,
            'editar'   => $editar,
        ]);
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $rol = in_array(($_POST['rol'] ?? ''), ['admin', 'editor'], true) ? $_POST['rol'] : 'editor';
        $password = (string) ($_POST['password'] ?? '');

        if ($nombre === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            Session::flash('error', 'Nombre y correo válidos son obligatorios.');
            $this->redirigir('/admin/usuarios');
        }

        $existente = Database::fetch('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
        if ($existente !== null && (int) $existente['id'] !== $id) {
            Session::flash('error', 'Ya existe un usuario con ese correo.');
            $this->redirigir('/admin/usuarios');
        }

        if ($id > 0) {
            $datos = ['nombre' => $nombre, 'email' => $email, 'rol' => $rol, 'activo' => !empty($_POST['activo']) ? 1 : 0];
            if ($password !== '') {
                if (strlen($password) < 10) {
                    Session::flash('error', 'La contraseña debe tener al menos 10 caracteres.');
                    $this->redirigir('/admin/usuarios');
                }
                $datos['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            Database::update('users', $datos, ['id' => $id]);
            $this->bitacora('usuario_actualizado', 'users', (string) $id);
        } else {
            if (strlen($password) < 10) {
                Session::flash('error', 'La contraseña inicial debe tener al menos 10 caracteres.');
                $this->redirigir('/admin/usuarios');
            }
            Database::insert('users', [
                'nombre'        => $nombre,
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'rol'           => $rol,
                'activo'        => 1,
            ]);
            $this->bitacora('usuario_creado', 'users', '', ['email' => $email]);
        }
        Session::flash('ok', 'Usuario guardado.');
        $this->redirigir('/admin/usuarios');
    }

    /** @param array<string,string> $params */
    public function eliminar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === (int) Auth::id()) {
            Session::flash('error', 'No puedes desactivar tu propio usuario.');
            $this->redirigir('/admin/usuarios');
        }
        Database::update('users', ['activo' => 0], ['id' => $id]);
        $this->bitacora('usuario_desactivado', 'users', (string) $id);
        Session::flash('ok', 'Usuario desactivado.');
        $this->redirigir('/admin/usuarios');
    }
}
