<?php /** Admin · usuarios. */
$e = $editar;
?>
<form class="form-admin" method="post" action="<?= url('/admin/usuarios/guardar') ?>" style="margin-bottom:1.4rem">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) ($e['id'] ?? 0) ?>">
  <div class="admin-panel">
    <h2><?= $e ? 'Editar usuario · ' . e($e['email']) : 'Nuevo usuario' ?></h2>
    <div class="fila-campos">
      <div class="campo"><label for="nombre">Nombre</label><input id="nombre" name="nombre" type="text" value="<?= e((string) ($e['nombre'] ?? '')) ?>" required></div>
      <div class="campo"><label for="email">Correo</label><input id="email" name="email" type="email" value="<?= e((string) ($e['email'] ?? '')) ?>" required></div>
      <div class="campo"><label for="rol">Rol</label>
        <select id="rol" name="rol"><option value="editor" <?= ($e['rol'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option><option value="admin" <?= ($e['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option></select>
      </div>
      <div class="campo"><label for="password"><?= $e ? 'Nueva contraseña (opcional)' : 'Contraseña inicial' ?></label><input id="password" name="password" type="password" minlength="10" <?= $e ? '' : 'required' ?> autocomplete="new-password"><span class="ayuda">Mínimo 10 caracteres.</span></div>
      <div class="campo"><label class="check"><input type="checkbox" name="activo" value="1" <?= !$e || (int) $e['activo'] === 1 ? 'checked' : '' ?>> Activo</label></div>
    </div>
  </div>
  <div class="fila">
    <button class="boton boton--primario" type="submit"><?= icon('save') ?> Guardar usuario</button>
    <?php if ($e): ?><a class="boton boton--contorno" href="<?= url('/admin/usuarios') ?>">Cancelar edición</a><?php endif; ?>
  </div>
</form>

<div class="admin-panel">
  <h2>Usuarios del panel</h2>
  <table class="tabla-admin">
    <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Último acceso</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($usuarios as $u): ?>
      <tr>
        <td><?= e($u['nombre']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><span class="badge <?= $u['rol'] === 'admin' ? 'badge--accent' : 'badge--info' ?>"><?= e(ucfirst($u['rol'])) ?></span></td>
        <td><?= (int) $u['activo'] === 1 ? '<span class="badge badge--ok">Activo</span>' : '<span class="badge badge--ghost">Inactivo</span>' ?></td>
        <td class="texto-pequeno"><?= e(fmt_date($u['ultimo_acceso'], 'd/m/Y H:i')) ?></td>
        <td>
          <div class="admin-acciones">
            <a class="icono-accion" href="<?= url('/admin/usuarios?editar=' . $u['id']) ?>" title="Editar"><?= icon('edit') ?></a>
            <?php if ((int) $u['id'] !== (int) App\Core\Auth::id()): ?>
              <form method="post" action="<?= url('/admin/usuarios/' . $u['id'] . '/eliminar') ?>" style="margin:0" onsubmit="return confirm('¿Desactivar usuario?')">
                <?= csrf_field() ?><button class="icono-accion icono-accion--peligro" type="submit" title="Desactivar"><?= icon('trash') ?></button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
