<?php /** Admin · categorías. */ ?>
<div class="admin-panel">
  <div class="fila fila--separada" style="margin-bottom:.8rem">
    <h2 style="margin:0"><?= count($categorias) ?> categorías</h2>
    <a class="boton boton--primario boton--sm" href="<?= url('/admin/categorias/nueva') ?>"><?= icon('plus') ?> Nueva categoría</a>
  </div>
  <table class="tabla-admin">
    <thead><tr><th>Nombre</th><th>Slug</th><th>Familia</th><th>Materiales</th><th>Orden</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($categorias as $cat): ?>
      <tr>
        <td><strong><?= e($cat['nombre']) ?></strong></td>
        <td class="texto-mono"><?= e($cat['slug']) ?></td>
        <td><?= e($cat['familia']) ?></td>
        <td><?= (int) $cat['total'] ?></td>
        <td><?= (int) $cat['orden'] ?></td>
        <td><?= (int) $cat['activo'] === 1 ? '<span class="badge badge--ok">Activa</span>' : '<span class="badge badge--ghost">Inactiva</span>' ?></td>
        <td>
          <div class="admin-acciones">
            <a class="icono-accion" href="<?= url('/admin/categorias/' . $cat['id'] . '/editar') ?>" title="Editar"><?= icon('edit') ?></a>
            <form method="post" action="<?= url('/admin/categorias/' . $cat['id'] . '/eliminar') ?>" style="margin:0" onsubmit="return confirm('¿Eliminar o desactivar la categoría?')">
              <?= csrf_field() ?><button class="icono-accion icono-accion--peligro" type="submit" title="Eliminar"><?= icon('trash') ?></button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
