<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Materiales/minerales del directorio.
 *
 * El buscador imita las facetas de UL Prospector: texto libre, categoría,
 * industria, disponibilidad y rangos de propiedades técnicas (EAV).
 */
final class Material
{
    /** @return array<int,array<string,mixed>> */
    public static function destacados(int $limite = 6): array
    {
        return Database::fetchAll(
            'SELECT m.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug, c.familia
             FROM materials m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.activo = 1 AND m.destacado = 1 AND c.activo = 1
             ORDER BY m.vistas DESC, m.nombre ASC
             LIMIT ' . max(1, $limite)
        );
    }

    /**
     * Buscador con facetas.
     *
     * @param array<string,mixed> $filtros q, categoria, industria, familia, disponibilidad, al2o3_min, fe2o3_max, humedad_max, blancura_min, orden, pagina
     * @return array{items: array<int,array<string,mixed>>, total: int, pagina: int, paginas: int, por_pagina: int}
     */
    public static function buscar(array $filtros = [], int $porPagina = 12): array
    {
        $porPagina = max(1, min(48, $porPagina));
        $pagina = max(1, (int) ($filtros['pagina'] ?? 1));
        $offset = ($pagina - 1) * $porPagina;

        [$where, $params] = self::construirFiltros($filtros);
        $orden = self::orden((string) ($filtros['orden'] ?? 'relevancia'));

        $total = (int) Database::scalar(
            'SELECT COUNT(DISTINCT m.id)
             FROM materials m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE ' . implode(' AND ', $where),
            $params
        );

        $items = Database::fetchAll(
            'SELECT m.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug, c.familia,
                    (SELECT COUNT(*) FROM material_suppliers ms WHERE ms.material_id = m.id AND ms.activo = 1) AS proveedores
             FROM materials m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY ' . $orden . '
             LIMIT ' . $porPagina . ' OFFSET ' . $offset,
            $params
        );

        return [
            'items'      => $items,
            'total'      => $total,
            'pagina'     => $pagina,
            'paginas'    => max(1, (int) ceil($total / $porPagina)),
            'por_pagina' => $porPagina,
        ];
    }

    /**
     * @param array<string,mixed> $filtros
     * @return array{0: array<int,string>, 1: array<string,mixed>}
     */
    private static function construirFiltros(array $filtros): array
    {
        $where = ['m.activo = 1', 'c.activo = 1'];
        $params = [];

        $texto = trim((string) ($filtros['q'] ?? ''));
        if ($texto !== '') {
            $where[] = '(m.nombre LIKE :q1 OR m.resumen LIKE :q2 OR m.descripcion LIKE :q3 OR m.aplicaciones LIKE :q4
                        OR c.nombre LIKE :q5
                        OR EXISTS (SELECT 1 FROM material_properties p
                                   WHERE p.material_id = m.id AND (p.valor_texto LIKE :q6 OR p.nombre LIKE :q7)))';
            foreach (range(1, 7) as $i) {
                $params['q' . $i] = '%' . $texto . '%';
            }
        }

        if (!empty($filtros['categoria'])) {
            $where[] = 'c.slug = :categoria';
            $params['categoria'] = (string) $filtros['categoria'];
        }

        if (!empty($filtros['familia'])) {
            $where[] = 'c.familia = :familia';
            $params['familia'] = (string) $filtros['familia'];
        }

        if (!empty($filtros['industria'])) {
            $where[] = 'EXISTS (SELECT 1 FROM material_industries mi
                                INNER JOIN industries i ON i.id = mi.industry_id
                                WHERE mi.material_id = m.id AND i.slug = :industria)';
            $params['industria'] = (string) $filtros['industria'];
        }

        if (!empty($filtros['disponibilidad'])) {
            $where[] = 'm.disponibilidad = :disponibilidad';
            $params['disponibilidad'] = (string) $filtros['disponibilidad'];
        }

        if (!empty($filtros['proveedor'])) {
            $where[] = 'EXISTS (SELECT 1 FROM material_suppliers ms
                                INNER JOIN suppliers s ON s.id = ms.supplier_id
                                WHERE ms.material_id = m.id AND ms.activo = 1 AND s.slug = :proveedor)';
            $params['proveedor'] = (string) $filtros['proveedor'];
        }

        // Rangos de propiedades: al2o3_min, al2o3_max, fe2o3_max, humedad_max…
        $rangos = [
            'al2o3_min'   => ['al2o3', '>='],
            'al2o3_max'   => ['al2o3', '<='],
            'fe2o3_max'   => ['fe2o3', '<='],
            'sio2_min'    => ['sio2', '>='],
            'blancura_min' => ['blancura', '>='],
            'humedad_max' => ['humedad', '<='],
            'ph_min'      => ['ph', '>='],
            'ph_max'      => ['ph', '<='],
            'mbi_min'     => ['mbi', '>='],
            'd50_max'     => ['d50', '<='],
        ];
        foreach ($rangos as $param => [$clave, $operador]) {
            if (!isset($filtros[$param]) || $filtros[$param] === '' || !is_numeric($filtros[$param])) {
                continue;
            }
            $where[] = 'EXISTS (SELECT 1 FROM material_properties p
                                WHERE p.material_id = m.id AND p.clave = :clave_' . $param . '
                                  AND p.valor ' . $operador . ' :valor_' . $param . ')';
            $params['clave_' . $param] = $clave;
            $params['valor_' . $param] = (float) $filtros[$param];
        }

        return [$where, $params];
    }

    private static function orden(string $orden): string
    {
        return match ($orden) {
            'nombre'      => 'm.nombre ASC',
            'recientes'   => 'm.creado_en DESC, m.id DESC',
            'precio_asc'  => 'm.precio_ref IS NULL, m.precio_ref ASC, m.nombre ASC',
            'precio_desc' => 'm.precio_ref IS NULL, m.precio_ref DESC, m.nombre ASC',
            'vistos'      => 'm.vistas DESC, m.nombre ASC',
            default       => 'm.destacado DESC, m.vistas DESC, m.nombre ASC',
        };
    }

    /** @return array<string,mixed>|null */
    public static function porSlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT m.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug, c.familia, c.descripcion AS categoria_descripcion
             FROM materials m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.slug = ? AND m.activo = 1 AND c.activo = 1
             LIMIT 1',
            [$slug]
        );
    }

    /** @return array<string,mixed>|null */
    public static function porId(int $id): ?array
    {
        return Database::fetch('SELECT * FROM materials WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function propiedades(int $materialId): array
    {
        return Database::fetchAll(
            'SELECT * FROM material_properties WHERE material_id = ? ORDER BY grupo ASC, orden ASC, id ASC',
            [$materialId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function proveedores(int $materialId): array
    {
        return Database::fetchAll(
            'SELECT s.*, ms.disponibilidad AS disponibilidad_oferta, ms.precio_ref AS precio_oferta,
                    ms.moneda AS moneda_oferta, ms.unidad AS unidad_oferta, ms.minimo, ms.notas, ms.es_principal
             FROM material_suppliers ms
             INNER JOIN suppliers s ON s.id = ms.supplier_id
             WHERE ms.material_id = ? AND ms.activo = 1 AND s.activo = 1
             ORDER BY ms.es_principal DESC, s.verificado DESC, s.nombre ASC',
            [$materialId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function industrias(int $materialId): array
    {
        return Database::fetchAll(
            'SELECT i.*, mi.aplicacion
             FROM material_industries mi
             INNER JOIN industries i ON i.id = mi.industry_id
             WHERE mi.material_id = ? AND i.activo = 1
             ORDER BY i.orden ASC',
            [$materialId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function documentos(int $materialId): array
    {
        return Database::fetchAll(
            'SELECT * FROM material_docs WHERE material_id = ? AND publico = 1 ORDER BY id ASC',
            [$materialId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function relacionados(array $material, int $limite = 3): array
    {
        return Database::fetchAll(
            'SELECT m.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug
             FROM materials m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.activo = 1 AND m.id <> ?
               AND (m.category_id = ? OR m.region = ?)
             ORDER BY (m.category_id = ?) DESC, m.vistas DESC
             LIMIT ' . max(1, $limite),
            [(int) $material['id'], (int) $material['category_id'], (string) ($material['region'] ?? ''), (int) $material['category_id']]
        );
    }

    /**
     * Comparador: devuelve materiales con sus propiedades agrupadas.
     *
     * @param array<int,string> $slugs
     * @return array<int,array<string,mixed>>
     */
    public static function comparar(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter(array_map('trim', $slugs))));
        $slugs = array_slice($slugs, 0, 4);
        if ($slugs === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($slugs), '?'));
        $items = Database::fetchAll(
            'SELECT m.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug
             FROM materials m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.slug IN (' . $in . ') AND m.activo = 1
             ORDER BY FIELD(m.slug, ' . $in . ')',
            array_merge($slugs, $slugs)
        );
        foreach ($items as &$item) {
            $props = self::propiedades((int) $item['id']);
            $item['props'] = [];
            foreach ($props as $prop) {
                $item['props'][(string) $prop['clave']] = $prop;
            }
        }
        unset($item);
        return $items;
    }

    /** @return array<int,array<string,mixed>> */
    public static function todosParaSitemap(): array
    {
        return Database::fetchAll(
            'SELECT slug, actualizado_en FROM materials WHERE activo = 1 ORDER BY id ASC'
        );
    }

    /** Registra una vista (una por sesión y material). */
    public static function registrarVista(int $id): void
    {
        $vistos = $_SESSION['_vistos'] ?? [];
        if (in_array($id, $vistos, true)) {
            return;
        }
        $vistos[] = $id;
        $_SESSION['_vistos'] = array_slice($vistos, -50);
        Database::run('UPDATE materials SET vistas = vistas + 1 WHERE id = ?', [$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function busquedaSugerencias(string $texto, int $limite = 6): array
    {
        $texto = trim($texto);
        if (mb_strlen($texto) < 2) {
            return [];
        }
        return Database::fetchAll(
            'SELECT slug, nombre, resumen FROM materials
             WHERE activo = 1 AND (nombre LIKE ? OR resumen LIKE ?)
             ORDER BY destacado DESC, nombre ASC LIMIT ' . max(1, $limite),
            ['%' . $texto . '%', '%' . $texto . '%']
        );
    }

    /**
     * Propiedades clave de varios materiales en una sola consulta (evita N+1).
     *
     * @param array<int,int> $ids
     * @return array<int,array<int,array<string,mixed>>>
     */
    public static function propiedadesClave(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            "SELECT * FROM material_properties
             WHERE material_id IN ($in) AND clave IN ('al2o3','fe2o3','blancura','humedad','d50','sio2','ph','mbi')
             ORDER BY material_id ASC, orden ASC",
            $ids
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['material_id']][] = $row;
        }
        return $out;
    }
}
