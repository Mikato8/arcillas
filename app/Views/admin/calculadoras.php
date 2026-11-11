<?php /** Admin · calculadoras (uso). */ ?>
<div class="admin-grid-2">
  <div class="admin-panel">
    <h2>Uso por calculadora</h2>
    <table class="tabla-admin">
      <thead><tr><th>Calculadora</th><th>Ejecuciones</th><th>Éxitos</th><th>Última</th></tr></thead>
      <tbody>
      <?php foreach ($runs as $run): ?>
        <tr>
          <td><a href="<?= url('/calculadoras/' . $run['calculadora']) ?>" target="_blank" rel="noopener"><?= e($nombres[$run['calculadora']] ?? $run['calculadora']) ?></a></td>
          <td><?= (int) $run['total'] ?></td>
          <td><?= (int) $run['exitos'] ?></td>
          <td class="texto-pequeno"><?= e(fmt_date($run['ultima'], 'd/m/Y H:i')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if ($runs === []): ?><p class="texto-muted texto-pequeno">Aún no hay ejecuciones registradas.</p><?php endif; ?>
  </div>
  <div class="admin-panel">
    <h2>Últimas ejecuciones</h2>
    <table class="tabla-admin">
      <thead><tr><th>Calculadora</th><th>Resultado</th><th>Fecha</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($recientes as $run): ?>
        <tr>
          <td><?= e($nombres[$run['calculadora']] ?? $run['calculadora']) ?></td>
          <td><?= (int) $run['exito'] === 1 ? '<span class="badge badge--ok">OK</span>' : '<span class="badge badge--danger">Error</span>' ?></td>
          <td class="texto-pequeno"><?= e(fmt_date($run['creado_en'], 'd/m H:i')) ?></td>
          <td class="texto-mono texto-pequeno"><?= e($run['ip'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
