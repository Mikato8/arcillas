<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

abstract class Controller
{
    /** @param array<string,mixed> $data */
    protected function view(string $vista, array $data = [], string $layout = 'layouts/main'): void
    {
        $meta = array_merge([
            'titulo'       => (string) setting('site_nombre', 'Arcillas MX'),
            'descripcion'  => (string) setting('site_descripcion', 'Directorio técnico de arcillas y minerales no ferrosos para la industria mexicana.'),
            'canonical'    => canonical(),
            'imagen'       => url('/assets/img/og-arcillas.jpg'),
            'noindex'      => false,
            'tipo'         => 'website',
            'json_ld'      => [],
        ], is_array($data['meta'] ?? null) ? $data['meta'] : []);
        $data['meta'] = $meta;
        $data['flash_error'] = Session::pullFlash('error');
        $data['flash_ok'] = Session::pullFlash('ok');
        View::render($vista, $data, $layout);
    }

    protected function noEncontrado(string $mensaje = ''): never
    {
        http_response_code(404);
        $this->view('pages/404', [
            'titulo' => 'Página no encontrada',
            'meta'   => [
                'titulo'      => 'Página no encontrada · Arcillas MX',
                'descripcion' => 'La página que buscas no existe o cambió de ubicación.',
                'noindex'     => true,
            ],
        ]);
        exit;
    }

    /** Redirige conservando mensajes flash. */
    protected function redirigir(string $url): never
    {
        Response::redirect($url);
    }

    protected function esPost(): bool
    {
        return Request::isPost();
    }

    /** @return array<string,mixed> */
    protected function datosFormulario(): array
    {
        return array_merge($_GET, $_POST, Request::json());
    }
}
