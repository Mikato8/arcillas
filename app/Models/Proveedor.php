<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Proveedor
{
    /** @return array<int,array<string,mixed>> */
    public static function todos(bool $soloActivos = true): array
    {
        $where = $soloActivos ? 'WHERE s.activo = 1' : '';
        return Database::fetchAll(
            'SELECT s.*, (SELECT COUNT(*) FROM material_suppliers ms
                           INNER JOIN materials m ON m.id = ms.material_id AND m.activo = 1
                           WHERE ms.supplier_id = s.id AND ms.activo = 1) AS total
             FROM suppliers s ' . $where . '
             ORDER BY s.verificado DESC, s.nombre ASC'
        );
    }

    /** @return array<string,mixed>|null */
    public static function porSlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM suppliers WHERE slug = ? AND activo = 1 LIMIT 1', [$slug]);
    }

    /** @return array<string,mixed>|null */
    public static function porId(int $id): ?array
    {
        return Database::fetch('SELECT * FROM suppliers WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function materiales(int $supplierId): array
    {
        return Database::fetchAll(
            'SELECT m.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug,
                    ms.disponibilidad AS disponibilidad_oferta, ms.precio_ref AS precio_oferta,
                    ms.moneda AS moneda_oferta, ms.unidad AS unidad_oferta, ms.minimo, ms.notas
             FROM material_suppliers ms
             INNER JOIN materials m ON m.id = ms.material_id AND m.activo = 1
             INNER JOIN categories c ON c.id = m.category_id
             WHERE ms.supplier_id = ? AND ms.activo = 1
             ORDER BY ms.es_principal DESC, m.nombre ASC',
            [$supplierId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function destacados(int $limite = 4): array
    {
        return Database::fetchAll(
            'SELECT s.*, (SELECT COUNT(*) FROM material_suppliers ms WHERE ms.supplier_id = s.id) AS total
             FROM suppliers s WHERE s.activo = 1 AND s.verificado = 1
             ORDER BY s.nombre ASC LIMIT ' . max(1, $limite)
        );
    }
}
