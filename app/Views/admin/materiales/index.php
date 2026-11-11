<?php /** Admin · materiales lista. */ ?>
<form class="admin-filtros" method="get" action="<?= url('/admin/materiales') ?>">
  <div class="campo"><label for="q">Buscar</label><input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Nombre o slug"></div>
  <div class="campo"><label for="categoria">Categoría</label>
    <select id="categoria" name="categoria"><option value="">Todas</option>
      <?php foreach ($categorias as $cat): ?><option value="<?= e($cat['slug']) ?>" <?= $categoria === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['nombre']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="campo"><label for="estado">Estado</label>
    <select id="estado" name="estado"><option value="">Todos</option>
      <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
      <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
    </select>
  </div>
  <button class="boton boton--primario boton--sm" type="submit"><?= icon('filter') ?> Filtrar</button>
  <a class="boton boton--contorno boton--sm" href="<?= url('/admin/materiales') ?>">Limpiar</a>
</form>

<div class="admin-panel">
  <div class="fila fila--separada" style="margin-bottom:.8rem">
    <h2 style="margin:0"><?= num($total) ?> materiales</h2>
    <a class="boton boton--primario boton--sm" href="<?= url('/admin/materiales/nuevo') ?>"><?= icon('plus') ?> Nuevo</a>
  </div>
  <div style="overflow-x:auto">
    <table class="tabla-admin">
      <thead><tr><th>Material</th><th>Categoría</th><th>Precio</th><th>Disponibilidad</th><th>Estado</th><th>Actualizado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $mat): ?>
        <tr>
          <td><a href="<?= url('/admin/materiales/' . $mat['id'] . '/editar') ?>"><strong><?= e($mat['nombre']) ?></strong></a><br><small class="texto-muted">/<?= e($mat['slug']) ?></small></td>
          <td><?= e($mat['categoria_nombre']) ?></td>
          <td><?= e(money($mat['precio_ref'], (string) $mat['moneda'])) ?></td>
          <td><?= badge_disponibilidad($mat['disponibilidad']) ?></td>
          <td><?= (int) $mat['activo'] === 1 ? '<span class="badge badge--ok">Activo</span>' : '<span class="badge badge--ghost">Inactivo</span>' ?></td>
          <td class="texto-pequeno"><?= e(fmt_date($mat['actualizado_en'], 'd/m/Y')) ?></td>
          <td>
            <div class="admin-acciones">
              <a class="icono-accion" href="<?= url('/materiales/' . $mat['slug']) ?>" target="_blank" rel="noopener" title="Ver en el sitio"><?= icon('eye') ?></a>
              <a class="icono-accion" href="<?= url('/admin/materiales/' . $mat['id'] . '/editar') ?>" title="Editar"><?= icon('edit') ?></a>
              <form method="post" action="<?= url('/admin/materiales/' . $mat['id'] . '/' . ((int) $mat['activo'] === 1 ? 'eliminar' : 'activar')) ?>" style="margin:0" onsubmit="return confirm('¿Confirma el cambio de estado del material?')">
                <?= csrf_field() ?>
                <button class="icono-accion <?= (int) $mat['activo'] === 1 ? 'icono-accion--peligro' : '' ?>" type="submit" title="<?= (int) $mat['activo'] === 1 ? 'Desactivar' : 'Activar' ?>"><?= icon((int) $mat['activo'] === 1 ? 'trash' : 'check') ?></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($items === []): ?><p class="texto-muted" style="padding:1rem 0">No hay materiales con esos filtros.</p><?php endif; ?>
  <?php $pagina = $pagina; $paginas = $paginas; require APP_BASE . '/app/Views/partials/paginacion.php'; ?>
</div>
