<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Industria;
use App\Models\Material;

final class IndustriaController extends Controller
{
    public function index(): void
    {
        $this->view('industrias/index', [
            'titulo'     => 'Industrias que consumen arcillas y minerales',
            'industrias' => Industria::todas(),
            'meta'       => [
                'titulo'      => 'Industrias · Arcillas y minerales por aplicación · Arcillas MX',
                'descripcion' => 'Cerámica, fundición, construcción, pinturas, plásticos, alimentos, perforación, agricultura, cosmética y vidrio: encuentra el mineral adecuado para cada proceso.',
                'canonical'   => canonical('/industrias'),
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $industria = Industria::porSlug((string) ($params['slug'] ?? ''));
        if ($industria === null) {
            $this->noEncontrado();
        }
        $resultado = Material::buscar(['industria' => $industria['slug'], 'pagina' => (int) ($_GET['pagina'] ?? 1)], 12);

        $this->view('industrias/show', [
            'titulo'     => $industria['nombre'],
            'industria'  => $industria,
            'resultado'  => $resultado,
            'meta'       => [
                'titulo'      => $industria['nombre'] . ' · Minerales y proveedores · Arcillas MX',
                'descripcion' => truncate((string) $industria['descripcion'], 155),
                'canonical'   => canonical('/industrias/' . $industria['slug']),
            ],
        ]);
    }
}
