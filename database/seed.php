<?php
declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════════════
 * Arcillas MX · Seeder idempotente del catálogo semilla
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Lee resources/data/catalogo.json (fuente canónica) y siembra:
 * industrias, categorías, proveedores DEMO, materiales, propiedades EAV,
 * relaciones material↔industria y material↔proveedor, settings base y el
 * usuario administrador demo.
 *
 * Uso:
 *   php database/seed.php --dry-run   Valida el JSON y reporta conteos SIN BD.
 *   php database/seed.php             Inserta lo que falte; conserva lo existente.
 *   php database/seed.php --force     Además actualiza los registros semilla existentes.
 *   php database/seed.php --help
 *
 * Garantías:
 *   - No elimina ningún registro (ni leads, ni usuarios, ni materiales editados).
 *   - El usuario admin@arcillas.mx solo se crea si no existe; nunca se
 *     sobrescribe su contraseña.
 *   - Todas las escrituras van en una transacción.
 *   - Conexión por PDO usando DB_* del entorno o de .env.
 *
 * Este catálogo es DEMOSTRATIVO: proveedores, certificaciones y precios son
 * ficticios o referencias de literatura. Ver docs/datos-fuentes.md.
 */

const SEED_ROOT = __DIR__ . '/..';
const SEED_CATALOGO = SEED_ROOT . '/resources/data/catalogo.json';
const SEED_ADMIN_EMAIL = 'admin@arcillas.mx';
const SEED_ADMIN_PASS = 'ArcillasAdmin2026$';

/** Claves canónicas de propiedades: clave => [grupo, unidad]. */
const SEED_PROPS = [
    'sio2' => ['quimica', '%'], 'al2o3' => ['quimica', '%'], 'fe2o3' => ['quimica', '%'],
    'tio2' => ['quimica', '%'], 'k2o' => ['quimica', '%'], 'na2o' => ['quimica', '%'],
    'cao' => ['quimica', '%'], 'mgo' => ['quimica', '%'], 'loi' => ['quimica', '%'],
    'so3' => ['quimica', '%'], 'p2o5' => ['quimica', '%'], 'mno' => ['quimica', '%'],
    'fe' => ['quimica', '%'], 'cu' => ['quimica', '%'], 'zn' => ['quimica', '%'],
    'pb' => ['quimica', '%'], 'ag' => ['quimica', 'g/t'], 'au' => ['quimica', 'g/t'],
    'sb' => ['quimica', '%'], 'as' => ['quimica', '%'], 'mn' => ['quimica', '%'],
    'li2o' => ['quimica', '%'], 'wo3' => ['quimica', '%'], 'mos2' => ['quimica', '%'],
    'bi' => ['quimica', '%'], 'caf2' => ['quimica', '%'], 'baso4' => ['quimica', '%'],
    'pureza' => ['quimica', '%'], 's' => ['quimica', '%'],
    'ph' => ['fisica', 'pH'], 'densidad' => ['fisica', 'g/cm³'],
    'densidad_aparente' => ['fisica', 'g/cm³'], 'blancura' => ['fisica', '%'],
    'plasticidad' => ['fisica', 'IP'], 'mbi' => ['fisica', 'meq/100 g'],
    'absorcion' => ['fisica', '%'], 'refractario' => ['fisica', 'cono'],
    'mohs' => ['fisica', 'Mohs'], 'hinchamiento' => ['fisica', '%'],
    'superficie' => ['fisica', 'm²/g'], 'aceite' => ['fisica', 'g/100 g'],
    'temperatura_uso' => ['fisica', '°C'], 'cicec' => ['fisica', 'meq/100 g'],
    'd50' => ['granulometria', 'µm'], 'd97' => ['granulometria', 'µm'],
    'malla_60' => ['granulometria', '%'], 'malla_100' => ['granulometria', '%'],
    'malla_200' => ['granulometria', '%'], 'malla_325' => ['granulometria', '%'],
    'retenido_45' => ['granulometria', '%'], 'humedad' => ['granulometria', '%'],
    'saco_kg' => ['presentacion', 'kg'], 'bigbag_kg' => ['presentacion', 'kg'],
];

final class Seeder
{
    private bool $dryRun = false;
    private bool $force = false;
    /** @var array<string,mixed> */
    private array $data = [];
    /** @var list<string> */
    private array $errores = [];
    /** @var list<string> */
    private array $avisos = [];
    /** @var array<string,int> */
    private array $stats = [];
    private ?PDO $pdo = null;
    /** @var array<string,int> */
    private array $idsCategorias = [];
    /** @var array<string,int> */
    private array $idsIndustrias = [];
    /** @var array<string,int> */
    private array $idsProveedores = [];
    /** @var array<string,int> */
    private array $idsMateriales = [];

    /** @param list<string> $argv */
    public function __construct(array $argv)
    {
        foreach (array_slice($argv, 1) as $arg) {
            if ($arg === '--dry-run') {
                $this->dryRun = true;
            } elseif ($arg === '--force') {
                $this->force = true;
            } elseif ($arg === '--help' || $arg === '-h') {
                $this->ayuda();
                exit(0);
            } else {
                fwrite(STDERR, "Opción desconocida: {$arg}\n");
                $this->ayuda();
                exit(2);
            }
        }
    }

    private function ayuda(): void
    {
        echo <<<TXT
        Seeder de Arcillas MX

          php database/seed.php --dry-run   Valida el catálogo sin conectarse a la BD.
          php database/seed.php             Siembra lo que falte (idempotente).
          php database/seed.php --force     Actualiza también los registros existentes.
          php database/seed.php --help      Muestra esta ayuda.

        La conexión usa DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS y DB_CHARSET
        del entorno o del archivo .env. Requiere el esquema de database/schema.sql.

        TXT . PHP_EOL;
    }

    public function ejecutar(): int
    {
        $this->cargarCatalogo();
        if ($this->errores !== []) {
            $this->imprimirErrores();
            return 1;
        }
        $this->validar();

        if ($this->errores !== []) {
            $this->imprimirErrores();
            return 1;
        }

        if ($this->dryRun) {
            $this->reporteDryRun();
            return 0;
        }

        $this->reporteCatalogo('Sembrando catálogo en la base de datos');
        if (!$this->conectar()) {
            return 1;
        }
        if (!$this->verificarEsquema()) {
            return 1;
        }

        try {
            $this->pdo->beginTransaction();
            $this->sembrar();
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            fwrite(STDERR, "\n✗ Error durante la siembra: " . $e->getMessage() . "\n");
            fwrite(STDERR, "  Se revirtieron todos los cambios de esta ejecución.\n");
            return 1;
        }

        $this->reporteFinal();
        return 0;
    }

    // ── Carga y validación ───────────────────────────────────────────

    private function cargarCatalogo(): void
    {
        if (!is_file(SEED_CATALOGO)) {
            $this->errores[] = 'No existe el catálogo: ' . SEED_CATALOGO;
            return;
        }
        $crudo = file_get_contents(SEED_CATALOGO);
        if ($crudo === false) {
            $this->errores[] = 'No se pudo leer ' . SEED_CATALOGO;
            return;
        }
        try {
            $data = json_decode($crudo, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->errores[] = 'JSON inválido: ' . $e->getMessage();
            return;
        }
        if (!is_array($data)) {
            $this->errores[] = 'El catálogo debe ser un objeto JSON.';
            return;
        }
        foreach (['industrias', 'categorias', 'proveedores', 'materiales'] as $clave) {
            if (!isset($data[$clave]) || !is_array($data[$clave])) {
                $this->errores[] = "Falta el arreglo '{$clave}' en el catálogo.";
            }
        }
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            $data['settings'] = [];
            $this->avisos[] = "El catálogo no trae 'settings'; se omitirá su siembra.";
        }
        $this->data = $data;
    }

    private function validar(): void
    {
        $d = $this->data;
        $cuenta = static fn (string $k): int => count($d[$k] ?? []);
        if ($cuenta('industrias') !== 10) {
            $this->errores[] = 'Se esperaban 10 industrias; hay ' . $cuenta('industrias') . '.';
        }
        if ($cuenta('categorias') !== 12) {
            $this->errores[] = 'Se esperaban 12 categorías; hay ' . $cuenta('categorias') . '.';
        }
        if ($cuenta('proveedores') !== 10) {
            $this->errores[] = 'Se esperaban 10 proveedores; hay ' . $cuenta('proveedores') . '.';
        }
        if ($cuenta('materiales') < 50 || $cuenta('materiales') > 60) {
            $this->errores[] = 'Se esperaban entre 50 y 60 materiales; hay ' . $cuenta('materiales') . '.';
        }

        $catSlugs = $this->slugsUnicos($d['categorias'] ?? [], 'categorías', 'slug');
        $indSlugs = $this->slugsUnicos($d['industrias'] ?? [], 'industrias', 'slug');
        $provSlugs = $this->slugsUnicos($d['proveedores'] ?? [], 'proveedores', 'slug');

        $familias = ['arcillas', 'industrial', 'no_ferroso', 'carga'];
        foreach ($d['categorias'] ?? [] as $i => $c) {
            foreach (['slug', 'nombre', 'descripcion', 'familia', 'orden'] as $campo) {
                if (!array_key_exists($campo, $c)) {
                    $this->errores[] = "Categoría #{$i}: falta '{$campo}'.";
                }
            }
            if (isset($c['familia']) && !in_array($c['familia'], $familias, true)) {
                $this->errores[] = "Categoría {$c['slug']}: familia '{$c['familia']}' inválida.";
            }
            if (mb_strlen((string) ($c['nombre'] ?? '')) > 120) {
                $this->errores[] = "Categoría {$c['slug']}: nombre > 120 caracteres.";
            }
        }

        $disponibilidades = ['inmediata', 'programada', 'bajo_pedido', 'agotado'];
        $tiposProveedor = ['mina', 'planta', 'distribuidor', 'broker'];
        foreach ($d['proveedores'] ?? [] as $i => $p) {
            foreach (['slug', 'nombre', 'tipo', 'estado', 'capacidad', 'certificaciones'] as $campo) {
                if (!array_key_exists($campo, $p)) {
                    $this->errores[] = "Proveedor #{$i}: falta '{$campo}'.";
                }
            }
            if (isset($p['tipo']) && !in_array($p['tipo'], $tiposProveedor, true)) {
                $this->errores[] = "Proveedor {$p['slug']}: tipo '{$p['tipo']}' inválido.";
            }
            if (mb_strlen((string) ($p['nombre'] ?? '')) > 160) {
                $this->errores[] = "Proveedor {$p['slug']}: nombre > 160 caracteres.";
            }
            if (!str_ends_with((string) ($p['nombre'] ?? ''), 'Demo')) {
                $this->avisos[] = "Proveedor {$p['slug']}: el nombre no termina en 'Demo'.";
            }
        }

        $usadasCat = [];
        $usadasInd = [];
        $usadasProv = [];
        $destacados = 0;
        $totalProps = 0;
        $cat = array_fill_keys($catSlugs, 0);
        $monedas = [];
        foreach ($d['materiales'] ?? [] as $m) {
            $slug = (string) ($m['slug'] ?? '(sin slug)');
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                $this->errores[] = "Material {$slug}: el slug no es kebab-case ASCII.";
            }
            foreach (['category_slug', 'nombre', 'slug', 'resumen', 'descripcion', 'origen',
                      'aplicaciones', 'presentacion', 'disponibilidad', 'region', 'moneda',
                      'unidad', 'destacado', 'propiedades', 'proveedores', 'industrias'] as $campo) {
                if (!array_key_exists($campo, $m)) {
                    $this->errores[] = "Material {$slug}: falta '{$campo}'.";
                }
            }
            if ($this->errores !== [] && !isset($m['category_slug'], $m['propiedades'])) {
                continue;
            }
            if (!isset($cat[$m['category_slug']])) {
                $this->errores[] = "Material {$slug}: categoría '{$m['category_slug']}' inexistente.";
            } else {
                $cat[$m['category_slug']]++;
                $usadasCat[$m['category_slug']] = true;
            }
            if (mb_strlen((string) $m['nombre']) > 160) {
                $this->errores[] = "Material {$slug}: nombre > 160 caracteres.";
            }
            $lenResumen = mb_strlen((string) $m['resumen']);
            if ($lenResumen < 40 || $lenResumen > 160) {
                $this->errores[] = "Material {$slug}: resumen de {$lenResumen} caracteres (40-160).";
            }
            $lenDesc = mb_strlen((string) $m['descripcion']);
            if ($lenDesc < 300 || $lenDesc > 2000) {
                $this->errores[] = "Material {$slug}: descripción de {$lenDesc} caracteres (300-2000).";
            }
            if (isset($m['meta_description']) && mb_strlen((string) $m['meta_description']) > 320) {
                $this->errores[] = "Material {$slug}: meta_description > 320 caracteres.";
            }
            if (!in_array($m['disponibilidad'], $disponibilidades, true)) {
                $this->errores[] = "Material {$slug}: disponibilidad '{$m['disponibilidad']}' inválida.";
            }
            if (!in_array($m['moneda'], ['MXN', 'USD'], true)) {
                $this->errores[] = "Material {$slug}: moneda '{$m['moneda']}' inválida.";
            }
            $monedas[$m['moneda']] = ($monedas[$m['moneda']] ?? 0) + 1;
            if ($m['precio_ref'] !== null) {
                if (!is_numeric($m['precio_ref']) || (float) $m['precio_ref'] <= 0) {
                    $this->errores[] = "Material {$slug}: precio_ref inválido.";
                }
            }
            if (!empty($m['destacado'])) {
                $destacados++;
            }
            $props = is_array($m['propiedades']) ? $m['propiedades'] : [];
            $totalProps += count($props);
            if (count($props) < 6 || count($props) > 12) {
                $this->errores[] = "Material {$slug}: " . count($props) . ' propiedades (6-12).';
            }
            $claves = [];
            foreach ($props as $p) {
                $clave = (string) ($p['clave'] ?? '');
                if (!isset(SEED_PROPS[$clave])) {
                    $this->errores[] = "Material {$slug}: propiedad desconocida '{$clave}'.";
                    continue;
                }
                $claves[] = $clave;
                [$grupoEsperado, $unidadEsperada] = SEED_PROPS[$clave];
                if (($p['grupo'] ?? '') !== $grupoEsperado) {
                    $this->errores[] = "Material {$slug}: grupo de '{$clave}' debe ser '{$grupoEsperado}'.";
                }
                if (($p['unidad'] ?? '') !== $unidadEsperada) {
                    $this->errores[] = "Material {$slug}: unidad de '{$clave}' debe ser '{$unidadEsperada}'.";
                }
                if (!array_key_exists('valor', $p)) {
                    $this->errores[] = "Material {$slug}: propiedad '{$clave}' sin valor numérico.";
                } elseif ($p['valor'] !== null && !is_numeric($p['valor'])) {
                    $this->errores[] = "Material {$slug}: propiedad '{$clave}' con valor no numérico.";
                } elseif (is_numeric($p['valor'])) {
                    $v = (float) $p['valor'];
                    if ($clave === 'ph' && ($v < 2 || $v > 12)) {
                        $this->errores[] = "Material {$slug}: pH {$v} fuera de 2-12.";
                    }
                    if ($clave === 'mohs' && ($v < 0.5 || $v > 10)) {
                        $this->errores[] = "Material {$slug}: Mohs {$v} fuera de 0.5-10.";
                    }
                    if (in_array($clave, ['sio2', 'al2o3', 'fe2o3', 'cao', 'mgo', 'k2o', 'na2o',
                        'loi', 'so3', 'cu', 'zn', 'pb', 'sb', 'mn', 'bi', 'li2o', 'wo3', 'mos2',
                        'caf2', 'baso4', 'pureza', 'blancura'], true) && ($v < 0 || $v > 100)) {
                        $this->errores[] = "Material {$slug}: '{$clave}'={$v} fuera de 0-100 %.";
                    }
                    if ($clave === 'd50' && $v <= 0) {
                        $this->errores[] = "Material {$slug}: D50 debe ser positivo.";
                    }
                }
            }
            if ($claves !== [] && count($claves) !== count(array_unique($claves))) {
                $this->errores[] = "Material {$slug}: propiedades duplicadas.";
            }
            foreach ((array) $m['proveedores'] as $ps) {
                if (!in_array($ps, $provSlugs, true)) {
                    $this->errores[] = "Material {$slug}: proveedor '{$ps}' inexistente.";
                } else {
                    $usadasProv[$ps] = true;
                }
            }
            if (count((array) $m['proveedores']) < 1) {
                $this->errores[] = "Material {$slug}: sin proveedores.";
            }
            foreach ((array) $m['industrias'] as $is) {
                if (!in_array($is, $indSlugs, true)) {
                    $this->errores[] = "Material {$slug}: industria '{$is}' inexistente.";
                } else {
                    $usadasInd[$is] = true;
                }
            }
            if (count((array) $m['industrias']) < 1) {
                $this->errores[] = "Material {$slug}: sin industrias.";
            }
        }

        foreach ($catSlugs as $cs) {
            if (($cat[$cs] ?? 0) === 0) {
                $this->errores[] = "Categoría '{$cs}' sin materiales.";
            }
        }
        foreach ($indSlugs as $is) {
            if (!isset($usadasInd[$is])) {
                $this->errores[] = "Industria '{$is}' sin materiales.";
            }
        }
        foreach ($provSlugs as $ps) {
            if (!isset($usadasProv[$ps])) {
                $this->errores[] = "Proveedor '{$ps}' sin materiales.";
            }
        }
        if ($destacados !== 6) {
            $this->errores[] = "Se esperaban exactamente 6 materiales destacados; hay {$destacados}.";
        }
        if ($totalProps < 300) {
            $this->errores[] = "Total de propiedades sospechosamente bajo: {$totalProps}.";
        }

        $clavesSettings = [];
        foreach ($d['settings'] ?? [] as $s) {
            foreach (['clave', 'valor', 'grupo', 'etiqueta', 'tipo'] as $campo) {
                if (!array_key_exists($campo, $s)) {
                    $this->errores[] = "Setting '{$s['clave']}': falta '{$campo}'.";
                }
            }
            $clave = (string) ($s['clave'] ?? '');
            if ($clave === '' || mb_strlen($clave) > 60) {
                $this->errores[] = "Setting con clave vacía o > 60 caracteres.";
            }
            if (isset($clavesSettings[$clave])) {
                $this->errores[] = "Setting duplicado: '{$clave}'.";
            }
            $clavesSettings[$clave] = true;
            if (!in_array($s['tipo'] ?? '', ['texto', 'textarea', 'email', 'url', 'numero', 'booleano'], true)) {
                $this->errores[] = "Setting '{$clave}': tipo inválido.";
            }
        }

        // Datos para reportes.
        $this->avisos[] = sprintf(
            'Catálogo: %d industrias, %d categorías, %d proveedores, %d materiales, %d propiedades (%s).',
            $cuenta('industrias'), $cuenta('categorias'), $cuenta('proveedores'), $cuenta('materiales'),
            $totalProps, json_encode($monedas, JSON_UNESCAPED_UNICODE) ?: '{}'
        );
        $this->avisos[] = 'Proveedores, certificaciones y precios son DEMO/referencia, no ofertas reales.';
    }

    /** @param list<array<string,mixed>> $lista @return list<string> */
    private function slugsUnicos(array $lista, string $etiqueta, string $campo): array
    {
        $slugs = [];
        foreach ($lista as $i => $item) {
            $slug = (string) ($item[$campo] ?? '');
            if ($slug === '') {
                $this->errores[] = "{$etiqueta} #{$i}: slug vacío.";
                continue;
            }
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                $this->errores[] = "{$etiqueta}: slug no kebab-case: {$slug}.";
            }
            if (isset($slugs[$slug])) {
                $this->errores[] = "{$etiqueta}: slug duplicado: {$slug}.";
            }
            $slugs[$slug] = true;
        }
        return array_keys($slugs);
    }

    private function imprimirErrores(): void
    {
        foreach ($this->avisos as $a) {
            echo "· {$a}\n";
        }
        foreach (array_slice($this->errores, 0, 40) as $e) {
            fwrite(STDERR, "✗ {$e}\n");
        }
        if (count($this->errores) > 40) {
            fwrite(STDERR, '… y ' . (count($this->errores) - 40) . " errores más.\n");
        }
        fwrite(STDERR, "\nValidación FALLIDA: " . count($this->errores) . " error(es).\n");
    }

    // ── Reportes ─────────────────────────────────────────────────────

    private function reporteCatalogo(string $titulo): void
    {
        $d = $this->data;
        $totalProps = 0;
        $porCat = [];
        $destacados = [];
        foreach ($d['materiales'] as $m) {
            $totalProps += count($m['propiedades']);
            $porCat[$m['category_slug']] = ($porCat[$m['category_slug']] ?? 0) + 1;
            if (!empty($m['destacado'])) {
                $destacados[] = $m['slug'];
            }
        }
        echo "══ {$titulo} ══\n";
        printf("  Archivo      : %s (%s bytes)\n", SEED_CATALOGO, number_format((float) filesize(SEED_CATALOGO)));
        printf("  Industrias   : %d\n", count($d['industrias']));
        printf("  Categorías   : %d\n", count($d['categorias']));
        printf("  Proveedores  : %d (es_demo=1)\n", count($d['proveedores']));
        printf("  Materiales   : %d\n", count($d['materiales']));
        printf("  Propiedades  : %d (%.1f por material)\n", $totalProps, $totalProps / max(1, count($d['materiales'])));
        printf("  Settings     : %d\n", count($d['settings']));
        printf("  Destacados   : %s\n", implode(', ', $destacados));
        echo "  Por categoría: \n";
        foreach ($d['categorias'] as $c) {
            printf("    - %-24s %d\n", $c['slug'], $porCat[$c['slug']] ?? 0);
        }
        if ($this->avisos !== []) {
            echo "  Avisos:\n";
            foreach ($this->avisos as $a) {
                echo "    · {$a}\n";
            }
        }
    }

    private function reporteDryRun(): void
    {
        $this->reporteCatalogo('DRY-RUN: validación del catálogo (sin conexión a BD)');
        echo "\n✓ El catálogo es válido y está listo para sembrarse.\n";
        echo "  Para sembrar: php database/seed.php        (inserta faltantes)\n";
        echo "  Para actualizar: php database/seed.php --force\n";
    }

    // ── Conexión ─────────────────────────────────────────────────────

    /** @return array<string,string> */
    private function env(): array
    {
        $vars = [];
        $ruta = SEED_ROOT . '/.env';
        if (is_file($ruta)) {
            $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                    continue;
                }
                [$clave, $valor] = explode('=', $linea, 2);
                $clave = trim($clave);
                $valor = trim($valor);
                if (strlen($valor) >= 2) {
                    $primero = $valor[0];
                    $ultimo = $valor[strlen($valor) - 1];
                    if (($primero === '"' && $ultimo === '"') || ($primero === "'" && $ultimo === "'")) {
                        $valor = substr($valor, 1, -1);
                    }
                }
                $vars[$clave] = $valor;
            }
        }
        return $vars;
    }

    private function envValor(string $clave, string $defecto = ''): string
    {
        $entorno = getenv($clave);
        if ($entorno !== false && $entorno !== '') {
            return (string) $entorno;
        }
        static $vars = null;
        if ($vars === null) {
            $vars = $this->env();
        }
        return $vars[$clave] ?? $defecto;
    }

    private function conectar(): bool
    {
        $host = $this->envValor('DB_HOST', '127.0.0.1');
        $puerto = $this->envValor('DB_PORT', '3306');
        $nombre = $this->envValor('DB_NAME', '');
        $usuario = $this->envValor('DB_USER', '');
        $clave = $this->envValor('DB_PASS', '');
        $charset = $this->envValor('DB_CHARSET', 'utf8mb4');
        if ($nombre === '' || $usuario === '') {
            fwrite(STDERR, "✗ Faltan DB_NAME o DB_USER en el entorno/.env.\n");
            return false;
        }
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $puerto, $nombre, $charset);
        try {
            $this->pdo = new PDO($dsn, $usuario, $clave, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $this->pdo->exec("SET NAMES utf8mb4");
            echo "✓ Conexión establecida con {$nombre} en {$host}:{$puerto} ("
                . $this->pdo->query('SELECT VERSION()')->fetchColumn() . ").\n";
            return true;
        } catch (PDOException $e) {
            fwrite(STDERR, "✗ No se pudo conectar a la base de datos: " . $e->getMessage() . "\n");
            fwrite(STDERR, "  Revisa DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS en .env.\n");
            return false;
        }
    }

    private function verificarEsquema(): bool
    {
        $requeridas = ['users', 'settings', 'industries', 'categories', 'suppliers',
            'materials', 'material_properties', 'material_industries', 'material_suppliers'];
        $existentes = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $faltan = array_values(array_diff($requeridas, array_map('strval', $existentes)));
        if ($faltan !== []) {
            fwrite(STDERR, "✗ Falta el esquema en la base de datos. Tablas ausentes: " . implode(', ', $faltan) . "\n");
            fwrite(STDERR, "  Ejecuta primero: mysql -h... -u... -p... < database/schema.sql\n");
            return false;
        }
        return true;
    }

    // ── Siembra ──────────────────────────────────────────────────────

    private function sembrar(): void
    {
        foreach ($this->data['industrias'] as $i) {
            [$id, $estado] = $this->sincronizar('industries', 'slug', $i['slug'], [
                'nombre' => $i['nombre'], 'descripcion' => $i['descripcion'],
                'icono' => $i['icono'], 'color' => $i['color'], 'orden' => (int) $i['orden'],
                'activo' => (int) ($i['activo'] ?? 1),
            ]);
            $this->idsIndustrias[$i['slug']] = $id;
            $this->contar('industrias', $estado);
        }
        foreach ($this->data['categorias'] as $c) {
            [$id, $estado] = $this->sincronizar('categories', 'slug', $c['slug'], [
                'nombre' => $c['nombre'], 'descripcion' => $c['descripcion'],
                'familia' => $c['familia'], 'orden' => (int) $c['orden'],
                'activo' => (int) ($c['activo'] ?? 1),
            ]);
            $this->idsCategorias[$c['slug']] = $id;
            $this->contar('categorias', $estado);
        }
        foreach ($this->data['proveedores'] as $p) {
            [$id, $estado] = $this->sincronizar('suppliers', 'slug', $p['slug'], [
                'nombre' => $p['nombre'], 'tipo' => $p['tipo'], 'descripcion' => $p['descripcion'],
                'pais' => 'México', 'estado' => $p['estado'], 'ciudad' => $p['ciudad'] ?? null,
                'capacidad' => $p['capacidad'], 'certificaciones' => $p['certificaciones'],
                'sitio_web' => $p['sitio_web'] ?? null, 'email_publico' => $p['email_publico'] ?? null,
                'telefono_publico' => $p['telefono_publico'] ?? null, 'logo' => $p['logo'] ?? null,
                'verificado' => (int) ($p['verificado'] ?? 0), 'es_demo' => 1,
                'activo' => (int) ($p['activo'] ?? 1),
            ]);
            $this->idsProveedores[$p['slug']] = $id;
            $this->contar('proveedores', $estado);
        }

        foreach ($this->data['materiales'] as $m) {
            $categoryId = $this->idsCategorias[$m['category_slug']] ?? null;
            if ($categoryId === null) {
                throw new RuntimeException("Categoría no resuelta para {$m['slug']}.");
            }
            [$id, $estado] = $this->sincronizar('materials', 'slug', $m['slug'], [
                'category_id' => $categoryId, 'nombre' => $m['nombre'], 'resumen' => $m['resumen'],
                'descripcion' => $m['descripcion'], 'origen' => $m['origen'],
                'aplicaciones' => $m['aplicaciones'], 'presentacion' => $m['presentacion'],
                'disponibilidad' => $m['disponibilidad'], 'region' => $m['region'],
                'precio_ref' => $m['precio_ref'], 'moneda' => $m['moneda'], 'unidad' => $m['unidad'],
                'imagen' => $m['imagen'] ?? null, 'destacado' => (int) $m['destacado'],
                'es_demo' => 1, 'meta_title' => $m['meta_title'] ?? null,
                'meta_description' => $m['meta_description'] ?? null, 'activo' => 1,
            ]);
            $this->idsMateriales[$m['slug']] = $id;
            $this->contar('materiales', $estado);

            $this->sembrarPropiedades($id, $m);
            $this->sembrarIndustrias($id, $m);
            $this->sembrarProveedores($id, $m);
        }

        foreach ($this->data['settings'] as $s) {
            $existe = $this->pdo->prepare('SELECT COUNT(*) FROM settings WHERE clave = ?');
            $existe->execute([$s['clave']]);
            $hay = (int) $existe->fetchColumn() > 0;
            if (!$hay) {
                $this->pdo->prepare('INSERT INTO settings (clave, valor, grupo, etiqueta, tipo) VALUES (?,?,?,?,?)')
                    ->execute([$s['clave'], $s['valor'], $s['grupo'], $s['etiqueta'], $s['tipo']]);
                $this->contar('settings', 'insertado');
            } elseif ($this->force) {
                $upd = $this->pdo->prepare('UPDATE settings SET valor=?, grupo=?, etiqueta=?, tipo=? WHERE clave=?');
                $upd->execute([$s['valor'], $s['grupo'], $s['etiqueta'], $s['tipo'], $s['clave']]);
                $this->contar('settings', $upd->rowCount() > 0 ? 'actualizado' : 'conservado');
            } else {
                $this->contar('settings', 'conservado');
            }
        }

        $admin = $this->pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $admin->execute([SEED_ADMIN_EMAIL]);
        $adminId = $admin->fetchColumn();
        if ($adminId === false) {
            $hash = password_hash(SEED_ADMIN_PASS, PASSWORD_BCRYPT);
            $this->pdo->prepare(
                'INSERT INTO users (nombre, email, password_hash, rol, activo) VALUES (?,?,?,?,1)'
            )->execute(['Administrador Arcillas MX', SEED_ADMIN_EMAIL, $hash, 'admin']);
            $this->contar('usuarios', 'insertado');
        } else {
            $this->contar('usuarios', 'conservado');
        }
    }

    /** @param array<string,mixed> $m */
    private function sembrarPropiedades(int $materialId, array $m): void
    {
        $stmt = $this->pdo->prepare('SELECT clave FROM material_properties WHERE material_id = ?');
        $stmt->execute([$materialId]);
        $existentes = array_fill_keys(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);

        foreach ($m['propiedades'] as $p) {
            $clave = (string) $p['clave'];
            if (!isset($existentes[$clave])) {
                $this->pdo->prepare(
                    'INSERT INTO material_properties (material_id, grupo, clave, nombre, valor, valor_texto, unidad, orden)
                     VALUES (?,?,?,?,?,?,?,?)'
                )->execute([
                    $materialId, $p['grupo'], $clave, $p['nombre'], $p['valor'],
                    $p['valor_texto'] ?? null, $p['unidad'], (int) ($p['orden'] ?? 0),
                ]);
                $this->contar('propiedades', 'insertado');
            } elseif ($this->force) {
                $upd = $this->pdo->prepare(
                    'UPDATE material_properties SET grupo=?, nombre=?, valor=?, valor_texto=?, unidad=?, orden=?
                     WHERE material_id=? AND clave=?'
                );
                $upd->execute([
                    $p['grupo'], $p['nombre'], $p['valor'], $p['valor_texto'] ?? null,
                    $p['unidad'], (int) ($p['orden'] ?? 0), $materialId, $clave,
                ]);
                $this->contar('propiedades', $upd->rowCount() > 0 ? 'actualizado' : 'conservado');
            } else {
                $this->contar('propiedades', 'conservado');
            }
        }
    }

    /** @param array<string,mixed> $m */
    private function sembrarIndustrias(int $materialId, array $m): void
    {
        $stmt = $this->pdo->prepare('SELECT industry_id FROM material_industries WHERE material_id = ?');
        $stmt->execute([$materialId]);
        $existentes = array_fill_keys(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);
        foreach ($m['industrias'] as $slug) {
            $industryId = $this->idsIndustrias[$slug] ?? null;
            if ($industryId === null) {
                throw new RuntimeException("Industria no resuelta '{$slug}' en {$m['slug']}.");
            }
            if (!isset($existentes[$industryId])) {
                $this->pdo->prepare('INSERT INTO material_industries (material_id, industry_id, aplicacion) VALUES (?,?,NULL)')
                    ->execute([$materialId, $industryId]);
                $this->contar('relaciones_industria', 'insertado');
            } else {
                $this->contar('relaciones_industria', 'conservado');
            }
        }
    }

    /** @param array<string,mixed> $m */
    private function sembrarProveedores(int $materialId, array $m): void
    {
        $stmt = $this->pdo->prepare('SELECT supplier_id FROM material_suppliers WHERE material_id = ?');
        $stmt->execute([$materialId]);
        $existentes = array_fill_keys(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);
        foreach (array_values($m['proveedores']) as $indice => $slug) {
            $supplierId = $this->idsProveedores[$slug] ?? null;
            if ($supplierId === null) {
                throw new RuntimeException("Proveedor no resuelto '{$slug}' en {$m['slug']}.");
            }
            $datos = [
                'material_id' => $materialId, 'supplier_id' => $supplierId,
                'es_principal' => $indice === 0 ? 1 : 0,
                'disponibilidad' => $m['disponibilidad'],
                'precio_ref' => $m['precio_ref'], 'moneda' => $m['moneda'], 'unidad' => $m['unidad'],
                'minimo' => '1 t (demo)',
                'notas' => 'Proveedor DEMO; precio de referencia, no oferta.',
                'activo' => 1,
            ];
            if (!isset($existentes[$supplierId])) {
                $this->pdo->prepare(
                    'INSERT INTO material_suppliers
                     (material_id, supplier_id, es_principal, disponibilidad, precio_ref, moneda, unidad, minimo, notas, activo)
                     VALUES (:material_id, :supplier_id, :es_principal, :disponibilidad, :precio_ref, :moneda, :unidad, :minimo, :notas, :activo)'
                )->execute($datos);
                $this->contar('relaciones_proveedor', 'insertado');
            } elseif ($this->force) {
                $upd = $this->pdo->prepare(
                    'UPDATE material_suppliers SET es_principal=:es_principal, disponibilidad=:disponibilidad,
                     precio_ref=:precio_ref, moneda=:moneda, unidad=:unidad, minimo=:minimo, notas=:notas, activo=:activo
                     WHERE material_id=:material_id AND supplier_id=:supplier_id'
                );
                $upd->execute($datos);
                $this->contar('relaciones_proveedor', $upd->rowCount() > 0 ? 'actualizado' : 'conservado');
            } else {
                $this->contar('relaciones_proveedor', 'conservado');
            }
        }
    }

    /**
     * Inserta o actualiza un registro identificado por una columna única.
     *
     * @param array<string,mixed> $datos
     * @return array{0:int,1:string} [id, estado]
     */
    private function sincronizar(string $tabla, string $unico, string $valor, array $datos): array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM {$tabla} WHERE {$unico} = ? LIMIT 1");
        $stmt->execute([$valor]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            $insertar = [$unico => $valor] + $datos;
            $columnas = array_keys($insertar);
            $marcas = array_map(static fn (string $c): string => ':' . $c, $columnas);
            $sql = "INSERT INTO {$tabla} (`" . implode('`, `', $columnas) . '`) VALUES (' . implode(', ', $marcas) . ')';
            $this->pdo->prepare($sql)->execute($insertar);
            return [(int) $this->pdo->lastInsertId(), 'insertado'];
        }
        $id = (int) $id;
        if ($this->force) {
            $sets = implode(', ', array_map(static fn (string $c): string => "`{$c}` = :{$c}", array_keys($datos)));
            $this->pdo->prepare("UPDATE {$tabla} SET {$sets} WHERE id = :__id")
                ->execute($datos + ['__id' => $id]);
            return [$id, 'actualizado'];
        }
        return [$id, 'conservado'];
    }

    private function contar(string $entidad, string $estado): void
    {
        $this->stats["{$entidad}.{$estado}"] = ($this->stats["{$entidad}.{$estado}"] ?? 0) + 1;
    }

    // ── Reporte final ────────────────────────────────────────────────

    private function reporteFinal(): void
    {
        echo "\n══ Resumen de la siembra ══\n";
        $entidades = ['industrias', 'categorias', 'proveedores', 'materiales', 'propiedades',
            'relaciones_industria', 'relaciones_proveedor', 'settings', 'usuarios'];
        foreach ($entidades as $e) {
            $ins = $this->stats["{$e}.insertado"] ?? 0;
            $act = $this->stats["{$e}.actualizado"] ?? 0;
            $con = $this->stats["{$e}.conservado"] ?? 0;
            printf("  %-22s insertados: %4d | actualizados: %4d | conservados: %4d\n", $e, $ins, $act, $con);
        }
        echo "\n  Conteos actuales en la base de datos:\n";
        foreach (['users', 'settings', 'industries', 'categories', 'suppliers', 'materials',
                     'material_properties', 'material_industries', 'material_suppliers'] as $tabla) {
            $n = (int) $this->pdo->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn();
            printf("    %-24s %d\n", $tabla, $n);
        }
        echo "\n✓ Siembra completada. No se eliminó ningún registro (leads y usuarios intactos).\n";
        if (!$this->force) {
            echo "  Los registros existentes se conservaron; usa --force para actualizarlos.\n";
        }
    }
}

$seeder = new Seeder($argv);
exit($seeder->ejecutar());
