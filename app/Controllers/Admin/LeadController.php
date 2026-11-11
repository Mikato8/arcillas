<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Models\Lead;

final class LeadController extends AdminController
{
    public function index(): void
    {
        $filtros = [
            'tipo'   => (string) ($_GET['tipo'] ?? ''),
            'estado' => (string) ($_GET['estado'] ?? ''),
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'desde'  => (string) ($_GET['desde'] ?? ''),
            'hasta'  => (string) ($_GET['hasta'] ?? ''),
            'pagina' => (int) ($_GET['pagina'] ?? 1),
        ];
        $resultado = Lead::listar($filtros, 25);
        $this->admin('admin/leads/index', [
            'titulo'    => 'Leads',
            'resultado' => $resultado,
            'filtros'   => $filtros,
            'stats'     => Lead::estadisticas(),
        ]);
    }

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $lead = Lead::porId((int) ($params['id'] ?? 0));
        if ($lead === null) {
            $this->noEncontrado();
        }
        $this->admin('admin/leads/show', [
            'titulo' => 'Lead #' . $lead['id'],
            'lead'   => $lead,
            'items'  => Lead::items((int) $lead['id']),
        ]);
    }

    /** @param array<string,string> $params */
    public function actualizar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $lead = Lead::porId($id);
        if ($lead === null) {
            $this->noEncontrado();
        }
        $estado = (string) ($_POST['estado'] ?? 'nuevo');
        if (!in_array($estado, ['nuevo', 'contactado', 'cotizado', 'ganado', 'perdido', 'spam'], true)) {
            $estado = 'nuevo';
        }
        Lead::actualizar($id, [
            'estado'         => $estado,
            'notas_internas' => trim((string) ($_POST['notas_internas'] ?? '')),
            'atendido_por'   => Auth::id(),
        ]);
        $this->bitacora('lead_actualizado', 'leads', (string) $id, ['estado' => $estado]);
        Session::flash('ok', 'Lead actualizado.');
        $this->redirigir('/admin/leads/' . $id);
    }

    public function exportar(): void
    {
        $filtros = [
            'tipo'   => (string) ($_GET['tipo'] ?? ''),
            'estado' => (string) ($_GET['estado'] ?? ''),
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'desde'  => (string) ($_GET['desde'] ?? ''),
            'hasta'  => (string) ($_GET['hasta'] ?? ''),
        ];
        $leads = Lead::exportables($filtros);

        $nombre = 'leads-arcillas-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
        fputcsv($out, ['ID', 'Fecha', 'Tipo', 'Estado', 'Nombre', 'Empresa', 'Cargo', 'Email', 'Teléfono', 'WhatsApp', 'Material', 'Volumen', 'Unidad', 'Destino', 'Frecuencia', 'Especificaciones', 'Mensaje', 'Consentimiento', 'Fuente', 'IP'], ';');
        foreach ($leads as $lead) {
            fputcsv($out, [
                $lead['id'], $lead['creado_en'], $lead['tipo'], $lead['estado'], $lead['nombre'], $lead['empresa'],
                $lead['cargo'], $lead['email'], $lead['telefono'], $lead['whatsapp'], $lead['material_nombre'] ?? $lead['material_texto'],
                $lead['volumen'], $lead['unidad'], $lead['destino'], $lead['frecuencia'], $lead['especificaciones'],
                $lead['mensaje'], (int) $lead['consentimiento'] === 1 ? 'Sí' : 'No', $lead['fuente'], $lead['ip'],
            ], ';');
        }
        fclose($out);
        $this->bitacora('leads_exportados', 'leads', '', ['total' => count($leads)]);
        exit;
    }

    /** @param array<string,string> $params */
    public function eliminar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Database::delete('leads', ['id' => $id]);
        $this->bitacora('lead_eliminado', 'leads', (string) $id);
        Session::flash('ok', 'Lead eliminado definitivamente.');
        $this->redirigir('/admin/leads');
    }
}
