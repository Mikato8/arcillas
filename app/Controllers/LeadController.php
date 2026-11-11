<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Lead;
use App\Models\Material;
use App\Services\Mailer;

final class LeadController extends Controller
{
    // ── Contacto general ────────────────────────────────────────────────
    public function contacto(): void
    {
        $this->formulario('contacto');
    }

    public function contactoEnviar(): void
    {
        $this->procesar('contacto', '/contacto');
    }

    // ── Soy comprador ───────────────────────────────────────────────────
    public function comprador(): void
    {
        $this->formulario('comprador');
    }

    public function compradorEnviar(): void
    {
        $this->procesar('comprador', '/soy-comprador');
    }

    // ── Soy proveedor ───────────────────────────────────────────────────
    public function proveedor(): void
    {
        $this->formulario('proveedor');
    }

    public function proveedorEnviar(): void
    {
        $this->procesar('proveedor', '/soy-proveedor');
    }

    // ── Solicitud de cotización / muestra ───────────────────────────────
    public function solicitud(): void
    {
        $this->formulario('solicitud');
    }

    public function solicitudEnviar(): void
    {
        $this->procesar('solicitud', '/solicitud');
    }

    public function gracias(): void
    {
        $tipo = (string) ($_GET['tipo'] ?? 'contacto');
        $this->view('pages/gracias', [
            'titulo' => 'Solicitud recibida',
            'tipo'   => $tipo,
            'meta'   => [
                'titulo'      => 'Solicitud recibida · Arcillas MX',
                'descripcion' => 'Recibimos tu solicitud. Un especialista de Arcillas MX te contactará en breve.',
                'noindex'     => true,
            ],
        ]);
    }

    // ── Internos ────────────────────────────────────────────────────────
    private function formulario(string $vista): void
    {
        $material = null;
        $slug = (string) ($_GET['material'] ?? '');
        if ($slug !== '') {
            $material = Material::porSlug($slug);
        }
        $items = [];
        $slugs = (string) ($_GET['items'] ?? '');
        if ($slugs !== '') {
            foreach (array_filter(array_map('trim', explode(',', $slugs))) as $itemSlug) {
                $mat = Material::porSlug($itemSlug);
                if ($mat !== null) {
                    $items[] = ['material_id' => (int) $mat['id'], 'nombre' => $mat['nombre'], 'slug' => $mat['slug']];
                }
            }
        }
        $tipos = ['contacto' => 'contacto', 'comprador' => 'cotizacion', 'solicitud' => null, 'proveedor' => 'proveedor'];
        $tipo = in_array($vista, ['solicitud'], true)
            ? (string) ($_GET['tipo'] ?? 'cotizacion')
            : ($tipos[$vista] ?? 'contacto');

        $this->view('leads/' . $vista, [
            'titulo'      => match ($vista) {
                'contacto'  => 'Contacto',
                'comprador' => 'Soy comprador',
                'proveedor' => 'Soy proveedor',
                default     => $tipo === 'muestra' ? 'Solicitar muestra' : 'Solicitar cotización',
            },
            'material'    => $material,
            'items'       => $items,
            'tipoLead'    => $tipo,
            'old'         => [],
            'errores'     => [],
            'meta'        => [
                'titulo'      => match ($vista) {
                    'contacto'  => 'Contacto · Arcillas MX',
                    'comprador' => 'Requerimiento de compra · Arcillas MX',
                    'proveedor' => 'Oferta de proveedor · Arcillas MX',
                    default     => 'Solicitud · Arcillas MX',
                },
                'descripcion' => 'Cotiza arcillas y minerales no ferrosos con proveedores verificados. Respuesta en menos de 24 horas hábiles.',
                'canonical'   => canonical('/' . ($vista === 'solicitud' ? 'solicitud' : str_replace('_', '-', $vista))),
            ],
        ]);
    }

    private function procesar(string $vista, string $url): void
    {
        $tipoMap = [
            'contacto'  => 'contacto',
            'comprador' => 'cotizacion',
            'proveedor' => 'proveedor',
            'solicitud' => (string) ($_POST['tipo'] ?? 'cotizacion'),
        ];
        $tipo = $tipoMap[$vista] ?? 'contacto';
        if (!in_array($tipo, Lead::TIPOS, true)) {
            $tipo = 'contacto';
        }

        // Honeypot: si viene lleno, se descarta silenciosamente.
        if (trim((string) ($_POST['sitio_web'] ?? '')) !== '') {
            Session::flash('ok', 'Recibimos tu mensaje.');
            $this->redirigir('/gracias?tipo=' . $tipo);
        }

        if (!RateLimit::check('lead-' . $tipo, 5, 900)) {
            Session::flash('error', 'Recibimos varias solicitudes desde tu conexión. Espera unos minutos o escríbenos por WhatsApp.');
            $this->redirigir($url);
        }

        $datos = [
            'tipo'             => $tipo,
            'nombre'           => $_POST['nombre'] ?? '',
            'empresa'          => $_POST['empresa'] ?? '',
            'cargo'            => $_POST['cargo'] ?? '',
            'email'            => $_POST['email'] ?? '',
            'telefono'         => $_POST['telefono'] ?? '',
            'whatsapp'         => $_POST['whatsapp'] ?? '',
            'material_id'      => $_POST['material_id'] ?? 0,
            'material_texto'   => $_POST['material_texto'] ?? '',
            'volumen'          => $_POST['volumen'] ?? '',
            'unidad'           => $_POST['unidad'] ?? '',
            'destino'          => $_POST['destino'] ?? '',
            'frecuencia'       => $_POST['frecuencia'] ?? '',
            'especificaciones' => $_POST['especificaciones'] ?? '',
            'mensaje'          => $_POST['mensaje'] ?? '',
            'consentimiento'   => $_POST['consentimiento'] ?? 0,
            'fuente'           => $vista . ($_POST['material_texto'] ? ' · ' . $_POST['material_texto'] : ''),
            'ip'               => Request::ip(),
            'user_agent'       => Request::userAgent(),
        ];

        if (empty($datos['material_texto']) && !empty($datos['material_id'])) {
            $mat = Material::porId((int) $datos['material_id']);
            if ($mat !== null) {
                $datos['material_texto'] = $mat['nombre'];
            }
        }

        $errores = Lead::validar($datos, $tipo);
        if ($errores !== []) {
            $material = null;
            if (!empty($datos['material_id'])) {
                $material = Material::porId((int) $datos['material_id']);
            }
            http_response_code(422);
            $this->view('leads/' . $vista, [
                'titulo'   => 'Revisa tu solicitud',
                'material' => $material,
                'tipoLead' => $tipo,
                'old'      => $datos,
                'errores'  => $errores,
                'meta'     => [
                    'titulo'      => 'Solicitud · Arcillas MX',
                    'descripcion' => 'Formulario de contacto y cotización.',
                    'canonical'   => canonical($url),
                ],
            ]);
            return;
        }

        $items = [];
        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (is_array($item) && !empty($item['material_id'])) {
                    $items[] = $item;
                }
            }
        }

        $leadId = Lead::crear($datos, $items);

        // Notificación interna + acuse al interesado (fallos no bloquean el guardado).
        $filas = [
            'Tipo'         => tipo_lead_label($tipo),
            'Nombre'       => (string) $datos['nombre'],
            'Empresa'      => (string) $datos['empresa'],
            'Cargo'        => (string) $datos['cargo'],
            'Correo'       => (string) $datos['email'],
            'Teléfono'     => (string) $datos['telefono'],
            'WhatsApp'     => (string) $datos['whatsapp'],
            'Material'     => (string) $datos['material_texto'],
            'Volumen'      => trim($datos['volumen'] . (is_numeric($datos['volumen']) ? ' ' . $datos['unidad'] : '')),
            'Destino'      => (string) $datos['destino'],
            'Frecuencia'   => (string) $datos['frecuencia'],
            'Especificaciones' => (string) $datos['especificaciones'],
            'Mensaje'      => (string) $datos['mensaje'],
            'Lead'         => '#' . $leadId,
            'IP'           => (string) $datos['ip'],
        ];
        $to = (string) \App\Core\Env::get('MAIL_TO', (string) setting('empresa_email', 'contacto@arcillas.mx'));
        Mailer::enviar(
            $to,
            '[' . empresa_corto() . '] ' . tipo_lead_label($tipo) . ' · ' . (string) $datos['empresa'],
            Mailer::plantilla('Nueva solicitud de ' . tipo_lead_label($tipo), 'Se recibió una solicitud desde el sitio web.', $filas, 'Abrir bandeja', url('/admin/leads')),
            (string) $datos['email'],
            (string) $datos['nombre']
        );
        Mailer::enviar(
            (string) $datos['email'],
            'Recibimos tu solicitud · ' . empresa_corto(),
            Mailer::plantilla(
                'Gracias, ' . (string) $datos['nombre'],
                'Recibimos tu solicitud y un especialista te contactará en menos de 24 horas hábiles. Si tu requerimiento es urgente, escríbenos por WhatsApp.',
                ['Material' => (string) $datos['material_texto'], 'Volumen' => (string) $datos['volumen'], 'Solicitud' => '#' . $leadId],
                'Escribir por WhatsApp',
                whatsapp_url('Hola, envié la solicitud #' . $leadId . ' desde arcillas.mx')
            )
        );

        Session::flash('ok', 'Recibimos tu solicitud. Un especialista te contactará en menos de 24 horas hábiles.');
        $this->redirigir('/gracias?tipo=' . $tipo);
    }

    /** Carrito de muestras: recibe JSON desde el comparador. */
    public function apiSolicitud(): void
    {
        $datos = Request::json();
        if ($datos === [] || empty($datos['email'])) {
            Response::json(['ok' => false, 'error' => 'Datos incompletos.'], 422);
        }
        if (!RateLimit::check('lead-api', 10, 900)) {
            Response::json(['ok' => false, 'error' => 'Demasiadas solicitudes. Intenta más tarde.'], 429);
        }
        $tipo = in_array(($datos['tipo'] ?? ''), Lead::TIPOS, true) ? (string) $datos['tipo'] : 'muestra';
        $lead = [
            'tipo'           => $tipo,
            'nombre'         => $datos['nombre'] ?? '',
            'empresa'        => $datos['empresa'] ?? '',
            'email'          => $datos['email'] ?? '',
            'telefono'       => $datos['telefono'] ?? '',
            'material_texto' => $datos['material_texto'] ?? '',
            'mensaje'        => $datos['mensaje'] ?? '',
            'consentimiento' => $datos['consentimiento'] ?? 0,
            'ip'             => Request::ip(),
            'user_agent'     => Request::userAgent(),
            'fuente'         => 'api',
        ];
        $errores = Lead::validar($lead, $tipo);
        if ($errores !== []) {
            Response::json(['ok' => false, 'errores' => $errores], 422);
        }
        $items = is_array($datos['items'] ?? null) ? $datos['items'] : [];
        $id = Lead::crear($lead, $items);
        Response::json(['ok' => true, 'lead' => $id, 'mensaje' => 'Solicitud registrada.']);
    }
}
