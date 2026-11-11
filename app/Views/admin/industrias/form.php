<?php /** Admin · form industria. */
$i = $industria;
?>
<form class="form-admin" method="post" action="<?= url('/admin/industrias/guardar') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
  <div class="admin-panel">
    <h2><?= (int) $i['id'] > 0 ? 'Editar industria' : 'Nueva industria' ?></h2>
    <div class="fila-campos">
      <div class="campo"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" type="text" value="<?= e($i['nombre']) ?>" required></div>
      <div class="campo"><label for="slug">Slug</label><input id="slug" name="slug" type="text" value="<?= e($i['slug']) ?>"></div>
      <div class="campo"><label for="icono">Icono</label><input id="icono" name="icono" type="text" value="<?= e($i['icono']) ?>" placeholder="factory, flask, layers…"></div>
      <div class="campo"><label for="color">Color</label><input id="color" name="color" type="color" value="<?= e($i['color']) ?>"></div>
      <div class="campo"><label for="orden">Orden</label><input id="orden" name="orden" type="number" value="<?= (int) $i['orden'] ?>"></div>
      <div class="campo"><label class="check"><input type="checkbox" name="activo" value="1" <?= (int) $i['activo'] === 1 ? 'checked' : '' ?>> Activa</label></div>
      <div class="campo campo--completo"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" rows="3"><?= e((string) $i['descripcion']) ?></textarea></div>
    </div>
  </div>
  <div class="fila">
    <button class="boton boton--primario" type="submit"><?= icon('save') ?> Guardar</button>
    <a class="boton boton--contorno" href="<?= url('/admin/industrias') ?>">Cancelar</a>
  </div>
</form>
