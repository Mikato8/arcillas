<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Models\Categoria;
use App\Models\Industria;
use App\Models\Material;

final class MaterialController extends Controller
{
    public function index(): void
    {
        $filtros = [
            'q'             => trim((string) ($_GET['q'] ?? '')),
            'categoria'     => (string) ($_GET['categoria'] ?? ''),
            'industria'     => (string) ($_GET['industria'] ?? ''),
            'familia'       => (string) ($_GET['familia'] ?? ''),
            'disponibilidad' => (string) ($_GET['disponibilidad'] ?? ''),
            'orden'         => (string) ($_GET['orden'] ?? 'relevancia'),
            'pagina'        => (int) ($_GET['pagina'] ?? 1),
            'al2o3_min'     => $_GET['al2o3_min'] ?? '',
            'fe2o3_max'     => $_GET['fe2o3_max'] ?? '',
            'blancura_min'  => $_GET['blancura_min'] ?? '',
            'humedad_max'   => $_GET['humedad_max'] ?? '',
            'ph_min'        => $_GET['ph_min'] ?? '',
        ];

        $resultado = Material::buscar($filtros, 12);

        if ($filtros['q'] !== '') {
            Database::run('INSERT INTO search_logs (termino, resultados, ip) VALUES (?, ?, ?)', [
                mb_substr($filtros['q'], 0, 180),
                $resultado['total'],
                Request::ip(),
            ]);
        }

        $categorias = Categoria::todas();
        $industrias = Industria::todas();

        $titulo = $filtros['q'] !== ''
            ? 'Resultados para “' . $filtros['q'] . '”'
            : 'Directorio de materiales y minerales';

        $this->view('materiales/index', [
            'titulo'     => $titulo,
            'filtros'    => $filtros,
            'resultado'  => $resultado,
            'categorias' => $categorias,
            'industrias' => $industrias,
            'meta'       => [
                'titulo'      => $titulo . ' · Arcillas MX',
                'descripcion' => 'Busca por tipo de arcilla, composición química, granulometría, blancura o industria: caolín, bentonita, sílice, feldespatos, carbonatos y concentrados de minerales no ferrosos.',
                'canonical'   => canonical('/materiales'),
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $material = Material::porSlug((string) ($params['slug'] ?? ''));
        if ($material === null) {
            $this->noEncontrado();
        }

        Material::registrarVista((int) $material['id']);
        $propiedades = Material::propiedades((int) $material['id']);
        $grupos = ['quimica' => [], 'fisica' => [], 'granulometria' => [], 'presentacion' => []];
        foreach ($propiedades as $prop) {
            $grupos[$prop['grupo']][] = $prop;
        }
        $proveedores = Material::proveedores((int) $material['id']);
        $industrias = Material::industrias((int) $material['id']);
        $documentos = Material::documentos((int) $material['id']);
        $relacionados = Material::relacionados($material, 3);

        $this->view('materiales/show', [
            'titulo'      => $material['nombre'],
            'material'    => $material,
            'propiedades' => $grupos,
            'proveedores' => $proveedores,
            'industrias'  => $industrias,
            'documentos'  => $documentos,
            'relacionados' => $relacionados,
            'meta'        => [
                'titulo'      => ($material['meta_title'] ?: $material['nombre'] . ' · Ficha técnica y proveedores · Arcillas MX'),
                'descripcion' => ($material['meta_description'] ?: truncate((string) $material['resumen'], 155)),
                'canonical'   => canonical('/materiales/' . $material['slug']),
                'tipo'        => 'product',
                'json_ld'     => [$this->schemaProducto($material, $proveedores)],
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function ficha(array $params): void
    {
        $material = Material::porSlug((string) ($params['slug'] ?? ''));
        if ($material === null) {
            $this->noEncontrado();
        }
        $propiedades = Material::propiedades((int) $material['id']);
        $grupos = ['quimica' => [], 'fisica' => [], 'granulometria' => [], 'presentacion' => []];
        foreach ($propiedades as $prop) {
            $grupos[$prop['grupo']][] = $prop;
        }
        $this->view('materiales/ficha', [
            'titulo'      => 'Ficha técnica · ' . $material['nombre'],
            'material'    => $material,
            'propiedades' => $grupos,
            'proveedores' => Material::proveedores((int) $material['id']),
            'meta'        => [
                'titulo'      => 'Ficha técnica · ' . $material['nombre'] . ' · Arcillas MX',
                'descripcion' => 'Ficha técnica imprimible de ' . $material['nombre'] . '.',
                'canonical'   => canonical('/materiales/' . $material['slug'] . '/ficha-tecnica'),
                'noindex'     => true,
            ],
        ], 'layouts/print');
    }

    public function comparar(): void
    {
        $slugs = $_GET['ids'] ?? $_GET['materiales'] ?? '';
        if (is_string($slugs)) {
            $slugs = array_filter(array_map('trim', explode(',', $slugs)));
        }
        $materiales = Material::comparar(is_array($slugs) ? $slugs : []);

        $this->view('materiales/comparar', [
            'titulo'     => 'Comparar materiales',
            'materiales' => $materiales,
            'meta'       => [
                'titulo'      => 'Comparador de arcillas y minerales · Arcillas MX',
                'descripcion' => 'Compara lado a lado composición química, propiedades físicas, granulometría, disponibilidad y proveedores de hasta cuatro materiales.',
                'canonical'   => canonical('/comparar'),
            ],
        ]);
    }

    /** @param array<string,mixed> $material @param array<int,array<string,mixed>> $proveedores @return array<string,mixed> */
    private function schemaProducto(array $material, array $proveedores): array
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $material['nombre'],
            'description' => truncate((string) ($material['meta_description'] ?: $material['resumen']), 300),
            'category'    => $material['categoria_nombre'],
            'url'         => url('/materiales/' . $material['slug']),
            'brand'       => ['@type' => 'Organization', 'name' => empresa_nombre()],
            'additionalProperty' => [],
        ];
        if (!empty($material['precio_ref'])) {
            $schema['offers'] = [
                '@type'         => 'Offer',
                'priceCurrency' => $material['moneda'],
                'price'         => (string) $material['precio_ref'],
                'availability'  => $material['disponibilidad'] === 'inmediata'
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/PreOrder',
                'url'           => url('/materiales/' . $material['slug']),
            ];
        }
        return $schema;
    }
}
