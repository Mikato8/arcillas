<?php /** Dashboard admin. */ ?>
<div class="admin-tarjetas">
  <div class="admin-tarjeta"><span>Materiales activos</span><strong><?= num($stats['materiales']) ?></strong><small><?= num($stats['inactivos']) ?> inactivos</small></div>
  <div class="admin-tarjeta"><span>Leads nuevos</span><strong><?= num($leads['nuevos']) ?></strong><small><?= num($leads['hoy']) ?> hoy · <?= num($leads['mes']) ?> en 30 días</small></div>
  <div class="admin-tarjeta"><span>Cálculos (30 d)</span><strong><?= num($stats['calculos']) ?></strong><small><?= num($stats['busquedas']) ?> búsquedas internas</small></div>
  <div class="admin-tarjeta"><span>Proveedores</span><strong><?= num($stats['proveedores']) ?></strong><small><?= num($stats['categorias']) ?> categorías activas</small></div>
</div>

<div class="admin-grid-2" style="margin-top:1.2rem">
  <div class="admin-panel">
    <div class="fila fila--separada" style="margin-bottom:.8rem">
      <h2 style="margin:0">Últimos leads</h2>
      <a class="enlace-flecha texto-pequeno" href="<?= url('/admin/leads') ?>">Ver bandeja <?= icon('arrow-right') ?></a>
    </div>
    <?php if ($recientes === []): ?>
      <p class="texto-muted texto-pequeno">Aún no hay leads. Cuando llegue una solicitud aparecerá aquí.</p>
    <?php else: ?>
      <table class="tabla-admin">
        <thead><tr><th>Fecha</th><th>Contacto</th><th>Tipo</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($recientes as $lead): ?>
          <tr>
            <td><?= e(fmt_date($lead['creado_en'], 'd/m H:i')) ?></td>
            <td><a href="<?= url('/admin/leads/' . $lead['id']) ?>"><?= e($lead['nombre']) ?></a><br><small class="texto-muted"><?= e($lead['empresa'] ?: $lead['email']) ?></small></td>
            <td><?= e(tipo_lead_label($lead['tipo'])) ?></td>
            <td><?= badge_lead($lead['estado']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="admin-panel">
    <h2>Leads por estado</h2>
    <?php foreach ($porEstado as $fila): ?>
      <div class="fila fila--separada" style="padding:.4rem 0;border-bottom:1px dashed var(--linea)">
        <span><?= badge_lead($fila['estado']) ?></span><strong><?= (int) $fila['total'] ?></strong>
      </div>
    <?php endforeach; ?>
    <?php if ($porEstado === []): ?><p class="texto-muted texto-pequeno">Sin datos todavía.</p><?php endif; ?>
  </div>
</div>

<div class="admin-grid-2" style="margin-top:1.2rem">
  <div class="admin-panel">
    <h2>Materiales más vistos</h2>
    <?php foreach ($topMateriales as $mat): ?>
      <div class="fila fila--separada" style="padding:.45rem 0;border-bottom:1px dashed var(--linea)">
        <a href="<?= url('/materiales/' . $mat['slug']) ?>" target="_blank" rel="noopener"><?= e($mat['nombre']) ?></a>
        <span class="texto-mono"><?= num($mat['vistas']) ?> vistas</span>
      </div>
    <?php endforeach; ?>
    <?php if ($topMateriales === []): ?><p class="texto-muted texto-pequeno">Sin visitas registradas.</p><?php endif; ?>
  </div>
  <div class="admin-panel">
    <h2>Búsquedas internas</h2>
    <?php if ($topBusquedas === []): ?>
      <p class="texto-muted texto-pequeno">Sin búsquedas todavía. Aquí verás qué minerales piden y no encuentran.</p>
    <?php else: ?>
      <table class="tabla-admin">
        <thead><tr><th>Término</th><th>Veces</th><th>Resultados</th></tr></thead>
        <tbody>
        <?php foreach ($topBusquedas as $b): ?>
          <tr><td><?= e($b['termino']) ?></td><td><?= (int) $b['total'] ?></td><td><?= (int) $b['resultados'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<div class="admin-panel" style="margin-top:1.2rem">
  <h2>Bitácora reciente</h2>
  <table class="tabla-admin">
    <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>IP</th></tr></thead>
    <tbody>
    <?php foreach ($ultimosLogs as $log): ?>
      <tr>
        <td><?= e(fmt_date($log['creado_en'], 'd/m/Y H:i')) ?></td>
        <td><?= e($log['usuario'] ?? 'Sistema') ?></td>
        <td><?= e($log['accion']) ?></td>
        <td><?= e(($log['entidad'] ?? '') . ($log['entidad_id'] ? ' #' . $log['entidad_id'] : '')) ?></td>
        <td class="texto-mono"><?= e($log['ip'] ?? '—') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
