<?php
declare(strict_types=1);

/**
 * Biblioteca de materiales cerámicos de Arcillas MX.
 *
 * Análisis tipo (% en peso, base tal como se recibe) de 14 materias primas
 * comunes en esmaltes de baja y media temperatura. Los porcentajes incluyen la
 * pérdida por ignición (LOI) para que cada material sume ~100 %; la LOI no
 * aporta óxidos y no entra en el cálculo de moles de la UMF.
 *
 * Los valores son análisis representativos de literatura cerámica pública
 * (Digitalfire, Hesselberth & Roy, TDS comerciales típicos) y sirven como
 * punto de partida editable; no sustituyen el análisis del proveedor.
 *
 * Estructura:
 *   'materiales'        => clave => ['clave','nombre','formula','analisis','loi','nota']
 *   'masas_moleculares' => óxido => g/mol
 *   'grupos'            => clasificación Seger de los óxidos
 */

return [
    'materiales' => [
        'feldespato_potasico' => [
            'clave'    => 'feldespato_potasico',
            'nombre'   => 'Feldespato potásico',
            'formula'  => 'K2O·Al2O3·6SiO2',
            'analisis' => [
                'K2O'    => 10.50,
                'Na2O'   => 2.80,
                'Al2O3'  => 18.20,
                'SiO2'   => 67.50,
                'Fe2O3'  => 0.12,
                'CaO'    => 0.25,
                'MgO'    => 0.05,
                'TiO2'   => 0.03,
            ],
            'loi'  => 0.55,
            'nota' => 'Fundente alcalino típico; sube la temperatura de maduración y estabiliza la viscosidad.',
        ],
        'feldespato_sodico' => [
            'clave'    => 'feldespato_sodico',
            'nombre'   => 'Feldespato sódico',
            'formula'  => 'Na2O·Al2O3·6SiO2',
            'analisis' => [
                'Na2O'   => 9.80,
                'K2O'    => 0.60,
                'Al2O3'  => 19.40,
                'SiO2'   => 68.98,
                'Fe2O3'  => 0.15,
                'CaO'    => 0.45,
                'MgO'    => 0.08,
                'TiO2'   => 0.04,
            ],
            'loi'  => 0.50,
            'nota' => 'Fundente más enérgico que el potásico; aumenta el coeficiente de expansión térmica.',
        ],
        'caolin' => [
            'clave'    => 'caolin',
            'nombre'   => 'Caolín',
            'formula'  => 'Al2O3·2SiO2·2H2O',
            'analisis' => [
                'Al2O3'  => 37.50,
                'SiO2'   => 46.30,
                'Fe2O3'  => 0.65,
                'TiO2'   => 0.35,
                'K2O'    => 0.25,
                'Na2O'   => 0.10,
                'MgO'    => 0.15,
                'CaO'    => 0.10,
            ],
            'loi'  => 14.60,
            'nota' => 'Aporta alúmina y suspensión; su LOI es agua estructural.',
        ],
        'silice' => [
            'clave'    => 'silice',
            'nombre'   => 'Sílice (cuarzo molido)',
            'formula'  => 'SiO2',
            'analisis' => [
                'SiO2'   => 99.50,
                'Fe2O3'  => 0.08,
                'Al2O3'  => 0.25,
                'TiO2'   => 0.02,
                'CaO'    => 0.02,
                'MgO'    => 0.01,
            ],
            'loi'  => 0.12,
            'nota' => 'Vitrificante/formador de red; controla la relación Si:Al y el brillo.',
        ],
        'carbonato_calcio' => [
            'clave'    => 'carbonato_calcio',
            'nombre'   => 'Carbonato de calcio (calcita)',
            'formula'  => 'CaCO3',
            'analisis' => [
                'CaO'    => 56.00,
            ],
            'loi'  => 44.00,
            'nota' => 'Fundente cálcico de media temperatura; mejora el brillo y la resistencia química.',
        ],
        'dolomita' => [
            'clave'    => 'dolomita',
            'nombre'   => 'Dolomita',
            'formula'  => 'CaMg(CO3)2',
            'analisis' => [
                'CaO'    => 30.50,
                'MgO'    => 21.50,
                'SiO2'   => 0.80,
                'Al2O3'  => 0.25,
                'Fe2O3'  => 0.10,
                'TiO2'   => 0.01,
                'K2O'    => 0.02,
                'Na2O'   => 0.02,
            ],
            'loi'  => 46.80,
            'nota' => 'Aporta CaO y MgO en proporción fija; modera la expansión térmica.',
        ],
        'talco' => [
            'clave'    => 'talco',
            'nombre'   => 'Talco',
            'formula'  => 'Mg3Si4O10(OH)2',
            'analisis' => [
                'MgO'    => 31.70,
                'SiO2'   => 61.50,
                'Al2O3'  => 0.30,
                'Fe2O3'  => 0.15,
                'CaO'    => 0.15,
                'TiO2'   => 0.02,
                'K2O'    => 0.01,
                'Na2O'   => 0.02,
            ],
            'loi'  => 6.15,
            'nota' => 'Fundente magnésico suave; reduce la contracción y aporta matez.',
        ],
        'borax' => [
            'clave'    => 'borax',
            'nombre'   => 'Bórax (decahidratado)',
            'formula'  => 'Na2B4O7·10H2O',
            'analisis' => [
                'B2O3'   => 36.50,
                'Na2O'   => 16.30,
            ],
            'loi'  => 47.20,
            'nota' => 'Fundente bórico muy enérgico; mejora la resistencia al craquelado pero puede dar inestabilidad.',
        ],
        'oxido_zinc' => [
            'clave'    => 'oxido_zinc',
            'nombre'   => 'Óxido de zinc',
            'formula'  => 'ZnO',
            'analisis' => [
                'ZnO'    => 99.50,
                'SiO2'   => 0.08,
                'Fe2O3'  => 0.02,
                'CaO'    => 0.05,
                'MgO'    => 0.05,
            ],
            'loi'  => 0.30,
            'nota' => 'Fundente/opacificante; en exceso cristaliza y puede provocar desconchado.',
        ],
        'carbonato_litio' => [
            'clave'    => 'carbonato_litio',
            'nombre'   => 'Carbonato de litio',
            'formula'  => 'Li2CO3',
            'analisis' => [
                'Li2O'   => 40.40,
            ],
            'loi'  => 59.60,
            'nota' => 'Fundente alcalino muy potente; baja la temperatura de maduración y aumenta la expansión.',
        ],
        'alumina_calcinada' => [
            'clave'    => 'alumina_calcinada',
            'nombre'   => 'Alúmina calcinada',
            'formula'  => 'Al2O3',
            'analisis' => [
                'Al2O3'  => 99.40,
                'SiO2'   => 0.15,
                'Na2O'   => 0.25,
                'Fe2O3'  => 0.05,
                'TiO2'   => 0.05,
            ],
            'loi'  => 0.10,
            'nota' => 'Aporta alúmina sin sílice; endurece el esmalte y reduce el craquelado por tracción.',
        ],
        'oxido_zirconio' => [
            'clave'    => 'oxido_zirconio',
            'nombre'   => 'Óxido de zirconio',
            'formula'  => 'ZrO2',
            'analisis' => [
                'ZrO2'   => 98.50,
                'SiO2'   => 1.10,
                'Al2O3'  => 0.15,
                'Fe2O3'  => 0.05,
                'TiO2'   => 0.10,
            ],
            'loi'  => 0.10,
            'nota' => 'Opacificante y estabilizador térmico.',
        ],
        'oxido_hierro' => [
            'clave'    => 'oxido_hierro',
            'nombre'   => 'Óxido de hierro (Fe2O3)',
            'formula'  => 'Fe2O3',
            'analisis' => [
                'Fe2O3'  => 98.80,
                'SiO2'   => 0.50,
                'Al2O3'  => 0.30,
                'TiO2'   => 0.10,
            ],
            'loi'  => 0.30,
            'nota' => 'Colorante; en esmaltes puede modificar la fusión según la atmósfera.',
        ],
        'oxido_cobalto' => [
            'clave'    => 'oxido_cobalto',
            'nombre'   => 'Óxido de cobalto',
            'formula'  => 'Co3O4 (expresado como CoO)',
            'analisis' => [
                'CoO'    => 93.00,
                'Fe2O3'  => 0.30,
                'Al2O3'  => 0.20,
                'SiO2'   => 0.50,
            ],
            'loi'  => 6.00,
            'nota' => 'Colorante azul intenso; se usa en dosis muy bajas (0.1–2 %).',
        ],
    ],

    'masas_moleculares' => [
        'SiO2'  => 60.084,
        'Al2O3' => 101.961,
        'B2O3'  => 69.620,
        'Na2O'  => 61.979,
        'K2O'   => 94.196,
        'Li2O'  => 29.881,
        'CaO'   => 56.077,
        'MgO'   => 40.304,
        'ZnO'   => 81.379,
        'Fe2O3' => 159.688,
        'TiO2'  => 79.866,
        'ZrO2'  => 123.223,
        'CoO'   => 74.933,
    ],

    'grupos' => [
        'RO'   => ['Na2O', 'K2O', 'Li2O', 'CaO', 'MgO', 'ZnO', 'CoO'],
        'R2O3' => ['Al2O3', 'B2O3', 'Fe2O3'],
        'RO2'  => ['SiO2', 'TiO2', 'ZrO2'],
    ],

    'leyenda' => 'Análisis tipo en % en peso. La LOI (pérdida por ignición) se reporta '
        . 'aparte y no contribuye moles de óxidos. En la fórmula unitaria los fundentes '
        . '(RO) se normalizan a ΣRO = 1 y los demás óxidos se reportan como moles '
        . 'relativos a esa base; B2O3 se presenta con los sesquióxidos.',
];
