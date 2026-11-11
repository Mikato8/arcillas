<?php
declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════════════
 * Arcillas MX · Generador de database/seed.sql a partir del catálogo JSON
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Uso:
 *   php tools/generar_seed_sql.php             Genera database/seed.sql
 *   php tools/generar_seed_sql.php --check-db  Genera y además valida el SQL
 *                                              ejecutándolo en una transacción
 *                                              con ROLLBACK (requiere esquema).
 *
 * El SQL usa INSERT ... ON DUPLICATE KEY UPDATE (idempotente) y está pensado
 * para desplegar el catálogo sin ejecutar PHP en el servidor:
 *
 *   mysql -h HOST -P PORT -u USER -p NOMBRE_BD < database/seed.sql
 *
 * Requiere que database/schema.sql ya esté aplicado.
 * Proveedores, certificaciones y precios son DEMO / referencia, no ofertas.
 */

define('GEN_ROOT', dirname(__DIR__));
define('GEN_CATALOGO', GEN_ROOT . '/resources/data/catalogo.json');
define('GEN_SALIDA', GEN_ROOT . '/database/seed.sql');
const GEN_ADMIN_EMAIL = 'admin@arcillas.mx';
const GEN_ADMIN_PASS = 'ArcillasAdmin2026$';
/** Hash bcrypt fijo de GEN_ADMIN_PASS (el seed.php genera uno nuevo solo al crear). */
const GEN_ADMIN_HASH = '$2y$12$Q2lCXeR6zZYgUe.qkaBe1.A1fqKSOQdlwn0KWfsf41/IcYx4QGYOC';

/** Escapa una cadena para SQL de MariaDB/MySQL (utf8mb4). */
function sql_str(?string $valor): string
{
    if ($valor === null) {
        return 'NULL';
    }
    $reemplazos = [
        '\\' => '\\\\',
        "\0" => '\\0',
        "\n" => '\\n',
        "\r" => '\\r',
        "'" => "\\'",
        "\x1a" => '\\Z',
    ];
    return "'" . strtr($valor, $reemplazos) . "'";
}

/** Formatea un valor escalar para SQL. */
function sql_val(mixed $valor): string
{
    if ($valor === null) {
        return 'NULL';
    }
    if (is_bool($valor)) {
        return $valor ? '1' : '0';
    }
    if (is_int($valor)) {
        return (string) $valor;
    }
    if (is_float($valor)) {
        $texto = number_format($valor, 4, '.', '');
        $texto = rtrim(rtrim($texto, '0'), '.');
        return $texto === '' || $texto === '-0' ? '0' : $texto;
    }
    return sql_str((string) $valor);
}

/** @param list<string> $columnas @param array<string,mixed> $fila */
function sql_insert(string $tabla, array $columnas, array $fila, array $actualizar): string
{
    $vals = [];
    foreach ($columnas as $columna) {
        $vals[] = sql_val($fila[$columna] ?? null);
    }
    $sets = [];
    foreach ($actualizar as $columna) {
        $sets[] = "`{$columna}` = VALUES(`{$columna}`)";
    }
    return sprintf(
        'INSERT INTO `%s` (`%s`) VALUES (%s) ON DUPLICATE KEY UPDATE %s;',
        $tabla,
        implode('`, `', $columnas),
        implode(', ', $vals),
        implode(', ', $sets)
    );
}

/** @param array<string,mixed> $m */
function sql_sub(string $tabla, string $slug): string
{
    return "(SELECT id FROM `{$tabla}` WHERE slug = " . sql_str($slug) . ' LIMIT 1)';
}

/**
 * Valida el catálogo antes de generar SQL.
 *
 * @param array<string,mixed> $d
 * @return list<string>
 */
function validar_catalogo(array $d): array
{
    $errores = [];
    foreach (['industrias', 'categorias', 'proveedores', 'materiales'] as $clave) {
        if (!isset($d[$clave]) || !is_array($d[$clave])) {
            $errores[] = "Falta el arreglo '{$clave}'.";
        }
    }
    if ($errores !== []) {
        return $errores;
    }
    if (count($d['industrias']) !== 10) {
        $errores[] = 'Se esperaban 10 industrias.';
    }
    if (count($d['categorias']) !== 12) {
        $errores[] = 'Se esperaban 12 categorías.';
    }
    if (count($d['proveedores']) !== 10) {
        $errores[] = 'Se esperaban 10 proveedores.';
    }
    if (count($d['materiales']) < 50 || count($d['materiales']) > 60) {
        $errores[] = 'Se esperaban 50-60 materiales.';
    }
    $catSlugs = array_map(static fn ($c) => (string) $c['slug'], $d['categorias']);
    $indSlugs = array_map(static fn ($i) => (string) $i['slug'], $d['industrias']);
    $provSlugs = array_map(static fn ($p) => (string) $p['slug'], $d['proveedores']);
    $slugs = [];
    $destacados = 0;
    foreach ($d['materiales'] as $m) {
        $slug = (string) ($m['slug'] ?? '');
        if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $errores[] = "Material con slug inválido: '{$slug}'.";
            continue;
        }
        if (isset($slugs[$slug])) {
            $errores[] = "Material duplicado: {$slug}.";
        }
        $slugs[$slug] = true;
        foreach (['category_slug', 'nombre', 'resumen', 'descripcion', 'disponibilidad',
                     'moneda', 'unidad', 'propiedades', 'proveedores', 'industrias'] as $campo) {
            if (!array_key_exists($campo, $m)) {
                $errores[] = "Material {$slug}: falta '{$campo}'.";
            }
        }
        if (!in_array($m['category_slug'] ?? '', $catSlugs, true)) {
            $errores[] = "Material {$slug}: categoría inexistente.";
        }
        foreach ((array) ($m['proveedores'] ?? []) as $p) {
            if (!in_array($p, $provSlugs, true)) {
                $errores[] = "Material {$slug}: proveedor '{$p}' inexistente.";
            }
        }
        foreach ((array) ($m['industrias'] ?? []) as $i) {
            if (!in_array($i, $indSlugs, true)) {
                $errores[] = "Material {$slug}: industria '{$i}' inexistente.";
            }
        }
        $props = (array) ($m['propiedades'] ?? []);
        if (count($props) < 6 || count($props) > 12) {
            $errores[] = "Material {$slug}: " . count($props) . ' propiedades (6-12).';
        }
        foreach ($props as $p) {
            foreach (['clave', 'nombre', 'grupo', 'valor', 'unidad'] as $campo) {
                if (!array_key_exists($campo, $p)) {
                    $errores[] = "Material {$slug}: propiedad sin '{$campo}'.";
                }
            }
            if (isset($p['unidad']) && !is_string($p['unidad'])) {
                $errores[] = "Material {$slug}: unidad de propiedad inválida.";
            }
        }
        if (empty($m['precio_ref'])) {
            // precio null es válido
        } elseif (!is_numeric($m['precio_ref']) || (float) $m['precio_ref'] <= 0) {
            $errores[] = "Material {$slug}: precio_ref inválido.";
        }
        if (!empty($m['destacado'])) {
            $destacados++;
        }
    }
    if ($destacados !== 6) {
        $errores[] = "Se esperaban 6 destacados; hay {$destacados}.";
    }
    if (!password_verify(GEN_ADMIN_PASS, GEN_ADMIN_HASH)) {
        $errores[] = 'El hash del administrador demo no corresponde a la contraseña.';
    }
    return $errores;
}

// ─────────────────────────────────────────────────────────────────────
// Carga
// ─────────────────────────────────────────────────────────────────────
$checkDb = in_array('--check-db', array_slice($argv, 1), true);
foreach (array_slice($argv, 1) as $arg) {
    if (!in_array($arg, ['--check-db', '--help', '-h'], true)) {
        fwrite(STDERR, "Opción desconocida: {$arg}\n");
        exit(2);
    }
    if (in_array($arg, ['--help', '-h'], true)) {
        echo "Uso: php tools/generar_seed_sql.php [--check-db]\n";
        exit(0);
    }
}

if (!is_file(GEN_CATALOGO)) {
    fwrite(STDERR, '✗ No existe ' . GEN_CATALOGO . PHP_EOL);
    exit(1);
}
try {
    $catalogo = json_decode((string) file_get_contents(GEN_CATALOGO), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    fwrite(STDERR, '✗ JSON inválido: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
$errores = validar_catalogo($catalogo);
if ($errores !== []) {
    foreach ($errores as $e) {
        fwrite(STDERR, "✗ {$e}\n");
    }
    exit(1);
}

// ─────────────────────────────────────────────────────────────────────
// Construcción de sentencias
// ─────────────────────────────────────────────────────────────────────
$sentencias = [];
$ahora = gmdate('Y-m-d\TH:i:s\Z');
$totalProps = 0;
foreach ($catalogo['materiales'] as $m) {
    $totalProps += count($m['propiedades']);
}

$sentencias[] = '-- Usuario administrador demo: solo se crea si no existe; no sobrescribe contraseña.';
$sentencias[] = 'INSERT INTO `users` (`nombre`, `email`, `password_hash`, `rol`, `activo`) VALUES ('
    . sql_str('Administrador Arcillas MX') . ', ' . sql_str(GEN_ADMIN_EMAIL) . ', '
    . sql_str(GEN_ADMIN_HASH) . ", 'admin', 1) ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);";

$sentencias[] = '';
$sentencias[] = '-- Settings base del sitio.';
foreach ($catalogo['settings'] as $s) {
    $sentencias[] = sql_insert('settings', ['clave', 'valor', 'grupo', 'etiqueta', 'tipo'], $s,
        ['valor', 'grupo', 'etiqueta', 'tipo']);
}

$sentencias[] = '';
$sentencias[] = '-- Industrias.';
foreach ($catalogo['industrias'] as $i) {
    $sentencias[] = sql_insert('industries',
        ['slug', 'nombre', 'descripcion', 'icono', 'color', 'orden', 'activo'],
        $i + ['activo' => $i['activo'] ?? 1],
        ['nombre', 'descripcion', 'icono', 'color', 'orden', 'activo']);
}

$sentencias[] = '';
$sentencias[] = '-- Categorías.';
foreach ($catalogo['categorias'] as $c) {
    $sentencias[] = sql_insert('categories',
        ['slug', 'nombre', 'descripcion', 'familia', 'orden', 'activo'],
        $c + ['activo' => $c['activo'] ?? 1],
        ['nombre', 'descripcion', 'familia', 'orden', 'activo']);
}

$sentencias[] = '';
$sentencias[] = '-- Proveedores DEMO.';
foreach ($catalogo['proveedores'] as $p) {
    $fila = $p + [
        'pais' => 'México', 'ciudad' => null, 'sitio_web' => null, 'email_publico' => null,
        'telefono_publico' => null, 'logo' => null, 'verificado' => 0, 'es_demo' => 1, 'activo' => 1,
    ];
    $sentencias[] = sql_insert('suppliers',
        ['slug', 'nombre', 'tipo', 'descripcion', 'pais', 'estado', 'ciudad', 'capacidad',
            'certificaciones', 'sitio_web', 'email_publico', 'telefono_publico', 'logo',
            'verificado', 'es_demo', 'activo'],
        $fila,
        ['nombre', 'tipo', 'descripcion', 'pais', 'estado', 'ciudad', 'capacidad',
            'certificaciones', 'verificado', 'es_demo', 'activo']);
}

$sentencias[] = '';
$sentencias[] = '-- Materiales: category_id se resuelve por slug de categoría.';
foreach ($catalogo['materiales'] as $m) {
    $fila = $m + [
        'imagen' => null, 'es_demo' => 1, 'activo' => 1,
        'meta_title' => $m['meta_title'] ?? null,
        'meta_description' => $m['meta_description'] ?? null,
    ];
    $sentencias[] = 'INSERT INTO `materials` (`category_id`, `nombre`, `slug`, `resumen`, `descripcion`, '
        . '`origen`, `aplicaciones`, `presentacion`, `disponibilidad`, `region`, `precio_ref`, '
        . '`moneda`, `unidad`, `imagen`, `destacado`, `es_demo`, `meta_title`, `meta_description`, `activo`) VALUES ('
        . sql_sub('categories', $m['category_slug']) . ', '
        . sql_str($fila['nombre']) . ', ' . sql_str($fila['slug']) . ', '
        . sql_str($fila['resumen']) . ', ' . sql_str($fila['descripcion']) . ', '
        . sql_str($fila['origen']) . ', ' . sql_str($fila['aplicaciones']) . ', '
        . sql_str($fila['presentacion']) . ', ' . sql_str($fila['disponibilidad']) . ', '
        . sql_str($fila['region']) . ', ' . sql_val($fila['precio_ref']) . ', '
        . sql_str($fila['moneda']) . ', ' . sql_str($fila['unidad']) . ', '
        . sql_val($fila['imagen']) . ', ' . sql_val((int) $fila['destacado']) . ', 1, '
        . sql_str($fila['meta_title']) . ', ' . sql_str($fila['meta_description']) . ', 1) '
        . 'ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`), `nombre` = VALUES(`nombre`), '
        . '`resumen` = VALUES(`resumen`), `descripcion` = VALUES(`descripcion`), '
        . '`origen` = VALUES(`origen`), `aplicaciones` = VALUES(`aplicaciones`), '
        . '`presentacion` = VALUES(`presentacion`), `disponibilidad` = VALUES(`disponibilidad`), '
        . '`region` = VALUES(`region`), `precio_ref` = VALUES(`precio_ref`), '
        . '`moneda` = VALUES(`moneda`), `unidad` = VALUES(`unidad`), `destacado` = VALUES(`destacado`), '
        . '`es_demo` = VALUES(`es_demo`), `meta_title` = VALUES(`meta_title`), '
        . '`meta_description` = VALUES(`meta_description`), `activo` = VALUES(`activo`);';
}

$sentencias[] = '';
$sentencias[] = '-- Propiedades técnicas (EAV).';
foreach ($catalogo['materiales'] as $m) {
    foreach ($m['propiedades'] as $p) {
        $sentencias[] = 'INSERT INTO `material_properties` (`material_id`, `grupo`, `clave`, `nombre`, '
            . '`valor`, `valor_texto`, `unidad`, `orden`) VALUES ('
            . sql_sub('materials', $m['slug']) . ', '
            . sql_str($p['grupo']) . ', ' . sql_str($p['clave']) . ', ' . sql_str($p['nombre']) . ', '
            . sql_val($p['valor']) . ', ' . sql_val($p['valor_texto'] ?? null) . ', '
            . sql_str($p['unidad']) . ', ' . sql_val((int) ($p['orden'] ?? 0)) . ') '
            . 'ON DUPLICATE KEY UPDATE `grupo` = VALUES(`grupo`), `nombre` = VALUES(`nombre`), '
            . '`valor` = VALUES(`valor`), `valor_texto` = VALUES(`valor_texto`), '
            . '`unidad` = VALUES(`unidad`), `orden` = VALUES(`orden`);';
    }
}

$sentencias[] = '';
$sentencias[] = '-- Relación material ↔ industria.';
foreach ($catalogo['materiales'] as $m) {
    foreach ($m['industrias'] as $slugIndustria) {
        $sentencias[] = 'INSERT INTO `material_industries` (`material_id`, `industry_id`, `aplicacion`) VALUES ('
            . sql_sub('materials', $m['slug']) . ', ' . sql_sub('industries', $slugIndustria) . ', NULL) '
            . 'ON DUPLICATE KEY UPDATE `aplicacion` = VALUES(`aplicacion`);';
    }
}

$sentencias[] = '';
$sentencias[] = '-- Relación material ↔ proveedor (principal = primer proveedor del JSON).';
foreach ($catalogo['materiales'] as $m) {
    foreach (array_values($m['proveedores']) as $indice => $slugProveedor) {
        $fila = [
            'material_id' => sql_sub('materials', $m['slug']),
            'supplier_id' => sql_sub('suppliers', $slugProveedor),
            'es_principal' => $indice === 0 ? 1 : 0,
            'disponibilidad' => $m['disponibilidad'],
            'precio_ref' => $m['precio_ref'],
            'moneda' => $m['moneda'],
            'unidad' => $m['unidad'],
            'minimo' => '1 t (demo)',
            'notas' => 'Proveedor DEMO; precio de referencia, no oferta.',
            'activo' => 1,
        ];
        $sentencias[] = 'INSERT INTO `material_suppliers` (`material_id`, `supplier_id`, `es_principal`, '
            . '`disponibilidad`, `precio_ref`, `moneda`, `unidad`, `minimo`, `notas`, `activo`) VALUES ('
            . $fila['material_id'] . ', ' . $fila['supplier_id'] . ', '
            . sql_val($fila['es_principal']) . ', ' . sql_str($fila['disponibilidad']) . ', '
            . sql_val($fila['precio_ref']) . ', ' . sql_str($fila['moneda']) . ', '
            . sql_str($fila['unidad']) . ', ' . sql_str($fila['minimo']) . ', '
            . sql_str($fila['notas']) . ', 1) '
            . 'ON DUPLICATE KEY UPDATE `es_principal` = VALUES(`es_principal`), '
            . '`disponibilidad` = VALUES(`disponibilidad`), `precio_ref` = VALUES(`precio_ref`), '
            . '`moneda` = VALUES(`moneda`), `unidad` = VALUES(`unidad`), `minimo` = VALUES(`minimo`), '
            . '`notas` = VALUES(`notas`), `activo` = VALUES(`activo`);';
    }
}

$totalMateriales = count($catalogo['materiales']);
$totalProveedores = count($catalogo['proveedores']);
$totalRelaciones = 0;
foreach ($catalogo['materiales'] as $m) {
    $totalRelaciones += count($m['proveedores']) + count($m['industrias']);
}

$sql = "-- ═══════════════════════════════════════════════════════════════════════\n"
    . "-- Arcillas MX · Semilla del catálogo (generada por tools/generar_seed_sql.php)\n"
    . "-- Generada: {$ahora}\n"
    . "-- Fuente: resources/data/catalogo.json\n"
    . "--\n"
    . "-- CONTENIDO: {$totalMateriales} materiales, {$totalProps} propiedades, "
    . "{$totalProveedores} proveedores DEMO, "
    . count($catalogo['industrias']) . " industrias, " . count($catalogo['categorias']) . " categorías, "
    . count($catalogo['settings']) . " settings.\n"
    . "-- ADVERTENCIA: proveedores, certificaciones y precios son DEMO / referencia, no ofertas.\n"
    . "-- Idempotente: INSERT ... ON DUPLICATE KEY UPDATE. No elimina leads ni usuarios.\n"
    . "-- Requiere database/schema.sql aplicado previamente.\n"
    . "-- Uso: mysql -h HOST -P PORT -u USER -p NOMBRE_BD < database/seed.sql\n"
    . "-- ═══════════════════════════════════════════════════════════════════════\n\n"
    . "SET NAMES utf8mb4;\n"
    . "SET time_zone = '+00:00';\n\n"
    . "START TRANSACTION;\n\n"
    . implode("\n", $sentencias) . "\n\n"
    . "COMMIT;\n\n"
    . "-- Fin de la semilla.\n";

if (file_put_contents(GEN_SALIDA, $sql) === false) {
    fwrite(STDERR, '✗ No se pudo escribir ' . GEN_SALIDA . PHP_EOL);
    exit(1);
}

printf("✓ seed.sql generado: %s\n", GEN_SALIDA);
printf("  Tamaño          : %s bytes\n", number_format((float) filesize(GEN_SALIDA)));
printf("  Sentencias      : %d\n", count($sentencias));
printf("  Materiales      : %d\n", $totalMateriales);
printf("  Propiedades     : %d\n", $totalProps);
printf("  Proveedores     : %d (DEMO)\n", $totalProveedores);
printf("  Relaciones      : %d (industria+proveedor)\n", $totalRelaciones);
printf("  Settings        : %d\n", count($catalogo['settings']));
printf("  Destacados      : %d\n", count(array_filter($catalogo['materiales'], static fn ($m) => !empty($m['destacado']))));
printf("  Comillas simples: %d cadenas escapadas con \\'\n", substr_count($sql, "\\'"));

if (!$checkDb) {
    echo "\nSiguiente paso: mysql -h HOST -P PORT -u USER -p BD < database/seed.sql\n";
    exit(0);
}

// ── Verificación opcional contra la BD ──────────────────────────────
/** @return array<string,string> */
function gen_env(): array
{
    $vars = [];
    $ruta = GEN_ROOT . '/.env';
    if (!is_file($ruta)) {
        return $vars;
    }
    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $linea, 2);
        $k = trim($k);
        $v = trim($v);
        if (strlen($v) >= 2 && (($v[0] === '"' && $v[-1] === '"') || ($v[0] === "'" && $v[-1] === "'"))) {
            $v = substr($v, 1, -1);
        }
        $vars[$k] = $v;
    }
    return $vars;
}

$env = gen_env();
$get = static function (string $k, string $def = '') use ($env): string {
    $e = getenv($k);
    return ($e !== false && $e !== '') ? (string) $e : ($env[$k] ?? $def);
};
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $get('DB_HOST', '127.0.0.1'), $get('DB_PORT', '3306'), $get('DB_NAME'));
try {
    $pdo = new PDO($dsn, $get('DB_USER'), $get('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, '✗ --check-db: no se pudo conectar: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
$tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('materials', $tablas, true)) {
    fwrite(STDERR, "✗ --check-db: falta el esquema (tabla materials). Aplica database/schema.sql.\n");
    exit(1);
}
$pdo->beginTransaction();
try {
    foreach ($sentencias as $sentencia) {
        if (trim($sentencia) === '' || str_starts_with(trim($sentencia), '--')) {
            continue;
        }
        $pdo->exec($sentencia);
    }
    $pdo->rollBack();
    echo "\n✓ --check-db OK: las " . count($sentencias) . " sentencias se ejecutaron en una transacción y se revirtieron.\n";
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "\n✗ --check-db FALLÓ: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
