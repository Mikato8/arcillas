<?php /** Admin · bandeja de leads. */ ?>
<div class="admin-tarjetas" style="margin-bottom:1.2rem">
  <div class="admin-tarjeta"><span>Total</span><strong><?= num($stats['total']) ?></strong><small>histórico</small></div>
  <div class="admin-tarjeta"><span>Nuevos</span><strong><?= num($stats['nuevos']) ?></strong><small>sin atender</small></div>
  <div class="admin-tarjeta"><span>Últimos 30 días</span><strong><?= num($stats['mes']) ?></strong><small>actividad reciente</small></div>
  <div class="admin-tarjeta"><span>Hoy</span><strong><?= num($stats['hoy']) ?></strong><small>nuevas solicitudes</small></div>
</div>

<form class="admin-filtros" method="get" action="<?= url('/admin/leads') ?>">
  <div class="campo"><label for="q">Buscar</label><input id="q" name="q" type="search" value="<?= e($filtros['q']) ?>" placeholder="Nombre, empresa, email"></div>
  <div class="campo"><label for="tipo">Tipo</label>
    <select id="tipo" name="tipo"><option value="">Todos</option>
      <?php foreach (['cotizacion', 'muestra', 'contacto', 'proveedor'] as $t): ?><option value="<?= $t ?>" <?= $filtros['tipo'] === $t ? 'selected' : '' ?>><?= tipo_lead_label($t) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="campo"><label for="estado">Estado</label>
    <select id="estado" name="estado"><option value="">Todos</option>
      <?php foreach (['nuevo', 'contactado', 'cotizado', 'ganado', 'perdido', 'spam'] as $e): ?><option value="<?= $e ?>" <?= $filtros['estado'] === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="campo"><label for="desde">Desde</label><input id="desde" name="desde" type="date" value="<?= e($filtros['desde']) ?>"></div>
  <div class="campo"><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="<?= e($filtros['hasta']) ?>"></div>
  <button class="boton boton--primario boton--sm" type="submit"><?= icon('filter') ?> Filtrar</button>
  <a class="boton boton--contorno boton--sm" href="<?= url('/admin/leads') ?>">Limpiar</a>
  <a class="boton boton--contorno boton--sm" href="<?= url('/admin/leads.csv?' . http_build_query($filtros)) ?>"><?= icon('download') ?> Exportar CSV</a>
</form>

<div class="admin-panel">
  <h2><?= num($resultado['total']) ?> resultados</h2>
  <div style="overflow-x:auto">
    <table class="tabla-admin">
      <thead><tr><th>#</th><th>Fecha</th><th>Contacto</th><th>Empresa</th><th>Tipo</th><th>Material</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($resultado['items'] as $lead): ?>
        <tr>
          <td class="texto-mono"><?= (int) $lead['id'] ?></td>
          <td class="texto-pequeno"><?= e(fmt_date($lead['creado_en'], 'd/m/Y H:i')) ?></td>
          <td><a href="<?= url('/admin/leads/' . $lead['id']) ?>"><strong><?= e($lead['nombre']) ?></strong></a><br><small class="texto-muted"><?= e($lead['email']) ?></small></td>
          <td><?= e($lead['empresa'] ?: '—') ?></td>
          <td><?= e(tipo_lead_label($lead['tipo'])) ?></td>
          <td class="texto-pequeno"><?= e($lead['material_nombre'] ?? $lead['material_texto'] ?? '—') ?></td>
          <td><?= badge_lead($lead['estado']) ?></td>
          <td><a class="icono-accion" href="<?= url('/admin/leads/' . $lead['id']) ?>" title="Abrir"><?= icon('eye') ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($resultado['items'] === []): ?><p class="texto-muted" style="padding:1rem 0">No hay leads con esos filtros.</p><?php endif; ?>
  <?php $pagina = $resultado['pagina']; $paginas = $resultado['paginas']; require APP_BASE . '/app/Views/partials/paginacion.php'; ?>
</div>
