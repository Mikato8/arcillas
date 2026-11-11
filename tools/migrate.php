<?php
/**
 * Migración y siembra de Arcillas MX.
 *
 *   php tools/migrate.php            # crea tablas si no existen
 *   php tools/migrate.php --seed     # además siembra el catálogo demo
 *   php tools/migrate.php --fresh    # elimina tablas y recrea (¡BORRA DATOS!)
 *   php tools/migrate.php --status   # estado de tablas y conteos
 *
 * Usa las variables DB_* del .env.
 */
declare(strict_types=1);

define('APP_SKIP_DISPATCH', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Env;

$opciones = $argv ?? [];
$fresh = in_array('--fresh', $opciones, true);
$seed = in_array('--seed', $opciones, true) || $fresh;
$status = in_array('--status', $opciones, true);

try {
    $pdo = Database::pdo();
} catch (Throwable $e) {
    fwrite(STDERR, "✖ No se pudo conectar a MySQL: " . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, "  Revisa DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS en .env" . PHP_EOL);
    exit(1);
}

$dbName = (string) Env::get('DB_NAME', '');

if ($status) {
    $tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Base de datos: {$dbName}" . PHP_EOL;
    foreach ($tablas as $tabla) {
        $total = (int) $pdo->query('SELECT COUNT(*) FROM `' . $tabla . '`')->fetchColumn();
        printf("  %-22s %6d registros\n", $tabla, $total);
    }
    exit(0);
}

if ($fresh) {
    echo "▶ Eliminando tablas existentes…" . PHP_EOL;
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $tabla) {
        $pdo->exec('DROP TABLE IF EXISTS `' . $tabla . '`');
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

$schemaFile = APP_BASE . '/database/schema.sql';
if (!is_file($schemaFile)) {
    fwrite(STDERR, "✖ Falta database/schema.sql" . PHP_EOL);
    exit(1);
}

echo "▶ Aplicando esquema…" . PHP_EOL;
$sql = (string) file_get_contents($schemaFile);
executarScript($pdo, $sql);
echo "  ✓ Tablas listas" . PHP_EOL;

if ($seed) {
    $seeder = APP_BASE . '/database/seed.php';
    if (is_file($seeder)) {
        echo "▶ Sembrando catálogo…" . PHP_EOL;
        $comando = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($seeder) . ' --force';
        passthru($comando, $codigo);
        if ($codigo !== 0) {
            fwrite(STDERR, "✖ El seeder terminó con código {$codigo}" . PHP_EOL);
            exit($codigo);
        }
    } else {
        fwrite(STDERR, "⚠ No existe database/seed.php; se omite la siembra." . PHP_EOL);
    }
}

// Garantiza un administrador si no existe ninguno.
$admins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE rol = 'admin'")->fetchColumn();
if ($admins === 0) {
    $email = (string) Env::get('ADMIN_EMAIL', 'admin@arcillas.mx');
    $password = 'ArcillasAdmin2026$';
    $stmt = $pdo->prepare('INSERT INTO users (nombre, email, password_hash, rol, activo) VALUES (?, ?, ?, "admin", 1)');
    $stmt->execute(['Administrador', $email, password_hash($password, PASSWORD_DEFAULT)]);
    echo "⚠ No había administradores. Creado {$email} con contraseña temporal {$password}" . PHP_EOL;
    echo "  Cámbiala en /admin/usuarios después del primer acceso." . PHP_EOL;
}

echo "✓ Migración terminada." . PHP_EOL;
echo "  Recuerda: la contraseña del administrador demo debe cambiarse antes de publicar." . PHP_EOL;

/**
 * Ejecuta un script SQL separando por punto y coma (ignora comentarios).
 */
function executarScript(PDO $pdo, string $sql): void
{
    $lineas = preg_split('/\R/', $sql) ?: [];
    $buffer = '';
    foreach ($lineas as $linea) {
        $trim = trim($linea);
        if ($trim === '' || str_starts_with($trim, '--')) {
            continue;
        }
        $buffer .= $linea . "\n";
        if (str_ends_with($trim, ';')) {
            $sentencia = trim($buffer);
            $buffer = '';
            if ($sentencia !== '' && $sentencia !== ';') {
                $pdo->exec($sentencia);
            }
        }
    }
    if (trim($buffer) !== '') {
        $pdo->exec(trim($buffer));
    }
}
