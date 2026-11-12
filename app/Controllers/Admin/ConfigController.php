<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Session;
use App\Core\Settings;
use App\Services\Calculators\CalculatorRegistry;

final class ConfigController extends AdminController
{
    private const GRUPOS = [
        'Identidad' => [
            'site_nombre'      => ['Nombre del sitio', 'texto'],
            'site_descripcion' => ['Descripción SEO', 'textarea'],
            'empresa_nombre'   => ['Razón social', 'texto'],
            'empresa_corto'    => ['Nombre corto / marca', 'texto'],
            'empresa_rfc'      => ['RFC', 'texto'],
            'empresa_logo'     => ['Logo (ruta en /uploads)', 'texto'],
        ],
        'Contacto' => [
            'empresa_email'    => ['Correo de contacto', 'email'],
            'empresa_telefono' => ['Teléfono', 'texto'],
            'empresa_whatsapp' => ['WhatsApp (solo dígitos)', 'texto'],
            'empresa_direccion' => ['Dirección', 'texto'],
            'empresa_ciudad'   => ['Ciudad', 'texto'],
            'empresa_linkedin' => ['LinkedIn', 'url'],
            'empresa_google'   => ['Perfil de Google (reseñas/mapa)', 'url'],
        ],
        'Portada' => [
            'hero_titulo'    => ['Título del hero', 'texto'],
            'hero_subtitulo' => ['Subtítulo del hero', 'textarea'],
            'hero_etiqueta'  => ['Etiqueta superior', 'texto'],
        ],
        'Comercial' => [
            'moneda'          => ['Moneda por defecto', 'texto'],
            'iva_pct'         => ['IVA (%)', 'numero'],
            'respuesta_horas' => ['Tiempo de respuesta (horas hábiles)', 'texto'],
            'mail_to'         => ['Correo que recibe leads', 'email'],
        ],
    ];

    public function index(): void
    {
        $valores = Settings::all();
        $this->admin('admin/configuracion', [
            'titulo' => 'Configuración',
            'grupos' => self::GRUPOS,
            'valores' => $valores,
        ]);
    }

    public function guardar(): void
    {
        $permitidas = [];
        foreach (self::GRUPOS as $campos) {
            foreach ($campos as $clave => $_) {
                $permitidas[] = $clave;
            }
        }
        $guardadas = 0;
        foreach ($permitidas as $clave) {
            if (!array_key_exists($clave, $_POST)) {
                continue;
            }
            $valor = trim((string) $_POST[$clave]);
            Settings::set($clave, $valor, 'general');
            $guardadas++;
        }
        $this->bitacora('configuracion_guardada', 'settings', '', ['campos' => $guardadas]);
        Session::flash('ok', 'Configuración guardada (' . $guardadas . ' campos).');
        $this->redirigir('/admin/configuracion');
    }

    public function calculadoras(): void
    {
        $runs = Database::fetchAll(
            'SELECT calculadora, COUNT(*) AS total, SUM(exito) AS exitos, MAX(creado_en) AS ultima
             FROM calculator_runs GROUP BY calculadora ORDER BY total DESC'
        );
        $recientes = Database::fetchAll(
            'SELECT * FROM calculator_runs ORDER BY id DESC LIMIT 30'
        );
        $nombres = [];
        foreach (CalculatorRegistry::all() as $calc) {
            $nombres[$calc->slug()] = $calc->nombre();
        }
        $this->admin('admin/calculadoras', [
            'titulo'    => 'Calculadoras',
            'runs'      => $runs,
            'recientes' => $recientes,
            'nombres'   => $nombres,
        ]);
    }
}
