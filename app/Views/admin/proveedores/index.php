<?php /** Admin · proveedores. */ ?>
<div class="admin-panel">
  <div class="fila fila--separada" style="margin-bottom:.8rem">
    <h2 style="margin:0"><?= count($proveedores) ?> proveedores</h2>
    <a class="boton boton--primario boton--sm" href="<?= url('/admin/proveedores/nuevo') ?>"><?= icon('plus') ?> Nuevo proveedor</a>
  </div>
  <table class="tabla-admin">
    <thead><tr><th>Proveedor</th><th>Tipo</th><th>Ubicación</th><th>Materiales</th><th>Verificado</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($proveedores as $prov): ?>
      <tr>
        <td><strong><?= e($prov['nombre']) ?></strong><br><small class="texto-muted"><?= e($prov['slug']) ?></small></td>
        <td><?= e(ucfirst($prov['tipo'])) ?></td>
        <td><?= e($prov['estado'] ?: 'México') ?></td>
        <td><?= (int) $prov['total'] ?></td>
        <td><?= (int) $prov['verificado'] === 1 ? '<span class="badge badge--ok">Sí</span>' : '<span class="badge badge--ghost">No</span>' ?></td>
        <td><?= (int) $prov['activo'] === 1 ? '<span class="badge badge--ok">Activo</span>' : '<span class="badge badge--ghost">Inactivo</span>' ?></td>
        <td>
          <div class="admin-acciones">
            <a class="icono-accion" href="<?= url('/admin/proveedores/' . $prov['id'] . '/editar') ?>" title="Editar"><?= icon('edit') ?></a>
            <form method="post" action="<?= url('/admin/proveedores/' . $prov['id'] . '/eliminar') ?>" style="margin:0" onsubmit="return confirm('¿Desactivar proveedor?')">
              <?= csrf_field() ?><button class="icono-accion icono-accion--peligro" type="submit" title="Desactivar"><?= icon('trash') ?></button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
