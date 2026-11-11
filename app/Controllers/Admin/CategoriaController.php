<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Session;
use App\Models\Categoria;

final class CategoriaController extends AdminController
{
    public function index(): void
    {
        $this->admin('admin/categorias/index', [
            'titulo'     => 'Categorías',
            'categorias' => Categoria::todas(false),
        ]);
    }

    /** @param array<string,string> $params */
    public function form(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $categoria = $id > 0 ? Categoria::porId($id) : null;
        if ($id > 0 && $categoria === null) {
            $this->noEncontrado();
        }
        $this->admin('admin/categorias/form', [
            'titulo'    => $id > 0 ? 'Editar categoría' : 'Nueva categoría',
            'categoria' => $categoria ?? ['id' => 0, 'nombre' => '', 'slug' => '', 'descripcion' => '', 'familia' => 'industrial', 'orden' => 0, 'activo' => 1],
            'errores'   => [],
        ]);
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            Session::flash('error', 'El nombre es obligatorio.');
            $this->redirigir($id > 0 ? '/admin/categorias/' . $id . '/editar' : '/admin/categorias/nueva');
        }
        $slug = trim((string) ($_POST['slug'] ?? '')) ?: slugify($nombre);
        $datos = [
            'nombre'      => $nombre,
            'slug'        => $slug,
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'familia'     => in_array(($_POST['familia'] ?? ''), ['arcillas', 'industrial', 'no_ferroso', 'carga'], true) ? $_POST['familia'] : 'industrial',
            'orden'       => (int) ($_POST['orden'] ?? 0),
            'activo'      => !empty($_POST['activo']) ? 1 : 0,
        ];
        if ($id > 0) {
            Database::update('categories', $datos, ['id' => $id]);
        } else {
            Database::insert('categories', $datos);
        }
        $this->bitacora($id > 0 ? 'categoria_actualizada' : 'categoria_creada', 'categories', (string) $id);
        Session::flash('ok', 'Categoría guardada.');
        $this->redirigir('/admin/categorias');
    }

    /** @param array<string,string> $params */
    public function eliminar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $materiales = (int) Database::scalar('SELECT COUNT(*) FROM materials WHERE category_id = ?', [$id]);
        if ($materiales > 0) {
            Database::update('categories', ['activo' => 0], ['id' => $id]);
            Session::flash('ok', 'La categoría tiene materiales: se desactivó para no romper las fichas.');
        } else {
            Database::delete('categories', ['id' => $id]);
            Session::flash('ok', 'Categoría eliminada.');
        }
        $this->redirigir('/admin/categorias');
    }
}
