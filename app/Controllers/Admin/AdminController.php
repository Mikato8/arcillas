<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Logger;

abstract class AdminController extends Controller
{
    protected function admin(string $vista, array $data = []): void
    {
        $data['usuario'] = Auth::user() ?? [];
        $data['meta'] = array_merge([
            'titulo'      => 'Panel · Arcillas MX',
            'descripcion' => 'Panel de administración.',
            'canonical'   => canonical(),
            'noindex'     => true,
        ], is_array($data['meta'] ?? null) ? $data['meta'] : []);
        $this->view($vista, $data, 'layouts/admin');
    }

    /** @param array<string,mixed> $detalle */
    protected function bitacora(string $accion, string $entidad = '', string $entidadId = '', array $detalle = []): void
    {
        Database::run(
            'INSERT INTO logs (user_id, accion, entidad, entidad_id, detalle, ip) VALUES (?, ?, ?, ?, ?, ?)',
            [Auth::id(), $accion, $entidad, $entidadId, json_encode($detalle, JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null]
        );
        Logger::info($accion, ['entidad' => $entidad, 'id' => $entidadId]);
    }
}
