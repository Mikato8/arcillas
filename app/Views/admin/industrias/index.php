<?php /** Admin · industrias. */ ?>
<div class="admin-panel">
  <div class="fila fila--separada" style="margin-bottom:.8rem">
    <h2 style="margin:0"><?= count($industrias) ?> industrias</h2>
    <a class="boton boton--primario boton--sm" href="<?= url('/admin/industrias/nueva') ?>"><?= icon('plus') ?> Nueva industria</a>
  </div>
  <table class="tabla-admin">
    <thead><tr><th>Nombre</th><th>Slug</th><th>Materiales</th><th>Orden</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($industrias as $ind): ?>
      <tr>
        <td><strong><?= e($ind['nombre']) ?></strong></td>
        <td class="texto-mono"><?= e($ind['slug']) ?></td>
        <td><?= (int) $ind['total'] ?></td>
        <td><?= (int) $ind['orden'] ?></td>
        <td><?= (int) $ind['activo'] === 1 ? '<span class="badge badge--ok">Activa</span>' : '<span class="badge badge--ghost">Inactiva</span>' ?></td>
        <td>
          <div class="admin-acciones">
            <a class="icono-accion" href="<?= url('/admin/industrias/' . $ind['id'] . '/editar') ?>" title="Editar"><?= icon('edit') ?></a>
            <form method="post" action="<?= url('/admin/industrias/' . $ind['id'] . '/eliminar') ?>" style="margin:0" onsubmit="return confirm('¿Desactivar industria?')">
              <?= csrf_field() ?><button class="icono-accion icono-accion--peligro" type="submit" title="Desactivar"><?= icon('trash') ?></button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
