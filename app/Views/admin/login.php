<?php /** Login admin. */ ?>
<div class="admin-login__caja">
  <a class="marca" href="<?= url('/') ?>">
    <img class="marca__simbolo" src="<?= url('/assets/img/marca.svg') ?>" alt="" width="42" height="42">
    <span class="marca__texto"><span class="marca__nombre">Arcillas MX</span><span class="marca__bajada">Panel de administración</span></span>
  </a>
  <h1 style="font-size:1.35rem;margin-bottom:.3rem">Acceso al panel</h1>
  <p class="texto-pequeno texto-muted" style="margin-bottom:1.2rem">Gestiona materiales, proveedores y leads del directorio.</p>
  <?php if (!empty($error)): ?><div class="aviso-error" style="margin-bottom:1rem"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="<?= url('/admin/login') ?>" class="formulario">
    <?= csrf_field() ?>
    <div class="campo">
      <label for="email">Correo</label>
      <input id="email" name="email" type="email" required autocomplete="username" autofocus>
    </div>
    <div class="campo">
      <label for="password">Contraseña</label>
      <input id="password" name="password" type="password" required autocomplete="current-password">
    </div>
    <button class="boton boton--primario boton--bloque" type="submit">Entrar <?= icon('arrow-right') ?></button>
  </form>
  <p class="admin-login__nota">Acceso restringido. Los intentos fallidos se limitan por seguridad y quedan registrados en la bitácora.</p>
</div>
