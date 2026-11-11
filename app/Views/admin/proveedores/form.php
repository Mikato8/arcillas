<?php /** Admin · form proveedor. */
$p = $proveedor;
?>
<form class="form-admin" method="post" action="<?= url('/admin/proveedores/guardar') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
  <div class="admin-panel">
    <h2><?= (int) $p['id'] > 0 ? 'Editar proveedor' : 'Nuevo proveedor' ?></h2>
    <div class="fila-campos">
      <div class="campo"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" type="text" value="<?= e($p['nombre']) ?>" required></div>
      <div class="campo"><label for="slug">Slug</label><input id="slug" name="slug" type="text" value="<?= e($p['slug']) ?>"></div>
      <div class="campo"><label for="tipo">Tipo</label>
        <select id="tipo" name="tipo"><?php foreach (['mina' => 'Mina', 'planta' => 'Planta', 'distribuidor' => 'Distribuidor', 'broker' => 'Broker'] as $k => $v): ?><option value="<?= $k ?>" <?= $p['tipo'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
      </div>
      <div class="campo"><label for="estado">Estado</label><input id="estado" name="estado" type="text" value="<?= e((string) $p['estado']) ?>"></div>
      <div class="campo"><label for="ciudad">Ciudad</label><input id="ciudad" name="ciudad" type="text" value="<?= e((string) $p['ciudad']) ?>"></div>
      <div class="campo"><label for="pais">País</label><input id="pais" name="pais" type="text" value="<?= e($p['pais']) ?>"></div>
      <div class="campo"><label for="capacidad">Capacidad</label><input id="capacidad" name="capacidad" type="text" value="<?= e((string) $p['capacidad']) ?>"></div>
      <div class="campo"><label for="certificaciones">Certificaciones</label><input id="certificaciones" name="certificaciones" type="text" value="<?= e((string) $p['certificaciones']) ?>"></div>
      <div class="campo"><label for="sitio_web">Sitio web</label><input id="sitio_web" name="sitio_web" type="url" value="<?= e((string) $p['sitio_web']) ?>"></div>
      <div class="campo"><label for="email_publico">Correo público</label><input id="email_publico" name="email_publico" type="email" value="<?= e((string) $p['email_publico']) ?>"></div>
      <div class="campo"><label for="telefono_publico">Teléfono público</label><input id="telefono_publico" name="telefono_publico" type="text" value="<?= e((string) $p['telefono_publico']) ?>"></div>
      <div class="campo">
        <label>Estado</label>
        <label class="check"><input type="checkbox" name="verificado" value="1" <?= (int) $p['verificado'] === 1 ? 'checked' : '' ?>> Proveedor verificado</label>
        <label class="check"><input type="checkbox" name="es_demo" value="1" <?= (int) $p['es_demo'] === 1 ? 'checked' : '' ?>> Dato demo</label>
        <label class="check"><input type="checkbox" name="activo" value="1" <?= (int) $p['activo'] === 1 ? 'checked' : '' ?>> Activo</label>
      </div>
      <div class="campo campo--completo"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" rows="4"><?= e((string) $p['descripcion']) ?></textarea></div>
    </div>
  </div>
  <div class="fila">
    <button class="boton boton--primario" type="submit"><?= icon('save') ?> Guardar</button>
    <a class="boton boton--contorno" href="<?= url('/admin/proveedores') ?>">Cancelar</a>
  </div>
</form>
