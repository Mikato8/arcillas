<?php /** Admin · configuración. */ ?>
<form class="form-admin" method="post" action="<?= url('/admin/configuracion') ?>">
  <?= csrf_field() ?>
  <?php foreach ($grupos as $grupo => $campos): ?>
    <div class="admin-panel">
      <h2><?= e($grupo) ?></h2>
      <div class="fila-campos">
        <?php foreach ($campos as $clave => [$etiqueta, $tipo]): ?>
          <?php $valor = (string) ($valores[$clave] ?? ''); ?>
          <div class="campo <?= in_array($tipo, ['textarea'], true) ? 'campo--completo' : '' ?>">
            <label for="cfg-<?= e($clave) ?>"><?= e($etiqueta) ?></label>
            <?php if ($tipo === 'textarea'): ?>
              <textarea id="cfg-<?= e($clave) ?>" name="<?= e($clave) ?>" rows="3"><?= e($valor) ?></textarea>
            <?php elseif ($tipo === 'booleano'): ?>
              <label class="check"><input type="checkbox" id="cfg-<?= e($clave) ?>" name="<?= e($clave) ?>" value="1" <?= $valor === '1' ? 'checked' : '' ?>> Sí</label>
            <?php else: ?>
              <input id="cfg-<?= e($clave) ?>" name="<?= e($clave) ?>" type="<?= e($tipo === 'numero' ? 'number' : $tipo) ?>" value="<?= e($valor) ?>">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="aviso-info">
    <?= icon('info') ?> Las credenciales de correo SMTP y la base de datos se configuran en el archivo <code>.env</code> del servidor, no en esta pantalla. Así se evita guardar secretos en la base de datos.
  </div>
  <div class="fila">
    <button class="boton boton--primario" type="submit"><?= icon('save') ?> Guardar configuración</button>
  </div>
</form>
