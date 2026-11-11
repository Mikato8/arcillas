<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;

final class PageController extends Controller
{
    public function comoFunciona(): void
    {
        $this->view('pages/como-funciona', [
            'titulo' => 'Cómo funciona',
            'meta'   => [
                'titulo'      => 'Cómo funciona la intermediación de arcillas y minerales · Arcillas MX',
                'descripcion' => 'Del requerimiento a la entrega: validamos especificaciones, identificamos proveedores, coordinamos muestras, calidad y logística. Proceso claro para compradores y proveedores.',
                'canonical'   => canonical('/como-funciona'),
            ],
        ]);
    }

    public function nosotros(): void
    {
        $this->view('pages/nosotros', [
            'titulo' => 'Nosotros',
            'meta'   => [
                'titulo'      => 'ARCILLAS PROTEÍNAS Y DESARROLLOS GARCÍA · Arcillas MX',
                'descripcion' => 'Somos un broker mexicano especializado en arcillas y minerales no ferrosos. Conectamos compradores industriales con minas y plantas, con criterio técnico y transparencia comercial.',
                'canonical'   => canonical('/nosotros'),
            ],
        ]);
    }

    public function aviso(): void
    {
        $this->view('pages/aviso', [
            'titulo' => 'Aviso de privacidad',
            'meta'   => [
                'titulo'      => 'Aviso de privacidad · Arcillas MX',
                'descripcion' => 'Aviso de privacidad integral conforme a la LFPDPPP: datos que recabamos, finalidades, transferencias y derechos ARCO.',
                'canonical'   => canonical('/aviso-de-privacidad'),
            ],
        ]);
    }

    public function terminos(): void
    {
        $this->view('pages/terminos', [
            'titulo' => 'Términos y condiciones',
            'meta'   => [
                'titulo'      => 'Términos y condiciones · Arcillas MX',
                'descripcion' => 'Condiciones de uso del sitio, alcance de la información técnica y reglas de la intermediación comercial.',
                'canonical'   => canonical('/terminos'),
            ],
        ]);
    }

    public function buscar(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $destino = '/materiales' . ($q !== '' ? '?q=' . urlencode($q) : '');
        Response::redirect($destino, 302);
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $sitemap = url('/sitemap.xml');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /storage\n";
        echo "Disallow: /api/\n";
        echo "Disallow: /gracias\n";
        echo "\nSitemap: " . $sitemap . "\n";
        exit;
    }
}
