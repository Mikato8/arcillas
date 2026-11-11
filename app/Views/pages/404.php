<?php /** 404. */ ?>
<section class="seccion" style="padding-block:clamp(3rem,8vw,6rem)">
  <div class="contenedor contenedor--estrecho">
    <div class="gracias" style="margin:0 auto">
      <div class="gracias__icono" style="background:var(--warn-bg);color:var(--warn)"><?= icon('search') ?></div>
      <span class="eyebrow" style="justify-content:center">Error 404</span>
      <h1 style="font-size:clamp(1.7rem,3vw,2.3rem)">No encontramos esa página</h1>
      <p class="texto-muted">Puede que el enlace haya cambiado o que el material ya no esté publicado. Prueba con el buscador o explora el catálogo.</p>
      <form action="<?= url('/materiales') ?>" method="get" class="buscador-hero" style="grid-template-columns:1fr auto;margin:1.4rem auto 0;box-shadow:none;border:1px solid var(--linea)">
        <input type="search" name="q" placeholder="Buscar mineral o material…" aria-label="Buscar">
        <button class="boton boton--primario" type="submit"><?= icon('search') ?> Buscar</button>
      </form>
      <div class="fila">
        <a class="boton boton--primario" href="<?= url('/materiales') ?>"><?= icon('layers') ?> Catálogo</a>
        <a class="boton boton--contorno" href="<?= url('/contacto') ?>">Contacto</a>
      </div>
    </div>
  </div>
</section>
