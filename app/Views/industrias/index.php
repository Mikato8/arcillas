<?php /** Índice de industrias. */ ?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta"><a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span>Industrias</span></nav>
</div>
<section class="seccion seccion--ajustada" style="padding-top:1.2rem">
  <div class="contenedor">
    <div class="encabezado-seccion">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Aplicaciones</span>
        <h2>Minerales por industria</h2>
        <p>Cada proceso pide una especificación distinta. Elige tu sector para ver los materiales que mejor se adaptan a tu operación.</p>
      </div>
    </div>
    <div class="grid grid--3">
      <?php foreach ($industrias as $ind): ?>
        <a class="tarjeta tarjeta--enlace tarjeta-industria" href="<?= url('/industrias/' . $ind['slug']) ?>" style="padding:1.4rem">
          <span class="tarjeta-industria__icono" style="width:50px;height:50px"><?= icon(industria_icono($ind['slug'])) ?></span>
          <span>
            <h3 style="font-size:1.1rem"><?= e($ind['nombre']) ?></h3>
            <p><?= e(truncate((string) $ind['descripcion'], 130)) ?></p>
            <span class="tarjeta-industria__total"><?= (int) $ind['total'] ?> materiales disponibles</span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
