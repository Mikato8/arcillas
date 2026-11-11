<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Response;
use App\Services\Calculators\CalculatorRegistry;

final class CalculadoraController extends Controller
{
    public function index(): void
    {
        $calculadoras = CalculatorRegistry::all();
        $categorias = [];
        foreach ($calculadoras as $calc) {
            $categorias[$calc->categoria()][] = $calc;
        }
        $usos = (int) Database::scalar('SELECT COUNT(*) FROM calculator_runs');
        $populares = Database::fetchAll(
            'SELECT calculadora, COUNT(*) AS usos FROM calculator_runs GROUP BY calculadora ORDER BY usos DESC LIMIT 4'
        );

        $this->view('calculadoras/index', [
            'titulo'      => 'Calculadoras técnicas',
            'calculadoras' => $calculadoras,
            'categorias'  => $categorias,
            'usos'        => $usos,
            'populares'   => $populares,
            'meta'        => [
                'titulo'      => 'Calculadoras de arcillas, esmaltes y minerales no ferrosos · Arcillas MX',
                'descripcion' => 'Calcula fórmulas moleculares de esmaltes (UMF), mezclas de arcillas, contracción y absorción, balance metalúrgico de concentrados, dosificación de bentonita, granulometría y precios normalizados.',
                'canonical'   => canonical('/calculadoras'),
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $calc = CalculatorRegistry::obtenerPorSlug((string) ($params['slug'] ?? ''));
        if ($calc === null) {
            $this->noEncontrado();
        }
        $ejemplos = [
            'umf-esmalte' => [
                'mat_feldespato_potasico' => 40,
                'mat_caolin' => 20,
                'mat_silice' => 20,
                'mat_carbonato_calcio' => 20,
            ],
            'contraccion-absorcion' => [
                'largo_verde' => 100,
                'largo_seco' => 92,
                'largo_cocido' => 88,
                'medida_final_deseada' => 200,
                'peso_seco' => 480,
                'peso_saturado' => 510,
            ],
            'balance-metalurgico' => [
                'ley_alimentacion' => 1.2,
                'ley_concentrado' => 26,
                'ley_colas' => 0.12,
                'toneladas_alimentacion' => 5000,
            ],
        ];
        $this->view('calculadoras/show', [
            'titulo'      => $calc->nombre(),
            'calc'        => $calc,
            'campos'      => $calc->campos(),
            'ejemplo'     => $ejemplos[$calc->slug()] ?? [],
            'relacionadas' => array_values(array_filter(
                CalculatorRegistry::all(),
                static fn ($c) => $c->slug() !== $calc->slug()
            )),
            'meta'        => [
                'titulo'      => $calc->nombre() . ' · Calculadora técnica · Arcillas MX',
                'descripcion' => truncate($calc->descripcion(), 155),
                'canonical'   => canonical('/calculadoras/' . $calc->slug()),
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function calcular(array $params): void
    {
        $calc = CalculatorRegistry::obtenerPorSlug((string) ($params['slug'] ?? ''));
        if ($calc === null) {
            Response::json(['ok' => false, 'error' => 'Calculadora no encontrada.'], 404);
        }
        if (!RateLimit::check('calc', 60, 3600)) {
            Response::json(['ok' => false, 'error' => 'Límite de cálculos por hora alcanzado. Intenta más tarde.'], 429);
        }

        $entrada = Request::json();
        if ($entrada === []) {
            $entrada = $_POST;
        }

        try {
            $resultado = $calc->calcular($entrada);
        } catch (\InvalidArgumentException $e) {
            Database::run(
                'INSERT INTO calculator_runs (calculadora, input_json, exito, ip) VALUES (?, ?, 0, ?)',
                [$calc->slug(), json_encode($entrada, JSON_UNESCAPED_UNICODE), Request::ip()]
            );
            Response::json(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Calculadora ' . $calc->slug() . ': ' . $e->getMessage());
            Response::json(['ok' => false, 'error' => 'No pudimos procesar los datos. Revisa los valores e intenta de nuevo.'], 500);
        }

        Database::run(
            'INSERT INTO calculator_runs (calculadora, input_json, exito, ip) VALUES (?, ?, 1, ?)',
            [$calc->slug(), json_encode($entrada, JSON_UNESCAPED_UNICODE), Request::ip()]
        );

        Response::json(['ok' => true, 'calculadora' => $calc->slug(), 'resultado' => $resultado]);
    }
}
