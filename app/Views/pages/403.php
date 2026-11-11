<?php /** 403. */ ?>
<section class="seccion">
  <div class="contenedor contenedor--estrecho">
    <div class="gracias" style="margin:0 auto">
      <div class="gracias__icono" style="background:var(--warn-bg);color:var(--warn)"><?= icon('shield') ?></div>
      <span class="eyebrow" style="justify-content:center">Error 403</span>
      <h1 style="font-size:clamp(1.7rem,3vw,2.3rem)">Acceso restringido</h1>
      <p class="texto-muted">Tu usuario no tiene permisos para esta sección. Si crees que es un error, contacta al administrador.</p>
      <div class="fila">
        <a class="boton boton--primario" href="<?= url('/admin') ?>">Ir al panel</a>
        <a class="boton boton--contorno" href="<?= url('/') ?>">Ir al sitio</a>
      </div>
    </div>
  </div>
</section>
