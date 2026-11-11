<?php /** Admin · form categoría. */
$c = $categoria;
?>
<form class="form-admin" method="post" action="<?= url('/admin/categorias/guardar') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
  <div class="admin-panel">
    <h2><?= (int) $c['id'] > 0 ? 'Editar categoría' : 'Nueva categoría' ?></h2>
    <div class="fila-campos">
      <div class="campo"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" type="text" value="<?= e($c['nombre']) ?>" required></div>
      <div class="campo"><label for="slug">Slug</label><input id="slug" name="slug" type="text" value="<?= e($c['slug']) ?>"></div>
      <div class="campo"><label for="familia">Familia</label>
        <select id="familia" name="familia"><?php foreach (['arcillas' => 'Arcillas', 'industrial' => 'Mineral industrial', 'no_ferroso' => 'No ferroso', 'carga' => 'Carga mineral'] as $k => $v): ?><option value="<?= $k ?>" <?= $c['familia'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
      </div>
      <div class="campo"><label for="orden">Orden</label><input id="orden" name="orden" type="number" value="<?= (int) $c['orden'] ?>"></div>
      <div class="campo campo--completo"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" rows="3"><?= e((string) $c['descripcion']) ?></textarea></div>
      <div class="campo"><label class="check"><input type="checkbox" name="activo" value="1" <?= (int) $c['activo'] === 1 ? 'checked' : '' ?>> Activa</label></div>
    </div>
  </div>
  <div class="fila">
    <button class="boton boton--primario" type="submit"><?= icon('save') ?> Guardar</button>
    <a class="boton boton--contorno" href="<?= url('/admin/categorias') ?>">Cancelar</a>
  </div>
</form>
