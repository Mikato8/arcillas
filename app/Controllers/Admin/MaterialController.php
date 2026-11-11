<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Session;
use App\Models\Categoria;
use App\Models\Industria;
use App\Models\Material;
use App\Models\Proveedor;

final class MaterialController extends AdminController
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $categoria = (string) ($_GET['categoria'] ?? '');
        $estado = (string) ($_GET['estado'] ?? '');
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $porPagina = 20;

        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(m.nombre LIKE :q OR m.slug LIKE :q OR m.resumen LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        if ($categoria !== '') {
            $where[] = 'c.slug = :categoria';
            $params['categoria'] = $categoria;
        }
        if ($estado !== '') {
            $where[] = 'm.activo = :activo';
            $params['activo'] = $estado === 'activos' ? 1 : 0;
        }

        $total = (int) Database::scalar(
            'SELECT COUNT(*) FROM materials m INNER JOIN categories c ON c.id = m.category_id WHERE ' . implode(' AND ', $where),
            $params
        );
        $items = Database::fetchAll(
            'SELECT m.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug
             FROM materials m INNER JOIN categories c ON c.id = m.category_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY m.actualizado_en DESC
             LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina),
            $params
        );

        $this->admin('admin/materiales/index', [
            'titulo'      => 'Materiales',
            'items'       => $items,
            'total'       => $total,
            'pagina'      => $pagina,
            'paginas'     => max(1, (int) ceil($total / $porPagina)),
            'q'           => $q,
            'categoria'   => $categoria,
            'estado'      => $estado,
            'categorias'  => Categoria::todas(false),
        ]);
    }

    public function nuevo(): void
    {
        $this->formulario(null);
    }

    /** @param array<string,string> $params */
    public function editar(array $params): void
    {
        $material = Material::porId((int) ($params['id'] ?? 0));
        if ($material === null) {
            $this->noEncontrado();
        }
        $this->formulario($material, [
            'props'      => Material::propiedades((int) $material['id']),
            'suppliers'  => array_column(Material::proveedores((int) $material['id']), 'id'),
            'industrias' => array_column(Material::industrias((int) $material['id']), 'id'),
        ]);
    }

    /** @param array<string,mixed>|null $material @param array<string,mixed> $extra */
    private function formulario(?array $material, array $extra = []): void
    {
        $props = $extra['props'] ?? [];
        if ($props === []) {
            for ($i = 0; $i < 6; $i++) {
                $props[] = ['grupo' => 'quimica', 'clave' => '', 'nombre' => '', 'valor' => '', 'valor_texto' => '', 'unidad' => ''];
            }
        }
        $this->admin('admin/materiales/form', [
            'titulo'      => $material ? 'Editar material' : 'Nuevo material',
            'material'    => $material ?? [
                'id' => 0, 'nombre' => '', 'slug' => '', 'resumen' => '', 'descripcion' => '', 'origen' => '',
                'aplicaciones' => '', 'presentacion' => '', 'disponibilidad' => 'inmediata', 'region' => '',
                'precio_ref' => '', 'moneda' => 'MXN', 'unidad' => 'tonelada', 'imagen' => '',
                'destacado' => 0, 'activo' => 1, 'meta_title' => '', 'meta_description' => '',
                'category_id' => (int) ($_GET['categoria_id'] ?? 0),
            ],
            'props'        => $props,
            'supplier_ids' => $extra['suppliers'] ?? [],
            'industria_ids' => $extra['industrias'] ?? [],
            'categorias'   => Categoria::todas(false),
            'proveedores'  => Proveedor::todos(false),
            'industrias'   => Industria::todas(false),
            'errores'      => [],
            'old'          => [],
        ]);
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $datos = [
            'category_id'      => (int) ($_POST['category_id'] ?? 0),
            'nombre'           => trim((string) ($_POST['nombre'] ?? '')),
            'slug'             => trim((string) ($_POST['slug'] ?? '')),
            'resumen'          => mb_substr(trim((string) ($_POST['resumen'] ?? '')), 0, 320),
            'descripcion'      => trim((string) ($_POST['descripcion'] ?? '')),
            'origen'           => trim((string) ($_POST['origen'] ?? '')),
            'aplicaciones'     => trim((string) ($_POST['aplicaciones'] ?? '')),
            'presentacion'     => trim((string) ($_POST['presentacion'] ?? '')),
            'disponibilidad'   => (string) ($_POST['disponibilidad'] ?? 'inmediata'),
            'region'           => trim((string) ($_POST['region'] ?? '')),
            'precio_ref'       => ($_POST['precio_ref'] ?? '') !== '' ? (float) $_POST['precio_ref'] : null,
            'moneda'           => (string) ($_POST['moneda'] ?? 'MXN'),
            'unidad'           => (string) ($_POST['unidad'] ?? 'tonelada'),
            'destacado'        => !empty($_POST['destacado']) ? 1 : 0,
            'activo'           => !empty($_POST['activo']) ? 1 : 0,
            'meta_title'       => trim((string) ($_POST['meta_title'] ?? '')),
            'meta_description' => mb_substr(trim((string) ($_POST['meta_description'] ?? '')), 0, 320),
            'es_demo'          => !empty($_POST['es_demo']) ? 1 : 0,
        ];

        $errores = [];
        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }
        if ($datos['category_id'] <= 0) {
            $errores['category_id'] = 'Selecciona una categoría.';
        }
        if (!in_array($datos['disponibilidad'], ['inmediata', 'programada', 'bajo_pedido', 'agotado'], true)) {
            $datos['disponibilidad'] = 'inmediata';
        }

        if ($datos['slug'] === '') {
            $datos['slug'] = slugify($datos['nombre']);
        } else {
            $datos['slug'] = slugify($datos['slug']);
        }
        if ($datos['slug'] === '') {
            $datos['slug'] = 'material-' . time();
        }
        $existente = Database::scalar('SELECT id FROM materials WHERE slug = ? AND id <> ? LIMIT 1', [$datos['slug'], $id]);
        if ($existente !== null) {
            $datos['slug'] .= '-' . ($id > 0 ? $id : substr((string) time(), -4));
        }

        // Imagen (opcional)
        if (!empty($_FILES['imagen']['name']) && ($_FILES['imagen']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $tmp = (string) $_FILES['imagen']['tmp_name'];
            $ext = strtolower(pathinfo((string) $_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            if (!in_array($ext, $permitidas, true)) {
                $errores['imagen'] = 'Formato no permitido (usa JPG, PNG, WEBP o SVG).';
            } elseif ((int) $_FILES['imagen']['size'] > 3 * 1024 * 1024) {
                $errores['imagen'] = 'La imagen supera 3 MB.';
            } else {
                $dir = APP_PUBLIC . '/uploads/materiales';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                $nombre = $datos['slug'] . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($tmp, $dir . '/' . $nombre)) {
                    $datos['imagen'] = 'materiales/' . $nombre;
                } else {
                    $errores['imagen'] = 'No se pudo guardar la imagen.';
                }
            }
        }

        if ($errores !== []) {
            http_response_code(422);
            $this->admin('admin/materiales/form', [
                'titulo'       => $id > 0 ? 'Editar material' : 'Nuevo material',
                'material'     => array_merge(['id' => $id], $datos),
                'props'        => $this->propsDePost(),
                'supplier_ids' => array_map('intval', (array) ($_POST['suppliers'] ?? [])),
                'industria_ids' => array_map('intval', (array) ($_POST['industrias'] ?? [])),
                'categorias'   => Categoria::todas(false),
                'proveedores'  => Proveedor::todos(false),
                'industrias'   => Industria::todas(false),
                'errores'      => $errores,
                'old'          => $datos,
            ]);
            return;
        }

        Database::transaction(static function () use ($id, $datos): void {
            if ($id > 0) {
                Database::update('materials', $datos, ['id' => $id]);
                $materialId = $id;
            } else {
                $materialId = Database::insert('materials', $datos);
            }

            Database::run('DELETE FROM material_properties WHERE material_id = ?', [$materialId]);
            foreach (($_POST['props'] ?? []) as $orden => $prop) {
                $clave = trim((string) ($prop['clave'] ?? ''));
                if ($clave === '') {
                    continue;
                }
                Database::insert('material_properties', [
                    'material_id' => $materialId,
                    'grupo'       => in_array(($prop['grupo'] ?? ''), ['quimica', 'fisica', 'granulometria', 'presentacion'], true) ? $prop['grupo'] : 'fisica',
                    'clave'       => strtolower($clave),
                    'nombre'      => trim((string) ($prop['nombre'] ?? $clave)),
                    'valor'       => ($prop['valor'] ?? '') !== '' ? (float) $prop['valor'] : null,
                    'valor_texto' => trim((string) ($prop['valor_texto'] ?? '')),
                    'unidad'      => trim((string) ($prop['unidad'] ?? '')),
                    'orden'       => (int) $orden,
                ]);
            }

            Database::run('DELETE FROM material_suppliers WHERE material_id = ?', [$materialId]);
            foreach (array_map('intval', (array) ($_POST['suppliers'] ?? [])) as $i => $supplierId) {
                if ($supplierId <= 0) {
                    continue;
                }
                Database::insert('material_suppliers', [
                    'material_id'  => $materialId,
                    'supplier_id'  => $supplierId,
                    'es_principal' => $i === 0 ? 1 : 0,
                    'disponibilidad' => $datos['disponibilidad'],
                    'precio_ref'   => $datos['precio_ref'],
                    'moneda'       => $datos['moneda'],
                    'unidad'       => $datos['unidad'],
                    'activo'       => 1,
                ]);
            }

            Database::run('DELETE FROM material_industries WHERE material_id = ?', [$materialId]);
            foreach (array_map('intval', (array) ($_POST['industrias'] ?? [])) as $industryId) {
                if ($industryId > 0) {
                    Database::insert('material_industries', ['material_id' => $materialId, 'industry_id' => $industryId]);
                }
            }
        });

        $this->bitacora($id > 0 ? 'material_actualizado' : 'material_creado', 'materials', (string) $id, ['slug' => $datos['slug']]);
        Session::flash('ok', 'Material guardado correctamente.');
        $this->redirigir('/admin/materiales');
    }

    /** @param array<string,string> $params */
    public function eliminar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $material = Material::porId($id);
        if ($material === null) {
            $this->noEncontrado();
        }
        Database::update('materials', ['activo' => 0], ['id' => $id]);
        $this->bitacora('material_desactivado', 'materials', (string) $id);
        Session::flash('ok', 'Material desactivado. Ya no aparece en el sitio público.');
        $this->redirigir('/admin/materiales');
    }

    /** @param array<string,string> $params */
    public function activar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Database::update('materials', ['activo' => 1], ['id' => $id]);
        $this->bitacora('material_activado', 'materials', (string) $id);
        Session::flash('ok', 'Material activado.');
        $this->redirigir('/admin/materiales');
    }

    /** @return array<int,array<string,mixed>> */
    private function propsDePost(): array
    {
        $rows = [];
        foreach (($_POST['props'] ?? []) as $prop) {
            if (is_array($prop) && trim((string) ($prop['clave'] ?? '')) !== '') {
                $rows[] = $prop;
            }
        }
        return $rows;
    }
}
