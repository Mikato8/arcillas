<?php /** 500. */ ?>
<section class="seccion">
  <div class="contenedor contenedor--estrecho">
    <div class="gracias" style="margin:0 auto">
      <div class="gracias__icono" style="background:var(--danger-bg);color:var(--danger)"><?= icon('alert') ?></div>
      <span class="eyebrow" style="justify-content:center">Error 500</span>
      <h1 style="font-size:clamp(1.7rem,3vw,2.3rem)">Algo salió mal</h1>
      <p class="texto-muted">Estamos revisando el problema. Intenta de nuevo en un momento o escríbenos y lo resolvemos.</p>
      <?php if (!empty($mensaje)): ?>
        <div class="aviso-error" style="text-align:left;margin-top:1rem"><strong>Debug:</strong><br><?= nl2br(e((string) $mensaje)) ?></div>
      <?php endif; ?>
      <div class="fila">
        <a class="boton boton--primario" href="<?= url('/') ?>">Volver al inicio</a>
        <a class="boton boton--contorno" href="<?= url('/contacto') ?>">Contacto</a>
      </div>
    </div>
  </div>
</section>
