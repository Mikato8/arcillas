<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Lead
{
    public const TIPOS = ['cotizacion', 'muestra', 'contacto', 'proveedor'];

    /**
     * Inserta un lead y sus ítems de muestra.
     *
     * @param array<string,mixed> $datos
     * @param array<int,array{material_id:int,cantidad?:string,unidad?:string,nota?:string}> $items
     */
    public static function crear(array $datos, array $items = []): int
    {
        $limpio = [
            'tipo'             => in_array(($datos['tipo'] ?? ''), self::TIPOS, true) ? (string) $datos['tipo'] : 'contacto',
            'nombre'           => trim((string) ($datos['nombre'] ?? '')),
            'empresa'          => self::nullable($datos['empresa'] ?? null),
            'cargo'            => self::nullable($datos['cargo'] ?? null),
            'email'            => strtolower(trim((string) ($datos['email'] ?? ''))),
            'telefono'         => self::nullable($datos['telefono'] ?? null),
            'whatsapp'         => self::nullable($datos['whatsapp'] ?? null),
            'material_id'      => isset($datos['material_id']) && (int) $datos['material_id'] > 0 ? (int) $datos['material_id'] : null,
            'material_texto'   => self::nullable($datos['material_texto'] ?? null),
            'volumen'          => self::nullable($datos['volumen'] ?? null),
            'unidad'           => self::nullable($datos['unidad'] ?? null),
            'destino'          => self::nullable($datos['destino'] ?? null),
            'frecuencia'       => self::nullable($datos['frecuencia'] ?? null),
            'especificaciones' => self::nullable($datos['especificaciones'] ?? null),
            'mensaje'          => self::nullable($datos['mensaje'] ?? null),
            'consentimiento'   => !empty($datos['consentimiento']) ? 1 : 0,
            'estado'           => 'nuevo',
            'fuente'           => self::nullable($datos['fuente'] ?? null),
            'ip'               => self::nullable($datos['ip'] ?? null),
            'user_agent'       => self::nullable($datos['user_agent'] ?? null),
        ];

        $id = Database::insert('leads', $limpio);

        foreach ($items as $item) {
            $materialId = (int) ($item['material_id'] ?? 0);
            if ($materialId <= 0) {
                continue;
            }
            Database::insert('lead_items', [
                'lead_id'     => $id,
                'material_id' => $materialId,
                'cantidad'    => self::nullable($item['cantidad'] ?? null),
                'unidad'      => self::nullable($item['unidad'] ?? null),
                'nota'        => self::nullable($item['nota'] ?? null),
            ]);
        }

        Database::run('INSERT INTO logs (accion, entidad, entidad_id, detalle, ip) VALUES (?, ?, ?, ?, ?)', [
            'lead_creado',
            'leads',
            (string) $id,
            'Nuevo lead ' . $limpio['tipo'] . ' de ' . $limpio['email'],
            $limpio['ip'],
        ]);

        return $id;
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : mb_substr($value, 0, 190);
    }

    /**
     * Valida el formulario según el tipo de lead.
     *
     * @param array<string,mixed> $datos
     * @return array<string,string> errores por campo
     */
    public static function validar(array $datos, string $tipo): array
    {
        $errores = [];

        if (trim((string) ($datos['nombre'] ?? '')) === '') {
            $errores['nombre'] = 'Indica tu nombre.';
        }
        $email = trim((string) ($datos['email'] ?? ''));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errores['email'] = 'Escribe un correo electrónico válido.';
        }
        if (empty($datos['consentimiento'])) {
            $errores['consentimiento'] = 'Debes aceptar el aviso de privacidad.';
        }

        if (in_array($tipo, ['cotizacion', 'muestra', 'proveedor'], true)
            && trim((string) ($datos['empresa'] ?? '')) === '') {
            $errores['empresa'] = 'Indica tu empresa.';
        }

        if (in_array($tipo, ['cotizacion', 'muestra'], true)) {
            $material = trim((string) ($datos['material_texto'] ?? ''));
            if ($material === '' && empty($datos['material_id'])) {
                $errores['material_texto'] = 'Indica el mineral o material que te interesa.';
            }
            if (trim((string) ($datos['volumen'] ?? '')) === '') {
                $errores['volumen'] = 'Indica el volumen estimado.';
            }
        }

        if ($tipo === 'proveedor') {
            if (trim((string) ($datos['material_texto'] ?? '')) === '') {
                $errores['material_texto'] = 'Indica el mineral o material que ofreces.';
            }
            if (trim((string) ($datos['destino'] ?? '')) === '') {
                $errores['destino'] = 'Indica la ubicación de la mina o planta.';
            }
            if (trim((string) ($datos['mensaje'] ?? '')) === '') {
                $errores['mensaje'] = 'Cuéntanos capacidad, especificaciones y documentación disponible.';
            }
        }

        if ($tipo === 'contacto' && trim((string) ($datos['mensaje'] ?? '')) === '') {
            $errores['mensaje'] = 'Escribe tu mensaje.';
        }

        // Longitud mínima del mensaje cuando aplica, para frenar spam trivial.
        if (isset($datos['mensaje']) && trim((string) $datos['mensaje']) !== '' && mb_strlen(trim((string) $datos['mensaje'])) < 10) {
            $errores['mensaje'] = 'Amplía un poco tu mensaje (mínimo 10 caracteres).';
        }

        return $errores;
    }

    /**
     * Bandeja administrativa con filtros.
     *
     * @param array<string,mixed> $filtros tipo, estado, q, desde, hasta, pagina
     * @return array{items: array<int,array<string,mixed>>, total: int, pagina: int, paginas: int}
     */
    public static function listar(array $filtros = [], int $porPagina = 25): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['tipo'])) {
            $where[] = 'l.tipo = :tipo';
            $params['tipo'] = (string) $filtros['tipo'];
        }
        if (!empty($filtros['estado'])) {
            $where[] = 'l.estado = :estado';
            $params['estado'] = (string) $filtros['estado'];
        }
        if (!empty($filtros['q'])) {
            $where[] = '(l.nombre LIKE :q OR l.empresa LIKE :q OR l.email LIKE :q OR l.material_texto LIKE :q)';
            $params['q'] = '%' . trim((string) $filtros['q']) . '%';
        }
        if (!empty($filtros['desde'])) {
            $where[] = 'l.creado_en >= :desde';
            $params['desde'] = (string) $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $where[] = 'l.creado_en <= :hasta';
            $params['hasta'] = (string) $filtros['hasta'] . ' 23:59:59';
        }

        $pagina = max(1, (int) ($filtros['pagina'] ?? 1));
        $offset = ($pagina - 1) * $porPagina;

        $total = (int) Database::scalar('SELECT COUNT(*) FROM leads l WHERE ' . implode(' AND ', $where), $params);

        $items = Database::fetchAll(
            'SELECT l.*, m.nombre AS material_nombre, m.slug AS material_slug
             FROM leads l
             LEFT JOIN materials m ON m.id = l.material_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY l.creado_en DESC, l.id DESC
             LIMIT ' . $porPagina . ' OFFSET ' . $offset,
            $params
        );

        return [
            'items'   => $items,
            'total'   => $total,
            'pagina'  => $pagina,
            'paginas' => max(1, (int) ceil($total / $porPagina)),
        ];
    }

    /** @return array<string,mixed>|null */
    public static function porId(int $id): ?array
    {
        return Database::fetch(
            'SELECT l.*, m.nombre AS material_nombre, m.slug AS material_slug, u.nombre AS atendido_nombre
             FROM leads l
             LEFT JOIN materials m ON m.id = l.material_id
             LEFT JOIN users u ON u.id = l.atendido_por
             WHERE l.id = ? LIMIT 1',
            [$id]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function items(int $leadId): array
    {
        return Database::fetchAll(
            'SELECT li.*, m.nombre, m.slug FROM lead_items li
             INNER JOIN materials m ON m.id = li.material_id
             WHERE li.lead_id = ?',
            [$leadId]
        );
    }

    /** @param array<string,mixed> $datos */
    public static function actualizar(int $id, array $datos): void
    {
        $permitidos = ['estado', 'notas_internas', 'atendido_por'];
        $limpio = array_intersect_key($datos, array_flip($permitidos));
        if ($limpio !== []) {
            Database::update('leads', $limpio, ['id' => $id]);
        }
    }

    /** @return array<string,int|float> */
    public static function estadisticas(): array
    {
        $total = (int) Database::scalar('SELECT COUNT(*) FROM leads');
        $nuevos = (int) Database::scalar("SELECT COUNT(*) FROM leads WHERE estado = 'nuevo'");
        $mes = (int) Database::scalar("SELECT COUNT(*) FROM leads WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $hoy = (int) Database::scalar('SELECT COUNT(*) FROM leads WHERE DATE(creado_en) = CURDATE()');
        return ['total' => $total, 'nuevos' => $nuevos, 'mes' => $mes, 'hoy' => $hoy];
    }

    /** @return array<int,array<string,mixed>> */
    public static function exportables(array $filtros = []): array
    {
        $filtros['pagina'] = 1;
        return self::listar($filtros, 5000)['items'];
    }
}
