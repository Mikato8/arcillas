<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\Categoria;
use App\Models\Industria;
use App\Models\Lead;
use App\Models\Material;
use App\Models\Proveedor;

final class HomeController extends Controller
{
    public function index(): void
    {
        $destacados = Material::destacados(6);
        $clavesDestacados = Material::propiedadesClave(array_column($destacados, 'id'));
        $categorias = Categoria::conMateriales(8);
        $industrias = Industria::todas();
        $proveedores = Proveedor::destacados(4);
        $ultimos = Database::fetchAll(
            'SELECT m.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug
             FROM materials m INNER JOIN categories c ON c.id = m.category_id
             WHERE m.activo = 1 ORDER BY m.creado_en DESC, m.id DESC LIMIT 4'
        );
        $totales = [
            'materiales'   => (int) Database::scalar('SELECT COUNT(*) FROM materials WHERE activo = 1'),
            'categorias'   => (int) Database::scalar('SELECT COUNT(*) FROM categories WHERE activo = 1'),
            'proveedores'  => (int) Database::scalar('SELECT COUNT(*) FROM suppliers WHERE activo = 1'),
            'industrias'   => (int) Database::scalar('SELECT COUNT(*) FROM industries WHERE activo = 1'),
            'calculadoras' => count(calculadoras_disponibles()),
        ];

        $this->view('pages/home', [
            'titulo'       => 'Arcillas y minerales no ferrosos para la industria mexicana',
            'destacados'   => $destacados,
            'clavesDestacados' => $clavesDestacados,
            'categorias'   => $categorias,
            'industrias'   => $industrias,
            'proveedores'  => $proveedores,
            'ultimos'      => $ultimos,
            'totales'      => $totales,
            'meta'         => [
                'titulo'      => 'Arcillas MX · Directorio técnico de arcillas y minerales no ferrosos',
                'descripcion' => 'Encuentra caolín, bentonita, arcillas cerámicas, sílice, feldespatos y concentrados de minerales no ferrosos. Fichas técnicas, proveedores verificados y calculadoras industriales para compradores y formuladores en México.',
                'json_ld'     => [$this->schemaOrganizacion()],
            ],
        ]);
    }

    /** @return array<string,mixed> */
    private function schemaOrganizacion(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => empresa_nombre(),
            'alternateName' => 'Arcillas MX',
            'url'      => url('/'),
            'email'    => (string) setting('empresa_email', 'contacto@arcillas.mx'),
            'telephone' => (string) setting('empresa_telefono', ''),
            'description' => 'Intermediación y desarrollo de proveeduría de arcillas y minerales no metálicos para la industria mexicana.',
            'address'  => [
                '@type'           => 'PostalAddress',
                'addressCountry'  => 'MX',
                'addressLocality' => (string) setting('empresa_ciudad', 'Ciudad de México'),
            ],
            'sameAs' => array_values(array_filter([
                (string) setting('empresa_linkedin', ''),
                (string) setting('empresa_google', ''),
            ])),
        ];
    }
}
