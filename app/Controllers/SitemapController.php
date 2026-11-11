<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;

final class SitemapController extends Controller
{
    public function index(): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $urls = [];
        $estaticas = [
            '/'                 => '1.0',
            '/materiales'       => '0.9',
            '/industrias'       => '0.8',
            '/proveedores'      => '0.8',
            '/calculadoras'     => '0.9',
            '/como-funciona'    => '0.6',
            '/nosotros'         => '0.6',
            '/contacto'         => '0.7',
            '/soy-comprador'    => '0.7',
            '/soy-proveedor'    => '0.7',
            '/aviso-de-privacidad' => '0.3',
            '/terminos'         => '0.3',
        ];
        foreach ($estaticas as $ruta => $prioridad) {
            $urls[] = ['loc' => url($ruta), 'prioridad' => $prioridad, 'fecha' => date('Y-m-d')];
        }
        foreach (Database::fetchAll('SELECT slug, actualizado_en FROM materials WHERE activo = 1') as $m) {
            $urls[] = ['loc' => url('/materiales/' . $m['slug']), 'prioridad' => '0.8', 'fecha' => substr((string) $m['actualizado_en'], 0, 10)];
        }
        foreach (Database::fetchAll('SELECT slug FROM industries WHERE activo = 1') as $i) {
            $urls[] = ['loc' => url('/industrias/' . $i['slug']), 'prioridad' => '0.7', 'fecha' => date('Y-m-d')];
        }
        foreach (Database::fetchAll('SELECT slug FROM suppliers WHERE activo = 1') as $s) {
            $urls[] = ['loc' => url('/proveedores/' . $s['slug']), 'prioridad' => '0.6', 'fecha' => date('Y-m-d')];
        }
        foreach (calculadoras_disponibles() as $calc) {
            $urls[] = ['loc' => url('/calculadoras/' . $calc->slug()), 'prioridad' => '0.7', 'fecha' => date('Y-m-d')];
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            echo '  <url><loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>'
                . '<lastmod>' . $url['fecha'] . '</lastmod>'
                . '<changefreq>weekly</changefreq>'
                . '<priority>' . $url['prioridad'] . '</priority></url>' . "\n";
        }
        echo '</urlset>' . "\n";
        exit;
    }
}
