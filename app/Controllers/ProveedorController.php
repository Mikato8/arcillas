<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Proveedor;

final class ProveedorController extends Controller
{
    public function index(): void
    {
        $this->view('proveedores/index', [
            'titulo'      => 'Proveedores y minas',
            'proveedores' => Proveedor::todos(),
            'meta'        => [
                'titulo'      => 'Directorio de proveedores de arcillas y minerales · Arcillas MX',
                'descripcion' => 'Minas, plantas y distribuidores de arcillas, minerales industriales y concentrados no ferrosos en México.',
                'canonical'   => canonical('/proveedores'),
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $proveedor = Proveedor::porSlug((string) ($params['slug'] ?? ''));
        if ($proveedor === null) {
            $this->noEncontrado();
        }
        $materiales = Proveedor::materiales((int) $proveedor['id']);

        $this->view('proveedores/show', [
            'titulo'     => $proveedor['nombre'],
            'proveedor'  => $proveedor,
            'materiales' => $materiales,
            'meta'       => [
                'titulo'      => $proveedor['nombre'] . ' · Proveedor · Arcillas MX',
                'descripcion' => truncate((string) $proveedor['descripcion'], 155),
                'canonical'   => canonical('/proveedores/' . $proveedor['slug']),
            ],
        ]);
    }
}
