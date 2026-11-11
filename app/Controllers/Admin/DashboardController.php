<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Models\Lead;

final class DashboardController extends AdminController
{
    public function index(): void
    {
        $stats = [
            'materiales'   => (int) Database::scalar('SELECT COUNT(*) FROM materials WHERE activo = 1'),
            'inactivos'    => (int) Database::scalar('SELECT COUNT(*) FROM materials WHERE activo = 0'),
            'proveedores'  => (int) Database::scalar('SELECT COUNT(*) FROM suppliers WHERE activo = 1'),
            'categorias'   => (int) Database::scalar('SELECT COUNT(*) FROM categories WHERE activo = 1'),
            'calculos'     => (int) Database::scalar('SELECT COUNT(*) FROM calculator_runs WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)'),
            'busquedas'    => (int) Database::scalar('SELECT COUNT(*) FROM search_logs WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)'),
        ];
        $leads = Lead::estadisticas();
        $recientes = Lead::listar(['pagina' => 1], 6)['items'];
        $porEstado = Database::fetchAll('SELECT estado, COUNT(*) AS total FROM leads GROUP BY estado ORDER BY total DESC');
        $topMateriales = Database::fetchAll(
            'SELECT m.nombre, m.slug, m.vistas FROM materials m WHERE m.activo = 1 ORDER BY m.vistas DESC LIMIT 5'
        );
        $topBusquedas = Database::fetchAll(
            'SELECT termino, COUNT(*) AS total, MAX(resultados) AS resultados FROM search_logs
             WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 60 DAY)
             GROUP BY termino ORDER BY total DESC LIMIT 8'
        );
        $ultimosLogs = Database::fetchAll(
            'SELECT l.*, u.nombre AS usuario FROM logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 8'
        );

        $this->admin('admin/dashboard', [
            'titulo'        => 'Resumen',
            'stats'         => $stats,
            'leads'         => $leads,
            'recientes'     => $recientes,
            'porEstado'     => $porEstado,
            'topMateriales' => $topMateriales,
            'topBusquedas'  => $topBusquedas,
            'ultimosLogs'   => $ultimosLogs,
        ]);
    }
}
