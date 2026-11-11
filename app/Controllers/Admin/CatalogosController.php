<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Session;
use App\Models\Industria;
use App\Models\Proveedor;

final class CatalogosController extends AdminController
{
    // ── Industrias ──────────────────────────────────────────────────────
    public function industrias(): void
    {
        $this->admin('admin/industrias/index', [
            'titulo'     => 'Industrias',
            'industrias' => Industria::todas(false),
        ]);
    }

    /** @param array<string,string> $params */
    public function industriaForm(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $industria = $id > 0 ? Industria::porId($id) : null;
        $this->admin('admin/industrias/form', [
            'titulo'    => $id > 0 ? 'Editar industria' : 'Nueva industria',
            'industria' => $industria ?? ['id' => 0, 'nombre' => '', 'slug' => '', 'descripcion' => '', 'icono' => 'factory', 'color' => '#8B5E3C', 'orden' => 0, 'activo' => 1],
        ]);
    }

    public function industriaGuardar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            Session::flash('error', 'El nombre es obligatorio.');
            $this->redirigir('/admin/industrias');
        }
        $datos = [
            'nombre'      => $nombre,
            'slug'        => trim((string) ($_POST['slug'] ?? '')) ?: slugify($nombre),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'icono'       => trim((string) ($_POST['icono'] ?? 'factory')),
            'color'       => preg_match('/^#[0-9a-f]{6}$/i', (string) ($_POST['color'] ?? '')) ? $_POST['color'] : '#8B5E3C',
            'orden'       => (int) ($_POST['orden'] ?? 0),
            'activo'      => !empty($_POST['activo']) ? 1 : 0,
        ];
        $id > 0 ? Database::update('industries', $datos, ['id' => $id]) : Database::insert('industries', $datos);
        $this->bitacora($id > 0 ? 'industria_actualizada' : 'industria_creada', 'industries', (string) $id);
        Session::flash('ok', 'Industria guardada.');
        $this->redirigir('/admin/industrias');
    }

    /** @param array<string,string> $params */
    public function industriaEliminar(array $params): void
    {
        Database::update('industries', ['activo' => 0], ['id' => (int) ($params['id'] ?? 0)]);
        Session::flash('ok', 'Industria desactivada.');
        $this->redirigir('/admin/industrias');
    }

    // ── Proveedores ─────────────────────────────────────────────────────
    public function proveedores(): void
    {
        $this->admin('admin/proveedores/index', [
            'titulo'      => 'Proveedores',
            'proveedores' => Proveedor::todos(false),
        ]);
    }

    /** @param array<string,string> $params */
    public function proveedorForm(array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $proveedor = $id > 0 ? Proveedor::porId($id) : null;
        $this->admin('admin/proveedores/form', [
            'titulo'    => $id > 0 ? 'Editar proveedor' : 'Nuevo proveedor',
            'proveedor' => $proveedor ?? [
                'id' => 0, 'nombre' => '', 'slug' => '', 'tipo' => 'mina', 'descripcion' => '',
                'pais' => 'México', 'estado' => '', 'ciudad' => '', 'capacidad' => '', 'certificaciones' => '',
                'sitio_web' => '', 'email_publico' => '', 'telefono_publico' => '', 'verificado' => 0,
                'es_demo' => 1, 'activo' => 1,
            ],
        ]);
    }

    public function proveedorGuardar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            Session::flash('error', 'El nombre es obligatorio.');
            $this->redirigir('/admin/proveedores');
        }
        $datos = [
            'nombre'          => $nombre,
            'slug'            => trim((string) ($_POST['slug'] ?? '')) ?: slugify($nombre),
            'tipo'            => in_array(($_POST['tipo'] ?? ''), ['mina', 'planta', 'distribuidor', 'broker'], true) ? $_POST['tipo'] : 'mina',
            'descripcion'     => trim((string) ($_POST['descripcion'] ?? '')),
            'pais'            => trim((string) ($_POST['pais'] ?? 'México')),
            'estado'          => trim((string) ($_POST['estado'] ?? '')),
            'ciudad'          => trim((string) ($_POST['ciudad'] ?? '')),
            'capacidad'       => trim((string) ($_POST['capacidad'] ?? '')),
            'certificaciones' => trim((string) ($_POST['certificaciones'] ?? '')),
            'sitio_web'       => trim((string) ($_POST['sitio_web'] ?? '')),
            'email_publico'   => trim((string) ($_POST['email_publico'] ?? '')),
            'telefono_publico' => trim((string) ($_POST['telefono_publico'] ?? '')),
            'verificado'      => !empty($_POST['verificado']) ? 1 : 0,
            'es_demo'         => !empty($_POST['es_demo']) ? 1 : 0,
            'activo'          => !empty($_POST['activo']) ? 1 : 0,
        ];
        $id > 0 ? Database::update('suppliers', $datos, ['id' => $id]) : Database::insert('suppliers', $datos);
        $this->bitacora($id > 0 ? 'proveedor_actualizado' : 'proveedor_creado', 'suppliers', (string) $id);
        Session::flash('ok', 'Proveedor guardado.');
        $this->redirigir('/admin/proveedores');
    }

    /** @param array<string,string> $params */
    public function proveedorEliminar(array $params): void
    {
        Database::update('suppliers', ['activo' => 0], ['id' => (int) ($params['id'] ?? 0)]);
        $this->bitacora('proveedor_desactivado', 'suppliers', (string) ($params['id'] ?? ''));
        Session::flash('ok', 'Proveedor desactivado.');
        $this->redirigir('/admin/proveedores');
    }
}
